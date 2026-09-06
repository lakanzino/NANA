<?php
/**
 * Plugin Name: QPedia Importer 14
 * Description: بستهٔ بازبینی مبانی — فقط مقالات موجود را با همان اسلاگ به‌روز می‌کند: افزودن لینک داخلی و منابع. تاریخ انتشار و تصویر شاخص دست‌نخورده می‌ماند.
 * Version:     14.0.0
 * Author:      QPedia
 * Text Domain: qpedia-importer-14
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPI14_CPT', 'quantum_article' );
define( 'QPI14_TAX', 'quantum_category' );
define( 'QPI14_DIR', plugin_dir_path( __FILE__ ) );

/* ---------------------------------------------------------------- منو */

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Importer 14',
		'QPedia Importer 14',
		'manage_options',
		'qpedia-importer-14',
		'qpi14_render_page'
	);
} );

/* ------------------------------------------------------- خواندن داده */

function qpi14_load_data() {
	$file = QPI14_DIR . 'data/articles.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpi14_nofile', 'فایل data/articles.json پیدا نشد.' );
	}
	$raw = file_get_contents( $file );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['articles'] ) ) {
		return new WP_Error( 'qpi14_badjson', 'ساختار JSON نامعتبر است یا آرایهٔ articles خالی است.' );
	}
	return $data;
}

/* ----------------------------------------- ساخت/یافتن دسته و سردسته */

function qpi14_ensure_terms( $categories ) {
	$map = array();   // slug => term_id
	if ( empty( $categories ) ) { return $map; }

	// دو پاس: اول والدها، بعد فرزندها — تا parent همیشه موجود باشد.
	foreach ( array( '', 'child' ) as $pass ) {
		foreach ( $categories as $cat ) {
			$slug   = sanitize_title( $cat['slug'] );
			$parent = isset( $cat['parent'] ) ? sanitize_title( $cat['parent'] ) : '';
			if ( '' === $pass && $parent ) { continue; }   // پاس اول فقط ریشه‌ها
			if ( 'child' === $pass && ! $parent ) { continue; }

			$term = get_term_by( 'slug', $slug, QPI14_TAX );
			if ( $term && ! is_wp_error( $term ) ) {
				$map[ $slug ] = (int) $term->term_id;
				continue;
			}
			$args = array( 'slug' => $slug );
			if ( $parent && isset( $map[ $parent ] ) ) {
				$args['parent'] = $map[ $parent ];
			}
			$new = wp_insert_term( $cat['name'], QPI14_TAX, $args );
			if ( ! is_wp_error( $new ) ) {
				$map[ $slug ] = (int) $new['term_id'];
			}
		}
	}
	return $map;
}

/**
 * زنجیرهٔ دسته را برمی‌گرداند: زیردستهٔ برگ + همهٔ والدهایش.
 * تا مقاله هم زیر «رایانش کوانتومی» بیاید هم زیر «فناوری و کاربردها».
 */
function qpi14_term_chain( $leaf_slug, $map ) {
	$ids  = array();
	$term = get_term_by( 'slug', sanitize_title( $leaf_slug ), QPI14_TAX );
	if ( ! $term || is_wp_error( $term ) ) { return $ids; }
	$ids[] = (int) $term->term_id;
	$p = (int) $term->parent;
	$guard = 0;
	while ( $p && $guard++ < 10 ) {
		$ids[] = $p;
		$pt = get_term( $p, QPI14_TAX );
		$p  = ( $pt && ! is_wp_error( $pt ) ) ? (int) $pt->parent : 0;
	}
	return array_values( array_unique( $ids ) );
}

/* ------------------------------------------------------ ایمپورت یکی */

/**
 * نسخهٔ ۴ — نگاشت نام فارسی نویسنده به شناسهٔ کاربر وردپرس.
 * اگر کاربری با آن display_name وجود نداشته باشد، صفر برمی‌گرداند و
 * نام فقط در متای qpedia_author باقی می‌ماند (بدون خطا).
 */
function qpi14_resolve_author( $name ) {
	$name = trim( wp_strip_all_tags( $name ) );
	if ( '' === $name ) { return 0; }

	$cache = wp_cache_get( 'qpi14_authors', 'qpedia' );
	if ( ! is_array( $cache ) ) {
		$cache = array();
		foreach ( get_users( array( 'fields' => array( 'ID', 'display_name' ) ) ) as $u ) {
			$cache[ $u->display_name ] = (int) $u->ID;
		}
		wp_cache_set( 'qpi14_authors', $cache, 'qpedia', 300 );
	}
	if ( isset( $cache[ $name ] ) ) { return $cache[ $name ]; }

	// تطبیق نرم: حذف پیشوند «مهندس » و مقایسهٔ دوباره.
	$soft = trim( preg_replace( '/^مهندس\s+/u', '', $name ) );
	foreach ( $cache as $dn => $id ) {
		if ( trim( preg_replace( '/^مهندس\s+/u', '', $dn ) ) === $soft ) { return $id; }
	}
	return 0;
}

function qpi14_import_article( $a, $map, $opts ) {
	$slug = sanitize_title( $a['slug'] );
	if ( ! $slug || empty( $a['title'] ) ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'اسلاگ یا عنوان خالی' );
	}

	$existing = get_page_by_path( $slug, OBJECT, QPI14_CPT );

	/*
	 * نسخهٔ ۹ — حالت «فقط بازبینی».
	 * این بسته برای مقالات موجود است. اگر اسلاگ پیدا نشد یعنی جایی اشتباه شده،
	 * پس به‌جای ساختن مقالهٔ تکراری، رد می‌کنیم و هشدار می‌دهیم.
	 */
	if ( ! $existing ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'این اسلاگ در سایت نیست — برای ایمنی چیزی ساخته نشد.' );
	}
	if ( empty( $opts['update'] ) ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'به‌روزرسانی خاموش است', 'id' => $existing->ID );
	}

	$postarr = array(
		'post_type'    => QPI14_CPT,
		'post_title'   => wp_strip_all_tags( $a['title'] ),
		'post_name'    => $slug,
		'post_excerpt' => isset( $a['excerpt'] ) ? wp_strip_all_tags( $a['excerpt'] ) : '',
		'post_content' => isset( $a['html'] ) ? $a['html'] : '',
		'post_status'  => ! empty( $opts['draft'] ) ? 'draft' : $existing->post_status,
		// تاریخ انتشار اصلی حفظ می‌شود تا ترتیب «تازه‌ترین‌ها» به هم نریزد.
		'post_date'     => $existing->post_date,
		'post_date_gmt' => $existing->post_date_gmt,
		'edit_date'     => true,
	);
	if ( ! empty( $a['author_id'] ) ) { $postarr['post_author'] = (int) $a['author_id']; }

	// نسخهٔ ۴: تطبیق نام فارسی نویسنده با کاربر وردپرس (در صورت وجود).
	if ( empty( $postarr['post_author'] ) && ! empty( $a['meta']['author'] ) ) {
		$uid = qpi14_resolve_author( $a['meta']['author'] );
		if ( $uid ) { $postarr['post_author'] = $uid; }
	}

	if ( $existing ) { $postarr['ID'] = $existing->ID; }

	// اجازهٔ HTML خام (kses را برای این عملیات کنار می‌گذاریم)
	kses_remove_filters();
	$id = $existing ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
	kses_init_filters();

	if ( is_wp_error( $id ) ) {
		return array( 'slug' => $slug, 'status' => 'error', 'msg' => $id->get_error_message() );
	}

	/*
	 * دسته‌بندی: در حالت بازبینی دست نمی‌خورد.
	 * فقط اگر مقاله به هیچ دسته‌ای وصل نباشد، دستهٔ پیشنهادی اعمال می‌شود.
	 */
	if ( ! empty( $a['category'] ) ) {
		$current = wp_get_object_terms( $id, QPI14_TAX, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $current ) || empty( $current ) ) {
			$ids = qpi14_term_chain( $a['category'], $map );
			if ( $ids ) { wp_set_object_terms( $id, $ids, QPI14_TAX, false ); }
		}
	}

	// متای اختیاری — متای موجود بازنویسی نمی‌شود (تصویر شاخص و سئو دست‌نخورده).
	if ( ! empty( $a['meta'] ) && is_array( $a['meta'] ) ) {
		foreach ( $a['meta'] as $k => $v ) {
			$key = sanitize_key( $k );
			if ( '' === get_post_meta( $id, $key, true ) ) {
				update_post_meta( $id, $key, $v );
			}
		}
	}

	return array(
		'slug'   => $slug,
		'status' => $existing ? 'updated' : 'created',
		'msg'    => get_permalink( $id ),
		'id'     => $id,
	);
}

/* ------------------------------------------------------ صفحهٔ ادمین */

function qpi14_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی مجاز نیست.' ); }

	$data = qpi14_load_data();
	echo '<div class="wrap" dir="rtl"><h1>QPedia Importer 14</h1>';

	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	$results = array();
	if ( isset( $_POST['qpi14_run'] ) && check_admin_referer( 'qpi14_import' ) ) {
		$opts = array(
			'update' => ! empty( $_POST['qpi14_update'] ),
			'draft'  => ! empty( $_POST['qpi14_draft'] ),
		);
		$map = qpi14_ensure_terms( isset( $data['categories'] ) ? $data['categories'] : array() );
		foreach ( $data['articles'] as $a ) {
			$results[] = qpi14_import_article( $a, $map, $opts );
		}
	}

	printf(
		'<p>آمادهٔ ایمپورت: <strong>%d</strong> مقاله · <strong>%d</strong> دسته/زیردسته · نوع پست: <code>%s</code></p>',
		count( $data['articles'] ),
		isset( $data['categories'] ) ? count( $data['categories'] ) : 0,
		esc_html( QPI14_CPT )
	);

	echo '<table class="widefat striped" style="max-width:1000px"><thead><tr>
		<th>#</th><th>عنوان</th><th>اسلاگ</th><th>دسته (برگ)</th><th>چکیده</th></tr></thead><tbody>';
	$i = 0;
	foreach ( $data['articles'] as $a ) {
		$i++;
		printf(
			'<tr><td>%d</td><td>%s</td><td><code>%s</code></td><td><code>%s</code></td><td>%s</td></tr>',
			$i,
			esc_html( $a['title'] ),
			esc_html( $a['slug'] ),
			esc_html( isset( $a['category'] ) ? $a['category'] : '—' ),
			esc_html( mb_substr( isset( $a['excerpt'] ) ? $a['excerpt'] : '', 0, 90 ) )
		);
	}
	echo '</tbody></table>';

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpi14_import' );
	echo '<p><label><input type="checkbox" name="qpi14_update" value="1" checked> به‌روزرسانی مقالات موجود (این بسته فقط همین کار را می‌کند)</label></p>';
	echo '<p><label><input type="checkbox" name="qpi14_draft" value="1"> ایمپورت به‌صورت پیش‌نویس (به‌جای انتشار)</label></p>';
	submit_button( '▶ اجرای بازبینی', 'primary', 'qpi14_run' );
	echo '</form>';

	if ( $results ) {
		echo '<h2>نتیجهٔ ایمپورت</h2><table class="widefat striped" style="max-width:1000px"><thead><tr>
			<th>اسلاگ</th><th>وضعیت</th><th>توضیح</th></tr></thead><tbody>';
		$c = array( 'created' => 0, 'updated' => 0, 'skip' => 0, 'error' => 0 );
		foreach ( $results as $r ) {
			$c[ $r['status'] ] = isset( $c[ $r['status'] ] ) ? $c[ $r['status'] ] + 1 : 1;
			$color = array( 'created' => '#0a0', 'updated' => '#06c', 'skip' => '#888', 'error' => '#c00' );
			printf(
				'<tr><td><code>%s</code></td><td style="color:%s;font-weight:600">%s</td><td>%s</td></tr>',
				esc_html( $r['slug'] ),
				esc_attr( isset( $color[ $r['status'] ] ) ? $color[ $r['status'] ] : '#000' ),
				esc_html( $r['status'] ),
				esc_html( $r['msg'] )
			);
		}
		echo '</tbody></table>';
		printf(
			'<p><strong>ساخته‌شده: %d · به‌روزشده: %d · رد‌شده: %d · خطا: %d</strong></p>',
			$c['created'], $c['updated'], $c['skip'], $c['error']
		);
	}

	echo '</div>';
}

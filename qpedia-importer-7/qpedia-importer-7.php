<?php
/**
 * Plugin Name: QPedia Importer 7
 * Description: ایمپورتر مقالات کیوپدیا — انتشار خودکار مقالات از فایل JSON با اسلاگ، چکیده، دسته و سردسته.
 * Version:     7.0.0
 * Author:      QPedia
 * Text Domain: qpedia-importer-7
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPI7_CPT', 'quantum_article' );
define( 'QPI7_TAX', 'quantum_category' );
define( 'QPI7_DIR', plugin_dir_path( __FILE__ ) );

/* ---------------------------------------------------------------- منو */

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Importer 7',
		'QPedia Importer 7',
		'manage_options',
		'qpedia-importer-7',
		'qpi7_render_page'
	);
} );

/* ------------------------------------------------------- خواندن داده */

function qpi7_load_data() {
	$file = QPI7_DIR . 'data/articles.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpi7_nofile', 'فایل data/articles.json پیدا نشد.' );
	}
	$raw = file_get_contents( $file );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['articles'] ) ) {
		return new WP_Error( 'qpi7_badjson', 'ساختار JSON نامعتبر است یا آرایهٔ articles خالی است.' );
	}
	return $data;
}

/* ----------------------------------------- ساخت/یافتن دسته و سردسته */

function qpi7_ensure_terms( $categories ) {
	$map = array();   // slug => term_id
	if ( empty( $categories ) ) { return $map; }

	// دو پاس: اول والدها، بعد فرزندها — تا parent همیشه موجود باشد.
	foreach ( array( '', 'child' ) as $pass ) {
		foreach ( $categories as $cat ) {
			$slug   = sanitize_title( $cat['slug'] );
			$parent = isset( $cat['parent'] ) ? sanitize_title( $cat['parent'] ) : '';
			if ( '' === $pass && $parent ) { continue; }   // پاس اول فقط ریشه‌ها
			if ( 'child' === $pass && ! $parent ) { continue; }

			$term = get_term_by( 'slug', $slug, QPI7_TAX );
			if ( $term && ! is_wp_error( $term ) ) {
				$map[ $slug ] = (int) $term->term_id;
				continue;
			}
			$args = array( 'slug' => $slug );
			if ( $parent && isset( $map[ $parent ] ) ) {
				$args['parent'] = $map[ $parent ];
			}
			$new = wp_insert_term( $cat['name'], QPI7_TAX, $args );
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
function qpi7_term_chain( $leaf_slug, $map ) {
	$ids  = array();
	$term = get_term_by( 'slug', sanitize_title( $leaf_slug ), QPI7_TAX );
	if ( ! $term || is_wp_error( $term ) ) { return $ids; }
	$ids[] = (int) $term->term_id;
	$p = (int) $term->parent;
	$guard = 0;
	while ( $p && $guard++ < 10 ) {
		$ids[] = $p;
		$pt = get_term( $p, QPI7_TAX );
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
function qpi7_resolve_author( $name ) {
	$name = trim( wp_strip_all_tags( $name ) );
	if ( '' === $name ) { return 0; }

	$cache = wp_cache_get( 'qpi7_authors', 'qpedia' );
	if ( ! is_array( $cache ) ) {
		$cache = array();
		foreach ( get_users( array( 'fields' => array( 'ID', 'display_name' ) ) ) as $u ) {
			$cache[ $u->display_name ] = (int) $u->ID;
		}
		wp_cache_set( 'qpi7_authors', $cache, 'qpedia', 300 );
	}
	if ( isset( $cache[ $name ] ) ) { return $cache[ $name ]; }

	// تطبیق نرم: حذف پیشوند «مهندس » و مقایسهٔ دوباره.
	$soft = trim( preg_replace( '/^مهندس\s+/u', '', $name ) );
	foreach ( $cache as $dn => $id ) {
		if ( trim( preg_replace( '/^مهندس\s+/u', '', $dn ) ) === $soft ) { return $id; }
	}
	return 0;
}

function qpi7_import_article( $a, $map, $opts ) {
	$slug = sanitize_title( $a['slug'] );
	if ( ! $slug || empty( $a['title'] ) ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'اسلاگ یا عنوان خالی' );
	}

	$existing = get_page_by_path( $slug, OBJECT, QPI7_CPT );
	if ( $existing && empty( $opts['update'] ) ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'از قبل موجود است (به‌روزرسانی خاموش)', 'id' => $existing->ID );
	}

	$postarr = array(
		'post_type'    => QPI7_CPT,
		'post_title'   => wp_strip_all_tags( $a['title'] ),
		'post_name'    => $slug,
		'post_excerpt' => isset( $a['excerpt'] ) ? wp_strip_all_tags( $a['excerpt'] ) : '',
		'post_content' => isset( $a['html'] ) ? $a['html'] : '',
		'post_status'  => ! empty( $opts['draft'] ) ? 'draft' : 'publish',
	);
	if ( ! empty( $a['author_id'] ) ) { $postarr['post_author'] = (int) $a['author_id']; }

	// نسخهٔ ۴: تطبیق نام فارسی نویسنده با کاربر وردپرس (در صورت وجود).
	if ( empty( $postarr['post_author'] ) && ! empty( $a['meta']['author'] ) ) {
		$uid = qpi7_resolve_author( $a['meta']['author'] );
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

	// دسته‌ها: برگ + همهٔ والدها
	if ( ! empty( $a['category'] ) ) {
		$ids = qpi7_term_chain( $a['category'], $map );
		if ( $ids ) { wp_set_object_terms( $id, $ids, QPI7_TAX, false ); }
	}

	// متای اختیاری
	if ( ! empty( $a['meta'] ) && is_array( $a['meta'] ) ) {
		foreach ( $a['meta'] as $k => $v ) { update_post_meta( $id, sanitize_key( $k ), $v ); }
	}

	return array(
		'slug'   => $slug,
		'status' => $existing ? 'updated' : 'created',
		'msg'    => get_permalink( $id ),
		'id'     => $id,
	);
}

/* ------------------------------------------------------ صفحهٔ ادمین */

function qpi7_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی مجاز نیست.' ); }

	$data = qpi7_load_data();
	echo '<div class="wrap" dir="rtl"><h1>QPedia Importer 7</h1>';

	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	$results = array();
	if ( isset( $_POST['qpi7_run'] ) && check_admin_referer( 'qpi7_import' ) ) {
		$opts = array(
			'update' => ! empty( $_POST['qpi7_update'] ),
			'draft'  => ! empty( $_POST['qpi7_draft'] ),
		);
		$map = qpi7_ensure_terms( isset( $data['categories'] ) ? $data['categories'] : array() );
		foreach ( $data['articles'] as $a ) {
			$results[] = qpi7_import_article( $a, $map, $opts );
		}
	}

	printf(
		'<p>آمادهٔ ایمپورت: <strong>%d</strong> مقاله · <strong>%d</strong> دسته/زیردسته · نوع پست: <code>%s</code></p>',
		count( $data['articles'] ),
		isset( $data['categories'] ) ? count( $data['categories'] ) : 0,
		esc_html( QPI7_CPT )
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
	wp_nonce_field( 'qpi7_import' );
	echo '<p><label><input type="checkbox" name="qpi7_update" value="1" checked> به‌روزرسانی مقالاتی که از قبل با همین اسلاگ موجودند</label></p>';
	echo '<p><label><input type="checkbox" name="qpi7_draft" value="1"> ایمپورت به‌صورت پیش‌نویس (به‌جای انتشار)</label></p>';
	submit_button( '▶ اجرای ایمپورت', 'primary', 'qpi7_run' );
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

<?php
/**
 * Plugin Name: QPedia Importer 21
 * Description: بستهٔ ۲۵ مقالهٔ تازه — فقط پیش نویس می سازد. اسلاگ موجود را بازنویسی نمی کند.
 * Version:     21.0.0
 * Author:      QPedia
 * Text Domain: qpedia-importer-21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPI21_CPT', 'quantum_article' );
define( 'QPI21_TAX', 'quantum_category' );
define( 'QPI21_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Importer 21',
		'QPedia Importer 21',
		'manage_options',
		'qpedia-importer-21',
		'qpi21_render_page'
	);
} );

function qpi21_load_data() {
	$file = QPI21_DIR . 'data/articles.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpi21_nofile', 'فایل data/articles.json پیدا نشد.' );
	}
	$raw  = file_get_contents( $file );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['articles'] ) ) {
		return new WP_Error( 'qpi21_badjson', 'ساختار JSON نامعتبر است یا آرایهٔ articles خالی است.' );
	}
	return $data;
}

function qpi21_ensure_terms( $categories ) {
	$map = array();
	if ( empty( $categories ) ) { return $map; }

	foreach ( array( '', 'child' ) as $pass ) {
		foreach ( $categories as $cat ) {
			$slug   = sanitize_title( $cat['slug'] );
			$parent = isset( $cat['parent'] ) ? sanitize_title( $cat['parent'] ) : '';
			if ( '' === $pass && $parent ) { continue; }
			if ( 'child' === $pass && ! $parent ) { continue; }

			$term = get_term_by( 'slug', $slug, QPI21_TAX );
			if ( $term && ! is_wp_error( $term ) ) {
				$map[ $slug ] = (int) $term->term_id;
				continue;
			}
			$args = array( 'slug' => $slug );
			if ( $parent && isset( $map[ $parent ] ) ) {
				$args['parent'] = $map[ $parent ];
			}
			$new = wp_insert_term( $cat['name'], QPI21_TAX, $args );
			if ( ! is_wp_error( $new ) ) {
				$map[ $slug ] = (int) $new['term_id'];
			}
		}
	}
	return $map;
}

function qpi21_term_chain( $leaf_slug ) {
	$ids  = array();
	$term = get_term_by( 'slug', sanitize_title( $leaf_slug ), QPI21_TAX );
	if ( ! $term || is_wp_error( $term ) ) { return $ids; }
	$ids[] = (int) $term->term_id;
	$p     = (int) $term->parent;
	$guard = 0;
	while ( $p && $guard++ < 10 ) {
		$ids[] = $p;
		$pt    = get_term( $p, QPI21_TAX );
		$p     = ( $pt && ! is_wp_error( $pt ) ) ? (int) $pt->parent : 0;
	}
	return array_values( array_unique( $ids ) );
}

function qpi21_find_by_slug( $slug ) {
	$found = get_posts( array(
		'name'           => $slug,
		'post_type'      => QPI21_CPT,
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'posts_per_page' => 1,
	) );
	return $found ? $found[0] : null;
}

function qpi21_import_article( $a ) {
	$slug = sanitize_title( $a['slug'] );
	if ( ! $slug || empty( $a['title'] ) ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'اسلاگ یا عنوان خالی' );
	}

	$existing = qpi21_find_by_slug( $slug );
	if ( $existing ) {
		return array(
			'slug'   => $slug,
			'status' => 'skip',
			'msg'    => 'اسلاگ از قبل هست — برای ایمنی بازنویسی نشد.',
			'id'     => $existing->ID,
		);
	}

	$postarr = array(
		'post_type'    => QPI21_CPT,
		'post_title'   => wp_strip_all_tags( $a['title'] ),
		'post_name'    => $slug,
		'post_excerpt' => isset( $a['excerpt'] ) ? wp_strip_all_tags( $a['excerpt'] ) : '',
		'post_content' => isset( $a['html'] ) ? $a['html'] : '',
		'post_status'  => 'draft',
	);
	if ( ! empty( $a['author_id'] ) ) {
		$postarr['post_author'] = (int) $a['author_id'];
	}

	kses_remove_filters();
	$id = wp_insert_post( $postarr, true );
	kses_init_filters();

	if ( is_wp_error( $id ) ) {
		return array( 'slug' => $slug, 'status' => 'error', 'msg' => $id->get_error_message() );
	}

	if ( ! empty( $a['category'] ) ) {
		$ids = qpi21_term_chain( $a['category'] );
		if ( $ids ) {
			wp_set_object_terms( $id, $ids, QPI21_TAX, false );
		}
	}

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
		'status' => 'created',
		'msg'    => get_permalink( $id ),
		'id'     => $id,
	);
}

function qpi21_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی مجاز نیست.' );
	}

	$data = qpi21_load_data();
	echo '<div class="wrap" dir="rtl"><h1>QPedia Importer 21</h1>';

	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<div class="notice notice-info"><p>این بسته <strong>فقط پیش نویس</strong> می سازد. هیچ مقاله ای منتشر نمی شود. اگر اسلاگ از قبل در سایت باشد، رد می شود.</p></div>';

	$results = array();
	if ( isset( $_POST['qpi21_run'] ) && check_admin_referer( 'qpi21_import' ) ) {
		qpi21_ensure_terms( isset( $data['categories'] ) ? $data['categories'] : array() );
		foreach ( $data['articles'] as $a ) {
			$results[] = qpi21_import_article( $a );
		}
	}

	printf(
		'<p>آمادهٔ ورود: <strong>%d</strong> مقاله · نوع پست: <code>%s</code> · وضعیت: <code>draft</code></p>',
		count( $data['articles'] ),
		esc_html( QPI21_CPT )
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
	wp_nonce_field( 'qpi21_import' );
	submit_button( 'ساخت پیش نویس ها', 'primary', 'qpi21_run' );
	echo '</form>';

	if ( $results ) {
		echo '<h2>نتیجه</h2><table class="widefat striped" style="max-width:1000px"><thead><tr>
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
			'<p><strong>ساخته شده: %d · رد شده: %d · خطا: %d</strong></p>',
			$c['created'], $c['skip'], $c['error']
		);
		echo '<p>بعد از ورود: افزونه را حذف کنید. پیش نویس ها در فهرست مقالات با وضعیت پیش نویس هستند.</p>';
	}

	echo '</div>';
}

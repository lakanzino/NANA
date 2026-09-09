<?php
/**
 * Plugin Name: QPedia Scientists 33
 * Description: هفت زندگی نامهٔ تازه — فقط پیش نویس می سازد. اسلاگ موجود را بازنویسی نمی کند.
 * Version:     33.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-33
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS33_CPT', 'quantum_scientist' );
define( 'QPS33_DIR', plugin_dir_path( __FILE__ ) );

function qps33_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 33',
		'QPedia Scientists 33',
		'manage_options',
		'qpedia-sci-33',
		'qps33_page'
	);
} );

function qps33_load() {
	$f = QPS33_DIR . 'data/payload.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/payload.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['scientists'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps33_find( $slug ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPS33_CPT,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps33_import_one( $s ) {
	$slug  = sanitize_title( isset( $s['slug'] ) ? $s['slug'] : '' );
	$title = isset( $s['title'] ) ? $s['title'] : '';
	$html  = isset( $s['html'] ) ? $s['html'] : '';
	$meta  = isset( $s['meta'] ) ? $s['meta'] : '';

	if ( '' === $slug || '' === $title || '' === $html ) {
		return array( 'slug' => $slug, 'status' => 'skip', 'msg' => 'اسلاگ یا عنوان یا متن خالی' );
	}

	$existing = qps33_find( $slug );
	if ( $existing ) {
		return array(
			'slug'   => $slug,
			'status' => 'skip',
			'msg'    => 'اسلاگ از قبل هست — برای ایمنی بازنویسی نشد.',
			'id'     => $existing,
		);
	}

	$postarr = array(
		'post_type'    => QPS33_CPT,
		'post_title'   => wp_strip_all_tags( $title ),
		'post_name'    => $slug,
		'post_content' => $html,
		'post_status'  => 'draft',
		'post_excerpt' => wp_strip_all_tags( $meta ),
	);

	kses_remove_filters();
	$id = wp_insert_post( $postarr, true );
	kses_init_filters();

	if ( is_wp_error( $id ) ) {
		return array( 'slug' => $slug, 'status' => 'error', 'msg' => $id->get_error_message() );
	}

	if ( '' !== $meta ) {
		foreach ( qps33_meta_keys() as $k ) {
			if ( '' === get_post_meta( $id, $k, true ) ) {
				update_post_meta( $id, $k, $meta );
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

function qps33_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی مجاز نیست.' );
	}

	$data = qps33_load();
	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 33</h1>';

	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<div class="notice notice-info"><p>این بسته <strong>فقط پیش نویس</strong> می سازد. هیچ صفحه ای منتشر نمی شود. اگر اسلاگ از قبل در سایت باشد، رد می شود.</p></div>';

	$results = array();
	if ( isset( $_POST['qps33_run'] ) && check_admin_referer( 'qps33_import' ) ) {
		foreach ( $data['scientists'] as $s ) {
			$results[] = qps33_import_one( $s );
		}
	}

	printf(
		'<p>آمادهٔ ورود: <strong>%d</strong> زندگی نامه · نوع پست: <code>%s</code> · وضعیت: <code>draft</code></p>',
		count( $data['scientists'] ),
		esc_html( QPS33_CPT )
	);

	echo '<table class="widefat striped" style="max-width:1000px"><thead><tr>'
		. '<th>#</th><th>عنوان</th><th>اسلاگ</th></tr></thead><tbody>';
	$i = 0;
	foreach ( $data['scientists'] as $s ) {
		$i++;
		printf(
			'<tr><td>%d</td><td>%s</td><td><code>%s</code></td></tr>',
			$i,
			esc_html( $s['title'] ),
			esc_html( $s['slug'] )
		);
	}
	echo '</tbody></table>';

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qps33_import' );
	submit_button( 'ساخت پیش نویس ها', 'primary', 'qps33_run' );
	echo '</form>';

	if ( $results ) {
		echo '<h2>نتیجه</h2><table class="widefat striped" style="max-width:1000px"><thead><tr>'
			. '<th>اسلاگ</th><th>وضعیت</th><th>توضیح</th></tr></thead><tbody>';
		$c = array( 'created' => 0, 'skip' => 0, 'error' => 0 );
		foreach ( $results as $r ) {
			$c[ $r['status'] ] = isset( $c[ $r['status'] ] ) ? $c[ $r['status'] ] + 1 : 1;
			$color = array( 'created' => '#0a0', 'skip' => '#888', 'error' => '#c00' );
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
		echo '<p>بعد از ورود: افزونه را حذف کنید. پیش نویس ها در فهرست دانشمندان با وضعیت پیش نویس هستند. سپس Purge All در LiteSpeed.</p>';
	}

	echo '</div>';
}

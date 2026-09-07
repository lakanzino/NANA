<?php
/**
 * Plugin Name: QPedia Scientists 31
 * Description: بازنویسی صفحهٔ آلن اسپه با قالب زندگی‌نامه‌های نسل دوم سایت. CSS و HTML سفارشی حذف می‌شود. اسلاگ، تاریخ و وضعیت حفظ می‌شود.
 * Version:     31.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-31
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS31_SCI', 'quantum_scientist' );
define( 'QPS31_DIR', plugin_dir_path( __FILE__ ) );

function qps31_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 31', 'QPedia Scientists 31',
		'manage_options', 'qpedia-sci-31', 'qps31_page'
	);
} );

function qps31_load() {
	$f = QPS31_DIR . 'data/payload.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/payload.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['scientists'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps31_find( $slug ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPS31_SCI,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps31_norm( $s ) {
	$s = (string) $s;
	$s = preg_replace( '/\x{200c}/u', '', $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	return trim( $s );
}

function qps31_run( $dry = false ) {
	$data = qps31_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'done'    => array(),
		'already' => array(),
		'miss'    => array(),
		'err'     => array(),
	);

	foreach ( $data['scientists'] as $s ) {
		$slug  = sanitize_title( isset( $s['slug'] ) ? $s['slug'] : '' );
		$title = isset( $s['title'] ) ? $s['title'] : '';
		$html  = isset( $s['html'] ) ? $s['html'] : '';
		$meta  = isset( $s['meta'] ) ? $s['meta'] : '';
		if ( '' === $slug || '' === $html || '' === $title ) { continue; }

		$pid = qps31_find( $slug );
		if ( ! $pid ) {
			$log['miss'][] = $slug;
			continue;
		}

		$post = get_post( $pid );
		$same_title = qps31_norm( $post->post_title ) === qps31_norm( $title );
		$same_html  = qps31_norm( $post->post_content ) === qps31_norm( $html );
		if ( $same_title && $same_html ) {
			$log['already'][] = $slug;
			continue;
		}

		if ( $dry ) {
			$log['done'][] = $slug . ' ← ' . $title;
			continue;
		}

		$r = wp_update_post( array(
			'ID'            => $pid,
			'post_title'    => $title,
			'post_content'  => $html,
			'post_name'     => $post->post_name,
			'post_date'     => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status'   => $post->post_status,
			'edit_date'     => true,
		), true );

		if ( is_wp_error( $r ) ) {
			$log['err'][] = $slug . ' — ' . $r->get_error_message();
			continue;
		}

		if ( '' !== $meta ) {
			foreach ( qps31_meta_keys() as $k ) {
				update_post_meta( $pid, $k, $meta );
			}
		}

		$log['done'][] = $slug;
	}

	return $log;
}

function qps31_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 31</h1>';
	echo '<p>صفحهٔ <strong>آلن اسپه</strong> را با قالب زندگی‌نامه‌های نسل دوم '
		. 'بازنویسی می‌کند. CSS و HTML سفارشی حذف می‌شود. '
		. '<strong>اسلاگ، تاریخ، وضعیت و تصویر شاخص حفظ می‌شوند.</strong> '
		. 'پست جدید ساخته نمی‌شود.</p>';

	$d = qps31_load();
	if ( is_wp_error( $d ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $d->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<table class="widefat striped" style="max-width:920px"><thead><tr>'
		. '<th>اسلاگ</th><th>عنوان تازه</th></tr></thead><tbody>';
	foreach ( $d['scientists'] as $s ) {
		echo '<tr><td><code>' . esc_html( $s['slug'] ) . '</code></td><td>'
			. esc_html( $s['title'] ) . '</td></tr>';
	}
	echo '</tbody></table>';

	$act = '';
	if ( isset( $_POST['qps31_nonce'] )
		&& wp_verify_nonce( $_POST['qps31_nonce'], 'qps31_run' ) ) {
		$act = isset( $_POST['qps31_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		$log = qps31_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'done'    => array( 'بازنویسی می‌شود', '#1d7a4a' ),
				'already' => array( 'از قبل همین متن را داشت', '#8a6d00' ),
				'miss'    => array( 'پیدا نشد', '#b32d2e' ),
				'err'     => array( 'خطا', '#b32d2e' ),
			) as $k => $m ) {
				if ( empty( $log[ $k ] ) ) { continue; }
				echo '<h2 style="color:' . esc_attr( $m[1] ) . '">'
					. esc_html( $m[0] ) . ': ' . count( $log[ $k ] )
					. '</h2><ol>';
				foreach ( $log[ $k ] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qps31_run', 'qps31_nonce' );
	echo '<button type="submit" name="qps31_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرا</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">پس از اجرا افزونه را حذف کنید '
		. 'و در LiteSpeed گزینهٔ Purge All را بزنید.</p>';
	echo '</div>';
}

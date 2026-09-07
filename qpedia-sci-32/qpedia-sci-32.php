<?php
/**
 * Plugin Name: QPedia Scientists 32
 * Description: بازنویسی بیست زندگی‌نامهٔ نسل اول به قالب نسل دوم. اسلاگ، تاریخ و وضعیت حفظ می‌شود. پست جدید ساخته نمی‌شود.
 * Version:     32.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-32
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS32_SCI', 'quantum_scientist' );
define( 'QPS32_DIR', plugin_dir_path( __FILE__ ) );

function qps32_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 32', 'QPedia Scientists 32',
		'manage_options', 'qpedia-sci-32', 'qps32_page'
	);
} );

function qps32_load() {
	$f = QPS32_DIR . 'data/payload.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/payload.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['scientists'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps32_find( $slug ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPS32_SCI,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps32_norm( $s ) {
	$s = (string) $s;
	$s = preg_replace( '/\x{200c}/u', '', $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	return trim( $s );
}

function qps32_run( $dry = false ) {
	$data = qps32_load();
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

		$pid = qps32_find( $slug );
		if ( ! $pid ) {
			$log['miss'][] = $slug;
			continue;
		}

		$post = get_post( $pid );
		$same_title = qps32_norm( $post->post_title ) === qps32_norm( $title );
		$same_html  = qps32_norm( $post->post_content ) === qps32_norm( $html );
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
			foreach ( qps32_meta_keys() as $k ) {
				update_post_meta( $pid, $k, $meta );
			}
		}

		$log['done'][] = $slug;
	}

	return $log;
}

function qps32_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 32</h1>';
	echo '<p>بیست زندگی‌نامهٔ نسل اول را به <strong>قالب نسل دوم</strong> برمی‌گرداند. '
		. 'CSS و HTML سفارشی حذف می‌شود. '
		. '<strong>اسلاگ، تاریخ، وضعیت و تصویر شاخص حفظ می‌شوند.</strong> '
		. 'پست جدید ساخته نمی‌شود.</p>';

	$d = qps32_load();
	if ( is_wp_error( $d ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $d->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<p>تعداد صفحات: <strong>' . count( $d['scientists'] ) . '</strong></p>';
	echo '<table class="widefat striped" style="max-width:920px"><thead><tr>'
		. '<th>اسلاگ</th><th>عنوان تازه</th></tr></thead><tbody>';
	foreach ( $d['scientists'] as $s ) {
		echo '<tr><td><code>' . esc_html( $s['slug'] ) . '</code></td><td>'
			. esc_html( $s['title'] ) . '</td></tr>';
	}
	echo '</tbody></table>';

	$act = '';
	if ( isset( $_POST['qps32_nonce'] )
		&& wp_verify_nonce( $_POST['qps32_nonce'], 'qps32_run' ) ) {
		$act = isset( $_POST['qps32_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		$log = qps32_run( 'dry' === $act );
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
	wp_nonce_field( 'qps32_run', 'qps32_nonce' );
	echo '<button type="submit" name="qps32_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرا</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">پس از اجرا افزونه را حذف کنید '
		. 'و در LiteSpeed گزینهٔ Purge All را بزنید.</p>';
	echo '</div>';
}

<?php
/**
 * Plugin Name: QPedia Scientists 29
 * Description: عنوان ۲۳ صفحهٔ دانشمند را از نامِ خالی به الگوی «نام؛ توصیف کوتاه» به‌روز می‌کند — همان الگویی که ۳۲ صفحه از قبل دارند. محتوا، اسلاگ، تاریخ و وضعیت دست نمی‌خورند.
 * Version:     29.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-29
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS29_SCI', 'quantum_scientist' );
define( 'QPS29_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 29', 'QPedia Scientists 29',
		'manage_options', 'qpedia-sci-29', 'qps29_page'
	);
} );

function qps29_load() {
	$f = QPS29_DIR . 'data/titles.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/titles.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['scientists'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps29_find( $slug ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPS29_SCI,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps29_norm( $s ) {
	$s = (string) $s;
	$s = preg_replace( '/\x{200c}/u', '', $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	return trim( $s );
}

function qps29_run( $dry = false ) {
	$data = qps29_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'done'    => array(),
		'already' => array(),
		'skip'    => array(),
		'miss'    => array(),
	);

	foreach ( $data['scientists'] as $s ) {
		$slug = sanitize_title( isset( $s['slug'] ) ? $s['slug'] : '' );
		$from = isset( $s['from'] ) ? $s['from'] : '';
		$to   = isset( $s['to'] ) ? $s['to'] : '';
		if ( '' === $slug || '' === $to ) { continue; }

		$pid = qps29_find( $slug );
		if ( ! $pid ) {
			$log['miss'][] = $slug;
			continue;
		}

		$post = get_post( $pid );
		$cur  = qps29_norm( $post->post_title );
		$nfrom = qps29_norm( $from );
		$nto   = qps29_norm( $to );

		if ( $cur === $nto ) {
			$log['already'][] = $slug;
			continue;
		}
		if ( $cur !== $nfrom ) {
			$log['skip'][] = $slug . ' (عنوان فعلی: ' . $post->post_title . ')';
			continue;
		}

		if ( $dry ) {
			$log['done'][] = $from . ' → ' . $to;
			continue;
		}

		$r = wp_update_post( array(
			'ID'            => $pid,
			'post_title'    => $to,
			'post_name'     => $post->post_name,
			'post_date'     => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status'   => $post->post_status,
			'edit_date'     => true,
		), true );

		if ( is_wp_error( $r ) ) {
			$log['skip'][] = $slug . ' (' . $r->get_error_message() . ')';
			continue;
		}
		$log['done'][] = $from . ' → ' . $to;
	}

	return $log;
}

function qps29_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 29</h1>';
	echo '<p>عنوان <strong>۲۳ صفحهٔ دانشمند</strong> را از نامِ خالی به الگوی '
		. '«نام؛ توصیف کوتاه» به‌روز می‌کند — همان الگویی که ۳۲ صفحه از قبل دارند. '
		. '<strong>محتوا، اسلاگ، تاریخ و وضعیت دست نمی‌خورند.</strong> '
		. 'اگر عنوان فعلی با نامِ ثبت‌شده فرق داشته باشد، آن صفحه رد می‌شود.</p>';

	$d = qps29_load();
	if ( is_wp_error( $d ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $d->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<p>رکورد در فایل: <strong>' . count( $d['scientists'] ) . '</strong></p>';

	echo '<table class="widefat striped" style="max-width:920px"><thead><tr>'
		. '<th>اسلاگ</th><th>عنوان فعلی (مورد انتظار)</th><th>عنوان تازه</th>'
		. '</tr></thead><tbody>';
	foreach ( $d['scientists'] as $s ) {
		echo '<tr><td><code>' . esc_html( $s['slug'] ) . '</code></td><td>'
			. esc_html( $s['from'] ) . '</td><td>'
			. esc_html( $s['to'] ) . '</td></tr>';
	}
	echo '</tbody></table>';

	$act = '';
	if ( isset( $_POST['qps29_nonce'] )
		&& wp_verify_nonce( $_POST['qps29_nonce'], 'qps29_run' ) ) {
		$act = isset( $_POST['qps29_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		$log = qps29_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'done'    => array( 'عنوان نوشته می‌شود', '#1d7a4a' ),
				'already' => array( 'از قبل همین عنوان را داشتند', '#8a6d00' ),
				'skip'    => array( 'رد شد (عنوان دستی/متفاوت)', '#8a6d00' ),
				'miss'    => array( 'پیدا نشد', '#b32d2e' ),
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
	wp_nonce_field( 'qps29_run', 'qps29_nonce' );
	echo '<button type="submit" name="qps29_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرا</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">پس از اجرا افزونه را حذف کنید '
		. 'و در LiteSpeed گزینهٔ Purge All را بزنید.</p>';
	echo '</div>';
}

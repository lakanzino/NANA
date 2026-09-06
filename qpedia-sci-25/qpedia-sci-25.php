<?php
/**
 * Plugin Name: QPedia Scientists 25
 * Description: پانزده بند کوتاه به مقالات مرتبط اضافه می‌کند تا ۱۵ دانشمندی که نامشان در هیچ مقاله‌ای نیامده بود، لینک ورودی بگیرند. هر بند نکتهٔ علمی واقعی دارد و مستقل از لینک هم ارزش خواندن دارد.
 * Version:     25.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS25_SCI', 'quantum_scientist' );
define( 'QPS25_ART', 'quantum_article' );
define( 'QPS25_DIR', plugin_dir_path( __FILE__ ) );

function qps25_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 25', 'QPedia Scientists 25',
		'manage_options', 'qpedia-sci-25', 'qps25_page'
	);
} );

function qps25_load() {
	$f = QPS25_DIR . 'data/articles.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/articles.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps25_find( $slug, $type ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => $type,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps25_run( $dry = false ) {
	$data = qps25_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'art'  => array(), 'art_skip'  => array(),
		'miss' => array(),
	);

	/* ── بخش ۲: لینک دانشمندان در متن مقالات ── */
	foreach ( $data['articles'] as $a ) {
		$slug = sanitize_title( $a['slug'] );
		$pid  = qps25_find( $slug, QPS25_ART );
		if ( ! $pid ) { $log['miss'][] = 'مقاله: ' . $slug; continue; }

		$post = get_post( $pid );
		/* محافظ: اگر محتوای فعلی با نسخهٔ مبنا فرق دارد، دست نزن.
		   مقایسه پس از حذف تگ و فاصله انجام می‌شود. */
		$now = preg_replace( '/\s+/u', '', wp_strip_all_tags( $post->post_content ) );
		$exp = preg_replace( '/\s+/u', '', wp_strip_all_tags( $a['html'] ) );
		if ( $now !== $exp ) {
			$log['art_skip'][] = $slug . ' (محتوا تغییر کرده)';
			continue;
		}
		if ( $dry ) {
			$n = substr_count( $a['html'], '/scientist/' );
			$log['art'][] = $slug . ' → ' . $n . ' لینک';
			continue;
		}
		wp_update_post( array(
			'ID'           => $pid,
			'post_content' => $a['html'],
			'post_date'    => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status'  => $post->post_status,
			'edit_date'    => true,
		) );
		$log['art'][] = $slug;
	}

	return $log;
}

function qps25_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 25</h1>';
	echo '<p>پانزده <strong>بند کوتاه</strong> به مقالات مرتبط اضافه می‌کند '
		. 'تا ۱۵ دانشمندی که نامشان در هیچ مقاله‌ای نیامده بود لینک ورودی '
		. 'بگیرند. هر بند یک نکتهٔ علمی واقعی دارد.</p>';

	$d = qps25_load();
	if ( is_wp_error( $d ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $d->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>مقالات: <strong>' . count( $d['articles'] ) . '</strong></p>';

	$act = '';
	if ( isset( $_POST['qps25_nonce'] )
		&& wp_verify_nonce( $_POST['qps25_nonce'], 'qps25_run' ) ) {
		$act = isset( $_POST['qps25_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		@set_time_limit( 300 );
		$log = qps25_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'art'      => array( 'بند افزوده شد', '#1d7a4a' ),
				'art_skip' => array( 'مقاله رد شد', '#8a6d00' ),
				'miss'     => array( 'پیدا نشد', '#b32d2e' ),
			) as $k => $m ) {
				if ( empty( $log[ $k ] ) ) { continue; }
				echo '<h2 style="color:' . esc_attr( $m[1] ) . '">'
					. esc_html( $m[0] ) . ': ' . count( $log[ $k ] )
					. '</h2><ol style="columns:2">';
				foreach ( $log[ $k ] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qps25_run', 'qps25_nonce' );
	echo '<button type="submit" name="qps25_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرا</button>';
	echo '</form></div>';
}

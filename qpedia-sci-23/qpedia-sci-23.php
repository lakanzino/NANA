<?php
/**
 * Plugin Name: QPedia Scientists 23
 * Description: متادسکریپشن و متن جایگزین تصویر (Alt) فارسی را برای ۵۶ صفحهٔ دانشمندان طبق استاندارد گوگل می‌نویسد. محتوای هیچ صفحه‌ای تغییر نمی‌کند و مقدار پرشدهٔ موجود بازنویسی نمی‌شود.
 * Version:     23.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-23
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS23_CPT', 'quantum_scientist' );
define( 'QPS23_DIR', plugin_dir_path( __FILE__ ) );

function qps23_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 23', 'QPedia Scientists 23',
		'manage_options', 'qpedia-sci-23', 'qps23_render_page'
	);
} );

function qps23_load() {
	$f = QPS23_DIR . 'data/scientists.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'qps23_nofile', 'فایل data/scientists.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['items'] ) ) {
		return new WP_Error( 'qps23_badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps23_run( $dry = false ) {
	$data = qps23_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'meta_done' => array(), 'meta_skip' => array(),
		'alt_done'  => array(), 'alt_skip'  => array(),
		'no_thumb'  => array(), 'missing'   => array(),
	);

	foreach ( $data['items'] as $it ) {
		$slug = isset( $it['slug'] ) ? sanitize_title( $it['slug'] ) : '';
		$desc = isset( $it['meta'] ) ? trim( $it['meta'] ) : '';
		$alt  = isset( $it['alt'] ) ? trim( $it['alt'] ) : '';
		if ( '' === $slug ) { continue; }

		/* محافظ ۱: اسلاگ باید موجود باشد — هرگز پست ساخته نمی‌شود */
		$posts = get_posts( array(
			'name'             => $slug,
			'post_type'        => QPS23_CPT,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts'      => 1,
			'suppress_filters' => false,
		) );
		if ( empty( $posts ) ) { $log['missing'][] = $slug; continue; }
		$pid = (int) $posts[0]->ID;

		/* ── متادسکریپشن ── */
		if ( '' !== $desc ) {
			$filled = '';
			foreach ( qps23_meta_keys() as $k ) {
				$v = get_post_meta( $pid, $k, true );
				if ( is_string( $v ) && '' !== trim( $v ) ) { $filled = $k; break; }
			}
			/* محافظ ۲: متای پرشده بازنویسی نمی‌شود */
			if ( '' !== $filled ) {
				$log['meta_skip'][] = $slug . ' (' . $filled . ')';
			} elseif ( $dry ) {
				$log['meta_done'][] = $slug . ' → ' . mb_substr( $desc, 0, 45 ) . '…';
			} else {
				foreach ( qps23_meta_keys() as $k ) {
					update_post_meta( $pid, $k, $desc );
				}
				$log['meta_done'][] = $slug;
			}
		}

		/* ── متن جایگزین تصویر شاخص ── */
		if ( '' !== $alt ) {
			$tid = get_post_thumbnail_id( $pid );
			if ( ! $tid ) {
				$log['no_thumb'][] = $slug;
			} else {
				$cur = get_post_meta( $tid, '_wp_attachment_image_alt', true );
				/* محافظ ۳: alt معنادارِ موجود دست نمی‌خورد.
				   alt کوتاه‌تر از ۲۵ نویسه ناقص است و جایگزین می‌شود. */
				if ( is_string( $cur ) && mb_strlen( trim( $cur ) ) >= 25 ) {
					$log['alt_skip'][] = $slug;
				} elseif ( $dry ) {
					$log['alt_done'][] = $slug . ' → ' . mb_substr( $alt, 0, 45 ) . '…';
				} else {
					update_post_meta( $tid, '_wp_attachment_image_alt', $alt );
					$log['alt_done'][] = $slug;
				}
			}
		}
	}
	return $log;
}

function qps23_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 23</h1>';
	echo '<p>برای ۵۶ صفحهٔ دانشمندان: <strong>متادسکریپشن</strong> و '
		. '<strong>متن جایگزین تصویر</strong> فارسی. محتوای صفحات تغییر '
		. 'نمی‌کند و مقدار پرشدهٔ موجود بازنویسی نمی‌شود.</p>';

	$data = qps23_load();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>رکورد در فایل: <strong>' . count( $data['items'] ) . '</strong></p>';

	$act = '';
	if ( isset( $_POST['qps23_nonce'] )
		&& wp_verify_nonce( $_POST['qps23_nonce'], 'qps23_run' ) ) {
		$act = isset( $_POST['qps23_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		$log = qps23_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'meta_done' => array( 'متادسکریپشن نوشته شد', '#1d7a4a' ),
				'alt_done'  => array( 'متن جایگزین نوشته شد', '#1d7a4a' ),
				'meta_skip' => array( 'متا از قبل داشتند', '#8a6d00' ),
				'alt_skip'  => array( 'alt معنادار از قبل داشتند', '#8a6d00' ),
				'no_thumb'  => array( 'بدون تصویر شاخص', '#b32d2e' ),
				'missing'   => array( 'اسلاگ پیدا نشد', '#b32d2e' ),
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
	wp_nonce_field( 'qps23_run', 'qps23_nonce' );
	echo '<button type="submit" name="qps23_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">'
		. 'نوشتن متا و متن جایگزین</button>';
	echo '</form></div>';
}

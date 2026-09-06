<?php
/**
 * Plugin Name: QPedia Scientists 24
 * Description: بستهٔ کامل دانشمندان — متادسکریپشن و متن جایگزین فارسی برای ۵۵ صفحهٔ دانشمند، و ۱۷۱ لینک به این صفحات در ۸۵ مقاله. متن هیچ مقاله‌ای تغییر نمی‌کند؛ فقط نامی که همین حالا در متن هست لینک‌دار می‌شود.
 * Version:     24.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-24
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPS24_SCI', 'quantum_scientist' );
define( 'QPS24_ART', 'quantum_article' );
define( 'QPS24_DIR', plugin_dir_path( __FILE__ ) );

function qps24_meta_keys() {
	return array( 'rank_math_description', '_yoast_wpseo_metadesc' );
}

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Scientists 24', 'QPedia Scientists 24',
		'manage_options', 'qpedia-sci-24', 'qps24_page'
	);
} );

function qps24_load() {
	$f = QPS24_DIR . 'data/payload.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/payload.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qps24_find( $slug, $type ) {
	$p = get_posts( array(
		'name'             => $slug,
		'post_type'        => $type,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $p ) ? 0 : (int) $p[0]->ID;
}

function qps24_run( $dry = false ) {
	$data = qps24_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'meta' => array(), 'meta_skip' => array(),
		'alt'  => array(), 'alt_skip'  => array(), 'no_thumb' => array(),
		'art'  => array(), 'art_skip'  => array(),
		'miss' => array(),
	);

	/* ── بخش ۱: متا و متن جایگزین صفحات دانشمندان ── */
	foreach ( $data['scientists'] as $s ) {
		$slug = sanitize_title( $s['slug'] );
		$pid  = qps24_find( $slug, QPS24_SCI );
		if ( ! $pid ) { $log['miss'][] = 'دانشمند: ' . $slug; continue; }

		$filled = '';
		foreach ( qps24_meta_keys() as $k ) {
			$v = get_post_meta( $pid, $k, true );
			if ( is_string( $v ) && '' !== trim( $v ) ) { $filled = $k; break; }
		}
		if ( '' !== $filled ) {
			$log['meta_skip'][] = $slug;
		} elseif ( $dry ) {
			$log['meta'][] = $slug . ' → ' . mb_substr( $s['meta'], 0, 40 ) . '…';
		} else {
			foreach ( qps24_meta_keys() as $k ) {
				update_post_meta( $pid, $k, $s['meta'] );
			}
			$log['meta'][] = $slug;
		}

		$tid = get_post_thumbnail_id( $pid );
		if ( ! $tid ) {
			$log['no_thumb'][] = $slug;
		} else {
			$cur = get_post_meta( $tid, '_wp_attachment_image_alt', true );
			/* alt کوتاه‌تر از ۲۵ نویسه فقط نام است، نه توصیف تصویر */
			if ( is_string( $cur ) && mb_strlen( trim( $cur ) ) >= 25 ) {
				$log['alt_skip'][] = $slug;
			} elseif ( $dry ) {
				$log['alt'][] = $slug . ' → ' . mb_substr( $s['alt'], 0, 40 ) . '…';
			} else {
				update_post_meta( $tid, '_wp_attachment_image_alt', $s['alt'] );
				$log['alt'][] = $slug;
			}
		}
	}

	/* ── بخش ۲: لینک دانشمندان در متن مقالات ── */
	foreach ( $data['articles'] as $a ) {
		$slug = sanitize_title( $a['slug'] );
		$pid  = qps24_find( $slug, QPS24_ART );
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

function qps24_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientists 24</h1>';
	echo '<p>سه کار: <strong>متادسکریپشن</strong> و <strong>متن جایگزین</strong> '
		. 'برای صفحات دانشمندان، و <strong>لینک‌دهی</strong> به آن‌ها در متن '
		. 'مقالات. متن مقالات تغییر نمی‌کند — فقط نامی که همین حالا در متن '
		. 'هست لینک‌دار می‌شود.</p>';

	$d = qps24_load();
	if ( is_wp_error( $d ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $d->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>دانشمند: <strong>' . count( $d['scientists'] )
		. '</strong> · مقاله: <strong>' . count( $d['articles'] )
		. '</strong></p>';

	if ( ! empty( $d['redirect'] ) ) {
		echo '<div class="notice notice-warning"><p><strong>یادآوری دستی:</strong> '
			. 'صفحهٔ <code>' . esc_html( $d['redirect']['from'] ) . '</code> '
			. 'نسخهٔ تکراری است. آن را حذف کنید و یک ریدایرکت ۳۰۱ به '
			. '<code>' . esc_html( $d['redirect']['to'] ) . '</code> بسازید. '
			. 'این افزونه چیزی حذف نمی‌کند.</p></div>';
	}

	$act = '';
	if ( isset( $_POST['qps24_nonce'] )
		&& wp_verify_nonce( $_POST['qps24_nonce'], 'qps24_run' ) ) {
		$act = isset( $_POST['qps24_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		@set_time_limit( 300 );
		$log = qps24_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'meta'      => array( 'متادسکریپشن نوشته شد', '#1d7a4a' ),
				'alt'       => array( 'متن جایگزین نوشته شد', '#1d7a4a' ),
				'art'       => array( 'مقالهٔ لینک‌دار شد', '#1d7a4a' ),
				'meta_skip' => array( 'متا از قبل داشتند', '#8a6d00' ),
				'alt_skip'  => array( 'متن جایگزین معنادار داشتند', '#8a6d00' ),
				'art_skip'  => array( 'مقاله رد شد', '#8a6d00' ),
				'no_thumb'  => array( 'بدون تصویر شاخص', '#b32d2e' ),
				'miss'      => array( 'پیدا نشد', '#b32d2e' ),
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
	wp_nonce_field( 'qps24_run', 'qps24_nonce' );
	echo '<button type="submit" name="qps24_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرا</button>';
	echo '</form></div>';
}

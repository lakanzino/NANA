<?php
/**
 * Plugin Name: QPedia Meta 21
 * Description: فقط متادسکریپشن ۹۱ مقاله‌ای را که توضیح متا ندارند پر می‌کند. محتوای هیچ مقاله‌ای را تغییر نمی‌دهد. متای پرشدهٔ موجود را بازنویسی نمی‌کند.
 * Version:     21.0.0
 * Author:      QPedia
 * Text Domain: qpedia-meta-21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPM21_CPT', 'quantum_article' );
define( 'QPM21_DIR', plugin_dir_path( __FILE__ ) );

/*
 * کلیدهای متایی که در سایت استفاده می‌شوند.
 * هر دو افزونهٔ Rank Math و Yoast روی سایت داده دارند، پس هر دو
 * پر می‌شوند تا هرکدام فعال باشد نتیجه یکسان باشد.
 */
function qpm21_keys() {
	return array(
		'rank_math_description',
		'_yoast_wpseo_metadesc',
	);
}

/* ---------------------------------------------------------------- منو */

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Meta 21',
		'QPedia Meta 21',
		'manage_options',
		'qpedia-meta-21',
		'qpm21_render_page'
	);
} );

/* ------------------------------------------------------- خواندن داده */

function qpm21_load_data() {
	$file = QPM21_DIR . 'data/meta.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpm21_nofile', 'فایل data/meta.json پیدا نشد.' );
	}
	$data = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $data ) || empty( $data['items'] ) ) {
		return new WP_Error( 'qpm21_badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $data;
}

/* ------------------------------------------------------------- اجرا */

function qpm21_run( $dry_run = false ) {
	$data = qpm21_load_data();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'updated' => array(),
		'skipped' => array(),
		'missing' => array(),
	);

	foreach ( $data['items'] as $item ) {
		$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
		$desc = isset( $item['description'] ) ? trim( $item['description'] ) : '';
		if ( '' === $slug || '' === $desc ) { continue; }

		/* ── محافظ ۱: اسلاگ باید موجود باشد. هرگز پست نمی‌سازیم. ── */
		$posts = get_posts( array(
			'name'             => $slug,
			'post_type'        => QPM21_CPT,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts'      => 1,
			'suppress_filters' => false,
		) );
		if ( empty( $posts ) ) {
			$log['missing'][] = $slug;
			continue;
		}
		$post_id = (int) $posts[0]->ID;

		/* ── محافظ ۲: اگر هر کلید متایی از قبل پر است، دست نزن. ── */
		$already = '';
		foreach ( qpm21_keys() as $key ) {
			$v = get_post_meta( $post_id, $key, true );
			if ( is_string( $v ) && '' !== trim( $v ) ) {
				$already = $key;
				break;
			}
		}
		if ( '' !== $already ) {
			$log['skipped'][] = $slug . ' (' . $already . ' از قبل پر است)';
			continue;
		}

		if ( $dry_run ) {
			$log['updated'][] = $slug . ' → ' . mb_substr( $desc, 0, 60 ) . '…';
			continue;
		}

		/* ── نوشتن: فقط متا. محتوا، تاریخ و دسته دست‌نخورده. ── */
		foreach ( qpm21_keys() as $key ) {
			update_post_meta( $post_id, $key, $desc );
		}
		$log['updated'][] = $slug;
	}

	return $log;
}

/* ------------------------------------------------------------ صفحه */

function qpm21_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl">';
	echo '<h1>QPedia Meta 21</h1>';
	echo '<p>این افزونه <strong>فقط توضیح متا</strong> را برای مقالاتی که '
		. 'توضیح ندارند می‌نویسد. محتوای هیچ مقاله‌ای تغییر نمی‌کند و '
		. 'متای پرشدهٔ موجود بازنویسی نمی‌شود.</p>';

	$data = qpm21_load_data();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>تعداد رکورد در فایل: <strong>'
		. count( $data['items'] ) . '</strong></p>';

	$action = '';
	if ( isset( $_POST['qpm21_nonce'] )
		&& wp_verify_nonce( $_POST['qpm21_nonce'], 'qpm21_run' ) ) {
		$action = isset( $_POST['qpm21_dry'] ) ? 'dry' : 'run';
	}

	if ( $action ) {
		$log = qpm21_run( 'dry' === $action );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			$title = ( 'dry' === $action )
				? 'پیش‌نمایش (چیزی نوشته نشد)' : 'انجام شد';
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( $title ) . '</strong></p></div>';

			echo '<h2>نوشته‌شده: ' . count( $log['updated'] ) . '</h2>';
			if ( $log['updated'] ) {
				echo '<ol style="columns:2">';
				foreach ( $log['updated'] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}

			if ( $log['skipped'] ) {
				echo '<h2>رد شد (از قبل متا داشتند): '
					. count( $log['skipped'] ) . '</h2><ul>';
				foreach ( $log['skipped'] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ul>';
			}

			if ( $log['missing'] ) {
				echo '<h2 style="color:#b32d2e">اسلاگ پیدا نشد: '
					. count( $log['missing'] ) . '</h2><ul>';
				foreach ( $log['missing'] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ul>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpm21_run', 'qpm21_nonce' );
	echo '<button type="submit" name="qpm21_dry" value="1" '
		. 'class="button">پیش‌نمایش بدون نوشتن</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">'
		. 'نوشتن متادسکریپشن‌ها</button>';
	echo '</form>';
	echo '</div>';
}

<?php
/**
 * Plugin Name: QPedia Thumbnails 22
 * Description: ۵۱ تصویر شاخص را به کتابخانهٔ رسانه اضافه می‌کند، متن جایگزین فارسی استاندارد گوگل می‌نویسد و هر تصویر را به مقالهٔ هم‌نامش وصل می‌کند. محتوای هیچ مقاله‌ای تغییر نمی‌کند و تصویر شاخصِ موجود بازنویسی نمی‌شود.
 * Version:     22.0.0
 * Author:      QPedia
 * Text Domain: qpedia-thumbs-22
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPT22_CPT', 'quantum_article' );
define( 'QPT22_DIR', plugin_dir_path( __FILE__ ) );

/* ---------------------------------------------------------------- منو */

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Thumbnails 22',
		'QPedia Thumbnails 22',
		'manage_options',
		'qpedia-thumbs-22',
		'qpt22_render_page'
	);
} );

/* ------------------------------------------------------- خواندن داده */

function qpt22_load() {
	$file = QPT22_DIR . 'data/manifest.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpt22_nofile', 'فایل data/manifest.json پیدا نشد.' );
	}
	$data = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $data ) || empty( $data['items'] ) ) {
		return new WP_Error( 'qpt22_badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $data;
}

/* ------------------------------------------------ آپلود یک تصویر */

function qpt22_sideload( $path, $filename, $alt, $post_id, $title ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( $filename, null, file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'qpt22_upload', $upload['error'] );
	}

	$filetype = wp_check_filetype( $upload['file'], null );
	$attach_id = wp_insert_attachment( array(
		'guid'           => $upload['url'],
		'post_mime_type' => $filetype['type'],
		'post_title'     => $title,
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $upload['file'], $post_id );

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return new WP_Error( 'qpt22_attach', 'ثبت پیوست ناموفق بود.' );
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );

	/* متن جایگزین فارسی — استاندارد گوگل */
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );

	return $attach_id;
}

/* ------------------------------------------------------------- اجرا */

function qpt22_run( $dry_run = false ) {
	$data = qpt22_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'done'    => array(),
		'skipped' => array(),
		'missing' => array(),
		'errors'  => array(),
	);

	foreach ( $data['items'] as $item ) {
		$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
		$file = isset( $item['file'] ) ? basename( $item['file'] ) : '';
		$alt  = isset( $item['alt'] ) ? trim( $item['alt'] ) : '';
		$ttl  = isset( $item['title'] ) ? trim( $item['title'] ) : $slug;
		if ( '' === $slug || '' === $file ) { continue; }

		/* ── محافظ ۱: اسلاگ باید موجود باشد. هرگز پست ساخته نمی‌شود. ── */
		$posts = get_posts( array(
			'name'             => $slug,
			'post_type'        => QPT22_CPT,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts'      => 1,
			'suppress_filters' => false,
		) );
		if ( empty( $posts ) ) {
			$log['missing'][] = $slug;
			continue;
		}
		$post_id = (int) $posts[0]->ID;

		/* ── محافظ ۲: اگر تصویر شاخص دارد، دست نزن. ── */
		if ( has_post_thumbnail( $post_id ) ) {
			$log['skipped'][] = $slug . ' (تصویر شاخص از قبل دارد)';
			continue;
		}

		/* ── محافظ ۳: فایل باید در بسته باشد. ── */
		$path = QPT22_DIR . 'images/' . $file;
		if ( ! file_exists( $path ) ) {
			$log['errors'][] = $slug . ' — فایل ' . $file . ' در بسته نیست';
			continue;
		}

		if ( $dry_run ) {
			$log['done'][] = $slug . ' → ' . $file
				. ' | alt: ' . mb_substr( $alt, 0, 55 ) . '…';
			continue;
		}

		$attach_id = qpt22_sideload( $path, $file, $alt, $post_id, $ttl );
		if ( is_wp_error( $attach_id ) ) {
			$log['errors'][] = $slug . ' — ' . $attach_id->get_error_message();
			continue;
		}

		set_post_thumbnail( $post_id, $attach_id );
		$log['done'][] = $slug;
	}

	return $log;
}

/* ------------------------------------------------------------ صفحه */

function qpt22_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl">';
	echo '<h1>QPedia Thumbnails 22</h1>';
	echo '<p>۵۱ تصویر شاخص را آپلود می‌کند، <strong>متن جایگزین فارسی</strong> '
		. 'می‌نویسد و به مقالهٔ هم‌نام وصل می‌کند. محتوای مقالات تغییر نمی‌کند '
		. 'و مقاله‌ای که از قبل تصویر شاخص دارد دست نمی‌خورد.</p>';

	$data = qpt22_load();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>رکورد در فایل: <strong>' . count( $data['items'] )
		. '</strong> · تصاویر موجود در بسته: <strong>'
		. count( glob( QPT22_DIR . 'images/*.webp' ) ) . '</strong></p>';

	$action = '';
	if ( isset( $_POST['qpt22_nonce'] )
		&& wp_verify_nonce( $_POST['qpt22_nonce'], 'qpt22_run' ) ) {
		$action = isset( $_POST['qpt22_dry'] ) ? 'dry' : 'run';
	}

	if ( $action ) {
		@set_time_limit( 300 );
		$log = qpt22_run( 'dry' === $action );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			$t = ( 'dry' === $action )
				? 'پیش‌نمایش (چیزی آپلود نشد)' : 'انجام شد';
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( $t ) . '</strong></p></div>';

			foreach ( array(
				'done'    => array( 'انجام‌شده', '#1d7a4a' ),
				'skipped' => array( 'رد شد (تصویر شاخص داشتند)', '#8a6d00' ),
				'missing' => array( 'اسلاگ پیدا نشد', '#b32d2e' ),
				'errors'  => array( 'خطا', '#b32d2e' ),
			) as $key => $meta ) {
				if ( empty( $log[ $key ] ) ) { continue; }
				echo '<h2 style="color:' . esc_attr( $meta[1] ) . '">'
					. esc_html( $meta[0] ) . ': ' . count( $log[ $key ] )
					. '</h2><ol style="columns:2">';
				foreach ( $log[ $key ] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpt22_run', 'qpt22_nonce' );
	echo '<button type="submit" name="qpt22_dry" value="1" class="button">'
		. 'پیش‌نمایش بدون آپلود</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">'
		. 'آپلود و اتصال تصاویر</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">اگر تعداد تصاویر زیاد است و '
		. 'اجرا نیمه‌کاره ماند، دوباره دکمه را بزنید؛ موارد انجام‌شده '
		. 'تکرار نمی‌شوند.</p>';
	echo '</div>';
}

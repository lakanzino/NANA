<?php
/**
 * Plugin Name: QPedia Thumbnails 30
 * Description: هفت تصویر شاخص دانشمندان (بستهٔ sci-33) را آپلود و به پیش نویس quantum_scientist وصل می کند. متن صفحه تغییر نمی کند. اجرای دوباره تکراری نمی سازد.
 * Version:     30.0.0
 * Author:      QPedia
 * Text Domain: qpedia-thumbs-30
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPT30_CPT', 'quantum_scientist' );
define( 'QPT30_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Thumbnails 30',
		'QPedia Thumbnails 30',
		'manage_options',
		'qpedia-thumbs-30',
		'qpt30_page'
	);
} );

function qpt30_load() {
	$f = QPT30_DIR . 'data/manifest.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/manifest.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['items'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

function qpt30_existing_attachment( $filename ) {
	global $wpdb;
	$id = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
		'%' . $wpdb->esc_like( $filename )
	) );
	return $id ? (int) $id : 0;
}

function qpt30_sideload( $path, $filename, $alt, $post_id, $title ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( $filename, null, file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload', $upload['error'] );
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
		return new WP_Error( 'attach', 'ثبت پیوست ناموفق بود.' );
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );
	return $attach_id;
}

function qpt30_run( $dry = false ) {
	$data = qpt30_load();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array( 'done' => array(), 'skipped' => array(), 'missing' => array(), 'errors' => array() );

	foreach ( $data['items'] as $item ) {
		$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
		$file = isset( $item['file'] ) ? basename( $item['file'] ) : '';
		$alt  = isset( $item['alt'] ) ? trim( $item['alt'] ) : '';
		$ttl  = isset( $item['title'] ) ? trim( $item['title'] ) : $slug;
		if ( '' === $slug || '' === $file ) { continue; }

		$posts = get_posts( array(
			'name'             => $slug,
			'post_type'        => QPT30_CPT,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'numberposts'      => 1,
			'suppress_filters' => false,
		) );
		if ( empty( $posts ) ) {
			$log['missing'][] = $slug;
			continue;
		}
		$post_id = (int) $posts[0]->ID;

		if ( has_post_thumbnail( $post_id ) ) {
			$log['skipped'][] = $slug . ' (تصویر شاخص از قبل دارد)';
			continue;
		}

		$exist = qpt30_existing_attachment( $file );
		if ( $exist ) {
			if ( ! $dry ) {
				set_post_thumbnail( $post_id, $exist );
				if ( '' !== $alt && '' === (string) get_post_meta( $exist, '_wp_attachment_image_alt', true ) ) {
					update_post_meta( $exist, '_wp_attachment_image_alt', $alt );
				}
			}
			$log['skipped'][] = $slug . ' (فایل از قبل در رسانه است؛ دوباره آپلود نشد)';
			continue;
		}

		$path = QPT30_DIR . 'images/' . $file;
		if ( ! file_exists( $path ) ) {
			$log['errors'][] = $slug . ' — فایل در بسته نیست';
			continue;
		}

		if ( $dry ) {
			$log['done'][] = $slug . ' → ' . $file;
			continue;
		}

		$attach_id = qpt30_sideload( $path, $file, $alt, $post_id, $ttl );
		if ( is_wp_error( $attach_id ) ) {
			$log['errors'][] = $slug . ' — ' . $attach_id->get_error_message();
			continue;
		}
		set_post_thumbnail( $post_id, $attach_id );
		$log['done'][] = $slug;
	}
	return $log;
}

function qpt30_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl"><h1>QPedia Thumbnails 30</h1>';
	echo '<p>هفت تصویر شاخص دانشمندان را آپلود می کند، متن جایگزین فارسی می نویسد و به صفحهٔ هم نام وصل می کند. محتوای صفحه تغییر نمی کند. اگر تصویر شاخص یا همان فایل در رسانه باشد رد می شود.</p>';

	$data = qpt30_load();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}
	echo '<p>رکورد: <strong>' . count( $data['items'] ) . '</strong> · فایل WebP: <strong>'
		. count( glob( QPT30_DIR . 'images/*.webp' ) ) . '</strong></p>';

	$act = '';
	if ( isset( $_POST['qpt30_nonce'] ) && wp_verify_nonce( $_POST['qpt30_nonce'], 'qpt30_run' ) ) {
		$act = isset( $_POST['qpt30_dry'] ) ? 'dry' : 'run';
	}

	if ( $act ) {
		@set_time_limit( 300 );
		$log = qpt30_run( 'dry' === $act );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش نمایش (چیزی آپلود نشد)' : 'انجام شد' )
				. '</strong></p></div>';
			foreach ( array(
				'done'    => array( 'انجام شده', '#1d7a4a' ),
				'skipped' => array( 'رد شد / تکراری نبود', '#8a6d00' ),
				'missing' => array( 'اسلاگ پیدا نشد', '#b32d2e' ),
				'errors'  => array( 'خطا', '#b32d2e' ),
			) as $k => $m ) {
				if ( empty( $log[ $k ] ) ) { continue; }
				echo '<h2 style="color:' . esc_attr( $m[1] ) . '">'
					. esc_html( $m[0] ) . ': ' . count( $log[ $k ] ) . '</h2><ol>';
				foreach ( $log[ $k ] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpt30_run', 'qpt30_nonce' );
	echo '<button type="submit" name="qpt30_dry" value="1" class="button">پیش نمایش بدون آپلود</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">آپلود و اتصال تصاویر</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">پس از اجرا افزونه را حذف کنید و Purge All در LiteSpeed. پیش نیاز: ایمپورتر sci-33.</p>';
	echo '</div>';
}

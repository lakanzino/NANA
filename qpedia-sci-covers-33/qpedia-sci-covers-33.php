<?php
/**
 * Plugin Name: QPedia Scientist Covers 33
 * Description: هفت تصویر شاخص بسته sci-33 را روی اسلاگ انگلیسی اختصاصی هر زندگی نامه می نشاند، متن جایگزین استاندارد می نویسد و پس از اجرای موفق خودش را از سایت حذف می کند. پیش نویس و منتشر شده فرقی ندارد.
 * Version:     33.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-covers-33
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPSC33_CPT', 'quantum_scientist' );
define( 'QPSC33_DIR', plugin_dir_path( __FILE__ ) );
define( 'QPSC33_FILE', __FILE__ );
define( 'QPSC33_BASENAME', plugin_basename( __FILE__ ) );
define( 'QPSC33_REPORT', 'qpedia-sci-covers-33-report.txt' );
define( 'QPSC33_W', 1376 );
define( 'QPSC33_H', 768 );

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Sci Covers 33',
		'QPedia Sci Covers 33',
		'manage_options',
		'qpedia-sci-covers-33',
		'qpsc33_page'
	);
} );

/* -------------------------------------------------------------------------
 * داده بسته
 * ---------------------------------------------------------------------- */

function qpsc33_load() {
	$f = QPSC33_DIR . 'data/manifest.json';
	if ( ! file_exists( $f ) ) {
		return new WP_Error( 'nofile', 'فایل data/manifest.json پیدا نشد.' );
	}
	$d = json_decode( file_get_contents( $f ), true );
	if ( ! is_array( $d ) || empty( $d['items'] ) ) {
		return new WP_Error( 'badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $d;
}

/* -------------------------------------------------------------------------
 * پیدا کردن صفحه فقط با اسلاگ انگلیسی اختصاصی
 * ---------------------------------------------------------------------- */

function qpsc33_statuses() {
	return array( 'publish', 'draft', 'pending', 'private', 'future' );
}

function qpsc33_find_posts( $slug ) {
	$found = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPSC33_CPT,
		'post_status'      => qpsc33_statuses(),
		'numberposts'      => -1,
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'suppress_filters' => false,
	) );

	$exact = array();
	if ( is_array( $found ) ) {
		foreach ( $found as $p ) {
			if ( isset( $p->post_name ) && $slug === $p->post_name ) {
				$exact[] = $p;
			}
		}
	}
	return $exact;
}

/**
 * اگر چند صفحه با یک اسلاگ بود: اول آن که تصویر شاخص ندارد، بعد منتشر شده.
 */
function qpsc33_pick_post( $posts ) {
	$best = null;
	foreach ( $posts as $p ) {
		if ( null === $best ) {
			$best = $p;
			continue;
		}
		$p_has    = has_post_thumbnail( $p->ID );
		$best_has = has_post_thumbnail( $best->ID );
		if ( $p_has && ! $best_has ) {
			continue;
		}
		if ( ! $p_has && $best_has ) {
			$best = $p;
			continue;
		}
		if ( 'publish' === $p->post_status && 'publish' !== $best->post_status ) {
			$best = $p;
		}
	}
	return $best;
}

/* -------------------------------------------------------------------------
 * رسانه: تشخیص تکراری و آپلود
 * ---------------------------------------------------------------------- */

function qpsc33_existing_attachment( $filename ) {
	global $wpdb;
	$id = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
		'%' . $wpdb->esc_like( $filename )
	) );
	return $id ? (int) $id : 0;
}

function qpsc33_sideload( $path, $filename, $alt, $title, $post_id ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( $filename, null, file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload', $upload['error'] );
	}

	$filetype = wp_check_filetype( $upload['file'], null );
	$mime     = ( ! empty( $filetype['type'] ) ) ? $filetype['type'] : 'image/webp';

	$attach_id = wp_insert_attachment( array(
		'guid'           => $upload['url'],
		'post_mime_type' => $mime,
		'post_title'     => $title,
		'post_excerpt'   => $alt,
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $upload['file'], $post_id );

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return new WP_Error( 'attach', 'ثبت پیوست ناموفق بود.' );
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );

	return (int) $attach_id;
}

/* -------------------------------------------------------------------------
 * اجرای اصلی: یک ردیف به ازای هر دانشمند
 * ---------------------------------------------------------------------- */

function qpsc33_run( $dry = false ) {
	$data = qpsc33_load();
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$counts = array( 'attached' => 0, 'reused' => 0, 'skipped' => 0, 'missing' => 0, 'error' => 0 );
	$rows   = array();
	$seen   = array();

	foreach ( $data['items'] as $item ) {
		$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
		$file = isset( $item['file'] ) ? basename( $item['file'] ) : '';
		$alt  = isset( $item['alt'] ) ? trim( $item['alt'] ) : '';
		$ttl  = isset( $item['media_title'] ) ? trim( $item['media_title'] ) : '';
		if ( '' === $ttl ) {
			$ttl = $slug;
		}

		if ( '' === $slug || '' === $file || '' === $alt ) {
			$counts['error']++;
			$rows[] = array( 'slug' => $slug, 'status' => 'error', 'msg' => 'اسلاگ یا فایل یا متن جایگزین خالی است' );
			continue;
		}

		if ( isset( $seen[ $slug ] ) ) {
			$counts['skipped']++;
			$rows[] = array( 'slug' => $slug, 'status' => 'skipped', 'msg' => 'اسلاگ در فهرست بسته تکراری است؛ رد شد' );
			continue;
		}
		$seen[ $slug ] = true;

		$posts = qpsc33_find_posts( $slug );
		if ( empty( $posts ) ) {
			$counts['missing']++;
			$rows[] = array(
				'slug'   => $slug,
				'status' => 'missing',
				'msg'    => 'صفحه ای با این اسلاگ در ' . QPSC33_CPT . ' نیست (پیش نویس یا منتشر شده)',
			);
			continue;
		}

		$post    = qpsc33_pick_post( $posts );
		$post_id = (int) $post->ID;
		$state   = get_post_status( $post_id );
		$note    = ( count( $posts ) > 1 )
			? ' — ' . count( $posts ) . ' صفحه با این اسلاگ؛ هدف: شناسه ' . $post_id
			: ' — شناسه ' . $post_id . ' (' . $state . ')';

		if ( has_post_thumbnail( $post_id ) ) {
			$counts['skipped']++;
			$rows[] = array(
				'slug'   => $slug,
				'status' => 'skipped',
				'msg'    => 'تصویر شاخص از قبل دارد (پیوست ' . get_post_thumbnail_id( $post_id ) . ')؛ رد شد' . $note,
			);
			continue;
		}

		$exist = qpsc33_existing_attachment( $file );
		if ( $exist ) {
			if ( ! $dry ) {
				set_post_thumbnail( $post_id, $exist );
				if ( '' === (string) get_post_meta( $exist, '_wp_attachment_image_alt', true ) ) {
					update_post_meta( $exist, '_wp_attachment_image_alt', $alt );
				}
			}
			$counts['reused']++;
			$rows[] = array(
				'slug'   => $slug,
				'status' => 'reused',
				'msg'    => 'فایل از قبل در رسانه بود (پیوست ' . $exist . ')؛ دوباره آپلود نشد و به همان وصل شد' . $note,
			);
			continue;
		}

		$path = QPSC33_DIR . 'images/' . $file;
		if ( ! file_exists( $path ) ) {
			$counts['error']++;
			$rows[] = array( 'slug' => $slug, 'status' => 'error', 'msg' => 'فایل در بسته نیست: images/' . $file );
			continue;
		}

		if ( $dry ) {
			$counts['attached']++;
			$rows[] = array( 'slug' => $slug, 'status' => 'ready', 'msg' => 'آمادهٔ آپلود و اتصال' . $note );
			continue;
		}

		$attach_id = qpsc33_sideload( $path, $file, $alt, $ttl, $post_id );
		if ( is_wp_error( $attach_id ) ) {
			$counts['error']++;
			$rows[] = array( 'slug' => $slug, 'status' => 'error', 'msg' => $attach_id->get_error_message() . $note );
			continue;
		}

		set_post_thumbnail( $post_id, $attach_id );
		$counts['attached']++;
		$rows[] = array(
			'slug'   => $slug,
			'status' => 'attached',
			'msg'    => 'پیوست ' . $attach_id . ' ساخته و تصویر شاخص شد' . $note,
		);
	}

	return array( 'rows' => $rows, 'counts' => $counts, 'dry' => $dry );
}

/* -------------------------------------------------------------------------
 * گزارش ماندگار (بیرون از پوشهٔ افزونه تا بعد از حذف افزونه بماند)
 * ---------------------------------------------------------------------- */

function qpsc33_report_path() {
	$up = wp_upload_dir();
	if ( empty( $up['basedir'] ) || ! is_dir( $up['basedir'] ) || ! is_writable( $up['basedir'] ) ) {
		return '';
	}
	return trailingslashit( $up['basedir'] ) . QPSC33_REPORT;
}

function qpsc33_write_report( $lines ) {
	$path = qpsc33_report_path();
	if ( '' === $path ) {
		return '';
	}
	@file_put_contents( $path, implode( "\n", $lines ) . "\n" );
	return $path;
}

function qpsc33_report_lines( $log, $extra = array() ) {
	$labels = array(
		'attached' => 'وصل شد',
		'ready'    => 'آماده (پیش نمایش)',
		'reused'   => 'تکراری در رسانه؛ وصل شد',
		'skipped'  => 'رد شد',
		'missing'  => 'اسلاگ پیدا نشد',
		'error'    => 'خطا',
	);
	$lines   = array();
	$lines[] = 'QPedia Scientist Covers 33 — ' . ( empty( $log['dry'] ) ? 'اجرای واقعی' : 'پیش نمایش' ) . ' — ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
	foreach ( $log['counts'] as $k => $v ) {
		$lines[] = ( isset( $labels[ $k ] ) ? $labels[ $k ] : $k ) . ': ' . $v;
	}
	$lines[] = str_repeat( '-', 60 );
	foreach ( $log['rows'] as $r ) {
		$lines[] = '[' . $r['status'] . '] ' . $r['slug'] . ' — ' . $r['msg'];
	}
	foreach ( $extra as $e ) {
		$lines[] = $e;
	}
	return $lines;
}

/* -------------------------------------------------------------------------
 * حذف خودکار افزونه از سایت
 * ---------------------------------------------------------------------- */

function qpsc33_rrmdir( $dir ) {
	$items = @scandir( $dir );
	if ( false === $items ) {
		return false;
	}
	$ok = true;
	foreach ( $items as $it ) {
		if ( '.' === $it || '..' === $it ) {
			continue;
		}
		$path = rtrim( $dir, '/' ) . '/' . $it;
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			$ok = qpsc33_rrmdir( $path ) && $ok;
		} else {
			$ok = (bool) @unlink( $path ) && $ok;
		}
	}
	return (bool) @rmdir( $dir ) && $ok;
}

function qpsc33_self_delete() {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// اول غیرفعال سازی تا در درخواست بعدی سراغ فایل حذف شده نرود.
	deactivate_plugins( QPSC33_BASENAME, true );

	// نگهبان: فقط اگر پوشهٔ افزونه واقعاً داخل wp-content/plugins است.
	$root = defined( 'WP_PLUGIN_DIR' ) ? realpath( WP_PLUGIN_DIR ) : false;
	$mine = realpath( QPSC33_DIR );
	$deleted = false;

	if ( $root && $mine && 0 === strpos( $mine, $root ) && $mine !== $root ) {
		$deleted = qpsc33_rrmdir( $mine );
	}

	// fallback فقط وقتی که دسترسی مستقیم فایل داریم؛ وگرنه فرم FTP وسط صفحه باز می شود.
	if ( ! $deleted && function_exists( 'delete_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( 'direct' === get_filesystem_method() ) {
			$deleted = ( true === delete_plugins( array( QPSC33_BASENAME ) ) );
		}
	}

	if ( function_exists( 'opcache_invalidate' ) ) {
		@opcache_invalidate( QPSC33_FILE, true );
	}

	return $deleted;
}

function qpsc33_self_delete_late() {
	$deleted = qpsc33_self_delete();
	$lines   = array(
		'',
		$deleted
			? 'حذف خودکار: پوشهٔ افزونه از wp-content/plugins پاک شد.'
			: 'حذف خودکار: پاک کردن فایل ها ناموفق بود؛ افزونه غیرفعال شد و باید دستی حذف شود.',
	);
	$path = qpsc33_report_path();
	if ( '' !== $path ) {
		@file_put_contents( $path, implode( "\n", $lines ) . "\n", FILE_APPEND );
	}
}

/* -------------------------------------------------------------------------
 * صفحهٔ ابزار
 * ---------------------------------------------------------------------- */

function qpsc33_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی مجاز نیست.' );
	}

	echo '<div class="wrap" dir="rtl"><h1>QPedia Scientist Covers 33</h1>';
	echo '<p>هفت تصویر شاخص دانشمندان را آپلود می کند و <strong>فقط با اسلاگ انگلیسی اختصاصی</strong> هر زندگی نامه '
		. '(نوع پست <code>' . esc_html( QPSC33_CPT ) . '</code>) جفت می کند؛ متن جایگزین فارسی استاندارد روی پیوست می نویسد '
		. 'و تصویر شاخص را می نشاند. چه صفحه <strong>پیش نویس</strong> باشد چه <strong>منتشر شده</strong>. '
		. 'متن، عنوان، اسلاگ و وضعیت انتشار صفحه تغییر نمی کند.</p>';
	echo '<p>اگر صفحه از قبل تصویر شاخص داشته باشد، یا همان فایل در رسانه باشد، <strong>رد می شود</strong> و دوباره آپلود نمی شود. '
		. 'پس از اجرای موفق، افزونه خودش را غیرفعال و از سایت حذف می کند.</p>';

	$data = qpsc33_load();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	// پیش نمایش بسته + سلامت فایل ها
	echo '<table class="widefat striped" style="max-width:1100px"><thead><tr>'
		. '<th>#</th><th>اسلاگ هدف</th><th>فایل</th><th>ابعاد</th><th>متن جایگزین</th></tr></thead><tbody>';
	$i = 0;
	foreach ( $data['items'] as $item ) {
		$i++;
		$file = isset( $item['file'] ) ? basename( $item['file'] ) : '';
		$path = QPSC33_DIR . 'images/' . $file;
		$dim  = 'ناموجود';
		if ( file_exists( $path ) ) {
			$info = @getimagesize( $path );
			$dim  = $info ? $info[0] . '×' . $info[1] . ' ' . $info['mime'] : 'خوانده نشد';
			if ( $info && ( QPSC33_W !== (int) $info[0] || QPSC33_H !== (int) $info[1] ) ) {
				$dim .= ' (هشدار: باید ' . QPSC33_W . '×' . QPSC33_H . ' باشد)';
			}
		}
		printf(
			'<tr><td>%d</td><td><code>%s</code></td><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
			$i,
			esc_html( isset( $item['slug'] ) ? $item['slug'] : '' ),
			esc_html( $file ),
			esc_html( $dim ),
			esc_html( isset( $item['alt'] ) ? $item['alt'] : '' )
		);
	}
	echo '</tbody></table>';

	$act = '';
	if ( isset( $_POST['qpsc33_nonce'] ) && check_admin_referer( 'qpsc33_run', 'qpsc33_nonce' ) ) {
		if ( isset( $_POST['qpsc33_dry'] ) ) {
			$act = 'dry';
		} elseif ( isset( $_POST['qpsc33_delete_only'] ) ) {
			$act = 'delete';
		} else {
			$act = 'run';
		}
	}

	if ( 'delete' === $act ) {
		echo '<div class="notice notice-warning"><p>افزونه در پایان همین درخواست غیرفعال و حذف می شود.</p></div>';
		add_action( 'shutdown', 'qpsc33_self_delete_late' );
		echo '</div>';
		return;
	}

	$will_delete = isset( $_POST['qpsc33_selfdelete'] );

	if ( 'dry' === $act || 'run' === $act ) {
		@set_time_limit( 300 );
		$log = qpsc33_run( 'dry' === $act );

		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			$clean = ( 0 === $log['counts']['error'] && 0 === $log['counts']['missing'] );

			echo '<div class="notice ' . ( 'dry' === $act ? 'notice-info' : 'notice-success' ) . '"><p><strong>'
				. esc_html( 'dry' === $act ? 'پیش نمایش — چیزی آپلود نشد' : 'اجرا شد' )
				. '</strong> · ' . ( 'dry' === $act ? 'آماده برای اتصال' : 'وصل شد' ) . ': ' . (int) $log['counts']['attached']
				. ' · تکراری در رسانه و وصل شد: ' . (int) $log['counts']['reused']
				. ' · رد شد: ' . (int) $log['counts']['skipped']
				. ' · اسلاگ پیدا نشد: ' . (int) $log['counts']['missing']
				. ' · خطا: ' . (int) $log['counts']['error']
				. '</p></div>';

			echo '<table class="widefat striped" style="max-width:1100px"><thead><tr>'
				. '<th>اسلاگ</th><th>وضعیت</th><th>توضیح</th></tr></thead><tbody>';
			$colors = array(
				'attached' => '#0a7a3d',
				'ready'    => '#3d5a80',
				'reused'   => '#0a6a7a',
				'skipped'  => '#8a6d00',
				'missing'  => '#b32d2e',
				'error'    => '#b32d2e',
			);
			foreach ( $log['rows'] as $r ) {
				printf(
					'<tr><td><code>%s</code></td><td style="color:%s;font-weight:600">%s</td><td>%s</td></tr>',
					esc_html( $r['slug'] ),
					esc_attr( isset( $colors[ $r['status'] ] ) ? $colors[ $r['status'] ] : '#000' ),
					esc_html( $r['status'] ),
					esc_html( $r['msg'] )
				);
			}
			echo '</tbody></table>';

			if ( 'dry' !== $act ) {
				$report = qpsc33_write_report( qpsc33_report_lines( $log ) );
				if ( '' !== $report ) {
					echo '<p>گزارش ماندگار: <code>' . esc_html( $report ) . '</code></p>';
				}

				if ( $will_delete && $clean ) {
					echo '<div class="notice notice-warning"><p><strong>افزونه در پایان همین درخواست غیرفعال و از سایت حذف می شود.</strong> '
						. 'بعد از آن در LiteSpeed گزینهٔ Purge All را بزنید.</p></div>';
					add_action( 'shutdown', 'qpsc33_self_delete_late' );
				} elseif ( $will_delete && ! $clean ) {
					echo '<div class="notice notice-error"><p>حذف خودکار انجام نشد، چون خطا یا اسلاگ پیدا نشده داریم. '
						. 'مورد های قرمز را بررسی کنید و دوباره اجرا کنید (موارد انجام شده دوباره آپلود نمی شوند).</p></div>';
				}
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpsc33_run', 'qpsc33_nonce' );
	echo '<p><label><input type="checkbox" name="qpsc33_selfdelete" value="1" ' . checked( true, true, false ) . ' /> '
		. 'پس از اجرای موفق، افزونه را از سایت حذف کن (پیش فرض روشن)</label></p>';
	echo '<button type="submit" name="qpsc33_dry" value="1" class="button">پیش نمایش بدون آپلود</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">آپلود، اتصال تصویر شاخص و حذف افزونه</button> &nbsp; ';
	echo '<button type="submit" name="qpsc33_delete_only" value="1" class="button button-link-delete">فقط حذف افزونه</button>';
	echo '</form>';
	echo '<p style="margin-top:14px;color:#666">ترتیب کار: آپلود WebP در رسانه ← نوشتن متن جایگزین ← '
		. '<code>set_post_thumbnail</code> روی صفحهٔ هم اسلاگ ← گزارش در پوشهٔ uploads ← حذف خودکار افزونه. '
		. 'پیش نویس ها پیش نویس می مانند؛ انتشار خودکار نیست.</p>';
	echo '</div>';
}

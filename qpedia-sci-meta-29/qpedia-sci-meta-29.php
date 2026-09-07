<?php
/**
 * Plugin Name: QPedia Sci Meta 29
 * Description: ترمیم متای صفحات دانشمندان — ۲۲ عنوان مرکب، ۱۱ چکیدهٔ خالی، ۲۲ متادسکریپشن، اصلاح نام انگلیسی اینشتین، حذف صفحهٔ تکراری شرودینگر با ریدایرکت ۳۰۱. اسلاگ، محتوا، تاریخ انتشار، دسته و نویسندهٔ هیچ صفحه‌ای تغییر نمی‌کند. متای پرشده بازنویسی نمی‌شود.
 * Version:     29.0.0
 * Author:      QPedia
 * Text Domain: qpedia-sci-meta-29
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QPSM29_CPT', 'quantum_scientist' );
define( 'QPSM29_DIR', plugin_dir_path( __FILE__ ) );

/* ---------------------------------------------------------------- منو */

add_action( 'admin_menu', function () {
	add_management_page(
		'QPedia Sci Meta 29',
		'QPedia Sci Meta 29',
		'manage_options',
		'qpedia-sci-meta-29',
		'qpsm29_render_page'
	);
} );

/* ------------------------------------------------------- خواندن داده */

function qpsm29_load_data() {
	$file = QPSM29_DIR . 'data/payload.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'qpsm29_nofile', 'فایل data/payload.json پیدا نشد.' );
	}
	$data = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'qpsm29_badjson', 'ساختار JSON نامعتبر است.' );
	}
	return $data;
}

/* کلیدهای متادسکریپشن — هر دو افزونهٔ سئوی فعال سایت */
function qpsm29_meta_keys() {
	return array(
		'rank_math_description',
		'_yoast_wpseo_metadesc',
	);
}

/* یافتن برگهٔ دانشمند با اسلاگ — هرگز برگه نمی‌سازد */
function qpsm29_find( $slug ) {
	$posts = get_posts( array(
		'name'             => $slug,
		'post_type'        => QPSM29_CPT,
		'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return empty( $posts ) ? null : $posts[0];
}

/* ------------------------------------------------------------- اجرا */

function qpsm29_run( $dry_run = false ) {
	$data = qpsm29_load_data();
	if ( is_wp_error( $data ) ) { return $data; }

	$log = array(
		'updated'  => array(),
		'done'     => array(), // از قبل اعمال شده
		'skipped'  => array(), // محافظ فعال شد
		'missing'  => array(),
		'warning'  => array(),
	);

	/* ── ۱) عنوان‌های مرکب — اسلاگ هرگز نوشته نمی‌شود ── */
	foreach ( $data['titles'] as $t ) {
		$slug = sanitize_title( $t['slug'] );
		$post = qpsm29_find( $slug );
		if ( ! $post ) { $log['missing'][] = "عنوان: {$slug}"; continue; }

		$cur = (string) $post->post_title;
		if ( trim( $cur ) === trim( $t['new'] ) ) {
			$log['done'][] = "عنوان: {$slug} (از قبل اصلاح شده)";
			continue;
		}
		if ( trim( $cur ) !== trim( $t['old'] ) ) {
			$log['warning'][] = "عنوان: {$slug} — عنوان فعلی «{$cur}» با مورد انتظار «{$t['old']}» یکی نیست؛ دست نزدیم";
			continue;
		}
		if ( $dry_run ) {
			$log['updated'][] = "عنوان: {$slug} → «{$t['new']}»";
			continue;
		}
		// post_name صریحاً همان مقدار فعلی فرستاده می‌شود تا اسلاگ تضمین‌شده ثابت بماند
		$res = wp_update_post( array(
			'ID'         => $post->ID,
			'post_title' => wp_unslash( $t['new'] ),
			'post_name'  => $post->post_name,
		), true );
		if ( is_wp_error( $res ) ) {
			$log['warning'][] = "عنوان: {$slug} — خطای وردپرس: " . $res->get_error_message();
		} else {
			$log['updated'][] = "عنوان: {$slug}";
		}
	}

	/* ── ۲) چکیده‌های خالی (فقط ۱۱ برگهٔ بدون چکیده) ── */
	foreach ( $data['excerpts'] as $e ) {
		$slug = sanitize_title( $e['slug'] );
		$post = qpsm29_find( $slug );
		if ( ! $post ) { $log['missing'][] = "چکیده: {$slug}"; continue; }

		$cur = (string) $post->post_excerpt;
		if ( '' !== trim( $cur ) ) {
			if ( trim( $cur ) === trim( $e['text'] ) ) {
				$log['done'][] = "چکیده: {$slug} (از قبل نوشته شده)";
			} else {
				$log['skipped'][] = "چکیده: {$slug} (چکیدهٔ موجود دست نمی‌خورد)";
			}
			continue;
		}
		if ( $dry_run ) {
			$log['updated'][] = "چکیده: {$slug} → " . mb_substr( $e['text'], 0, 50 ) . '…';
			continue;
		}
		$res = wp_update_post( array(
			'ID'            => $post->ID,
			'post_excerpt'  => wp_unslash( $e['text'] ),
			'post_name'     => $post->post_name,
		), true );
		if ( is_wp_error( $res ) ) {
			$log['warning'][] = "چکیده: {$slug} — خطای وردپرس: " . $res->get_error_message();
		} else {
			$log['updated'][] = "چکیده: {$slug}";
		}
	}

	/* ── ۳) متادسکریپشن — فقط وقتی هر دو کلید خالی‌اند (بخش ۲۱.۳) ── */
	foreach ( $data['meta_descriptions'] as $m ) {
		$slug = sanitize_title( $m['slug'] );
		$post = qpsm29_find( $slug );
		if ( ! $post ) { $log['missing'][] = "متا: {$slug}"; continue; }

		$already = '';
		foreach ( qpsm29_meta_keys() as $key ) {
			$v = get_post_meta( $post->ID, $key, true );
			if ( is_string( $v ) && '' !== trim( $v ) ) { $already = $key; break; }
		}
		if ( '' !== $already ) {
			$log['skipped'][] = "متا: {$slug} ({$already} از قبل پر است)";
			continue;
		}
		if ( $dry_run ) {
			$log['updated'][] = "متا: {$slug} → " . mb_substr( $m['text'], 0, 50 ) . '…';
			continue;
		}
		foreach ( qpsm29_meta_keys() as $key ) {
			update_post_meta( $post->ID, $key, wp_unslash( $m['text'] ) );
		}
		$log['updated'][] = "متا: {$slug}";
	}

	/* ── ۴) اصلاح نام انگلیسی اینشتین ── */
	foreach ( $data['en_name_fixes'] as $f ) {
		$slug = sanitize_title( $f['slug'] );
		$post = qpsm29_find( $slug );
		if ( ! $post ) { $log['missing'][] = "نام انگلیسی: {$slug}"; continue; }

		$cur = (string) get_post_meta( $post->ID, '_scientist_en_name', true );
		if ( trim( $cur ) === trim( $f['new'] ) ) {
			$log['done'][] = "نام انگلیسی: {$slug} (درست است)";
			continue;
		}
		if ( trim( $cur ) !== trim( $f['old'] ) ) {
			$log['warning'][] = "نام انگلیسی: {$slug} — مقدار فعلی «{$cur}» انتظار نمی‌رفت؛ دست نزدیم";
			continue;
		}
		if ( $dry_run ) {
			$log['updated'][] = "نام انگلیسی: {$slug} → {$f['new']}";
			continue;
		}
		update_post_meta( $post->ID, '_scientist_en_name', wp_unslash( $f['new'] ) );
		$log['updated'][] = "نام انگلیسی: {$slug}";
	}

	/* ── ۵) صفحهٔ تکراری شرودینگر: سطل زباله + ریدایرکت ۳۰۱ (بخش ۲۲.۳) ── */
	$dup = $data['duplicate'];
	$post = qpsm29_find( $dup['slug'] );
	if ( ! $post ) {
		$log['done'][] = "تکراری: {$dup['slug']} (حذف‌شده — فقط ریدایرکت ۳۰۱ فعال است)";
	} elseif ( $dry_run ) {
		$log['updated'][] = "تکراری: {$dup['slug']} → سطل زباله + ریدایرکت ۳۰۱ به {$dup['redirect_to']}";
	} else {
		$to = home_url( user_trailingslashit( '/' . $dup['redirect_to'] ) );
		wp_trash_post( $post->ID ); // حذف نرم — از سطل زباله قابل بازگردانی است
		$log['updated'][] = "تکراری: {$dup['slug']} → سطل زباله + ریدایرکت ۳۰۱ به {$to}";
	}

	return $log;
}

/* -------------------------------- نمایش وضعیت پس از اجرا */

function qpsm29_redirect() {
	$data = qpsm29_load_data();
	if ( is_wp_error( $data ) || empty( $data['duplicate'] ) ) { return; }
	$dup = $data['duplicate'];

	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	$want = '/' . trim( $dup['redirect_from'], '/' );
	if ( $path !== null && untrailingslashit( $path ) === untrailingslashit( $want ) ) {
		wp_redirect( home_url( user_trailingslashit( '/' . $dup['redirect_to'] ) ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'qpsm29_redirect' );

/* ------------------------------------------------------------ صفحه */

function qpsm29_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo '<div class="wrap" dir="rtl">';
	echo '<h1>QPedia Sci Meta 29 — ترمیم متای دانشمندان</h1>';
	echo '<p>این افزونه <strong>فقط</strong> این‌ها را تغییر می‌دهد: عنوان ۲۲ صفحهٔ دانشمند (بدون تغییر اسلاگ)، '
		. 'چکیدهٔ ۱۱ صفحهٔ خالی، متادسکریپشن ۲۲ صفحهٔ فاقد متا، نام انگلیسی اینشتین، '
		. 'و انتقال صفحهٔ تکراری <code>schrodingerr</code> به سطل زباله با ریدایرکت ۳۰۱.</p>';
	echo '<p><strong>اسلاگ، محتوای متن، تاریخ انتشار، وضعیت، دسته و نویسندهٔ هیچ صفحه‌ای تغییر نمی‌کند.</strong> '
		. 'متا یا چکیدهٔ پرشدهٔ موجود بازنویسی نمی‌شود. افزونه هرگز برگهٔ جدید نمی‌سازد.</p>';

	$data = qpsm29_load_data();
	if ( is_wp_error( $data ) ) {
		echo '<div class="notice notice-error"><p>'
			. esc_html( $data->get_error_message() ) . '</p></div></div>';
		return;
	}

	echo '<table class="widefat striped" style="max-width:520px"><tbody>'
		. '<tr><td>عنوان مرکب جدید</td><td><strong>' . count( $data['titles'] ) . '</strong></td></tr>'
		. '<tr><td>چکیدهٔ جدید (صفحات خالی)</td><td><strong>' . count( $data['excerpts'] ) . '</strong></td></tr>'
		. '<tr><td>متادسکریپشن (صفحات بدون متا)</td><td><strong>' . count( $data['meta_descriptions'] ) . '</strong></td></tr>'
		. '<tr><td>اصلاح نام انگلیسی</td><td><strong>' . count( $data['en_name_fixes'] ) . '</strong></td></tr>'
		. '<tr><td>حذف تکراری + ریدایرکت ۳۰۱</td><td><strong>' . count( array( $data['duplicate'] ) ) . '</strong></td></tr>'
		. '</tbody></table>';

	$action = '';
	if ( isset( $_POST['qpsm29_nonce'] )
		&& wp_verify_nonce( $_POST['qpsm29_nonce'], 'qpsm29_run' ) ) {
		$action = isset( $_POST['qpsm29_dry'] ) ? 'dry' : 'run';
	}

	if ( $action ) {
		$log = qpsm29_run( 'dry' === $action );
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( $log->get_error_message() ) . '</p></div>';
		} else {
			$title = ( 'dry' === $action )
				? 'پیش‌نمایش — چیزی تغییر نکرد'
				: 'انجام شد';
			echo '<div class="notice notice-success"><p><strong>'
				. esc_html( $title ) . '</strong></p></div>';

			$sections = array(
				'updated' => array( 'تغییر/اعمال‌شده', '#00a32a' ),
				'done'    => array( 'از قبل انجام‌شده (دست نخورد)', '#00a32a' ),
				'skipped' => array( 'رد شد (محافظ ایمنی)', '#996800' ),
				'missing' => array( 'اسلاگ پیدا نشد', '#b32d2e' ),
				'warning' => array( 'هشدار — بررسی دستی', '#b32d2e' ),
			);
			foreach ( $sections as $key => $info ) {
				if ( empty( $log[ $key ] ) ) { continue; }
				echo '<h2 style="color:' . $info[1] . '">' . esc_html( $info[0] ) . ': '
					. count( $log[ $key ] ) . '</h2><ol>';
				foreach ( $log[ $key ] as $l ) {
					echo '<li>' . esc_html( $l ) . '</li>';
				}
				echo '</ol>';
			}
		}
	}

	echo '<form method="post" style="margin-top:20px">';
	wp_nonce_field( 'qpsm29_run', 'qpsm29_nonce' );
	echo '<button type="submit" name="qpsm29_dry" value="1" '
		. 'class="button">پیش‌نمایش بدون تغییر</button> &nbsp; ';
	echo '<button type="submit" class="button button-primary">اجرای نهایی</button>';
	echo '</form>';
	echo '<p style="color:#666">روال پیشنهادی: اول «پیش‌نمایش»، بازبینی فهرست، بعد «اجرای نهایی». '
		. 'افزونه را فعال نگه دارید تا ریدایرکت ۳۰۱ شرودینگر کار کند.</p>';
	echo '</div>';
}

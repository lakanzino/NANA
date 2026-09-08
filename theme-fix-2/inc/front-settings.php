<?php
/**
 * تنظیمات صفحهٔ نخست — پیشخوان وردپرس.
 * مسیر: quantum-pedia-child/inc/front-settings.php
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * مقادیر پیش‌فرض = همان متن‌های فعلی سایت.
 */
function qpedia_front_defaults() {
	return array(
		'show_hero'       => 1,
		'hero_badge'      => 'دانشنامهٔ فارسی فیزیک کوانتوم',
		'hero_title'      => 'شگفتی‌های دنیای کوانتوم را ساده، دقیق و بی‌اغراق کشف کنید',
		'hero_desc'       => 'هر مقاله با منبع علمی معتبر نوشته شده، به زبان ساده — بدون فرمول‌های ترسناک و بدون ادعاهای بی‌پایه.',
		'hero_btn1_text'  => 'شروع از مبانی',
		'hero_btn1_url'   => '/topic/fundamentals/',
		'hero_btn2_text'  => 'مرور دسته‌ها',
		'hero_btn2_url'   => '#qp-front-cats',
		'show_search'     => 1,
		'search_label'    => 'دنبال موضوع خاصی هستید؟',
		'show_stats'      => 1,
		'counter_ms'      => 1100,
		'show_featured'   => 1,
		'featured'        => array(
			array(
				'slug' => 'schrodinger-cat',
				'hook' => 'گربه‌ای که نه زنده بود نه مرده — و چرا این ماجرا اصلاً دربارهٔ گربه نیست.',
				'tag'  => 'معروف‌ترین',
			),
			array(
				'slug' => 'double-slit-experiment',
				'hook' => 'آزمایشی که فاینمن آن را «تنها راز واقعی کوانتوم» می‌دانست.',
				'tag'  => 'کلاسیک',
			),
			array(
				'slug' => 'quantum-entanglement-explained',
				'hook' => 'اینشتین اسمش را گذاشت «کنش شبح‌وار از راه دور» و تا آخر عمر قبولش نکرد.',
				'tag'  => 'پرسش‌برانگیز',
			),
			array(
				'slug' => 'q-day',
				'hook' => 'روزی که رمزنگاری اینترنت می‌شکند. چقدر فاصله داریم؟',
				'tag'  => 'کاربردی',
			),
			array(
				'slug' => 'nobel-physics-2025',
				'hook' => 'نوبل امسال به سه نفری رسید که کوانتوم را از اتم بیرون کشیدند.',
				'tag'  => 'تازه',
			),
			array(
				'slug' => 'law-of-attraction-quantum',
				'hook' => 'رایج‌ترین سوءاستفاده از کوانتوم؛ دقیقاً کجای استدلال می‌لنگد؟',
				'tag'  => 'نقد',
			),
			array(
				'slug' => '',
				'hook' => '',
				'tag'  => '',
			),
			array(
				'slug' => '',
				'hook' => '',
				'tag'  => '',
			),
		),
		'show_latest'     => 1,
		'latest_eyebrow'  => 'تازه‌ترین‌ها',
		'latest_title'    => 'آخرین مقاله‌ها',
		'latest_link'     => 'همهٔ مقاله‌ها',
		'latest_url'      => '/topic/fundamentals/',
		'latest_count'    => 6,
		'show_cats'       => 1,
		'cats_eyebrow'    => 'ساختار دانشنامه',
		'cats_title'      => 'دسته‌بندی موضوعات',
		'cats_desc'       => 'مسیرهای اصلی برای خواندن موضوعی مقاله‌ها.',
		'cat_descriptions' => array(
			'fundamentals'        => 'سنگ‌بنای مکانیک کوانتومی؛ از مفاهیم پایه تا ذرات بنیادی.',
			'technology'          => 'از لیزر و GPS تا رایانش و کاربردهای واقعی کوانتوم.',
			'history-experiments' => 'روایت تاریخی نظریه و آزمایش‌هایی که فهم ما را تغییر دادند.',
			'phenomena'           => 'درهم‌تنیدگی، تونل‌زنی و پدیده‌هایی که شهود کلاسیک را می‌شکنند.',
			'mathematics'         => 'زبان ریاضی کوانتوم؛ فضای هیلبرت، عملگرها و معادلات.',
			'interpretations'     => 'خوانش‌های فلسفی و تفسیری از معنای نظریهٔ کوانتوم.',
			'pseudoscience'       => 'مرزبندی علم دقیق با سوءاستفاده‌های بازاری و شبه‌علم.',
		),
		'cat_icons'       => array(
			'fundamentals'        => 'مبانی',
			'technology'          => 'فناوری',
			'history-experiments' => 'تاریخ',
			'phenomena'           => 'پدیده',
			'mathematics'         => 'ریاضی',
			'interpretations'     => 'تفسیر',
			'pseudoscience'       => 'نقد',
		),
		'show_scientists' => 1,
		'sci_eyebrow'     => 'تالار دانشمندان',
		'sci_title'       => 'چهره‌های مهم کوانتوم',
		'sci_desc'        => 'برای دیدن بقیه، ردیف را بکشید.',
		'sci_link'        => 'همهٔ دانشمندان',
		'sci_url'         => '/scientists/',
		'sci_count'       => 10,
		'header_title'    => 'کوانتوم پدیا فارسی',
		'header_desc'     => 'دانشنامهٔ فارسی فیزیک کوانتوم',
		'footer_desc'     => 'منبعی مینیمال و دقیق برای مرور مفاهیم و فناوری‌های دنیای کوانتوم.',
		'footer_copy'     => 'کوانتوم پدیا فارسی — همه حقوق محفوظ است.',
	);
}

/**
 * خواندن یک کلید یا کل آرایه.
 *
 * @param string|null $key کلید یا تهی برای همه.
 * @return mixed
 */
function qpedia_front_get( $key = null ) {
	$defaults = qpedia_front_defaults();
	$saved    = get_option( 'qpedia_front', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$out = array_replace_recursive( $defaults, $saved );

	if ( empty( $out['featured'] ) || ! is_array( $out['featured'] ) ) {
		$out['featured'] = $defaults['featured'];
	} else {
		while ( count( $out['featured'] ) < 8 ) {
			$out['featured'][] = array(
				'slug' => '',
				'hook' => '',
				'tag'  => '',
			);
		}
		$out['featured'] = array_slice( $out['featured'], 0, 8 );
	}

	if ( null === $key ) {
		return $out;
	}

	return isset( $out[ $key ] ) ? $out[ $key ] : '';
}

/**
 * ساخت href از مقدار ذخیره‌شده (#لنگر، /مسیر، یا URL کامل).
 *
 * @param string $url مقدار خام.
 * @return string
 */
function qpedia_front_href( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return home_url( '/' );
	}
	if ( '#' === $url[0] ) {
		return $url;
	}
	if ( '/' === $url[0] ) {
		return home_url( $url );
	}
	return $url;
}

/**
 * منوی پیشخوان.
 */
function qpedia_front_admin_menu() {
	add_menu_page(
		'صفحهٔ نخست',
		'صفحهٔ نخست',
		'edit_theme_options',
		'qpedia-front',
		'qpedia_front_settings_page',
		'dashicons-admin-home',
		3
	);
}
add_action( 'admin_menu', 'qpedia_front_admin_menu' );

/**
 * ذخیره.
 */
function qpedia_front_handle_save() {
	if ( ! isset( $_POST['qpedia_front_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qpedia_front_nonce'] ) ), 'qpedia_front_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$redirect = admin_url( 'admin.php?page=qpedia-front' );

	if ( isset( $_POST['qpedia_front_reset'] ) ) {
		delete_option( 'qpedia_front' );
		wp_safe_redirect( add_query_arg( 'qpedia_front_msg', 'reset', $redirect ) );
		exit;
	}

	$d   = qpedia_front_defaults();
	$chk = function ( $name ) {
		return isset( $_POST[ $name ] ) ? 1 : 0;
	};
	$txt = function ( $name, $fallback = '' ) {
		if ( ! isset( $_POST[ $name ] ) ) {
			return $fallback;
		}
		return sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
	};
	$area = function ( $name, $fallback = '' ) {
		if ( ! isset( $_POST[ $name ] ) ) {
			return $fallback;
		}
		return sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
	};

	$featured = array();
	for ( $i = 0; $i < 8; $i++ ) {
		$slug = isset( $_POST['feat_slug'][ $i ] ) ? sanitize_title( wp_unslash( $_POST['feat_slug'][ $i ] ) ) : '';
		$hook = isset( $_POST['feat_hook'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['feat_hook'][ $i ] ) ) : '';
		$tag  = isset( $_POST['feat_tag'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['feat_tag'][ $i ] ) ) : '';
		$featured[] = array(
			'slug' => $slug,
			'hook' => $hook,
			'tag'  => $tag,
		);
	}

	$cat_slugs = array_keys( $d['cat_descriptions'] );
	$cat_desc  = array();
	$cat_icon  = array();
	foreach ( $cat_slugs as $slug ) {
		$cat_desc[ $slug ] = isset( $_POST['cat_desc'][ $slug ] )
			? sanitize_text_field( wp_unslash( $_POST['cat_desc'][ $slug ] ) )
			: $d['cat_descriptions'][ $slug ];
		$cat_icon[ $slug ] = isset( $_POST['cat_icon'][ $slug ] )
			? sanitize_text_field( wp_unslash( $_POST['cat_icon'][ $slug ] ) )
			: $d['cat_icons'][ $slug ];
	}

	$latest_count = isset( $_POST['latest_count'] ) ? absint( $_POST['latest_count'] ) : 6;
	$sci_count    = isset( $_POST['sci_count'] ) ? absint( $_POST['sci_count'] ) : 10;
	$counter_ms   = isset( $_POST['counter_ms'] ) ? absint( $_POST['counter_ms'] ) : 1100;

	if ( $latest_count < 1 ) {
		$latest_count = 1;
	}
	if ( $latest_count > 12 ) {
		$latest_count = 12;
	}
	if ( $sci_count < 3 ) {
		$sci_count = 3;
	}
	if ( $sci_count > 24 ) {
		$sci_count = 24;
	}
	if ( $counter_ms < 200 ) {
		$counter_ms = 200;
	}
	if ( $counter_ms > 5000 ) {
		$counter_ms = 5000;
	}

	$save = array(
		'show_hero'        => $chk( 'show_hero' ),
		'hero_badge'       => $txt( 'hero_badge', $d['hero_badge'] ),
		'hero_title'       => $txt( 'hero_title', $d['hero_title'] ),
		'hero_desc'        => $area( 'hero_desc', $d['hero_desc'] ),
		'hero_btn1_text'   => $txt( 'hero_btn1_text', $d['hero_btn1_text'] ),
		'hero_btn1_url'    => $txt( 'hero_btn1_url', $d['hero_btn1_url'] ),
		'hero_btn2_text'   => $txt( 'hero_btn2_text', $d['hero_btn2_text'] ),
		'hero_btn2_url'    => $txt( 'hero_btn2_url', $d['hero_btn2_url'] ),
		'show_search'      => $chk( 'show_search' ),
		'search_label'     => $txt( 'search_label', $d['search_label'] ),
		'show_stats'       => $chk( 'show_stats' ),
		'counter_ms'       => $counter_ms,
		'show_featured'    => $chk( 'show_featured' ),
		'featured'         => $featured,
		'show_latest'      => $chk( 'show_latest' ),
		'latest_eyebrow'   => $txt( 'latest_eyebrow', $d['latest_eyebrow'] ),
		'latest_title'     => $txt( 'latest_title', $d['latest_title'] ),
		'latest_link'      => $txt( 'latest_link', $d['latest_link'] ),
		'latest_url'       => $txt( 'latest_url', $d['latest_url'] ),
		'latest_count'     => $latest_count,
		'show_cats'        => $chk( 'show_cats' ),
		'cats_eyebrow'     => $txt( 'cats_eyebrow', $d['cats_eyebrow'] ),
		'cats_title'       => $txt( 'cats_title', $d['cats_title'] ),
		'cats_desc'        => $area( 'cats_desc', $d['cats_desc'] ),
		'cat_descriptions' => $cat_desc,
		'cat_icons'        => $cat_icon,
		'show_scientists'  => $chk( 'show_scientists' ),
		'sci_eyebrow'      => $txt( 'sci_eyebrow', $d['sci_eyebrow'] ),
		'sci_title'        => $txt( 'sci_title', $d['sci_title'] ),
		'sci_desc'         => $area( 'sci_desc', $d['sci_desc'] ),
		'sci_link'         => $txt( 'sci_link', $d['sci_link'] ),
		'sci_url'          => $txt( 'sci_url', $d['sci_url'] ),
		'sci_count'        => $sci_count,
		'header_title'     => $txt( 'header_title', $d['header_title'] ),
		'header_desc'      => $txt( 'header_desc', $d['header_desc'] ),
		'footer_desc'      => $area( 'footer_desc', $d['footer_desc'] ),
		'footer_copy'      => $txt( 'footer_copy', $d['footer_copy'] ),
	);

	update_option( 'qpedia_front', $save, false );
	wp_safe_redirect( add_query_arg( 'qpedia_front_msg', 'saved', $redirect ) );
	exit;
}
add_action( 'admin_init', 'qpedia_front_handle_save' );

/**
 * فیلد متنی.
 */
function qpedia_front_field( $name, $value, $label, $wide = true ) {
	$id = 'qpf-' . $name;
	echo '<p class="qpf-field">';
	echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	echo '<input type="text" class="' . ( $wide ? 'qpf-wide' : 'qpf-short' ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	echo '</p>';
}

/**
 * صفحهٔ تنظیمات.
 */
function qpedia_front_settings_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$F = qpedia_front_get();
	if ( isset( $_GET['qpedia_front_msg'] ) ) {
		$msg = sanitize_key( wp_unslash( $_GET['qpedia_front_msg'] ) );
		if ( 'saved' === $msg ) {
			add_settings_error( 'qpedia_front', 'saved', 'ذخیره شد. کش LiteSpeed را Purge All کنید تا در سایت دیده شود.', 'updated' );
		} elseif ( 'reset' === $msg ) {
			add_settings_error( 'qpedia_front', 'reset', 'همهٔ تنظیمات به حالت پیش‌فرض برگشت.', 'updated' );
		}
	}
	settings_errors( 'qpedia_front' );
	?>
	<style>
		.qpf-wrap{max-width:920px}
		.qpf-wrap h1{margin-bottom:8px}
		.qpf-lead{color:#555;margin:0 0 18px}
		.qpf-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 20px;margin:0 0 16px}
		.qpf-card h2{margin:0 0 12px;font-size:16px}
		.qpf-field{margin:0 0 12px}
		.qpf-field label{display:block;font-weight:600;margin:0 0 4px}
		.qpf-wide{width:100%;max-width:100%}
		.qpf-short{width:140px}
		.qpf-row{display:flex;gap:12px;flex-wrap:wrap}
		.qpf-row .qpf-field{flex:1;min-width:180px}
		.qpf-check{margin:0 0 10px}
		.qpf-feat{border:1px dashed #c3c4c7;border-radius:6px;padding:12px;margin:0 0 10px;background:#f6f7f7}
		.qpf-feat h3{margin:0 0 8px;font-size:13px;color:#1d2327}
		.qpf-actions{display:flex;gap:10px;align-items:center;margin-top:8px}
		.qpf-note{color:#646970;font-size:12px;margin:4px 0 0}
	</style>
	<div class="wrap qpf-wrap" dir="rtl">
		<h1>صفحهٔ نخست</h1>
		<p class="qpf-lead">متن‌ها، دکمه‌ها، مقاله‌های پیشنهادی و نمایش هر بخش را از اینجا عوض کنید. نیازی به ویرایش فایل PHP نیست.</p>
		<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">مشاهدهٔ صفحهٔ نخست</a></p>

		<form method="post">
			<?php wp_nonce_field( 'qpedia_front_save', 'qpedia_front_nonce' ); ?>

			<div class="qpf-card">
				<h2>قهرمان (بالای صفحه)</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_hero" value="1" <?php checked( $F['show_hero'] ); ?>> نمایش این بخش</label></p>
				<?php
				qpedia_front_field( 'hero_badge', $F['hero_badge'], 'برچسب کوچک بالای عنوان' );
				qpedia_front_field( 'hero_title', $F['hero_title'], 'عنوان اصلی (H1)' );
				?>
				<p class="qpf-field">
					<label for="qpf-hero_desc">توضیح زیر عنوان</label>
					<textarea class="qpf-wide" id="qpf-hero_desc" name="hero_desc" rows="3"><?php echo esc_textarea( $F['hero_desc'] ); ?></textarea>
				</p>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'hero_btn1_text', $F['hero_btn1_text'], 'متن دکمهٔ اصلی' );
					qpedia_front_field( 'hero_btn1_url', $F['hero_btn1_url'], 'نشانی دکمهٔ اصلی' );
					?>
				</div>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'hero_btn2_text', $F['hero_btn2_text'], 'متن دکمهٔ دوم' );
					qpedia_front_field( 'hero_btn2_url', $F['hero_btn2_url'], 'نشانی دکمهٔ دوم' );
					?>
				</div>
				<p class="qpf-note">نشانی می‌تواند مسیر باشد مثل <code>/topic/fundamentals/</code> یا لنگر مثل <code>#qp-front-cats</code>.</p>
			</div>

			<div class="qpf-card">
				<h2>جست‌وجو و شمارنده‌ها</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_search" value="1" <?php checked( $F['show_search'] ); ?>> نمایش جعبهٔ جست‌وجو</label></p>
				<?php qpedia_front_field( 'search_label', $F['search_label'], 'متن بالای جست‌وجو' ); ?>
				<p class="qpf-check"><label><input type="checkbox" name="show_stats" value="1" <?php checked( $F['show_stats'] ); ?>> نمایش شمارنده‌ها (مقاله / دسته / دانشمند)</label></p>
				<?php qpedia_front_field( 'counter_ms', (string) $F['counter_ms'], 'مدت انیمیشن شمارنده (میلی‌ثانیه)', false ); ?>
			</div>

			<div class="qpf-card">
				<h2>مقاله‌های پیشنهادی</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_featured" value="1" <?php checked( $F['show_featured'] ); ?>> نمایش این بخش</label></p>
				<p class="qpf-note">اسلاگ را از نشانی مقاله بردارید؛ مثلاً <code>qpedia.ir/schrodinger-cat/</code> → <code>schrodinger-cat</code>. کارت اول پهن‌تر است. اسلاگ خالی یعنی آن کارت نشان داده نشود.</p>
				<?php
				for ( $i = 0; $i < 8; $i++ ) {
					$item = isset( $F['featured'][ $i ] ) ? $F['featured'][ $i ] : array(
						'slug' => '',
						'hook' => '',
						'tag'  => '',
					);
					echo '<div class="qpf-feat"><h3>کارت ' . esc_html( (string) ( $i + 1 ) ) . ( 0 === $i ? ' — پهن' : '' ) . '</h3>';
					echo '<div class="qpf-row">';
					echo '<p class="qpf-field"><label>اسلاگ</label><input type="text" name="feat_slug[' . $i . ']" value="' . esc_attr( $item['slug'] ) . '"></p>';
					echo '<p class="qpf-field"><label>برچسب کوچک</label><input type="text" name="feat_tag[' . $i . ']" value="' . esc_attr( $item['tag'] ) . '"></p>';
					echo '</div>';
					echo '<p class="qpf-field"><label>جملهٔ قلاب</label><input type="text" class="qpf-wide" name="feat_hook[' . $i . ']" value="' . esc_attr( $item['hook'] ) . '"></p>';
					echo '</div>';
				}
				?>
			</div>

			<div class="qpf-card">
				<h2>آخرین مقاله‌ها</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_latest" value="1" <?php checked( $F['show_latest'] ); ?>> نمایش این بخش</label></p>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'latest_eyebrow', $F['latest_eyebrow'], 'عنوان کوچک' );
					qpedia_front_field( 'latest_title', $F['latest_title'], 'عنوان بخش' );
					?>
				</div>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'latest_link', $F['latest_link'], 'متن لینک «همه»' );
					qpedia_front_field( 'latest_url', $F['latest_url'], 'نشانی لینک «همه»' );
					qpedia_front_field( 'latest_count', (string) $F['latest_count'], 'تعداد کارت (۱ تا ۱۲)', false );
					?>
				</div>
			</div>

			<div class="qpf-card">
				<h2>دسته‌بندی موضوعات</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_cats" value="1" <?php checked( $F['show_cats'] ); ?>> نمایش این بخش</label></p>
				<?php
				qpedia_front_field( 'cats_eyebrow', $F['cats_eyebrow'], 'عنوان کوچک' );
				qpedia_front_field( 'cats_title', $F['cats_title'], 'عنوان بخش' );
				?>
				<p class="qpf-field">
					<label for="qpf-cats_desc">توضیح</label>
					<textarea class="qpf-wide" id="qpf-cats_desc" name="cats_desc" rows="2"><?php echo esc_textarea( $F['cats_desc'] ); ?></textarea>
				</p>
				<?php foreach ( $F['cat_descriptions'] as $slug => $desc ) : ?>
					<div class="qpf-row">
						<p class="qpf-field">
							<label>برچسب کارت — <code><?php echo esc_html( $slug ); ?></code></label>
							<input type="text" name="cat_icon[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( isset( $F['cat_icons'][ $slug ] ) ? $F['cat_icons'][ $slug ] : '' ); ?>">
						</p>
						<p class="qpf-field" style="flex:2">
							<label>توضیح کارت</label>
							<input type="text" class="qpf-wide" name="cat_desc[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $desc ); ?>">
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="qpf-card">
				<h2>دانشمندان</h2>
				<p class="qpf-check"><label><input type="checkbox" name="show_scientists" value="1" <?php checked( $F['show_scientists'] ); ?>> نمایش این بخش</label></p>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'sci_eyebrow', $F['sci_eyebrow'], 'عنوان کوچک' );
					qpedia_front_field( 'sci_title', $F['sci_title'], 'عنوان بخش' );
					?>
				</div>
				<p class="qpf-field">
					<label for="qpf-sci_desc">توضیح / راهنمای کشیدن</label>
					<textarea class="qpf-wide" id="qpf-sci_desc" name="sci_desc" rows="2"><?php echo esc_textarea( $F['sci_desc'] ); ?></textarea>
				</p>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'sci_link', $F['sci_link'], 'متن لینک «همه»' );
					qpedia_front_field( 'sci_url', $F['sci_url'], 'نشانی لینک «همه»' );
					qpedia_front_field( 'sci_count', (string) $F['sci_count'], 'تعداد چهره (۳ تا ۲۴)', false );
					?>
				</div>
			</div>

			<div class="qpf-card">
				<h2>هدر و فوتر</h2>
				<div class="qpf-row">
					<?php
					qpedia_front_field( 'header_title', $F['header_title'], 'عنوان هدر' );
					qpedia_front_field( 'header_desc', $F['header_desc'], 'زیرعنوان هدر' );
					?>
				</div>
				<p class="qpf-field">
					<label for="qpf-footer_desc">متن معرفی فوتر</label>
					<textarea class="qpf-wide" id="qpf-footer_desc" name="footer_desc" rows="2"><?php echo esc_textarea( $F['footer_desc'] ); ?></textarea>
				</p>
				<?php qpedia_front_field( 'footer_copy', $F['footer_copy'], 'متن کپی‌رایت (سال خودکار جلو می‌آید)' ); ?>
			</div>

			<div class="qpf-actions">
				<button type="submit" class="button button-primary">ذخیرهٔ تنظیمات</button>
				<button type="submit" name="qpedia_front_reset" value="1" class="button" onclick="return confirm('همه به حالت پیش‌فرض برگردد؟');">بازگشت به پیش‌فرض</button>
			</div>
		</form>
	</div>
	<?php
}

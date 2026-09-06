<?php
/**
 * Quantum Pedia Child — نسخهٔ سبک و تمیز (اصلاح‌شده ۱۴۰۴/۰۶/۰۹)
 * هدف: فقط ساختارهای ضروری سایت در پوسته بماند.
 *
 * تغییرات این نسخه نسبت به قبلی:
 * - رفع باگ ۴۰۴ برگه‌ها: فیلتر request حالا اگر مقاله نبود، برگه را درست نشان می‌دهد.
 * - افزودن load_child_theme_textdomain برای رفع خطای «Doing it Wrong» ترجمه.
 * - افزودن گارد امن برای CONCATENATE_SCRIPTS (اختیاری، بی‌اثر اگر جای دیگر تعریف شده).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QPEDIA_CHILD_VERSION', '2026.08.30-ui3' );

/**
 * بارگذاری textdomain پوستهٔ فرزند — رفع خطای Doing it Wrong (ترجمهٔ زودهنگام)
 */
function qpedia_child_load_textdomain() {
	load_child_theme_textdomain( 'quantum-pedia-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'qpedia_child_load_textdomain' );

/**
 * استایل‌های ضروری پوستهٔ فرزند
 */
function qpedia_child_enqueue_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'qpedia-child-style',
		get_stylesheet_uri(),
		array(),
		$theme_version
	);

	$layouts_file = get_stylesheet_directory() . '/assets/css/qpedia-layouts.css';
	if ( file_exists( $layouts_file ) ) {
		wp_enqueue_style(
			'qpedia-layouts',
			get_stylesheet_directory_uri() . '/assets/css/qpedia-layouts.css',
			array( 'qpedia-child-style' ),
			filemtime( $layouts_file )
		);
	}

	$custom_file = get_stylesheet_directory() . '/assets/css/custom.css';
	if ( file_exists( $custom_file ) ) {
		wp_enqueue_style(
			'qpedia-child-custom',
			get_stylesheet_directory_uri() . '/assets/css/custom.css',
			array( 'qpedia-layouts' ),
			filemtime( $custom_file )
		);
	}

	$custom_js = get_stylesheet_directory() . '/assets/js/custom.js';
	if ( file_exists( $custom_js ) ) {
		wp_enqueue_script(
			'qpedia-child-custom',
			get_stylesheet_directory_uri() . '/assets/js/custom.js',
			array(),
			filemtime( $custom_js ),
			true
		);
	}

	/*
	 * ── دارایی‌های مخصوص صفحهٔ نخست ──
	 * فقط روی صفحهٔ اول بارگذاری می‌شوند تا صفحات مقاله سبک بمانند.
	 */
	if ( is_front_page() ) {

		$front_css = get_stylesheet_directory() . '/assets/css/qpedia-front-v2.css';
		if ( file_exists( $front_css ) ) {
			wp_enqueue_style(
				'qpedia-front-v2',
				get_stylesheet_directory_uri() . '/assets/css/qpedia-front-v2.css',
				array( 'qpedia-child-custom' ),
				filemtime( $front_css )
			);
		}

		$counters_js = get_stylesheet_directory() . '/assets/js/qpedia-counters.js';
		if ( file_exists( $counters_js ) ) {
			wp_enqueue_script(
				'qpedia-counters',
				get_stylesheet_directory_uri() . '/assets/js/qpedia-counters.js',
				array(),
				filemtime( $counters_js ),
				true
			);
			wp_script_add_data( 'qpedia-counters', 'defer', true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'qpedia_child_enqueue_assets', 20 );

/**
 * پاک‌سازی سبکِ head
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * ثبت ساختارهای اصلی محتوا
 */
function qpedia_child_register_content_types() {
	register_post_type(
		'quantum_article',
		array(
			'labels' => array(
				'name'          => 'مقالات کوانتوم',
				'singular_name' => 'مقالهٔ کوانتوم',
				'add_new_item'  => 'افزودن مقالهٔ جدید',
				'edit_item'     => 'ویرایش مقاله',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-media-document',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'query_var'          => 'quantum_article',
		)
	);

	register_post_type(
		'quantum_scientist',
		array(
			'labels' => array(
				'name'          => 'دانشمندان کوانتوم',
				'singular_name' => 'دانشمند کوانتوم',
				'add_new_item'  => 'افزودن دانشمند جدید',
				'edit_item'     => 'ویرایش دانشمند',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-groups',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
			'has_archive'        => 'scientists',
			'rewrite'            => array(
				'slug'       => 'scientists',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'quantum_category',
		array( 'quantum_article' ),
		array(
			'labels' => array(
				'name'              => 'دسته‌بندی کوانتوم',
				'singular_name'     => 'دسته',
				'search_items'      => 'جست‌وجوی دسته',
				'all_items'         => 'همهٔ دسته‌ها',
				'parent_item'       => 'دستهٔ مادر',
				'parent_item_colon' => 'دستهٔ مادر:',
				'edit_item'         => 'ویرایش دسته',
				'update_item'       => 'به‌روزرسانی دسته',
				'add_new_item'      => 'افزودن دستهٔ جدید',
				'new_item_name'     => 'نام دستهٔ جدید',
				'menu_name'         => 'دسته‌بندی کوانتوم',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_in_nav_menus' => true,
			'rewrite'           => array(
				'slug'         => 'topic',
				'with_front'   => false,
				'hierarchical' => false,
			),
		)
	);
}
add_action( 'init', 'qpedia_child_register_content_types', 5 );

/**
 * rewriteهای ضروری سایت
 */
function qpedia_child_rewrite_rules() {
	// مقاله‌های تخت: /qubit/
	// این rule باید بالاتر از rule عمومی page باشد تا مقاله‌ها 404 نشوند.
	// هم‌زمان مسیرهای رزروشده را مستثنا می‌کنیم.
	add_rewrite_rule(
		'^(?!scientists/?$)(?!topic/)(?!wp-admin/?$)(?!wp-json/?$)(?!feed/?$)(?!page/)(?!search/?$)(?!robots\\.txt$)(?!favicon\\.ico$)(?!xmlrpc\\.php$)(?!wp-login\\.php$)(?!sitemap.*\\.xml$)([a-z0-9][a-z0-9\\-]{2,})/?$',
		'index.php?quantum_article=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qpedia_child_rewrite_rules', 20 );

/**
 * جلوگیری از بلعیده‌شدن مسیرهای سیستمی و رزرو شده
 *
 * اصلاح‌شده: اگر مقاله‌ای با این اسلاگ نبود، پیش از 404 بررسی می‌کند که
 * آیا «برگه» با همان اسلاگ وجود دارد؛ اگر بله، برگه را نشان می‌دهد.
 * این همان رفع باگ 404 برگه‌ها (about-us / contact-us / ...) است.
 */
function qpedia_child_filter_article_request( $query_vars ) {
	if ( empty( $query_vars['quantum_article'] ) ) {
		return $query_vars;
	}

	$slug = sanitize_title_for_query( $query_vars['quantum_article'] );

	/*
	 * برگه‌های ثابت سایت — این اسلاگ‌ها همیشه «برگه» هستند، نه مقاله.
	 * این فهرست تضمین می‌کند که این آدرس‌ها هیچ‌وقت ۴۰۴ نشوند و به خانه نروند،
	 * حتی اگر قوانین rewrite تازه‌سازی (flush) نشده باشند.
	 * اگر بعداً برگهٔ ثابت جدیدی ساختی، فقط اسلاگش را به این آرایه اضافه کن.
	 */
	$known_pages = array(
		'about-us',
		'contact-us',
		'privacy-policy',
	);

	if ( in_array( $slug, $known_pages, true ) ) {
		unset( $query_vars['quantum_article'] );
		$query_vars['pagename'] = $slug;
		return $query_vars;
	}

	$reserved = array(
		'glossary',
		'scientists',
		'topic',
		'page',
		'feed',
		'author',
		'category',
		'tag',
		'search',
		'wp-json',
		'wp-admin',
		'wp-login',
		'xmlrpc',
		'robots',
		'favicon',
		'sitemap',
	);

	// مسیر رزرو‌شده → بگذار WordPress خودش تصمیم بگیرد.
	if ( in_array( $slug, $reserved, true ) ) {
		unset( $query_vars['quantum_article'] );
		return $query_vars;
	}

	// اگر مقالهٔ کوانتومی با این اسلاگ هست → همین درست است.
	$article = get_page_by_path( $slug, OBJECT, 'quantum_article' );
	if ( $article instanceof WP_Post ) {
		return $query_vars;
	}

	// مقاله نبود. حالا ببین «برگه» با این اسلاگ هست؟
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		unset( $query_vars['quantum_article'] );
		$query_vars['pagename'] = $slug; // برگه را درست نشان بده.
		return $query_vars;
	}

	// نه مقاله بود نه برگه → بگذار طبیعی 404 شود.
	unset( $query_vars['quantum_article'] );
	return $query_vars;
}
add_filter( 'request', 'qpedia_child_filter_article_request' );

/**
 * لینک درست مقاله‌های تخت
 */
function qpedia_child_article_permalink( $post_link, $post ) {
	if ( isset( $post->post_type ) && 'quantum_article' === $post->post_type ) {
		return home_url( '/' . $post->post_name . '/' );
	}

	return $post_link;
}
add_filter( 'post_type_link', 'qpedia_child_article_permalink', 10, 2 );

/**
 * یک بار flush پس از جایگزینی فایل
 * (نسخه عوض شد تا با آپلود این فایل، rewriteها یک‌بار به‌روز شوند)
 */
function qpedia_child_maybe_flush_rewrites() {
	// بهینه‌سازی: flush فقط در پیشخوان اجرا شود، نه در هر بازدید کاربر.
	// flush_rewrite_rules عملیات سنگینی است و روی صفحات عمومی جایی ندارد.
	if ( ! is_admin() ) {
		return;
	}

	$version = 'qpedia-lite-2026-09-06-front3';

	if ( get_option( 'qpedia_child_rewrite_version' ) !== $version ) {
		flush_rewrite_rules();
		update_option( 'qpedia_child_rewrite_version', $version, false );
	}
}
add_action( 'admin_init', 'qpedia_child_maybe_flush_rewrites', 99 );

/**
 * جمع‌کردن مسیر قدیمی glossary
 */
function qpedia_child_redirect_legacy_glossary() {
	$raw = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

	// بهینه‌سازی: اگر رشتهٔ glossary اصلاً در آدرس نیست، بی‌خود
	// wp_parse_url را صدا نزن. این تابع روی همهٔ بازدیدها اجرا می‌شود.
	if ( '' === $raw || false === strpos( $raw, 'glossary' ) ) {
		return;
	}

	$request_path = wp_parse_url( wp_unslash( $raw ), PHP_URL_PATH );
	$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';

	if ( 'glossary' === $request_path ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'qpedia_child_redirect_legacy_glossary', 1 );

/**
 * تنظیم queryهای ضروری
 */
function qpedia_child_main_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// اگر خانه روی «آخرین نوشته‌ها» باشد، مقالات کوانتوم را نشان بده.
	if ( $query->is_home() ) {
		$query->set( 'post_type', array( 'quantum_article' ) );
		$query->set( 'posts_per_page', 12 );
		$query->set( 'ignore_sticky_posts', true );
		return;
	}

	// جست‌وجو: مقاله + دانشمند + برگه.
	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'quantum_article', 'quantum_scientist', 'page' ) );
		$query->set( 'posts_per_page', 12 );
		return;
	}

	// آرشیو دانشمندان.
	if ( $query->is_post_type_archive( 'quantum_scientist' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'ignore_sticky_posts', true );
		return;
	}

	// آرشیو دستهٔ کوانتومی.
	if ( $query->is_tax( 'quantum_category' ) ) {
		$query->set( 'post_type', array( 'quantum_article' ) );
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'qpedia_child_main_queries' );

/**
 * نرمال‌سازی جست‌وجوی فارسی
 */
function qpedia_child_normalize_search_query( $query ) {
	if ( is_search() ) {
		$query = str_replace( array( 'ي', 'ك', '‌' ), array( 'ی', 'ک', ' ' ), $query );
	}
	return $query;
}
add_filter( 'get_search_query', 'qpedia_child_normalize_search_query' );


/**
 * QPEDIA — وصلهٔ سایت‌مپ بومی وردپرس
 */
if ( ! function_exists( 'qpedia_is_native_sitemap_request' ) ) {
	function qpedia_is_native_sitemap_request() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = is_string( $path ) ? trim( $path, '/' ) : '';

		if ( '' === $path ) {
			return false;
		}

		return (bool) preg_match( '/^wp-sitemap(?:-[a-z0-9_-]+)*(?:-\d+)?\.xml$/i', $path );
	}
}

if ( ! function_exists( 'qpedia_native_sitemap_pre_handle_404' ) ) {
	function qpedia_native_sitemap_pre_handle_404( $preempt, $wp_query ) {
		if ( is_admin() || ! qpedia_is_native_sitemap_request() ) {
			return $preempt;
		}

		if ( is_object( $wp_query ) && isset( $wp_query->is_404 ) ) {
			$wp_query->is_404 = false;
		}

		status_header( 200 );
		return true;
	}
}
add_filter( 'pre_handle_404', 'qpedia_native_sitemap_pre_handle_404', 10, 2 );

if ( ! function_exists( 'qpedia_filter_sitemap_provider' ) ) {
	function qpedia_filter_sitemap_provider( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}
}
add_filter( 'wp_sitemaps_add_provider', 'qpedia_filter_sitemap_provider', 10, 2 );

if ( ! function_exists( 'qpedia_filter_sitemap_taxonomies' ) ) {
	function qpedia_filter_sitemap_taxonomies( $taxonomies ) {
		unset( $taxonomies['article_domain'] );
		unset( $taxonomies['scientist_field'] );

		return $taxonomies;
	}
}
add_filter( 'wp_sitemaps_taxonomies', 'qpedia_filter_sitemap_taxonomies' );


/**
 * پیدا کردن آدرس برگه از بین چند اسلاگ محتمل.
 */
function qpedia_child_find_page_url( $candidate_slugs, $fallback_path = '/' ) {
	if ( empty( $candidate_slugs ) || ! is_array( $candidate_slugs ) ) {
		return home_url( $fallback_path );
	}

	foreach ( $candidate_slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	return home_url( $fallback_path );
}


/**
 * بی‌اثر کردن شورت‌کدهای مرده افزونه‌های قبلی برای جلوگیری از نمایش متن خام
 */
add_shortcode( 'qsci_card', '__return_empty_string' );
add_shortcode( 'qpt_card', '__return_empty_string' );
add_shortcode( 'qterm', '__return_empty_string' );


require_once get_stylesheet_directory() . '/inc/glossary-post-type.php';
require_once get_stylesheet_directory() . '/inc/glossary-assets.php';
require_once get_stylesheet_directory() . '/inc/glossary-cache.php';
require_once get_stylesheet_directory() . '/inc/glossary-content.php';

/* حذف شد: پیام دیباگ unfiltered_html در پیشخوان. کارش تمام شده بود. */



/**
 * Quantum Pedia Child Theme Functions
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

/*
 * حذف شد: تابع تکراری qpedia_child_setup.
 * کار آن با qpedia_child_load_textdomain (بالای همین فایل) یکی بود.
 */

/*
 * حذف شد: تابع تکراری qpedia_child_enqueue_styles.
 * همان استایل qpedia-layouts را دوباره صف می‌کرد و نسخهٔ ثابت 1.0.3 داشت
 * که جلوی به‌روزرسانی کش را می‌گرفت. حالا فقط
 * qpedia_child_enqueue_assets (بالای همین فایل) این کار را می‌کند.
 */

// ۳. درج خودکار متاتگ‌های سئو و OpenGraph در هدر مقالات
add_action( 'wp_head', 'qpedia_auto_seo_meta_tags', 1 );
function qpedia_auto_seo_meta_tags() {
    if ( ! is_singular( 'quantum_article' ) ) {
        return;
    }

    $post_id   = get_the_ID();
    $post      = get_post( $post_id );
    $title     = esc_attr( get_the_title( $post_id ) . ' | دانشنامه کوانتوم پدیا' );
    $url       = esc_url( get_permalink( $post_id ) );
    $image     = esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ?: get_site_icon_url( 512 ) );
    $published = esc_attr( get_the_date( 'c', $post_id ) );
    $modified  = esc_attr( get_the_modified_date( 'c', $post_id ) );

    $desc = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    if ( empty( $desc ) ) {
        $desc = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
    }
    $desc = esc_attr( $desc );

    echo "\n<!-- Qpedia SEO & OpenGraph Meta Tags -->\n";
    echo '<meta name="description" content="' . $desc . '">' . "\n";
    echo '<link rel="canonical" href="' . $url . '">' . "\n";

    echo '<meta property="og:locale" content="fa_IR">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $desc . '">' . "\n";
    echo '<meta property="og:url" content="' . $url . '">' . "\n";
    echo '<meta property="og:site_name" content="کوانتوم پدیا">' . "\n";
    if ( $image ) {
        echo '<meta property="og:image" content="' . $image . '">' . "\n";
    }
    echo '<meta property="article:published_time" content="' . $published . '">' . "\n";
    echo '<meta property="article:modified_time" content="' . $modified . '">' . "\n";

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $desc . '">' . "\n";
    if ( $image ) {
        echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
    }
    echo "<!-- /Qpedia SEO -->\n\n";
}

// ۴. درج ساختار داده اسکیما (JSON-LD) استاندارد گوگل
add_action( 'wp_head', 'qpedia_auto_article_schema', 2 );
function qpedia_auto_article_schema() {
    if ( ! is_singular( 'quantum_article' ) ) {
        return;
    }

    $post_id   = get_the_ID();
    $post      = get_post( $post_id );
    $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
    $excerpt   = wp_strip_all_tags( get_the_excerpt( $post_id ) );

    if ( empty( $excerpt ) ) {
        $excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 35, '...' );
    }

    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'ScholarlyArticle',
        'headline'         => get_the_title( $post_id ),
        'description'      => $excerpt,
        'inLanguage'       => 'fa-IR',
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post_id ),
        ),
        'datePublished'    => get_the_date( 'c', $post_id ),
        'dateModified'     => get_the_modified_date( 'c', $post_id ),
        'author'           => array(
            '@type' => 'Organization',
            'name'  => 'کوانتوم پدیا',
            'url'   => home_url(),
        ),
        'publisher'        => array(
            '@type' => 'Organization',
            'name'  => 'کوانتوم پدیا',
            'url'   => home_url(),
            'logo'  => array(
                '@type' => 'ImageObject',
                'url'   => get_site_icon_url( 512 ) ?: home_url( '/wp-content/uploads/logo.png' ),
            ),
        ),
    );

    if ( $image_url ) {
        $schema['image'] = $image_url;
    }

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}

// ۵. ساخت خودکار نقشه سایت اختصاصی مقالات در qpedia.ir/quantum-sitemap.xml
add_action( 'init', 'qpedia_register_custom_sitemap_rewrite' );
function qpedia_register_custom_sitemap_rewrite() {
    add_rewrite_rule( '^quantum-sitemap\.xml$', 'index.php?qpedia_sitemap=1', 'top' );
}

add_filter( 'query_vars', 'qpedia_register_custom_sitemap_query_vars' );
function qpedia_register_custom_sitemap_query_vars( $vars ) {
    $vars[] = 'qpedia_sitemap';
    return $vars;
}

add_action( 'template_redirect', 'qpedia_render_custom_sitemap' );
function qpedia_render_custom_sitemap() {
    if ( get_query_var( 'qpedia_sitemap' ) != 1 ) {
        return;
    }

    // بهینه‌سازی: پیش‌تر همهٔ آبجکت‌های کامل پست را می‌کشید.
    // حالا فقط شناسه‌ها می‌آیند و کش متا/ترم غیرفعال است.
    $articles = get_posts( array(
        'post_type'              => 'quantum_article',
        'post_status'            => 'publish',
        'posts_per_page'         => 500,
        'orderby'                => 'modified',
        'order'                  => 'DESC',
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );

    header( 'Content-Type: application/xml; charset=utf-8', true, 200 );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    echo "  <url>\n";
    echo "    <loc>" . esc_url( home_url( '/' ) ) . "</loc>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    echo "  </url>\n";

    foreach ( $articles as $post_id ) {
        echo "  <url>\n";
        echo "    <loc>" . esc_url( get_permalink( $post_id ) ) . "</loc>\n";
        echo "    <lastmod>" . esc_html( get_the_modified_date( 'Y-m-d\TH:i:sP', $post_id ) ) . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }

    echo '</urlset>' . "\n";
    exit;
}


/**
 * نوار نویسندهٔ پایان مقاله — آبی تیره با تایپ سایبری سبز (انگلیسی).
 * نام و اطلاعات به‌صورت خودکار از «نویسندهٔ برگه» خوانده می‌شود.
 * طبق خواستهٔ مالک، هیچ «نام کاربری» وردپرسی اینجا درج نمی‌شود؛
 * کلید نقشه = «نام نمایشی» نویسنده است (تطابق حساس به بزرگی حروف، نقطه و فاصله نیست).
 */
function qpedia_get_author_credentials( $author_id ) {
		$authors = array(
		'Reza Darvishi' => 'Reza Darvishi // Chemical Engineer, Petroleum University of Technology, Ahvaz — Independent Quantum Researcher',
		'Adel Lak'      => 'Adel Lak // Electrical & Electronics Engineer, Naghsh-e Jahan University, Isfahan — Quantum Researcher',
		'M.R. Bardia'   => 'M.R. Bardia // Founder & Editor, QPedia — Graduate of Jundishapur University, Ahvaz',
	);

	$needle = qpedia_normalize_author_name( get_the_author_meta( 'display_name', (int) $author_id ) );

	foreach ( $authors as $key => $line ) {
		if ( $needle === qpedia_normalize_author_name( $key ) ) {
			return $line;
		}
	}

	// تطابق اغماضی: اگر نام نمایشی بخشی از یکی از نام‌های نقشه باشد (مثلاً فقط «Bardia»).
	if ( mb_strlen( $needle ) >= 4 ) {
		foreach ( $authors as $key => $line ) {
			$norm_key = qpedia_normalize_author_name( $key );

			if ( strpos( $norm_key, $needle ) !== false || strpos( $needle, $norm_key ) !== false ) {
				return $line;
			}
		}
	}

	return ''; // نویسندهٔ ناشناس → نوار نمایش داده نمی‌شود (محافظ بی‌صدا).
}

/**
 * نرمال‌سازی نام برای تطابق: حروف کوچک + حذف نقطه، فاصله و ویرگول عربی.
 * «M.R. Bardia» و «M.R.Bardia» هر دو به «mrbardia» تبدیل می‌شوند.
 */
function qpedia_normalize_author_name( $name ) {
	if ( ! is_string( $name ) ) {
		return '';
	}

	return str_replace( array( '.', ' ', '،' ), '', mb_strtolower( trim( $name ), 'UTF-8' ) );
}

function qpedia_author_bar() {
	if ( ! is_singular( array( 'quantum_article', 'quantum_scientist' ) ) ) {
		return;
	}

	$line = qpedia_get_author_credentials( (int) get_post_field( 'post_author', get_the_ID() ) );

	if ( ! $line ) {
		return;
	}

	$parts = array_map( 'trim', explode( '//', $line, 2 ) );
	$name  = $parts[0];
	$cred  = isset( $parts[1] ) ? $parts[1] : '';

	echo '<div class="qp-author-bar" role="note" aria-label="Author">';
	echo '<span class="qp-author-bar__name">' . esc_html( $name ) . '</span>';

	if ( $cred ) {
		echo '<span class="qp-author-bar__sep">//</span>';
		echo '<span class="qp-author-bar__cred">' . esc_html( $cred ) . '</span>';
	}

	echo '</div>';
}
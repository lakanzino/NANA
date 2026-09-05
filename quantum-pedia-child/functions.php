<?php
/**
 * Quantum Pedia Child — نسخهٔ تمیز و یکپارچه (۱۴۰۵/۰۶/۱۴)
 *
 * این فایل از ادغام نسخه‌های گیت‌هاب ساخته شد و ایرادهای زیر رفع شده‌اند:
 * - چسبیدن دو functions.php به هم (توابع تکراری enqueue/textdomain)
 * - نوتیس دیباگ unfiltered_html در پیشخوان
 * - require بدون گارد برای فایل‌های glossary
 * - ۸ درخواست ۴۰۴ فونت والد (خنثی‌سازی بدون deregister)
 * - بلعیده‌شدن برگه‌ها و quantum-sitemap.xml توسط قانون catch-all
 * - نرمال‌سازی جست‌وجوی فارسی فقط روی نمایش، نه روی خود کوئری
 *
 * @package Quantum_Pedia_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QPEDIA_CHILD_VERSION', '2026.09.05-ui4' );

/**
 * بارگذاری textdomain پوستهٔ فرزند.
 */
function qpedia_child_load_textdomain() {
	load_child_theme_textdomain( 'quantum-pedia-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'qpedia_child_load_textdomain' );

/**
 * استایل‌ها و اسکریپت‌های ضروری پوستهٔ فرزند.
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
			(string) filemtime( $layouts_file )
		);
	}

	$custom_file = get_stylesheet_directory() . '/assets/css/custom.css';
	if ( file_exists( $custom_file ) ) {
		wp_enqueue_style(
			'qpedia-child-custom',
			get_stylesheet_directory_uri() . '/assets/css/custom.css',
			array( 'qpedia-layouts' ),
			(string) filemtime( $custom_file )
		);
	}

	$custom_js = get_stylesheet_directory() . '/assets/js/custom.js';
	if ( file_exists( $custom_js ) ) {
		wp_enqueue_script(
			'qpedia-child-custom',
			get_stylesheet_directory_uri() . '/assets/js/custom.js',
			array(),
			(string) filemtime( $custom_js ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'qpedia_child_enqueue_assets', 20 );

/**
 * خنثی‌سازی fonts.css والد بدون شکستن زنجیرهٔ استایل.
 *
 * هرگز wp_deregister_style( 'quantum-pedia-fonts' ) نزنید.
 * dequeue هندل را ثبت‌شده نگه می‌دارد ولی ۸ فایل ۴۰۴ را لود نمی‌کند.
 */
function qpedia_child_neutralize_parent_fonts() {
	wp_dequeue_style( 'quantum-pedia-fonts' );
}
add_action( 'wp_enqueue_scripts', 'qpedia_child_neutralize_parent_fonts', 30 );

/**
 * پاک‌سازی سبکِ head.
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * ثبت ساختارهای اصلی محتوا.
 */
function qpedia_child_register_content_types() {
	register_post_type(
		'quantum_article',
		array(
			'labels'             => array(
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
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'query_var'          => 'quantum_article',
		)
	);

	register_post_type(
		'quantum_scientist',
		array(
			'labels'             => array(
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
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
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
			'labels'            => array(
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
 * rewriteهای ضروری سایت.
 */
function qpedia_child_rewrite_rules() {
	add_rewrite_rule(
		'^quantum-sitemap\\.xml$',
		'index.php?qpedia_sitemap=1',
		'top'
	);

	// مقاله‌های تخت: /qubit/ — مسیرهای سیستمی با negative lookahead مستثنا شده‌اند.
	add_rewrite_rule(
		'^(?!scientists/?$)(?!topic/)(?!wp-admin/?$)(?!wp-json/?$)(?!feed/?$)(?!page/)(?!search/?$)(?!robots\\.txt$)(?!favicon\\.ico$)(?!xmlrpc\\.php$)(?!wp-login\\.php$)(?!sitemap.*\\.xml$)(?!quantum-sitemap\\.xml$)([a-z0-9][a-z0-9\\-]{2,})/?$',
		'index.php?quantum_article=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qpedia_child_rewrite_rules', 20 );

/**
 * متغیر کوئری نقشهٔ سایت اختصاصی.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function qpedia_register_custom_sitemap_query_vars( $vars ) {
	$vars[] = 'qpedia_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'qpedia_register_custom_sitemap_query_vars' );

/**
 * پیدا کردن پست منتشرشده با اسلاگ و نوع مشخص.
 *
 * @param string $slug      اسلاگ.
 * @param string $post_type نوع پست.
 * @return WP_Post|null
 */
function qpedia_child_get_post_by_slug( $slug, $post_type ) {
	$posts = get_posts(
		array(
			'name'                   => $slug,
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return ( ! empty( $posts ) && $posts[0] instanceof WP_Post ) ? $posts[0] : null;
}

/**
 * جلوگیری از بلعیده‌شدن مسیرهای سیستمی، برگه‌ها و نقشهٔ سایت.
 *
 * @param array<string,mixed> $query_vars Query vars.
 * @return array<string,mixed>
 */
function qpedia_child_filter_article_request( $query_vars ) {
	if ( empty( $query_vars['quantum_article'] ) ) {
		return $query_vars;
	}

	$slug = sanitize_title_for_query( $query_vars['quantum_article'] );

	$known_pages = array(
		'about',
		'about-us',
		'contact',
		'contact-us',
		'privacy',
		'privacy-policy',
		'terms',
		'terms-of-service',
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
		'quantum-sitemap',
	);

	if ( in_array( $slug, $reserved, true ) ) {
		unset( $query_vars['quantum_article'] );
		return $query_vars;
	}

	$article = qpedia_child_get_post_by_slug( $slug, 'quantum_article' );
	if ( $article instanceof WP_Post ) {
		return $query_vars;
	}

	$page = qpedia_child_get_post_by_slug( $slug, 'page' );
	if ( $page instanceof WP_Post ) {
		unset( $query_vars['quantum_article'] );
		$query_vars['pagename'] = $slug;
		return $query_vars;
	}

	unset( $query_vars['quantum_article'] );
	return $query_vars;
}
add_filter( 'request', 'qpedia_child_filter_article_request' );

/**
 * لینک تخت مقاله‌ها: /{slug}/
 *
 * @param string  $post_link Permalink.
 * @param WP_Post $post      Post object.
 * @return string
 */
function qpedia_child_article_permalink( $post_link, $post ) {
	if ( isset( $post->post_type ) && 'quantum_article' === $post->post_type && ! empty( $post->post_name ) ) {
		return home_url( '/' . $post->post_name . '/' );
	}

	return $post_link;
}
add_filter( 'post_type_link', 'qpedia_child_article_permalink', 10, 2 );

/**
 * یک‌بار flush پس از جایگزینی فایل.
 */
function qpedia_child_maybe_flush_rewrites() {
	$version = 'qpedia-child-2026-09-05-ui4';
	if ( get_option( 'qpedia_child_rewrite_version' ) !== $version ) {
		flush_rewrite_rules( false );
		update_option( 'qpedia_child_rewrite_version', $version, false );
	}
}
add_action( 'init', 'qpedia_child_maybe_flush_rewrites', 99 );

/**
 * جمع‌کردن مسیر قدیمی glossary.
 */
function qpedia_child_redirect_legacy_glossary() {
	$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';

	if ( 'glossary' === $request_path ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'qpedia_child_redirect_legacy_glossary', 1 );

/**
 * تنظیم queryهای ضروری.
 *
 * @param WP_Query $query Main query.
 */
function qpedia_child_main_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_home() ) {
		$query->set( 'post_type', array( 'quantum_article' ) );
		$query->set( 'posts_per_page', 12 );
		$query->set( 'ignore_sticky_posts', true );
		return;
	}

	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'quantum_article', 'quantum_scientist', 'page' ) );
		$query->set( 'posts_per_page', 12 );

		$s = $query->get( 's' );
		if ( is_string( $s ) && '' !== $s ) {
			$query->set( 's', qpedia_child_normalize_persian_text( $s ) );
		}
		return;
	}

	if ( $query->is_post_type_archive( 'quantum_scientist' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'ignore_sticky_posts', true );
		return;
	}

	if ( $query->is_tax( 'quantum_category' ) ) {
		$query->set( 'post_type', array( 'quantum_article' ) );
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'qpedia_child_main_queries' );

/**
 * نرمال‌سازی حروف عربی/نیم‌فاصله در متن فارسی.
 *
 * @param string $text Text.
 * @return string
 */
function qpedia_child_normalize_persian_text( $text ) {
	return str_replace( array( 'ي', 'ك', '‌' ), array( 'ی', 'ک', ' ' ), $text );
}

/**
 * نرمال‌سازی نمایش عبارت جست‌وجو.
 *
 * @param string $query Search query.
 * @return string
 */
function qpedia_child_normalize_search_query( $query ) {
	if ( is_search() ) {
		$query = qpedia_child_normalize_persian_text( $query );
	}
	return $query;
}
add_filter( 'get_search_query', 'qpedia_child_normalize_search_query' );

/**
 * آیا درخواست فعلی سایت‌مپ بومی وردپرس است؟
 *
 * @return bool
 */
function qpedia_is_native_sitemap_request() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
	$path        = is_string( $path ) ? trim( $path, '/' ) : '';

	if ( '' === $path ) {
		return false;
	}

	return (bool) preg_match( '/^wp-sitemap(?:-[a-z0-9_-]+)*(?:-\d+)?\.xml$/i', $path );
}

/**
 * جلوگیری از هدر ۴۰۴ روی سایت‌مپ بومی.
 *
 * @param bool     $preempt  Whether to short-circuit.
 * @param WP_Query $wp_query Query.
 * @return bool
 */
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
add_filter( 'pre_handle_404', 'qpedia_native_sitemap_pre_handle_404', 10, 2 );

/**
 * حذف فهرست کاربران از سایت‌مپ.
 *
 * @param WP_Sitemaps_Provider|false $provider Provider.
 * @param string                     $name     Name.
 * @return WP_Sitemaps_Provider|false
 */
function qpedia_filter_sitemap_provider( $provider, $name ) {
	if ( 'users' === $name ) {
		return false;
	}

	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'qpedia_filter_sitemap_provider', 10, 2 );

/**
 * حذف تاکسونومی‌های مرده از سایت‌مپ.
 *
 * @param array<string,WP_Taxonomy> $taxonomies Taxonomies.
 * @return array<string,WP_Taxonomy>
 */
function qpedia_filter_sitemap_taxonomies( $taxonomies ) {
	unset( $taxonomies['article_domain'], $taxonomies['scientist_field'] );
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'qpedia_filter_sitemap_taxonomies' );

/**
 * خروجی XML نقشهٔ سایت اختصاصی مقالات: /quantum-sitemap.xml
 */
function qpedia_render_custom_sitemap() {
	if ( (string) get_query_var( 'qpedia_sitemap' ) !== '1' ) {
		return;
	}

	$articles = get_posts(
		array(
			'post_type'      => 'quantum_article',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	header( 'Content-Type: application/xml; charset=utf-8', true, 200 );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	echo "  <url>\n";
	echo '    <loc>' . esc_url( home_url( '/' ) ) . "</loc>\n";
	echo "    <changefreq>daily</changefreq>\n";
	echo "    <priority>1.0</priority>\n";
	echo "  </url>\n";

	foreach ( $articles as $post ) {
		echo "  <url>\n";
		echo '    <loc>' . esc_url( get_permalink( $post ) ) . "</loc>\n";
		echo '    <lastmod>' . esc_html( get_the_modified_date( 'c', $post ) ) . "</lastmod>\n";
		echo "    <changefreq>weekly</changefreq>\n";
		echo "    <priority>0.8</priority>\n";
		echo "  </url>\n";
	}

	echo '</urlset>' . "\n";
	exit;
}
add_action( 'template_redirect', 'qpedia_render_custom_sitemap' );

/**
 * پیدا کردن آدرس برگه از بین چند اسلاگ محتمل.
 *
 * @param string[] $candidate_slugs Candidate slugs.
 * @param string   $fallback_path   Fallback path.
 * @return string
 */
function qpedia_child_find_page_url( $candidate_slugs, $fallback_path = '/' ) {
	if ( empty( $candidate_slugs ) || ! is_array( $candidate_slugs ) ) {
		return home_url( $fallback_path );
	}

	foreach ( $candidate_slugs as $slug ) {
		$page = qpedia_child_get_post_by_slug( $slug, 'page' );
		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	return home_url( $fallback_path );
}

/**
 * بی‌اثر کردن شورت‌کدهای مردهٔ افزونه‌های قبلی.
 */
add_shortcode( 'qsci_card', '__return_empty_string' );
add_shortcode( 'qpt_card', '__return_empty_string' );
add_shortcode( 'qterm', '__return_empty_string' );

/**
 * متاتگ‌های سئو و Open Graph برای مقالات.
 */
function qpedia_auto_seo_meta_tags() {
	if ( ! is_singular( 'quantum_article' ) ) {
		return;
	}

	$post_id = get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$title     = esc_attr( get_the_title( $post_id ) . ' | دانشنامه کوانتوم پدیا' );
	$url       = esc_url( get_permalink( $post_id ) );
	$thumb     = get_the_post_thumbnail_url( $post_id, 'large' );
	$image     = esc_url( $thumb ? $thumb : get_site_icon_url( 512 ) );
	$published = esc_attr( get_the_date( 'c', $post_id ) );
	$modified  = esc_attr( get_the_modified_date( 'c', $post_id ) );

	$desc = wp_strip_all_tags( get_the_excerpt( $post_id ) );
	if ( '' === $desc ) {
		$desc = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
	}
	$desc = esc_attr( $desc );

	echo "\n<!-- Qpedia SEO & OpenGraph Meta Tags -->\n";
	echo '<meta name="description" content="' . $desc . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<link rel="canonical" href="' . $url . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta property="og:locale" content="fa_IR">' . "\n";
	echo '<meta property="og:type" content="article">' . "\n";
	echo '<meta property="og:title" content="' . $title . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta property="og:description" content="' . $desc . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta property="og:url" content="' . $url . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta property="og:site_name" content="کوانتوم پدیا">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . $image . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<meta property="article:published_time" content="' . $published . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta property="article:modified_time" content="' . $modified . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . $title . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<meta name="twitter:description" content="' . $desc . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( $image ) {
		echo '<meta name="twitter:image" content="' . $image . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo "<!-- /Qpedia SEO -->\n\n";
}
add_action( 'wp_head', 'qpedia_auto_seo_meta_tags', 1 );

/**
 * اسکیما JSON-LD مقاله.
 */
function qpedia_auto_article_schema() {
	if ( ! is_singular( 'quantum_article' ) ) {
		return;
	}

	$post_id = get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$image_url = get_the_post_thumbnail_url( $post_id, 'full' );
	$excerpt   = wp_strip_all_tags( get_the_excerpt( $post_id ) );
	if ( '' === $excerpt ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 35, '...' );
	}

	$logo = get_site_icon_url( 512 );
	if ( ! $logo ) {
		$logo = get_stylesheet_directory_uri() . '/assets/images/qpedia-logo-blue.png';
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
			'url'   => home_url( '/' ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => 'کوانتوم پدیا',
			'url'   => home_url( '/' ),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			),
		),
	);

	if ( $image_url ) {
		$schema['image'] = $image_url;
	}

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'qpedia_auto_article_schema', 2 );

/**
 * نرمال‌سازی نام نویسنده برای تطابق نوار امضا.
 *
 * @param string $name Display name.
 * @return string
 */
function qpedia_normalize_author_name( $name ) {
	if ( ! is_string( $name ) ) {
		return '';
	}

	return str_replace( array( '.', ' ', '،' ), '', mb_strtolower( trim( $name ), 'UTF-8' ) );
}

/**
 * نقشهٔ نام نمایشی نویسنده → خط اعتبار.
 *
 * @param int $author_id User ID.
 * @return string
 */
function qpedia_get_author_credentials( $author_id ) {
	$authors = array(
		'Reza Darvishi' => 'Reza Darvishi // Chemical Engineer, Petroleum University of Technology, Ahvaz — Independent Quantum Researcher',
		'Adel Lak'      => 'Adel Lak // Electrical & Electronics Engineer, Naghsh-e Jahan University, Isfahan — Quantum Researcher',
		'M.R. Bardia'   => 'M.R. Bardia // Founder & Editor, QPedia — Graduate of Jundishapur University, Ahvaz',
	);

	$needle = qpedia_normalize_author_name( get_the_author_meta( 'display_name', (int) $author_id ) );
	if ( '' === $needle ) {
		return '';
	}

	foreach ( $authors as $key => $line ) {
		if ( $needle === qpedia_normalize_author_name( $key ) ) {
			return $line;
		}
	}

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $needle ) >= 4 ) {
		foreach ( $authors as $key => $line ) {
			$norm_key = qpedia_normalize_author_name( $key );
			if ( false !== strpos( $norm_key, $needle ) || false !== strpos( $needle, $norm_key ) ) {
				return $line;
			}
		}
	}

	return '';
}

/**
 * نوار نویسندهٔ پایان مقاله.
 */
function qpedia_author_bar() {
	if ( ! is_singular( array( 'quantum_article', 'quantum_scientist' ) ) ) {
		return;
	}

	$line = qpedia_get_author_credentials( (int) get_post_field( 'post_author', get_the_ID() ) );
	if ( '' === $line ) {
		return;
	}

	$parts = array_map( 'trim', explode( '//', $line, 2 ) );
	$name  = $parts[0];
	$cred  = isset( $parts[1] ) ? $parts[1] : '';

	echo '<div class="qp-author-bar" role="note" aria-label="نویسنده">';
	echo '<span class="qp-author-bar__name">' . esc_html( $name ) . '</span>';
	if ( $cred ) {
		echo '<span class="qp-author-bar__sep">//</span>';
		echo '<span class="qp-author-bar__cred">' . esc_html( $cred ) . '</span>';
	}
	echo '</div>';
}

$qpedia_inc = get_stylesheet_directory() . '/inc/';
foreach ( array( 'glossary-post-type.php', 'glossary-assets.php', 'glossary-cache.php', 'glossary-content.php' ) as $qpedia_inc_file ) {
	$qpedia_inc_path = $qpedia_inc . $qpedia_inc_file;
	if ( is_readable( $qpedia_inc_path ) ) {
		require_once $qpedia_inc_path;
	}
}

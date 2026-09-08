<?php
/**
 * Quantum Pedia parent theme setup.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QUANTUM_PEDIA_VERSION', '1.0.0' );

/**
 * Load core files.
 */
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/sanitization.php';
require_once get_template_directory() . '/inc/template-functions.php';

if ( ! function_exists( 'quantum_pedia_setup' ) ) {
	function quantum_pedia_setup() {
		load_theme_textdomain( 'quantum-pedia', get_template_directory() . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );

		set_post_thumbnail_size( 1200, 675, true );
		add_image_size( 'quantum-pedia-featured', 1200, 675, true );

		add_theme_support( 'custom-logo', array(
			'height'      => 80,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
		) );

		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		) );

		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor-style.css' );

		register_nav_menus( array(
			'primary' => esc_html__( 'Primary Menu', 'quantum-pedia' ),
			'footer'  => esc_html__( 'Footer Menu', 'quantum-pedia' ),
		) );
	}
}
add_action( 'after_setup_theme', 'quantum_pedia_setup' );

function quantum_pedia_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'quantum-pedia' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'quantum-pedia' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'quantum_pedia_widgets_init' );

function quantum_pedia_assets() {
	wp_enqueue_style( 'quantum-pedia-style', get_stylesheet_uri(), array(), QUANTUM_PEDIA_VERSION );
	wp_enqueue_style( 'quantum-pedia-main', get_template_directory_uri() . '/assets/css/main.css', array( 'quantum-pedia-style' ), QUANTUM_PEDIA_VERSION );
	wp_enqueue_style( 'quantum-pedia-fonts', get_template_directory_uri() . '/assets/css/fonts.css', array( 'quantum-pedia-main' ), QUANTUM_PEDIA_VERSION );

	if ( is_rtl() ) {
		wp_enqueue_style( 'quantum-pedia-rtl', get_template_directory_uri() . '/assets/css/rtl.css', array( 'quantum-pedia-main' ), QUANTUM_PEDIA_VERSION );
	}

	wp_enqueue_script( 'quantum-pedia-main', get_template_directory_uri() . '/assets/js/main.js', array(), QUANTUM_PEDIA_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'quantum_pedia_assets' );
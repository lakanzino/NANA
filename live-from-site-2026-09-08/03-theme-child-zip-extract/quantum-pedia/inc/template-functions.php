<?php
/**
 * Functions which enhance the theme by hooking into WordPress.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

if ( ! function_exists( 'quantum_pedia_body_classes' ) ) {
	/**
	 * Adds custom classes to the array of body classes.
	 */
	function quantum_pedia_body_classes( $classes ) {
		// Adds a class of hfeed to non-singular pages.
		if ( ! is_singular() ) {
			$classes[] = 'hfeed';
		}

		// Adds a class of no-sidebar when there is no sidebar present.
		if ( ! is_active_sidebar( 'sidebar-1' ) ) {
			$classes[] = 'no-sidebar';
		}

		return $classes;
	}
}
add_filter( 'body_class', 'quantum_pedia_body_classes' );

if ( ! function_exists( 'quantum_pedia_pingback_header' ) ) {
	/**
	 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
	 */
	function quantum_pedia_pingback_header() {
		if ( is_singular() && pings_open() ) {
			printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
		}
	}
}
add_action( 'wp_head', 'quantum_pedia_pingback_header' );
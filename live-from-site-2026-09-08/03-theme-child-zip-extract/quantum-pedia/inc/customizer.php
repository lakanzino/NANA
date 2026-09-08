<?php
/**
 * Quantum Pedia Customizer options.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

if ( ! function_exists( 'quantum_pedia_customize_register' ) ) {
	/**
	 * Add postMessage support for site title and description.
	 */
	function quantum_pedia_customize_register( $wp_customize ) {
		$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
		$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'blogname',
				array(
					'selector'        => '.site-title a',
					'render_callback' => 'quantum_pedia_customize_partial_blogname',
				)
			);
			$wp_customize->selective_refresh->add_partial(
				'blogdescription',
				array(
					'selector'        => '.site-description',
					'render_callback' => 'quantum_pedia_customize_partial_blogdescription',
				)
			);
		}

		// Add section for footer text.
		$wp_customize->add_section(
			'quantum_pedia_options',
			array(
				'title'    => esc_html__( 'Theme Options', 'quantum-pedia' ),
				'priority' => 30,
			)
		);

		// Footer copyright text.
		$wp_customize->add_setting(
			'quantum_pedia_footer_text',
			array(
				'default'           => esc_html__( 'All rights reserved.', 'quantum-pedia' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			'quantum_pedia_footer_text',
			array(
				'label'   => esc_html__( 'Footer Text', 'quantum-pedia' ),
				'section' => 'quantum_pedia_options',
				'type'    => 'text',
			)
		);
	}
}
add_action( 'customize_register', 'quantum_pedia_customize_register' );

if ( ! function_exists( 'quantum_pedia_customize_partial_blogname' ) ) {
	function quantum_pedia_customize_partial_blogname() {
		bloginfo( 'name' );
	}
}

if ( ! function_exists( 'quantum_pedia_customize_partial_blogdescription' ) ) {
	function quantum_pedia_customize_partial_blogdescription() {
		bloginfo( 'description' );
	}
}
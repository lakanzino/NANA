<?php
/**
 * Sanitization functions.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

if ( ! function_exists( 'quantum_pedia_sanitize_checkbox' ) ) {
	/**
	 * Sanitize checkbox.
	 */
	function quantum_pedia_sanitize_checkbox( $checked ) {
		return ( isset( $checked ) && true === $checked ) ? true : false;
	}
}

if ( ! function_exists( 'quantum_pedia_sanitize_select' ) ) {
	/**
	 * Sanitize select.
	 */
	function quantum_pedia_sanitize_select( $input, $setting ) {
		$input   = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;
		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}

if ( ! function_exists( 'quantum_pedia_sanitize_color' ) ) {
	/**
	 * Sanitize hex color.
	 */
	function quantum_pedia_sanitize_color( $color ) {
		if ( empty( $color ) || is_array( $color ) ) {
			return '';
		}
		if ( false === strpos( $color, '#' ) ) {
			$color = '#' . $color;
		}
		if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $color ) ) {
			return '';
		}
		return $color;
	}
}
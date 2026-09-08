/**
 * Quantum Pedia main scripts.
 */
( function() {
	'use strict';

	// Mobile menu toggle.
	var menuToggle = document.querySelector( '.menu-toggle' );
	var navigation = document.querySelector( '.main-navigation' );

	if ( menuToggle && navigation ) {
		menuToggle.addEventListener( 'click', function() {
			var expanded = menuToggle.getAttribute( 'aria-expanded' ) === 'true' || false;
			menuToggle.setAttribute( 'aria-expanded', ! expanded );
			navigation.classList.toggle( 'toggled' );
		} );
	}

	// Header search toggle.
	var searchToggle = document.querySelector( '.search-toggle' );
	var headerSearch = document.getElementById( 'header-search' );

	if ( searchToggle && headerSearch ) {
		searchToggle.addEventListener( 'click', function() {
			var hidden = headerSearch.hidden;
			headerSearch.hidden = ! hidden;
			searchToggle.setAttribute( 'aria-expanded', ! hidden );
		} );
	}
} )();
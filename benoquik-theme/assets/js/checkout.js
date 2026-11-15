/**
 * Checkout only: numeric keypad for PIN code and phone, correct autocomplete.
 * WooCommerce re-renders its checkout, so attributes are re-applied on change.
 */
( function () {
	'use strict';
	function apply() {
		document.querySelectorAll( 'input[id$="-postcode"], input[autocomplete="postal-code"]' ).forEach( function ( el ) {
			el.setAttribute( 'inputmode', 'numeric' );
			el.setAttribute( 'pattern', '[0-9]*' );
			if ( ! el.getAttribute( 'autocomplete' ) ) {
				el.setAttribute( 'autocomplete', 'postal-code' );
			}
		} );
		document.querySelectorAll( 'input[type="tel"], input[id$="-phone"]' ).forEach( function ( el ) {
			el.setAttribute( 'type', 'tel' );
			el.setAttribute( 'inputmode', 'tel' );
			if ( ! el.getAttribute( 'autocomplete' ) ) {
				el.setAttribute( 'autocomplete', 'tel' );
			}
		} );
		document.querySelectorAll( 'input[id="email"], input[type="email"]' ).forEach( function ( el ) {
			el.setAttribute( 'inputmode', 'email' );
			if ( ! el.getAttribute( 'autocomplete' ) ) {
				el.setAttribute( 'autocomplete', 'email' );
			}
		} );
	}
	var t;
	new MutationObserver( function () {
		clearTimeout( t );
		t = setTimeout( apply, 150 );
	} ).observe( document.body, { childList: true, subtree: true } );
	apply();
}() );

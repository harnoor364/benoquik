/**
 * Checkout only: international phone keypad/placeholder and correct autocomplete.
 * Postal codes are NOT forced numeric — UK, Canadian and other codes use letters.
 * WooCommerce re-renders its checkout, so attributes are re-applied on change.
 */
( function () {
	'use strict';
	function apply() {
		document.querySelectorAll( 'input[id$="-postcode"], input[autocomplete="postal-code"]' ).forEach( function ( el ) {
			el.removeAttribute( 'pattern' );
			el.setAttribute( 'inputmode', 'text' );
			if ( ! el.getAttribute( 'autocomplete' ) ) {
				el.setAttribute( 'autocomplete', 'postal-code' );
			}
		} );
		document.querySelectorAll( 'input[type="tel"], input[id$="-phone"]' ).forEach( function ( el ) {
			el.setAttribute( 'type', 'tel' );
			el.setAttribute( 'inputmode', 'tel' );
			if ( ! el.getAttribute( 'placeholder' ) ) {
				el.setAttribute( 'placeholder', '+1 555 000 0000' );
			}
			if ( ! el.getAttribute( 'autocomplete' ) ) {
				el.setAttribute( 'autocomplete', 'tel' );
			}
		} );
		// Block checkout ignores the PHP label filter; say "with country code" here.
		document.querySelectorAll( 'label[for$="-phone"]' ).forEach( function ( label ) {
			if ( ! /country code/i.test( label.textContent ) ) {
				label.textContent = label.textContent.replace( /^Phone/, 'Phone (with country code)' );
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

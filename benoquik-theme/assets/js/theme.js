/**
 * Benoquik theme — tiny vanilla JS (no jQuery).
 * 1. Header: adds .is-scrolled after the page scrolls (solid cream + shadow).
 * 2. Scroll reveal: elements with .bq-reveal fade/slide up into view.
 * Both respect prefers-reduced-motion via CSS.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.site-header' );
	if ( header ) {
		var ticking = false;
		var update = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 24 );
			ticking = false;
		};
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		}, { passive: true } );
		update();
	}

	var items = document.querySelectorAll( '.bq-reveal' );
	if ( ! items.length ) {
		return;
	}
	if ( ! ( 'IntersectionObserver' in window ) ) {
		items.forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
		return;
	}
	var io = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( entry.isIntersecting ) {
				entry.target.classList.add( 'is-visible' );
				io.unobserve( entry.target );
			}
		} );
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 } );
	items.forEach( function ( el ) { io.observe( el ); } );
}() );

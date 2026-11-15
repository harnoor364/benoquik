/**
 * Benoquik theme — tiny vanilla JS (no jQuery).
 * 1. Header: adds .is-scrolled after the page scrolls (solid white + shadow).
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

	/* Mobile menu: aria-expanded on the toggle, close when a link is tapped. */
	document.querySelectorAll( '.wp-block-navigation__responsive-container' ).forEach( function ( menu ) {
		var nav = menu.closest( '.wp-block-navigation' );
		var openBtn = nav && nav.querySelector( '.wp-block-navigation__responsive-container-open' );
		if ( ! openBtn ) {
			return;
		}
		if ( ! menu.id ) {
			menu.id = 'bq-menu-' + Math.random().toString( 36 ).slice( 2, 8 );
		}
		openBtn.setAttribute( 'aria-controls', menu.id );
		var sync = function () {
			openBtn.setAttribute( 'aria-expanded', menu.classList.contains( 'is-menu-open' ) ? 'true' : 'false' );
		};
		sync();
		new MutationObserver( sync ).observe( menu, { attributes: true, attributeFilter: [ 'class' ] } );
		menu.addEventListener( 'click', function ( e ) {
			var link = e.target.closest( 'a' );
			if ( link && menu.classList.contains( 'is-menu-open' ) ) {
				var close = menu.querySelector( '.wp-block-navigation__responsive-container-close' );
				if ( close ) {
					close.click();
				}
			}
		} );
	} );

	/* WooCommerce mini-cart drawer: make it inert while hidden so it can't take keyboard focus. */
	document.querySelectorAll( '.wc-block-mini-cart__drawer' ).forEach( function ( drawer ) {
		var syncDrawer = function () {
			drawer.inert = drawer.getAttribute( 'aria-hidden' ) === 'true';
		};
		syncDrawer();
		new MutationObserver( syncDrawer ).observe( drawer, { attributes: true, attributeFilter: [ 'aria-hidden' ] } );
	} );

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

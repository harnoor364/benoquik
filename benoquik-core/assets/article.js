/**
 * Single article only: open the table of contents on wide screens,
 * highlight the current section, and the "Copy link" share button.
 */
( function () {
	'use strict';
	var toc = document.querySelector( '.bq-toc__details' );
	if ( toc ) {
		var mq = window.matchMedia( '(min-width: 1024px)' );
		var sync = function () {
			if ( mq.matches ) {
				toc.setAttribute( 'open', '' );
			}
		};
		sync();
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', sync );
		}
		var links = toc.querySelectorAll( 'a[href^="#"]' );
		var targets = [];
		links.forEach( function ( a ) {
			var t = document.getElementById( decodeURIComponent( a.getAttribute( 'href' ).slice( 1 ) ) );
			if ( t ) {
				targets.push( { link: a, el: t } );
			}
			a.addEventListener( 'click', function () {
				if ( ! mq.matches ) {
					toc.removeAttribute( 'open' );
				}
			} );
		} );
		if ( 'IntersectionObserver' in window && targets.length ) {
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( e ) {
					if ( e.isIntersecting ) {
						targets.forEach( function ( t ) {
							var on = t.el === e.target;
							t.link.classList.toggle( 'is-active', on );
							if ( on ) {
								t.link.setAttribute( 'aria-current', 'location' );
							} else {
								t.link.removeAttribute( 'aria-current' );
							}
						} );
					}
				} );
			}, { rootMargin: '-15% 0px -70% 0px' } );
			targets.forEach( function ( t ) { io.observe( t.el ); } );
		}
	}

	document.querySelectorAll( '.bq-share__copy' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var status = btn.closest( '.bq-share' ).querySelector( '.bq-share__status' );
			var url = btn.getAttribute( 'data-url' );
			var done = function () { status.textContent = 'Link copied'; setTimeout( function () { status.textContent = ''; }, 2500 ); };
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( done, function () { window.prompt( 'Copy this link:', url ); } );
			} else {
				window.prompt( 'Copy this link:', url );
			}
		} );
	} );
}() );

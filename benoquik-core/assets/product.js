/**
 * Benoquik product page enhancements (vanilla JS; uses jQuery only to talk to
 * WooCommerce's own variation form, which is jQuery-based).
 * 1. Pack-size dropdowns become pill buttons (the real <select> stays in the
 *    form, visually hidden, so WooCommerce keeps working without JS).
 * 2. Delivery estimate by country.
 * 3. Sticky add-to-cart bar on mobile.
 */
( function () {
	'use strict';
	var $ = window.jQuery;

	/* ---------- 1. Pill selector ---------- */
	function enhanceSelect( select ) {
		if ( select.dataset.bqPills ) {
			return;
		}
		select.dataset.bqPills = '1';
		var row = select.closest( 'tr' ) || select.parentNode;
		var label = row.querySelector( 'label' );
		var group = document.createElement( 'div' );
		group.className = 'bq-pills';
		group.setAttribute( 'role', 'group' );
		group.setAttribute( 'aria-label', label ? label.textContent.trim() : 'Options' );

		function change( value ) {
			if ( $ ) {
				$( select ).val( value ).trigger( 'change' );
			} else {
				select.value = value;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
			sync();
		}
		function sync() {
			Array.prototype.forEach.call( group.children, function ( btn ) {
				var opt = select.querySelector( 'option[value="' + CSS.escape( btn.dataset.value ) + '"]' );
				var on = select.value === btn.dataset.value;
				btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				btn.classList.toggle( 'is-active', on );
				var unavailable = ! opt || opt.disabled || opt.classList.contains( 'disabled' );
				btn.disabled = unavailable && ! on;
			} );
		}
		Array.prototype.forEach.call( select.options, function ( opt ) {
			if ( ! opt.value ) {
				return;
			}
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'bq-pill';
			btn.dataset.value = opt.value;
			btn.textContent = opt.textContent;
			btn.addEventListener( 'click', function () {
				change( opt.value );
			} );
			group.appendChild( btn );
		} );
		select.classList.add( 'bq-visually-hidden' );
		select.setAttribute( 'tabindex', '-1' );
		select.setAttribute( 'aria-hidden', 'true' );
		select.parentNode.insertBefore( group, select );

		// Pre-select the first option so "Add to cart" is ready straight away.
		if ( ! select.value && group.firstChild ) {
			change( group.firstChild.dataset.value );
		}
		select.addEventListener( 'change', sync );
		if ( $ ) {
			$( select ).closest( 'form' ).on( 'woocommerce_update_variation_values reset_data', function () {
				setTimeout( sync, 0 );
			} );
		}
		sync();
	}

	function initPills() {
		document.querySelectorAll( 'form.variations_form select[name^="attribute_"]' ).forEach( enhanceSelect );
	}

	/* ---------- 2. Delivery estimate by country ---------- */
	function initDelivery() {
		document.querySelectorAll( '[data-bq-delivery]' ).forEach( function ( box ) {
			var cfg;
			try {
				cfg = JSON.parse( box.getAttribute( 'data-bq-delivery' ) );
			} catch ( e ) {
				return;
			}
			var select = box.querySelector( 'select' );
			var out = box.querySelector( '.bq-delivery__text' );
			if ( ! select || ! out ) {
				return;
			}
			function show() {
				var c = select.value;
				out.textContent = ( c && cfg.rules && cfg.rules[ c ] ) ? cfg.rules[ c ] : cfg.default;
			}
			select.addEventListener( 'change', function () {
				try { localStorage.setItem( 'bqCountry', select.value ); } catch ( err ) {}
				show();
			} );
			try {
				var saved = localStorage.getItem( 'bqCountry' );
				if ( saved && select.querySelector( 'option[value="' + saved + '"]' ) ) {
					select.value = saved;
				}
			} catch ( err ) {}
			show();
		} );
	}

	/* ---------- 3. Sticky add-to-cart (mobile) ---------- */
	function initSticky() {
		var bar = document.querySelector( '.bq-sticky-cart' );
		var main = document.querySelector( '.single_add_to_cart_button' );
		if ( ! bar || ! main ) {
			return;
		}
		var btn = bar.querySelector( 'button' );
		var price = bar.querySelector( '.bq-sticky-cart__price' );
		var pack = bar.querySelector( '.bq-sticky-cart__pack' );
		var pastButton = false;
		var footerVisible = false;
		function setVisible( on ) {
			bar.classList.toggle( 'is-visible', on );
			if ( on ) {
				bar.removeAttribute( 'aria-hidden' );
				bar.removeAttribute( 'inert' );
			} else {
				bar.setAttribute( 'aria-hidden', 'true' );
				bar.setAttribute( 'inert', '' );
			}
		}
		if ( 'IntersectionObserver' in window ) {
			// Show once the real button has scrolled up out of view; hide again near the footer.
			new IntersectionObserver( function ( entries ) {
				var e = entries[ 0 ];
				pastButton = ! e.isIntersecting && e.boundingClientRect.top < 0;
				setVisible( pastButton && ! footerVisible );
			} ).observe( main );
			var footer = document.querySelector( '.site-footer' );
			if ( footer ) {
				new IntersectionObserver( function ( entries ) {
					footerVisible = entries[ 0 ].isIntersecting;
					setVisible( pastButton && ! footerVisible );
				} ).observe( footer );
			}
		}
		btn.addEventListener( 'click', function () {
			var disabled = main.disabled || main.classList.contains( 'disabled' );
			if ( ! disabled ) {
				main.click();
				return;
			}
			main.closest( 'form' ).scrollIntoView( { behavior: 'smooth', block: 'center' } );
			var first = document.querySelector( '.bq-pill:not([disabled])' );
			if ( first ) {
				first.focus( { preventScroll: true } );
			}
		} );
		if ( $ ) {
			$( 'form.variations_form' ).on( 'found_variation', function ( e, variation ) {
				if ( variation && variation.price_html ) {
					price.innerHTML = variation.price_html;
				}
				if ( pack && variation && variation.attributes ) {
					var vals = Object.keys( variation.attributes ).map( function ( k ) { return variation.attributes[ k ]; } ).filter( Boolean );
					pack.textContent = vals.length ? ' · ' + vals.join( ', ' ) : '';
				}
			} );
		}
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}
	ready( function () {
		initPills();
		initDelivery();
		initSticky();
		// WooCommerce may build the variation form after load.
		if ( $ ) {
			$( document.body ).on( 'wc_variation_form', initPills );
		}
	} );
}() );

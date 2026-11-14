/* Pause / play button for the Benoquik marquee (WCAG 2.2.2). */
document.querySelectorAll( '.bq-marquee__toggle' ).forEach( function ( btn ) {
	btn.addEventListener( 'click', function () {
		var paused = btn.getAttribute( 'aria-pressed' ) !== 'true';
		btn.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
		btn.setAttribute( 'aria-label', paused ? 'Play scrolling text' : 'Pause scrolling text' );
		btn.firstElementChild.textContent = paused ? '▶' : '❚❚';
		btn.closest( '.bq-marquee' ).classList.toggle( 'is-paused', paused );
	} );
} );

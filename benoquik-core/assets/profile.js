/* User profile: choose a profile photo from the Media Library. */
jQuery( function ( $ ) {
	var frame;
	$( '.bq-avatar-choose' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( ! frame ) {
			frame = wp.media( { title: 'Choose profile photo', library: { type: 'image' }, multiple: false, button: { text: 'Use this photo' } } );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				var src = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				$( '#bq_avatar_id' ).val( a.id );
				$( '.bq-avatar-preview' ).html( $( '<img>', { src: src, width: 96, height: 96, alt: '', css: { borderRadius: '50%' } } ) );
				$( '.bq-avatar-remove' ).show();
			} );
		}
		frame.open();
	} );
	$( '.bq-avatar-remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#bq_avatar_id' ).val( '' );
		$( '.bq-avatar-preview' ).empty();
		$( this ).hide();
	} );
} );

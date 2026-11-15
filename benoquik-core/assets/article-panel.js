/**
 * Post editor sidebar: "Benoquik article details".
 * Medical reviewer, last reviewed date, references, hide product CTA,
 * reading time (automatic) and a warning when the featured image has no alt text.
 * No build step — plain wp.element.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var useEffect = wp.element.useEffect;
	var C = wp.components;
	var __ = wp.i18n.__;
	var PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;
	var PluginPrePublishPanel = ( wp.editor && wp.editor.PluginPrePublishPanel ) || wp.editPost.PluginPrePublishPanel;
	var NOTICE_ID = 'bq-featured-alt';

	function useArticle() {
		var data = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var mediaId = editor.getEditedPostAttribute( 'featured_media' );
			var media = mediaId ? select( 'core' ).getMedia( mediaId ) : null;
			return {
				meta: editor.getEditedPostAttribute( 'meta' ) || {},
				mediaId: mediaId,
				media: media,
				content: editor.getEditedPostAttribute( 'content' ) || '',
				postType: editor.getCurrentPostType()
			};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		return {
			data: data,
			setMeta: function ( key, value ) {
				var patch = {};
				patch[ key ] = value;
				editPost( { meta: patch } );
			}
		};
	}

	function readingTime( html ) {
		var text = html.replace( /<[^>]+>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
		var words = text ? text.split( ' ' ).length : 0;
		return Math.max( 1, Math.ceil( words / 200 ) );
	}

	function missingAlt( d ) {
		return d.mediaId && d.media && ! ( d.media.alt_text || '' ).trim();
	}

	function References( props ) {
		var refs = Array.isArray( props.value ) ? props.value : [];
		function update( i, key, val ) {
			var next = refs.map( function ( r ) { return { title: r.title || '', url: r.url || '' }; } );
			next[ i ][ key ] = val;
			props.onChange( next );
		}
		return el( 'div', { className: 'bq-refs' },
			el( 'p', { style: { fontWeight: 600, margin: '12px 0 6px' } }, __( 'References', 'benoquik-core' ) ),
			refs.map( function ( r, i ) {
				return el( 'div', { key: i, style: { borderLeft: '3px solid #D6E2F0', paddingLeft: 8, marginBottom: 10 } },
					el( C.TextControl, { label: ( i + 1 ) + '. ' + __( 'Title', 'benoquik-core' ), value: r.title || '', onChange: function ( v ) { update( i, 'title', v ); }, __nextHasNoMarginBottom: true } ),
					el( C.TextControl, { label: __( 'URL', 'benoquik-core' ), type: 'url', value: r.url || '', onChange: function ( v ) { update( i, 'url', v ); }, __nextHasNoMarginBottom: true } ),
					el( C.Button, { variant: 'link', isDestructive: true, onClick: function () { props.onChange( refs.filter( function ( x, j ) { return j !== i; } ) ); } }, __( 'Remove', 'benoquik-core' ) )
				);
			} ),
			el( C.Button, { variant: 'secondary', onClick: function () { props.onChange( refs.concat( [ { title: '', url: '' } ] ) ); } }, __( '+ Add reference', 'benoquik-core' ) )
		);
	}

	function Panel() {
		var a = useArticle();
		var d = a.data;
		var m = d.meta;
		var notices = useDispatch( 'core/notices' );
		var noAlt = missingAlt( d );

		useEffect( function () {
			if ( noAlt ) {
				notices.createWarningNotice( __( 'The featured image has no alt text. Add a short description in Featured image → Edit (Alternative text) — it is required for accessibility and SEO.', 'benoquik-core' ), { id: NOTICE_ID, isDismissible: false } );
			} else {
				notices.removeNotice( NOTICE_ID );
			}
		}, [ noAlt ] );

		if ( d.postType !== 'post' ) {
			return null;
		}
		return el( PluginDocumentSettingPanel, { name: 'benoquik-article', title: __( 'Benoquik article details', 'benoquik-core' ), className: 'bq-article-panel' },
			noAlt ? el( C.Notice, { status: 'warning', isDismissible: false }, __( 'Featured image is missing alt text.', 'benoquik-core' ) ) : null,
			el( 'p', { style: { margin: '0 0 12px', color: '#5B6B82' } }, __( 'Reading time (automatic):', 'benoquik-core' ) + ' ' + readingTime( d.content ) + ' ' + __( 'min', 'benoquik-core' ) ),
			el( 'p', { style: { fontWeight: 600, margin: '0 0 6px' } }, __( 'Medical reviewer', 'benoquik-core' ) ),
			el( C.TextControl, { label: __( 'Name', 'benoquik-core' ), value: m.bq_reviewer_name || '', onChange: function ( v ) { a.setMeta( 'bq_reviewer_name', v ); }, help: __( 'Leave empty if not yet reviewed — nothing is shown.', 'benoquik-core' ), __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Credentials', 'benoquik-core' ), placeholder: 'MD, Dermatology', value: m.bq_reviewer_credentials || '', onChange: function ( v ) { a.setMeta( 'bq_reviewer_credentials', v ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Bio page link (optional)', 'benoquik-core' ), type: 'url', value: m.bq_reviewer_url || '', onChange: function ( v ) { a.setMeta( 'bq_reviewer_url', v ); }, __nextHasNoMarginBottom: true } ),
			el( C.TextControl, { label: __( 'Last reviewed', 'benoquik-core' ), type: 'date', value: m.bq_last_reviewed || '', onChange: function ( v ) { a.setMeta( 'bq_last_reviewed', v ); }, __nextHasNoMarginBottom: true } ),
			el( References, { value: m.bq_references, onChange: function ( v ) { a.setMeta( 'bq_references', v ); } } ),
			el( 'div', { style: { marginTop: 14 } },
				el( C.CheckboxControl, { label: __( 'Hide the product card at the end of this article', 'benoquik-core' ), checked: !! m.bq_hide_cta, onChange: function ( v ) { a.setMeta( 'bq_hide_cta', v ); }, __nextHasNoMarginBottom: true } )
			)
		);
	}

	function PrePublish() {
		var a = useArticle();
		var d = a.data;
		if ( d.postType !== 'post' ) {
			return null;
		}
		var items = [];
		if ( ! d.mediaId ) {
			items.push( __( 'No featured image set.', 'benoquik-core' ) );
		} else if ( missingAlt( d ) ) {
			items.push( __( 'Featured image is missing alt text.', 'benoquik-core' ) );
		}
		if ( ! d.meta.bq_reviewer_name ) {
			items.push( __( 'No medical reviewer set (optional, but recommended for medical articles).', 'benoquik-core' ) );
		}
		if ( /DRAFT — to be written/.test( d.content ) ) {
			items.push( __( 'The "DRAFT — to be written and medically reviewed" note is still in the article.', 'benoquik-core' ) );
		}
		if ( ! items.length ) {
			return null;
		}
		return el( PluginPrePublishPanel, { title: __( 'Benoquik checks', 'benoquik-core' ), initialOpen: true },
			el( 'ul', { style: { margin: 0, paddingLeft: 18, listStyle: 'disc' } }, items.map( function ( t, i ) { return el( 'li', { key: i }, t ); } ) )
		);
	}

	wp.plugins.registerPlugin( 'benoquik-article', {
		render: function () {
			return el( Fragment, null, el( Panel ), el( PrePublish ) );
		}
	} );
}( window.wp ) );

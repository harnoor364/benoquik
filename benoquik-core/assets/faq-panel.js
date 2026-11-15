/**
 * Page/post editor sidebar: "FAQ schema" — checkbox "Output FAQ schema from accordions".
 * Default (no choice saved): on for the FAQ page and homepage, off elsewhere.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var __ = wp.i18n.__;
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

	function FaqPanel() {
		var meta = useSelect( function ( s ) { return s( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}; }, [] );
		var count = useSelect( function ( s ) {
			var n = 0;
			var walk = function ( blocks ) {
				blocks.forEach( function ( b ) {
					var hidden = b.attributes && b.attributes.metadata && b.attributes.metadata.blockVisibility === false;
					if ( hidden ) { return; }
					if ( b.name === 'core/details' ) { n++; }
					walk( b.innerBlocks || [] );
				} );
			};
			walk( s( 'core/block-editor' ).getBlocks() );
			return n;
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		var value = meta.bq_faq_schema;
		var checked = value === 'on' ? true : value === 'off' ? false : !! window.bqFaqDefault;
		return el( Panel, { name: 'benoquik-faq-schema', title: __( 'FAQ schema', 'benoquik-core' ) },
			el( wp.components.CheckboxControl, {
				label: __( 'Output FAQ schema from accordions', 'benoquik-core' ),
				help: __( 'Adds the visible Details (accordion) blocks on this page to Rank Math’s schema as FAQPage — summary = question, content = answer. Visible accordions found: ', 'benoquik-core' ) + count,
				checked: checked,
				onChange: function ( v ) { editPost( { meta: { bq_faq_schema: v ? 'on' : 'off' } } ); },
				__nextHasNoMarginBottom: true
			} )
		);
	}
	wp.plugins.registerPlugin( 'benoquik-faq-schema', { render: FaqPanel } );
}( window.wp ) );

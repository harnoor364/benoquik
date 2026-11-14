/**
 * Editor registration for Benoquik's dynamic blocks (no build step needed).
 * Each block is rendered by PHP; text is edited in Benoquik -> Settings.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var __ = wp.i18n.__;

	var settingsLink = el( 'p', {}, el( 'a', { href: 'admin.php?page=benoquik', target: '_blank' }, __( 'Edit text in Benoquik → Settings', 'benoquik-core' ) ) );

	var blocks = {
		'benoquik/marquee': { title: 'Benoquik: Marquee strip', icon: 'controls-forward', supports: { align: [ 'full', 'wide' ] }, attributes: { align: { type: 'string', default: 'full' } } },
		'benoquik/announcement': { title: 'Benoquik: Announcement bar', icon: 'megaphone' },
		'benoquik/compliance': { title: 'Benoquik: Licence & compliance row', icon: 'shield' },
		'benoquik/subscribe': { title: 'Benoquik: Email subscribe form', icon: 'email' }
	};

	Object.keys( blocks ).forEach( function ( name ) {
		var cfg = blocks[ name ];
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: cfg.title,
			icon: cfg.icon,
			category: 'benoquik',
			supports: Object.assign( { html: false }, cfg.supports || {} ),
			attributes: cfg.attributes || {},
			edit: function ( props ) {
				return el( 'div', useBlockProps(),
					el( InspectorControls, {}, el( PanelBody, { title: __( 'Content', 'benoquik-core' ) }, settingsLink ) ),
					el( SSR, { block: name, attributes: props.attributes } )
				);
			},
			save: function () { return null; }
		} );
	} );
}( window.wp ) );

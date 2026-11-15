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
		'benoquik/compliance': { title: 'Benoquik: License & compliance row', icon: 'shield' },
		'benoquik/subscribe': { title: 'Benoquik: Email subscribe form', icon: 'email' },
		'benoquik/product-subtitle': { title: 'Benoquik: Product subtitle (pack size)', icon: 'editor-textcolor', usesContext: [ 'postId', 'postType' ] },
		'benoquik/rx-notice': { title: 'Benoquik: Prescription notice', icon: 'shield-alt', usesContext: [ 'postId', 'postType' ] },
		'benoquik/delivery-estimate': { title: 'Benoquik: Delivery estimate', icon: 'car', usesContext: [ 'postId', 'postType' ] },
		'benoquik/product-info': { title: 'Benoquik: Medicine information accordions', icon: 'list-view', usesContext: [ 'postId', 'postType' ] },
		'benoquik/price-note': { title: 'Benoquik: Price note (currency, duties)', icon: 'money-alt' },
		'benoquik/shipping-note': { title: 'Benoquik: Shipping line', icon: 'airplane' },
		'benoquik/marketed-by': { title: 'Benoquik: Marketed by', icon: 'building' },
		'benoquik/sticky-cart': { title: 'Benoquik: Sticky add-to-cart (mobile)', icon: 'cart', usesContext: [ 'postId', 'postType' ] }
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
			usesContext: cfg.usesContext || [],
			edit: function ( props ) {
				return el( 'div', useBlockProps(),
					el( InspectorControls, {}, el( PanelBody, { title: __( 'Content', 'benoquik-core' ) }, settingsLink ) ),
					el( SSR, { block: name, attributes: props.attributes, urlQueryArgs: props.context && props.context.postId ? { post_id: props.context.postId } : {} } )
				);
			},
			save: function () { return null; }
		} );
	} );
}( window.wp ) );

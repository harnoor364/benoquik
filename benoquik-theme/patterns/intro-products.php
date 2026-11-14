<?php
/**
 * Title: Intro + product cards (from WooCommerce)
 * Slug: benoquik/intro-products
 * Categories: benoquik-home
 * Keywords: products, shop, intro
 * Description: Serif heading, short paragraph and 3 product cards pulled live from WooCommerce. Change which products show in the block settings (Product Collection).
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"align":"full","className":"bq-section bq-intro","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group alignfull bq-section bq-intro" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"bottom","className":"bq-reveal"} -->
<div class="wp-block-columns are-vertically-aligned-bottom bq-reveal"><!-- wp:column {"verticalAlignment":"bottom","width":"60%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:60%"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Treatment is a journey, not a quick fix</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"40%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:40%"><!-- wp:paragraph {"align":"right","className":"bq-intro__note","textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-right bq-intro__note has-muted-color has-text-color has-small-font-size">Benoquik is dispensed only against a valid prescription. Choose the pack your dermatologist recommends — our pharmacist checks every prescription before dispatch.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:woocommerce/product-collection {"queryId":11,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"className":"bq-products bq-reveal"} -->
<div class="wp-block-woocommerce-product-collection bq-products bq-reveal"><!-- wp:woocommerce/product-template -->
<!-- wp:group {"className":"bq-product-card__media","layout":{"type":"default"}} -->
<div class="wp-block-group bq-product-card__media"><!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"1"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"className":"bq-product-card__add","fontSize":"x-small"} /--></div>
<!-- /wp:group -->

<!-- wp:post-terms {"term":"product_cat","className":"bq-product-card__cat","fontSize":"x-small"} /-->

<!-- wp:group {"className":"bq-product-card__meta","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
<div class="wp-block-group bq-product-card__meta"><!-- wp:post-title {"level":3,"isLink":true,"fontSize":"small","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"fontSize":"small"} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->

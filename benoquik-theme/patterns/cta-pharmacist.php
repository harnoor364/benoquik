<?php
/**
 * Title: Image CTA — Talk to a pharmacist (WhatsApp)
 * Slug: benoquik/cta-pharmacist
 * Categories: benoquik-home, benoquik-pages
 * Keywords: cta, whatsapp, pharmacist, contact
 * Description: Full-width rounded image with heading and a WhatsApp button. The button's number comes from Benoquik -> Settings (any button with the CSS class "bq-whatsapp"). Placeholder image.
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"align":"full","className":"bq-section bq-cta-wrap","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group alignfull bq-section bq-cta-wrap" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:cover {"url":"<?php echo benoquik_img( 'cta.webp' ); ?>","alt":"","dimRatio":50,"overlayColor":"olive","minHeight":460,"className":"bq-cta bq-reveal","layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-cover bq-cta bq-reveal" style="min-height:460px"><span aria-hidden="true" class="wp-block-cover__background has-olive-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo benoquik_img( 'cta.webp' ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","textColor":"white","fontSize":"huge"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-huge-font-size">Start your treatment with confidence</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Questions about your prescription, how to apply Benoquik or delivery? Speak to our registered pharmacist on WhatsApp.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"bq-whatsapp"} -->
<div class="wp-block-button bq-whatsapp"><a class="wp-block-button__link wp-element-button" href="https://wa.me/919999999999">Talk to a pharmacist</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->

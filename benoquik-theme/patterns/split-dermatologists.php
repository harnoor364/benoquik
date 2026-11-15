<?php
/**
 * Title: Split — image + "Prescribed and supervised by dermatologists"
 * Slug: benoquik/split-dermatologists
 * Categories: benoquik-home
 * Keywords: split, image, text, doctor
 * Description: Image on the left; heading, paragraph, important-safety note and button on the right. Placeholder image.
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"align":"full","className":"bq-section bq-split","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group alignfull bq-section bq-split" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"46%","className":"bq-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center bq-reveal" style="flex-basis:46%"><!-- wp:image {"aspectRatio":"4/5","scale":"cover","sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo benoquik_img( 'split.webp' ); ?>" alt="Placeholder image — dermatologist consultation" style="aspect-ratio:4/5;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"54%","className":"bq-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center bq-reveal" style="flex-basis:54%"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Prescribed and supervised by dermatologists</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Monobenzone 20% is a strong, permanent treatment. Your dermatologist decides whether it is right for you, which areas to treat and how often to review your progress. We dispense only against a valid prescription.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"bq-note","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"1.25rem","right":"1.25rem"}}},"backgroundColor":"sky","layout":{"type":"default"}} -->
<div class="wp-block-group bq-note has-sky-background-color has-background" style="padding-top:1rem;padding-right:1.25rem;padding-bottom:1rem;padding-left:1.25rem"><!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><strong>Important:</strong> Benoquik permanently removes pigment from normal skin. It is <strong>not a cure</strong> for vitiligo, does <strong>not restore color</strong>, and is only for people with extensive vitiligo under a doctor’s supervision.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/learn/how-depigmentation-therapy-works/">How depigmentation therapy works</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

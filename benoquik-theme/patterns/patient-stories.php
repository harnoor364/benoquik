<?php
/**
 * Title: Patient stories
 * Slug: benoquik/patient-stories
 * Categories: benoquik-home
 * Keywords: testimonials, stories, reviews, video
 * Description: Three cards plus one wide video card with a Play pill. ALL STORIES, NAMES AND IMAGES ARE PLACEHOLDERS. To hide the whole section until you have real, consented testimonials: open List View, select "Patient stories (placeholder)" -> Options (⋮) -> Hide. Never add before/after photos or result promises.
 * Viewport Width: 1400
 */

$benoquik_story = static function ( $img, $name, $text ) {
	ob_start();
	?>
<!-- wp:group {"className":"bq-story bq-reveal","style":{"spacing":{"blockGap":"0.6rem"}},"layout":{"type":"default"}} -->
<div class="wp-block-group bq-story bq-reveal"><!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"medium","linkDestination":"none"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo benoquik_img( $img ); ?>" alt="Abstract placeholder image" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><strong><?php echo esc_html( $name ); ?></strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php echo esc_html( $text ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
	<?php
	return ob_get_clean();
};
?>
<!-- wp:group {"metadata":{"name":"Patient stories (placeholder — hide until real, consented testimonials)"},"align":"full","className":"bq-section bq-stories-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group alignfull bq-section bq-stories-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"bq-reveal","style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group bq-reveal"><!-- wp:heading {"textAlign":"center","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size">Patient stories</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size">People living with vitiligo, in their own words. Every experience is different. (Placeholder stories — replace with real, consented testimonials.)</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"bq-stories","layout":{"type":"default"}} -->
<div class="wp-block-group bq-stories"><?php echo $benoquik_story( 'story-1.webp', 'Priya, 34 · Pune (placeholder)', '“My dermatologist walked me through the patch test and sun care before I began. I felt informed, not rushed.”' ); // phpcs:ignore ?>

<!-- wp:cover {"url":"<?php echo benoquik_img( 'story-2.webp' ); ?>","alt":"","dimRatio":100,"gradient":"card-scrim","minHeight":360,"contentPosition":"bottom left","className":"bq-story bq-story--video bq-reveal"} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left bq-story bq-story--video bq-reveal" style="min-height:360px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient has-card-scrim-gradient-background"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo benoquik_img( 'story-2.webp' ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:buttons {"className":"bq-play"} -->
<div class="wp-block-buttons bq-play"><!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><a class="wp-block-button__link wp-element-button" href="#">▶ Play</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"textColor":"white","fontSize":"small"} -->
<p class="has-white-color has-text-color has-small-font-size"><strong>Rahul &amp; his mother (placeholder)</strong><br>A caregiver’s view of starting treatment — video coming soon.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<?php echo $benoquik_story( 'story-3.webp', 'Farah, 41 · Hyderabad (placeholder)', '“Choosing depigmentation was a big decision. Talking it through with my doctor and family helped.”' ); // phpcs:ignore ?>

<?php echo $benoquik_story( 'story-4.webp', 'Anil, 52 · Jaipur (placeholder)', '“Sunscreen every morning is simply part of my routine now.”' ); // phpcs:ignore ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

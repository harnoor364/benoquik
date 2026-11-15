<?php
/**
 * Title: Hero — full-bleed image with product card
 * Slug: benoquik/hero
 * Categories: benoquik-home
 * Keywords: hero, banner, header, intro
 * Description: Full-width hero with H1, prescription-accurate subtext, two buttons, trust row and a floating product card. Placeholder image — replace with licensed photography.
 * Viewport Width: 1400
 */
?>
<!-- wp:cover {"url":"<?php echo benoquik_img( 'hero.webp' ); ?>","alt":"","dimRatio":100,"gradient":"hero-scrim","minHeight":100,"minHeightUnit":"vh","contentPosition":"bottom left","align":"full","className":"bq-hero","metadata":{"name":"Hero"},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-bottom-left bq-hero" style="min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient has-hero-scrim-gradient-background"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo benoquik_img( 'hero.webp' ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:columns {"verticalAlignment":"bottom","className":"bq-hero__grid"} -->
<div class="wp-block-columns are-vertically-aligned-bottom bq-hero__grid"><!-- wp:column {"verticalAlignment":"bottom","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:58%"><!-- wp:group {"className":"bq-hero__content","layout":{"type":"constrained","contentSize":"760px","justifyContent":"left"}} -->
<div class="wp-block-group bq-hero__content"><!-- wp:paragraph {"className":"is-style-pill"} -->
<p class="is-style-pill">℞ Prescription medicine · Monobenzone 20% w/w</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"textColor":"white","fontSize":"colossal"} -->
<h1 class="wp-block-heading has-white-color has-text-color has-colossal-font-size">Even, confident skin — on your terms</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"white","fontSize":"large"} -->
<p class="has-white-color has-text-color has-large-font-size">Benoquik is a prescription depigmentation cream for people with extensive vitiligo. Used under a dermatologist’s supervision, it permanently removes the remaining pigment so your skin tone becomes even. It is not a cure for vitiligo and does not restore colour.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Shop Benoquik</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><a class="wp-block-button__link wp-element-button" href="#how-it-works">How it works</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"bq-trust","textColor":"white","fontSize":"x-small"} -->
<p class="bq-trust has-white-color has-text-color has-x-small-font-size">Dermatologist-prescribed · Genuine product · Licensed pharmacy</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"42%","className":"bq-hero__visual"} -->
<div class="wp-block-column is-vertically-aligned-bottom bq-hero__visual" style="flex-basis:42%"><!-- wp:image {"aspectRatio":"4/5","scale":"cover","sizeSlug":"large","linkDestination":"none","className":"bq-hero__image"} -->
<figure class="wp-block-image size-large bq-hero__image"><img src="<?php echo benoquik_img( 'hero-visual.webp' ); ?>" alt="Benoquik Monobenzone Cream USP 20% — 20 g tube and box" style="aspect-ratio:4/5;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"bq-hero-card is-style-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group bq-hero-card is-style-card"><!-- wp:image {"width":"76px","height":"76px","scale":"cover","sizeSlug":"thumbnail","linkDestination":"none"} -->
<figure class="wp-block-image size-thumbnail is-resized"><img src="<?php echo benoquik_img( 'benoquik-20g.webp' ); ?>" alt="Benoquik Monobenzone Cream USP 20% — 20 g tube and box" style="object-fit:cover;width:76px;height:76px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.2rem"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><strong>Benoquik Cream 20 g</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"x-small"} -->
<p class="has-muted-color has-text-color has-x-small-font-size">Monobenzone Cream USP 20% · ℞ only</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"bq-link-arrow","fontSize":"x-small"} -->
<p class="bq-link-arrow has-x-small-font-size"><a href="/shop/benoquik-cream-20g/">View product</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div></div>
<!-- /wp:cover -->

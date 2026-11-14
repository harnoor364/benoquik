<?php
/**
 * Title: How Benoquik works — bento grid
 * Slug: benoquik/bento-how-it-works
 * Categories: benoquik-home
 * Keywords: bento, grid, how it works, steps, safety
 * Description: Five rounded image tiles of varied sizes with overlay captions. Placeholder images.
 * Viewport Width: 1400
 */

$benoquik_tiles = array(
	array( 'bento-patch.webp', '01', 'Patch test first (7 days)', 'Apply a small amount to a test area as your doctor directs and watch for redness, itching or swelling before wider use.' ),
	array( 'bento-layer.webp', '02', 'Thin layer, as prescribed', 'Only on the areas and as often as your doctor says. Avoid the eyes and wash your hands after applying.' ),
	array( 'bento-time.webp', '03', 'Change builds gradually, over months', 'Depigmentation is slow and differs from person to person. Keep your follow-up appointments.' ),
	array( 'bento-sun.webp', '04', 'Daily sunscreen is essential', 'Depigmented skin burns easily. Use broad-spectrum sunscreen every day and cover up outdoors.' ),
	array( 'bento-doctor.webp', '05', 'Talk to your doctor', 'Stop and see your doctor if irritation or swelling occurs, or if you are pregnant or breastfeeding.' ),
);
?>
<!-- wp:group {"align":"full","anchor":"how-it-works","className":"bq-section bq-how","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div id="how-it-works" class="wp-block-group alignfull bq-section bq-how" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"bq-reveal","style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group bq-reveal"><!-- wp:heading {"textAlign":"center","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size">How Benoquik works</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size">Five things to know before you start. Always follow your own doctor’s instructions.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"bq-bento","layout":{"type":"default"}} -->
<div class="wp-block-group bq-bento">
<?php foreach ( $benoquik_tiles as $benoquik_i => $benoquik_tile ) : ?>
<!-- wp:cover {"url":"<?php echo benoquik_img( $benoquik_tile[0] ); ?>","alt":"","dimRatio":100,"gradient":"card-scrim","minHeight":280,"contentPosition":"bottom left","className":"bq-tile bq-tile--<?php echo (int) $benoquik_i + 1; ?> bq-reveal"} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left bq-tile bq-tile--<?php echo (int) $benoquik_i + 1; ?> bq-reveal" style="min-height:280px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient has-card-scrim-gradient-background"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo benoquik_img( $benoquik_tile[0] ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"className":"bq-tile__num","fontSize":"x-small"} -->
<p class="bq-tile__num has-x-small-font-size"><?php echo esc_html( $benoquik_tile[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"textColor":"white","fontSize":"x-large"} -->
<h3 class="wp-block-heading has-white-color has-text-color has-x-large-font-size"><?php echo esc_html( $benoquik_tile[2] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"white","fontSize":"small"} -->
<p class="has-white-color has-text-color has-small-font-size"><?php echo esc_html( $benoquik_tile[3] ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

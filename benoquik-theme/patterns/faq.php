<?php
/**
 * Title: FAQ — heading left, accordion right
 * Slug: benoquik/faq
 * Categories: benoquik-home, benoquik-pages
 * Keywords: faq, questions, accordion, details
 * Description: Uses native Details blocks (first one open). Add a question: select an item -> Duplicate. For FAQ rich results, add FAQ schema in Rank Math (Edit page -> Rank Math -> Schema).
 * Viewport Width: 1400
 */

$benoquik_faqs = array(
	array( 'Does Benoquik cure vitiligo?', 'No. Benoquik does not cure vitiligo and does not restore skin color. It permanently removes the remaining pigment from normal skin so that the overall skin tone becomes even. It is meant only for people with extensive vitiligo, on a dermatologist’s advice.' ),
	array( 'Do you ship to my country?', 'We ship from India to most countries worldwide, with tracking and discreet packaging. Only countries we deliver to can be chosen at checkout — see our Shipping & Availability page.' ),
	array( 'Will I pay customs duties?', 'Possibly. Prices are in US dollars and exclude any import duties, VAT or customs fees charged by your country. These are paid by the recipient on delivery.' ),
	array( 'Is a prescription from my own doctor accepted?', 'Yes. Upload a valid prescription in English from a doctor licensed in your country (PDF, JPG or PNG) at checkout. Our pharmacist verifies every order before dispatch.' ),
	array( 'How should I apply it?', 'Do a patch test first, as your doctor advises. Apply a thin layer only to the areas and as often as prescribed. Avoid the eyes and mouth, and wash your hands after applying. Benoquik can also lighten skin you did not treat.' ),
	array( 'How do I protect my skin from the sun?', 'Depigmented skin has little natural protection and burns easily. Use a broad-spectrum sunscreen every day, wear protective clothing and avoid strong midday sun.' ),
);
?>
<!-- wp:group {"align":"full","className":"bq-section bq-faq","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group alignfull bq-section bq-faq" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"38%","className":"bq-reveal"} -->
<div class="wp-block-column bq-reveal" style="flex-basis:38%"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Frequently asked questions</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size">Can’t find your answer? Our registered pharmacist is a message away.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline-dark"} -->
<div class="wp-block-button is-style-outline-dark"><a class="wp-block-button__link wp-element-button" href="/faq/">All FAQs</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"62%","className":"bq-accordion bq-reveal"} -->
<div class="wp-block-column bq-accordion bq-reveal" style="flex-basis:62%">
<?php foreach ( $benoquik_faqs as $benoquik_i => $benoquik_faq ) : ?>
<?php if ( 0 === $benoquik_i ) : ?>
<!-- wp:details {"showContent":true} -->
<details class="wp-block-details" open><summary><?php echo esc_html( $benoquik_faq[0] ); ?></summary><!-- wp:paragraph -->
<?php else : ?>
<!-- wp:details -->
<details class="wp-block-details"><summary><?php echo esc_html( $benoquik_faq[0] ); ?></summary><!-- wp:paragraph -->
<?php endif; ?>
<p><?php echo esc_html( $benoquik_faq[1] ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
<?php endforeach; ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

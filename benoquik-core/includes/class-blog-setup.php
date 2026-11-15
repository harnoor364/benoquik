<?php
/**
 * One-time blog setup: categories + 6 starter DRAFT articles.
 *
 *   wp benoquik setup-blog
 *   or WordPress admin → Benoquik → Set up Learn (blog)
 *
 * Safe to run again: existing categories and posts (matched by slug) are left
 * untouched. It never changes pages, products, prices or the Home page.
 *
 * Starter articles are DRAFTS with an outline and placeholder text, to be
 * written and medically reviewed before publishing.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Blog_Setup {

	public static function categories() {
		return array(
			'understanding-vitiligo' => array( 'Understanding Vitiligo', 'What vitiligo is, its causes, types and how it is diagnosed.' ),
			'depigmentation-therapy' => array( 'Depigmentation Therapy', 'How depigmentation therapy works and who it is for.' ),
			'using-benoquik-safely'  => array( 'Using Benoquik Safely', 'Applying monobenzone cream correctly, side effects and safety.' ),
			'sun-care-skin-care'     => array( 'Sun Care & Skin Care', 'Protecting depigmented skin from the sun, every day.' ),
			'living-with-vitiligo'   => array( 'Living with Vitiligo', 'Everyday life, wellbeing and support.' ),
			'ordering-shipping'      => array( 'Ordering & Shipping', 'Prescriptions, international delivery, customs and safe online ordering.' ),
		);
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 45 );
		add_action( 'admin_post_benoquik_setup_blog', array( __CLASS__, 'handle' ) );
	}

	public static function menu() {
		add_submenu_page( 'benoquik', __( 'Set up Learn (blog)', 'benoquik-core' ), __( 'Set up Learn (blog)', 'benoquik-core' ), 'manage_options', 'benoquik-blog', array( __CLASS__, 'page' ) );
	}

	public static function page() {
		$log = get_transient( 'benoquik_blog_log' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Set up Learn (blog)', 'benoquik-core' ) . '</h1><p>' . esc_html__( 'Creates the article categories and 6 starter articles as DRAFTS. Nothing is published, and no pages, products or the homepage are changed. Existing categories/articles are kept.', 'benoquik-core' ) . '</p>';
		if ( $log ) {
			echo '<div class="notice notice-success"><pre style="white-space:pre-wrap">' . esc_html( implode( "\n", (array) $log ) ) . '</pre></div>';
			delete_transient( 'benoquik_blog_log' );
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bq_setup_blog' );
		echo '<input type="hidden" name="action" value="benoquik_setup_blog">';
		submit_button( __( 'Create categories and draft articles', 'benoquik-core' ) );
		echo '</form></div>';
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bq_setup_blog' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'benoquik-core' ) );
		}
		set_transient( 'benoquik_blog_log', self::run(), 300 );
		wp_safe_redirect( admin_url( 'admin.php?page=benoquik-blog' ) );
		exit;
	}

	/* ----------------------------------------------------------- block helpers */

	private static function h2( $t ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$t}</h2>\n<!-- /wp:heading -->\n\n";
	}
	private static function h3( $t ) {
		return "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$t}</h3>\n<!-- /wp:heading -->\n\n";
	}
	private static function p( $t ) {
		return "<!-- wp:paragraph -->\n<p>{$t}</p>\n<!-- /wp:paragraph -->\n\n";
	}
	private static function todo( $t ) {
		return "<!-- wp:paragraph {\"className\":\"bq-todo\"} -->\n<p class=\"bq-todo\">[To be written: {$t}]</p>\n<!-- /wp:paragraph -->\n\n";
	}
	private static function ul( array $items, $ordered = false ) {
		$li = '';
		foreach ( $items as $i ) {
			$li .= "<!-- wp:list-item -->\n<li>{$i}</li>\n<!-- /wp:list-item -->\n";
		}
		$tag = $ordered ? 'ol' : 'ul';
		$att = $ordered ? '{"ordered":true}' : '';
		return '<!-- wp:list ' . ( $att ? $att . ' ' : '' ) . "-->\n<{$tag} class=\"wp-block-list\">{$li}</{$tag}>\n<!-- /wp:list -->\n\n";
	}
	private static function box( $style, $inner ) {
		return "<!-- wp:group {\"className\":\"is-style-{$style}\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group is-style-{$style}\">{$inner}</div>\n<!-- /wp:group -->\n\n";
	}
	private static function draft_note() {
		return self::box( 'callout-important', self::p( '<strong>DRAFT — to be written and medically reviewed.</strong> This outline and placeholder text must be completed and checked by a qualified medical reviewer before publishing. Remove this box when done.' ) );
	}
	private static function takeaways( array $points ) {
		return self::box( 'key-takeaways', "<!-- wp:heading {\"level\":2,\"className\":\"bq-takeaways-title\"} -->\n<h2 class=\"wp-block-heading bq-takeaways-title\">Key takeaways</h2>\n<!-- /wp:heading -->\n\n" . self::ul( $points ) );
	}
	private static function safety( $t ) {
		return self::box( 'callout-warning', self::p( '<strong>Safety:</strong> ' . $t ) );
	}

	/* ------------------------------------------------------------- articles */

	public static function articles() {
		$nhs     = array( 'title' => 'NHS — Vitiligo (to verify)', 'url' => 'https://www.nhs.uk/conditions/vitiligo/' );
		$dermnet = array( 'title' => 'DermNet — Vitiligo (to verify)', 'url' => 'https://dermnetnz.org/topics/vitiligo' );
		$add     = array( 'title' => 'Add peer-reviewed source(s) here', 'url' => '' );

		return array(
			'what-is-vitiligo'                 => array(
				'title'   => 'What Is Vitiligo? Causes, Types and Treatment Options',
				'cat'     => 'understanding-vitiligo',
				'tags'    => array( 'vitiligo', 'diagnosis' ),
				'image'   => array( 'split.webp', 'Placeholder image — soft light-blue abstract texture' ),
				'sticky'  => true,
				'excerpt' => 'Vitiligo is a long-term condition that causes patches of skin to lose their color. Learn about its causes, the main types, and the treatment options your dermatologist may discuss.',
				'refs'    => array( $nhs, $dermnet, $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'Vitiligo causes patches of skin to lose pigment because pigment cells (melanocytes) stop working.', 'It is not contagious.', 'Treatment options include repigmentation treatments and, for extensive vitiligo, depigmentation therapy — chosen with a dermatologist.' ) )
					. self::h2( 'What is vitiligo?' )
					. self::p( 'Vitiligo is a long-term skin condition in which patches of skin lose their natural color because the cells that make pigment (melanocytes) stop working or are destroyed. It can affect any part of the body and people of any skin type.' )
					. self::todo( 'how common it is, how it usually starts and how it can change over time' )
					. self::h2( 'What causes vitiligo?' )
					. self::todo( 'autoimmune factors, genetics, triggers; what is and is not known' )
					. self::h2( 'Types of vitiligo' )
					. self::h3( 'Non-segmental vitiligo' )
					. self::todo( 'the most common type; patches often appear on both sides of the body' )
					. self::h3( 'Segmental vitiligo' )
					. self::todo( 'usually affects one area or one side of the body' )
					. self::h2( 'How is vitiligo diagnosed?' )
					. self::todo( 'examination, Wood’s lamp, when tests are needed' )
					. self::h2( 'Treatment options' )
					. self::h3( 'Treatments that aim to restore color' )
					. self::todo( 'topical treatments, phototherapy and newer options — as available in different countries' )
					. self::h3( 'Depigmentation for extensive vitiligo' )
					. self::p( 'When vitiligo already affects most of the skin, some people choose, with their dermatologist, to remove the remaining pigment so that their skin tone becomes even. This is permanent and is not a cure for vitiligo.' )
					. self::h3( 'Camouflage and skin care' )
					. self::todo( 'cosmetic camouflage, sun protection' )
					. self::h2( 'When to see a dermatologist' )
					. self::todo( 'new or spreading patches, emotional impact, choosing a treatment' ),
			),
			'depigmentation-therapy-how-it-works' => array(
				'title'   => 'Depigmentation Therapy for Vitiligo: How It Works',
				'cat'     => 'depigmentation-therapy',
				'tags'    => array( 'depigmentation', 'monobenzone' ),
				'image'   => array( 'bento-patch.webp', 'Placeholder image — pale blue abstract skin-texture background' ),
				'excerpt' => 'Depigmentation therapy can even out skin tone for people with extensive vitiligo by permanently removing remaining pigment. Here is how it works, who it is for and what to consider.',
				'refs'    => array( $dermnet, $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'Depigmentation therapy is for people with extensive vitiligo, under a dermatologist’s supervision.', 'It permanently removes the remaining pigment — it does not cure vitiligo or restore color.', 'Change is gradual and varies from person to person.' ) )
					. self::h2( 'What is depigmentation therapy?' )
					. self::p( 'Depigmentation therapy removes the pigment from the remaining normally colored skin, so that it matches the areas already affected by vitiligo. The most widely used medicine for this is monobenzone cream.' )
					. self::h2( 'Who is it for?' )
					. self::todo( 'extensive vitiligo affecting most of the skin; why it is not suitable for limited vitiligo or cosmetic lightening' )
					. self::h2( 'How monobenzone works' )
					. self::todo( 'effect on melanocytes; why the result is permanent' )
					. self::h2( 'What to expect during treatment' )
					. self::p( 'Depigmentation develops slowly and differs between people. Your dermatologist will explain how your progress will be checked. No one can promise how long it will take.' )
					. self::todo( 'follow-up appointments, patch test, practical routine' )
					. self::h2( 'Risks and things to consider' )
					. self::ul( array( 'The result is permanent and cannot be reliably reversed.', 'Untreated skin — including skin away from where the cream is applied — can also lose pigment.', 'Depigmented skin burns easily and needs lifelong sun protection.', '[To be written: emotional and practical considerations]' ) )
					. self::h2( 'Making the decision with your dermatologist' )
					. self::todo( 'questions to ask, support, second opinions' ),
			),
			'monobenzone-cream-uses-side-effects-safety' => array(
				'title'   => 'Monobenzone Cream: Uses, Side Effects and Safety',
				'cat'     => 'using-benoquik-safely',
				'tags'    => array( 'monobenzone', 'safety', 'side effects' ),
				'image'   => array( 'bento-layer.webp', 'Placeholder image — soft blue fabric texture' ),
				'excerpt' => 'Monobenzone 20% cream is a prescription medicine for extensive vitiligo. Learn what it is used for, possible side effects, and important safety advice.',
				'refs'    => array( $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'Monobenzone is a prescription-only medicine for extensive vitiligo.', 'It is not for cosmetic skin lightening, freckles or melasma.', 'Stop and see your doctor if you notice irritation, swelling or blistering.' ) )
					. self::h2( 'What is monobenzone?' )
					. self::todo( 'what monobenzone is; Benoquik® is Monobenzone Cream USP 20% w/w' )
					. self::h2( 'What it is used for — and what it is not for' )
					. self::p( 'Monobenzone is used to permanently remove remaining pigment in people with extensive vitiligo, under a doctor’s supervision. It must not be used for cosmetic skin lightening or for other pigmentation conditions such as melasma or freckles.' )
					. self::h2( 'Possible side effects' )
					. self::ul( array( 'Redness, burning, itching, dryness or a rash where it is applied.', 'Loss of pigment in skin that was not treated.', '[To be written: less common side effects, as listed in the product leaflet]' ) )
					. self::safety( 'Stop using monobenzone and see your doctor if you notice marked irritation, swelling or blistering.' )
					. self::h2( 'Pregnancy and breastfeeding' )
					. self::p( 'Talk to your doctor before using monobenzone if you are pregnant, planning a pregnancy or breastfeeding.' )
					. self::h2( 'Storage' )
					. self::todo( 'storage temperature, keeping out of reach of children, expiry' )
					. self::h2( 'Questions to ask your doctor' )
					. self::todo( 'checklist of questions' ),
			),
			'how-to-apply-monobenzone-cream'   => array(
				'title'   => 'How to Apply Monobenzone Cream: Step-by-Step Guide',
				'cat'     => 'using-benoquik-safely',
				'tags'    => array( 'monobenzone', 'how to' ),
				'image'   => array( 'story-3.webp', 'Placeholder image — calm light-blue abstract texture' ),
				'excerpt' => 'A practical step-by-step guide to applying monobenzone cream as prescribed: patch testing, applying a thin layer, avoiding the eyes, washing hands and protecting your skin.',
				'refs'    => array( $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'Always follow your own doctor’s instructions — this guide is general.', 'Do a patch test first, as your doctor advises.', 'Apply a thin layer only where prescribed, then wash your hands.' ) )
					. self::h2( 'Before you start' )
					. self::todo( 'valid prescription, discussing the plan with your dermatologist, patch test (about 7 days, as your doctor directs)' )
					. self::h2( 'Step by step' )
					. self::ul(
						array(
							'Make sure the skin is clean and dry.',
							'Apply a thin layer only to the areas your doctor prescribed, as often as prescribed.',
							'Avoid the eyes, mouth and inside of the nose.',
							'Wash your hands straight after applying.',
							'Let the cream absorb before treated skin touches other people — it can lighten their skin too.',
						),
						true
					)
					. self::h2( 'If you miss a dose' )
					. self::todo( 'general advice — follow your doctor or the product leaflet' )
					. self::h2( 'Common mistakes to avoid' )
					. self::todo( 'applying too much, applying to broken or sunburnt skin, skipping sunscreen' )
					. self::h2( 'Sun protection during treatment' )
					. self::p( 'Depigmented skin burns easily. Use a broad-spectrum sunscreen every day and cover up outdoors. See our sun-care guide for a daily routine.' ),
			),
			'sun-care-for-vitiligo'            => array(
				'title'   => 'Sun Care for Vitiligo: A Practical Daily Guide',
				'cat'     => 'sun-care-skin-care',
				'tags'    => array( 'sunscreen', 'skin care' ),
				'image'   => array( 'bento-sun.webp', 'Placeholder image — sunscreen bottle on a light-blue background' ),
				'excerpt' => 'Skin without pigment burns easily. A simple daily sun-care routine for people with vitiligo or depigmented skin: sunscreen, clothing, shade and travel tips.',
				'refs'    => array( $nhs, $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'Skin without pigment has little natural protection from UV light.', 'Use a broad-spectrum sunscreen every day and reapply often.', 'Clothing, hats and shade are just as important.' ) )
					. self::h2( 'Why depigmented skin burns easily' )
					. self::todo( 'role of melanin in UV protection' )
					. self::h2( 'Choosing a sunscreen' )
					. self::todo( 'broad-spectrum (UVA/UVB), high SPF, water resistance, formulas for face and body' )
					. self::h2( 'How to apply sunscreen properly' )
					. self::todo( 'amount, timing before going out, reapplying, after swimming or sweating' )
					. self::h2( 'Clothing, hats and shade' )
					. self::todo( 'UPF clothing, wide-brimmed hats, sunglasses, avoiding strong midday sun' )
					. self::h2( 'A simple daily routine' )
					. self::box( 'callout-info', self::p( '[To be written: a short morning-to-evening checklist]' ) )
					. self::h2( 'Travel and holidays' )
					. self::todo( 'hot climates, altitude, beach and snow' ),
			),
			'order-prescription-medicine-online-safely' => array(
				'title'   => 'How to Order Prescription Medicine Online Safely',
				'cat'     => 'ordering-shipping',
				'tags'    => array( 'prescription', 'online pharmacy', 'shipping' ),
				'image'   => array( 'bento-doctor.webp', 'Placeholder image — blue leaf on a light background' ),
				'excerpt' => 'How to recognise a legitimate online pharmacy, check your country’s import rules, use your own doctor’s prescription and know what to expect from international delivery and customs.',
				'refs'    => array( $add ),
				'content' => self::draft_note()
					. self::takeaways( array( 'A legitimate pharmacy always asks for a valid prescription.', 'Check that personal import with a prescription is allowed in your country.', 'Customs duties and delivery times depend on your country.' ) )
					. self::h2( 'Signs of a legitimate online pharmacy' )
					. self::todo( 'asks for a prescription, licensed pharmacist, clear company and license details, secure checkout' )
					. self::h2( 'Check your country’s import rules' )
					. self::todo( 'personal import of prescription medicines; where to check' )
					. self::h2( 'Using your own doctor’s prescription' )
					. self::todo( 'what the prescription should show, language, file types' )
					. self::h2( 'Delivery, customs and import duties' )
					. self::todo( 'tracking, typical delivery times, duties paid by the recipient' )
					. self::h2( 'Protecting your data and payment' )
					. self::todo( 'secure payments, how prescriptions are stored' )
					. self::h2( 'Red flags to watch for' )
					. self::todo( 'no prescription needed, unrealistic promises, no contact details' ),
			),
		);
	}

	private static function image( $file, $alt ) {
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'meta_key'    => '_bq_blog_source', // phpcs:ignore
				'meta_value'  => $file, // phpcs:ignore
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $existing ) {
			return $existing[0];
		}
		$src = get_theme_file_path( 'assets/images/' . $file );
		if ( ! is_readable( $src ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $file );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => 'learn-' . $file, 'tmp_name' => $tmp ), 0, $alt );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, '_bq_blog_source', $file );
		return $id;
	}

	public static function run() {
		$log = array();
		$ids = array();
		foreach ( self::categories() as $slug => $c ) {
			$term = get_term_by( 'slug', $slug, 'category' );
			if ( $term ) {
				$ids[ $slug ] = (int) $term->term_id;
				$log[]        = "Category exists: {$c[0]}";
				continue;
			}
			$t = wp_insert_term( $c[0], 'category', array( 'slug' => $slug, 'description' => $c[1] ) );
			if ( ! is_wp_error( $t ) ) {
				$ids[ $slug ] = (int) $t['term_id'];
				$log[]        = "Category created: {$c[0]} (/learn/category/{$slug}/)";
			}
		}

		$author = get_current_user_id() ? get_current_user_id() : (int) ( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 1 );
		foreach ( self::articles() as $slug => $a ) {
			$found = get_posts( array( 'name' => $slug, 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( $found ) {
				$log[] = "Article exists (kept): {$a['title']}";
				continue;
			}
			$post_id = wp_insert_post(
				wp_slash(
					array(
						'post_type'     => 'post',
						'post_status'   => 'draft',
						'post_title'    => $a['title'],
						'post_name'     => $slug,
						'post_excerpt'  => $a['excerpt'],
						'post_content'  => $a['content'],
						'post_author'   => $author,
						'post_category' => isset( $ids[ $a['cat'] ] ) ? array( $ids[ $a['cat'] ] ) : array(),
						'tags_input'    => $a['tags'],
					)
				)
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$log[] = "Could not create: {$a['title']}";
				continue;
			}
			$img = self::image( $a['image'][0], $a['image'][1] );
			if ( $img ) {
				set_post_thumbnail( $post_id, $img );
			}
			update_post_meta( $post_id, 'bq_references', $a['refs'] );
			if ( ! empty( $a['sticky'] ) ) {
				stick_post( $post_id );
			}
			$log[] = "Draft created: {$a['title']}";
		}
		flush_rewrite_rules( false );
		$log[] = 'Rewrite rules flushed. Nothing was published; pages, products and the homepage were not changed.';
		return $log;
	}
}

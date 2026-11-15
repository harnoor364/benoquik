<?php
/**
 * International page content (used by `wp benoquik migrate-intl`).
 *
 * EVERY PAGE IS PLACEHOLDER CONTENT — it must be reviewed by a lawyer (and,
 * for medical text, by your pharmacist/medical advisor) before launch.
 * Contact details, delivery times, shipping rates and restricted countries are
 * pulled live from Benoquik → Settings and WooCommerce shipping zones through
 * shortcodes, so they stay correct when you change them there.
 *
 * @package BenoquikCore
 */

defined( 'ABSPATH' ) || exit;

$bq_h2   = static fn( $t ) => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$t}</h2>\n<!-- /wp:heading -->\n\n";
$bq_h3   = static fn( $t ) => "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$t}</h3>\n<!-- /wp:heading -->\n\n";
$bq_p    = static fn( $t ) => "<!-- wp:paragraph -->\n<p>{$t}</p>\n<!-- /wp:paragraph -->\n\n";
$bq_sc   = static fn( $t ) => "<!-- wp:shortcode -->\n{$t}\n<!-- /wp:shortcode -->\n\n";
$bq_ul   = static function ( array $items ) {
	$li = '';
	foreach ( $items as $i ) {
		$li .= "<!-- wp:list-item -->\n<li>{$i}</li>\n<!-- /wp:list-item -->\n";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">{$li}</ul>\n<!-- /wp:list -->\n\n";
};
$bq_note = static function ( $extra = '' ) {
	$t = '<strong>Placeholder — have this reviewed by a lawyer before launch.</strong> This text is a starting point written for an international store run by an Indian company. It is not legal advice.' . ( $extra ? ' ' . $extra : '' );
	return "<!-- wp:paragraph {\"className\":\"bq-note bq-intl\",\"backgroundColor\":\"sky\",\"fontSize\":\"small\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"1rem\",\"bottom\":\"1rem\",\"left\":\"1.25rem\",\"right\":\"1.25rem\"}}}} -->\n<p class=\"bq-note bq-intl has-sky-background-color has-background has-small-font-size\" style=\"padding-top:1rem;padding-right:1.25rem;padding-bottom:1rem;padding-left:1.25rem\">{$t}</p>\n<!-- /wp:paragraph -->\n\n";
};
$bq_details = static function ( $q, $a ) {
	return "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>{$q}</summary><!-- wp:paragraph -->\n<p>{$a}</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->\n\n";
};

return array(

	'shipping-availability'        => array(
		'title'     => 'Shipping & Availability',
		'old_slugs' => array( 'shipping-policy' ),
		'content'   => $bq_note()
			. $bq_p( 'Benoquik® is dispatched from our licensed pharmacy in India to customers worldwide. Every order is shipped with tracking and in plain, discreet packaging.' )
			. $bq_h2( 'Where we ship' )
			. $bq_p( 'We ship to most countries. At checkout you can only choose a country we currently deliver to. We do <strong>not</strong> ship to:' )
			. $bq_sc( '[benoquik_restricted_countries]' )
			. $bq_p( 'Some countries restrict the import of prescription medicines. Before you order, please check that monobenzone 20% cream may be imported for personal use with a prescription in your country. At checkout we ask you to confirm this.' )
			. $bq_h2( 'Delivery times' )
			. $bq_p( 'Delivery times start once your prescription has been approved and your order has been dispatched. They are estimates, not guarantees — customs checks can add time.' )
			. $bq_sc( '[benoquik_delivery_table]' )
			. $bq_h2( 'Shipping costs' )
			. $bq_sc( '[benoquik_shipping_rates]' )
			. $bq_h2( 'Tracking' )
			. $bq_p( 'When your order ships we email you a tracking number and link. You can also follow it on our <a href="/track-order/">Track order</a> page or in <a href="/my-account/">My account</a>.' )
			. $bq_h2( 'Customs, import duties and taxes' )
			. $bq_p( 'Our prices do not include import duties, VAT, sales tax or customs fees charged by your country. These are set by your country’s customs authority and are <strong>paid by the recipient</strong>, usually on delivery. We cannot predict or refund these charges.' )
			. $bq_h2( 'If customs holds or refuses your parcel' )
			. $bq_ul(
				array(
					'Customs may ask you for a copy of your prescription or other documents. Please reply to them quickly — we will help with any paperwork we can provide.',
					'If customs <strong>destroys or refuses</strong> a parcel because import is not allowed, we are not able to offer a refund or replacement, because the medicine cannot be returned to stock.',
					'If a parcel is returned to us undamaged and unopened, we will refund the product price minus the original shipping cost (see <a href="/returns-refunds-cancellation/">Returns, Refunds & Cancellation</a>).',
				)
			),
	),

	'returns-refunds-cancellation' => array(
		'title'   => 'Returns, Refunds & Cancellation',
		'content' => $bq_note()
			. $bq_h2( 'Cancelling an order' )
			. $bq_p( 'You can cancel free of charge until your order is dispatched. Contact us with your order number. Once a parcel has left our pharmacy it can no longer be cancelled.' )
			. $bq_p( 'If our pharmacist cannot approve your prescription, we cancel the order and refund you in full.' )
			. $bq_h2( 'Returns' )
			. $bq_p( 'For safety reasons, <strong>medicines cannot be returned once dispatched</strong>, except when:' )
			. $bq_ul(
				array(
					'the item arrived damaged, or',
					'you received the wrong item.',
				)
			)
			. $bq_p( 'Please contact us within 7 days of delivery with your order number and photos of the parcel and product. Do not use a damaged product.' )
			. $bq_h2( 'Refunds' )
			. $bq_ul(
				array(
					'Refunds are made to your <strong>original payment method</strong>, in the <strong>original currency</strong> of your order.',
					'We process approved refunds within 5 business days. Your bank or card provider may take a further 5–10 business days to show the money.',
					'Because of exchange-rate changes and bank fees, the amount shown on your statement may differ slightly from what you paid. We cannot cover these differences.',
					'Import duties, taxes and customs fees paid to your country’s authorities are not refundable by us.',
				)
			),
	),

	'terms-and-conditions'         => array(
		'title'   => 'Terms & Conditions',
		'content' => $bq_note( 'Add your company’s full legal name, registered address and registration numbers.' )
			. $bq_h2( '1. Who we are' )
			. $bq_p( 'This website is operated by [legal business name], a company registered in India ("we", "us"). Benoquik® is marketed by Quik Pharmaceuticals. Our registered address and licenses are listed on our <a href="/licenses-compliance/">Licenses & Compliance</a> page.' )
			. $bq_h2( '2. Prescription medicine' )
			. $bq_ul(
				array(
					'Benoquik® (monobenzone 20% w/w) is a prescription-only medicine for people with extensive vitiligo. It permanently removes pigment from normal skin. It is not a cure for vitiligo and does not restore color.',
					'Every order needs a valid prescription, written in English, from a doctor licensed in your country. Our pharmacist checks every prescription and may contact you or refuse an order.',
					'You must follow your own doctor’s instructions and your country’s medical advice.',
				)
			)
			. $bq_h2( '3. International orders and import rules' )
			. $bq_ul(
				array(
					'You are responsible for making sure the product may legally be imported into your country for your personal use, with a prescription. We ask you to confirm this at checkout.',
					'You must not resell, or order on behalf of anyone other than the patient named on the prescription.',
					'Import duties, taxes and customs fees are paid by you (see <a href="/shipping-availability/">Shipping & Availability</a>).',
				)
			)
			. $bq_h2( '4. Prices, currency and payment' )
			. $bq_ul(
				array(
					'Prices are shown in US dollars (USD) and exclude import duties, taxes and customs fees.',
					'If you pay with a card or account in another currency, your bank converts the amount at its own exchange rate and may charge a fee. We do not control these rates or fees.',
					'Payments are processed securely by our payment provider. We do not store your full card details.',
				)
			)
			. $bq_h2( '5. Your account' )
			. $bq_p( 'You need an account to order. Keep your login details private. You must be 18 or older, or order through a parent or guardian.' )
			. $bq_h2( '6. Liability' )
			. $bq_p( 'Nothing in these terms limits liability that cannot be limited by law. We are not responsible for delays or refusals by customs authorities or delivery companies outside our control.' )
			. $bq_h2( '7. Governing law' )
			. $bq_p( 'These terms are governed by the laws of India. Courts in [city], India have jurisdiction, without removing any mandatory consumer rights you have in your own country.' ),
	),

	'privacy-policy'               => array(
		'title'   => 'Privacy Policy',
		'content' => $bq_note( 'Confirm what data you collect, your payment, hosting and email providers, and how long you keep records.' )
			. $bq_h2( 'Who is responsible for your data' )
			. $bq_p( '[Legal business name], India, is the data controller. Contact us at [support email] about anything in this policy.' )
			. $bq_h2( 'What we collect and why' )
			. $bq_ul(
				array(
					'<strong>Account and order details</strong> (name, email, phone, addresses, order history) — to process and deliver your orders.',
					'<strong>Prescriptions</strong> — to check that each order is clinically appropriate and legal.',
					'<strong>Records that you accepted our terms</strong> (date and the policy version) — to meet our legal obligations.',
					'<strong>Payment information</strong> — handled by our payment provider; we do not store full card numbers.',
					'<strong>Website usage</strong> (cookies) — see "Cookies" below.',
				)
			)
			. $bq_h2( 'How prescriptions are stored' )
			. $bq_p( 'Prescription files are stored in a private area of our server that is not publicly accessible. Only our pharmacy staff can open them, to verify your order. We keep them for as long as the law requires for pharmacy records, then delete them securely.' )
			. $bq_h2( 'International transfers' )
			. $bq_p( 'We are based in India, so your personal data is <strong>transferred to and processed in India</strong>, which may have different data-protection laws from your country. We protect it with appropriate safeguards, such as contractual clauses with our service providers, encryption in transit and restricted access.' )
			. $bq_h2( 'Your rights' )
			. $bq_p( 'Depending on where you live, you may have the right to:' )
			. $bq_ul(
				array(
					'access a copy of your personal data;',
					'correct inaccurate data;',
					'ask us to delete your data (we may need to keep some records, such as prescriptions and invoices, for legal reasons);',
					'receive your data in a portable format;',
					'object to or restrict certain processing, and withdraw consent at any time;',
					'complain to your local data-protection authority.',
				)
			)
			. $bq_h3( 'EU and UK residents (GDPR / UK GDPR)' )
			. $bq_p( 'Our legal bases are: performing our contract with you (orders), legal obligations (pharmacy and tax records), legitimate interests (preventing fraud, improving our website) and your consent (marketing emails, non-essential cookies). Health data such as prescriptions is processed only to provide the medicine you ordered and as required by law.' )
			. $bq_h2( 'Cookies' )
			. $bq_p( 'We use essential cookies to run the shop (cart, login, security). We only use analytics or marketing cookies if you agree to them, and you can change your choice at any time.' )
			. $bq_h2( 'How long we keep data' )
			. $bq_p( 'Order and prescription records are kept for the period required by Indian pharmacy and tax law. Marketing data is kept until you unsubscribe.' ),
	),

	'contact'                      => array(
		'title'   => 'Contact',
		'content' => $bq_note( 'Fill in your real phone number, email and address in Benoquik → Settings.' )
			. $bq_p( 'Our team and registered pharmacist are based in India and help customers worldwide.' )
			. $bq_sc( '[benoquik_contact]' )
			. $bq_h2( 'Questions about your treatment?' )
			. $bq_p( 'Our pharmacist can answer questions about Benoquik, but cannot replace advice from your own doctor. Please follow your doctor’s instructions and your country’s medical advice.' ),
	),

	'about'                        => array(
		'title'   => 'About',
		'content' => $bq_note()
			. $bq_h2( 'Based in India, shipping worldwide' )
			. $bq_p( 'Benoquik® (Monobenzone Cream USP 20% w/w) is marketed by Quik Pharmaceuticals and supplied by our licensed pharmacy in India to customers around the world.' )
			. $bq_p( 'We serve people living with extensive vitiligo who, together with their dermatologist, have decided on depigmentation therapy for a more even skin tone. Monobenzone permanently removes pigment from normal skin. It is not a cure for vitiligo and does not restore color.' )
			. $bq_h2( 'How we work' )
			. $bq_ul(
				array(
					'Every order is checked by a registered pharmacist against a valid prescription.',
					'Genuine product, shipped with tracking in discreet packaging.',
					'Clear information on use, safety and sun protection.',
				)
			),
	),

	'faq'                          => array(
		'title'   => 'FAQ',
		'content' => $bq_note( 'Medical answers should also be reviewed by your pharmacist/medical advisor.' )
			. $bq_h2( 'Ordering & delivery' )
			. $bq_details( 'Do you ship to my country?', 'We ship to most countries worldwide. At checkout you can only choose countries we deliver to. See <a href="/shipping-availability/">Shipping & Availability</a> for the full list of countries we cannot ship to.' )
			. $bq_details( 'How long does delivery take?', 'Usually 7–20 business days after your prescription is approved, depending on your country and customs. You can check an estimate for your country on each product page.' )
			. $bq_details( 'Will I pay customs duties?', 'Possibly. Our prices exclude import duties, VAT and customs fees. If your country charges them, they are paid by you on delivery.' )
			. $bq_details( 'Which currencies do you accept?', 'Prices are in US dollars (USD). You can pay with most international cards; your bank converts the amount to your own currency at its exchange rate.' )
			. $bq_details( 'Can I track my order?', 'Yes. Every order ships with tracking. We email you the tracking link when your order is dispatched, and you can also see it in My account.' )
			. $bq_h2( 'Prescriptions' )
			. $bq_details( 'Is my prescription from my own doctor accepted?', 'Yes — a valid prescription from a doctor licensed in your country is required. It must be in English, clearly readable and show the patient’s name, the medicine, the doctor’s name, registration and signature. Upload it as a PDF, JPG or PNG.' )
			. $bq_details( 'Do I need a prescription?', 'Yes. Benoquik is a prescription-only medicine. Our pharmacist verifies every order before dispatch.' )
			. $bq_h2( 'About the treatment' )
			. $bq_details( 'Does Benoquik cure vitiligo?', 'No. It does not cure vitiligo and does not restore color. It permanently removes the remaining pigment from normal skin so that skin tone becomes even, for people with extensive vitiligo under a dermatologist’s supervision.' )
			. $bq_details( 'Is the depigmentation permanent?', 'Yes. Depigmentation with monobenzone is generally permanent and cannot be reliably reversed, so make this decision carefully with your dermatologist.' )
			. $bq_details( 'How do I protect my skin from the sun?', 'Depigmented skin burns easily. Use a broad-spectrum sunscreen every day, wear protective clothing and avoid strong midday sun.' ),
	),

	'grievance-officer'            => array(
		'title'   => 'Grievance Officer',
		'content' => $bq_note( 'Required by Indian law (Information Technology Rules and Consumer Protection (E-Commerce) Rules). Fill in the real details.' )
			. $bq_p( 'We are an Indian company, so Indian law requires us to name a Grievance Officer. Customers in any country can contact this person if a complaint has not been resolved by our support team.' )
			. $bq_ul(
				array(
					'<strong>Name:</strong> [Grievance Officer name] (placeholder)',
					'<strong>Designation:</strong> [designation] (placeholder)',
					'<strong>Email:</strong> [email] (placeholder)',
					'<strong>Phone:</strong> +91 [number] (placeholder)',
					'<strong>Address:</strong> [registered address], India (placeholder)',
					'<strong>Hours:</strong> Mon–Fri, 10:00–17:00 IST (UTC+5:30)',
				)
			)
			. $bq_p( 'We acknowledge complaints within 48 hours and aim to resolve them within one month.' ),
	),

	'licenses-compliance'          => array(
		'title'     => 'Licenses & Compliance',
		'old_slugs' => array( 'licences-compliance' ),
		'content'   => $bq_note( 'Fill in the real license numbers from your documents.' )
			. $bq_p( 'Benoquik® is supplied by a licensed pharmacy in India and shipped internationally. These are our Indian registrations:' )
			. $bq_sc( '[benoquik_legal_details]' )
			. $bq_p( 'Importing medicines is regulated by each country. Customers are responsible for checking that personal import with a prescription is allowed where they live.' ),
	),

	'medical-disclaimer'           => array(
		'title'   => 'Medical Disclaimer',
		'content' => $bq_note( 'Have this reviewed by your medical advisor as well.' )
			. $bq_p( 'Benoquik® (monobenzone 20% w/w) is a prescription medicine for people with extensive vitiligo, to be used only under the supervision of a doctor. It permanently removes pigment from normal skin. It is not a cure for vitiligo and does not restore color.' )
			. $bq_p( 'The information on this website is general and for international readers. It does not replace advice from your own doctor or pharmacist. Always follow your doctor’s instructions, the product leaflet, and the medical advice and regulations of your own country.' )
			. $bq_p( 'Stop using the product and see a doctor if you notice irritation, swelling or any unexpected reaction. Talk to your doctor before use if you are pregnant, planning a pregnancy or breastfeeding.' ),
	),

	'upload-prescription'          => array(
		'title'   => 'Upload Prescription',
		'content' => $bq_note( 'The upload form itself is part of the prescription system still to be built.' )
			. $bq_p( 'Benoquik® is a prescription-only medicine. You will upload your prescription at checkout, or you can send it before ordering.' )
			. $bq_h2( 'What we accept' )
			. $bq_ul(
				array(
					'A valid prescription from a <strong>doctor licensed in your country</strong>.',
					'Written in <strong>English</strong> (or with a certified English translation).',
					'Showing the patient’s name, the medicine (monobenzone 20%), the doctor’s name, registration number, signature and the date.',
					'File types: <strong>PDF, JPG or PNG</strong>, up to 5 MB.',
				)
			)
			. $bq_p( 'Our pharmacist checks every prescription before dispatch and may contact you if anything is unclear.' ),
	),
);

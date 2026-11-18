# Placeholder checklist — fill these in before going live

Everything below is a **placeholder** or needs your confirmation. Nothing has been removed. Tick each
item as you replace it.

> **Tip:** **Benoquik → Settings** holds most of the business details in one place: contact, support hours,
> announcement, marquee, delivery estimates, duties text, restricted countries and legal details.

## 1. Before launch: must confirm

| ✓ | Item | Why | Where |
|---|---|---|---|
| ☐ | **Razorpay "International Payments" activated** | The store charges in **USD**. Razorpay only accepts non-INR payments once International Payments is approved on your Razorpay account. Until then customers get a payment error. WordPress admin shows a warning notice until you confirm. | Razorpay Dashboard → Account & Settings → International Payments, then test a USD payment in Test mode |
| ☐ | **Countries we do not ship to** | Currently **India only**. Add every country where importing monobenzone, or personal import of prescription medicines, isn't allowed. **Review with your legal advisor.** | **Benoquik → Settings → Shipping countries** (two-letter codes, e.g. `IN, US`) |
| ☐ | **All policy pages reviewed by a lawyer** | Rewritten for international buyers but still placeholders (see section 7) | **Pages** |
| ☐ | **Privacy Policy published** | It's still a **DRAFT**. Until it's published, the Privacy Policy links in the registration checkbox and at checkout show "page not found" | **Settings → Privacy**, then edit and publish |
| ☐ | **Geolocation key** | "Default customer location: Geolocate" needs a free MaxMind license key, otherwise no country is pre-selected | **WooCommerce → Settings → Integration → MaxMind Geolocation** |
| ☐ | **SEO texts (Rank Math)** | Site title and meta description, Open Graph text, and Organization schema for an international audience; mention India only as the company's location | **Rank Math → Titles & Meta**, and **Rank Math → General → Local SEO** ("Organization"; set *no* geo-targeting) |

## 2. Prices (USD) — all placeholders

Each product also has an admin-only note saying the prices are placeholders (**Product data → General → Internal note**).

| ✓ | Product | Variation | SKU | Price (placeholder) |
|---|---|---|---|---|
| ☐ | Benoquik Cream 20 g | 1 tube (20 g) | BQ-20G-1 | $29.00 |
| ☐ | Benoquik Cream 20 g | 2 tubes (2 × 20 g) | BQ-20G-2 | $55.00 |
| ☐ | Benoquik 2-Pack | Cream only | BQ-20G-X2-C | $55.00 |
| ☐ | Benoquik 2-Pack | With SPF 50 sunscreen | BQ-20G-X2-SPF | $105.00 |
| ☐ | Benoquik 3-Pack | Cream only | BQ-20G-X3-C | $79.00 |
| ☐ | Benoquik 3-Pack | With SPF 50 sunscreen | BQ-20G-X3-SPF | $149.00 |

Edit prices in **Products → (product) → Edit → Product data → Variations**. To show a struck-through
"was" price, put the old price in *Regular price* and the new one in *Sale price*.
Confirm you actually sell the "With SPF 50 sunscreen" bundles, or delete those variations.

## 3. Shipping (USD) — all placeholders

**WooCommerce → Settings → Shipping**. Every zone has "Free tracked shipping" on orders over **$99**.

| ✓ | Zone | Countries | Flat rate | Free over |
|---|---|---|---|---|
| ☐ | United States & Canada | US, CA | $15.00 | $99.00 |
| ☐ | United Kingdom & Europe | All European countries | $15.00 | $99.00 |
| ☐ | Middle East (GCC) | AE, SA, QA, KW, BH, OM | $12.00 | $99.00 |
| ☐ | Australia & New Zealand | AU, NZ | $18.00 | $99.00 |
| ☐ | Rest of world ("Locations not covered by your other zones") | Everything else, except restricted countries | $20.00 | $99.00 |

If you change the $99 free-shipping threshold, also update the **announcement bar**, the **marquee**
(Benoquik → Settings) and the homepage FAQ.

| ✓ | Item | Current value | Where |
|---|---|---|---|
| ☐ | Delivery estimates by country | US/CA 7–12, GB/IE 7–10, DE/FR 7–12, AE 5–8, SA 6–10, AU/NZ 8–14 business days; others 10–20 | **Benoquik → Settings → Delivery estimate by country** |
| ☐ | Product weights (placeholders) | 1 tube 45 g, 2 tubes 90 g, 2-Pack 90 g, +sunscreen 190 g, 3-Pack 135 g, +sunscreen 235 g | **Product data → Shipping** and each variation |
| ☐ | Product dimensions (placeholders) | 15 × 4 × 3 cm (single), 15 × 8 × 4 cm (2-Pack), 15 × 12 × 4 cm (3-Pack) | **Product data → Shipping** |
| ☐ | Import duties notice (cart & checkout) | "Prices exclude any import duties, VAT or customs fees…" | **Benoquik → Settings → Import duties notice** |
| ☐ | Import confirmation checkbox text | "I confirm this medicine may be legally imported into my country…" | **Benoquik → Settings → Import confirmation checkbox** (have your lawyer approve it) |

## 4. Company & legal details (Indian legal entity, footer and legal pages only)

| ✓ | Item | Current placeholder | Where |
|---|---|---|---|
| ☐ | Registered business name | `Benoquik Pharmacy Pvt. Ltd. (placeholder)` | **Benoquik → Settings → Company & legal details** |
| ☐ | Registered address (India) | `Registered address, India (placeholder)` | same |
| ☐ | Drug license no. (India) | `DL-XX-000000 (placeholder)` | same |
| ☐ | GSTIN (Indian tax registration) | `00AAAAA0000A0Z0 (placeholder)` | same |
| ☐ | Registered pharmacist + registration no. | `Pharmacist Name (placeholder)`, `Reg. No. 00000 (placeholder)` | same |
| ☐ | Quik Pharmaceuticals address (from the pack) | `Address (placeholder)` | same → Marketer address |
| ☐ | Manufacturer / marketer license no. (from the pack) | `Mfg. Lic. No. (placeholder)` | same |
| ☐ | Store address for invoices (stays in India) | WooCommerce store address | **WooCommerce → Settings → General → Store Address** |
| ☐ | Grievance Officer: name, designation, email, +91 phone, address | Placeholders on the page | **Pages → Grievance Officer** (required by Indian law) |
| ☐ | Terms & Conditions: legal business name, court city | `[legal business name]`, `[city]` | **Pages → Terms & Conditions** |

## 5. Contact & support

| ✓ | Item | Current placeholder | Where |
|---|---|---|---|
| ☑ | WhatsApp number (international format, digits only) | `18314802325` | **Benoquik → Settings → WhatsApp number** |
| ☑ | Support phone (with country code) | `+1-8314802325` | **Benoquik → Settings → Support phone** |
| ☑ | Support email | `Support@benoquik.us` | **Benoquik → Settings → Support email** |
| ☐ | Support hours and response time | `Mon–Sat, 09:00–18:00 IST (UTC+5:30) · We reply within 1 business day` | **Benoquik → Settings → Support hours** (also used in emails; re-run *Repair store data* to refresh the email texts) |
| ☐ | Email footer and extra email texts | International wording with support hours | **WooCommerce → Settings → Emails** (each email → *Additional content*) |

## 6. People, testimonials & images (homepage)

| ✓ | Item | Current placeholder | Where |
|---|---|---|---|
| ☐ | Dermatologist quote and signature | "Dr. A. Placeholder", "MD (Dermatology) · Placeholder name…" | **Pages → Home → Edit** → quote section. Use a real advisor only with their written consent |
| ☐ | Patient stories | "Sarah, 38 · London", "Daniel, 45 · Toronto" (video card, ▶ Play links to `#`), "Aisha, 33 · Dubai", "Maria, 51 · Sydney", all "(placeholder)" | **Pages → Home → Edit → Patient stories** |
| ☐ | Decorative images | Hero background, "Prescribed and supervised" image, 5 bento tiles, 4 story images, CTA background (computer-generated, no real people) | **Pages → Home → Edit**: click an image → *Replace* |
| ✓ | Product photos | The real pack photo (2-Pack / 3-Pack use a "2 × 20 g" / "3 × 20 g" badge) | **Products → Edit → Product image** |

**To hide Patient stories** until you have real, consented testimonials: **Pages → Home → Edit** → List View →
"Patient stories (placeholder…)" → **⋮ → Hide**. Never use before/after photos, cure claims or timed result promises.

## 7. Pages: rewritten for international customers, to be reviewed by a lawyer

Each page starts with a box saying "Placeholder — have this reviewed by a lawyer before launch". Remove that box once a page is approved.

- ☐ Shipping & Availability (was "Shipping Policy"; the old address redirects)
- ☐ Returns, Refunds & Cancellation
- ☐ Terms & Conditions (governing law India; import legality; prescription; currency)
- ☐ Privacy Policy (GDPR/UK GDPR rights, transfer to India, cookies, prescription storage). **Still a draft, publish it**
- ☐ Contact (phone, email, WhatsApp and hours come from Benoquik → Settings)
- ☐ About ("Based in India, shipping worldwide")
- ☐ FAQ (international questions added)
- ☐ Grievance Officer (Indian legal requirement)
- ☐ Licenses & Compliance (was "Licences & Compliance"; the old address redirects)
- ☐ Medical Disclaimer
- ☐ Upload Prescription (the upload form itself is still to be built)
- ☐ Track Order (still a placeholder)

Also delete WordPress's sample content: **Posts → "Hello world!"** and **Pages → "Sample Page"**.

## 8. Medical text: review with your pharmacist / medical advisor

| ✓ | Item | Where |
|---|---|---|
| ☐ | Medicine information accordions (Overview, How to use, Side effects, Safety advice, Storage, FAQs) | **Products → Edit → Medicine information** |
| ☐ | Homepage FAQ and "How Benoquik works" tiles | **Pages → Home → Edit** |
| ☐ | Prescription notice ("upload a valid prescription from a licensed doctor in your country…") | **Benoquik → Settings → Prescription notice** |

## 9. Accounts & security

| ✓ | Item | Where |
|---|---|---|
| ☐ | Admin login `admin` / `admin` (local demo only) | **Users → Profile → New password** |
| ☐ | Admin email `admin@example.com` | **Settings → General → Administration Email Address** |

## 10. Learn (blog)

| ✓ | Item | Where |
|---|---|---|
| ☐ | 6 starter articles are **drafts** with outlines and "[To be written: …]" placeholders. Write each one and have it **medically reviewed** before publishing | **Posts** (filter: Drafts) |
| ☐ | References in the drafts are marked "(to verify)" or "Add peer-reviewed source(s) here" | Edit post → sidebar → **Benoquik article details → References** |
| ☐ | Featured images are abstract placeholders (alt text says "Placeholder image") | Edit post → **Featured image** |
| ☐ | Author name and photo. Articles currently show the **admin** user. Set a real display name, bio, **Credentials** and **Profile photo** | **Users → Profile** ("Benoquik author profile" section) |
| ☐ | Medical reviewer for each article (name, credentials such as "MD, Dermatology", optional bio link, last reviewed date) | Edit post → **Benoquik article details** |
| ☐ | Delete WordPress's sample post "Hello world!" | **Posts** |
| ☐ | Rank Math breadcrumbs: turn on **Show Blog Page** and **Show Category(s)** so articles read *Home / Learn / Category / Title* | **Rank Math → General Settings → Breadcrumbs** |
| ☐ | Rank Math schema for posts: **Article** (or *BlogPosting*) | **Rank Math → Titles & Meta → Posts → Schema Type** |
| ☐ | Confirm posts are in the sitemap | **Rank Math → Sitemap Settings → Posts → Include in Sitemap** = On, then open `/sitemap_index.xml` |
| ☐ | Article disclaimer text | **Benoquik → Settings → Learn (blog)** |

### How to publish a blog post

1. **Write:** go to **Posts → Add New** (or open a draft from **Posts**). Type the title, then write the article. Use
   **Heading 2** for main sections and **Heading 3** for sub-sections. They build the "In this article" table of
   contents automatically.
   - Notice boxes: click **+ → Patterns → Benoquik: Article boxes** (*Key takeaways*, *Info*, *Important*, *Safety warning*),
     or select any Group block → **Styles**.
   - Don't add the product card, disclaimer, references or author box yourself. They're added automatically at the end.
2. **Category:** in the right-hand sidebar, open the **Post** tab → **Categories** and tick one, for example *Using Benoquik Safely*.
   Add a few **Tags** if you like.
3. **Featured image + alt text:** sidebar → **Featured image** → *Set featured image* → upload or choose an image. Before
   clicking *Set featured image*, fill in **Alternative Text** with a short description of the picture. If it's missing,
   the editor shows a warning. Use a landscape image (16:9, at least 1600 × 900 px).
4. **Excerpt:** sidebar → **Excerpt**: one or two sentences. It appears under the title and on the article cards.
5. **Medical reviewer & references:** sidebar → **Benoquik article details**:
   - reviewer name, credentials (e.g. "MD, Dermatology"), optional bio link and **Last reviewed** date;
   - **+ Add reference** for each source (title + link);
   - tick **Hide the product card** for articles where the product card isn't appropriate.

   Reading time is calculated automatically.
6. **Rank Math:** in the Rank Math panel (top right, or below the editor), enter a **Focus Keyword**, then edit the
   **SEO title** and **meta description** (*Edit Snippet*). Aim for green checks, but keep the medical wording accurate:
   no "cure", "guaranteed" or timed result promises.
7. **Check & publish:** remove the "DRAFT — to be written and medically reviewed" box. Click **Preview** to check it on
   phone and desktop, then **Publish**. The pre-publish panel lists anything still missing (featured image, alt text,
   reviewer, draft note).
   - To make an article the large **featured** card at the top of /learn/, sidebar → **Summary** → tick **Stick to the top of the blog**.

# Benoquik — WordPress block theme + store plugin

A calm, editorial WooCommerce store for **Benoquik (monobenzone 20% w/w cream)**, a prescription
depigmentation therapy for people with extensive vitiligo. Built for Indian patients and caregivers
(prices in ₹, mobile-first).

> **Status:** Step 1 of 4 is complete: the local preview, design system, header, footer and **homepage**.
> Product page, cart/checkout styling, the prescription upload and review system, other pages, the blog
> and the full owner's guide come in the next steps (see the [roadmap](#roadmap)).

---

## What's in this repository

| Folder | What it is | Upload to |
|---|---|---|
| `benoquik-theme/` | The **design**: colours, fonts, header, footer, page templates, homepage sections (block patterns) | *Appearance → Themes → Add New → Upload* (as a .zip) |
| `benoquik-core/` | The **business logic**: Settings page, WhatsApp/marquee/announcement/licence blocks, newsletter sign-ups, demo import (and, next, prescriptions) | *Plugins → Add New → Upload* (as a .zip) |
| `docker-compose.yml`, `bin/setup.sh` | Local preview site on your own computer | — |
| `tools/` | Script that generated the placeholder images | — |

The design and the business logic are kept apart on purpose. If you ever change themes, your
prescriptions, settings and subscribers stay safe in the plugin.

---

## Preview the site on your computer (about 5 minutes)

You only need **Docker Desktop** (free): https://www.docker.com/products/docker-desktop/

1. Install Docker Desktop and open it. Wait until it says *Engine running*.
2. Download this repository (the green **Code** button → *Download ZIP*) and unzip it.
   Or, if you use git: `git clone https://github.com/harnoor364/benoquik.git`
3. Open **Terminal** (Mac) or **PowerShell** (Windows), go into the folder and start the site:

```bash
cd benoquik
```

```bash
docker compose up -d
```

4. The first time only, install WordPress, WooCommerce and the demo content:

```bash
./bin/setup.sh
```

5. Open **http://localhost:8080**. To edit, open **http://localhost:8080/wp-admin** and log in
   with username `admin` and password `admin`.

Stop the site with `docker compose down`. Start it again later with `docker compose up -d`; you
don't need to run setup again. To wipe everything and start fresh, run `docker compose down -v`
and then repeat steps 3 and 4.

> Windows: run `bash bin/setup.sh` inside *Git Bash* or WSL if `./bin/setup.sh` doesn't run.

---

## Install on LocalWP (or any real WordPress site)

1. Get the two zips: run `./bin/build-zips.sh`, which puts them in `dist/` (or use the zips you were sent).
   - `benoquik-theme.zip`: the theme
   - `benoquik-core.zip`: the companion plugin
2. Create a site in LocalWP (Preferred environment is fine) and open **WP Admin**.
   Check **Dashboard → Updates**: WooCommerce now needs **WordPress 7.0 or newer**.
3. **Plugins → Add New → search "WooCommerce" → Install → Activate.** Skip the setup wizard for now.
4. **Plugins → Add New → Upload Plugin →** choose `benoquik-core.zip` **→ Install Now → Activate**.
5. **Appearance → Themes → Add New → Upload Theme →** choose `benoquik-theme.zip` **→ Install Now → Activate**.
6. **Settings → Permalinks →** pick *Custom Structure* and enter `/learn/%postname%/`. In the
   *Product permalinks* section, choose *Shop base* (gives `/shop/product-name/`). Save.
7. **WooCommerce → Settings → General:** set the store country to *India*, the currency to
   *Indian rupee (₹)* and *Number of decimals* to `0`. Save.
8. **Benoquik → Import demo content → Import.** This creates the Home page, the placeholder pages and
   3 demo products, and sets Home as your front page.
9. Visit the site. Change the WhatsApp number and licence details in **Benoquik → Settings**.

---

## Editing the homepage (no code)

The homepage is a normal page: **Pages → Home → Edit**. Each section is a block pattern:

| # | Section | How to change it |
|---|---|---|
| 1 | Hero (headline, buttons, trust row, floating product card) | Click any text to type. Click the background → *Replace* to change the image |
| 2 | "Treatment is a journey" + 3 product cards | Cards come **live from WooCommerce**. Select the product block → sidebar to choose which products appear |
| 3 | "Prescribed and supervised by dermatologists" | Click to edit text and image |
| 4 | "How Benoquik works" (5 image tiles) | Click a tile to edit its caption or replace its image |
| 5 | Dermatologist quote | Click to edit. **The doctor name is a placeholder** |
| 6 | Lime scrolling strip | **Benoquik → Settings → Marquee items** (one per line) |
| 7 | Patient stories | Click to edit. Put a YouTube link on the *▶ Play* button. **All stories are placeholders** |
| 8 | FAQ accordion | Click a question to edit it. To add one: select an item → ⋮ → *Duplicate* |
| 9 | "Start your treatment with confidence" | The button opens WhatsApp. Set the number in **Benoquik → Settings** |

- **Reorder** sections with the ↑ ↓ arrows on the block toolbar. **Remove** one with ⋮ → *Delete*.
- **Add a section back**, or put one on another page: click **+** → *Patterns* → **Benoquik: Homepage sections**.
- **Start the homepage over**: Benoquik → Import demo content → tick *Rebuild the Home page* → Import.

### Header, footer and site-wide text

| What | Where |
|---|---|
| Announcement bar above the header | **Benoquik → Settings → Announcement bar text** (leave empty to hide it) |
| Lime marquee text | **Benoquik → Settings → Marquee items** |
| WhatsApp number (every "Talk to a pharmacist" button) | **Benoquik → Settings → WhatsApp number** |
| Drug licence, GSTIN, pharmacist name and registration no., company name | **Benoquik → Settings → Licences & compliance** |
| Menu links (header and footer) | **Appearance → Editor → Navigation** (or *Patterns → Header/Footer*) |
| Footer heading, disclaimer and big wordmark | **Appearance → Editor → Patterns → Footer** |
| Colours and fonts | **Appearance → Editor → Styles** (preset to the brand palette) |
| Newsletter sign-ups | **Benoquik → Subscribers**, plus **Export subscribers (CSV)** |

> **Tip:** any button gets the WhatsApp number automatically if you give it the CSS class
> `bq-whatsapp` (select the button → *Advanced* → *Additional CSS class*).

---

## Design system (in `theme.json`)

- **Fonts** (self-hosted, no Google requests): *Instrument Serif* for headings, *Inter* for body and UI text
- **Colours:** cream `#F6F4EF` · olive charcoal `#23281F` · taupe `#8A8273` (framing only) ·
  lime `#D9E59A` (buttons and highlights only) · ink `#2B2B2B` (body text) · muted `#5C584F` (AA-safe secondary text)
- **Radius:** 12 / 16 / 24 px, with pill buttons and an automatic → arrow (add the class `no-arrow` to hide it)
- **Motion:** fade-up on scroll, lift on hover and a slow marquee with a pause button. Everything switches off when the visitor's device asks for reduced motion.

## Medical and content rules followed

- The homepage says clearly that monobenzone **permanently** removes pigment, is **not a cure** and
  does **not restore colour**, and is only for **extensive vitiligo under a doctor's supervision**.
- No fairness, "glow", beauty or skin-lightening language. No before/after images, no "cure",
  "guaranteed" or "miracle", and no timed result promises.
- Safety points included: patch test first, avoid the eyes, wash hands after applying, strict sun
  protection, may lighten untreated skin, stop and see a doctor if irritation or swelling occurs,
  and consult a doctor if pregnant or breastfeeding.
- A medical disclaimer appears in the footer of every page.
- **Placeholders:** all images (computer-generated stand-ins, no real people), testimonials, the
  doctor's name, licence numbers and prices. Replace them before launch.

## SEO

The theme outputs **no** meta tags, Open Graph tags or schema. **Rank Math** controls titles,
descriptions, schema (Product, FAQ, Organization, Article), breadcrumbs and sitemaps, so nothing is
duplicated. Every page has exactly one H1. Permalinks are `/shop/product-name/` and
`/learn/post-name/`.

## Plugins to install (not bundled)

WooCommerce · Rank Math SEO · Razorpay for WooCommerce (plus the built-in Cash on Delivery) · a
caching plugin (e.g. LiteSpeed Cache or WP Super Cache) · an image optimiser that creates WebP
(e.g. EWWW or ShortPixel) · WP Mail SMTP · a security plugin (e.g. Wordfence or Solid Security) ·
UpdraftPlus (backups).
Step-by-step setup for each comes with the full guide in step 4.

---

## Roadmap

1. ✅ **Local preview, design system, header, footer, homepage** (this step)
2. ⏳ WooCommerce: product page (gallery and zoom, pack sizes, MRP strikethrough, ℞ notice, pincode
   delivery estimate, sticky mobile add-to-cart, info accordions), shop, cart, checkout and order
   confirmation. **Prescription system:** "Requires prescription" checkbox, checkout upload
   (JPG/PNG/PDF, 5 MB), private storage, Approve/Reject on the order screen, the "Awaiting
   prescription review" status and emails. Track Order and My Account → Saved prescriptions
3. ⏳ Pages: About, Contact, FAQ, Upload Prescription, legal pages, 404, search, blog archive and
   single post (author box, reading time, table of contents, related posts) with 3 articles
4. ⏳ Full owner's guide: products, orders, prescriptions, coupons, blog posts, Rank Math, and the
   go-live checklist

# Benoquik — WordPress block theme + store plugin

A calm, editorial WooCommerce store for **Benoquik (monobenzone 20% w/w cream)**, a prescription
depigmentation therapy for people with extensive vitiligo. An **international** store: the company and
pharmacy are in India, and it ships to customers worldwide (prices in USD, mobile-first).

> **Status:** Step 1 of 4 is complete: the local preview, design system, header, footer and **homepage**.
> Product page, cart/checkout styling, the prescription upload and review system, other pages, the blog
> and the full owner's guide come in the next steps (see the [roadmap](#roadmap)).

---

## What's in this repository

| Folder | What it is | Upload to |
|---|---|---|
| `benoquik-theme/` | The **design**: colors, fonts, header, footer, page templates, homepage sections (block patterns) | *Appearance → Themes → Add New → Upload* (as a .zip) |
| `benoquik-core/` | The **business logic**: Settings page, WhatsApp/marquee/announcement/license blocks, international shipping & checkout rules, newsletter sign-ups, demo import (and, next, prescriptions) | *Plugins → Add New → Upload* (as a .zip) |
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

## International store (theme 0.4.0 / plugin 0.5.0)

The store sells in **USD** to customers outside India and ships from India.

**To update an existing LocalWP site:**
1. Back up the database:
```bash
wp db export ../backup-before-intl.sql
```
2. Replace the `benoquik` theme and `benoquik-core` plugin folders with the new ones.
3. Run the international setup, which also rebuilds the homepage and clears caches:
```bash
wp benoquik migrate-intl
```
   No shell? Use **Benoquik → Repair store data → tick "Also rebuild the Home page" → Run repair**.

**What `migrate-intl` sets:**
- **Currency and prices:** USD (`$12.00`) with the new placeholder prices.
- **Customers and units:** customer location by geolocation, grams and centimeters.
- **Language and dates:** en-US, dates like `15 Nov 2026`, 24-hour times with time zone (`18:49 IST`).
- **Payments:** Cash on Delivery off.
- **Shipping:** five zones with flat rates and free shipping over $99.
- **Content:** international policy pages, emails, and US spelling.

**Always on (from the plugin):**
- **Shipping countries:** all countries except those in **Benoquik → Settings → Countries we do not ship to** (India to start).
- **Checkout requirements:** an international phone number is required, and every order needs both checkboxes:
  the Terms box and *"I confirm this medicine may be legally imported into my country for personal use with a valid prescription."*
  The server enforces both, and both are recorded on the order.
- **Duties notice:** shown on the cart and checkout.
- **Product page:** a country-based delivery estimate, plus "Ships from India · Tracked international delivery · Discreet packaging".
- **Product data:** shipping details for each region are added to WooCommerce's own product markup (no duplicate schema).
- **Razorpay warning:** an admin notice reminds you to enable Razorpay **International Payments** for USD.

## Checkout rules: login and Terms acceptance (plugin v0.4.0)

The order of steps is always **log in or register (with Terms) → checkout form → tick Terms → Place Order → Razorpay popup**.
The Razorpay popup can never open for a logged-out customer, or without the Terms box ticked.

- **Logged-out visitors** who open the checkout (or click *Proceed to checkout*) are sent to **My account** with a
  "Please log in or create an account to place your order" notice. After logging in or registering they come
  straight back to the checkout with their cart intact. Only addresses on this site are accepted as the return address.
- **Registration** needs the *"I agree to the Terms & Conditions and Privacy Policy"* box ticked. The acceptance date and
  the version (last-updated date) of both pages are saved. See **Users → (customer) → Terms & Privacy acceptance**.
- **Checkout** has a required *"I have read and agree…"* box. The server checks it too, so an order can't be
  forced through without it. The acceptance time and policy versions are saved on the order and shown on the
  order screen under the billing address.
- **Payment page** (where Razorpay opens its popup): if an order has no recorded acceptance, for example one you created
  in the admin, the customer sees the pay form with the Terms box first.
- These settings are enforced by the plugin: guest checkout off, registration on the My account page on,
  Terms page = *Terms & Conditions*.

## Updating an existing LocalWP site to v0.3.0 (new colors, real 20 g product, mobile)

In Local's site shell (right-click the site → **Open site shell**), from inside your site folder:

1. Back up the database:
```bash
wp db export ../backup-before-v030.sql
```
2. Copy the new `benoquik-theme` folder over `wp-content/themes/benoquik`, and `benoquik-core` over
   `wp-content/plugins/benoquik-core`. Or upload them as zips with *Replace current with uploaded*.
3. Update the products (20 g packs, names, real photos, SKUs), then rebuild the homepage and clear caches:
```bash
wp benoquik migrate-v030
```
```bash
wp benoquik rebuild-home
```
```bash
wp benoquik flush
```
   No shell? **Benoquik → Repair store data → tick "Also rebuild the Home page" → Run repair** does all three.

Old product addresses (`/shop/benoquik-cream-30g/`, `/shop/benoquik-3-month-pack/`) redirect automatically.

---

## Repairing a LocalWP site imported from the first zip (garbled characters, no Add to cart)

The first `benoquik-localwp-site.zip` had a database file with no `SET NAMES utf8mb4` line. Local
therefore imported it as latin1, which double-encoded every special character (`—` became `â€”`,
`×` became `Ã—`). That also corrupted the stored pack-size data, so variable products lost their
selector and Add to cart was disabled. The exporter is fixed, and the plugin now includes a repair tool.

In **Local**, right-click your site → **Open site shell**, then run these one at a time:

1. **Back up first.** The file is saved next to your `public` folder, at
   `~/Local Sites/<your-site>/app/backup-before-fixes.sql`:
```bash
wp db export ../backup-before-fixes.sql
```
2. Install the updated plugin and theme. Use the zips you downloaded; this example assumes they're in
   your Downloads folder.
```bash
wp plugin install ~/Downloads/benoquik-core.zip --force --activate
```
```bash
wp theme install ~/Downloads/benoquik-theme.zip --force --activate
```
3. Preview what the encoding repair will change. This step changes nothing:
```bash
wp benoquik fix-encoding --dry-run
```
4. Run the whole repair: characters, products, Home page, caches, then a final check.
```bash
wp benoquik upgrade
```
   Add `--keep-home` if you've already edited your homepage and don't want it rebuilt.
   The last lines it prints must show `Rows containing "â€": 0` and `Rows containing "Ã": 0`, and
   `2 purchasable variation(s)` for each product.
5. Make sure Razorpay is active with **International Payments** enabled (see PLACEHOLDERS.md).

To undo everything: `wp db import ../backup-before-fixes.sql`.

No shell? Use **WP Admin → Benoquik → Repair store data** instead (back up with UpdraftPlus first).

**Individual repair commands:** `wp benoquik doctor` (report only) · `wp benoquik fix-encoding` ·
`wp benoquik repair-products` · `wp benoquik rebuild-home` · `wp benoquik flush`

---

## Easiest: import the whole site into LocalWP (one zip)

`benoquik-localwp-site.zip` is a complete copy of the demo site: WordPress content, WooCommerce, the
Benoquik theme and plugin, demo pages and products, and the database. Build it with
`./bin/export-localwp.sh`, which writes it to `dist/`.

1. Open **Local** and drag `benoquik-localwp-site.zip` onto the window (or click **+** at the bottom left →
   **Import an existing site** → choose the zip).
2. Name the site **`benoquik`** so its address is `http://benoquik.local`, which the database is prepared for.
   Keep the *Preferred* environment → **Import site**.
3. When it finishes, click **Open site**. To edit, click **WP Admin** and log in with `admin` / `admin`
   (change this password under **Users → Profile**).
4. If you chose a different site name and the site looks unstyled, open **WP Admin → Tools →
   Better Search Replace** (install that plugin) and replace `http://benoquik.local` with your Local address.

## Install on LocalWP (or any real WordPress site), one piece at a time

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
7. **WooCommerce → Settings → General:** keep the store address in India (for invoices). The currency, selling
   countries and so on are set automatically by step 8 and **Benoquik → Repair store data**.
8. **Benoquik → Import demo content → Import.** This creates the Home page, the placeholder pages and
   3 demo products, and sets Home as your front page.
9. Visit the site. Change the WhatsApp number and license details in **Benoquik → Settings**.

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
| Indian legal details (drug license, GSTIN, pharmacist, registered address) | **Benoquik → Settings → Company & legal details** |
| Menu links (header and footer) | **Appearance → Editor → Navigation** (or *Patterns → Header/Footer*) |
| Footer heading, disclaimer and big wordmark | **Appearance → Editor → Patterns → Footer** |
| Colours and fonts | **Appearance → Editor → Styles** (preset to the brand palette) |
| Newsletter sign-ups | **Benoquik → Subscribers**, plus **Export subscribers (CSV)** |

> **Tip:** any button gets the WhatsApp number automatically if you give it the CSS class
> `bq-whatsapp` (select the button → *Advanced* → *Additional CSS class*).

---

## Design system (in `theme.json`)

- **Fonts** (self-hosted, no Google requests): *Instrument Serif* for headings, *Inter* for body and UI text
- **Colours** (matching the real Benoquik pack):
  - blue `#1F5CA8`: buttons, links, icons and focus rings
  - blue-dark `#163F78`: hover states and the footer
  - navy `#14213D`: headings and text
  - red `#D2232A`: **logo and small accents only**
  - white `#FFFFFF`: background
  - sky `#EEF4FB`: soft sections and cards
  - line `#D6E2F0`: borders
  - muted `#5B6B82`: secondary text

  Every text/background pair passes WCAG AA.
- **Breakpoints:** phones below 600 px (one product per row), tablets 600–1023 px (two per row, hamburger menu), desktop 1024 px and up (three per row)
- **Radius:** 12 / 16 / 24 px, with pill buttons and an automatic → arrow (add the class `no-arrow` to hide it)
- **Motion:** fade-up on scroll, lift on hover and a slow marquee with a pause button. Everything switches off when the visitor's device asks for reduced motion.

## Medical and content rules followed

- The homepage says clearly that monobenzone **permanently** removes pigment, is **not a cure** and
  does **not restore color**, and is only for **extensive vitiligo under a doctor's supervision**.
- No fairness, "glow", beauty or skin-lightening language. No before/after images, no "cure",
  "guaranteed" or "miracle", and no timed result promises.
- Safety points included: patch test first, avoid the eyes, wash hands after applying, strict sun
  protection, may lighten untreated skin, stop and see a doctor if irritation or swelling occurs,
  and consult a doctor if pregnant or breastfeeding.
- A medical disclaimer appears in the footer of every page.
- **Placeholders:** all images (computer-generated stand-ins, no real people), testimonials, the
  doctor's name, license numbers, prices and shipping rates. Replace them before launch.

## SEO

The theme outputs **no** meta tags, Open Graph tags or schema. **Rank Math** controls titles,
descriptions, schema (Product, FAQ, Organization, Article), breadcrumbs and sitemaps, so nothing is
duplicated. Every page has exactly one H1. Permalinks are `/shop/product-name/` and
`/learn/post-name/`.

## Plugins to install (not bundled)

WooCommerce · Rank Math SEO · Razorpay for WooCommerce (with **International Payments** enabled; Cash on Delivery is off for international orders) · a
caching plugin (e.g. LiteSpeed Cache or WP Super Cache) · an image optimiser that creates WebP
(e.g. EWWW or ShortPixel) · WP Mail SMTP · a security plugin (e.g. Wordfence or Solid Security) ·
UpdraftPlus (backups).
Step-by-step setup for each comes with the full guide in step 4.

---

## Placeholders

Every placeholder (names, license numbers, GSTIN, pharmacist, grievance officer, testimonials,
images, prices) is listed in **[PLACEHOLDERS.md](PLACEHOLDERS.md)**, with where to edit each one.

## Roadmap

1. ✅ **Local preview, design system, header, footer, homepage** (this step)
2. ⏳ WooCommerce: product page (gallery and zoom, pack sizes, sale-price strikethrough, ℞ notice, delivery-estimate
   delivery estimate, sticky mobile add-to-cart, info accordions), shop, cart, checkout and order
   confirmation. **Prescription system:** "Requires prescription" checkbox, checkout upload
   (JPG/PNG/PDF, 5 MB), private storage, Approve/Reject on the order screen, the "Awaiting
   prescription review" status and emails. Track Order and My Account → Saved prescriptions
3. ⏳ Pages: About, Contact, FAQ, Upload Prescription, legal pages, 404, search, blog archive and
   single post (author box, reading time, table of contents, related posts) with 3 articles
4. ⏳ Full owner's guide: products, orders, prescriptions, coupons, blog posts, Rank Math, and the
   go-live checklist

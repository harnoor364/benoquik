#!/usr/bin/env bash
# One-time setup of the local preview site. Run AFTER:  docker compose up -d
# Re-running is safe.
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm -T cli wp "$@"; }
# Option updates "fail" when the value is already set — that is fine.
opt() { wp option update "$@" >/dev/null 2>&1 || true; }

echo "→ Waiting for the database…"
for i in $(seq 1 30); do
  if docker compose run --rm -T cli wp db check >/dev/null 2>&1; then break; fi
  sleep 3
done

if ! wp core is-installed >/dev/null 2>&1; then
  echo "→ Installing WordPress…"
  wp core install --url="http://localhost:8080" --title="Benoquik" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi

echo "→ Installing WooCommerce…"
if ! wp plugin is-installed woocommerce; then
  mkdir -p .cache
  [ -s .cache/woocommerce.zip ] || curl -fL --retry 3 -o .cache/woocommerce.zip \
    https://github.com/woocommerce/woocommerce/releases/latest/download/woocommerce.zip
  wp plugin install /cache/woocommerce.zip
fi
wp plugin activate woocommerce benoquik-core
wp theme activate benoquik

echo "→ Store settings (India, ₹, no decimals)…"
opt timezone_string "Asia/Kolkata"
opt woocommerce_default_country "IN:MH"
opt woocommerce_currency "INR"
opt woocommerce_currency_pos "left"
opt woocommerce_price_num_decimals 0
opt woocommerce_price_thousand_sep ","
opt woocommerce_onboarding_profile '{"skipped":true}' --format=json
opt woocommerce_task_list_hidden "yes"
opt woocommerce_coming_soon "no"
opt woocommerce_enable_coupons "yes"

echo "→ Clean permalinks (/shop/product-name/, /learn/post-name/)…"
wp rewrite structure '/learn/%postname%/'
opt woocommerce_permalinks '{"product_base":"/shop","category_base":"shop/category","tag_base":"shop/tag","attribute_base":"","use_verbose_page_rules":true}' --format=json
wp wc tool run install_pages --user=admin >/dev/null 2>&1 || true

echo "→ Demo content…"
wp eval 'foreach ( Benoquik\Demo_Import::run() as $l ) { echo $l, PHP_EOL; }'
wp rewrite flush --hard

echo ""
echo "✅ Done!  Site:  http://localhost:8080    Admin: http://localhost:8080/wp-admin  (admin / admin)"

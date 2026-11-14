#!/usr/bin/env bash
# Exports the running local site as ONE zip that LocalWP can import
# (Local -> "+" -> "Import an existing site" / drag the zip onto Local).
# Contains: wp-content (WooCommerce, Benoquik theme + plugin, media) and the database (.sql).
set -euo pipefail
cd "$(dirname "$0")/.."
wp() { docker compose run --rm -T cli wp "$@"; }

wp plugin delete akismet hello >/dev/null 2>&1 || true
wp transient delete --all >/dev/null 2>&1 || true
# Export with URLs rewritten for a LocalWP site named "benoquik" (live DB is untouched).
LOCAL_URL="${LOCAL_URL:-http://benoquik.local}"
wp search-replace "http://localhost:8080" "$LOCAL_URL" --all-tables --skip-columns=guid \
  --export=/var/www/html/benoquik.sql >/dev/null

OUT=dist/localwp; rm -rf "$OUT"; mkdir -p "$OUT/benoquik-site"
C=$(docker compose ps -q wordpress)
docker cp "$C":/var/www/html/wp-content "$OUT/benoquik-site/wp-content"
docker cp "$C":/var/www/html/benoquik.sql "$OUT/benoquik-site/benoquik.sql"
docker compose exec -T wordpress rm -f /var/www/html/benoquik.sql

# The theme & plugin are mounted from this repo; copy the real files in.
rm -rf "$OUT/benoquik-site/wp-content/themes/benoquik" "$OUT/benoquik-site/wp-content/plugins/benoquik-core"
cp -R benoquik-theme "$OUT/benoquik-site/wp-content/themes/benoquik"
cp -R benoquik-core "$OUT/benoquik-site/wp-content/plugins/benoquik-core"
rm -rf "$OUT/benoquik-site/wp-content/uploads/wc-logs" "$OUT/benoquik-site/wp-content/upgrade" "$OUT/benoquik-site/wp-content/cache"
find "$OUT" -name '.DS_Store' -delete

(cd "$OUT" && zip -qr ../benoquik-localwp-site.zip benoquik-site)
rm -rf "$OUT"
ls -lh dist/benoquik-localwp-site.zip

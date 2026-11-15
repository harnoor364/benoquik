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

# IMPORTANT: "wp search-replace --export" writes no charset header. Without it, importers
# (LocalWP included) may read the file as latin1 and double-encode every — ’ × ₹ ℞ character.
SQL="$OUT/benoquik-site/benoquik.sql"
{ printf '/*!40101 SET NAMES utf8mb4 */;\nSET NAMES utf8mb4;\n'; cat "$SQL"; } > "$SQL.tmp" && mv "$SQL.tmp" "$SQL"

# The theme & plugin are mounted from this repo; copy the real files in.
rm -rf "$OUT/benoquik-site/wp-content/themes/benoquik" "$OUT/benoquik-site/wp-content/plugins/benoquik-core"
cp -R benoquik-theme "$OUT/benoquik-site/wp-content/themes/benoquik"
cp -R benoquik-core "$OUT/benoquik-site/wp-content/plugins/benoquik-core"
rm -rf "$OUT/benoquik-site/wp-content/uploads/wc-logs" "$OUT/benoquik-site/wp-content/upgrade" "$OUT/benoquik-site/wp-content/cache"
# Never ship local test helpers (e.g. payment-gateway stand-ins used only for automated tests).
rm -f "$OUT"/benoquik-site/wp-content/mu-plugins/bq-test-* "$OUT"/benoquik-site/wp-content/mu-plugins/bq-trace.php "$OUT/benoquik-site/wp-content/debug.log"
find "$OUT" -name '.DS_Store' -delete

(cd "$OUT" && zip -qr ../benoquik-localwp-site.zip benoquik-site)
rm -rf "$OUT"
ls -lh dist/benoquik-localwp-site.zip

#!/usr/bin/env bash
# Builds upload-ready zips in ./dist:
#   benoquik-theme.zip  -> Appearance > Themes > Add New > Upload Theme
#   benoquik-core.zip   -> Plugins > Add New > Upload Plugin
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist && rm -rf dist/tmp dist/benoquik-theme.zip dist/benoquik-core.zip && mkdir -p dist/tmp
cp -R benoquik-theme dist/tmp/benoquik          # theme folder must be named "benoquik"
cp -R benoquik-core dist/tmp/benoquik-core
find dist/tmp -name '.DS_Store' -delete
(cd dist/tmp && zip -qr ../benoquik-theme.zip benoquik && zip -qr ../benoquik-core.zip benoquik-core)
rm -rf dist/tmp
ls -lh dist

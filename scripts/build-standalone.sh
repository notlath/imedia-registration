#!/usr/bin/env bash
# =============================================================================
# IMedia Registration — Standalone App Build Script
#
# Flattens the standalone app into a deployable package at dist/registration/.
# The package root IS the web root — no public/ subdirectory.
#
# Usage:  ./scripts/build-standalone.sh
# Output: dist/imedia-registration-standalone.zip
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST="$PROJECT_ROOT/dist"
OUT_DIR="$DIST/registration"

echo "==> Cleaning dist/"
rm -rf "$DIST"
mkdir -p "$OUT_DIR"

echo "==> Copying app/"
cp -r "$PROJECT_ROOT/app" "$OUT_DIR/app"

echo "==> Copying config/"
cp -r "$PROJECT_ROOT/config" "$OUT_DIR/config"

echo "==> Copying vendor/"
if [ -d "$PROJECT_ROOT/vendor" ]; then
    cp -r "$PROJECT_ROOT/vendor" "$OUT_DIR/vendor"
else
    echo "WARNING: vendor/ not found. PHPMailer will not be available."
fi

echo "==> Copying resources/"
cp -r "$PROJECT_ROOT/resources" "$OUT_DIR/resources"

# public/assets/ — standalone CSS, JS, Chart.js (views reference /assets/...)
# These are separate from resources/assets/ (which are WP-admin assets)
echo "==> Copying public/assets/"
if [ -d "$PROJECT_ROOT/public/assets" ]; then
    cp -r "$PROJECT_ROOT/public/assets" "$OUT_DIR/assets"
else
    echo "WARNING: public/assets/ not found. Admin UI will be unstyled."
fi

echo "==> Copying cron/"
cp -r "$PROJECT_ROOT/cron" "$OUT_DIR/cron"

echo "==> Copying routes.php"
cp "$PROJECT_ROOT/routes.php" "$OUT_DIR/routes.php"

echo "==> Copying database/"
mkdir -p "$OUT_DIR/database"
cp "$PROJECT_ROOT/database/schema.sql" "$OUT_DIR/database/schema.sql"
for f in "$PROJECT_ROOT"/database/migration-*.sql; do
    [ -f "$f" ] && cp "$f" "$OUT_DIR/database/"
done

echo "==> Flattening public/ -> root"

# index.php — fix require paths (was ../app/..., now root-level)
sed \
  -e 's|require __DIR__ . '"'"'/\.\./app/Core/Bootstrap\.php'"'"'|require __DIR__ . '"'"'/app/Core/Bootstrap.php'"'"'|' \
  -e 's|require __DIR__ . '"'"'/\.\./routes\.php'"'"'|require __DIR__ . '"'"'/routes.php'"'"'|' \
  "$PROJECT_ROOT/public/index.php" > "$OUT_DIR/index.php"

# .htaccess — same content, now at root level
cp "$PROJECT_ROOT/public/.htaccess" "$OUT_DIR/.htaccess"

# uploads/ — mirror structure (currently empty, but ensure it exists)
if [ -d "$PROJECT_ROOT/public/uploads" ]; then
    cp -r "$PROJECT_ROOT/public/uploads" "$OUT_DIR/uploads"
else
    mkdir -p "$OUT_DIR/uploads"
fi

# IMPORTANT: Remove <Directory> block from .htaccess since it won't work
# in per-directory context. The FilesMatch deny is sufficient.
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' '/<Directory "uploads">/,/<\/Directory>/d' "$OUT_DIR/.htaccess"
else
    sed -i '/<Directory "uploads">/,/<\/Directory>/d' "$OUT_DIR/.htaccess"
fi
# Remove any trailing blank lines left by sed
awk 'NF { blank=0 } !NF { blank++ } blank<=1' "$OUT_DIR/.htaccess" > "$OUT_DIR/.htaccess.tmp" && mv "$OUT_DIR/.htaccess.tmp" "$OUT_DIR/.htaccess"

echo "==> Patching IMREG_PUBLIC_PATH in Bootstrap.php"
# Change: IMREG_PUBLIC_PATH = IMREG_BASE_PATH . '/public'
# To:     IMREG_PUBLIC_PATH = IMREG_BASE_PATH
BOOTSTRAP="$OUT_DIR/app/Core/Bootstrap.php"
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s|define('IMREG_PUBLIC_PATH', IMREG_BASE_PATH . '/public');|define('IMREG_PUBLIC_PATH', IMREG_BASE_PATH);|" "$BOOTSTRAP"
else
    sed -i "s|define('IMREG_PUBLIC_PATH', IMREG_BASE_PATH . '/public');|define('IMREG_PUBLIC_PATH', IMREG_BASE_PATH);|" "$BOOTSTRAP"
fi

echo "==> Copying INSTALLATION.md"
if [ -f "$PROJECT_ROOT/INSTALLATION.md" ]; then
    cp "$PROJECT_ROOT/INSTALLATION.md" "$OUT_DIR/INSTALLATION.md"
else
    echo "WARNING: INSTALLATION.md not found at project root."
fi

echo "==> Removing development-only files"
rm -rf "$OUT_DIR/tests" 2>/dev/null || true
rm -f "$OUT_DIR/.gitignore" 2>/dev/null || true

echo "==> Creating dist/imedia-registration-standalone.zip"
cd "$DIST"
zip -r imedia-registration-standalone.zip registration/ \
    -x "registration/storage/logs/*" \
    -x "registration/.DS_Store" \
    -x "registration/**/.DS_Store" \
    -x "registration/storage/.gitkeep"
cd "$PROJECT_ROOT"

echo ""
echo "==> Done!"
echo "    Package: dist/imedia-registration-standalone.zip"
echo "    Extract to: public_html/registration/"
echo ""

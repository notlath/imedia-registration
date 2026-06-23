#!/usr/bin/env bash
# =============================================================================
# build-dist.sh — Build the WordPress plugin distribution package
# =============================================================================
# Output: wp-registration-plugin/wp-content/plugins/imedia-registration/
#
# Usage:
#   ./tools/build-dist.sh              # normal build
#   ./tools/build-dist.sh --no-dev     # exclude dev dependencies
#   ./tools/build-dist.sh --help       # this message
#
# Prerequisites:
#   - rsync
#   - composer
#   - PHP 8.1+
# =============================================================================
set -euo pipefail

DIST_BASE="wp-registration-plugin/wp-content/plugins/imedia-registration"
SRC_EXCLUDES="--exclude=vendor/ --exclude=tests/ --exclude=tools/ --exclude=coverage/ --exclude=.phpunit.cache/"

usage() {
  sed -n '3,12p' "$0"
  exit 0
}

# ---- Parse args ----
NO_DEV=false
for arg in "$@"; do
  case "$arg" in
    --help) usage ;;
    --no-dev) NO_DEV=true ;;
    *) echo "Unknown option: $arg"; usage ;;
  esac
done

# ---- Build ----
echo "==> Removing previous distribution…"
rm -rf "$DIST_BASE"

echo "==> Copying source tree…"
mkdir -p "$DIST_BASE"
rsync -a --delete $SRC_EXCLUDES ./ "$DIST_BASE/"

echo "==> Installing production dependencies…"
cd "$DIST_BASE"
if [ "$NO_DEV" = true ]; then
  composer install --no-dev --no-interaction --optimize-autoloader
else
  composer install --no-interaction
fi

echo ""
echo "==> Distribution built: $DIST_BASE"
echo "    Size: $(du -sh . | cut -f1)"
echo "    Files: $(find . -type f | wc -l)"

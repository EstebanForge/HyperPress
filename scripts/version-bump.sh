#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PACKAGE_NAME="$(basename "$PROJECT_DIR")"

CURRENT_VERSION=$(awk -F'"' '/"version":/{print $4; exit}' "$PROJECT_DIR/composer.json")

if [[ -z "${CURRENT_VERSION:-}" ]]; then
  echo "Error: Could not detect current version from composer.json"
  exit 1
fi

echo ""
echo "┌─────────────────────────────────────┐"
echo "│   ${PACKAGE_NAME} Version Bump"
echo "└─────────────────────────────────────┘"
echo ""
echo "  Current version: $CURRENT_VERSION"
echo ""

while true; do
  read -rp "  Enter new version (X.Y.Z): " NEW_VERSION

  if [[ -z "$NEW_VERSION" ]]; then
    echo "  ✗ Version cannot be empty"
    continue
  fi

  if [[ ! "$NEW_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "  ✗ Invalid format. Use semantic versioning: X.Y.Z (e.g., 1.2.3)"
    continue
  fi

  if [[ "$NEW_VERSION" == "$CURRENT_VERSION" ]]; then
    echo "  ✗ New version must be different from current version"
    continue
  fi

  break
done

sedi() {
  if [[ "$OSTYPE" == darwin* ]]; then
    sed -i '' "$@"
  else
    sed -i "$@"
  fi
}

echo ""
echo "  Bumping: $CURRENT_VERSION -> $NEW_VERSION"
echo ""

# Update composer.json version field
sedi "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$PROJECT_DIR/composer.json"
echo "  ✓ composer.json"

# Update WordPress plugin header version if present
for candidate in hyperpress.php api-for-htmx.php hyperfields.php hyperblocks.php; do
  file="$PROJECT_DIR/$candidate"
  if [[ -f "$file" ]]; then
    sedi -E "s/^(\s*\*\s*Version:\s*).*/\1$NEW_VERSION/" "$file"
    echo "  ✓ $candidate"
  fi
done

# Update WordPress.org readme stable tag when present
if [[ -f "$PROJECT_DIR/README.txt" ]]; then
  sedi -E "s/^(Stable tag:\s*).*/\1$NEW_VERSION/" "$PROJECT_DIR/README.txt"
  echo "  ✓ README.txt (Stable tag)"
fi

# Update security policy supported versions when present
if [[ -f "$PROJECT_DIR/SECURITY.md" ]]; then
  sedi -E "s/^(\|\s*)[0-9]+\.[0-9]+\.[0-9]+(\s*\|\s*:white_check_mark:\s*\|)/\1$NEW_VERSION\2/" "$PROJECT_DIR/SECURITY.md"
  sedi -E "s/^(\|\s*<)[0-9]+\.[0-9]+\.[0-9]+(\s*\|\s*:x:\s*\|)/\1$NEW_VERSION\2/" "$PROJECT_DIR/SECURITY.md"
  echo "  ✓ SECURITY.md"
fi

echo ""
echo "┌─────────────────────────────────────┐"
echo "│  Version bumped to $NEW_VERSION"
echo "└─────────────────────────────────────┘"
echo ""
echo "  Next steps:"
echo "    1. Update changelog/release notes"
echo "    2. composer production"
echo "    3. git add -A && git commit -m 'Bump version to $NEW_VERSION'"

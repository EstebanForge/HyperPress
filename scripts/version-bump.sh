#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PACKAGE_NAME="$(basename "$PROJECT_DIR")"

cd "$PROJECT_DIR" || exit 1

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

FILES_UPDATED=()

# Update composer.json version field
if sedi "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$PROJECT_DIR/composer.json"; then
  FILES_UPDATED+=("composer.json")
fi

# Update WordPress plugin header version if present
for candidate in hyperpress.php api-for-htmx.php hyperfields.php hyperblocks.php; do
  file="$PROJECT_DIR/$candidate"
  if [[ -f "$file" ]]; then
    if sedi -E "s/(\* Version:) +[0-9]+\.[0-9]+\.[0-9]+/\1 $NEW_VERSION/" "$file"; then
      FILES_UPDATED+=("$candidate (header)")
    fi
  fi
done

# Update WordPress.org readme stable tag when present
if [[ -f "$PROJECT_DIR/README.txt" ]]; then
  if sedi -E "s/(Stable tag:)[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+/\1 $NEW_VERSION/" "$PROJECT_DIR/README.txt"; then
    FILES_UPDATED+=("README.txt (Stable tag)")
  fi
fi

# Update security policy supported versions when present
if [[ -f "$PROJECT_DIR/SECURITY.md" ]]; then
  # Update current version row (| X.Y.Z | :white_check_mark: |)
  if sedi -E "s/^[|][[:space:]]*[0-9]+\.[0-9]+\.[0-9]+[[:space:]]*[|][[:space:]]*:white_check_mark:/| $NEW_VERSION | :white_check_mark:/" "$PROJECT_DIR/SECURITY.md"; then
    FILES_UPDATED+=("SECURITY.md (current version)")
  fi
  # Update previous version marker (| <X.Y.Z | :x: |)
  if sedi -E "s/^[|][[:space:]]*<[0-9]+\.[0-9]+\.[0-9]+[[:space:]]*[|]/| <$NEW_VERSION |/" "$PROJECT_DIR/SECURITY.md"; then
    FILES_UPDATED+=("SECURITY.md (previous marker)")
  fi
fi

echo "  ✓ Files updated:"
for file in "${FILES_UPDATED[@]}"; do
  echo "    - $file"
done

echo ""
echo "┌─────────────────────────────────────┐"
echo "│  Version bumped to $NEW_VERSION"
echo "└─────────────────────────────────────┘"
echo ""
echo "  Next steps:"
echo "    1. Update CHANGELOG.md with release notes"
echo "    2. Run: composer run production"
echo "    3. Commit: git add -A && git commit -m 'Bump version to $NEW_VERSION'"
echo ""

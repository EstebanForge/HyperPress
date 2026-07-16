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

# --- bump math (shared) ---
bump_version() {
  local cur="$1" level="$2" major minor patch
  IFS='.' read -r major minor patch <<< "$cur"
  case "$level" in
    major) major=$((major+1)); minor=0; patch=0 ;;
    minor) minor=$((minor+1)); patch=0 ;;
    patch) patch=$((patch+1)) ;;
    *) return 1 ;;
  esac
  echo "${major}.${minor}.${patch}"
}

usage() {
  cat >&2 <<EOF
Usage:
  scripts/version-bump.sh                 # interactive prompt
  scripts/version-bump.sh --patch         # bump patch (1.2.3 -> 1.2.4)
  scripts/version-bump.sh --minor         # bump minor
  scripts/version-bump.sh --major         # bump major
  scripts/version-bump.sh --version 1.2.4 # explicit version
EOF
}

# --- resolve target version ---
BUMP_LEVEL=""
NEW_VERSION=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --version) NEW_VERSION="${2:-}"; shift 2 ;;
    --patch|--minor|--major) BUMP_LEVEL="${1#--}"; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage; exit 2 ;;
  esac
done

if [[ -z "$NEW_VERSION" && -z "$BUMP_LEVEL" ]]; then
  # Interactive mode (default when no flags are passed)
  while true; do
    read -rp "  Enter new version (X.Y.Z): " NEW_VERSION
    if [[ -z "$NEW_VERSION" ]]; then echo "  ✗ Version cannot be empty"; continue; fi
    if [[ ! "$NEW_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then echo "  ✗ Invalid format. Use X.Y.Z (e.g., 1.2.3)"; continue; fi
    if [[ "$NEW_VERSION" == "$CURRENT_VERSION" ]]; then echo "  ✗ New version must be different from current version"; continue; fi
    break
  done
elif [[ -n "$BUMP_LEVEL" ]]; then
  NEW_VERSION="$(bump_version "$CURRENT_VERSION" "$BUMP_LEVEL")" || { echo "  ✗ Invalid bump level" >&2; exit 1; }
  echo "  Computed ($BUMP_LEVEL): $CURRENT_VERSION -> $NEW_VERSION"
else
  if [[ ! "$NEW_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then echo "  ✗ Invalid format. Use X.Y.Z (e.g., 1.2.3)" >&2; exit 1; fi
  if [[ "$NEW_VERSION" == "$CURRENT_VERSION" ]]; then echo "  ✗ New version must be different from current version" >&2; exit 1; fi
fi

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

echo ""
echo "┌─────────────────────────────────────┐"
echo "│  Version bumped to $NEW_VERSION"
echo "└─────────────────────────────────────┘"
echo ""
echo "  Next steps:"
echo "    1. Update changelog/release notes"
echo "    2. composer production"
echo "    3. git add -A && git commit -m 'Bump version to $NEW_VERSION'"
echo ""
echo "RESULT: $CURRENT_VERSION -> $NEW_VERSION"

#!/usr/bin/env bash
# Simple script to update only README.txt in WordPress.org SVN repository

set -euo pipefail

# Configuration
PLUGINSLUG="api-for-htmx"
CURRENTDIR=$(pwd)
SVNPATH="/tmp/$PLUGINSLUG-readme-update"
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/"

# Prompt for SVN username interactively
echo "========================================="
echo "WordPress.org SVN README Update"
echo "========================================="
echo ""
read -rp "Enter your WordPress.org SVN username: " SVNUSER

if [ -z "$SVNUSER" ]; then
    echo "Error: SVN username cannot be empty."
    exit 1
fi

echo ""
echo "SVN username set to: $SVNUSER"
echo ""

# Detect default branch (main or master)
MAIN_BRANCH=$(git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@^.*/@@')
if [ -z "$MAIN_BRANCH" ]; then
    # Fallback detection if symbolic-ref fails
    if git show-ref --verify --quiet refs/remotes/origin/main; then
        MAIN_BRANCH="main"
    elif git show-ref --verify --quiet refs/remotes/origin/master; then
        MAIN_BRANCH="master"
    else
        echo "Could not detect default branch. Please ensure you have origin/main or origin/master."
        exit 1
    fi
fi

echo "Detected default branch: $MAIN_BRANCH"

echo "..........................................."
echo
echo "Preparing to update README.txt in WordPress repository"
echo
echo "..........................................."
echo

# Clean up any previous temp directory
rm -rf "$SVNPATH"

# Check if README.txt exists
if [ ! -f "$CURRENTDIR/README.txt" ]; then
    echo "README.txt not found in current directory. Exiting..."
    exit 1
fi

echo "Creating local copy of SVN repo ..."
svn co "$SVNURL" "$SVNPATH" || exit 1

echo "Updating README.txt in trunk..."
cp "$CURRENTDIR/README.txt" "$SVNPATH/trunk/README.txt" || exit 1

echo "Changing directory to SVN trunk and committing README.txt"
cd "$SVNPATH/trunk/" || exit 1

# Check if there are changes to commit
if svn status README.txt | grep -q "README.txt"; then
    echo "Committing updated README.txt to trunk"
    svn commit --username="$SVNUSER" -m "Update README.txt - maintenance-focused messaging" || exit 1
    echo "README.txt updated successfully!"
else
    echo "No changes detected in README.txt"
fi

echo "Removing temporary directory $SVNPATH"
rm -fr "${SVNPATH:?}/"

echo "*** DONE ***"

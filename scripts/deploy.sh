#!/usr/bin/env bash
# https://github.com/GaryJones/wordpress-plugin-git-flow-svn-deploy
# http://speakinginbytes.com/2012/10/wordpress-plugin-deployment-script/

set -euo pipefail

# main config
PLUGINSLUG="api-for-htmx"
CURRENTDIR=$(pwd)
MAINFILE="api-for-htmx.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org, with no trailing slash

# Prompt for SVN username interactively
echo "========================================="
echo "WordPress.org SVN Deployment"
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

# Let's begin...
echo "..........................................."
echo
echo "Preparing to deploy to WordPress repository"
echo
echo "..........................................."
echo

# Check version in README.txt is the same as plugin file after translating both to unix line breaks to work around grep's failure to identify mac line breaks
PLUGINVERSION=$(grep "Version:" "$GITPATH/$MAINFILE" | awk -F' ' '{print $NF}' | tr -d '\r')
echo "$MAINFILE version: $PLUGINVERSION"
READMEVERSION=$(grep "^Stable tag:" "$GITPATH/README.txt" | awk -F' ' '{print $NF}' | tr -d '\r')
echo "README.txt version: $READMEVERSION"

if [ "$READMEVERSION" = "trunk" ]; then
    echo "Version in README.txt & $MAINFILE don't match, but Stable tag is trunk. Let's proceed..."
elif [ "$PLUGINVERSION" != "$READMEVERSION" ]; then
    echo "Version in README.txt & $MAINFILE don't match. Exiting...."
    exit 1
elif [ "$PLUGINVERSION" = "$READMEVERSION" ]; then
    echo "Versions match in README.txt and $MAINFILE. Let's proceed..."
fi

# Clean up any previous temp directory
rm -rf "$SVNPATH"

if git show-ref --tags --quiet --verify -- "refs/tags/$PLUGINVERSION"; then
    echo "Version $PLUGINVERSION already exists as git tag. Using existing tag..."
    echo -n "Enter a commit message for SVN trunk (git tag $PLUGINVERSION already exists): "
    read -r COMMITMSG
else
    echo "Git version does not exist. Creating new tag..."
    cd "$GITPATH"
    echo -n "Enter a commit message for this new version: "
    read -r COMMITMSG
    git commit -am "$COMMITMSG"

    echo "Tagging new version in git"
    git tag -a "$PLUGINVERSION" -m "Tagging version $PLUGINVERSION"
fi

echo "Pushing latest commit to origin, with tags"
git push origin "$MAIN_BRANCH"
git push origin "$MAIN_BRANCH" --tags

echo
echo "Ensuring we're on the $MAIN_BRANCH branch"
git checkout "$MAIN_BRANCH"

echo "Creating local copy of SVN repo ..."
svn co "$SVNURL" "$SVNPATH" || exit 1

echo "Ensuring the tags directory exists..."
if [ ! -d "$SVNPATH/tags" ]; then
    mkdir "$SVNPATH/tags"
    svn add "$SVNPATH/tags"
fi

echo "Exporting the HEAD of $MAIN_BRANCH from git to the trunk of SVN"
find "$SVNPATH/trunk" -maxdepth 1 -mindepth 1 -not -name ".svn" -exec rm -rf {} +
git checkout-index -a -f --prefix="$SVNPATH/trunk/" || exit 1

echo "Removing dev dependencies from nested vendors..."
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperpress-core/tests"
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperpress-core/coverage-html"
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperpress-core/.ci"
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperpress-core/scripts"
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperfields/tests"
rm -rf "$SVNPATH/trunk/vendor/estebanforge/hyperblocks/tests"

# Stub nested vendor/ dirs — classes are already registered by the top-level autoloader.
# Keeps the autoload.php path valid while eliminating duplicate/redundant packages.
STUB_AUTOLOAD="<?php // Stub: classes loaded by parent autoloader"
for pkg in hyperfields hyperpress-core hyperblocks; do
    nested="$SVNPATH/trunk/vendor/estebanforge/$pkg/vendor"
    if [ -d "$nested" ]; then
        find "$nested" -mindepth 1 -maxdepth 1 ! -name 'autoload.php' -exec rm -rf {} +
        echo "$STUB_AUTOLOAD" > "$nested/autoload.php"
    fi
done

echo "Ignoring github specific & deployment script"
svn propset svn:ignore "deploy.sh
.claude
.config-tcattd
.wp-org-assets
sftp-config.json
composer.json
composer.lock
scoper.inc.php
AGENTS.md
CLAUDE.md
CODE_OF_CONDUCT.md
CONTRIBUTING.md
FAQ.md
README.md
SECURITY.md
TODO.md
.php-cs-fixer.cache
.php-cs-fixer.dist.php
.editorconfig
.git
.gitignore
tests
vendor/estebanforge/hyperfields/tests
vendor/estebanforge/hyperpress-core/tests" "$SVNPATH/trunk/"

echo "Moving .wp-org-assets"
mkdir -p "$SVNPATH/assets/"
if [ -d "$SVNPATH/trunk/.wp-org-assets" ]; then
    mv "$SVNPATH/trunk/.wp-org-assets/"* "$SVNPATH/assets/"
    svn add "$SVNPATH/assets/" --force
    svn delete --force "$SVNPATH/trunk/.wp-org-assets"
fi

# Remove deployment script if it exists (it shouldn't due to svn:ignore)
if [ -f "$SVNPATH/trunk/deploy.sh" ]; then
    svn delete --force "$SVNPATH/trunk/deploy.sh"
fi

echo "Changing directory to SVN and committing to trunk"
cd "$SVNPATH/trunk/"
# Delete missing files
svn status | grep "^\!" | awk '{print $2}' | xargs -r svn delete || true
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs -r svn add || true
echo "committing to trunk"
svn commit --username="$SVNUSER" -m "$COMMITMSG" || exit 1

echo "Updating WP plugin repo assets & committing"
cd "$SVNPATH/assets/"
svn commit --username="$SVNUSER" -m "Updating wp-repo-assets" || exit 1

# Only create SVN tag if not deploying to trunk
if [ "$READMEVERSION" != "trunk" ]; then
    echo "Creating new SVN tag & committing it"
    cd "$SVNPATH"

    # Check if tag already exists in remote SVN and remove it
    if svn ls "$SVNURL/tags/$PLUGINVERSION" >/dev/null 2>&1; then
        echo "Tag $PLUGINVERSION already exists in SVN. Removing and recreating..."
        svn delete --force "$SVNURL/tags/$PLUGINVERSION" -m "Removing existing tag for recreation"
        svn update --accept theirs-full  # Update local working copy
    fi

    # Remove any existing local tag directory
    if [ -d "tags/$PLUGINVERSION" ]; then
        echo "Removing existing local tag directory..."
        svn revert -R "tags/$PLUGINVERSION" 2>/dev/null || true
        rm -rf "tags/$PLUGINVERSION"
    fi

    # Update the working copy to ensure clean state
    svn update

    # Create the new tag directly from remote URL to avoid local conflicts
    echo "Creating tag from remote trunk..."
    svn copy "$SVNURL/trunk" "$SVNURL/tags/$PLUGINVERSION" -m "Tagging version $PLUGINVERSION" || exit 1

    echo "Tag created successfully in remote SVN"
else
    echo "Stable tag is trunk - skipping SVN tag creation"
fi

echo "Removing temporary directory $SVNPATH"
rm -fr "${SVNPATH:?}/"

echo "*** END ***"

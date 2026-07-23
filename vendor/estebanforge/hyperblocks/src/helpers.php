<?php

declare(strict_types=1);

/**
 * HyperBlocks - Helper Functions.
 *
 * This file provides convenience functions for working with HyperBlocks.
 */

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Registry;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Create a new Block instance.
 *
 * @param string $title The block title.
 * @return Block
 */
function hyperblocks_block(string $title): Block
{
    return Block::make($title);
}

/**
 * Create a new Field instance.
 *
 * @param string $type  The field type.
 * @param string $name  The field name.
 * @param string $label The field label.
 * @return Field
 */
function hyperblocks_field(string $type, string $name, string $label): Field
{
    return Field::make($type, $name, $label);
}

/**
 * Create a new FieldGroup instance.
 *
 * @param string $name The field group name.
 * @param string $id   The field group ID.
 * @return FieldGroup
 */
function hyperblocks_field_group(string $name, string $id): FieldGroup
{
    return FieldGroup::make($name, $id);
}

/**
 * Get the Registry instance.
 *
 * @return Registry
 */
function hyperblocks_registry(): Registry
{
    return Registry::getInstance();
}

/**
 * Register a block.
 *
 * @param Block $block The block to register.
 * @return void
 */
function hyperblocks_register_block(Block $block): void
{
    Registry::getInstance()->registerFluentBlock($block);
}

/**
 * Register a field group.
 *
 * @param FieldGroup $group The field group to register.
 * @return void
 */
function hyperblocks_register_field_group(FieldGroup $group): void
{
    Registry::getInstance()->registerFieldGroup($group);
}

/**
 * Register a block discovery path.
 *
 * The path is both scanned for block definitions and added to the
 * template-validation allowlist. To register a path for template
 * validation only (never scanned), use hyperblocks_register_template_path().
 *
 * @param string $path The path to register.
 * @return void
 */
function hyperblocks_register_path(string $path): void
{
    HyperBlocks\Config::registerBlockPath($path);
}

/**
 * Register a template-only path.
 *
 * The path is added to the template-validation allowlist but is never
 * scanned for block definitions. Use when a directory holds render
 * templates that must resolve via Block::setRenderTemplateFile() but
 * must not be auto-executed as block definitions on init.
 *
 * @param string $path The path to register.
 * @return void
 */
function hyperblocks_register_template_path(string $path): void
{
    HyperBlocks\Config::registerTemplatePath($path);
}

/**
 * Get a configuration value.
 *
 * @param string $key     The configuration key.
 * @param mixed  $default The default value.
 * @return mixed
 */
function hyperblocks_config(string $key, mixed $default = null): mixed
{
    return HyperBlocks\Config::get($key, $default);
}

/**
 * Render a block template.
 *
 * @param string $template   The template path or string.
 * @param array  $attributes The block attributes.
 * @return string The rendered HTML.
 */
function hyperblocks_render(string $template, array $attributes = []): string
{
    $renderer = new HyperBlocks\Renderer();

    return $renderer->render($template, $attributes);
}

/**
 * Check if a block is registered.
 *
 * @param string $blockName The block name.
 * @return bool
 */
function hyperblocks_has_block(string $blockName): bool
{
    return Registry::getInstance()->hasFluentBlock($blockName);
}

/**
 * Get a registered block.
 *
 * @param string $blockName The block name.
 * @return Block|null
 */
function hyperblocks_get_block(string $blockName): ?Block
{
    return Registry::getInstance()->getFluentBlock($blockName);
}

/**
 * Resolve a filesystem path to its public URL by matching it against the
 * web-accessible WordPress content roots.
 *
 * HyperBlocks' editor assets (editor.js, editor.css) live inside the library
 * directory and must be served over HTTP for the Gutenberg inserter to
 * register fluent blocks client-side. WordPress' plugins_url($path, $file)
 * only resolves correctly when $file sits directly under WP_PLUGIN_DIR: it
 * calls plugin_basename() which strips that one prefix and nothing else. When
 * HyperBlocks is vendored into a non-plugin directory — notably a Bedrock
 * application's root composer vendor (public_html/src/vendor), which lives
 * outside both WP_PLUGIN_DIR and the web document root — plugin_basename()
 * returns the full path and plugins_url() emits a URL like
 * https://host/app/plugins/home/.../src/vendor/... that 404s. The editor
 * script then never loads, wp.blocks.registerBlockType() never fires, and
 * every fluent block is silently invisible in the inserter.
 *
 * This resolver walks every web-accessible content root (plugins, mu-plugins,
 * content, active theme template + stylesheet dirs) and returns the first
 * containing root's URL plus the relative remainder of $path. It returns an
 * empty string when $path is under no web-accessible root — the documented
 * signal that the library is loaded from a location HTTP cannot reach, so
 * callers can bail and log instead of enqueuing a broken URL.
 *
 * @param string $path Absolute filesystem path (file or directory).
 * @return string Public URL with no trailing slash, or '' if not resolvable.
 */
function hyperblocks_resolve_content_url(string $path): string
{
    // Delegate to the canonical HyperFields implementation when present, so a
    // stack that ships all three libraries (HyperFields, HyperBlocks,
    // HyperPress-Core) runs one resolver. The local implementation below is
    // the fallback for standalone HyperBlocks installs without HyperFields.
    if (function_exists('hyperfields_resolve_content_url')) {
        return hyperfields_resolve_content_url($path);
    }

    $normalize = static function (string $p): string {
        $p = str_replace('\\', '/', $p);

        return function_exists('wp_normalize_path') ? wp_normalize_path($p) : $p;
    };

    // realpath() so symlinked content roots match a realpath'd script path:
    // bootstrap.php feeds us dirname(realpath(__FILE__)), while WP_PLUGIN_DIR
    // et al. are usually the raw (possibly symlinked) configured path. Without
    // this, a plugin dir symlinked onto a dev stack (wp-env, Lando, Bedrock)
    // would not prefix-match and the resolver would wrongly return ''. realpath
    // can return false for non-existent paths; fall back to the normalized raw.
    $canonicalize = static function (string $p) use ($normalize): string {
        $real = realpath($p);
        if ($real !== false) {
            return $normalize($real);
        }

        return $normalize($p);
    };

    $normalized = $canonicalize($path);

    // [directory, url] pairs for every web-accessible WP content root. Order
    // matters only for tie-breaking; prefixes are matched on a directory
    // boundary so '/wp-content' never matches '/wp-content-other'.
    $candidates = [];

    $pairs = [
        ['WP_PLUGIN_DIR', 'WP_PLUGIN_URL'],
        ['WPMU_PLUGIN_DIR', 'WPMU_PLUGIN_URL'],
        ['WP_CONTENT_DIR', 'WP_CONTENT_URL'],
    ];
    foreach ($pairs as [$dirConst, $urlConst]) {
        if (defined($dirConst) && defined($urlConst)) {
            $dir = (string) constant($dirConst);
            $url = (string) constant($urlConst);
            if ($dir !== '' && $url !== '') {
                $candidates[] = [$dir, $url];
            }
        }
    }

    // Active theme template + stylesheet dirs are web-accessible too.
    foreach (
        [
            ['get_template_directory', 'get_template_directory_uri'],
            ['get_stylesheet_directory', 'get_stylesheet_directory_uri'],
        ] as [$dirFn, $urlFn]
    ) {
        if (function_exists($dirFn) && function_exists($urlFn)) {
            $dir = (string) $dirFn();
            $url = (string) $urlFn();
            if ($dir !== '' && $url !== '') {
                $candidates[] = [$dir, $url];
            }
        }
    }

    foreach ($candidates as [$dir, $url]) {
        $ndir = $canonicalize($dir);
        $nurl = rtrim($url, '/\\');

        if ($normalized === $ndir) {
            return $nurl;
        }

        if (str_starts_with($normalized, $ndir . '/')) {
            return $nurl . '/' . substr($normalized, strlen($ndir) + 1);
        }
    }

    return '';
}

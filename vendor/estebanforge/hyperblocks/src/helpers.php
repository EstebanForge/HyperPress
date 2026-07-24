<?php

declare(strict_types=1);

/**
 * HyperBlocks helper functions.
 *
 * Procedural API for working with HyperBlocks. Each function is declared in
 * the global namespace and guarded with function_exists so a host project
 * can define its own override without colliding. Mirrors HyperFields.
 */

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Config;
use HyperBlocks\Renderer;
use HyperBlocks\Registry;

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

if (!function_exists('hb_block')) {
    /**
     * Create a new Block instance.
     *
     * @param string $title The block title.
     * @return Block
     */
    function hb_block(string $title): Block
    {
        return Block::make($title);
    }
}

if (!function_exists('hb_field')) {
    /**
     * Create a new Field instance.
     *
     * @param string $type  The field type.
     * @param string $name  The field name.
     * @param string $label The field label.
     * @return Field
     */
    function hb_field(string $type, string $name, string $label): Field
    {
        return Field::make($type, $name, $label);
    }
}

if (!function_exists('hb_field_group')) {
    /**
     * Create a new FieldGroup instance.
     *
     * @param string $name The field group name.
     * @param string $id   The field group ID.
     * @return FieldGroup
     */
    function hb_field_group(string $name, string $id): FieldGroup
    {
        return FieldGroup::make($name, $id);
    }
}

if (!function_exists('hb_registry')) {
    /**
     * Get the Registry instance.
     *
     * @return Registry
     */
    function hb_registry(): Registry
    {
        return Registry::getInstance();
    }
}

if (!function_exists('hb_register_block')) {
    /**
     * Register a block.
     *
     * @param Block $block The block to register.
     * @return void
     */
    function hb_register_block(Block $block): void
    {
        Registry::getInstance()->registerFluentBlock($block);
    }
}

if (!function_exists('hb_register_field_group')) {
    /**
     * Register a field group.
     *
     * @param FieldGroup $group The field group to register.
     * @return void
     */
    function hb_register_field_group(FieldGroup $group): void
    {
        Registry::getInstance()->registerFieldGroup($group);
    }
}

if (!function_exists('hb_register_path')) {
    /**
     * Register a block discovery path.
     *
     * The path is both scanned for block definitions and added to the
     * template-validation allowlist. To register a path for template
     * validation only (never scanned), use hb_register_template_path().
     *
     * @param string $path The path to register.
     * @return void
     */
    function hb_register_path(string $path): void
    {
        Config::registerBlockPath($path);
    }
}

if (!function_exists('hb_register_template_path')) {
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
    function hb_register_template_path(string $path): void
    {
        Config::registerTemplatePath($path);
    }
}

if (!function_exists('hb_config')) {
    /**
     * Get a configuration value.
     *
     * @param string $key     The configuration key.
     * @param mixed  $default The default value.
     * @return mixed
     */
    function hb_config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('hb_render')) {
    /**
     * Render a block template.
     *
     * @param string $template   The template path or string.
     * @param array  $attributes The block attributes.
     * @return string The rendered HTML.
     */
    function hb_render(string $template, array $attributes = []): string
    {
        $renderer = new Renderer();

        return $renderer->render($template, $attributes);
    }
}

if (!function_exists('hb_has_block')) {
    /**
     * Check if a block is registered.
     *
     * @param string $blockName The block name.
     * @return bool
     */
    function hb_has_block(string $blockName): bool
    {
        return Registry::getInstance()->hasFluentBlock($blockName);
    }
}

if (!function_exists('hb_get_block')) {
    /**
     * Get a registered block.
     *
     * @param string $blockName The block name.
     * @return Block|null
     */
    function hb_get_block(string $blockName): ?Block
    {
        return Registry::getInstance()->getFluentBlock($blockName);
    }
}

if (!function_exists('hb_resolve_content_url')) {
    /**
     * Resolve a filesystem path to its public URL by matching it against the
     * web-accessible WordPress content roots.
     *
     * Thin procedural wrapper exposed so callers can resolve asset URLs without
     * touching HyperFields directly. Delegates to the canonical HyperFields
     * implementation (hyperfields_resolve_content_url) when present, so a stack
     * that ships both libraries runs one resolver. The local fallback below
     * covers standalone HyperBlocks installs without HyperFields.
     *
     * WordPress' plugins_url($path, $file) resolves correctly only when $file
     * sits directly under WP_PLUGIN_DIR: it calls plugin_basename(), which
     * strips that one prefix and nothing else. When HyperBlocks is vendored
     * into a non-plugin directory (notably a Bedrock application's root
     * composer vendor, outside both WP_PLUGIN_DIR and the web document root),
     * plugin_basename() returns the full path and plugins_url() emits a URL
     * that 404s. The editor script then never loads and every fluent block is
     * silently invisible in the inserter.
     *
     * This resolver walks every web-accessible content root (plugins,
     * mu-plugins, content, active theme template + stylesheet dirs) and
     * returns the first containing root's URL plus the relative remainder of
     * $path. It returns an empty string when $path is under no web-accessible
     * root, the signal that the library is loaded from a location HTTP cannot
     * reach, so callers can bail and log instead of enqueuing a broken URL.
     *
     * @param string $path Absolute filesystem path (file or directory).
     * @return string Public URL with no trailing slash, or '' if not resolvable.
     */
    function hb_resolve_content_url(string $path): string
    {
        // Delegate to the canonical HyperFields implementation when present.
        // Resolves via global namespace fallback: HyperFields declares its own
        // namespaced copy, so the global lookup here is only satisfied when a
        // consumer explicitly exposes the global bridge.
        if (function_exists('\\hyperfields_resolve_content_url')) {
            return \hyperfields_resolve_content_url($path);
        }

        $normalize = static function (string $p): string {
            $p = str_replace('\\', '/', $p);

            return function_exists('wp_normalize_path') ? wp_normalize_path($p) : $p;
        };

        // realpath() so symlinked content roots match a realpath'd script path.
        // realpath can return false for non-existent paths; fall back to the
        // normalized raw path.
        $canonicalize = static function (string $p) use ($normalize): string {
            $real = realpath($p);
            if ($real !== false) {
                return $normalize($real);
            }

            return $normalize($p);
        };

        $normalized = $canonicalize($path);

        // [directory, url] pairs for every web-accessible WP content root.
        // Prefix matching is anchored to a directory boundary so '/wp-content'
        // never matches '/wp-content-other'.
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
}

<?php

declare(strict_types=1);

/**
 * WordPress Bootstrap for HyperPress-Core.
 *
 * Carries the prefix-safe first-to-boot guard and sets Config runtime
 * identity. The dev-env bootstrap.php delegates here at after_setup_theme.
 *
 * @since 1.4.3
 */

namespace HyperPress;

use HyperPress\Admin\Activation;

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * Bootstrap class for HyperPress-Core.
 *
 * Replaces the former global HYPERPRESS_BOOTSTRAP_LOADED guard and the
 * hyperpress_api_candidates / hyperpress_select_and_load_latest /
 * hyperpress_run_initialization_logic multi-instance election machinery with a
 * single namespace-scoped first-to-boot guard, mirroring HyperFields and
 * HyperBlocks.
 */
final class Bootstrap
{
    /**
     * Initialize HyperPress-Core for this copy.
     *
     * Idempotent: the namespace-scoped LOADED constant elects the first copy
     * to reach init(), and Config::isInitialized() short-circuits re-entry.
     *
     * @param array $args Optional overrides: plugin_file, plugin_url.
     * @return void
     */
    public static function init(array $args = []): void
    {
        // Cross-copy election guard (Carbon Fields pattern). The first copy of
        // HyperPress-Core to reach init() claims a namespace-scoped constant
        // and wins; every later copy bails before bootstrapping, so two
        // plugins shipping HyperPress-Core do not double-init (re-register
        // hooks, conflict on Config state) and do not fatal. Built with
        // __NAMESPACE__ so it is prefix-safe: unprefixed copies share
        // HyperPress\LOADED and elect one winner, while a namespace-prefixed
        // copy lives under a different namespace (different constant) and
        // boots independently. First-to-boot wins, not newest; prefix if you
        // need version determinism across divergent copies.
        if (defined(__NAMESPACE__ . '\\LOADED')) {
            return;
        }
        define(__NAMESPACE__ . '\\LOADED', __DIR__);

        if (Config::isInitialized()) {
            return;
        }

        // Resolve this copy's entry file and base directory. Plugin mode is
        // active when one of the known WordPress entry files exists; otherwise
        // we run as a Composer library and treat bootstrap.php's directory as
        // the library root.
        $plugin_file = self::resolvePluginFile($args['plugin_file'] ?? null);
        $is_library_mode = !in_array(basename((string) $plugin_file), ['hyperpress.php', 'api-for-htmx.php'], true);

        $base_dir = dirname($plugin_file);
        $basename = $is_library_mode ? 'hyperpress/bootstrap.php' : plugin_basename($plugin_file);

        // Runtime identity (prefix-safe). Mirrors HyperFields/ HyperBlocks
        // Config: per-copy paths/URL so a prefixed copy is fully isolated.
        if ($is_library_mode) {
            $plugin_url = isset($args['plugin_url'])
                ? (string) $args['plugin_url']
                : self::resolvePluginUrl($base_dir);
        } else {
            $plugin_url = isset($args['plugin_url'])
                ? (string) $args['plugin_url']
                : plugin_dir_url($plugin_file);
        }

        Config::markInitialized();
        Config::$abspath = trailingslashit($base_dir);
        Config::$pluginFile = $plugin_file;
        Config::$pluginUrl = $plugin_url !== '' ? trailingslashit($plugin_url) : '';
        Config::$basename = $basename;

        // Load the procedural helper API (hp_*) and the deprecated hm_*/hxwp_*
        // aliases. Loaded here (after ABSPATH is available) rather than via
        // Composer autoload.files, so the direct-access guard in helpers.php
        // does not bail when a consumer's autoloader runs before WordPress
        // loads. Mirrors HyperFields and HyperBlocks.
        $helpers = __DIR__ . '/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }
        $deprecated = __DIR__ . '/deprecated.php';
        if (is_file($deprecated)) {
            require_once $deprecated;
        }

        // Skip the heavy WordPress wiring for background and API contexts;
        // the helpers/constants above are still available for them.
        if ((defined('DOING_CRON') && DOING_CRON === true)
            || (defined('DOING_AJAX') && DOING_AJAX === true)
            || (defined('REST_REQUEST') && REST_REQUEST === true)
            || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST === true)
            || (defined('WP_CLI') && WP_CLI === true)
        ) {
            return;
        }

        // Activation/deactivation hooks only make sense in plugin mode.
        if (!$is_library_mode) {
            register_activation_hook($plugin_file, [Activation::class, 'activate']);
            register_deactivation_hook($plugin_file, [Activation::class, 'deactivate']);
        }

        if (class_exists(Main::class)) {
            $router = new Router();
            $render = new Render();
            $config = new Config();
            $compatibility = new Compatibility();
            $theme_support = new Theme();
            $hyperpress_main = new Main(
                $router,
                $render,
                $config,
                $compatibility,
                $theme_support
            );
            $hyperpress_main->run();

            // Initialize the blocks system
            if (class_exists(Blocks\Registry::class)) {
                $blocksRegistry = Blocks\Registry::getInstance();
                $blocksRegistry->init();

                // Initialize the blocks REST API
                if (class_exists(Blocks\RestApi::class)) {
                    $blocksRestApi = Blocks\RestApi::getInstance();
                    $blocksRestApi->init();
                }
            }

            // Demo blocks are automatically discovered by the Registry auto-discovery system
        }
    }

    /**
     * Resolve this copy's entry file path.
     *
     * Prefers an explicit override, then the known plugin entry files, then
     * falls back to this library's bootstrap.php (library mode).
     *
     * @param string|null $override Explicit plugin file path.
     * @return string
     */
    private static function resolvePluginFile(?string $override): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        $library_root = dirname(__DIR__);
        foreach (['hyperpress.php', 'api-for-htmx.php'] as $entry) {
            $candidate = $library_root . '/' . $entry;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Library mode: the bootstrap file itself is the anchor.
        return $library_root . '/bootstrap.php';
    }

    /**
     * Resolve the library root URL against the web-accessible WordPress
     * content roots.
     *
     * Delegates to the canonical HyperFields resolver (HyperPress-Core
     * vendors HyperFields, so the procedural helper is present) so a stack
     * shipping both libraries runs one resolver. Returns '' when the
     * directory is not under any web-accessible root (e.g. a Bedrock root
     * composer vendor outside the web document root); callers that need the
     * assets must then bail. plugins_url()/plugin_dir_url() only handle files
     * directly under WP_PLUGIN_DIR and would produce a broken URL in that
     * topology.
     *
     * @param string $base_dir Library root directory (no trailing slash required).
     * @return string URL with no trailing slash, or '' when not resolvable.
     */
    private static function resolvePluginUrl(string $base_dir): string
    {
        if (function_exists('hyperfields_resolve_content_url')) {
            return hyperfields_resolve_content_url($base_dir);
        }

        // Fallback when HyperFields' resolver is not loaded: best-effort via
        // plugins_url(), correct for plugin-mode-style layout only.
        if (function_exists('plugins_url')) {
            $resolved = plugins_url('', $base_dir . '/bootstrap.php');

            return $resolved !== '' ? $resolved : '';
        }

        return '';
    }
}

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

        // G1: markInitialized runs before define(LOADED) (load-bearing invariant;
        // see HyperFields\LibraryBootstrap::init). Moved so a mid-init abort
        // cannot claim LOADED while Config stays uninitialized.
        Config::markInitialized();
        define(__NAMESPACE__ . '\\LOADED', __DIR__);
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

        // Abilities registration must run BEFORE the background/API guard
        // below: the /wp-abilities/v1 controller serves abilities during
        // REST requests, so a no-op under REST_REQUEST would hide them from
        // exactly the consumers they exist for. The WP_DEBUG log observer
        // wires independently of the kill switch: it is a generic logger for
        // every ability on the site, not part of HyperPress registration.
        if (class_exists(Abilities\AbilityRegistrar::class)) {
            Abilities\AbilityRegistrar::init();
        } elseif (defined('WP_DEBUG') && WP_DEBUG) {
            // The class ships in this same library, so a miss here means a
            // stale vendor copy (path repo, symlink:false). Never silent.
            error_log('HyperPress: Abilities\\AbilityRegistrar not found; refresh the vendored copy with `composer reinstall estebanforge/hyperpress-core`.');
        }
        if (class_exists(Abilities\LogObserver::class)) {
            Abilities\LogObserver::init();
        }

        // Activation/deactivation hooks only make sense in plugin mode.
        if (!$is_library_mode) {
            register_activation_hook($plugin_file, [Activation::class, 'activate']);
            register_deactivation_hook($plugin_file, [Activation::class, 'deactivate']);
        }

        // Main wiring runs in EVERY context (admin, REST, cron, WP-CLI). The
        // former early-return here left REST/CLI blind to the options-page
        // metadata that the Abilities layer resolves pages from; scheduled
        // hooks (enqueue, menus, template_redirect) simply never fire
        // off-context, so booting Main everywhere is inert but complete.

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
        // Prefer the canonical HyperFields resolver as a class method: it is
        // available the moment the class is autoloaded, independent of whether
        // HyperFields' own init() has run yet (init order between the two
        // libraries is not guaranteed). Returns '' for directories under no
        // web-accessible WP content root (e.g. a Bedrock root composer vendor).
        if (class_exists(\HyperFields\LibraryBootstrap::class)) {
            return \HyperFields\LibraryBootstrap::resolveContentUrl($base_dir);
        }

        // Procedural helper equivalent, when only that is loaded.
        if (function_exists('hyperfields_resolve_content_url')) {
            return hyperfields_resolve_content_url($base_dir);
        }

        // No reliable resolver available (e.g. a Mozart-prefixed HyperFields
        // whose class/helper are namespaced away). Do NOT fall back to
        // plugins_url(): it returns a non-empty URL for any path and would
        // mask the not-web-reachable case, enqueuing a 404ing URL. Return ''
        // so the caller degrades gracefully (empty Config::$pluginUrl).
        return '';
    }
}

<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * Library bootstrap helper for Composer-based usage.
 */
final class LibraryBootstrap
{
    /**
     * Initialize HyperFields when used as a library.
     *
     * @param array $args Optional overrides: plugin_file, base_dir, plugin_url, version.
     * @return void
     */
    public static function init(array $args = []): void
    {
        // Cross-copy election guard (Carbon Fields pattern). The first copy of
        // HyperFields to reach init() claims a namespaced constant and wins;
        // every later copy bails before bootstrapping, so two plugins shipping
        // HyperFields do not double-init (re-register hooks, conflict on Config
        // state) and do not fatal. The constant is built with __NAMESPACE__ so
        // it is prefix-safe: unprefixed copies share HyperFields\LOADED and
        // elect one winner, while a namespace-prefixed copy lives under a
        // different namespace (different constant) and boots independently with
        // real isolation. First-to-boot wins, not newest; prefix if you need
        // version determinism across divergent copies.
        if (defined(__NAMESPACE__ . '\LOADED')) {
            return;
        }

        if (Config::isInitialized()) {
            return;
        }

        $base_dir = isset($args['base_dir']) ? (string) $args['base_dir'] : trailingslashit(dirname(__DIR__));
        $plugin_file = isset($args['plugin_file']) ? (string) $args['plugin_file'] : $base_dir . 'bootstrap.php';
        $plugin_url = isset($args['plugin_url']) ? (string) $args['plugin_url'] : self::resolve_plugin_url($base_dir, $plugin_file);

        // INVARIANT (load-bearing): Config::markInitialized() must run BEFORE the
        // Registry::getInstance() call below and BEFORE define(LOADED). Layer 2's
        // ensureInitialized() guards Registry::getInstance() and bails on
        // Config::isInitialized(); if markInitialized ran after getInstance,
        // ensureInitialized would recurse. define(LOADED) is moved here (G1) so a
        // mid-init abort cannot leave LOADED claimed while Config stays
        // uninitialized (which would make every later ensureInitialized a silent
        // no-op). See docs/library-zero-config-bootstrap.md.
        Config::markInitialized();
        define(__NAMESPACE__ . '\LOADED', __DIR__);
        Config::$abspath = trailingslashit($base_dir);
        Config::$pluginFile = $plugin_file;
        Config::$pluginUrl = $plugin_url;

        // Note: HYPERPRESS_PLUGIN_URL is intentionally NOT defined here as a
        // fallback. HyperPress-Core owns that constant and resolves it from
        // its own base directory (HyperPress vendors HyperFields, so it may
        // live at a different path). An earlier version of this block copied
        // HYPERFIELDS_PLUGIN_URL verbatim, which silently propagated a broken
        // (404ing) HyperFields URL into HyperPress-Core's frontend asset
        // enqueue. Even resolving independently from $base_dir here would be
        // wrong, because $base_dir is HyperFields' dir, not HyperPress-Core's.
        // Let HyperPress-Core's own bootstrap define it.

        $helpers = __DIR__ . '/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }

        if (class_exists(Assets::class)) {
            (new Assets())->init();
        }

        if (class_exists(TemplateLoader::class)) {
            TemplateLoader::init();
        }

        if (class_exists(Transfer\AuditLogger::class)) {
            Transfer\AuditLogger::init();
        }

        // Automatic cache (transients + OPcache) invalidation on HyperFields
        // saves. Default on; disable with the `hyperfields/cache/auto_invalidate`
        // filter. Registered last so a save mid-init still flushes.
        if (class_exists(CacheInvalidator::class)) {
            CacheInvalidator::init();
        }

        // Registry runs LAST. With Layer 3 Rule B a late ensureInitialized()
        // (did_action('init') > 0) fires registerAll synchronously, which runs
        // do_action('hyperfields/register') and any consumer callbacks on it.
        // Running Registry after Assets/TemplateLoader/AuditLogger/CacheInvalidator
        // are wired means those subsystems are available to such callbacks.
        // markInitialized() above keeps the Registry::getInstance() ->
        // ensureInitialized() path recursion-safe.
        if (class_exists(Registry::class)) {
            Registry::getInstance()->init();
        }
    }

    /**
     * Late-use safety net: bring the library up if a consumer touched an
     * early-reachable facade (HyperFields::registerOptionsPage, a container
     * factory, or Registry::getInstance) before after_setup_theme fired.
     *
     * Emits _doing_it_wrong() when the init hook has already fired, because
     * Registry registrations added this late will be incomplete: fields and
     * options pages registered after init do not appear. Layer 1 makes this a
     * rare fallback, not the primary init path.
     *
     * @return void
     */
    public static function ensureInitialized(): void
    {
        if (Config::isInitialized()) {
            return;
        }

        if (function_exists('did_action') && did_action('init') > 0) {
            if (function_exists('_doing_it_wrong')) {
                _doing_it_wrong(
                    'HyperFields\LibraryBootstrap::ensureInitialized',
                    'HyperFields initialized after the "init" hook already fired. '
                    . 'Registry field/options-page registration will be incomplete. '
                    . 'Ensure the library boots at after_setup_theme, not on first facade use.',
                    Config::VERSION
                );
            } else {
                error_log('HyperFields: late initialization after init; Registry registrations may be incomplete.');
            }
        }

        self::init();
    }

    /**
     * Resolve a filesystem path to its public URL by matching it against the
     * web-accessible WordPress content roots.
     *
     * WordPress' plugins_url($path, $file) resolves correctly only when $file
     * sits directly under WP_PLUGIN_DIR: it calls plugin_basename(), which
     * strips that one prefix and nothing else. When a library is vendored into
     * a non-plugin directory — most notably a Bedrock application's root
     * composer vendor (public_html/src/vendor), outside both WP_PLUGIN_DIR
     * and the web document root — plugin_basename() returns the full path
     * with its leading slash stripped and plugins_url() emits a URL like
     * https://host/app/plugins/home/.../src/vendor/... that 404s. The admin/
     * field assets enqueued from that URL never load (broken HyperFields
     * options pages, missing multiselect JS), and for HyperBlocks the editor
     * script fails to register blocks client-side so fluent blocks vanish
     * from the Gutenberg inserter.
     *
     * This resolver walks every web-accessible content root (plugins,
     * mu-plugins, content, active theme template + stylesheet dirs) and
     * returns the first containing root's URL plus the relative remainder of
     * $path. It returns an empty string when $path is under no web-accessible
     * root, which is the signal that the library is loaded from a location
     * HTTP cannot reach so callers can bail and log instead of enqueuing a
     * broken URL.
     *
     * @param string $path Absolute filesystem path (file or directory).
     * @return string Public URL with no trailing slash, or '' if not resolvable.
     */
    public static function resolveContentUrl(string $path): string
    {
        $normalize = static function (string $p): string {
            return function_exists('wp_normalize_path') ? wp_normalize_path($p) : str_replace('\\', '/', $p);
        };

        // realpath() so symlinked content roots match a realpath'd script path:
        // bootstrap files typically feed us dirname(realpath(__FILE__)), while
        // WP_PLUGIN_DIR et al. are the raw (possibly symlinked) configured
        // path. Without this, a plugin dir symlinked onto a dev stack would
        // not prefix-match and the resolver would wrongly return ''.
        $canonicalize = static function (string $p) use ($normalize): string {
            $real = realpath($p);

            return $real !== false ? $normalize($real) : $normalize($p);
        };

        $normalized = $canonicalize($path);

        // [directory, url] pairs for every web-accessible WP content root.
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

    /**
     * Resolve the trailingslashed base URL for the library's own assets.
     *
     * Single source of truth for asset enqueues (TemplateLoader,
     * Assets::enqueueScripts, and future callers). Resolution order:
     *
     *   1. Config::$pluginUrl    — set by self::init(); the normal elected path.
     *   2. HYPERPRESS_PLUGIN_URL — HyperPress-Core owns this constant and
     *      resolves it from its own base directory; respected when HyperPress
     *      is the active host.
     *   3. resolveContentUrl(dirname(__DIR__)) — defense-in-depth fallback for
     *      when no bootstrap completed (e.g. bootstrap.php was pulled in by
     *      Composer's files-autoload before add_action() existed, so its
     *      after_setup_theme scheduling silently no-op'd — common when an
     *      early drop-in or mu-plugin triggers a composer autoloader).
     *      Resolves from THIS copy's own root, so the assets that match the
     *      loaded classes are the ones that enqueue.
     *
     * Config is NOT mutated: the cross-copy election/bootstrap state stays
     * untouched, so HyperPress-Core's own bootstrap still wins when present.
     * Returns '' for non-web-accessible locations (e.g. a Bedrock root vendor
     * outside the document root); callers bail then. Mirrors the fallback
     * HyperBlocks' editor-asset enqueue already uses against the same gap.
     *
     * @return string Trailingslashed base URL, or '' when not resolvable.
     */
    public static function resolveAssetBaseUrl(): string
    {
        if (Config::$pluginUrl !== '') {
            return Config::$pluginUrl;
        }

        if (defined('HYPERPRESS_PLUGIN_URL') && HYPERPRESS_PLUGIN_URL !== '') {
            return HYPERPRESS_PLUGIN_URL;
        }

        $resolved = self::resolveContentUrl(dirname(__DIR__));

        return $resolved !== '' ? trailingslashit($resolved) : '';
    }

    /**
     * Resolve plugin URL for library usage.
     *
     * @param string $base_dir HyperFields base directory.
     * @param string $plugin_file Host plugin file path.
     * @return string
     */
    private static function resolve_plugin_url(string $base_dir, string $plugin_file): string
    {
        // Resolve against web-accessible content roots rather than plugins_url(),
        // which only handles files directly under WP_PLUGIN_DIR and 404s when
        // the library is vendored elsewhere (e.g. a Bedrock root composer
        // vendor outside the web document root). Empty when HTTP cannot reach
        // the directory; callers that need the assets must then bail.
        $resolved = self::resolveContentUrl(rtrim($base_dir, '/\\'));

        return $resolved !== '' ? trailingslashit($resolved) : '';
    }
}

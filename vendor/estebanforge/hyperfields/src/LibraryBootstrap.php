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
        if (defined('HYPERFIELDS_INSTANCE_LOADED')) {
            return;
        }

        $base_dir = isset($args['base_dir']) ? (string) $args['base_dir'] : trailingslashit(dirname(__DIR__));
        $plugin_file = isset($args['plugin_file']) ? (string) $args['plugin_file'] : $base_dir . 'bootstrap.php';
        $version = isset($args['version']) ? (string) $args['version'] : self::read_version($base_dir);
        $plugin_url = isset($args['plugin_url']) ? (string) $args['plugin_url'] : self::resolve_plugin_url($base_dir, $plugin_file);

        define('HYPERFIELDS_INSTANCE_LOADED', true);
        define('HYPERFIELDS_VERSION', $version);
        define('HYPERFIELDS_ABSPATH', trailingslashit($base_dir));
        define('HYPERFIELDS_PLUGIN_FILE', $plugin_file);
        if (!defined('HYPERFIELDS_PLUGIN_URL')) {
            define('HYPERFIELDS_PLUGIN_URL', $plugin_url);
        }

        if (!defined('HYPERPRESS_PLUGIN_URL')) {
            define('HYPERPRESS_PLUGIN_URL', HYPERFIELDS_PLUGIN_URL);
        }

        if (!defined('HYPERPRESS_VERSION')) {
            define('HYPERPRESS_VERSION', $version);
        }

        $helpers = HYPERFIELDS_ABSPATH . 'includes/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        $compat = HYPERFIELDS_ABSPATH . 'includes/backward-compatibility.php';
        if (file_exists($compat)) {
            require_once $compat;
        }

        if (class_exists(Registry::class)) {
            Registry::getInstance()->init();
        }

        if (class_exists(Assets::class)) {
            (new Assets())->init();
        }

        if (class_exists(TemplateLoader::class)) {
            TemplateLoader::init();
        }
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
        if (!function_exists('plugins_url') || !function_exists('plugin_dir_path')) {
            return '';
        }

        $plugin_dir = trailingslashit(plugin_dir_path($plugin_file));
        if (strpos($base_dir, $plugin_dir) === 0) {
            $relative = ltrim(str_replace($plugin_dir, '', $base_dir), '/');

            return trailingslashit(plugins_url($relative, $plugin_file));
        }

        return trailingslashit(plugins_url('', $plugin_file));
    }

    /**
     * Read version from the library composer.json.
     *
     * @param string $base_dir HyperFields base directory.
     * @return string
     */
    private static function read_version(string $base_dir): string
    {
        $composer_json = $base_dir . 'composer.json';
        if (!file_exists($composer_json)) {
            return '0.0.0';
        }

        $data = json_decode((string) file_get_contents($composer_json), true);
        if (!is_array($data) || empty($data['version'])) {
            return '0.0.0';
        }

        return (string) $data['version'];
    }
}

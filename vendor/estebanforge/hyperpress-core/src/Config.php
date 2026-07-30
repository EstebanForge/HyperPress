<?php

/**
 * Load plugin Config on frontend.
 *
 * @since   2023-12-04
 */

namespace HyperPress;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * Config Class.
 *
 * Dual responsibility: (1) runtime identity for HyperPress (VERSION, endpoint
 * and template constants, and per-copy filesystem path / URL set once at
 * bootstrap), and (2) feature configuration such as the htmx-config meta tag.
 *
 * The runtime-identity block replaces the former global HYPERPRESS_* define()
 * constants so namespace prefixing isolates these values per consumer: a
 * prefixed copy becomes e.g. ConsumerA\Dependencies\HyperPress\Config, fully
 * distinct from any other consumer's copy. Static values (endpoint slugs,
 * template directory names, extensions) live as class constants; the
 * per-copy filesystem path, URL, and basename live as static properties set
 * once during Bootstrap::init(). Mirrors HyperFields\Config and
 * HyperBlocks\Config.
 */
class Config
{
    /**
     * Semantic version string. Mirrors composer.json (single source of truth
     * for the PHP side; run `composer version-bump` to keep both in sync).
     */
    public const VERSION = '1.5.0';

    /**
     * Primary rewrite endpoint slug (e.g. /wp-html/v1/).
     */
    public const ENDPOINT = 'wp-html';

    /**
     * Legacy rewrite endpoint slug (e.g. /wp-htmx/v1/) for backward
     * compatibility with older api-for-htmx consumers.
     */
    public const LEGACY_ENDPOINT = 'wp-htmx';

    /**
     * Endpoint version segment.
     */
    public const ENDPOINT_VERSION = 'v1';

    /**
     * Theme subdirectory holding HyperPress templates.
     */
    public const TEMPLATE_DIR = 'hypermedia';

    /**
     * Legacy theme subdirectory holding templates.
     */
    public const LEGACY_TEMPLATE_DIR = 'htmx-templates';

    /**
     * Comma-separated primary template file extensions.
     */
    public const TEMPLATE_EXT = '.hp.php,.hm.php,.hb.php';

    /**
     * Comma-separated legacy template file extensions.
     */
    public const LEGACY_TEMPLATE_EXT = '.htmx.php,.hmedia.php';

    /**
     * POST key used when compact input mode is enabled.
     */
    public const COMPACT_INPUT_KEY = 'hyperpress_compact_input';

    /**
     * Absolute path to the library root, with a trailing slash.
     * Empty until initialization runs.
     *
     * @var string
     */
    public static string $abspath = '';

    /**
     * Public content URL for the library root, with a trailing slash.
     * Empty when the directory is not reachable over HTTP so asset enqueues
     * can bail instead of emitting a broken URL.
     *
     * @var string
     */
    public static string $pluginUrl = '';

    /**
     * Absolute path to the bootstrap/entry file.
     * Empty until initialization runs.
     *
     * @var string
     */
    public static string $pluginFile = '';

    /**
     * Plugin basename relative to the plugins directory. 'hyperpress/bootstrap.php'
     * in library mode, or the host plugin basename in plugin mode. Empty until
     * initialization runs.
     *
     * @var string
     */
    public static string $basename = '';

    /**
     * Whether compact-input decoding is enabled for option saves. Replaces the
     * former HYPERPRESS_COMPACT_INPUT constant.
     *
     * @var bool
     */
    public static bool $compactInput = false;

    /**
     * Set to true while a HyperPress endpoint template is rendering, so other
     * code can detect an in-flight hypermedia request. Replaces the former
     * HYPERPRESS_REQUEST constant.
     *
     * @var bool
     */
    public static bool $isEndpointRequest = false;

    /**
     * Whether bootstrap initialization has run for this copy. Guards the
     * init-once contract per prefixed instance.
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Whether bootstrap initialization has run for this copy.
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Mark bootstrap initialization as complete for this copy.
     *
     * @return void
     */
    public static function markInitialized(): void
    {
        self::$initialized = true;
    }

    /**
     * Get plugin options with programmatic configuration support.
     *
     * @since 2.0.0
     * @return array
     */
    private function getOptions(): array
    {
        return OptionsResolver::resolve();
    }

    /**
     * Insert library-specific config meta tags into <head>.
     * Currently supports htmx-config meta tag.
     *
     * @since 2023-12-04
     * @return void
     */
    public function insertConfigMetaTag(): void
    {
        $options = $this->getOptions();
        // Align with Assets.php option key
        $active_library = $options['active_library'] ?? 'datastar'; // Default to datastar if not set

        // Only output htmx-config if HTMX is the active library
        if ('htmx' !== $active_library) {
            return;
        }

        $meta_config_content = $options['hyperpress_meta_config_content'] ?? '';

        if (empty($meta_config_content)) {
            return;
        }

        $meta_config_content = apply_filters('hyperpress/config/config_meta_content', $meta_config_content);

        // Sanitize the content for the meta tag
        $escaped_meta_config_content = esc_attr($meta_config_content);
        $meta_tag = "<meta name=\"htmx-config\" content='{$escaped_meta_config_content}'>";

        // Allow filtering of the entire meta tag
        $meta_tag = apply_filters('hyperpress/config/insert_config_meta_tag', $meta_tag, $escaped_meta_config_content);

        /*
         * Action hook before echoing the htmx-config meta tag.
         *
         * @since 2.0.0
         * @param string $meta_tag The complete HTML meta tag.
         */
        do_action('hyperpress/config/before_echo_config_meta_tag', $meta_tag);

        echo $meta_tag;
    }
}

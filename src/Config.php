<?php

/**
 * Load plugin Config on frontend.
 *
 * @since   2023-12-04
 */

namespace HyperPress;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Config Class.
 * Handles outputting library-specific configurations, like HTMX meta tags.
 */
class Config
{
    /**
     * Get plugin options with programmatic configuration support.
     *
     * @since 2.0.0
     * @return array
     */
    private function getOptions(): array
    {
        $default_options = [
            // Use the same key as Assets to avoid mismatch
            'active_library' => 'htmx',
            'hyperpress_meta_config_content' => '',
        ];

        // Apply filter to allow programmatic configuration
        $default_options = apply_filters('hyperpress/default_options', $default_options);

        return get_option('hyperpress_options', $default_options);
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
        $active_library = $options['active_library'] ?? 'htmx'; // Default to htmx if not set

        // Only output htmx-config if HTMX is the active library
        if ('htmx' !== $active_library) {
            return;
        }

        $meta_config_content = $options['hyperpress_meta_config_content'] ?? '';

        if (empty($meta_config_content)) {
            return;
        }

        $meta_config_content = apply_filters('hyperpress/meta/config_content', $meta_config_content);

        // Sanitize the content for the meta tag
        $escaped_meta_config_content = esc_attr($meta_config_content);
        $meta_tag = "<meta name=\"htmx-config\" content='{$escaped_meta_config_content}'>";

        // Allow filtering of the entire meta tag
        $meta_tag = apply_filters('hyperpress/meta/insert_config_tag', $meta_tag, $escaped_meta_config_content);

        /*
         * Action hook before echoing the htmx-config meta tag.
         *
         * @since 2.0.0
         * @param string $meta_tag The complete HTML meta tag.
         */
        do_action('hyperpress/meta/before_echo_config_tag', $meta_tag);

        echo $meta_tag;
    }
}

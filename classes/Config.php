<?php

/**
 * Load plugin Config on frontend
 *
 * @package HXWP
 * @since   2023-12-04
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Config Class
 */
class Config
{
    /**
     * Insert HTMX config meta tag into <head>
     *
     * @since 2023-12-04
     * @return void
     */
    public function insert_config_meta_tag()
    {
        $meta_config = '';
        $meta_config = apply_filters('hxwp/meta_config', $meta_config);

        if (empty($meta_config)) {
            return;
        }

        $meta_tag = '<meta name="htmx-config" content="' . $meta_config . '">';
        $meta_tag = apply_filters('hxwp/insert_config_meta_tag', $meta_tag);

        do_action('hxwp/insert_config_meta_tag_end', $meta_tag);

        if (empty($meta_tag)) {
            return;
        }

        echo $meta_tag;
    }
}

<?php

/**
 * Handles compatibility with other plugins that may not work well with HTMX.
 *
 * @since   2024-02-21
 */

namespace HMApi;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibility Class.
 */
class Compatibility
{
    /**
     * Runner.
     */
    public function run()
    {
        add_filter('the_content', [$this, 'woocommerce'], PHP_INT_MAX);

        do_action('hxwp/compatibility/run');
    }

    /**
     * Fix WooCommerce compatibility issues.
     * It doesn't like HTMX's boost.
     */
    public function woocommerce($content)
    {
        do_action('hxwp/compatibility/woocommerce');

        if (function_exists('is_woocommerce') && is_woocommerce()) {
            $content = str_ireplace('<div class="woocommerce', '<div hx-boost="false" class="woocommerce', $content);
        }

        return $content;
    }
}

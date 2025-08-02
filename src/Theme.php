<?php

/**
 * Handles theme-related integrations for HyperPress for WordPress.
 *
 * @since   2024-02-27
 */

namespace HMApi;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme support Class.
 * This class is a placeholder for any future theme-specific integrations.
 * The hx-boost functionality previously here is now handled by HMApi\Assets.
 */
class Theme
{
    /**
     * Runner - registers theme-related hooks or actions.
     */
    public function run(): void
    {
        /*
         * Action hook for theme-related integrations.
         *
         * @since 2.0.0
         */
        do_action('hmapi/theme/run');
    }
}

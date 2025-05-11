<?php

/**
 * Activation and Deactivation methods.
 *
 * @since   2023
 */

namespace HXWP\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation and Deactivation Class.
 */
class Activation
{
    /**
     * Activation.
     *
     * @since 2023-11-22
     * @return void
     */
    public static function activate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation.
     *
     * @since 2023-11-22
     * @return void
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

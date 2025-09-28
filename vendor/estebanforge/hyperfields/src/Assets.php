<?php

declare(strict_types=1);

namespace HyperFields;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets manager for the plugin.
 */
class Assets
{
    /**
     * Register hooks.
     */
    public function init(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueueScripts(): void
    {
        wp_enqueue_script(
            'hyperfields-conditional-fields',
            HYPERFIELDS_PLUGIN_URL . 'src/js/conditional-fields.js',
            [],
            HYPERFIELDS_VERSION,
            true
        );
    }
}

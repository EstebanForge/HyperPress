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
        if (!defined('HYPERFIELDS_PLUGIN_URL')) {
            return;
        }

        wp_enqueue_script(
            'hyperfields-conditional-fields',
            HYPERFIELDS_PLUGIN_URL . 'assets/js/conditional-fields.js',
            [],
            HYPERFIELDS_VERSION,
            true
        );

        // Only enqueue enhanced multiselect JS if we have enhanced multiselects on the page
        global $pagenow;
        if ($pagenow === 'options-general.php' || did_action('hyperfields_enhanced_multiselect')) {
            wp_enqueue_script(
                'hyperfields-multiselect-enhanced',
                HYPERFIELDS_PLUGIN_URL . 'assets/js/multiselect-enhanced.js',
                ['jquery'],
                HYPERFIELDS_VERSION,
                true
            );
        }
    }
}

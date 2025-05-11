<?php

/**
 * Load plugin assets.
 *
 * @since   2023-11-22
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets Class.
 */
class Assets
{
    /**
     * Enqueue scripts and styles.
     *
     * @since 2023-11-22
     * @return void
     */
    public function enqueue_scripts()
    {
        do_action('hxwp/init_enqueue_scripts_start');

        $hxwp_options = get_option('hxwp_options');

        // If $hxwp_options false, set some defaults
        if ($hxwp_options == false) {
            $hxwp_options = [
                'load_from_cdn'                        => 0,
                'load_hyperscript'                     => 0,
                'load_alpinejs'                        => 0,
                'set_htmx_hxboost'                     => 0,
                'load_htmx_backend'                    => 0,
                // Official
                'load_extension_sse'                   => 0,
                'load_extension_ws'                    => 0,
                'load_extension_htmx-1-compatible'     => 0,
                'load_extension_preload'               => 0,
                'load_extension_response-targets'      => 0,
                // Community
                'load_extension_ajax-header'           => 0,
                'load_extension_alpine-morph'          => 0,
                'load_extension_class-tools'           => 0,
                'load_extension_client-side-templates' => 0,
                'load_extension_debug'                 => 0,
                //'load_extension_disable-element'       => 0,
                'load_extension_event-header'          => 0,
                'load_extension_include-vals'          => 0,
                'load_extension_json-enc'              => 0,
                'load_extension_loading-states'        => 0,
                'load_extension_morphdom-swap'         => 0,
                'load_extension_multi-swap'            => 0,
                'load_extension_no-cache'              => 0,
                'load_extension_path-deps'             => 0,
                'load_extension_path-params'           => 0,
                'load_extension_remove-me'             => 0,
                'load_extension_restored'              => 0,
            ];
        }

        // CDN?
        $load_from_cdn = $hxwp_options['load_from_cdn'];

        // Load HTMX
        if ($load_from_cdn == 0) {
            $src_htmx = HXWP_PLUGIN_URL . 'assets/js/libs/htmx.min.js';
            $src_ver = filemtime(HXWP_ABSPATH . 'assets/js/libs/htmx.min.js');
        } else {
            $src_htmx = 'https://unpkg.com/htmx.org';
            $src_ver = 'latest';
        }

        wp_enqueue_script(
            'hxwp-htmx',
            $src_htmx,
            [],
            $src_ver,
            true
        );

        // Load Hyperscript
        $load_hyperscript = $hxwp_options['load_hyperscript'];

        if ($load_hyperscript == 1) {
            if ($load_from_cdn == 0) {
                $src_hyperscript = HXWP_PLUGIN_URL . 'assets/js/libs/_hyperscript.min.js';
                $sec_hs_ver = filemtime(HXWP_ABSPATH . 'assets/js/libs/_hyperscript.min.js');
            } else {
                $src_hyperscript = 'https://unpkg.com/hyperscript.org';
                $sec_hs_ver = 'latest';
            }

            wp_enqueue_script('hxwp-hyperscript', $src_hyperscript, ['hxwp-htmx'], $sec_hs_ver, true);
        }

        // Load Alpine.js
        $load_alpinejs = $hxwp_options['load_alpinejs'];

        if ($load_alpinejs == 1) {
            if ($load_from_cdn == 0) {
                $src_alpinejs = HXWP_PLUGIN_URL . 'assets/js/libs/alpinejs.min.js';
                $sec_al_ver = filemtime(HXWP_ABSPATH . 'assets/js/libs/alpinejs.min.js');
            } else {
                $src_alpinejs = 'https://unpkg.com/alpinejs';
                $sec_al_ver = 'latest';
            }

            wp_enqueue_script('hxwp-alpinejs', $src_alpinejs, [], $sec_al_ver, true);
        }

        // Load HTMX extensions
        // get all options that start with "load_extension_"
        foreach ($hxwp_options as $key => $value) {
            if (strpos($key, 'load_extension_') === 0) {
                $ext_script_name = str_replace('load_extension_', '', $key);
                $ext_script_name = str_replace('_', '-', $ext_script_name);

                if ($value == 1) {
                    if ($load_from_cdn == 1) {
                        $src_extension = 'https://unpkg.com/htmx-ext-' . $ext_script_name . '/' . $ext_script_name . '.js';
                        $src_ext_ver = 'latest';
                    } else {
                        $src_extension = HXWP_PLUGIN_URL . 'assets/js/libs/htmx-extensions/' . $ext_script_name . '/' . $ext_script_name . '.js';
                        $src_ext_ver = filemtime(HXWP_ABSPATH . 'assets/js/libs/htmx-extensions/' . $ext_script_name . '/' . $ext_script_name . '.js');
                    }
                } else {
                    continue;
                }

                wp_enqueue_script('hxwp-htmx-ext-' . $ext_script_name, $src_extension, ['hxwp-htmx'], $src_ext_ver, true);
            }
        }

        // Nonce
        $hxwp_nonce = wp_create_nonce('hxwp_nonce');

        // wp-htmx URL
        $hxwp_api_url = home_url(HXWP_ENDPOINT . '/' . HXWP_ENDPOINT_VERSION . '/');

        // Localize script
        wp_localize_script('hxwp-htmx', 'htmx_api_wp', [
            'htmx_api' => $hxwp_api_url,
            'nonce'    => $hxwp_nonce,
        ]);

        // Also, let use X-WP-Nonce header, to automate nonce integration with HTMX
        $hxwp_script = "document.body.addEventListener('htmx:configRequest', function(evt) {evt.detail.headers['X-WP-Nonce'] = '" . $hxwp_nonce . "';});";

        // Filter
        $hxwp_script = apply_filters('hxwp/htmx_configrequest_nonce', $hxwp_script);

        wp_add_inline_script('hxwp-htmx', $hxwp_script);

        do_action('hxwp/init_enqueue_scripts_end');
    }
}

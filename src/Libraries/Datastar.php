<?php

declare(strict_types=1);

namespace HMApi\Libraries;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Datastar Class.
 * Handles Datastar PHP SDK related functionalities.
 *
 * @since 2.0.2
 */
class Datastar
{
    /**
     * Get Datastar SDK status information.
     *
     * Checks if the Datastar PHP SDK is available and provides status information
     * for display in the admin interface. Also handles automatic loading when
     * Datastar is selected as the active library.
     *
     * @since 2.0.2 Adapted from HMApi\Admin\Options
     *
     * @param string $option_name WordPress option name for storing plugin settings.
     * @return array {
     *     SDK status information array.
     *
     *     @type bool   $loaded  Whether the SDK is loaded and available.
     *     @type string $version SDK version if available, empty if not.
     *     @type string $html    HTML content for admin display.
     *     @type string $message Status message for logging/debugging.
     * }
     */
    public static function get_sdk_status(string $option_name): array
    {
        $sdk_loaded = false;
        $version = '';
        $message = '';
        $html = '';

        // Check if Datastar classes are already available (namespaced by Strauss)
        if (class_exists('HMApi\\starfederation\\datastar\\ServerSentEventGenerator')) {
            $sdk_loaded = true;
            $message = 'SDK already loaded by another source';

            // Try to get version from composer if possible
            // Note: Strauss might alter how version is found, this is a best-effort
            if (function_exists('get_plugin_data') && file_exists(HMAPI_ABSPATH . 'vendor-dist/composer/installed.json')) {
                $installed_json = @file_get_contents(HMAPI_ABSPATH . 'vendor-dist/composer/installed.json');
                if ($installed_json) {
                    $installed_data = json_decode($installed_json, true);
                    // Adjust logic if Strauss changes package data structure
                    if (isset($installed_data['packages'])) {
                        foreach ($installed_data['packages'] as $package) {
                            if (str_ends_with($package['name'], 'starfederation/datastar-php')) { // Check suffix due to potential prefixing
                                $version = $package['version'] ?? 'unknown';
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Try to load SDK if Datastar is selected as active library and SDK not already loaded
        if (!$sdk_loaded) {
            $default_options = ['active_library' => 'htmx'];
            $default_options = apply_filters('hmapi/default_options', $default_options);
            $current_options = get_option($option_name, $default_options);
            $active_library = $current_options['active_library'] ?? 'htmx';

            if ($active_library === 'datastar') {
                $sdk_loaded = self::load_sdk();
                if ($sdk_loaded) {
                    $message = 'SDK loaded automatically for Datastar library';
                } else {
                    $message = 'SDK loading failed - check installation';
                }
            } else {
                $message = 'SDK not loaded - Datastar is not the active library';
            }
        }

        // Generate HTML status display
        if ($sdk_loaded) {
            $status_class = 'notice-success';
            $status_icon = '✅';
            $status_text = esc_html__('Available', 'api-for-htmx');
            $version_text = $version ? sprintf(' (v%s)', esc_html($version)) : '';
        } else {
            $status_class = 'notice-warning';
            $status_icon = '⚠️';
            $status_text = esc_html__('Not Available', 'api-for-htmx');
            $version_text = '';
        }

        $html = '<div class="notice ' . $status_class . ' inline" style="margin: 0; padding: 8px 12px;">';
        $html .= '<p style="margin: 0;">';
        $html .= $status_icon . ' <strong>' . $status_text . '</strong>' . $version_text;

        if (!$sdk_loaded) {
            $html .= '<br><small>' . esc_html__('Run "composer require starfederation/datastar-php" in the plugin directory to install the SDK.', 'api-for-htmx') . '</small>';
        } else {
            $html .= '<br><small>' . esc_html($message) . '</small>';
        }

        $html .= '</p></div>';

        return [
            'loaded' => $sdk_loaded,
            'version' => $version,
            'html' => $html,
            'message' => $message,
        ];
    }

    /**
     * Load Datastar PHP SDK if available.
     *
     * Attempts to load the Datastar PHP SDK through Composer autoloader.
     * Only loads if not already available to prevent conflicts.
     *
     * @since 2.0.2 Adapted from HMApi\Admin\Options
     *
     * @return bool True if SDK is loaded and available, false otherwise.
     */
    public static function load_sdk(): bool
    {
        // Check if already loaded (namespaced by Strauss)
        if (class_exists('HMApi\\starfederation\\datastar\\ServerSentEventGenerator')) {
            return true;
        }

        // Try to load from plugin's vendor-dist directory
        $vendor_autoload = HMAPI_ABSPATH . 'vendor-dist/autoload.php';
        if (file_exists($vendor_autoload)) {
            try {
                require_once $vendor_autoload;

                // Verify the classes are now available (namespaced by Strauss)
                if (class_exists('HMApi\\starfederation\\datastar\\ServerSentEventGenerator')) {
                    return true;
                }
            } catch (\Exception $e) {
                // Log error if WordPress debug is enabled
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Hypermedia API: Failed to load Datastar SDK - ' . $e->getMessage());
                }
            }
        }

        return false;
    }
}

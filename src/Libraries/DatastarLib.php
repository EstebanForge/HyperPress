<?php

declare(strict_types=1);

namespace HyperPress\Libraries;

use HyperPress\Main;

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
class DatastarLib
{
    /**
     * The main plugin instance.
     *
     * @var Main
     */
    private Main $main;

    /**
     * Constructor.
     *
     * @param Main $main The main plugin instance.
     */
    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    /**
     * Get Datastar SDK status information.
     *
     * Checks if the Datastar PHP SDK is available and provides status information
     * for display in the admin interface. Also handles automatic loading when
     * Datastar is selected as the active library.
     *
     * @since 2.0.2 Adapted from HyperPress\Admin\Options
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
    public function getSdkStatus(array $options): array
    {
        $sdk_loaded = $this->isSdkLoaded();
        $version = 'not available';
        $message = 'Datastar PHP SDK not found. Please run composer install.';

        if ($sdk_loaded) {
            $message = 'Datastar PHP SDK is available.';
        }

        // Check Composer's installed.json for the package version.
        $composer_installed_path = HYPERPRESS_ABSPATH . '/vendor/composer/installed.json';
        if (file_exists($composer_installed_path)) {
            $installed_data = json_decode(file_get_contents($composer_installed_path), true);
            if (isset($installed_data['packages'])) {
                foreach ($installed_data['packages'] as $package) {
                    if (is_array($package) && isset($package['name']) && $package['name'] === 'starfederation/datastar-php') {
                        $version = $package['version'] ?? 'unknown';
                        break;
                    }
                }
            }
        }

        // Try to load SDK if Datastar is selected as active library and SDK not already loaded
        if (!$sdk_loaded) {
            $active_library = $options['active_library'] ?? 'htmx';

            if ($active_library === 'datastar') {
                $sdk_loaded = self::loadSdk();
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
     * @since 2.0.2 Adapted from HyperPress\Admin\Options
     *
     * @return bool True if SDK is loaded and available, false otherwise.
     */
    public static function loadSdk(): bool
    {
        return class_exists('HyperPress\starfederation\datastar\Consts');
    }

    /**
     * Check if the Datastar SDK is loaded.
     *
     * @return bool
     */
    private function isSdkLoaded(): bool
    {
        return class_exists('HyperPress\starfederation\datastar\Consts');
    }
}

<?php

declare(strict_types=1);

/**
 * HyperPress plugin adapter bootstrap.
 *
 * Thin WordPress plugin concerns only. Loads the Composer autoloader (which
 * pulls HyperPress-Core and its HyperFields/HyperBlocks dependencies and runs
 * each library's bootstrap.php as a Composer autoload.files entry), then wires
 * the adapter-level hooks the plugin owns: the HyperFields Data Tools page,
 * activation/deactivation, and the About system-info rows.
 *
 * The libraries self-initialize: each one's bootstrap schedules its init at
 * after_setup_theme behind a first-to-boot namespace-scoped LOADED guard. The
 * adapter therefore no longer registers the old multi-instance election
 * machinery (removed from the libraries) and no longer loads the bundled
 * packages autoloader the stack previously depended on.
 *
 * Class references use ::class so a Mozart-prefixed build rewrites them to the
 * prefixed namespace; the previous string literals ('HyperFields\\HyperFields')
 * would have been missed by the prefixer and silently failed.
 */

if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

if (defined('HYPERPRESS_PLUGIN_ADAPTER_BOOTSTRAPPED')) {
    return;
}
define('HYPERPRESS_PLUGIN_ADAPTER_BOOTSTRAPPED', true);

if (!function_exists('hyperpress_adapter_require_once_path')) {
    /**
     * Require a file only once across this request, using normalized absolute paths.
     */
    function hyperpress_adapter_require_once_path(string $path): bool
    {
        static $loaded = [];
        $resolved = realpath($path) ?: $path;
        $normalized = str_replace('\\', '/', $resolved);
        if (isset($loaded[$normalized])) {
            return true;
        }
        if (!file_exists($resolved)) {
            return false;
        }
        require_once $resolved;
        $loaded[$normalized] = true;

        return true;
    }
}

require_once __DIR__ . '/includes/adapter-functions.php';

$adapter_main_file = file_exists(__DIR__ . '/hyperpress.php')
    ? __DIR__ . '/hyperpress.php'
    : __DIR__ . '/api-for-htmx.php';

// Load the Composer autoloader. Plugin vendor first, local monorepo fallback
// second. This also runs every bundled library's bootstrap.php (Composer
// autoload.files), scheduling each library's init at after_setup_theme.
$autoload_candidates = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/HyperPress-Core/vendor/autoload.php',
];
foreach ($autoload_candidates as $autoload) {
    if (hyperpress_adapter_require_once_path($autoload)) {
        break;
    }
}

// Bail with an admin notice if HyperPress-Core is not autoloadable.
if (!class_exists(\HyperPress\Bootstrap::class)) {
    if (function_exists('add_action')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="error"><p>' . esc_html__('HyperPress: HyperPress Core not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
        });
    }

    return;
}

// Register HyperFields Export/Import UI under Tools.
if (class_exists(\HyperFields\HyperFields::class) && function_exists('add_action')) {
    add_action('admin_menu', static function (): void {
        \HyperFields\HyperFields::registerDataToolsPage(
            parentSlug: 'tools.php',
            pageSlug: 'hyperpress-data-tools',
            options: [
                'hyperpress_options' => __('HyperPress Settings', 'api-for-htmx'),
            ],
            allowedImportOptions: ['hyperpress_options'],
            prefix: '',
            title: __('HyperPress Data Tools', 'api-for-htmx'),
            capability: 'manage_options'
        );
    });
}

// Plugin lifecycle hooks remain in the adapter layer.
if (
    class_exists(\HyperPress\Admin\Activation::class)
    && function_exists('register_activation_hook')
    && function_exists('register_deactivation_hook')
) {
    register_activation_hook($adapter_main_file, [\HyperPress\Admin\Activation::class, 'activate']);
    register_deactivation_hook($adapter_main_file, [\HyperPress\Admin\Activation::class, 'deactivate']);
}

// Enrich the About page system-info table with vendored library versions,
// sourced from each library's prefix-safe Config class (no global constants).
if (function_exists('add_filter')) {
    add_filter('hyperpress/about/system_info', static function (array $info): array {
        $library_versions = [];

        if (class_exists(\HyperFields\Config::class)) {
            $library_versions[__('HyperFields Library', 'api-for-htmx')] = \HyperFields\Config::VERSION;
        }
        if (class_exists(\HyperBlocks\Config::class)) {
            $library_versions[__('HyperBlocks Library', 'api-for-htmx')] = \HyperBlocks\Config::VERSION;
        }
        if (class_exists(\HyperPress\Config::class)) {
            $library_versions[__('HyperPress Core', 'api-for-htmx')] = \HyperPress\Config::VERSION;
        }

        return hyperpress_adapter_insert_library_versions(
            $info,
            $library_versions,
            __('Plugin Version', 'api-for-htmx')
        );
    });
}

/**
 * Milestone review-ask.
 *
 * Celebrates the first served /wp-html/v1/ hypermedia request with a
 * dismissible review-ask notice shown to administrators. One-shot per site.
 * The milestone is recorded on the REST request; the notice surfaces on the
 * next admin page load. Domain 'api-for-htmx' matches the adapter layer.
 */
if (function_exists('add_filter') && function_exists('add_action')) {
    // Record the milestone once, on the first wp-html REST dispatch.
    add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
        $route = $request->get_route();

        if (hyperpress_adapter_should_record_review_milestone(
            !empty(get_option('hyperpress_review_milestone', false)),
            is_string($route) ? $route : null
        )) {
            update_option(
                'hyperpress_review_milestone',
                ['at' => time()],
                false
            );
        }

        return $result;
    }, 10, 3);

    // Render the notice, gated to administrators on HyperPress or plugins pages.
    add_action('admin_notices', static function (): void {
        $now = time();
        $user_id = get_current_user_id();
        $screen = get_current_screen();

        if (!hyperpress_adapter_should_show_review_notice(
            get_option('hyperpress_review_milestone', false),
            current_user_can('manage_options'),
            $user_id,
            (int) get_user_meta($user_id, 'hyperpress_review_snooze', true),
            $now,
            $screen ? $screen->id : null
        )) {
            return;
        }

        // Showing it now re-snoozes for the full interval so it never nags.
        update_user_meta($user_id, 'hyperpress_review_snooze', $now + (180 * DAY_IN_SECONDS));

        $review_url = 'https://wordpress.org/support/plugin/api-for-htmx/reviews/#new-post';
        $dismiss_url = wp_nonce_url(
            add_query_arg('hyperpress_review_dismiss', '1'),
            'hyperpress_review_dismiss',
            'hyperpress_review_nonce'
        );

        echo '<div class="notice notice-info">';
        echo '<p>';
        echo '<strong>' . esc_html__('HyperPress', 'api-for-htmx') . '</strong>. ';
        echo esc_html__('Nice, HyperPress served its first hypermedia request on your site.', 'api-for-htmx') . ' ';
        echo esc_html__('If it is helping, a quick review on WordPress.org helps others find it.', 'api-for-htmx');
        echo ' <a href="' . esc_url($review_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Leave a review', 'api-for-htmx') . '</a>';
        echo ' &middot; <a href="' . esc_url($dismiss_url) . '">' . esc_html__('Maybe later', 'api-for-htmx') . '</a>';
        echo '</p>';
        echo '</div>';
    });

    // Handle the per-user dismissal.
    add_action('admin_init', static function (): void {
        if (!isset($_GET['hyperpress_review_dismiss']) || '1' !== $_GET['hyperpress_review_dismiss']) {
            return;
        }

        if (
            !isset($_GET['hyperpress_review_nonce'])
            || !wp_verify_nonce(sanitize_key($_GET['hyperpress_review_nonce']), 'hyperpress_review_dismiss')
        ) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        update_user_meta($user_id, 'hyperpress_review_snooze', time() + (180 * DAY_IN_SECONDS));

        wp_safe_redirect(remove_query_arg(['hyperpress_review_dismiss', 'hyperpress_review_nonce']));
        exit;
    });
}

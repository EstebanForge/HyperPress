<?php

declare(strict_types=1);

/**
 * WordPress Function Mocks.
 *
 * Provides mock implementations of WordPress functions for unit testing.
 */
if (!function_exists('add_action')) {
    /**
     * Mock add_action function.
     */
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('add_filter')) {
    /**
     * Mock add_filter function.
     *
     * Records callbacks into a global registry so tests can exercise code
     * paths that read filtered values (e.g. the hyperblocks/blocks/
     * register_fluent_blocks escape hatch). Faithful to WP semantics:
     * apply_filters() invokes registered callbacks, falling back to the
     * raw value when none are attached. Priority is ignored (registration
     * order is preserved).
     */
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        global $__hb_test_filters;
        $__hb_test_filters[$hook][] = $callback;

        return true;
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Mock apply_filters function.
     *
     * Invokes callbacks registered via add_filter() for $hook, threading
     * $value through each. With no callbacks attached, returns $value
     * unchanged (the historical no-op behavior existing tests rely on).
     */
    function apply_filters(string $hook, mixed $value, ...$args): mixed
    {
        global $__hb_test_filters;

        foreach (($__hb_test_filters[$hook] ?? []) as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }
}

if (!function_exists('__return_true')) {
    /**
     * Mock __return_true — WordPress core shorthand.
     */
    function __return_true(): bool
    {
        return true;
    }
}

if (!function_exists('__return_false')) {
    /**
     * Mock __return_false — WordPress core shorthand.
     */
    function __return_false(): bool
    {
        return false;
    }
}

if (!function_exists('esc_html')) {
    /**
     * Mock esc_html function.
     */
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Mock esc_attr function.
     */
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    /**
     * Mock esc_url function.
     */
    function esc_url(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('esc_url_raw')) {
    /**
     * Mock esc_url_raw function.
     */
    function esc_url_raw(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('sanitize_title')) {
    /**
     * Mock sanitize_title function.
     */
    function sanitize_title(string $title): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
}

if (!function_exists('sanitize_text_field')) {
    /**
     * Mock sanitize_text_field function.
     */
    function sanitize_text_field(string $str): string
    {
        return strip_tags($str);
    }
}

if (!function_exists('wp_kses_post')) {
    /**
     * Mock wp_kses_post function.
     */
    function wp_kses_post(string $string): string
    {
        return strip_tags($string, '<p><a><strong><em><ul><ol><li><h1><h2><h3>');
    }
}

if (!function_exists('wp_normalize_path')) {
    /**
     * Mock wp_normalize_path function.
     */
    function wp_normalize_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('wp_json_encode')) {
    /**
     * Mock wp_json_encode function.
     */
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('is_dir')) {
    /**
     * Mock is_dir function (PHP function, but included for completeness).
     */
    function is_dir(string $filename): bool
    {
        return \is_dir($filename);
    }
}

if (!function_exists('file_exists')) {
    /**
     * Mock file_exists function.
     */
    function file_exists(string $filename): bool
    {
        return \file_exists($filename);
    }
}

if (!function_exists('get_file_data')) {
    /**
     * Mock get_file_data function — faithful port of WP core semantics.
     *
     * Reads the first 8192 bytes of $file and parses WordPress-style file
     * headers (the same convention used for plugin/theme/dropin headers,
     * including headers nested in docblock comment lines prefixed with ` * `).
     * Used by Registry::isFluentBlockFile() to detect the HyperBlocks Block
     * header without executing the file.
     */
    function get_file_data(string $file, array $default_headers, string $context = ''): array
    {
        $fp = @fopen($file, 'r');
        if (!$fp) {
            return array_fill_keys(array_keys($default_headers), '');
        }

        $file_data = fread($fp, 8192);
        fclose($fp);

        $file_data = str_replace("\r", "\n", $file_data);

        foreach ($default_headers as $field => $regex) {
            $default_headers[$field] = '';
            if (preg_match('/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote((string) $regex, '/') . ':(.*)$/mi', $file_data, $match)
                && isset($match[1])
                && $match[1] !== ''
            ) {
                // _cleanup_header_comment() equivalent.
                $default_headers[$field] = trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $match[1]));
            }
        }

        return $default_headers;
    }
}

if (!function_exists('get_option')) {
    /**
     * Mock get_option function.
     */
    function get_option(string $option, mixed $default = false): mixed
    {
        global $_test_options;

        return $_test_options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    /**
     * Mock update_option function.
     */
    function update_option(string $option, mixed $value, bool $autoload = true): bool
    {
        global $_test_options;
        $_test_options[$option] = $value;

        return true;
    }
}

// Initialize test options storage
global $_test_options;
$_test_options = [];

if (!function_exists('plugins_url')) {
    /**
     * Mock plugins_url function.
     */
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        return 'https://example.com/wp-content/plugins' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('plugin_dir_path')) {
    /**
     * Mock plugin_dir_path function.
     */
    function plugin_dir_path(string $file): string
    {
        return dirname($file) . '/';
    }
}

if (!function_exists('trailingslashit')) {
    /**
     * Mock trailingslashit function.
     */
    function trailingslashit(string $value): string
    {
        return rtrim($value, '/\\') . '/';
    }
}

if (!function_exists('untrailingslashit')) {
    /**
     * Mock untrailingslashit function.
     */
    function untrailingslashit(string $value): string
    {
        return rtrim($value, '/\\');
    }
}

if (!function_exists('get_template_directory')) {
    /**
     * Mock get_template_directory function.
     */
    function get_template_directory(): string
    {
        return sys_get_temp_dir() . '/wp-content/themes/test-theme';
    }
}

if (!function_exists('get_stylesheet_directory')) {
    /**
     * Mock get_stylesheet_directory function.
     */
    function get_stylesheet_directory(): string
    {
        return sys_get_temp_dir() . '/wp-content/themes/test-theme';
    }
}

if (!function_exists('is_child_theme')) {
    /**
     * Mock is_child_theme function.
     */
    function is_child_theme(): bool
    {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    /**
     * Mock current_user_can function.
     */
    function current_user_can(string $capability, ...$args): bool
    {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    /**
     * Mock wp_enqueue_script function.
     */
    function wp_enqueue_script(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $ver = false,
        bool $in_footer = false
    ): void {
        HyperBlocks_Testing_Registry::recordEnqueueScript($handle, $src, $deps, $ver, $in_footer);
    }
}

if (!function_exists('wp_register_script')) {
    /**
     * Mock wp_register_script function.
     *
     * Records calls into HyperBlocks_Testing_Registry so unit tests can assert
     * on the handle, URL, and dependency list that Bootstrap::registerEditorScript()
     * wires up. Mirrors the wp_enqueue_script capture.
     */
    function wp_register_script(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $ver = false,
        bool $in_footer = false
    ): bool {
        HyperBlocks_Testing_Registry::recordRegisterScript($handle, $src, $deps, $ver, $in_footer);

        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * Mock wp_enqueue_style function.
     */
    function wp_enqueue_style(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $ver = false,
        string $media = 'all'
    ): void {}
}

if (!function_exists('wp_add_inline_script')) {
    /**
     * Mock wp_add_inline_script function.
     */
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
    {
        HyperBlocks_Testing_Registry::recordInlineScript($handle, $data, $position);

        return true;
    }
}

if (!function_exists('register_rest_route')) {
    /**
     * Mock register_rest_route function.
     */
    function register_rest_route(
        string $namespace,
        string $route,
        array $args = [],
        bool $override = false
    ): bool {
        return true;
    }
}

if (!function_exists('register_block_type')) {
    /**
     * Mock register_block_type function.
     *
     * Captures the last call's (name, args) into HyperBlocks_Testing_Registry
     * so unit tests can assert on the exact args threaded through by
     * Bootstrap::registerSingleBlock(). Read via
     * HyperBlocks_Testing_Registry::getLastBlockRegistration().
     */
    function register_block_type(string $block_name, array $args): WP_Block_Type|false
    {
        HyperBlocks_Testing_Registry::recordBlockRegistration($block_name, $args);

        // Return false (a valid per-signature value) rather than a fabricated
        // WP_Block_Type: the mock has no such class, and unit tests assert on
        // the recorded args via HyperBlocks_Testing_Registry, not the return.
        return false;
    }
}

/**
 * Test-only capture helper for the register_block_type mock.
     *
 * Lives in the global namespace alongside the mock. Not loaded in production.
 */
final class HyperBlocks_Testing_Registry
{
    /** @var array{0: string, 1: array} */
    private static array $last = ['', []];

    /** @var array{handle: string, src: string, deps: array, ver: string|bool|null, in_footer: bool} */
    private static array $lastEnqueue = [
        'handle'    => '',
        'src'       => '',
        'deps'      => [],
        'ver'       => false,
        'in_footer' => false,
    ];

    /** @var array{handle: string, src: string, deps: array, ver: string|bool|null, in_footer: bool} */
    private static array $lastRegister = [
        'handle'    => '',
        'src'       => '',
        'deps'      => [],
        'ver'       => false,
        'in_footer' => false,
    ];

    /** @var array{handle: string, data: string, position: string} */
    private static array $lastInline = ['handle' => '', 'data' => '', 'position' => ''];

    public static function recordBlockRegistration(string $name, array $args): void
    {
        self::$last = [$name, $args];
    }

    /**
     * @return array{0: string, 1: array}
     */
    public static function getLastBlockRegistration(): array
    {
        return self::$last;
    }

    public static function recordEnqueueScript(
        string $handle,
        string $src,
        array $deps,
        string|bool|null $ver,
        bool $in_footer
    ): void {
        self::$lastEnqueue = [
            'handle'    => $handle,
            'src'       => $src,
            'deps'      => $deps,
            'ver'       => $ver,
            'in_footer' => $in_footer,
        ];
    }

    /**
     * @return array{handle: string, src: string, deps: array, ver: string|bool|null, in_footer: bool}
     */
    public static function getLastEnqueueScript(): array
    {
        return self::$lastEnqueue;
    }

    public static function recordRegisterScript(
        string $handle,
        string $src,
        array $deps,
        string|bool|null $ver,
        bool $in_footer
    ): void {
        self::$lastRegister = [
            'handle'    => $handle,
            'src'       => $src,
            'deps'      => $deps,
            'ver'       => $ver,
            'in_footer' => $in_footer,
        ];
    }

    /**
     * @return array{handle: string, src: string, deps: array, ver: string|bool|null, in_footer: bool}
     */
    public static function getLastRegisterScript(): array
    {
        return self::$lastRegister;
    }

    public static function recordInlineScript(string $handle, string $data, string $position): void
    {
        self::$lastInline = ['handle' => $handle, 'data' => $data, 'position' => $position];
    }

    /**
     * @return array{handle: string, data: string, position: string}
     */
    public static function getLastInlineScript(): array
    {
        return self::$lastInline;
    }

    public static function reset(): void
    {
        self::$last = ['', []];
        self::$lastEnqueue = [
            'handle'    => '',
            'src'       => '',
            'deps'      => [],
            'ver'       => false,
            'in_footer' => false,
        ];
        self::$lastRegister = [
            'handle'    => '',
            'src'       => '',
            'deps'      => [],
            'ver'       => false,
            'in_footer' => false,
        ];
        self::$lastInline = ['handle' => '', 'data' => '', 'position' => ''];
    }
}

if (!class_exists('\WP_Block')) {
    /**
     * Mock WP_Block class.
     */
    class WP_Block
    {
        public string $name = '';
        public array $attributes = [];
    }
}

if (!class_exists('\WP_REST_Request')) {
    /**
     * Mock WP_REST_Request class.
     */
    class WP_REST_Request
    {
        public function get_param(string $key): mixed
        {
            return null;
        }
    }
}

if (!class_exists('\WP_REST_Response')) {
    /**
     * Mock WP_REST_Response class.
     */
    class WP_REST_Response
    {
        public function __construct(mixed $data = null, int $status = 200, array $headers = []) {}
    }
}

if (!class_exists('\WP_REST_Server')) {
    /**
     * Mock WP_REST_Server class.
     */
    class WP_REST_Server
    {
        public const READABLE = 'GET';
        public const CREATABLE = 'POST';
        public const EDITABLE = 'POST, PUT, PATCH';
        public const DELETABLE = 'DELETE';
        public const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}

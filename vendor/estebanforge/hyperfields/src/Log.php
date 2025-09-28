<?php

namespace HyperFields;

/**
 * Handles plugin-specific logging with daily rotation and source-based grouping.
 * Logs are stored in wp-content/uploads/hyperpress-logs/.
 */
class Log
{
    public const LOG_LEVEL_DEBUG = 'debug';
    public const LOG_LEVEL_INFO = 'info';
    public const LOG_LEVEL_WARNING = 'warning';
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_CRITICAL = 'critical';

    private static bool $logDirSetupDone = false;
    private static ?string $logBaseDir = null;

    /**
     * Registers a handler to catch and log fatal errors.
     * This should be called once when the plugin initializes.
     */
    public static function registerFatalErrorHandler()
    {
        register_shutdown_function([new self(), 'handleFatalError']);
    }

    /**
     * Handles fatal errors at script shutdown.
     *
     * This method is registered via `register_shutdown_function` and should not be called directly.
     * It checks for a fatal error and logs it.
     */
    public function handleFatalError()
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $message = sprintf(
                'Fatal Error: %s in %s on line %d',
                $error['message'],
                $error['file'],
                $error['line']
            );

            // Use the existing log method to write the fatal error
            self::log(self::LOG_LEVEL_CRITICAL, $message, ['source' => 'hyperpress-fatal-error']);
        }
    }

    /**
     * Logs a message to a custom file.
     *
     * Mimics WC_Logger::log functionality with daily rotation and source-based grouping.
     * Logs are stored in wp-content/uploads/hyperpress-logs/.
     *
     * @param string $level   Log level (e.g., Log::LOG_LEVEL_DEBUG, 'info', 'error').
     * @param string $message Log message.
     * @param array  $context Context for the log message. Expected: ['source' => 'my-source'].
     *                        If 'source' is not provided, 'hyperpress-plugin' will be used.
     * @return bool True if the message was logged successfully to the custom file, false otherwise.
     */
    public static function log(string $level, string $message, array $context = []): bool
    {
        // Log CRITICAL and ERROR messages regardless of WP_DEBUG.
        // For other levels (DEBUG, INFO, WARNING), log only if WP_DEBUG is on.
        if ($level !== self::LOG_LEVEL_CRITICAL && $level !== self::LOG_LEVEL_ERROR) {
            if (!defined('WP_DEBUG') || !WP_DEBUG) {
                return true; // WP_DEBUG is off, so don't log DEBUG, INFO, or WARNING messages.
            }
        }

        if (!self::$logDirSetupDone) {
            if (!self::setupLogDirectory()) {
                // Fallback to standard PHP error log if setup fails
                error_log("HyperPress Log Directory Setup Failed. Original log: [{$level}] {$message}");

                return false;
            }
            self::$logDirSetupDone = true;
        }

        $source = sanitize_file_name($context['source'] ?? 'hyperpress-plugin');
        if (empty($source)) {
            $source = 'hyperpress-plugin'; // Ensure source is not empty after sanitization
        }

        $date_suffix = date('Y-m-d');
        $file_hash = wp_hash($source);
        $filename = "{$source}-{$date_suffix}-{$file_hash}.log";
        $log_file_path = self::$logBaseDir . $filename;

        $timestamp = date('Y-m-d\TH:i:s\Z'); // ISO 8601 UTC
        $formatted_level = strtoupper($level);
        $log_entry = "{$timestamp} [{$formatted_level}]: {$message}" . PHP_EOL;

        if (!error_log($log_entry, 3, $log_file_path)) {
            // Fallback to standard PHP error log if custom file write fails
            error_log("HyperPress Log File Write Failed to {$log_file_path}. Original log: [{$level}] {$message}");

            return false;
        }

        return true;
    }

    /**
     * Logs a CRITICAL message.
     *
     * @param string $message The message to log.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Logs an ERROR message.
     *
     * @param string $message The message to log.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * Logs a WARNING message.
     *
     * @param string $message The message to log.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * Logs an INFO message.
     *
     * @param string $message The message to log.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * Logs a DEBUG message.
     *
     * @param string $message The message to log.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_DEBUG, $message, $context);
    }

    /**
     * Sets up the log directory, ensuring it exists and is secured.
     *
     * @return bool True if setup was successful or already done, false on critical failure.
     */
    private static function setupLogDirectory(): bool
    {
        if (self::$logBaseDir === null) {
            $upload_dir = wp_upload_dir();
            if (!empty($upload_dir['error'])) {
                error_log('HyperPress Log Error: Could not get WordPress upload directory. ' . $upload_dir['error']);

                return false;
            }
            self::$logBaseDir = $upload_dir['basedir'] . '/hyperpress-logs/';
        }

        if (!is_dir(self::$logBaseDir)) {
            if (!wp_mkdir_p(self::$logBaseDir)) {
                error_log('HyperPress Log Error: Could not create log directory: ' . self::$logBaseDir);

                return false;
            }
        }

        $htaccess_file = self::$logBaseDir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = 'deny from all' . PHP_EOL . 'Require all denied' . PHP_EOL;
            if (@file_put_contents($htaccess_file, $htaccess_content) === false) {
                error_log('HyperPress Log Error: Could not create .htaccess file in ' . self::$logBaseDir);
            }
        }

        $index_html_file = self::$logBaseDir . 'index.html';
        if (!file_exists($index_html_file)) {
            $index_content = '<!-- Silence is golden. -->';
            if (@file_put_contents($index_html_file, $index_content) === false) {
                error_log('HyperPress Log Error: Could not create index.html file in ' . self::$logBaseDir);
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

/**
 * Log observer for the Abilities layer.
 *
 * When WP_DEBUG is on, every ability execution (ours and core's) lands in
 * the HyperFields debug log: the ability name plus a truncated JSON snapshot
 * of the input before execution, and of the validated result after.
 *
 * Known limits, stated plainly:
 * - wp_before_execute_ability fires AFTER the permission check, and
 *   wp_after_execute_ability fires only after output validation passes. A
 *   permission-denied call reaches NEITHER hook, so denials are invisible
 *   here. Denial visibility needs wp_ability_permission_result (WP 7.1+).
 * - HyperFields' log directory protection is .htaccess-based, which nginx
 *   ignores. On nginx stacks, deny hyperpress-logs/ at the server level or
 *   relocate the directory before treating these logs as sensitive.
 * - Payloads are truncated per entry; this is a debugging aid, not an audit
 *   trail.
 *
 * @since 3.6.0
 */

namespace HyperPress\Abilities;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * WP_DEBUG-gated execution logging for ability runs.
 */
final class LogObserver
{
    /**
     * Maximum characters of serialized payload per log entry.
     */
    private const PAYLOAD_LIMIT = 1000;

    /**
     * Hook the execution observers. Independent of the abilities kill
     * switch: this is a generic WP_DEBUG logger for every ability on the
     * site (core's included), not part of HyperPress's registration.
     *
     * @return void
     */
    public static function init(): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        if (!class_exists(\HyperFields\Log::class)) {
            return;
        }

        add_action('wp_before_execute_ability', [self::class, 'logBefore'], 10, 2);
        add_action('wp_after_execute_ability', [self::class, 'logAfter'], 10, 3);
    }

    /**
     * Log the attempt. Fires after the permission check passed and before
     * the execute callback runs; denied calls never reach this.
     *
     * @param string $ability_name Namespaced ability name.
     * @param mixed  $input        Input data.
     * @return void
     */
    public static function logBefore(string $ability_name, $input): void
    {
        \HyperFields\Log::debug(
            sprintf(
                'Ability %s executing. Input: %s',
                $ability_name,
                self::payload($input)
            ),
            ['source' => 'hyperpress-abilities']
        );
    }

    /**
     * Log the validated result. Only fires after output validation passes.
     *
     * @param string $ability_name Namespaced ability name.
     * @param mixed  $input        Input data.
     * @param mixed  $result       Validated result.
     * @return void
     */
    public static function logAfter(string $ability_name, $input, $result): void
    {
        \HyperFields\Log::debug(
            sprintf(
                'Ability %s completed. Result: %s',
                $ability_name,
                self::payload($result)
            ),
            ['source' => 'hyperpress-abilities']
        );
    }

    /**
     * Serialize a payload for the log line, truncated to the entry limit.
     *
     * @param mixed $payload Input or result data.
     * @return string
     */
    private static function payload(mixed $payload): string
    {
        $json = wp_json_encode($payload);

        if ($json === false || $json === '') {
            return '(none)';
        }

        if (strlen($json) > self::PAYLOAD_LIMIT) {
            return substr($json, 0, self::PAYLOAD_LIMIT) . '...(truncated)';
        }

        return $json;
    }
}

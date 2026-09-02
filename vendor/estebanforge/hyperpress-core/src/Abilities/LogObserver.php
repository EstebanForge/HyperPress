<?php

declare(strict_types=1);

/**
 * Log observer for the Abilities layer.
 *
 * When WP_DEBUG is on, every ability execution (ours and core's) lands in
 * the HyperFields debug log: name and input before the permission-checked
 * execution, name and validated result after. wp_after_execute_ability only
 * fires on success, so failed executions are visible through the before-hook
 * alone - pair the two entries when auditing.
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
     * Hook the execution observers. Called from AbilityRegistrar::init()
     * after the kill switch, only when WP_DEBUG is on.
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
     * Log the attempt: fires before permission checks, so denials are
     * visible here even though wp_after_execute_ability will not fire.
     *
     * @param string $ability_name Namespaced ability name.
     * @param mixed  $input        Input data.
     * @return void
     */
    public static function logBefore(string $ability_name, $input): void
    {
        \HyperFields\Log::debug(
            sprintf('Ability %s executing.', $ability_name),
            [
                'source'      => 'hyperpress-abilities',
                'ability'     => $ability_name,
                'input'       => $input,
            ]
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
            sprintf('Ability %s completed.', $ability_name),
            [
                'source'      => 'hyperpress-abilities',
                'ability'     => $ability_name,
                'input'       => $input,
                'result'      => $result,
            ]
        );
    }
}

<?php

declare(strict_types=1);

/**
 * Central configuration and runtime paths for HyperFields.
 *
 * VERSION and BASENAME are class constants. The runtime-computed paths and
 * URL (ABSPATH, PLUGIN_URL, PLUGIN_FILE) are static properties set once during
 * initialization. This replaces the former global define() constants so that
 * namespace prefixing isolates these values per consumer: a prefixed copy
 * becomes e.g. ConsumerA\Dependencies\HyperFields\Config, fully distinct from
 * any other consumer's copy.
 *
 * @since 1.5.0
 */

namespace HyperFields;

final class Config
{
    /**
     * Semantic version string. Kept in sync with composer.json via the
     * version-bump script (single source of truth for the PHP side).
     */
    public const VERSION = '1.5.2';

    /**
     * Bootstrap file identifier relative to the library root.
     */
    public const BASENAME = 'hyperfields/bootstrap.php';

    /**
     * Absolute path to the library root, with a trailing slash.
     * Empty until initialization runs.
     *
     * @var string
     */
    public static string $abspath = '';

    /**
     * Public content URL for the library root, with a trailing slash.
     * Empty when the directory is not reachable over HTTP so asset enqueues
     * can bail instead of emitting a broken URL.
     *
     * @var string
     */
    public static string $pluginUrl = '';

    /**
     * Absolute path to the bootstrap file.
     * Empty until initialization runs.
     *
     * @var string
     */
    public static string $pluginFile = '';

    /**
     * Whether initialization has run for this copy. Guards the init-once
     * contract per prefixed instance.
     *
     * @var bool
     */
    private static bool $initialized = false;

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function markInitialized(): void
    {
        self::$initialized = true;
    }
}

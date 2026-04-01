<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

/**
 * @deprecated Use WPSettingsCompatibility.
 */
final class SettingsCompatibility
{
    /**
     * Register.
     *
     * @return \HyperFields\OptionsPage
     */
    public static function register(array $config): \HyperFields\OptionsPage
    {
        return WPSettingsCompatibility::register($config);
    }
}


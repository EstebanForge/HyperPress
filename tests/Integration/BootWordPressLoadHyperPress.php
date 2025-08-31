<?php

declare(strict_types=1);

namespace HyperPress\Tests\Integration;

use Yoast\WPTestUtils\WPIntegration\TestCase;

final class BootWordPressLoadHyperPress extends TestCase
{
    public function test_boots_wordpress_and_loads_hyperpress(): void
    {
        // Basic sanity checks to ensure WordPress is loaded.
        $this->assertTrue(function_exists('do_action'));

        // HyperPress defines constants during initialization.
        $this->assertTrue(defined('HYPERPRESS_VERSION'));
        $this->assertTrue(defined('HYPERPRESS_ABSPATH'));
    }
}

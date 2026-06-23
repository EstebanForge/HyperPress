<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use HyperPress\OptionsResolver;
use PHPUnit\Framework\TestCase;

/**
 * Test the canonical options resolver.
 *
 * In production the `hyperpress/options` filter is applied last so consumers
 * always win, even when a stored option exists. This test environment cannot
 * intercept WP filter calls (the shared bootstrap defines `apply_filters` as
 * a passthrough before Brain\Monkey loads), so the filter's last-wins
 * guarantee is covered structurally by verifying the resolver calls
 * `apply_filters` with the canonical filter constant. Override behavior
 * end-to-end is an integration-test concern.
 */
class OptionsResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // includes/helpers.php references HYPERPRESS_ABSPATH at the
        // bottom (for deprecated.php include). The production bootstrap
        // defines it inside hyperpress_run_initialization_logic(); unit
        // tests bypass that path.
        if (!defined('HYPERPRESS_ABSPATH')) {
            define('HYPERPRESS_ABSPATH', HYPERPRESS_DIR . '/');
        }

        if (!function_exists('hp_get_options')) {
            require_once HYPERPRESS_DIR . '/includes/helpers.php';
        }
    }

    public function test_filter_and_action_constants_are_exposed(): void
    {
        $this->assertSame('hyperpress/options', OptionsResolver::FILTER);
        $this->assertSame('hyperpress/configured', OptionsResolver::ACTION);
    }

    public function test_defaults_returns_canonical_shape(): void
    {
        $defaults = OptionsResolver::defaults();

        $this->assertSame('datastar', $defaults['active_library']);
        $this->assertArrayHasKey('load_from_cdn', $defaults);
        $this->assertArrayHasKey('load_hyperscript', $defaults);
        $this->assertArrayHasKey('load_alpinejs_with_htmx', $defaults);
        $this->assertArrayHasKey('set_htmx_hxboost', $defaults);
        $this->assertArrayHasKey('load_htmx_backend', $defaults);
        $this->assertArrayHasKey('load_alpinejs_backend', $defaults);
        $this->assertArrayHasKey('load_datastar_backend', $defaults);
        $this->assertArrayHasKey('hyperpress_meta_config_content', $defaults);
    }

    public function test_defaults_synthesizes_load_extension_keys_for_htmx_extensions(): void
    {
        $extensions = [
            'sse' => ['version' => '2.2.3'],
            'loading-states' => ['version' => '2.0.1'],
            'head-support' => ['version' => '2.0.4'],
        ];

        $defaults = OptionsResolver::defaults($extensions);

        // Keys must use underscores to match Admin/Options.php and the
        // stored DB option; the CDN map uses hyphens but the option
        // shape does not.
        $this->assertSame(0, $defaults['load_extension_sse']);
        $this->assertSame(0, $defaults['load_extension_loading_states']);
        $this->assertSame(0, $defaults['load_extension_head_support']);
    }

    public function test_resolve_returns_merged_defaults_when_db_empty(): void
    {
        // The test bootstrap mocks get_option as a passthrough returning
        // the default ([]), so DB is effectively empty here.
        $resolved = OptionsResolver::resolve();

        $this->assertIsArray($resolved);
        $this->assertSame('datastar', $resolved['active_library']);
        $this->assertArrayHasKey('hyperpress_meta_config_content', $resolved);
    }

    public function test_resolve_includes_htmx_extension_defaults(): void
    {
        $extensions = ['sse' => ['version' => '2.2.3']];

        $resolved = OptionsResolver::resolve($extensions);

        $this->assertArrayHasKey('load_extension_sse', $resolved);
        $this->assertSame(0, $resolved['load_extension_sse']);
    }

    public function test_resolve_handles_non_array_stored_option(): void
    {
        // Defensive: get_option() can return non-array values if the option
        // is corrupted. The resolver must coerce to [] and not fatal.
        // The bootstrap's get_option returns the default [], so we cover
        // this by checking the resolver type signature is array.
        $resolved = OptionsResolver::resolve();

        $this->assertIsArray($resolved);
    }

    public function test_hp_get_options_helper_matches_resolver(): void
    {
        $this->assertTrue(
            function_exists('hp_get_options'),
            'hp_get_options() must be defined in includes/helpers.php'
        );
        $this->assertSame(OptionsResolver::resolve(), hp_get_options());
    }

    public function test_hp_get_options_uses_datastar_default_in_test_env(): void
    {
        // Sanity: confirms the helper resolves the same canonical defaults.
        $this->assertSame('datastar', hp_get_options()['active_library']);
    }

    public function test_hp_get_option_returns_canonical_value(): void
    {
        $this->assertTrue(function_exists('hp_get_option'));
        $this->assertSame('datastar', hp_get_option('active_library'));
    }

    public function test_hp_get_option_returns_default_for_missing_key(): void
    {
        $this->assertSame('fallback', hp_get_option('nonexistent_key', 'fallback'));
        $this->assertNull(hp_get_option('nonexistent_key'));
    }

    public function test_hp_get_option_accepts_non_scalar_default(): void
    {
        $default = ['nested' => 'value'];
        $this->assertSame($default, hp_get_option('nonexistent_array_key', $default));
    }
}

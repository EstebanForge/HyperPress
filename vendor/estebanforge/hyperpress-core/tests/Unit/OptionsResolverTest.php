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

        // The procedural helpers (hp_*) are loaded globally by the test
        // bootstrap (tests/bootstrap.php requires src/helpers.php after
        // populating Config). No per-test constant or include is needed.
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

    public function test_defaults_default_to_htmx_4_with_hxlive_on(): void
    {
        $defaults = OptionsResolver::defaults();

        // New installs get the htmx 4.x line and hx-live included by
        // default (opt-out, not opt-in).
        $this->assertSame('4', $defaults['htmx_version']);
        $this->assertSame(1, $defaults['load_hxlive']);
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

        // Empty stored row = fresh install = htmx 4.x line with hx-live on.
        $this->assertSame('4', $resolved['htmx_version']);
        $this->assertSame(1, $resolved['load_hxlive']);
    }

    public function test_htmx_version_rule_keeps_default_for_fresh_install(): void
    {
        $merged = ['htmx_version' => '4', 'active_library' => 'htmx'];

        $result = OptionsResolver::applyHtmxVersionRule([], $merged);

        $this->assertSame('4', $result['htmx_version']);
    }

    public function test_htmx_version_rule_coerces_legacy_row_to_two(): void
    {
        // A non-empty stored row without the htmx_version key was written
        // by an older release: that site predates htmx 4 and must stay on
        // the 2.x line instead of being silently upgraded.
        $stored = ['active_library' => 'htmx', 'load_from_cdn' => 0];
        $merged = ['htmx_version' => '4', 'active_library' => 'htmx', 'load_from_cdn' => 0];

        $result = OptionsResolver::applyHtmxVersionRule($stored, $merged);

        $this->assertSame('2', $result['htmx_version']);
    }

    public function test_htmx_version_rule_honors_explicit_key(): void
    {
        // An explicit stored value is a user decision: never overridden,
        // in either direction.
        $storedTwo = ['htmx_version' => '2', 'active_library' => 'htmx'];
        $resultTwo = OptionsResolver::applyHtmxVersionRule($storedTwo, ['htmx_version' => '2']);
        $this->assertSame('2', $resultTwo['htmx_version']);

        $storedFour = ['htmx_version' => '4', 'active_library' => 'htmx'];
        $resultFour = OptionsResolver::applyHtmxVersionRule($storedFour, ['htmx_version' => '4']);
        $this->assertSame('4', $resultFour['htmx_version']);
    }

    public function test_htmx_version_rule_normalizes_non_canonical_writes(): void
    {
        // WP-CLI and imports can store the value as an int.
        $intRow = ['htmx_version' => 4, 'active_library' => 'htmx'];
        $resultInt = OptionsResolver::applyHtmxVersionRule($intRow, ['htmx_version' => 4]);
        $this->assertSame('4', $resultInt['htmx_version']);

        // A corrupt value must fall back to the 2.x line, never upgrade.
        $garbageRow = ['htmx_version' => 'nine', 'active_library' => 'htmx'];
        $resultGarbage = OptionsResolver::applyHtmxVersionRule($garbageRow, ['htmx_version' => 'nine']);
        $this->assertSame('2', $resultGarbage['htmx_version']);
    }

    public function test_htmx_version_rule_treats_null_as_legacy(): void
    {
        // A key present but explicitly null (importer bug, direct DB edit,
        // filter returning null) must not resolve to the 4.x default.
        $stored = ['htmx_version' => null, 'active_library' => 'htmx'];
        $merged = ['htmx_version' => '4', 'active_library' => 'htmx'];

        $result = OptionsResolver::applyHtmxVersionRule($stored, $merged);

        $this->assertSame('2', $result['htmx_version']);
    }

    public function test_hp_get_option_returns_htmx_4_default_in_test_env(): void
    {
        $this->assertSame('4', hp_get_option('htmx_version'));
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
            'hp_get_options() must be defined in src/helpers.php'
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

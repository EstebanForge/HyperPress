<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use HyperPress\DatastarCsp;
use HyperPress\OptionsResolver;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for DatastarCsp pure logic. The WP shims in tests/bootstrap
 * are inert (apply_filters passes values through, is_admin() is always true),
 * so filter-override and hook-wiring behavior is covered by the container
 * e2e run, not here.
 */
final class DatastarCspTest extends TestCase
{
    /**
     * The nonce is cached in a private static; reset it between tests so a
     * generated value from one test cannot leak into the next.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $nonce = new \ReflectionProperty(DatastarCsp::class, 'nonce');
        $nonce->setValue(null, null);
    }

    public function test_datastar_csp_defaults_to_off(): void
    {
        $defaults = OptionsResolver::defaults();

        $this->assertArrayHasKey('datastar_csp', $defaults);
        $this->assertSame(0, $defaults['datastar_csp']);
    }

    public function test_is_enabled_is_false_for_fresh_defaults(): void
    {
        // get_option returns [] in the test shim, so hp_get_options()
        // resolves to defaults: datastar active, datastar_csp off.
        $this->assertFalse(DatastarCsp::isEnabled());
    }

    public function test_nonce_is_32_lowercase_hex_chars(): void
    {
        $nonce = DatastarCsp::nonce();

        $this->assertSame(32, strlen($nonce));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $nonce);
    }

    public function test_nonce_is_stable_within_a_request(): void
    {
        $this->assertSame(DatastarCsp::nonce(), DatastarCsp::nonce());
    }

    public function test_html_attribute_escapes_and_carries_the_nonce(): void
    {
        $nonce = str_repeat('a', 32);

        $this->assertSame(' data-nonce="' . $nonce . '"', DatastarCsp::htmlAttribute($nonce));
    }

    public function test_with_nonce_adds_the_request_nonce(): void
    {
        $attributes = DatastarCsp::withNonce(['id' => 'x']);

        $this->assertSame('x', $attributes['id']);
        $this->assertSame(DatastarCsp::nonce(), $attributes['nonce']);
    }

    public function test_with_nonce_honors_an_explicit_nonce(): void
    {
        $attributes = DatastarCsp::withNonce([], str_repeat('b', 32));

        $this->assertSame(str_repeat('b', 32), $attributes['nonce']);
    }

    public function test_is_valid_nonce_accepts_token_shapes(): void
    {
        $this->assertTrue(DatastarCsp::isValidNonce(str_repeat('a', 32)));
        $this->assertTrue(DatastarCsp::isValidNonce('Ab3-_+/=x'));
    }

    public function test_is_valid_nonce_rejects_unsafe_values(): void
    {
        // Empty: Datastar 1.0.3 throws on data-nonce="".
        $this->assertFalse(DatastarCsp::isValidNonce(''));
        // Policy injection: quote/semicolon/whitespace break out of the
        // 'nonce-...' token or add directives.
        $this->assertFalse(DatastarCsp::isValidNonce("abc'; script-src 'self'"));
        $this->assertFalse(DatastarCsp::isValidNonce('ab cd'));
        $this->assertFalse(DatastarCsp::isValidNonce("ab\ncd"));
        // Absurd length.
        $this->assertFalse(DatastarCsp::isValidNonce(str_repeat('a', 129)));
    }

    public function test_header_value_is_strict_script_src_only(): void
    {
        $nonce = str_repeat('c', 32);

        $this->assertSame(
            "script-src 'self' 'nonce-{$nonce}'",
            DatastarCsp::headerValue($nonce)
        );
    }

    public function test_header_value_allows_jsdelivr_in_cdn_mode(): void
    {
        $nonce = str_repeat('d', 32);

        $this->assertSame(
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
            DatastarCsp::headerValue($nonce, true)
        );
    }

    public function test_boot_runs_clean_while_disabled(): void
    {
        // Defaults: CSP off, so boot must be a no-op. The WP shim silently
        // accepts every add_filter call, so hook presence is verified in the
        // container e2e; here we only prove boot() runs without error while
        // disabled.
        (new DatastarCsp())->boot();

        $this->assertFalse(DatastarCsp::isEnabled());
    }
}

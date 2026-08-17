<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the legacy nonce deprecation in hp_validate_request() (audit L8).
 *
 * The legacy 'hxwp_nonce' action is still accepted for old clients but every
 * legacy-verified request must surface a deprecation notice (via
 * _doing_it_wrong) and fire 'hyperpress/nonce/legacy_verified'. A request
 * verified with the current 'hyperpress_nonce' action must NOT warn.
 *
 * Brain Monkey constraint (documented in RenderStrictModeTest): this suite's
 * bootstrap pre-defines apply_filters/apply_filters_deprecated as
 * passthroughs, so the notice-suppression filter cannot be intercepted; the
 * tested behavior is the doing-it-wrong call and the action, with the filter
 * left at its passthrough default (true).
 */
class LegacyNonceDeprecationTest extends TestCase
{
    /**
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private array $wrongCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrongCalls = [];

        Functions\when('sanitize_key')->alias(static function ($key): string {
            $key = strtolower((string) $key);

            return preg_replace('/[^a-z0-9_-]/', '', $key);
        });
        Functions\when('wp_unslash')->alias(static function ($value) {
            return $value;
        });
        // sanitize_text_field is pre-defined by tests/bootstrap.php.
        Functions\when('_doing_it_wrong')->alias(function (string $fn, string $message, string $version): void {
            $this->wrongCalls[] = [$fn, $message, $version];
        });
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        unset($_SERVER['HTTP_X_WP_NONCE'], $GLOBALS['__hp_test_nonce_valid_actions']);
        parent::tearDown();
    }

    private function stubVerify(array $validActions): void
    {
        // wp_verify_nonce is a pre-bootstrap passthrough (see bootstrap.php
        // docblock); per-action validity is driven through its global override.
        $GLOBALS['__hp_test_nonce_valid_actions'] = $validActions;
    }

    /**
     * Legacy nonce verifies (and the current one does not): request passes,
     * deprecation notice fires, legacy action fires.
     */
    public function testLegacyNonceWarnsAndPasses(): void
    {
        $_REQUEST['_wpnonce'] = 'legacynonce';
        $this->stubVerify(['hxwp_nonce']);
        Actions\expectDone('hyperpress/nonce/legacy_verified')->once();

        $this->assertTrue(hp_validate_request());
        $this->assertCount(1, $this->wrongCalls, 'legacy verification must emit exactly one doing-it-wrong');
        $this->assertSame('hp_validate_request', $this->wrongCalls[0][0]);
        $this->assertStringContainsString('hxwp_nonce', $this->wrongCalls[0][1]);
    }

    /**
     * Current nonce verifies: request passes with no deprecation noise.
     */
    public function testCurrentNonceDoesNotWarn(): void
    {
        $_REQUEST['_wpnonce'] = 'freshnonce';
        $this->stubVerify(['hyperpress_nonce']);

        $this->assertTrue(hp_validate_request());
        $this->assertSame([], $this->wrongCalls, 'current-action verification must not warn');
    }

    /**
     * Neither action verifies: rejected, no warning (a failed legacy nonce is
     * just an invalid nonce, not a deprecation event).
     */
    public function testInvalidNonceRejectsSilently(): void
    {
        $_REQUEST['_wpnonce'] = 'garbage';
        $this->stubVerify([]);

        $this->assertFalse(hp_validate_request());
        $this->assertSame([], $this->wrongCalls);
    }
}

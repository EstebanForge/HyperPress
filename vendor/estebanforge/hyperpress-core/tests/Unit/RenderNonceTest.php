<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use Brain\Monkey\Functions;
use HyperPress\Render;
use PHPUnit\Framework\TestCase;

/**
 * Characterization + security tests for Render::validNonce().
 *
 * Locks the nonce-check behavior on the /wp-html/v1/ render path:
 *  - missing nonce de-authenticates and rejects (preserved).
 *  - a valid request-param nonce passes (preserved).
 *  - a valid X-WP-Nonce header passes (preserved).
 *  - an INVALID nonce must de-authenticate exactly like a missing one
 *    (security regression guard; was the inverted-posture bug where a bad
 *    nonce was more trusted than none).
 *
 * validNonce() is exercised via reflection. loadTemplate() dispatches
 * rendering and die()s, so it is not unit-testable in isolation.
 *
 * wp_verify_nonce is a pre-bootstrap passthrough Patchwork cannot override,
 * so it is hand-rolled controllable via $GLOBALS['__hp_test_nonce_valid']
 * (default true). All other WP functions here are Brain Monkey stubs.
 */
class RenderNonceTest extends TestCase
{
    /** @var array{called: bool, id: int|string|null} */
    private array $setUserCalls;

    /** @var array<string, mixed> */
    private array $requestBackup;

    /** @var array<string, mixed> */
    private array $serverBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestBackup = $_REQUEST;
        $this->serverBackup = $_SERVER;
        $_REQUEST = [];
        unset($_SERVER['HTTP_X_WP_NONCE']);

        $this->setUserCalls = ['called' => false, 'id' => null];
        unset($GLOBALS['__hp_test_nonce_valid']);

        // wp_set_current_user is not pre-defined in the bootstrap, so Brain
        // Monkey can stub it. Record the id so tests can assert who
        // validNonce() switched the request to.
        Functions\when('wp_set_current_user')->alias(function ($id): void {
            $this->setUserCalls['called'] = true;
            $this->setUserCalls['id'] = $id;
        });
        Functions\when('sanitize_key')->alias(static function ($key): string {
            $key = strtolower((string) $key);

            return preg_replace('/[^a-z0-9_-]/', '', $key);
        });
        Functions\when('wp_unslash')->alias(static function ($value) {
            return $value;
        });
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->requestBackup;
        $_SERVER = $this->serverBackup;
        unset($GLOBALS['__hp_test_nonce_valid']);
        parent::tearDown();
    }

    /**
     * Invoke protected validNonce() on a fresh Render instance.
     */
    private function validNonce(): bool
    {
        $render = new Render();
        $method = new \ReflectionMethod($render, 'validNonce');

        return (bool) $method->invoke($render);
    }

    /**
     * No nonce param and no nonce header: de-authenticate and reject.
     * Preserved behavior.
     */
    public function testMissingNonceDeauthenticatesAndReturnsFalse(): void
    {
        unset($_REQUEST['_wpnonce'], $_SERVER['HTTP_X_WP_NONCE']);

        $this->assertFalse($this->validNonce());
        $this->assertTrue($this->setUserCalls['called'], 'wp_set_current_user is invoked');
        $this->assertSame(0, $this->setUserCalls['id'], 'user is switched to 0 (unauthenticated)');
    }

    /**
     * A valid _wpnonce passes and leaves the user context untouched.
     * Preserved behavior.
     */
    public function testValidRequestNonceReturnsTrueAndKeepsUser(): void
    {
        $_REQUEST['_wpnonce'] = 'goodnonce';
        $GLOBALS['__hp_test_nonce_valid'] = true;

        $this->assertTrue($this->validNonce());
        $this->assertFalse($this->setUserCalls['called'], 'valid nonce must not clear the user');
    }

    /**
     * A valid X-WP-Nonce header passes when the request param is absent.
     * Preserved behavior.
     */
    public function testValidHeaderNonceReturnsTrueAndKeepsUser(): void
    {
        $_SERVER['HTTP_X_WP_NONCE'] = 'goodheader';
        $GLOBALS['__hp_test_nonce_valid'] = true;

        $this->assertTrue($this->validNonce());
        $this->assertFalse($this->setUserCalls['called']);
    }

    /**
     * SECURITY: an invalid nonce must de-authenticate exactly like a missing
     * one. Before the fix, validNonce() cleared the user only on a MISSING
     * nonce, so a GET with ?_wpnonce=garbage rendered fully authenticated
     * (a bad nonce was more trusted than none). This test fails on the unfixed
     * code and passes after.
     */
    public function testInvalidNonceDeauthenticatesAndReturnsFalse(): void
    {
        $_REQUEST['_wpnonce'] = 'garbage';
        $GLOBALS['__hp_test_nonce_valid'] = false;

        $this->assertFalse($this->validNonce());
        $this->assertTrue($this->setUserCalls['called'], 'invalid nonce must clear the user');
        $this->assertSame(0, $this->setUserCalls['id']);
    }
}

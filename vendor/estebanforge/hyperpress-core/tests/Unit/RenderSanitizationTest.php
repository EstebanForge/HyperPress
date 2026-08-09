<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use Brain\Monkey\Functions;
use HyperPress\Render;
use PHPUnit\Framework\TestCase;

/**
 * Characterization tests for Render request-parameter handling.
 *
 * Locks the security fixes applied to the /wp-html/v1/ render path:
 *  - sanitizeParams() rebuilds a fresh array, so an unsanitized original key
 *    (mixed case or special chars) never survives into the template, and a key
 *    that sanitizes to '' is dropped instead of collapsing onto one empty key.
 *  - Request params are sourced from GET + POST only, never $_COOKIE, and POST
 *    overrides GET on collision.
 *
 * loadTemplate() dispatches rendering and calls die(), and depends on dozens of
 * WordPress request functions, so it is not unit-testable in isolation.
 * sanitizeParams() is exercised directly via reflection; the GET/POST sourcing
 * is verified through the sourceRequestParams() seam.
 */
class RenderSanitizationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $getBackup;

    /** @var array<string, mixed> */
    private array $postBackup;

    /** @var array<string, mixed> */
    private array $cookieBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->cookieBackup = $_COOKIE;
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        // Stub the two WP functions Render::sanitizeParams() depends on via
        // Brain Monkey. Unlike apply_filters / add_action (defined as
        // passthrough globals before Brain Monkey loads, which Patchwork then
        // cannot reach — see tests/Unit/Admin/OptionsTest.php), these are not
        // pre-defined, so Brain Monkey can take them over directly.
        Functions\when('sanitize_key')->alias(static function ($key): string {
            $key = strtolower((string) $key);

            return preg_replace('/[^a-z0-9_-]/', '', $key);
        });
        Functions\when('apply_filters_deprecated')->alias(static function ($tag, $args, $version, $replacement = null, $message = null) {
            return is_array($args) && isset($args[0]) ? $args[0] : null;
        });
    }

    protected function tearDown(): void
    {
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_COOKIE = $this->cookieBackup;
        parent::tearDown();
    }

    /**
     * Invoke the private sanitizeParams() against a fresh Render instance.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>|false
     */
    private function sanitizeParams(array $input): array|false
    {
        $render = new Render();
        $method = new \ReflectionMethod($render, 'sanitizeParams');

        /** @var array<string, mixed>|false $result */
        $result = $method->invoke($render, $input);

        return $result;
    }

    /**
     * A mixed-case / special-character key is sanitized and the raw key never
     * remains, so a template cannot read the unsanitized variant.
     */
    public function testUnsanitizedOriginalKeyDoesNotSurvive(): void
    {
        $result = $this->sanitizeParams(['Name' => '<script>alert(1)</script>']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('Name', $result);
        // Value is sanitized: the script tag is stripped by sanitize_text_field.
        $this->assertSame('', $result['name']);
    }

    /**
     * Scalar values pass through sanitize_text_field; already-clean keys keep
     * their value.
     */
    public function testScalarValuesAreSanitized(): void
    {
        $result = $this->sanitizeParams(['heading' => '<b>hello</b>']);

        $this->assertSame(['heading' => 'hello'], $result);
    }

    /**
     * A key that sanitizes to an empty string is dropped instead of collapsing
     * many raw keys onto a single '' key.
     */
    public function testKeyThatSanitizesToEmptyIsDropped(): void
    {
        $result = $this->sanitizeParams(['!!!' => 'kept', 'good' => 'v']);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('', $result);
        $this->assertArrayNotHasKey('!!!', $result);
        $this->assertSame('v', $result['good']);
    }

    /**
     * Array values (multi-value form elements) are sanitized element by element.
     */
    public function testArrayValuesAreSanitizedElementWise(): void
    {
        $result = $this->sanitizeParams(['tags' => ['<b>a</b>', '<i>b</i>']]);

        $this->assertSame(['tags' => ['a', 'b']], $result);
    }

    /**
     * Nested arrays must be sanitized recursively so inner values survive
     * instead of being silently lost. Fails on the one-level-deep code;
     * passes after recursive sanitization.
     */
    public function testNestedArrayValuesAreSanitizedRecursively(): void
    {
        $result = $this->sanitizeParams(['matrix' => [['<b>x</b>'], ['<i>y</br>']]]);

        $this->assertSame(['matrix' => [['x'], ['y']]], $result);
    }

    /**
     * Nonces are stripped from the bag passed to templates.
     */
    public function testNoncesAreRemoved(): void
    {
        $result = $this->sanitizeParams([
            '_wpnonce' => 'abc',
            'hyperpress_nonce' => 'def',
            'keep' => 'v',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('_wpnonce', $result);
        $this->assertArrayNotHasKey('hyperpress_nonce', $result);
        $this->assertSame('v', $result['keep']);
    }

    /**
     * Nonces are stripped from the bag passed to templates.
     */
    public function testNestedArrayKeysAreSanitizedRecursively(): void
    {
        $input = [
            'filter' => [
                'Status' => '<script>bad</script>',
                'Nested' => [
                    'Deep_Key!' => 'hello',
                ],
            ],
        ];

        $result = $this->sanitizeParams($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('filter', $result);
        $this->assertIsArray($result['filter']);
        $this->assertArrayHasKey('status', $result['filter']);
        $this->assertArrayNotHasKey('Status', $result['filter']);
        $this->assertSame('', $result['filter']['status']);
        $this->assertArrayHasKey('deep_key', $result['filter']['nested']);
        $this->assertArrayNotHasKey('Deep_Key!', $result['filter']['nested']);
        $this->assertSame('hello', $result['filter']['nested']['deep_key']);
    }

    /**
     * Params are sourced from GET and POST only. $_COOKIE never pollutes the
     * bag, and POST takes precedence over GET on key collision.
     */
    public function testRequestParamsSourcedFromGetAndPostNotCookie(): void
    {
        $_GET = ['from_get' => 'g', 'shared' => 'get_value'];
        $_POST = ['from_post' => 'p', 'shared' => 'post_value'];
        $_COOKIE = ['from_cookie' => 'c', 'shared' => 'cookie_value'];

        $render = new Render();
        $method = new \ReflectionMethod($render, 'sourceRequestParams');
        /** @var array<string, mixed> $result */
        $result = $method->invoke($render);

        $this->assertSame('g', $result['from_get']);
        $this->assertSame('p', $result['from_post']);
        $this->assertArrayNotHasKey('from_cookie', $result);
        // POST overrides GET on conflict.
        $this->assertSame('post_value', $result['shared']);
    }
}

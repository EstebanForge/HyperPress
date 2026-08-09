<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\RestApi;
use HyperBlocks_Testing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for the /block-fields REST permission gate.
 *
 * /block-fields was previously public (permission_callback => __return_true),
 * which let an anonymous caller force an uncached block-tree scan by passing
 * arbitrary name values. It now requires edit_posts, matching /render-preview.
 */
class RestApiPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        unset($GLOBALS['__hb_test_current_user_can']);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        unset($GLOBALS['__hb_test_current_user_can']);
        parent::tearDown();
    }

    /**
     * Capture the args registered for a given route, or null if not registered.
     */
    private function routeArgs(string $route): ?array
    {
        foreach (HyperBlocks_Testing_Registry::getRestRoutes() as $registered) {
            if ($registered['route'] === $route) {
                return $registered['args'];
            }
        }

        return null;
    }

    /**
     * /block-fields is registered and its permission_callback is a real gate,
     * not the public __return_true shorthand.
     */
    public function testBlockFieldsEndpointIsRegisteredWithAGate(): void
    {
        (new RestApi())->registerRoutes();

        $args = $this->routeArgs('/block-fields');
        $this->assertNotNull($args, '/block-fields route is registered');
        $this->assertArrayHasKey('permission_callback', $args);
        $this->assertNotSame('__return_true', $args['permission_callback']);
        $this->assertIsCallable($args['permission_callback']);
    }

    /**
     * A caller without edit_posts is denied; a caller with it is allowed.
     */
    public function testBlockFieldsDeniesWithoutEditPostsAndAllowsWithIt(): void
    {
        (new RestApi())->registerRoutes();
        $args = $this->routeArgs('/block-fields');
        $this->assertNotNull($args);
        $permission = $args['permission_callback'];

        $GLOBALS['__hb_test_current_user_can'] = false;
        $this->assertFalse($permission());

        $GLOBALS['__hb_test_current_user_can'] = true;
        $this->assertTrue($permission());
    }

    /**
     * /render-preview still requires edit_posts (unchanged behavior, sanity).
     */
    public function testRenderPreviewStillRequiresEditPosts(): void
    {
        (new RestApi())->registerRoutes();
        $args = $this->routeArgs('/render-preview');
        $this->assertNotNull($args);
        $permission = $args['permission_callback'];

        $GLOBALS['__hb_test_current_user_can'] = false;
        $this->assertFalse($permission());

        $GLOBALS['__hb_test_current_user_can'] = true;
        $this->assertTrue($permission());
    }
}

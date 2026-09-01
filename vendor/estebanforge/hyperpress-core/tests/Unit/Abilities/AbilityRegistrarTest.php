<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit\Abilities;

use HyperPress\Abilities\AbilityRegistrar;
use HyperPress\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test the HyperPress Abilities registrar payload builders.
 *
 * The WP_Ability registration path needs WordPress core, so the tests target
 * the pure payload methods and the arg builder; the hooks themselves are
 * covered end to end in the dev environment (see
 * docs/ABILITIES-API-ADOPTION-PLAN.md, Phase 1 verification).
 */
class AbilityRegistrarTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['__hp_registered_ability_categories'], $GLOBALS['__hp_registered_abilities']);
        parent::tearDown();
    }

    public function test_config_payload_returns_canonical_shape(): void
    {
        $payload = AbilityRegistrar::configPayload();

        $this->assertSame('datastar', $payload['active_library']);
        $this->assertSame('4', $payload['htmx_version']);
        $this->assertSame('wp-html/v1', $payload['endpoint']);
        $this->assertSame('hypermedia', $payload['template_dir']);
        $this->assertSame(['.hp.php', '.hm.php', '.hb.php'], $payload['template_extensions']);
        $this->assertSame(Config::VERSION, $payload['version']);
    }

    public function test_extension_status_filters_load_extension_keys_only(): void
    {
        $status = AbilityRegistrar::extensionStatusFromOptions([
            'active_library'              => 'datastar',
            'load_from_cdn'               => 1,
            'load_extension_sse'          => 1,
            'load_extension_head_support' => 0,
            'load_extension_alpine-morph' => 'yes',
        ]);

        $this->assertSame(
            [
                'load_extension_alpine-morph' => true,
                'load_extension_head_support' => false,
                'load_extension_sse'          => true,
            ],
            $status
        );
    }

    public function test_extension_status_is_empty_when_no_extension_keys_exist(): void
    {
        $this->assertSame([], AbilityRegistrar::extensionStatusFromOptions([
            'active_library' => 'datastar',
        ]));
    }

    public function test_endpoints_payload_scans_theme_and_legacy_dirs(): void
    {
        $theme_dir = sys_get_temp_dir() . '/hp-abilities-' . uniqid('', true);
        mkdir($theme_dir . '/hypermedia/parts', 0777, true);
        mkdir($theme_dir . '/htmx-templates', 0777, true);
        file_put_contents($theme_dir . '/hypermedia/list.hp.php', '<?php // template');
        file_put_contents($theme_dir . '/hypermedia/parts/header.hp.php', '<?php // template');
        file_put_contents($theme_dir . '/hypermedia/notes.txt', 'ignored');
        file_put_contents($theme_dir . '/htmx-templates/old-page.htmx.php', '<?php // legacy');

        try {
            $entries = AbilityRegistrar::endpointsPayload($theme_dir);
        } finally {
            self::removeDir($theme_dir);
        }

        // Theme entries come before legacy entries; each group is name-sorted.
        $this->assertSame(
            ['list', 'parts/header', 'old-page'],
            array_column($entries, 'name')
        );
        $this->assertSame(['theme', 'theme', 'legacy'], array_column($entries, 'source'));
        $this->assertSame(
            'http://localhost/wp-html/v1/parts/header',
            $entries[1]['url']
        );
        $this->assertSame('parts/header.hp.php', $entries[1]['file']);
    }

    public function test_endpoints_payload_ignores_missing_directories(): void
    {
        $this->assertSame(
            [],
            AbilityRegistrar::endpointsPayload(sys_get_temp_dir() . '/hp-abilities-does-not-exist')
        );
    }

    public function test_ability_args_forces_explicit_hidden_meta(): void
    {
        $args = AbilityRegistrar::abilityArgs([
            'label' => 'Test',
        ]);

        // The exposure filters pass through untouched in the test env, so
        // the registered default must be fully hidden.
        $this->assertFalse($args['meta']['show_in_rest']);
        $this->assertArrayNotHasKey('mcp', $args['meta']);

        // Annotations are explicit and read-only: destructive defaults to
        // true in the Abilities API, which would map the ability to DELETE.
        $this->assertTrue($args['meta']['annotations']['readonly']);
        $this->assertFalse($args['meta']['annotations']['destructive']);
        $this->assertTrue($args['meta']['annotations']['idempotent']);
    }

    public function test_init_is_a_noop_without_the_abilities_api(): void
    {
        if (class_exists(\WP_Ability::class)) {
            $this->markTestSkipped('Abilities API is present in this environment.');
        }

        $this->assertNull(AbilityRegistrar::init());
    }

    public function test_register_abilities_registers_three_hidden_read_only_abilities(): void
    {
        $GLOBALS['__hp_registered_ability_categories'] = [];
        $GLOBALS['__hp_registered_abilities'] = [];

        AbilityRegistrar::registerCategories();
        AbilityRegistrar::registerAbilities();

        $this->assertArrayHasKey(AbilityRegistrar::CATEGORY, $GLOBALS['__hp_registered_ability_categories']);

        $registered = $GLOBALS['__hp_registered_abilities'];

        $this->assertSame(
            [
                'hyperpress/get-config',
                'hyperpress/list-endpoints',
                'hyperpress/get-extension-status',
            ],
            array_keys($registered)
        );

        foreach ($registered as $name => $args) {
            $this->assertSame(AbilityRegistrar::CATEGORY, $args['category'], $name);
            $this->assertArrayNotHasKey('input_schema', $args, $name);
            $this->assertIsCallable($args['execute_callback'], $name);
            $this->assertIsCallable($args['permission_callback'], $name);

            // Register-everything, expose-nothing posture.
            $this->assertFalse($args['meta']['show_in_rest'], $name);
            $this->assertArrayNotHasKey('mcp', $args['meta'], $name);
            $this->assertTrue($args['meta']['annotations']['readonly'], $name);
            $this->assertFalse($args['meta']['annotations']['destructive'], $name);
            $this->assertTrue($args['meta']['annotations']['idempotent'], $name);
        }

        // get-config is the only site-level surface; the other two are
        // content-level reads.
        $this->assertSame(
            [AbilityRegistrar::class, 'currentUserCanManageOptions'],
            $registered['hyperpress/get-config']['permission_callback']
        );
        $this->assertSame(
            [AbilityRegistrar::class, 'currentUserCanEditPosts'],
            $registered['hyperpress/list-endpoints']['permission_callback']
        );
    }

    /**
     * Remove a test fixture directory tree.
     *
     * @param string $dir Directory to remove.
     */
    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}

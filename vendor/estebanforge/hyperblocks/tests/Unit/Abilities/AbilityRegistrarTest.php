<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit\Abilities;

use HyperBlocks\Abilities\AbilityRegistrar;
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\BlockOperations;
use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks_Testing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the HyperBlocks Abilities registrar and the shared
 * BlockOperations service behind both the REST and ability surfaces.
 */
class AbilityRegistrarTest extends TestCase
{
    private string $tmp_dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        $GLOBALS['__hb_registered_abilities'] = [];
        $GLOBALS['__hb_registered_ability_categories'] = [];
        unset($GLOBALS['__hb_test_current_user_can']);
    }

    protected function tearDown(): void
    {
        if ($this->tmp_dir !== '' && is_dir($this->tmp_dir)) {
            self::removeDir($this->tmp_dir);
        }

        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        unset(
            $GLOBALS['__hb_test_current_user_can'],
            $GLOBALS['__hb_registered_abilities'],
            $GLOBALS['__hb_registered_ability_categories']
        );
        parent::tearDown();
    }

    public function test_list_blocks_returns_fluent_and_json_entries(): void
    {
        $block = Block::make('Test Alpha')
            ->setName('test/alpha')
            ->setRenderTemplate('<?php echo "alpha-ok"; ?>');
        Registry::getInstance()->registerFluentBlock($block);

        $this->makeJsonFixture('test/cta', ['heading' => ['type' => 'string', 'default' => 'Hi']]);

        $entries = BlockOperations::listBlocks();

        $byName = array_column($entries, null, 'name');

        $this->assertArrayHasKey('test/alpha', $byName);
        $this->assertSame('fluent', $byName['test/alpha']['source']);
        $this->assertSame('Test Alpha', $byName['test/alpha']['title']);
        $this->assertTrue($byName['test/alpha']['has_render_template']);

        $this->assertArrayHasKey('test/cta', $byName);
        $this->assertSame('json', $byName['test/cta']['source']);
        $this->assertTrue($byName['test/cta']['has_render_template']);
    }

    public function test_get_fields_matches_for_fluent_and_json_blocks(): void
    {
        $block = Block::make('Test Alpha')
            ->setName('test/alpha')
            ->addFields([
                Field::make('text', 'heading', 'Heading')->setDefault('Welcome'),
            ]);
        Registry::getInstance()->registerFluentBlock($block);

        $this->makeJsonFixture('test/cta', ['heading' => ['type' => 'string', 'default' => 'Hi']]);

        $fluentFields = BlockOperations::getFields('test/alpha');
        $this->assertIsArray($fluentFields);
        $this->assertSame('heading', $fluentFields[0]['name']);

        $jsonFields = BlockOperations::getFields('test/cta');
        $this->assertIsArray($jsonFields);
        $this->assertSame('heading', $jsonFields[0]['name']);
        $this->assertSame('Heading', $jsonFields[0]['label']);
        $this->assertSame('Hi', $jsonFields[0]['default']);

        $this->assertNull(BlockOperations::getFields('test/unknown'));
    }

    public function test_get_fields_dedupes_group_and_block_field_collisions(): void
    {
        $group = FieldGroup::make('Common', 'common')
            ->addFields([Field::make('text', 'heading', 'Group Heading')->setDefault('group-default')]);
        Registry::getInstance()->registerFieldGroup($group);

        $block = Block::make('Collide')
            ->setName('test/collide')
            ->addFieldGroup('common')
            ->addFields([Field::make('text', 'heading', 'Block Heading')->setDefault('block-default')]);
        Registry::getInstance()->registerFluentBlock($block);

        $fields = BlockOperations::getFields('test/collide');

        // One entry, block definition wins (same rule preview() applies).
        $this->assertCount(1, $fields);
        $this->assertSame('Block Heading', $fields[0]['label']);
        $this->assertSame('block-default', $fields[0]['default']);
    }

    public function test_register_json_blocks_filter_feeds_lookup_and_inventory(): void
    {
        $dir = sys_get_temp_dir() . '/hb-filtered-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/block.json', (string) json_encode([
            'name'       => 'test/filtered',
            'title'      => 'Filtered',
            'hyperblocks' => true,
        ]));
        $this->tmp_dir = $dir; // tearDown cleanup; deliberately NOT a registered block path

        add_filter('hyperblocks/blocks/register_json_blocks', static fn (): array => [$dir]);

        // Lookup (get-block-fields path) and inventory (list-blocks path)
        // must agree on filter-provided blocks.
        $this->assertNotNull(Registry::getInstance()->findJsonBlockPath('test/filtered'));

        $names = array_column(BlockOperations::listBlocks(), 'name');
        $this->assertContains('test/filtered', $names);
    }

    public function test_preview_renders_fluent_template(): void
    {
        $block = Block::make('Test Alpha')
            ->setName('test/alpha')
            ->setRenderTemplate('<?php echo "alpha-ok"; ?>');
        Registry::getInstance()->registerFluentBlock($block);

        $result = BlockOperations::preview('test/alpha', []);

        $this->assertSame('ok', $result['status']);
        $this->assertStringContainsString('alpha-ok', $result['html']);
    }

    public function test_preview_renders_json_block_render_php(): void
    {
        $this->makeJsonFixture('test/cta', ['heading' => ['type' => 'string', 'default' => 'Hi']]);

        $result = BlockOperations::preview('test/cta', ['heading' => 'hello']);

        $this->assertSame('ok', $result['status'], $result['error']);
        $this->assertStringContainsString('fixture-okhello', $result['html']);
    }

    public function test_preview_reports_not_found_for_unknown_block(): void
    {
        $result = BlockOperations::preview('test/missing', []);

        $this->assertSame('not_found', $result['status']);
        $this->assertSame(404, $result['rest_status']);
    }

    public function test_ability_args_default_to_fully_hidden(): void
    {
        $args = AbilityRegistrar::abilityArgs(['label' => 'Test']);

        $this->assertFalse($args['meta']['show_in_rest']);
        $this->assertArrayNotHasKey('mcp', $args['meta']);
        $this->assertTrue($args['meta']['annotations']['readonly']);
        $this->assertFalse($args['meta']['annotations']['destructive']);
        $this->assertTrue($args['meta']['annotations']['idempotent']);
    }

    public function test_ability_args_honor_exposure_filters(): void
    {
        add_filter('hyperblocks/abilities/expose_rest', static fn (): bool => true);
        add_filter('hyperblocks/abilities/mcp_public', static fn (): bool => true);

        $args = AbilityRegistrar::abilityArgs(['label' => 'Test']);

        $this->assertTrue($args['meta']['show_in_rest']);
        $this->assertTrue($args['meta']['mcp']['public']);
    }

    public function test_register_abilities_registers_three_gated_abilities(): void
    {
        AbilityRegistrar::registerCategories();
        AbilityRegistrar::registerAbilities();

        $this->assertArrayHasKey(AbilityRegistrar::CATEGORY, $GLOBALS['__hb_registered_ability_categories']);

        $registered = $GLOBALS['__hb_registered_abilities'];

        $this->assertSame(
            [
                'hyperblocks/list-blocks',
                'hyperblocks/get-block-fields',
                'hyperblocks/render-preview',
            ],
            array_keys($registered)
        );

        foreach ($registered as $name => $args) {
            $this->assertSame(AbilityRegistrar::CATEGORY, $args['category'], $name);
            $this->assertIsCallable($args['execute_callback'], $name);
            $this->assertSame(
                [AbilityRegistrar::class, 'currentUserCanEditPosts'],
                $args['permission_callback'],
                $name
            );

            // Register-everything, expose-nothing posture.
            $this->assertFalse($args['meta']['show_in_rest'], $name);
            $this->assertArrayNotHasKey('mcp', $args['meta'], $name);
            $this->assertFalse($args['meta']['annotations']['destructive'], $name);
            $this->assertTrue($args['meta']['annotations']['idempotent'], $name);
        }

        // render-preview executes a render (readonly false); the other two
        // are pure reads.
        $this->assertFalse($registered['hyperblocks/render-preview']['meta']['annotations']['readonly']);
        $this->assertTrue($registered['hyperblocks/list-blocks']['meta']['annotations']['readonly']);
        $this->assertTrue($registered['hyperblocks/get-block-fields']['meta']['annotations']['readonly']);
    }

    public function test_execute_callbacks_report_unknown_blocks_as_errors(): void
    {
        $fields = AbilityRegistrar::executeGetBlockFields(['name' => 'test/missing']);
        $this->assertInstanceOf(\WP_Error::class, $fields);

        $preview = AbilityRegistrar::executeRenderPreview([
            'blockName'  => 'test/missing',
            'attributes' => [],
        ]);
        $this->assertInstanceOf(\WP_Error::class, $preview);
    }

    public function test_init_is_a_noop_without_the_abilities_api(): void
    {
        if (class_exists(\WP_Ability::class)) {
            $this->markTestSkipped('Abilities API is present in this environment.');
        }

        $this->assertNull(AbilityRegistrar::init());
    }

    /**
     * Create a temp-dir JSON block fixture with the HyperBlocks ownership
     * marker and register its path for discovery.
     *
     * @param string $name       Block name (namespace/slug).
     * @param array  $attributes block.json attribute declarations.
     */
    private function makeJsonFixture(string $name, array $attributes): void
    {
        if ($this->tmp_dir === '') {
            $this->tmp_dir = sys_get_temp_dir() . '/hb-abilities-' . uniqid('', true);
            mkdir($this->tmp_dir, 0777, true);
            Config::registerBlockPath($this->tmp_dir);
        }

        $slug = substr(strrchr($name, '/') ?: $name, 1);
        $dir = $this->tmp_dir . '/' . $slug;
        mkdir($dir, 0777, true);

        file_put_contents($dir . '/block.json', (string) json_encode([
            'name'       => $name,
            'title'      => 'Fixture ' . $name,
            'hyperblocks' => true,
            'attributes' => $attributes,
        ]));
        file_put_contents($dir . '/render.php', '<?php echo "fixture-ok" . ($heading ?? "");');
    }

    /**
     * Remove a test fixture directory tree.
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

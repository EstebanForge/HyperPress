<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\HyperFields;
use HyperFields\Container\PostMetaContainer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class HyperFieldsApiTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('add_filter')->justReturn(true);
        Functions\when('add_menu_page')->justReturn('hook_name');
        Functions\when('add_submenu_page')->justReturn('hook_name');
        Functions\when('register_setting')->justReturn(true);
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('wp_parse_args')->returnArg(1);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('settings_fields')->justReturn('');

        // Mock global variables
        global $pagenow, $hook_suffix;
        $pagenow = 'options-general.php';
        $hook_suffix = 'settings_page_test';
    }

    // ... (skipping to testUpdateFieldOption)

    public function testUpdateFieldOption()
    {
        // Mock existing options
        Functions\when('get_option')->justReturn(['field1' => 'old_value']);

        // Use when to stub update_option and verify result
        Functions\when('update_option')->justReturn(true);

        $result = HyperFields::setFieldValue('test_option', 'field1', 'new_value');
        $this->assertTrue($result);
    }

    public function testUpdateFieldOptionWithNewKey()
    {
        Functions\when('get_option')->justReturn([]);

        Functions\when('update_option')->justReturn(true);

        $result = HyperFields::setFieldValue('test_option', 'new_field', 'new_value');
        $this->assertTrue($result);
    }

    public function testDeleteFieldOption()
    {
        // Mock existing options
        Functions\when('get_option')->justReturn(['field1' => 'value1']);

        // Use when to stub update_option and verify result
        Functions\when('update_option')->justReturn(true);

        $result = HyperFields::deleteFieldOption('test_option', 'field1');
        $this->assertTrue($result);
    }

    public function testDeleteFieldOptionNonExistent()
    {
        // Mock existing options
        Functions\when('get_option')->justReturn(['field1' => 'value1']);

        $result = HyperFields::deleteFieldOption('test_option', 'non_existent');
        $this->assertFalse($result);
    }

    public function testCreatePostMetaContainer()
    {
        $container = HyperFields::createPostMetaContainer('test_meta', 'Test Meta');
        $this->assertInstanceOf(PostMetaContainer::class, $container);
    }

    public function testCreatePostMetaContainerWithPostTypes()
    {
        $container = HyperFields::createPostMetaContainer('test_meta', 'Test Meta');
        $container->where('page');
        
        $this->assertInstanceOf(PostMetaContainer::class, $container);
    }
}
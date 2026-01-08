<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\Field;
use HyperFields\HyperFields;
use HyperFields\OptionsPage;
use HyperFields\OptionsSection;
use HyperFields\Container\PostMetaContainer;
use HyperFields\Container\TermMetaContainer;
use HyperFields\Container\UserMetaContainer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class HyperFieldsTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Mock common WordPress functions
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('add_action')->justReturn(true);
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('add_query_arg')->justReturn('http://example.com/page');
    }

    public function testMakeOptionPage()
    {
        $page = HyperFields::makeOptionPage('Test Page', 'test-page');

        $this->assertInstanceOf(OptionsPage::class, $page);
    }

    public function testMakeField()
    {
        $field = HyperFields::makeField('text', 'test_field', 'Test Field');

        $this->assertInstanceOf(Field::class, $field);
    }

    public function testMakeSection()
    {
        $section = HyperFields::makeSection('test-section', 'Test Section');

        $this->assertInstanceOf(OptionsSection::class, $section);
    }

    public function testMakeSeparator()
    {
        $field = HyperFields::makeSeparator('test_separator');

        $this->assertInstanceOf(Field::class, $field);
    }

    public function testMakeHeading()
    {
        $field = HyperFields::makeHeading('test_heading', 'Test Heading');

        $this->assertInstanceOf(Field::class, $field);
    }

    public function testMakeTabs()
    {
        Functions\when('apply_filters')->returnArg(2);

        $tabs = HyperFields::makeTabs('test_tabs', 'Test Tabs');

        $this->assertInstanceOf(\HyperFields\TabsField::class, $tabs);
    }

    public function testMakeRepeater()
    {
        Functions\when('apply_filters')->returnArg(2);

        $repeater = HyperFields::makeRepeater('test_repeater', 'Test Repeater');

        $this->assertInstanceOf(\HyperFields\RepeaterField::class, $repeater);
    }

    public function testGetOptions()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            if ($option === 'test_option') {
                return ['key' => 'value'];
            }
            return $default;
        });

        $result = HyperFields::getOptions('test_option');

        $this->assertIsArray($result);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testGetOptionsReturnsDefaultWhenNotArray()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            return 'string_value'; // Not an array
        });

        $result = HyperFields::getOptions('test_option');

        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }

    public function testGetOptionsWithCustomDefault()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            return $default;
        });

        $result = HyperFields::getOptions('test_option', ['default' => 'value']);

        $this->assertEquals(['default' => 'value'], $result);
    }

    public function testGetFieldValue()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            if ($option === 'test_option') {
                return ['field1' => 'value1'];
            }
            return $default;
        });

        $result = HyperFields::getFieldValue('test_option', 'field1');

        $this->assertEquals('value1', $result);
    }

    public function testGetFieldValueReturnsDefault()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            return [];
        });

        $result = HyperFields::getFieldValue('test_option', 'nonexistent', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function testSetFieldValue()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            if ($option === 'test_option') {
                return ['existing' => 'value'];
            }
            return $default;
        });

        Functions\when('update_option')->alias(function($option, $value) {
            return true;
        });

        $result = HyperFields::setFieldValue('test_option', 'new_field', 'new_value');

        $this->assertTrue($result);
    }

    public function testDeleteFieldOption()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            if ($option === 'test_option') {
                return ['field1' => 'value1', 'field2' => 'value2'];
            }
            return $default;
        });

        Functions\when('update_option')->alias(function($option, $value) {
            return true;
        });

        $result = HyperFields::deleteFieldOption('test_option', 'field1');

        $this->assertTrue($result);
    }

    public function testDeleteFieldOptionReturnsFalseWhenFieldNotFound()
    {
        Functions\when('get_option')->alias(function($option, $default = []) {
            if ($option === 'test_option') {
                return ['field1' => 'value1'];
            }
            return $default;
        });

        $result = HyperFields::deleteFieldOption('test_option', 'nonexistent');

        $this->assertFalse($result);
    }

    public function testCreatePostMetaContainer()
    {
        $container = HyperFields::createPostMetaContainer('test_meta', 'Test Meta Box');

        $this->assertInstanceOf(PostMetaContainer::class, $container);
    }

    public function testMakePostMeta()
    {
        $container = HyperFields::makePostMeta('test_meta', 'Test Meta Box');

        $this->assertInstanceOf(PostMetaContainer::class, $container);
    }

    public function testMakeTermMeta()
    {
        $container = HyperFields::makeTermMeta('test_term_meta', 'Test Term Meta');

        $this->assertInstanceOf(TermMetaContainer::class, $container);
    }

    public function testMakeUserMeta()
    {
        $container = HyperFields::makeUserMeta('test_user_meta', 'Test User Meta');

        $this->assertInstanceOf(UserMetaContainer::class, $container);
    }

    public function testRegisterOptionsPageWithMinimalConfig()
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('add_query_arg')->justReturn('http://example.com/page');
        Functions\when('get_option')->justReturn([]);

        $config = [
            'title' => 'Test Page',
            'slug' => 'test-page',
        ];

        HyperFields::registerOptionsPage($config);

        $this->assertTrue(true); // No exception means success
    }

    public function testRegisterOptionsPageWithFullConfig()
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('add_query_arg')->justReturn('http://example.com/page');
        Functions\when('get_option')->justReturn([]);

        $config = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'menu_title' => 'Menu Title',
            'parent_slug' => 'options-general.php',
            'capability' => 'manage_options',
            'option_name' => 'test_options',
            'footer_content' => '<p>Footer</p>',
            'sections' => [
                [
                    'id' => 'section1',
                    'title' => 'Section 1',
                    'description' => 'Description',
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'field1',
                            'label' => 'Field 1',
                            'default' => 'default_value',
                            'placeholder' => 'Enter text',
                            'help' => 'Help text',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'field2',
                            'label' => 'Field 2',
                            'options' => ['opt1' => 'Option 1'],
                        ],
                        [
                            'type' => 'html',
                            'name' => 'field3',
                            'html_content' => '<p>HTML Content</p>',
                        ],
                    ],
                ],
            ],
        ];

        HyperFields::registerOptionsPage($config);

        $this->assertTrue(true); // No exception means success
    }

    public function testRegisterOptionsPageSkipsInvalidSections()
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('get_option')->justReturn([]);

        $config = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'sections' => [
                ['id' => 'valid', 'title' => 'Valid Section'],
                ['title' => 'Missing ID'], // Should be skipped
                ['id' => 'no-title'], // Should be skipped
            ],
        ];

        HyperFields::registerOptionsPage($config);

        $this->assertTrue(true);
    }

    public function testRegisterOptionsPageSkipsInvalidFields()
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('add_query_arg')->justReturn('http://example.com/page');
        Functions\when('get_option')->justReturn([]);

        $config = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'sections' => [
                [
                    'id' => 'section1',
                    'title' => 'Section 1',
                    'fields' => [
                        ['type' => 'text', 'name' => 'valid_field'],
                        ['name' => 'no_type'], // Should be skipped
                        ['type' => 'text'], // Should be skipped - no name
                    ],
                ],
            ],
        ];

        HyperFields::registerOptionsPage($config);

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}

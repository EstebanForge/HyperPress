<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\OptionField;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class OptionFieldTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_option')->justReturn(false);
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testForOption()
    {
        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');

        $this->assertInstanceOf(OptionField::class, $field);
    }

    public function testSetOptionGroup()
    {
        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->setOptionGroup('custom_group');

        $this->assertSame($field, $result);
        $this->assertEquals('custom_group', $field->getOptionGroup());
    }

    public function testGetOptionName()
    {
        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');

        $this->assertEquals('test_option', $field->getOptionName());
    }

    public function testGetOptionGroup()
    {
        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');

        $this->assertEquals('hyperpress_fields', $field->getOptionGroup());
    }

    public function testGetValueWithArrayStorage()
    {
        Functions\when('get_option')->alias(function($name) {
            return ['field_name' => 'stored_value'];
        });

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $value = $field->getValue();

        $this->assertEquals('stored_value', $value);
    }

    public function testGetValueReturnsDefault()
    {
        Functions\when('get_option')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $field->setDefault('default_value');
        $value = $field->getValue();

        $this->assertEquals('default_value', $value);
    }

    public function testSetValueWithArrayStorage()
    {
        Functions\when('get_option')->alias(function($name) {
            return ['existing' => 'value'];
        });
        Functions\when('sanitize_text_field')->returnArg();

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->setValue('new_value');

        $this->assertTrue($result);
    }

    public function testSetValueWithScalarStorage()
    {
        Functions\when('get_option')->justReturn('old_value');
        Functions\when('sanitize_text_field')->returnArg();

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->setValue('new_value');

        $this->assertTrue($result);
    }

    public function testDeleteValueWithArrayStorage()
    {
        Functions\when('get_option')->alias(function($name) {
            return ['field_name' => 'value', 'other_field' => 'other'];
        });

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->deleteValue();

        $this->assertTrue($result);
    }

    public function testDeleteValueWithEmptyArray()
    {
        Functions\when('get_option')->alias(function($name) {
            return ['field_name' => 'value'];
        });

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->deleteValue();

        $this->assertTrue($result);
    }

    public function testDeleteValueWithScalarStorage()
    {
        Functions\when('get_option')->justReturn('scalar_value');

        $field = OptionField::forOption('test_option', 'text', 'field_name', 'Field Label');
        $result = $field->deleteValue();

        $this->assertTrue($result);
    }
}

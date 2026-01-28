<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit\Block;

use HyperBlocks\Block\Field;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Field class.
 */
class FieldTest extends TestCase
{
    public function testFieldCreation(): void
    {
        $field = Field::make('text', 'title', 'Title');

        $this->assertEquals('text', $field->type);
        $this->assertEquals('title', $field->name);
        $this->assertEquals('Title', $field->label);
    }

    public function testSetDefault(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default Value');

        $this->assertEquals('Default Value', $field->default);
    }

    public function testSetPlaceholder(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setPlaceholder('Enter title');

        $this->assertEquals('Enter title', $field->placeholder);
    }

    public function testSetRequired(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setRequired(true);

        $this->assertTrue($field->required);
    }

    public function testSetNotRequired(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setRequired(false);

        $this->assertFalse($field->required);
    }

    public function testSetHelp(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setHelp('This is help text');

        $this->assertEquals('This is help text', $field->help);
    }

    public function testSetOptions(): void
    {
        $options = ['option1' => 'Option 1', 'option2' => 'Option 2'];
        $field = Field::make('select', 'choice', 'Choice')
            ->setOptions($options);

        $this->assertEquals($options, $field->getHyperField()->getOptions());
    }

    public function testSetValidation(): void
    {
        $validation = ['min' => 3, 'max' => 100];
        $field = Field::make('text', 'title', 'Title')
            ->setValidation($validation);

        $this->assertEquals($validation, $field->getHyperField()->getValidation());
    }

    public function testFluentApiChaining(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default')
            ->setPlaceholder('Enter title')
            ->setRequired(true)
            ->setHelp('Help text');

        $this->assertInstanceOf(Field::class, $field);
    }

    public function testGetHyperField(): void
    {
        $field = Field::make('text', 'title', 'Title');
        $hyperField = $field->getHyperField();

        $this->assertInstanceOf(\HyperFields\Field::class, $hyperField);
        $this->assertEquals('text', $hyperField->getType());
        $this->assertEquals('title', $hyperField->getName());
    }

    public function testGetAdapter(): void
    {
        $field = Field::make('text', 'title', 'Title');
        $adapter = $field->getAdapter();

        $this->assertInstanceOf(\HyperFields\BlockFieldAdapter::class, $adapter);
    }

    public function testToBlockAttribute(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default');

        $attribute = $field->toBlockAttribute();

        $this->assertIsArray($attribute);
        $this->assertArrayHasKey('type', $attribute);
        $this->assertArrayHasKey('default', $attribute);
    }

    public function testSanitizeValue(): void
    {
        $field = Field::make('text', 'title', 'Title');
        $sanitized = $field->sanitizeValue('<script>alert("xss")</script>Hello');

        $this->assertEquals('Hello', $sanitized);
    }

    public function testValidateValue(): void
    {
        $field = Field::make('text', 'title', 'Title');
        $this->assertTrue($field->validateValue('Valid Title'));
    }

    public function testValidateValueWithRequired(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setRequired(true);

        $this->assertFalse($field->validateValue(''));
        $this->assertTrue($field->validateValue('Valid'));
    }

    public function testMagicGetter(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default')
            ->setPlaceholder('Placeholder')
            ->setRequired(true)
            ->setHelp('Help');

        $this->assertEquals('text', $field->type);
        $this->assertEquals('title', $field->name);
        $this->assertEquals('Title', $field->label);
        $this->assertEquals('Default', $field->default);
        $this->assertEquals('Placeholder', $field->placeholder);
        $this->assertTrue($field->required);
        $this->assertEquals('Help', $field->help);
    }

    public function testMagicSetter(): void
    {
        $field = Field::make('text', 'title', 'Title');

        $field->default = 'New Default';
        $field->placeholder = 'New Placeholder';
        $field->required = true;
        $field->help = 'New Help';

        $this->assertEquals('New Default', $field->default);
        $this->assertEquals('New Placeholder', $field->placeholder);
        $this->assertTrue($field->required);
        $this->assertEquals('New Help', $field->help);
    }

    public function testSupportedFieldTypes(): void
    {
        $types = Field::FIELD_TYPES;

        $this->assertContains('text', $types);
        $this->assertContains('textarea', $types);
        $this->assertContains('color', $types);
        $this->assertContains('image', $types);
        $this->assertContains('select', $types);
        $this->assertContains('checkbox', $types);
    }

    public function testUnsupportedFieldTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Field::make('invalid_type', 'title', 'Title');
    }

    public function testToArray(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default')
            ->setPlaceholder('Placeholder')
            ->setRequired(true);

        $array = $field->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('text', $array['type']);
        $this->assertEquals('title', $array['name']);
        $this->assertEquals('Title', $array['label']);
        $this->assertEquals('Default', $array['default']);
        $this->assertEquals('Placeholder', $array['placeholder']);
        $this->assertTrue($array['required']);
    }
}

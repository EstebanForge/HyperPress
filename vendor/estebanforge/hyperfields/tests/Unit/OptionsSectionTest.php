<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\OptionsSection;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class OptionsSectionTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    private OptionsSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('esc_html')->returnArg();

        $this->section = new OptionsSection('test_section', 'Test Section', 'Test description');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testSectionCreation()
    {
        $this->assertEquals('test_section', $this->section->getId());
        $this->assertEquals('Test Section', $this->section->getTitle());
        $this->assertEquals('Test description', $this->section->getDescription());
    }

    public function testStaticMakeMethod()
    {
        $section = OptionsSection::make('static_section', 'Static Section', 'Static description');

        $this->assertInstanceOf(OptionsSection::class, $section);
        $this->assertEquals('static_section', $section->getId());
        $this->assertEquals('Static Section', $section->getTitle());
        $this->assertEquals('Static description', $section->getDescription());
    }

    public function testSetDescription()
    {
        $this->section->setDescription('New description');

        $this->assertEquals('New description', $this->section->getDescription());
    }

    public function testAddField()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        $this->section->addField($field);

        $fields = $this->section->getFields();
        $this->assertCount(1, $fields);
        $this->assertArrayHasKey('test_field', $fields);
        $this->assertSame($field, $fields['test_field']);
    }

    public function testAddFieldSetsContext()
    {
        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('setContext')->once()->with('option');

        $this->section->addField($field);

        $fields = $this->section->getFields();
        $this->assertArrayHasKey('test_field', $fields);
    }

    public function testAddMultipleFields()
    {
        $field1 = Field::make('text', 'field1', 'Field 1');
        $field2 = Field::make('email', 'field2', 'Field 2');

        $this->section->addField($field1);
        $this->section->addField($field2);

        $fields = $this->section->getFields();
        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
    }

    public function testGetFieldsEmpty()
    {
        $fields = $this->section->getFields();

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    public function testRenderWithDescription()
    {
        // esc_html is already stubbed in setUp via stubEscapeFunctions()
        ob_start();
        $this->section->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('description', $output);
        $this->assertStringContainsString('Test description', $output);
    }

    public function testRenderWithoutDescription()
    {
        $section = new OptionsSection('test_section', 'Test Section');

        ob_start();
        $section->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testFluentInterface()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        $result = $this->section->setDescription('New description')
                               ->addField($field);

        $this->assertSame($this->section, $result);
        $this->assertEquals('New description', $this->section->getDescription());
        $this->assertCount(1, $this->section->getFields());
    }

    public function testSectionWithEmptyDescription()
    {
        $section = new OptionsSection('test_section', 'Test Section', '');

        $this->assertEquals('', $section->getDescription());

        ob_start();
        $section->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testOverwriteFieldWithSameName()
    {
        $field1 = Field::make('text', 'duplicate_field', 'Field 1');
        $field2 = Field::make('email', 'duplicate_field', 'Field 2');

        $this->section->addField($field1);
        $this->section->addField($field2);

        $fields = $this->section->getFields();
        $this->assertCount(1, $fields);
        $this->assertSame($field2, $fields['duplicate_field']);
    }
}
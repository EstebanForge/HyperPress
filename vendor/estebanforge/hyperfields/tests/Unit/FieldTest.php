<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class FieldTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions that might be needed
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('sanitize_text_field')->alias(function($value) {
            // Mimic WordPress sanitize_text_field behavior
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            $value = strip_tags($value);
            $value = trim($value);
            return $value;
        });
        Functions\when('sanitize_email')->alias(function($value) {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        });
        Functions\when('sanitize_url')->alias(function($value) {
            return filter_var($value, FILTER_SANITIZE_URL);
        });
        Functions\when('is_email')->alias(function($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : false;
        });
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('absint')->alias(function($value) { return abs((int) $value); });
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_hex_color')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFieldCreationWithMake()
    {
        $field = Field::make('text', 'field_name', 'Field Label');

        $this->assertEquals('text', $field->getType());
        $this->assertEquals('field_name', $field->getName());
        $this->assertEquals('Field Label', $field->getLabel());
    }

    public function testFieldSettersAndGetters()
    {
        $field = Field::make('email', 'user_email', 'User Email');

        // Test setter methods
        $field->setDefault('user@example.com');
        $field->setPlaceholder('Enter your email');
        $field->setRequired(true);
        $field->setHelp('This is your email address');
        $field->setHtml('<div>Custom HTML</div>');

        // Test getter methods
        $this->assertEquals('email', $field->getType());
        $this->assertEquals('user_email', $field->getName());
        $this->assertEquals('User Email', $field->getLabel());
        $this->assertEquals('user@example.com', $field->getDefault());
        $this->assertEquals('Enter your email', $field->getPlaceholder());
        $this->assertTrue($field->isRequired());
        $this->assertEquals('This is your email address', $field->getHelp());
        $this->assertEquals('<div>Custom HTML</div>', $field->getHtml());
    }

    public function testHtmlContentAlias()
    {
        $field = Field::make('text', 'test_field', 'Test Field');
        $field->setHtmlContent('<span>Custom Content</span>');

        $this->assertEquals('<span>Custom Content</span>', $field->getHtml());
    }

    public function testFieldContexts()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        // Test different contexts
        $this->assertEquals('post', $field->getContext()); // Default context

        $field->setContext('term');
        $this->assertEquals('term', $field->getContext());

        $field->setContext('user');
        $this->assertEquals('user', $field->getContext());

        $field->setContext('option');
        $this->assertEquals('option', $field->getContext());
    }

    public function testFieldStorageTypes()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        // Test default storage type
        $this->assertEquals('meta', $field->getStorageType());

        // Test changing storage type
        $field->setStorageType('option');
        $this->assertEquals('option', $field->getStorageType());
    }

    public function testFieldOptions()
    {
        $field = Field::make('select', 'test_select', 'Test Select');

        $options = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ];

        $field->setOptions($options);
        $this->assertEquals($options, $field->getOptions());
    }

    public function testToArrayConversion()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        $field->setDefault('Default value')
              ->setRequired(true)
              ->setPlaceholder('Enter value')
              ->setHelp('Help text');

        $array = $field->toArray();

        $this->assertEquals('text', $array['type']);
        $this->assertEquals('test_field', $array['name']);
        $this->assertEquals('Test Field', $array['label']);
        $this->assertEquals('Default value', $array['default']);
        $this->assertTrue($array['required']);
        $this->assertEquals('Enter value', $array['placeholder']);
        $this->assertEquals('Help text', $array['help']);
    }

    public function testInvalidFieldTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid field type: invalid_type');

        Field::make('invalid_type', 'field_name', 'Field Label');
    }

    public function testInvalidFieldNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid field name: 123-invalid');

        Field::make('text', '123-invalid', 'Field Label');
    }

    public function testValidationSetters()
    {
        $field = Field::make('number', 'test_number', 'Test Number');

        $field->setValidation([
            'min' => 1,
            'max' => 100,
            'required' => true
        ]);

        $this->assertEquals([
            'min' => 1,
            'max' => 100,
            'required' => true
        ], $field->getValidation());
    }

    public function testConditionalLogic()
    {
        $field = Field::make('text', 'dependent_field', 'Dependent Field');

        $field->setConditionalLogic([
            'show_when' => [
                'field' => 'parent_field',
                'operator' => 'equals',
                'value' => 'show'
            ]
        ]);

        $this->assertEquals([
            'show_when' => [
                'field' => 'parent_field',
                'operator' => 'equals',
                'value' => 'show'
            ]
        ], $field->getConditionalLogic());
    }

    public function testMultipleValues()
    {
        $field = Field::make('select', 'test_select', 'Test Select');
        
        $this->assertFalse($field->isMultiple());
        
        $field->setMultiple(true);
        $this->assertTrue($field->isMultiple());
    }

    public function testSanitizeTypes()
    {
        // Text/Hidden
        $field = Field::make('text', 'f', 'F');
        $this->assertEquals('clean', $field->sanitizeValue('clean'));
        
        // Textarea/RichText/Wysiwyg
        $field = Field::make('textarea', 'f', 'F');
        $this->assertEquals('<b>safe</b>', $field->sanitizeValue('<b>safe</b>'));
        
        // Number
        $field = Field::make('number', 'f', 'F');
        $this->assertEquals(123.45, $field->sanitizeValue('123.45'));
        $this->assertEquals(0, $field->sanitizeValue('abc'));

        // URL
        $field = Field::make('url', 'f', 'F');
        $this->assertEquals('http://example.com', $field->sanitizeValue('http://example.com'));
        
        // Color
        $field = Field::make('color', 'f', 'F');
        $this->assertEquals('#ffffff', $field->sanitizeValue('#ffffff'));
        
        // Date/Time
        $field = Field::make('date', 'f', 'F');
        $this->assertEquals('2023-01-01', $field->sanitizeValue('2023-01-01'));
        
        // Image (absint)
        $field = Field::make('image', 'f', 'F');
        $this->assertEquals(123, $field->sanitizeValue('123'));
        
        // File (url)
        $field = Field::make('file', 'f', 'F');
        $this->assertEquals('http://example.com/file.pdf', $field->sanitizeValue('http://example.com/file.pdf'));
        
        // Checkbox
        $field = Field::make('checkbox', 'f', 'F');
        $this->assertTrue($field->sanitizeValue('1'));
        $this->assertFalse($field->sanitizeValue('0'));

        // Default custom filter
        $field = Field::make('custom', 'f', 'F');
        
        // Override apply_filters for this specific call
        Functions\when('apply_filters')
            ->alias(function($tag, $value, ...$args) {
                if ($tag === 'hyperpress/fields/sanitize_custom') {
                    return 'filtered';
                }
                return $value; // Fallback to 2nd arg behavior
            });
            
        $this->assertEquals('filtered', $field->sanitizeValue('val'));
    }

    public function testArgsAndOptionValue()
    {
        $field = Field::make('text', 'f', 'F')->setDefault('def');
        
        // Args
        $field->addArg('key', 'val');
        $this->assertEquals(['key' => 'val'], $field->getArgs());
        
        // Option Value direct retrieval
        Functions\expect('get_option')->with('f', 'def')->andReturn('opt_val');
        $this->assertEquals('opt_val', $field->getOptionValue());
    }

    public function testSanitizeComplexTypes()
    {
        // Set
        $field = Field::make('set', 'f', 'F')->setOptions(['a'=>'A', 'b'=>'B']);
        $this->assertEquals(['a'], $field->sanitizeValue(['a', '__hm_empty__', 'invalid']));
        $this->assertEquals([], $field->sanitizeValue('not_array'));

        // Multiselect
        $field = Field::make('multiselect', 'f', 'F');
        $this->assertEquals(['a', 'b'], $field->sanitizeValue(['a', 'b']));
        $this->assertEquals([], $field->sanitizeValue('not_array'));

        // Map
        $field = Field::make('map', 'f', 'F');
        $this->assertEquals(['lat'=>1.1, 'lng'=>2.2, 'address'=>'addr'], $field->sanitizeValue(['lat'=>'1.1', 'lng'=>'2.2', 'address'=>'addr']));
        $this->assertEquals(['lat'=>0, 'lng'=>0, 'address'=>''], $field->sanitizeValue('not_array'));

        // Association
        $field = Field::make('association', 'f', 'F');
        $this->assertEquals([1, 2], $field->sanitizeValue(['1', '2']));
        $this->assertEquals([], $field->sanitizeValue('not_array'));

        // Complex/Repeater
        $field = Field::make('repeater', 'f', 'F');
        $this->assertEquals(['clean'], $field->sanitizeValue(['clean'])); // sanitizeNestedValue -> sanitize_text_field
        $this->assertEquals([], $field->sanitizeValue('not_array'));
        // Test nested array in repeater
        $this->assertEquals([['clean']], $field->sanitizeValue([['clean']]));
    }

    public function testSanitizeSelect()
    {
        $field = Field::make('select', 'f', 'F')->setOptions(['a'=>'A', 'b'=>'B']);
        $this->assertEquals('a', $field->sanitizeValue('a'));
        $this->assertEquals('a', $field->sanitizeValue('invalid')); // defaults to first option

        $fieldEmpty = Field::make('select', 'f', 'F');
        $this->assertEquals('val', $fieldEmpty->sanitizeValue('val'));
    }

    public function testValidationRules()
    {
        $field = Field::make('text', 'f', 'F');

        // Required
        $field->setRequired(true);
        $this->assertFalse($field->validateValue(''));
        $this->assertTrue($field->validateValue('valid'));
        $field->setRequired(false);

        // Min/Max length
        $field->setValidation(['min' => 3]);
        $this->assertTrue($field->validateValue('abc'));
        $this->assertFalse($field->validateValue('ab'));

        $field->setValidation(['max' => 3]);
        $this->assertTrue($field->validateValue('abc'));
        $this->assertFalse($field->validateValue('abcd'));

        // Pattern
        $field->setValidation(['pattern' => '/^a+$/']);
        $this->assertTrue($field->validateValue('aaa'));
        $this->assertFalse($field->validateValue('aab'));

        // Numeric/Integer/Float
        $field->setValidation(['numeric' => true]);
        $this->assertTrue($field->validateValue('123'));
        $this->assertFalse($field->validateValue('abc'));

        $field->setValidation(['integer' => true]);
        $this->assertTrue($field->validateValue('123'));
        $this->assertFalse($field->validateValue('12.3'));

        $field->setValidation(['float' => true]);
        $this->assertTrue($field->validateValue('12.3'));
        $this->assertFalse($field->validateValue('abc'));
        
        // Custom rule
        $field->setValidation(['custom' => 'param']);
        Functions\expect('apply_filters')
            ->with('hyperpress/fields/validation_custom', true, 'val', 'param', $field)
            ->andReturn(true);
        $this->assertTrue($field->validateValue('val'));
    }

    public function testValueRetrieval()
    {
        $field = Field::make('text', 'f', 'F')->setDefault('def');

        // 1. Pre-loaded options (via setOptionValues)
        $field->setOptionValues(['f' => 'preloaded']);
        $this->assertEquals('preloaded', $field->getValue());
        
        // --- Test calls to get_option for different scenarios ---

        // Scenario: option_name is string ('my_option') and returns array value
        $ref = new \ReflectionClass($field); // get reflector for $field
        $prop = $ref->getProperty('option_name');
        $prop->setValue($field, 'my_option'); // Set $field->option_name to 'my_option'
        Functions\expect('get_option')
            ->once()
            ->with('my_option')
            ->andReturn(['f' => 'opt_val']);
        $this->assertEquals('opt_val', $field->getValue());
        
        // Scenario: Default value (option_name is null)
        $prop->setValue($field, null); // Reset $field->option_name to null
        $this->assertEquals('def', $field->getValue());

        // Scenario: getValue when option_name is null and default is null
        $fieldNoDefault = Field::make('text', 'f', 'F');
        // No need to set option_name here, it's null by default for new Field
        $this->assertNull($fieldNoDefault->getValue()); // option_name is null, default is null -> null

        // Scenario: getValue when option_name is string ('scalar_option') and get_option returns scalar
        $fieldScalarOption = Field::make('text', 'f', 'F');
        $prop = (new \ReflectionClass($fieldScalarOption))->getProperty('option_name');
        $prop->setValue($fieldScalarOption, 'scalar_option'); // Set option_name for this field
        Functions\expect('get_option')
            ->once()
            ->with('scalar_option')
            ->andReturn(['f' => 'scalar_value']); // Must return array
        $this->assertEquals('scalar_value', $fieldScalarOption->getValue());

        // Scenario: getValue when option_name is empty string and get_option returns false (default)
        $fieldEmptyOptionName = Field::make('text', 'f', 'F')->setDefault('def_empty');
        $prop = (new \ReflectionClass($fieldEmptyOptionName))->getProperty('option_name');
        $prop->setValue($fieldEmptyOptionName, ''); // Set option_name to empty string
        // Expect NO call to get_option because option_name is empty
        $this->assertEquals('def_empty', $fieldEmptyOptionName->getValue());
    }

    public function testGetNameAttr()
    {
        $field = Field::make('text', 'f', 'F');
        
        // Default (context=post, option_group=null, option_name=null)
        $this->assertEquals('f', $field->getNameAttr());
        
        // Metabox context (also returns just name)
        $field->setContext('metabox');
        $this->assertEquals('f', $field->getNameAttr());
        
        // Option Group (context=option, option_group set)
        $field->setContext('option');
        $field->setOptionValues([], 'my_group');
        $this->assertEquals('my_group[f]', $field->getNameAttr());
        
        // Option Name is array, option_group is null (legacy fallback)
        $fieldArrayOptionName = Field::make('text', 'f', 'F');
        $fieldArrayOptionName->setContext('option');
        $ref = new \ReflectionClass($fieldArrayOptionName);
        $prop = $ref->getProperty('option_name');
        $prop->setValue($fieldArrayOptionName, ['f' => 'val']);
        // Ensure option_group is null
        $propGroup = $ref->getProperty('option_group');
        $propGroup->setValue($fieldArrayOptionName, null);
        $this->assertEquals('f', $fieldArrayOptionName->getNameAttr());


        // Option Name is string, option_group is null
        $fieldStringOptionName = Field::make('text', 'f', 'F');
        $fieldStringOptionName->setContext('option');
        $ref = new \ReflectionClass($fieldStringOptionName); // Re-get reflector for new instance
        $prop = $ref->getProperty('option_name'); // Get prop again for this instance
        $prop->setValue($fieldStringOptionName, 'my_opt');
        $this->assertEquals('my_opt[f]', $fieldStringOptionName->getNameAttr());

        // Option Name is null, option_group is null, context is 'option' -> should return just name
        $fieldNullOptionName = Field::make('text', 'f', 'F');
        $fieldNullOptionName->setContext('option');
        $ref = new \ReflectionClass($fieldNullOptionName); // Re-get reflector for new instance
        $prop = $ref->getProperty('option_name'); // Get prop again for this instance
        $prop->setValue($fieldNullOptionName, null);
        $this->assertEquals('f', $fieldNullOptionName->getNameAttr());

        // When option_group and option_name are null, but context is 'metabox' or 'post' (default) -> should return just name
        $fieldContextPost = Field::make('text', 'f', 'F');
        $this->assertEquals('f', $fieldContextPost->getNameAttr());
        $fieldContextMetabox = Field::make('text', 'f', 'F')->setContext('metabox');
        $this->assertEquals('f', $fieldContextMetabox->getNameAttr());
    }

    public function testRender()
    {
        $field = Field::make('text', 'f', 'F')->setDefault('default_value'); // Set default for getValue
        
        // Mock TemplateLoader static method
        $mockTemplateLoader = \Mockery::mock('alias:HyperFields\TemplateLoader');
        $mockTemplateLoader->shouldReceive('renderField')
            ->once()
            ->with(\Mockery::on(function($args) {
                // Verify essential keys are present and name attributes match expectation context
                return isset($args['name']) && $args['name'] === 'f'
                    && isset($args['name_attr']) && $args['name_attr'] === 'my_option_group[f]';
            }), 'default_value'); 

        $field->setOptionValues([], 'my_option_group'); 
        
        // If option_name is string 'f' but default is provided, and option_group logic sets option_name to array...
        // Wait, setOptionValues sets option_name to the array passed (first arg).
        // So option_name is []. getValue() sees array, tries to find 'f' in it. Not found. Returns default.
        // So get_option is NOT called.
        // We should NOT expect get_option here.
            
        $field->render(['arg1' => 'val1']);
    }

    public function testProtectedSetArgs()
    {
        $field = Field::make('text', 'f', 'F');
        $reflector = new \ReflectionClass($field);
        $method = $reflector->getMethod('setArgs');

        // First call sets initial args
        $method->invoke($field, ['initial_arg' => 'initial_val']);
        $this->assertEquals(['initial_arg' => 'initial_val'], $field->getArgs());

        // Add another arg via public addArg
        $field->addArg('existing_arg', 'old_val');
        $this->assertEquals(['initial_arg' => 'initial_val', 'existing_arg' => 'old_val'], $field->getArgs());

        // Second call to setArgs merges with existing args
        $method->invoke($field, ['merged_arg' => 'merged_val']);
        $this->assertEquals(['initial_arg' => 'initial_val', 'existing_arg' => 'old_val', 'merged_arg' => 'merged_val'], $field->getArgs());
    }

    public function testSetHtml()
    {
        $field = Field::make('html', 'test_field', 'Test Field');
        $result = $field->setHtml('<p>Test HTML</p>');

        $this->assertSame($field, $result);
        $this->assertEquals('<p>Test HTML</p>', $field->getHtml());
    }

    public function testSetHtmlContent()
    {
        $field = Field::make('html', 'test_field', 'Test Field');
        $result = $field->setHtmlContent('<div>Content</div>');

        $this->assertSame($field, $result);
        $this->assertEquals('<div>Content</div>', $field->getHtml());
    }

    public function testSetMultiple()
    {
        $field = Field::make('select', 'test_field', 'Test Field');
        $result = $field->setMultiple(true);

        $this->assertSame($field, $result);
        $this->assertTrue($field->isMultiple());
    }

    public function testIsMultipleFalseByDefault()
    {
        $field = Field::make('select', 'test_field', 'Test Field');

        $this->assertFalse($field->isMultiple());
    }

    public function testToArray()
    {
        $field = Field::make('text', 'test_field', 'Test Label');
        $field->setDefault('default_value');
        $field->setRequired(true);

        $array = $field->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('text', $array['type']);
        $this->assertEquals('test_field', $array['name']);
        $this->assertEquals('Test Label', $array['label']);
        $this->assertEquals('default_value', $array['default']);
        $this->assertTrue($array['required']);
    }
}
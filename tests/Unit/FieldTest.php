<?php

/**
 * Unit test for HyperPress Field class
 */

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Fields\Field;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('Field class can be created', function () {
    $field = new Field('test_field', 'Test Field', 'text');
    
    expect($field)->toBeInstanceOf(Field::class);
    expect($field->getId())->toBe('test_field');
    expect($field->getLabel())->toBe('Test Field');
    expect($field->getType())->toBe('text');
});

test('Field can set and get value', function () {
    $field = new Field('test_field', 'Test Field', 'text');
    
    $field->setValue('test value');
    expect($field->getValue())->toBe('test value');
});

test('Field can validate required field', function () {
    $field = new Field('test_field', 'Test Field', 'text');
    $field->setRequired(true);
    
    // Test empty value
    $field->setValue('');
    expect($field->validate())->toBeFalse();
    
    // Test non-empty value
    $field->setValue('some value');
    expect($field->validate())->toBeTrue();
});

test('Field can sanitize input', function () {
    $field = new Field('test_field', 'Test Field', 'text');
    
    // Test HTML sanitization
    $field->setValue('<script>alert("xss")</script>');
    expect($field->sanitize())->not()->toContain('<script>');
});

test('Field can render basic input', function () {
    $field = new Field('test_field', 'Test Field', 'text');
    $field->setValue('test value');
    
    $output = $field->render();
    expect($output)->toContain('type="text"');
    expect($output)->toContain('name="test_field"');
    expect($output)->toContain('value="test value"');
});
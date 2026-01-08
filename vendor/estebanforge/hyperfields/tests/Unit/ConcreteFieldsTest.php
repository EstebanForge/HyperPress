<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\PostField;
use HyperFields\UserField;
use HyperFields\TermField;
use HyperFields\OptionField;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ConcreteFieldsTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('apply_filters')->alias(function($tag, $value, ...$args) {
            // Handle specific filter for key generation if needed
            if (strpos($tag, '_meta_key') !== false || strpos($tag, 'option_field_name') !== false) {
                return $value;
            }
            return $value; // Fallback
        });
        Functions\when('delete_option')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testPostField()
    {
        $field = PostField::forPost(1, 'text', 'f', 'F');
        $this->assertEquals(1, $field->getPostId());
        $this->assertEquals('post', $field->getContext());
        
        // Meta Key
        $this->assertEquals('f', $field->getMetaKey());
        $field->setMetaKeyPrefix('_');
        $this->assertEquals('_f', $field->getMetaKey());

        // Get Value
        Functions\expect('get_post_meta')
            ->times(2)
            ->with(1, '_f', true)
            ->andReturn('val', '');
            
        $this->assertEquals('val', $field->getValue());

        $field->setDefault('def');
        $this->assertEquals('def', $field->getValue());

        // Set Value
        Functions\expect('update_post_meta')->with(1, '_f', 'new')->andReturn(true);
        $this->assertTrue($field->setValue('new'));

        // Set Value Validation Fail
        $field->setRequired(true);
        $this->assertFalse($field->setValue('')); // Empty value fails required

        // Delete Value
        Functions\expect('delete_post_meta')->with(1, '_f')->andReturn(true);
        $this->assertTrue($field->deleteValue());
    }

    public function testUserField()
    {
        $field = UserField::forUser(1, 'text', 'f', 'F');
        $this->assertEquals(1, $field->getUserId());
        $this->assertEquals('user', $field->getContext());

        // Meta Key
        $this->assertEquals('f', $field->getMetaKey());
        $field->setMetaKeyPrefix('_');
        $this->assertEquals('_f', $field->getMetaKey());

        // Get Value
        Functions\expect('get_user_meta')
            ->times(2)
            ->with(1, '_f', true)
            ->andReturn('val', '');
            
        $this->assertEquals('val', $field->getValue());

        $field->setDefault('def');
        $this->assertEquals('def', $field->getValue());

        // Set Value
        Functions\expect('update_user_meta')->with(1, '_f', 'new')->andReturn(true);
        $this->assertTrue($field->setValue('new'));

        // Set Value Validation Fail
        $field->setRequired(true);
        $this->assertFalse($field->setValue(''));

        // Delete Value
        Functions\expect('delete_user_meta')->with(1, '_f')->andReturn(true);
        $this->assertTrue($field->deleteValue());
    }

    public function testTermField()
    {
        $field = TermField::forTerm(1, 'text', 'f', 'F');
        $this->assertEquals(1, $field->getTermId());
        $this->assertEquals('term', $field->getContext());

        // Meta Key
        $this->assertEquals('f', $field->getMetaKey());
        $field->setMetaKeyPrefix('_');
        $this->assertEquals('_f', $field->getMetaKey());

        // Get Value
        Functions\expect('get_term_meta')
            ->times(2)
            ->with(1, '_f', true)
            ->andReturn('val', '');
            
        $this->assertEquals('val', $field->getValue());

        $field->setDefault('def');
        $this->assertEquals('def', $field->getValue());

        // Set Value
        Functions\expect('update_term_meta')->with(1, '_f', 'new')->andReturn(true);
        $this->assertTrue($field->setValue('new'));

        // Set Value Validation Fail
        $field->setRequired(true);
        $this->assertFalse($field->setValue(''));

        // Delete Value
        Functions\expect('delete_term_meta')->with(1, '_f')->andReturn(true);
        $this->assertTrue($field->deleteValue());
    }

    public function testOptionField()
    {
        $field = OptionField::forOption('opt_name', 'text', 'f', 'F');
        $this->assertEquals('option', $field->getContext());
        $this->assertEquals('opt_name', $field->getOptionName());
        
        $field->setOptionGroup('grp');
        $this->assertEquals('grp', $field->getOptionGroup());

        // Get Value
        // Expect get_option twice: once for scalar, once for array
        Functions\expect('get_option')
            ->with('opt_name')
            ->andReturn('val', ['f' => 'val_in_arr']);
            
        $this->assertEquals('val', $field->getValue());
        $this->assertEquals('val_in_arr', $field->getValue());

        // Set Value (Single)
        Functions\expect('get_option')->with('opt_name', [])->andReturn([], 'string_val', [], ['f'=>'v', 'other'=>'v2'], ['f'=>'v'], 'v');
        // 1. Array storage path (returns [])
        Functions\expect('update_option')->with('opt_name', ['f' => 'new'])->andReturn(true);
        $this->assertTrue($field->setValue('new'));

        // 2. Single storage path (returns 'string_val')
        Functions\expect('update_option')->with('opt_name', 'new_single')->andReturn(true);
        $this->assertTrue($field->setValue('new_single'));
        
        // 3. Validation Fail
        $field->setRequired(true);
        $this->assertFalse($field->setValue(''));

        // Delete Value
        // 3. Array storage (returns [...]) (Note: get_option queue shifted by validation fail?)
        // Validation fail does NOT call get_option.
        
        Functions\expect('update_option')->with('opt_name', ['other' => 'v2'])->andReturn(true);
        $this->assertTrue($field->deleteValue());
        
        // 4. Array storage empty after delete (returns ['f'=>'v'])
        Functions\expect('delete_option')->with('opt_name')->andReturn(true);
        $this->assertTrue($field->deleteValue());

        // 5. Single storage (returns 'v')
        Functions\expect('delete_option')->with('opt_name')->andReturn(true);
        $this->assertTrue($field->deleteValue());
    }
}
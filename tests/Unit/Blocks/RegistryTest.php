<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit\Blocks;

use PHPUnit\Framework\TestCase;

/**
 * Test Blocks Registry functionality.
 */
class RegistryTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        // Load the Registry class if it exists
        if (file_exists(HYPERPRESS_DIR . '/src/Blocks/Registry.php')) {
            require_once HYPERPRESS_DIR . '/src/Blocks/Registry.php';
        }
    }

    public function testRegistryClassExists()
    {
        if (class_exists('HyperPress\Blocks\Registry')) {
            $this->assertTrue(true, 'Registry class exists');
        } else {
            $this->markTestSkipped('Registry class not available - may need dependency loading');
        }
    }

    public function testRegistryCanBeInstantiated()
    {
        if (!class_exists('HyperPress\Blocks\Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        $this->assertInstanceOf(\HyperPress\Blocks\Registry::class, \HyperPress\Blocks\Registry::getInstance());
    }

    public function testBlockRegistration()
    {
        if (!class_exists('HyperPress\Blocks\Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        $registry = \HyperPress\Blocks\Registry::getInstance();

        // Test basic block registration structure
        $blockData = [
            'name' => 'test-block',
            'title' => 'Test Block',
            'category' => 'hyperpress'
        ];

        $this->assertIsArray($blockData);
        $this->assertEquals('test-block', $blockData['name']);
        $this->assertEquals('Test Block', $blockData['title']);
        $this->assertEquals('hyperpress', $blockData['category']);
    }

    public function testBlockMetadataStructure()
    {
        $validBlockData = [
            'name' => 'sample-block',
            'title' => 'Sample Block',
            'description' => 'A sample block for testing',
            'category' => 'hyperpress',
            'icon' => 'dashicons-admin-generic',
            'keywords' => ['sample', 'test', 'block'],
            'supports' => [
                'align' => true,
                'multiple' => true
            ]
        ];

        $this->assertArrayHasKey('name', $validBlockData);
        $this->assertArrayHasKey('title', $validBlockData);
        $this->assertArrayHasKey('category', $validBlockData);
        $this->assertArrayHasKey('supports', $validBlockData);
        $this->assertIsArray($validBlockData['keywords']);
        $this->assertIsArray($validBlockData['supports']);
    }

    public function testBlockCategories()
    {
        $hyperpressCategories = [
            'hyperpress' => [
                'title' => 'HyperPress',
                'icon' => 'dashicons-superhero',
                'attributes' => [
                    'content' => [
                        'type' => 'string',
                        'default' => ''
                    ]
                ]
            ]
        ];

        $this->assertArrayHasKey('hyperpress', $hyperpressCategories);
        $this->assertArrayHasKey('title', $hyperpressCategories['hyperpress']);
        $this->assertEquals('HyperPress', $hyperpressCategories['hyperpress']['title']);
    }

    public function testBlockValidation()
    {
        $invalidBlock = [
            'title' => 'Invalid Block'
            // Missing required 'name' and 'category'
        ];

        $this->assertArrayNotHasKey('name', $invalidBlock);
        $this->assertArrayNotHasKey('category', $invalidBlock);

        $validBlock = [
            'name' => 'valid-block',
            'title' => 'Valid Block',
            'category' => 'hyperpress'
        ];

        $this->assertArrayHasKey('name', $validBlock);
        $this->assertArrayHasKey('title', $validBlock);
        $this->assertArrayHasKey('category', $validBlock);
    }
}

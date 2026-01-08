<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit\Container;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\Container\TermMetaContainer;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class TermMetaContainerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    private TermMetaContainer $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('apply_filters')->returnArg();

        // Mock get_term with default behavior - tests can override with expect()
        Functions\when('get_term')->alias(function($term_id, $taxonomy = '') {
            if ($term_id instanceof \stdClass) {
                return $term_id;
            }
            return (object) [
                'term_id' => $term_id,
                'taxonomy' => $taxonomy ?: 'category',
                'slug' => 'test-term-' . $term_id,
                'name' => 'Test Term ' . $term_id
            ];
        });

        $this->container = new TermMetaContainer('test_container', 'Test Container');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Helper method to call protected shouldShowForTerm method using reflection
     */
    private function callShouldShowForTerm(): bool
    {
        $reflection = new \ReflectionClass($this->container);
        $method = $reflection->getMethod('shouldShowForTerm');
        return $method->invoke($this->container);
    }

    public function testContainerCreation()
    {
        $this->assertEquals('test_container', $this->container->getId());
        $this->assertEquals('Test Container', $this->container->getTitle());
    }

    public function testWhereTaxonomy()
    {
        $this->container->where('category');
        $this->container->where('post_tag');

        $reflection = new \ReflectionClass($this->container);
        $taxonomies = $reflection->getProperty('taxonomies');

        $this->assertContains('category', $taxonomies->getValue($this->container));
        $this->assertContains('post_tag', $taxonomies->getValue($this->container));
    }

    public function testWherePreventsDuplicates()
    {
        $this->container->where('category');
        $this->container->where('category'); // Duplicate

        $reflection = new \ReflectionClass($this->container);
        $taxonomies = $reflection->getProperty('taxonomies');

        $this->assertCount(1, $taxonomies->getValue($this->container));
        $this->assertContains('category', $taxonomies->getValue($this->container));
    }

    public function testWhereTermId()
    {
        $this->container->whereTermId(123);
        $this->container->whereTermId(456);

        $reflection = new \ReflectionClass($this->container);
        $termIds = $reflection->getProperty('term_ids');

        $this->assertContains(123, $termIds->getValue($this->container));
        $this->assertContains(456, $termIds->getValue($this->container));
    }

    public function testWhereTermIds()
    {
        $this->container->whereTermIds([123, 456, 789]);

        $reflection = new \ReflectionClass($this->container);
        $termIds = $reflection->getProperty('term_ids');

        $this->assertEquals([123, 456, 789], $termIds->getValue($this->container));
    }

    public function testWhereTermSlug()
    {
        $this->container->whereTermSlug('sample-term');
        $this->container->whereTermSlug('another-term');

        $reflection = new \ReflectionClass($this->container);
        $termSlugs = $reflection->getProperty('term_slugs');

        $this->assertContains('sample-term', $termSlugs->getValue($this->container));
        $this->assertContains('another-term', $termSlugs->getValue($this->container));
    }

    public function testWhereTermSlugs()
    {
        $this->container->whereTermSlugs(['slug-1', 'slug-2', 'slug-3']);

        $reflection = new \ReflectionClass($this->container);
        $termSlugs = $reflection->getProperty('term_slugs');

        $this->assertEquals(['slug-1', 'slug-2', 'slug-3'], $termSlugs->getValue($this->container));
    }

    public function testSetTermId()
    {
        $this->container->setTermId(123);

        $reflection = new \ReflectionClass($this->container);
        $termId = $reflection->getProperty('term_id');

        $this->assertEquals(123, $termId->getValue($this->container));
    }

    public function testInitSetsTermIdFromGet()
    {
        $_GET['tag_ID'] = '123';

        $this->container->init();

        $reflection = new \ReflectionClass($this->container);
        $termId = $reflection->getProperty('term_id');

        $this->assertEquals(123, $termId->getValue($this->container));
    }

    public function testInitRegistersHooks()
    {
        $this->container->where('category');
        $this->container->where('post_tag');

        Functions\expect('add_action')->once()->with('admin_init', [$this->container, 'attach']);
        Functions\expect('add_action')->once()->with('category_edit_form_fields', [$this->container, 'render']);
        Functions\expect('add_action')->once()->with('edited_category', [$this->container, '_save'], 10, 2);
        Functions\expect('add_action')->once()->with('created_category', [$this->container, '_save'], 10, 2);
        Functions\expect('add_action')->once()->with('post_tag_edit_form_fields', [$this->container, 'render']);
        Functions\expect('add_action')->once()->with('edited_post_tag', [$this->container, '_save'], 10, 2);
        Functions\expect('add_action')->once()->with('created_post_tag', [$this->container, '_save'], 10, 2);

        $this->container->init();
    }

    public function testSetTermIdUpdatesTaxonomy()
    {
        // Default get_term alias returns term with taxonomy 'category'
        $this->container->setTermId(123);

        $reflection = new \ReflectionClass($this->container);
        $taxonomy = $reflection->getProperty('taxonomy');

        $this->assertEquals('category', $taxonomy->getValue($this->container));
    }

    public function testSetTermIdWithInvalidTerm()
    {
        $error = new \WP_Error('invalid_term', 'Invalid term');
        
        // Override the setup alias with a specific return for this test
        Functions\when('get_term')->justReturn($error);

        $this->container->setTermId(123);

        $reflection = new \ReflectionClass($this->container);
        $taxonomy = $reflection->getProperty('taxonomy');

        $this->assertEmpty($taxonomy->getValue($this->container));
    }

    public function testIsValidSaveWithoutNonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $this->assertFalse($this->container->isValidSave());
    }

    // ... (skipping lines)

    public function testRenderWithFields()
    {
        $this->container->setTermId(123);

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'saved_value']);

        $this->container->addField($field);

        Functions\expect('get_term_meta')
            ->once()
            ->with(123, 'test_field', true)
            ->andReturn('saved_value');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('hyperfields_metabox_test_container', '_hyperfields_metabox_nonce_test_container');

        ob_start();
        try {
            $this->container->render();
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertStringContainsString('form-field', $output);
        $this->assertStringContainsString('hyperfields-container', $output);
        $this->assertStringContainsString('data-container-id="test_container"', $output);
        $this->assertStringContainsString('Test Container', $output);
    }

    public function testRenderWithTermObject()
    {
        $term = (object) [
            'term_id' => 456,
            'taxonomy' => 'category'
        ];

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'default']);

        $this->container->addField($field);

        Functions\expect('get_term_meta')
            ->once()
            ->with(456, 'test_field', true)
            ->andReturn('');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('hyperfields_metabox_test_container', '_hyperfields_metabox_nonce_test_container');

        ob_start();
        $this->container->render($term);
        $output = ob_get_clean();

        $this->assertStringContainsString('hyperfields-container', $output);
    }

    public function testShouldShowForTermWithoutSpecificTargeting()
    {
        $this->assertTrue($this->callShouldShowForTerm());
    }

    public function testShouldShowForTermWithoutTermId()
    {
        $this->container->whereTermId(123);

        // Should return true for new terms
        $this->assertTrue($this->callShouldShowForTerm());
    }

    public function testShouldShowForTermWithMatchingId()
    {
        $this->container->whereTermId(123);
        $this->container->setTermId(123);

        $this->assertTrue($this->callShouldShowForTerm());
    }

    public function testShouldShowForTermWithNonMatchingId()
    {
        $this->container->whereTermId(456);
        $this->container->setTermId(123);

        $this->assertFalse($this->callShouldShowForTerm());
    }

    public function testShouldShowForTermWithMatchingSlug()
    {
        $this->container->whereTermSlug('test-term-123');
        $this->container->setTermId(123);

        // Default alias returns slug 'test-term-123' for term ID 123
        $this->assertTrue($this->callShouldShowForTerm());
    }

    public function testShouldShowForTermWithNonMatchingSlug()
    {
        $this->container->whereTermSlug('non-matching-slug');
        $this->container->setTermId(123);

        // Default alias returns slug 'test-term-123' which doesn't match 'non-matching-slug'
        $this->assertFalse($this->callShouldShowForTerm());
    }

    public function testRenderWithoutShouldShow()
    {
        $this->container->whereTermId(456);
        $this->container->setTermId(123);

        // Should not render anything if shouldShowForTerm returns false
        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRenderWithZeroTermId()
    {
        $this->container->setTermId(0);

        // Should not render anything for new terms
        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
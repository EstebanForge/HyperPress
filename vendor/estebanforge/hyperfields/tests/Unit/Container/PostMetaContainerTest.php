<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit\Container;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\Container\PostMetaContainer;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class PostMetaContainerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    private PostMetaContainer $container;

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
        Functions\when('apply_filters')->returnArg();

        $this->container = new PostMetaContainer('test_container', 'Test Container');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testContainerCreation()
    {
        $this->assertEquals('test_container', $this->container->getId());
        $this->assertEquals('Test Container', $this->container->getTitle());
        $this->assertEquals('normal', $this->container->getSetting('context'));
        $this->assertEquals('high', $this->container->getSetting('priority'));
    }

    public function testWherePostType()
    {
        $this->container->where('post');
        $this->container->where('page');

        $reflection = new \ReflectionClass($this->container);
        $postTypes = $reflection->getProperty('post_types');

        $this->assertContains('post', $postTypes->getValue($this->container));
        $this->assertContains('page', $postTypes->getValue($this->container));
    }

    public function testWherePreventsDuplicates()
    {
        $this->container->where('post');
        $this->container->where('post'); // Duplicate

        $reflection = new \ReflectionClass($this->container);
        $postTypes = $reflection->getProperty('post_types');

        $this->assertCount(1, $postTypes->getValue($this->container));
        $this->assertContains('post', $postTypes->getValue($this->container));
    }

    public function testWherePostId()
    {
        $this->container->wherePostId(123);
        $this->container->wherePostId(456);

        $reflection = new \ReflectionClass($this->container);
        $postIds = $reflection->getProperty('post_ids');

        $this->assertContains(123, $postIds->getValue($this->container));
        $this->assertContains(456, $postIds->getValue($this->container));
    }

    public function testWherePostIds()
    {
        $this->container->wherePostIds([123, 456, 789]);

        $reflection = new \ReflectionClass($this->container);
        $postIds = $reflection->getProperty('post_ids');

        $this->assertEquals([123, 456, 789], $postIds->getValue($this->container));
    }

    public function testWherePostSlug()
    {
        $this->container->wherePostSlug('sample-post');
        $this->container->wherePostSlug('another-post');

        $reflection = new \ReflectionClass($this->container);
        $postSlugs = $reflection->getProperty('post_slugs');

        $this->assertContains('sample-post', $postSlugs->getValue($this->container));
        $this->assertContains('another-post', $postSlugs->getValue($this->container));
    }

    public function testWherePostSlugs()
    {
        $this->container->wherePostSlugs(['slug-1', 'slug-2', 'slug-3']);

        $reflection = new \ReflectionClass($this->container);
        $postSlugs = $reflection->getProperty('post_slugs');

        $this->assertEquals(['slug-1', 'slug-2', 'slug-3'], $postSlugs->getValue($this->container));
    }

    public function testSetContext()
    {
        $this->container->setContext('side');
        $this->assertEquals('side', $this->container->getSetting('context'));
    }

    public function testSetPriority()
    {
        $this->container->setPriority('low');
        $this->assertEquals('low', $this->container->getSetting('priority'));
    }

    public function testSetPostId()
    {
        $this->container->setPostId(123);

        $reflection = new \ReflectionClass($this->container);
        $postId = $reflection->getProperty('post_id');

        $this->assertEquals(123, $postId->getValue($this->container));
    }

    public function testInitSetsPostIdFromGet()
    {
        global $pagenow;
        $_GET['post'] = '123';
        $pagenow = 'post.php';

        $this->container->init();

        $reflection = new \ReflectionClass($this->container);
        $postId = $reflection->getProperty('post_id');

        $this->assertEquals(123, $postId->getValue($this->container));
    }

    public function testInitRegistersHooks()
    {
        Functions\expect('add_action')->once()->with('add_meta_boxes', [$this->container, 'attach']);
        Functions\expect('add_action')->once()->with('save_post', [$this->container, '_save']);
        Functions\expect('add_action')->once()->with('add_attachment', [$this->container, '_save']);
        Functions\expect('add_action')->once()->with('edit_attachment', [$this->container, '_save']);

        $this->container->init();
    }

    public function testAttachWithPostTypes()
    {
        $this->container->where('post');
        $this->container->where('page');

        Functions\expect('add_meta_box')
            ->once()
            ->with(
                'test_container',
                'Test Container',
                [$this->container, 'render'],
                'post',
                'normal',
                'high'
            );

        Functions\expect('add_meta_box')
            ->once()
            ->with(
                'test_container',
                'Test Container',
                [$this->container, 'render'],
                'page',
                'normal',
                'high'
            );

        $this->container->attach();
    }

    public function testAttachToSpecificPosts()
    {
        global $pagenow;
        $pagenow = 'post.php';
        $this->container->wherePostId(123);
        $_GET['post'] = '123';

        Functions\expect('get_post_type')->once()->with(123)->andReturn('post');
        Functions\expect('add_meta_box')
            ->once()
            ->with(
                'test_container',
                'Test Container',
                [$this->container, 'render'],
                'post',
                'normal',
                'high'
            );

        $this->container->attach();
    }

    public function testIsValidSaveWithAutosave()
    {
        define('DOING_AUTOSAVE', true);

        $this->assertFalse($this->container->isValidSave());
    }

    public function testIsValidSaveWithoutNonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $this->assertFalse($this->container->isValidSave());
    }

    public function testIsValidSaveWithValidData()
    {
        $_POST['post_ID'] = '123';
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn((object) [
            'cap' => (object) ['edit_post' => 'edit_posts']
        ]);
        Functions\when('current_user_can')->justReturn(true);

        $this->assertTrue($this->container->isValidSave());
    }

    public function testSave()
    {
        $this->container->setPostId(123);

        $field = Field::make('text', 'test_field', 'Test Field');
        $this->container->addField($field);

        $_POST['test_field'] = 'test value';
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\expect('update_post_meta')
            ->once()
            ->with(123, 'test_field', 'test value');

        Functions\expect('do_action')
            ->once()
            ->with('hyperpress/fields/post_meta_container_saved', 123, $this->container);

        $this->container->save();
    }

    public function testRenderWithFields()
    {
        $this->container->setPostId(123);

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'saved_value']);

        $this->container->addField($field);

        Functions\expect('get_post_meta')
            ->once()
            ->with(123, 'test_field', true)
            ->andReturn('saved_value');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('hyperfields_metabox_test_container', '_hyperfields_metabox_nonce_test_container');

        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('hyperfields-container', $output);
        $this->assertStringContainsString('data-container-id="test_container"', $output);
    }

    public function testRenderUsesGlobalPost()
    {
        global $post;
        $post = (object) ['ID' => 456];

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'default']);

        $this->container->addField($field);

        Functions\expect('get_post_meta')
            ->once()
            ->with(456, 'test_field', true)
            ->andReturn('');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('hyperfields_metabox_test_container', '_hyperfields_metabox_nonce_test_container');

        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('hyperfields-container', $output);
    }
}
<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit\Container;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\Container\UserMetaContainer;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class UserMetaContainerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    private UserMetaContainer $container;

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
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('apply_filters')->returnArg();

        $this->container = new UserMetaContainer('test_container', 'Test Container');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Helper method to call protected userHasRequiredRole method using reflection
     */
    private function callUserHasRequiredRole(int $user_id): bool
    {
        $reflection = new \ReflectionClass($this->container);
        $method = $reflection->getMethod('userHasRequiredRole');
        return $method->invoke($this->container, $user_id);
    }

    public function testContainerCreation()
    {
        $this->assertEquals('test_container', $this->container->getId());
        $this->assertEquals('Test Container', $this->container->getTitle());
    }

    public function testWhereRole()
    {
        $this->container->where('administrator');
        $this->container->where('editor');

        $reflection = new \ReflectionClass($this->container);
        $userRoles = $reflection->getProperty('user_roles');

        $this->assertContains('administrator', $userRoles->getValue($this->container));
        $this->assertContains('editor', $userRoles->getValue($this->container));
    }

    public function testWherePreventsDuplicates()
    {
        $this->container->where('administrator');
        $this->container->where('administrator'); // Duplicate

        $reflection = new \ReflectionClass($this->container);
        $userRoles = $reflection->getProperty('user_roles');

        $this->assertCount(1, $userRoles->getValue($this->container));
        $this->assertContains('administrator', $userRoles->getValue($this->container));
    }

    public function testWhereUserId()
    {
        $this->container->whereUserId(123);
        $this->container->whereUserId(456);

        $reflection = new \ReflectionClass($this->container);
        $userIds = $reflection->getProperty('user_ids');

        $this->assertContains(123, $userIds->getValue($this->container));
        $this->assertContains(456, $userIds->getValue($this->container));
    }

    public function testWhereUserIds()
    {
        $this->container->whereUserIds([123, 456, 789]);

        $reflection = new \ReflectionClass($this->container);
        $userIds = $reflection->getProperty('user_ids');

        $this->assertEquals([123, 456, 789], $userIds->getValue($this->container));
    }

    public function testSetUserId()
    {
        $this->container->setUserId(123);

        $reflection = new \ReflectionClass($this->container);
        $userId = $reflection->getProperty('user_id');

        $this->assertEquals(123, $userId->getValue($this->container));
    }

    public function testInitSetsUserIdFromProfilePage()
    {
        global $pagenow;
        $pagenow = 'profile.php';
        Functions\when('get_current_user_id')->justReturn(42);

        $this->container->init();

        $reflection = new \ReflectionClass($this->container);
        $userId = $reflection->getProperty('user_id');

        $this->assertEquals(42, $userId->getValue($this->container));
    }

    public function testInitSetsUserIdFromUserEditPage()
    {
        global $pagenow;
        $pagenow = 'user-edit.php';
        $_GET['user_id'] = '123';

        $this->container->init();

        $reflection = new \ReflectionClass($this->container);
        $userId = $reflection->getProperty('user_id');

        $this->assertEquals(123, $userId->getValue($this->container));
    }

    public function testInitRegistersHooks()
    {
        Functions\expect('add_action')->once()->with('show_user_profile', [$this->container, 'render']);
        Functions\expect('add_action')->once()->with('edit_user_profile', [$this->container, 'render']);
        Functions\expect('add_action')->once()->with('personal_options_update', [$this->container, '_save']);
        Functions\expect('add_action')->once()->with('edit_user_profile_update', [$this->container, '_save']);
        Functions\expect('add_action')->once()->with('user_register', [$this->container, '_save']);

        $this->container->init();
    }

    public function testIsValidSaveWithoutNonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $this->assertFalse($this->container->isValidSave());
    }

    public function testIsValidSaveWithInvalidUserId()
    {
        Functions\when('wp_verify_nonce')->justReturn(true);

        $this->assertFalse($this->container->isValidSave());
    }

    public function testIsValidSaveWithValidData()
    {
        $this->container->setUserId(123);
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $this->assertTrue($this->container->isValidSave());
    }

    public function testUserHasRequiredRoleWithSpecificUserIds()
    {
        $this->container->whereUserId(123);

        $this->assertTrue($this->callUserHasRequiredRole(123));
        $this->assertFalse($this->callUserHasRequiredRole(456));
    }

    public function testUserHasRequiredRoleWithoutRoleRestriction()
    {
        $this->assertTrue($this->callUserHasRequiredRole(123));
    }

    public function testUserHasRequiredRoleWithRoleRestriction()
    {
        $this->container->where('administrator');

        $user = (object) [
            'ID' => 123,
            'roles' => ['administrator', 'editor']
        ];

        Functions\expect('get_userdata')
            ->once()
            ->with(123)
            ->andReturn($user);

        $this->assertTrue($this->callUserHasRequiredRole(123));
    }

    public function testUserHasRequiredRoleWithoutMatchingRole()
    {
        $this->container->where('administrator');

        $user = (object) [
            'ID' => 123,
            'roles' => ['editor', 'author']
        ];

        Functions\expect('get_userdata')
            ->once()
            ->with(123)
            ->andReturn($user);

        $this->assertFalse($this->callUserHasRequiredRole(123));
    }

    public function testUserHasRequiredRoleWithNonExistentUser()
    {
        $this->container->where('administrator');

        Functions\expect('get_userdata')
            ->once()
            ->with(123)
            ->andReturn(false);

        $this->assertFalse($this->callUserHasRequiredRole(123));
    }

    public function testSave()
    {
        $this->container->setUserId(123);

        $field = Field::make('text', 'test_field', 'Test Field');
        $this->container->addField($field);

        $_POST['test_field'] = 'test value';
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\expect('update_user_meta')
            ->once()
            ->with(123, 'test_field', 'test value');

        Functions\expect('do_action')
            ->once()
            ->with('hyperpress/fields/user_meta_container_saved', 123, $this->container);

        $this->container->save();
    }

    public function testSaveWithRoleRestriction()
    {
        $this->container->where('administrator');
        $this->container->setUserId(123);

        $field = Field::make('text', 'test_field', 'Test Field');
        $this->container->addField($field);

        $_POST['test_field'] = 'test value';
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_userdata')->justReturn((object) ['roles' => ['administrator']]);
        Functions\expect('update_user_meta')
            ->once()
            ->with(123, 'test_field', 'test value');

        Functions\expect('do_action')
            ->once()
            ->with('hyperpress/fields/user_meta_container_saved', 123, $this->container);

        $this->container->save();
    }

    public function testSaveWithoutRoleRestrictionSkips()
    {
        $this->container->where('administrator');
        $this->container->setUserId(123);

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_userdata')->justReturn((object) ['roles' => ['editor']]);

        Functions\expect('update_user_meta')->never();

        $this->container->save();
    }

    public function testRenderWithFields()
    {
        $this->container->setUserId(123);

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'saved_value']);
        $field->shouldReceive('getHelp')->andReturn('This is help text');
        $field->shouldReceive('getLabel')->andReturn('Test Label');

        $this->container->addField($field);

        Functions\expect('get_user_meta')
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

        $this->assertStringContainsString('form-table', $output);
        $this->assertStringContainsString('hyperfields-container', $output);
        $this->assertStringContainsString('data-container-id="test_container"', $output);
        $this->assertStringContainsString('<h2>Test Container</h2>', $output);
        $this->assertStringContainsString('This is help text', $output);
        $this->assertStringContainsString('Test Label', $output);
    }

    public function testRenderWithUserObject()
    {
        $user = (object) [
            'ID' => 456,
            'display_name' => 'Test User'
        ];

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('setContext')->with('metabox');
        $field->shouldReceive('render')->with(['value' => 'default']);
        $field->shouldReceive('getLabel')->andReturn('Test Field');
        $field->shouldReceive('getHelp')->andReturn(null);

        $this->container->addField($field);

        Functions\expect('get_user_meta')
            ->once()
            ->with(456, 'test_field', true)
            ->andReturn('');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('hyperfields_metabox_test_container', '_hyperfields_metabox_nonce_test_container');

        ob_start();
        $this->container->render($user);
        $output = ob_get_clean();

        $this->assertStringContainsString('hyperfields-container', $output);
        $this->assertStringContainsString('<label for="test_field">Test Field</label>', $output);
    }

    public function testRenderWithoutRoleRestriction()
    {
        $this->container->whereUserId(123);
        $this->container->setUserId(456);

        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRenderWithRoleRestriction()
    {
        $this->container->where('administrator');
        $this->container->setUserId(123);

        $user = (object) [
            'ID' => 123,
            'roles' => ['editor', 'author']
        ];

        Functions\when('get_userdata')->justReturn($user);

        ob_start();
        $this->container->render();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testSaveWrapper()
    {
        $_POST['_hyperfields_metabox_nonce_test_container'] = 'test_nonce';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_userdata')->justReturn((object) ['roles' => []]);

        $field = \Mockery::mock(Field::class);
        $field->shouldReceive('getName')->andReturn('test_field');
        $field->shouldReceive('getDefault')->andReturn('default');
        $field->shouldReceive('sanitizeValue')->with('test_value')->andReturn('test_value');

        $this->container->addField($field);

        $_POST['test_field'] = 'test_value';

        Functions\expect('update_user_meta')
            ->once()
            ->with(123, 'test_field', 'test_value');

        Functions\expect('do_action')
            ->once()
            ->with('hyperpress/fields/user_meta_container_saved', 123, $this->container);

        $this->container->_save(123);

        $reflection = new \ReflectionClass($this->container);
        $userId = $reflection->getProperty('user_id');

        $this->assertEquals(123, $userId->getValue($this->container));
    }
}
<?php

/**
 * Simple test demonstrating WP_Mock capabilities for HyperPress
 * 
 * This test shows various WP_Mock mocking techniques for WordPress functions
 */

use HyperPress\Tests\WordPressTestCase;

uses(WordPressTestCase::class);

test('WP_Mock can mock basic WordPress functions', function () {
    // Mock a simple function
    WP_Mock::userFunction('get_option')
        ->with('my_option')
        ->andReturn('my_value');
    
    expect(get_option('my_option'))->toBe('my_value');
});

test('WP_Mock can mock functions with different arguments', function () {
    // Mock with specific arguments
    WP_Mock::userFunction('get_post_meta')
        ->with(1, 'my_meta_key', true)
        ->andReturn('meta_value');
    
    expect(get_post_meta(1, 'my_meta_key', true))->toBe('meta_value');
});

test('WP_Mock can expect actions to be fired', function () {
    // Expect an action to be fired
    WP_Mock::expectAction('my_action', 'arg1', 'arg2');
    
    // Fire the action (in real code this would be called)
    do_action('my_action', 'arg1', 'arg2');
});

test('WP_Mock can expect filters to be applied', function () {
    // Expect a filter to be applied
    WP_Mock::expectFilter('my_filter', 'original_value');
    
    // Apply the filter
    apply_filters('my_filter', 'original_value');
});

test('WP_Mock can mock conditional functions', function () {
    // Mock is_admin to return true
    WP_Mock::userFunction('is_admin')->andReturn(true);
    
    expect(is_admin())->toBeTrue();
    
    // Change the mock
    WP_Mock::userFunction('is_admin')->andReturn(false);
    
    expect(is_admin())->toBeFalse();
});

test('WP_Mock can mock WordPress objects', function () {
    // Mock a WP_Post object
    $mock_post = Mockery::mock(\WP_Post::class);
    $mock_post->ID = 123;
    $mock_post->post_title = 'Test Post';
    $mock_post->post_content = 'Test Content';
    
    WP_Mock::userFunction('get_post')
        ->with(123)
        ->andReturn($mock_post);
    
    $post = get_post(123);
    expect($post)->toBeInstanceOf(\WP_Post::class);
    expect($post->ID)->toBe(123);
    expect($post->post_title)->toBe('Test Post');
});

test('WP_Mock can mock user functions', function () {
    // Mock user functions
    WP_Mock::userFunction('is_user_logged_in')->andReturn(true);
    WP_Mock::userFunction('current_user_can')
        ->with('edit_posts')
        ->andReturn(true);
    WP_Mock::userFunction('wp_get_current_user')
        ->andReturn((object)['ID' => 1, 'user_login' => 'admin']);
    
    expect(is_user_logged_in())->toBeTrue();
    expect(current_user_can('edit_posts'))->toBeTrue();
    
    $current_user = wp_get_current_user();
    expect($current_user->ID)->toBe(1);
    expect($current_user->user_login)->toBe('admin');
});

test('WP_Mock can mock transient functions', function () {
    // Mock transient functions
    WP_Mock::userFunction('get_transient')
        ->with('my_transient')
        ->andReturn('cached_value');
    
    WP_Mock::userFunction('set_transient')
        ->with('my_transient', 'new_value', 3600)
        ->andReturn(true);
    
    expect(get_transient('my_transient'))->toBe('cached_value');
    expect(set_transient('my_transient', 'new_value', 3600))->toBeTrue();
});

test('WP_Mock can mock file system operations', function () {
    // Mock file system functions
    WP_Mock::userFunction('file_exists')
        ->with('/path/to/file.php')
        ->andReturn(true);
    
    WP_Mock::userFunction('file_get_contents')
        ->with('/path/to/file.php')
        ->andReturn('<?php echo "Hello World";');
    
    WP_Mock::userFunction('file_put_contents')
        ->with('/path/to/file.php', '<?php echo "Hello";')
        ->andReturn(true);
    
    expect(file_exists('/path/to/file.php'))->toBeTrue();
    expect(file_get_contents('/path/to/file.php'))->toBe('<?php echo "Hello World";');
    expect(file_put_contents('/path/to/file.php', '<?php echo "Hello";'))->toBeTrue();
});

test('WP_Mock can mock error handling', function () {
    // Mock error handling
    WP_Mock::userFunction('is_wp_error')
        ->andReturn(false);
    
    WP_Mock::userFunction('wp_error')
        ->andReturn(new \WP_Error('error_code', 'Error message'));
    
    expect(is_wp_error('not_an_error'))->toBeFalse();
    
    $error = wp_error();
    expect($error)->toBeInstanceOf(\WP_Error::class);
});

test('WP_Mock can handle return callbacks', function () {
    // Mock with callback for dynamic return values
    WP_Mock::userFunction('get_option')
        ->andReturnArg(0); // Return the first argument
    
    expect(get_option('option1'))->toBe('option1');
    expect(get_option('option2'))->toBe('option2');
});

test('WP_Mock can handle multiple calls with different arguments', function () {
    // Mock different behaviors for different arguments
    WP_Mock::userFunction('get_option')
        ->with('option1')->andReturn('value1')
        ->with('option2')->andReturn('value2')
        ->with('option3')->andReturn('value3');
    
    expect(get_option('option1'))->toBe('value1');
    expect(get_option('option2'))->toBe('value2');
    expect(get_option('option3'))->toBe('value3');
});
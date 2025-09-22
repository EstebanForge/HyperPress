# HyperPress Testing with PestPHP, WP_Mock, and SQLite

This setup provides a complete testing environment for the HyperPress WordPress plugin using PestPHP v4, WP_Mock for WordPress function mocking, and SQLite for database testing.

## Setup

### Prerequisites
- PHP 8.1+
- Composer
- SQLite PDO extension

### Installation

1. Install dependencies:
   ```bash
   composer install
   ```

2. Set up the test database:
   ```bash
   php setup-sqlite-db.php
   ```

3. Run tests:
   ```bash
   # Run all tests
   composer test
   
   # Run unit tests only
   composer test:unit
   
   # Run integration tests only
   composer test:integration
   
   # Run feature tests only
   composer test:feature
   
   # Run tests with coverage
   composer test:coverage
   ```

## Configuration

### Test Configuration Files

- `phpunit.xml` - PHPUnit configuration for PestPHP and WP_Mock
- `tests/Pest.php` - PestPHP configuration and WordPress bootstrap
- `tests/TestCase.php` - Base test case class extending WP_Mock\Tools\TestCase
- `tests/bootstrap.php` - WordPress environment setup

### Database Configuration

The test suite uses SQLite for testing, which provides:
- Fast test execution
- No external database dependencies
- Easy test isolation

### Test Structure

```
tests/
├── Unit/           # Unit tests for individual classes
├── Integration/    # Integration tests for components
├── Feature/        # Feature tests for complete workflows
├── db/             # SQLite database file
├── bootstrap.php   # WordPress bootstrap
├── TestCase.php    # Base test case
└── Pest.php        # PestPHP configuration
```

## Creating Tests

### Unit Test Example

```php
<?php

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Main;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('Main class can be instantiated', function () {
    $main = new Main();
    expect($main)->toBeInstanceOf(Main::class);
});
```

### Available Assertions

PestPHP provides a variety of assertions:
- `expect($value)->toBe($expected)`
- `expect($value)->toBeTrue()`
- `expect($value)->toBeFalse()`
- `expect($value)->toBeInstanceOf($class)`
- `expect($value)->toContain($string)`
- `expect($value)->toHaveProperty($property)`

### WordPress Function Mocking with WP_Mock

The test suite uses WP_Mock for comprehensive WordPress function mocking:

```php
// Mock a simple function
WP_Mock::userFunction('get_option')
    ->with('my_option')
    ->andReturn('my_value');

// Mock with different arguments
WP_Mock::userFunction('get_post_meta')
    ->with(1, 'my_meta_key', true)
    ->andReturn('meta_value');

// Mock multiple calls with different arguments
WP_Mock::userFunction('get_option')
    ->with('option1')->andReturn('value1')
    ->with('option2')->andReturn('value2');

// Use passthru for functions that should return their arguments
WP_Mock::passthruFunction('__');
WP_Mock::passthruFunction('esc_html');

// Expect actions to be fired
WP_Mock::expectAction('my_action', 'arg1', 'arg2');
do_action('my_action', 'arg1', 'arg2');

// Expect filters to be applied
WP_Mock::expectFilter('my_filter', 'original_value');
apply_filters('my_filter', 'original_value');

// Mock conditional functions
WP_Mock::userFunction('is_admin')->andReturn(true);
expect(is_admin())->toBeTrue();

// Mock WordPress objects
$mock_post = Mockery::mock(\WP_Post::class);
$mock_post->ID = 123;
$mock_post->post_title = 'Test Post';

WP_Mock::userFunction('get_post')
    ->with(123)
    ->andReturn($mock_post);
```

### Advanced WP_Mock Features

```php
// Mock user functions
WP_Mock::userFunction('is_user_logged_in')->andReturn(true);
WP_Mock::userFunction('current_user_can')
    ->with('edit_posts')
    ->andReturn(true);

// Mock transient functions
WP_Mock::userFunction('get_transient')
    ->with('my_transient')
    ->andReturn('cached_value');

// Mock file system operations
WP_Mock::userFunction('file_exists')
    ->with('/path/to/file.php')
    ->andReturn(true);

// Use return callbacks for dynamic values
WP_Mock::userFunction('get_option')
    ->andReturnArg(0); // Return the first argument

// Mock error handling
WP_Mock::userFunction('is_wp_error')->andReturn(false);
```

## Test Coverage

To generate test coverage reports:

```bash
composer test:coverage
```

This will generate an HTML coverage report in the `coverage/` directory.

## Troubleshooting

### Common Issues

1. **SQLite extension not available**
   - Install the SQLite PDO extension: `sudo apt-get install php-sqlite3` (Ubuntu) or `sudo dnf install php-sqlite3` (Fedora)

2. **Permission denied**
   - Make sure the `tests/db/` directory is writable
   - Run `chmod 755 tests/db/`

3. **Tests failing with WordPress function errors**
   - Make sure WordPress functions are properly mocked using WP_Mock
   - Check `tests/TestCase.php` for available mocked functions
   - Use `WP_Mock::userFunction()` to mock functions in individual tests

### Adding New Mocks

WP_Mock automatically handles function mocking, but you can add common mocks to the base TestCase:

```php
// In tests/TestCase.php, add to mockCommonWordPressFunctions()
WP_Mock::userFunction('your_function')->andReturn('default_value');
```

### WP_Mock vs Brain\Monkey

This setup uses WP_Mock instead of Brain\Monkey for several advantages:

1. **Better WordPress Integration**: WP_Mock is specifically designed for WordPress testing
2. **Action/Filter Expectations**: Built-in support for expecting hooks to be fired
3. **Object Mocking**: Better support for mocking WordPress objects like WP_Post, WP_User, etc.
4. **Strict Mode**: Enforces that all mocked functions are actually called
5. **Patchwork Integration**: Uses Patchwork for function replacement, which is more reliable

**Note**: Brain\Monkey has been completely removed from this project. All testing now uses WP_Mock exclusively.

### Migration from Brain\Monkey

If you're migrating from Brain\Monkey, here are the key differences:

```php
// Old (Brain\Monkey)
Brain\Monkey\Functions\when('get_option')->justReturn('value');
Brain\Monkey\Actions\expectFired('init')->once();

// New (WP_Mock)
WP_Mock::userFunction('get_option')->andReturn('value');
WP_Mock::expectAction('init');
```

## Best Practices

1. **Isolate Tests**: Each test should be independent and not rely on other tests
2. **Mock Dependencies**: Mock WordPress functions and external dependencies
3. **Test Both Success and Failure**: Test both successful and error scenarios
4. **Use Descriptive Test Names**: Make test names clear and descriptive
5. **Clean Up After Tests**: Use `afterEach` to clean up test state

## Continuous Integration

This testing setup is designed to work well with CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.1'
    extensions: pdo_sqlite

- name: Install dependencies
  run: composer install

- name: Setup test database
  run: php setup-sqlite-db.php

- name: Run tests
  run: composer test
```

The SQLite database ensures fast and reliable tests in CI environments without requiring external database services.
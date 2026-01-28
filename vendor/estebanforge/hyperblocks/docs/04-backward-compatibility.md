# Backward Compatibility Strategy

## Overview

This document defines the backward compatibility strategy for ensuring existing HyperPress users can continue using their block definitions without modification after extracting block functionality to the HyperBlocks library.

## Compatibility Principles

1. **Zero Breaking Changes**: Existing code continues to work without modification
2. **Graceful Deprecation**: Clear deprecation warnings with migration guidance
3. **Transparent Migration**: Facade pattern hides implementation changes
4. **Tested Compatibility**: All existing tests pass without changes
5. **Future-Proof**: Clear path forward for new development

## Compatibility Layers

### Layer 1: Namespace Compatibility

```php
// Old API (continues to work)
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Registry;

// New API (recommended for new code)
use HyperBlocks\Block;
use HyperBlocks\Registry;
```

### Layer 2: Facade Pattern Implementation

The facade provides a complete drop-in replacement for the old API:

```php
namespace HyperPress\Blocks;

/**
 * Facade for backward compatibility.
 *
 * @deprecated 2.2.0 Use HyperBlocks\Block instead.
 */
class Block {
    /**
     * Proxy to the new Block implementation.
     */
    private \HyperBlocks\Block $proxy;

    /**
     * Constructor with deprecation notice.
     */
    public function __construct(string $title) {
        _deprecated_class(
            self::class,
            '2.2.0',
            '\\HyperBlocks\\Block',
            'Block functionality has been extracted to HyperBlocks library. ' .
            'See https://github.com/EstebanForge/HyperBlocks/blob/main/docs/migration-guide.md'
        );
        $this->proxy = \HyperBlocks\Block::make($title);
    }

    /**
     * Static factory method.
     */
    public static function make(string $title): self {
        return new self($title);
    }

    /**
     * All public methods forward to proxy.
     */
    public function setName(string $name): self {
        $this->proxy->setName($name);
        return $this;
    }

    public function setIcon(string $icon): self {
        $this->proxy->setIcon($icon);
        return $this;
    }

    public function addFields(array $fields): self {
        // Convert facade fields to real fields
        $realFields = array_map(function($field) {
            return $field->proxy ?? $field;
        }, $fields);
        $this->proxy->addFields($realFields);
        return $this;
    }

    public function addFieldGroup(string $groupName): self {
        $this->proxy->addFieldGroup($groupName);
        return $this;
    }

    public function setRenderTemplate(string $template): self {
        $this->proxy->setRenderTemplate($template);
        return $this;
    }

    /**
     * Magic property access for backward compatibility.
     */
    public function __get(string $name) {
        return $this->proxy->$name;
    }

    /**
     * Magic property setter for backward compatibility.
     */
    public function __set(string $name, $value) {
        $this->proxy->$name = $value;
    }
}
```

### Layer 3: Registry Facade

```php
namespace HyperPress\Blocks;

/**
 * Facade for backward compatibility.
 *
 * @deprecated 2.2.0 Use HyperBlocks\Registry instead.
 */
class Registry {
    /**
     * Singleton instance of facade.
     */
    private static ?self $instance = null;

    /**
     * Proxy to the new Registry.
     */
    private \HyperBlocks\Registry $proxy;

    /**
     * Private constructor.
     */
    private function __construct() {
        _deprecated_class(
            self::class,
            '2.2.0',
            '\\HyperBlocks\\Registry'
        );
        $this->proxy = \HyperBlocks\Registry::getInstance();
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Forward all methods to proxy.
     */
    public function registerFluentBlock(\HyperPress\Blocks\Block $block): void {
        $this->proxy->registerFluentBlock($block->proxy ?? $block);
    }

    public function getFluentBlock(string $blockName): ?\HyperPress\Blocks\Block {
        $realBlock = $this->proxy->getFluentBlock($blockName);
        if ($realBlock === null) {
            return null;
        }
        // Wrap in facade
        $facade = new \HyperPress\Blocks\Block($realBlock->title);
        $facade->proxy = $realBlock;
        return $facade;
    }

    public function registerFieldGroup(\HyperPress\Blocks\FieldGroup $group): void {
        $this->proxy->registerFieldGroup($group->proxy ?? $group);
    }

    public function getFieldGroup(string $groupId): ?\HyperPress\Blocks\FieldGroup {
        $realGroup = $this->proxy->getFieldGroup($groupId);
        if ($realGroup === null) {
            return null;
        }
        $facade = new \HyperPress\Blocks\FieldGroup($realGroup->name, $realGroup->id);
        $facade->proxy = $realGroup;
        return $facade;
    }
}
```

### Layer 4: Field Facade

```php
namespace HyperPress\Blocks;

/**
 * Facade for backward compatibility.
 *
 * @deprecated 2.2.0 Use HyperBlocks\Field instead.
 */
class Field {
    /**
     * Proxy to the new Field.
     */
    private \HyperBlocks\Field $proxy;

    /**
     * Constructor with deprecation notice.
     */
    public function __construct(string $type, string $name, string $label) {
        _deprecated_class(
            self::class,
            '2.2.0',
            '\\HyperBlocks\\Field'
        );
        $this->proxy = \HyperBlocks\Field::make($type, $name, $label);
    }

    /**
     * Static factory method.
     */
    public static function make(string $type, string $name, string $label): self {
        return new self($type, $name, $label);
    }

    /**
     * Forward all methods.
     */
    public function setDefault($default): self {
        $this->proxy->setDefault($default);
        return $this;
    }

    public function setPlaceholder(string $placeholder): self {
        $this->proxy->setPlaceholder($placeholder);
        return $this;
    }

    public function setRequired(bool $required = true): self {
        $this->proxy->setRequired($required);
        return $this;
    }

    public function setHelp(string $help): self {
        $this->proxy->setHelp($help);
        return $this;
    }

    public function getHyperField(): \HyperFields\Field {
        return $this->proxy->getHyperField();
    }

    /**
     * Magic property access.
     */
    public function __get(string $name) {
        return $this->proxy->$name;
    }

    /**
     * Magic property setter.
     */
    public function __set(string $name, $value) {
        $this->proxy->$name = $value;
    }
}
```

## Compatibility Guarantees

### What Will Continue to Work

1. **All Existing Block Definitions**
   ```php
   // This continues to work without changes
   use HyperPress\Blocks\Block;
   use HyperPress\Blocks\Field;
   use HyperPress\Blocks\Registry;

   $block = Block::make('Hero Banner')
       ->setName('my-theme/hero-banner')
       ->setIcon('star-filled')
       ->addFields([
           Field::make('text', 'heading', 'Heading')
               ->setDefault('Welcome'),
       ]);

   Registry::getInstance()->registerFluentBlock($block);
   ```

2. **All Field Definitions**
   ```php
   use HyperPress\Blocks\Field;

   $field = Field::make('text', 'title', 'Title')
       ->setPlaceholder('Enter title')
       ->setRequired(true);
   ```

3. **All Field Groups**
   ```php
   use HyperPress\Blocks\FieldGroup;
   use HyperPress\Blocks\Field;

   $group = FieldGroup::make('Common Fields', 'common')
       ->addFields([
           Field::make('text', 'title', 'Title'),
           Field::make('textarea', 'description', 'Description'),
       ]);

   Registry::getInstance()->registerFieldGroup($group);
   ```

4. **All Template References**
   ```php
   $block->setRenderTemplate('file:blocks/hero-banner.hb.php');
   ```

5. **All REST API Endpoints**
   - `/wp-json/hyperblocks/v1/block-fields`
   - `/wp-json/hyperblocks/v1/render-preview`

### What Requires Updates

1. **Direct Class Name References (if not using `use` statements)**
   ```php
   // Old - still works with deprecation notice
   $block = new \HyperPress\Blocks\Block('Title');

   // New - recommended for new code
   $block = new \HyperBlocks\Block('Title');
   ```

2. **Reflection-Based Code**
   ```php
   // If using reflection on class names, update them
   $class = new \ReflectionClass('HyperPress\Blocks\Block'); // Works with notice
   $class = new \ReflectionClass('HyperBlocks\Block'); // New code
   ```

## Deprecation Strategy

### Deprecation Notices

```php
/**
 * @deprecated 2.2.0 Use HyperBlocks\Block instead.
 *             See: https://github.com/EstebanForge/HyperBlocks/blob/main/docs/migration-guide.md
 */
_deprecated_class(
    'HyperPress\\Blocks\\Block',
    '2.2.0',
    '\\HyperBlocks\\Block',
    'Block functionality has been extracted to HyperBlocks library. ' .
    'See migration guide for details.'
);
```

### Deprecation Timeline

| Version | Action | User Impact |
|---------|--------|-------------|
| 2.1.0 | Current version with blocks in HyperPress | No impact |
| 2.2.0 | HyperPress uses HyperBlocks, facades with warnings | Deprecation notices in debug mode |
| 2.3.0 | Enhanced warnings, encourage migration | More prominent warnings |
| 3.0.0 | Remove facades, breaking change | Requires code updates |

### Deprecation Control

```php
// Allow developers to opt-out of deprecation warnings during transition
define('HYPERPRESS_SUPPRESS_DEPRECATIONS', true);

// Or enable strict mode to catch all issues early
define('HYPERPRESS_STRICT_MODE', true);
```

## Migration Guide for Users

### Step 1: Update Imports

```php
// Before
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Field;
use HyperPress\Blocks\Registry;

// After
use HyperBlocks\Block;
use HyperBlocks\Field;
use HyperBlocks\Registry;
```

### Step 2: Update Class Names (if using fully qualified names)

```php
// Before
$block = new \HyperPress\Blocks\Block('Title');

// After
$block = new \HyperBlocks\Block('Title');
```

### Step 3: Update Composer Dependencies (when ready)

```bash
# No changes needed - HyperPress manages the dependency
composer update
```

### Step 4: Test Thoroughly

```bash
# Run tests
composer test

# Test in development environment
# - Create blocks using new API
# - Verify block rendering
# - Check REST API endpoints
```

## Testing Compatibility

### Compatibility Test Suite

```php
namespace HyperPress\Tests\Compatibility;

class BackwardCompatibilityTest extends \PHPUnit\Framework\TestCase {
    public function test_old_block_api_works() {
        use HyperPress\Blocks\Block;
        use HyperPress\Blocks\Field;

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->addFields([
                Field::make('text', 'title', 'Title'),
            ]);

        $this->assertEquals('Test Block', $block->title);
        $this->assertEquals('test/block', $block->name);
        $this->assertCount(1, $block->fields);
    }

    public function test_new_block_api_works() {
        use HyperBlocks\Block;
        use HyperBlocks\Field;

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->addFields([
                Field::make('text', 'title', 'Title'),
            ]);

        $this->assertEquals('Test Block', $block->title);
        $this->assertEquals('test/block', $block->name);
        $this->assertCount(1, $block->fields);
    }

    public function test_registry_facade_works() {
        use HyperPress\Blocks\Registry;
        use HyperPress\Blocks\Block;
        use HyperPress\Blocks\Field;

        $block = Block::make('Test')
            ->setName('test/block');

        Registry::getInstance()->registerFluentBlock($block);

        $retrieved = Registry::getInstance()->getFluentBlock('test/block');
        $this->assertNotNull($retrieved);
        $this->assertEquals('Test', $retrieved->title);
    }
}
```

### Integration Testing

```bash
# Test with real WordPress environment
cd HyperPress
wp plugin activate hyperpress
wp scaffold plugin-tests
phpunit
```

## Rollback Plan

If compatibility issues arise:

1. **Feature Flag**: Revert to old implementation
   ```php
   define('HYPERPRESS_USE_LEGACY_BLOCKS', true);
   ```

2. **Version Rollback**: Deploy HyperPress v2.1.0
   ```bash
   git checkout v2.1.0
   composer update
   ```

3. **Dual Release**: Maintain compatibility branch
   ```bash
   git checkout -b 2.1.x-maintenance
   # Apply patches and release v2.1.1, v2.1.2, etc.
   ```

4. **Migration Assistance**: Provide migration scripts
   ```bash
   php scripts/migrate-blocks.php
   ```

## Documentation Updates

### User-Facing Documentation

1. **README**: Update to mention HyperBlocks dependency
2. **Migration Guide**: Step-by-step migration instructions
3. **Changelog**: Document breaking changes and migration path
4. **Examples**: Update all examples to use new namespace

### Developer Documentation

1. **API Reference**: Document both old and new APIs
2. **Deprecation Guide**: Explain deprecation timeline
3. **Contributing**: Update contribution guidelines
4. **Architecture**: Document new architecture

## Support Strategy

### During Transition Period

1. **Community Support**: Answer questions in forums/issues
2. **Documentation**: Provide comprehensive guides
3. **Examples**: Create migration examples
4. **Testing**: Provide test cases for common patterns

### After Removal of Facades

1. **Archive Old Versions**: Keep v2.1.x available
2. **Migration Scripts**: Automated migration tools
3. **Support Window**: Continue supporting v2.1.x for 6 months
4. **FAQ**: Address common migration questions

## Success Metrics

### Compatibility Success Indicators

1. **All existing tests pass** without modification
2. **Zero breaking changes** for existing users
3. **Clear deprecation path** with documentation
4. **Performance parity** or improvement
5. **Developer adoption** of new namespace

### Migration Success Indicators

1. **80% of users** migrate within 3 months
2. **Zero critical issues** reported
3. **Positive feedback** on new architecture
4. **Increased adoption** of HyperBlocks library
5. **Community contributions** to new library

## Conclusion

The backward compatibility strategy ensures a smooth transition from the monolithic HyperPress\Blocks namespace to the standalone HyperBlocks library. By implementing a comprehensive facade layer with clear deprecation notices, existing users can continue using their code while new users adopt the improved architecture.

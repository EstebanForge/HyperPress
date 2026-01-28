# Extraction Approach and API Boundaries

## Overview

This document defines the extraction strategy for migrating block functionality from `HyperPress\Blocks\*` to a standalone `HyperBlocks` library, with clear API boundaries and separation of concerns.

## Extraction Goals

1. **Self-Contained Library**: HyperBlocks must be independently usable
2. **Zero Hard Dependencies**: Only depend on HyperFields (well-defined dependency)
3. **Clear API Boundaries**: Explicit interfaces between components
4. **Backward Compatibility**: Existing HyperPress usage continues to work
5. **Testable in Isolation**: Can be tested without WordPress context

## Source Code Mapping

### Files to Extract

| Original Path | Target Path | Purpose |
|---------------|-------------|---------|
| `src/Blocks/Block.php` | `src/Block/Block.php` | Fluent API block definition |
| `src/Blocks/Field.php` | `src/Block/Field.php` | Field wrapper for blocks |
| `src/Blocks/FieldGroup.php` | `src/Block/FieldGroup.php` | Reusable field groups |
| `src/Blocks/Registry.php` | `src/Registry.php` | Block registration management |
| `src/Blocks/Renderer.php` | `src/Renderer.php` | Block rendering engine |
| `src/Blocks/RestApi.php` | `src/RestApi.php` | REST API endpoints |
| `includes/backward-compatibility.php` | `src/BackwardCompatibility.php` | BC layer for HyperPress |
| `hyperblocks/*` | `examples/*` | Example blocks (moved to examples) |

### Namespace Mapping

```
HyperPress\Blocks\* → HyperBlocks\*

Specific mappings:
- HyperPress\Blocks\Block → HyperBlocks\Block
- HyperPress\Blocks\Field → HyperBlocks\Field
- HyperPress\Blocks\FieldGroup → HyperBlocks\FieldGroup
- HyperPress\Blocks\Registry → HyperBlocks\Registry
- HyperPress\Blocks\Renderer → HyperBlocks\Renderer
- HyperPress\Blocks\RestApi → HyperBlocks\RestApi
```

## API Boundaries

### Layer 1: Core Library (HyperBlocks)

```php
namespace HyperBlocks;

// Core classes - no WordPress hard dependencies
class Block {}
class Field {}
class FieldGroup {}
class Registry {}
class Renderer {}
class Config {}
```

**Responsibilities:**
- Define block structure and fluent API
- Manage block registry
- Render blocks from templates
- Provide configuration interface

**Boundaries:**
- Uses HyperFields for field definitions (dependency)
- Abstracts WordPress via interfaces
- No direct calls to `add_action()`, `register_block_type()`, etc.

### Layer 2: WordPress Integration (HyperBlocks\WordPress)

```php
namespace HyperBlocks\WordPress;

// WordPress-specific integration
class Bootstrap {}
class BlockRegistration {}
class RestApi {}
class EditorScript {}
```

**Responsibilities:**
- Hook into WordPress lifecycle
- Register blocks with WordPress
- Register REST API routes
- Enqueue editor assets

**Boundaries:**
- Wraps Core Library for WordPress integration
- Can be swapped for other CMS integrations

### Layer 3: HyperPress Compatibility (HyperPress\Blocks)

```php
namespace HyperPress\Blocks;

// Facade layer for backward compatibility
class Block {
    private static ?\HyperBlocks\Block $proxy = null;

    public static function make(string $title): self
    {
        // Forward to HyperBlocks\Block
        $instance = new self($title);
        $instance->proxy = \HyperBlocks\Block::make($title);
        return $instance;
    }
}

class Registry {
    private static ?\HyperBlocks\Registry $proxy = null;

    public static function getInstance(): self
    {
        if (null === self::$proxy) {
            self::$proxy = \HyperBlocks\Registry::getInstance();
        }
        return self::$proxy;
    }
}
```

**Responsibilities:**
- Maintain existing HyperPress API
- Forward calls to HyperBlocks library
- Deprecate old usage patterns

## Dependencies

### Required Dependencies

```
HyperBlocks
├── HyperFields (library)
│   ├── HyperFields\Field
│   └── HyperFields\BlockFieldAdapter
└── PHP 8.1+
```

### Optional Dependencies

```
HyperBlocks\WordPress
├── WordPress (via WordPress abstraction)
└── HyperPress (via backward compatibility)
```

## Extraction Workflow

### Phase 1: Prepare Foundation (Current)

1. ✓ Create HyperBlocks repository
2. ✓ Design configuration interface
3. ✓ Document extraction approach
4. ⏳ Plan namespace migration
5. ⏳ Define backward compatibility

### Phase 2: Extract Core Library

1. Copy core classes to HyperBlocks
2. Refactor to remove WordPress hard dependencies
3. Create WordPress abstraction layer
4. Write unit tests (no WordPress context)
5. Ensure HyperFields integration works

### Phase 3: WordPress Integration

1. Create `HyperBlocks\WordPress` namespace
2. Implement `Bootstrap` class for WordPress hooks
3. Create `BlockRegistration` class
4. Port `RestApi` functionality
5. Write integration tests

### Phase 4: Backward Compatibility

1. Create facade in HyperPress
2. Add deprecation notices
3. Test existing HyperPress functionality
4. Update HyperPress documentation

### Phase 5: Migration

1. Update HyperPress to depend on HyperBlocks
2. Remove extracted code from HyperPress
3. Release HyperBlocks v1.0
4. Release HyperPress with HyperBlocks dependency

## API Boundaries in Detail

### Core Library APIs (WordPress-Agnostic)

```php
// Block definition - no WordPress
$block = Block::make('Hero Banner')
    ->setName('my-theme/hero-banner')
    ->setIcon('star-filled')
    ->addFields([
        Field::make('text', 'heading', 'Heading')
            ->setDefault('Welcome'),
        Field::make('image', 'background', 'Background'),
    ])
    ->setRenderTemplate('file:blocks/hero-banner.hb.php');

// Registry - no WordPress
$registry = Registry::getInstance();
$registry->registerFluentBlock($block);

// Renderer - no WordPress
$renderer = new Renderer();
$html = $renderer->render(
    'file:blocks/hero-banner.hb.php',
    ['heading' => 'Welcome', 'background' => 123]
);
```

### WordPress Integration APIs

```php
// Bootstrap - hooks into WordPress
$bootstrap = new Bootstrap();
$bootstrap->init();

// BlockRegistration - registers with WordPress
$registration = new BlockRegistration();
$registration->register($block);

// RestApi - WordPress REST endpoints
$restApi = new RestApi();
$restApi->init();
```

### HyperPress Facade APIs

```php
// Existing API - continues to work
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Registry;

$block = Block::make('Hero Banner')
    ->setName('hyperblocks/hero-banner')
    ->setIcon('star-filled');

Registry::getInstance()->registerFluentBlock($block);
```

## Data Flow

### Block Registration Flow

```
Developer Code
    ↓
HyperPress\Blocks\Block (facade)
    ↓
HyperBlocks\Block (core)
    ↓
HyperBlocks\Registry (core)
    ↓
HyperBlocks\WordPress\BlockRegistration
    ↓
WordPress register_block_type()
```

### Block Rendering Flow

```
WordPress Editor (Gutenberg)
    ↓
Block Attributes (JSON)
    ↓
HyperBlocks\Renderer (core)
    ↓
Template Execution (.hb.php)
    ↓
HTML Output
```

## Testing Strategy

### Unit Tests (HyperBlocks Core)

```php
// Tests without WordPress context
class BlockTest extends TestCase {
    public function test_block_creation() {
        $block = Block::make('Test');
        $this->assertEquals('Test', $block->title);
    }

    public function test_field_addition() {
        $block = Block::make('Test')
            ->addFields([Field::make('text', 'name', 'Name')]);
        $this->assertCount(1, $block->fields);
    }
}
```

### Integration Tests (HyperBlocks\WordPress)

```php
// Tests with WordPress context
class WordPressIntegrationTest extends TestCase {
    public function test_block_registration() {
        $block = Block::make('Test')
            ->setName('test/block');
        $registration = new BlockRegistration();
        $result = $registration->register($block);
        $this->assertTrue($result);
    }
}
```

### Compatibility Tests (HyperPress)

```php
// Tests backward compatibility
class BackwardCompatibilityTest extends TestCase {
    public function test_facade_forwards_to_library() {
        $block = HyperPress\Blocks\Block::make('Test');
        $this->assertInstanceOf(HyperBlocks\Block::class, $block->proxy);
    }
}
```

## Migration Checklist

- [ ] Core classes extracted and tested
- [ ] WordPress integration layer created
- [ ] Backward compatibility facade implemented
- [ ] All existing tests pass
- [ ] New tests added
- [ ] Documentation updated
- [ ] HyperPress depends on HyperBlocks
- [ ] Original code removed from HyperPress
- [ ] Release notes prepared

## Rollback Strategy

If issues arise during migration:

1. **Feature Flag**: Add `HYPERBLOCKS_LEGACY_MODE` constant
2. **Graceful Degradation**: Facade falls back to old implementation
3. **Migration Script**: Database migration for existing options
4. **Version Pinning**: Lock to working HyperBlocks version

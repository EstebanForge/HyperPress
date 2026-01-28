# Namespace Migration Plan

## Overview

This document outlines the migration strategy for moving block functionality from `HyperPress\Blocks\*` to `HyperBlocks\*`, including a step-by-step approach to maintain backward compatibility while establishing the new namespace structure.

## Namespace Structure

### Current Structure (HyperPress)

```
HyperPress\
├── Blocks\
│   ├── Block.php
│   ├── Field.php
│   ├── FieldGroup.php
│   ├── Registry.php
│   ├── Renderer.php
│   └── RestApi.php
└── ... (other HyperPress code)
```

### Target Structure (HyperBlocks)

```
HyperBlocks\
├── Block\
│   ├── Block.php
│   ├── Field.php
│   └── FieldGroup.php
├── Registry.php
├── Renderer.php
├── RestApi.php
├── WordPress\
│   ├── Bootstrap.php
│   ├── BlockRegistration.php
│   └── EditorScript.php
└── ... (other HyperBlocks code)
```

### Compatibility Structure (HyperPress - Post-Migration)

```
HyperPress\
├── Blocks\
│   ├── Block.php (facade)
│   ├── Field.php (facade)
│   ├── FieldGroup.php (facade)
│   ├── Registry.php (facade)
│   ├── Renderer.php (facade)
│   └── RestApi.php (facade)
└── ... (other HyperPress code, no longer contains block logic)
```

## Namespace Mapping Table

| Class | Old Namespace | New Namespace | Migration Path |
|-------|---------------|----------------|----------------|
| Block | `HyperPress\Blocks\Block` | `HyperBlocks\Block` | Facade → New |
| Field | `HyperPress\Blocks\Field` | `HyperBlocks\Field` | Facade → New |
| FieldGroup | `HyperPress\Blocks\FieldGroup` | `HyperBlocks\FieldGroup` | Facade → New |
| Registry | `HyperPress\Blocks\Registry` | `HyperBlocks\Registry` | Facade → New |
| Renderer | `HyperPress\Blocks\Renderer` | `HyperBlocks\Renderer` | Facade → New |
| RestApi | `HyperPress\Blocks\RestApi` | `HyperBlocks\RestApi` | Facade → New |
| Bootstrap | N/A | `HyperBlocks\WordPress\Bootstrap` | New |
| BlockRegistration | N/A | `HyperBlocks\WordPress\BlockRegistration` | New |

## Migration Phases

### Phase 1: Preparation (Current)

1. **Create HyperBlocks Repository** ✓
   - Initialize project structure
   - Set up composer.json
   - Configure PSR-4 autoloading

2. **Design New Namespace Structure** ✓
   - Document API boundaries
   - Define dependency relationships
   - Plan facade layer

3. **Prepare HyperPress for Migration**
   - Tag current version: `v2.1.0`
   - Create feature branch: `feature/hyperblocks-extraction`

### Phase 2: Create HyperBlocks Library

1. **Set Up Project Structure**
   ```
   HyperBlocks/
   ├── composer.json
   ├── src/
   │   ├── Block/
   │   │   ├── Block.php
   │   │   ├── Field.php
   │   │   └── FieldGroup.php
   │   ├── Registry.php
   │   ├── Renderer.php
   │   ├── RestApi.php
   │   ├── Config.php
   │   ├── WordPress/
   │   │   ├── Bootstrap.php
   │   │   └── BlockRegistration.php
   │   └── BackwardCompatibility.php
   ├── tests/
   └── docs/
   ```

2. **Copy and Refactor Core Classes**
   - Copy classes from HyperPress\Blocks
   - Update namespace to HyperBlocks
   - Remove WordPress hard dependencies
   - Add WordPress abstraction layer

3. **Create WordPress Integration Layer**
   - Implement `HyperBlocks\WordPress\Bootstrap`
   - Implement `HyperBlocks\WordPress\BlockRegistration`
   - Port `RestApi` to use abstraction

4. **Add Unit Tests**
   - Test core classes without WordPress
   - Test WordPress integration
   - Test HyperFields integration

5. **Release HyperBlocks v1.0**
   - Tag release: `v1.0.0`
   - Publish to Packagist (if applicable)

### Phase 3: Create HyperPress Facade

1. **Create Facade Classes**
   ```php
   // HyperPress/Blocks/Block.php
   namespace HyperPress\Blocks;

   use HyperBlocks\Block as NewBlock;
   use HyperBlocks\Field as NewField;
   use HyperBlocks\FieldGroup as NewFieldGroup;

   class Block {
       private NewBlock $proxy;

       public function __construct(string $title) {
           _deprecated_file(
               __CLASS__,
               '2.2.0',
               '\\HyperBlocks\\Block',
               'Block functionality has been extracted to HyperBlocks library'
           );
           $this->proxy = NewBlock::make($title);
       }

       public static function make(string $title): self {
           return new self($title);
       }

       // Forward all methods to proxy
       public function setName(string $name): self {
           $this->proxy->setName($name);
           return $this;
       }

       public function addFields(array $fields): self {
           $newFields = array_map(function($field) {
               return $field->proxy ?? $field;
           }, $fields);
           $this->proxy->addFields($newFields);
           return $this;
       }

       // ... other methods
   }
   ```

2. **Create Registry Facade**
   ```php
   // HyperPress/Blocks/Registry.php
   namespace HyperPress\Blocks;

   use HyperBlocks\Registry as NewRegistry;

   class Registry {
       private static ?NewRegistry $proxy = null;

       public static function getInstance(): self {
           _deprecated_file(
               __CLASS__,
               '2.2.0',
               '\\HyperBlocks\\Registry',
               'Registry has been extracted to HyperBlocks library'
           );
           return new self();
       }

       private function getProxy(): NewRegistry {
           if (self::$proxy === null) {
               self::$proxy = NewRegistry::getInstance();
           }
           return self::$proxy;
       }

       // Forward methods
       public function registerFluentBlock(object $block): void {
           $this->getProxy()->registerFluentBlock($block->proxy ?? $block);
       }
   }
   ```

3. **Create Field Facade**
   ```php
   // HyperPress/Blocks/Field.php
   namespace HyperPress\Blocks;

   use HyperFields\Field as HyperField;
   use HyperBlocks\Field as NewField;

   class Field {
       private NewField $proxy;

       public function __construct(string $type, string $name, string $label) {
           _deprecated_file(
               __CLASS__,
               '2.2.0',
               '\\HyperBlocks\\Field',
               'Field has been extracted to HyperBlocks library'
           );
           // HyperBlocks\Field wraps HyperFields\Field directly
           $this->proxy = NewField::make($type, $name, $label);
       }

       public static function make(string $type, string $name, string $label): self {
           return new self($type, $name, $label);
       }

       // Forward methods
   }
   ```

4. **Create Remaining Facades**
   - `HyperPress\Blocks\Renderer` → `HyperBlocks\Renderer`
   - `HyperPress\Blocks\RestApi` → `HyperBlocks\RestApi`
   - `HyperPress\Blocks\FieldGroup` → `HyperBlocks\FieldGroup`

### Phase 4: Update HyperPress Dependencies

1. **Add HyperBlocks to composer.json**
   ```json
   {
     "require": {
       "estebanforge/hyperblocks": "^1.0"
     }
   }
   ```

2. **Update Autoloading**
   ```json
   {
     "autoload": {
       "psr-4": {
         "HyperPress\\Blocks\\": "includes/backward-compat/Blocks/"
       }
     }
   }
   ```

3. **Remove Old Block Code**
   - Delete `src/Blocks/` directory
   - Move facade code to `includes/backward-compat/Blocks/`
   - Update references throughout codebase

### Phase 5: Testing and Validation

1. **Run All Tests**
   ```bash
   cd HyperPress
   composer test
   ```

2. **Test Backward Compatibility**
   - Existing block definitions still work
   - Facade methods forward correctly
   - Deprecation warnings displayed

3. **Test New Functionality**
   - New `HyperBlocks\*` namespace works
   - WordPress integration works
   - REST API endpoints work

4. **Manual Testing**
   - Create test blocks using old API
   - Create test blocks using new API
   - Verify block rendering
   - Verify REST API functionality

### Phase 6: Release

1. **Update Version Numbers**
   - HyperPress: `v2.2.0` (with HyperBlocks dependency)
   - Update CHANGELOG with migration notes

2. **Update Documentation**
   - Update README with new dependencies
   - Create migration guide
   - Update examples

3. **Release Notes**
   - Highlight breaking changes
   - Provide migration path
   - Document deprecation timeline

## Migration Timeline

| Week | Tasks | Deliverables |
|------|-------|--------------|
| 1 | Create HyperBlocks repository, set up structure | Project scaffolded |
| 2 | Copy and refactor core classes, add unit tests | Core library functional |
| 3 | Create WordPress integration layer | WordPress hooks working |
| 4 | Create HyperPress facades, update dependencies | Backward compatibility working |
| 5 | Testing and validation, bug fixes | All tests passing |
| 6 | Documentation and release | v1.0.0 and v2.2.0 released |

## Deprecation Timeline

- **v2.1.0** (Current): Last version with blocks in HyperPress
- **v2.2.0** (Release): HyperPress uses HyperBlocks, facades with deprecation warnings
- **v2.3.0** (Planned): Deprecation warnings become errors in dev mode
- **v3.0.0** (Future): Remove facades, require direct use of HyperBlocks

## Rollback Plan

If migration encounters critical issues:

1. **Revert HyperPress**: Rollback to v2.1.0
2. **Maintain Both**: Keep both versions available
3. **Feature Flag**: Add `HYPERPRESS_USE_HYPERBLOCKS` constant
4. **Gradual Migration**: Allow opt-in via configuration

## Migration Scripts

### Class Aliases Script (For Transition Period)

```php
// Add to HyperPress bootstrap during transition
if (defined('HYPERPRESS_ENABLE_ALIASES') && HYPERPRESS_ENABLE_ALIASES) {
    class_alias('HyperBlocks\Block', 'HyperPress\Blocks\Block');
    class_alias('HyperBlocks\Field', 'HyperPress\Blocks\Field');
    class_alias('HyperBlocks\Registry', 'HyperPress\Blocks\Registry');
    class_alias('HyperBlocks\Renderer', 'HyperPress\Blocks\Renderer');
}
```

### Search/Replace Script

```bash
# Find all uses of old namespace
find . -name "*.php" -type f -exec grep -l "use HyperPress\\Blocks" {} \;

# Replace namespace references (manual review required)
# Old: use HyperPress\Blocks\Block;
# New: use HyperBlocks\Block;
```

## Verification Checklist

- [ ] All classes migrated to new namespace
- [ ] All tests passing in HyperBlocks
- [ ] All tests passing in HyperPress
- [ ] Backward compatibility verified
- [ ] Deprecation warnings working
- [ ] Documentation updated
- [ ] Examples updated
- [ ] Release notes prepared
- [ ] Composer dependencies updated
- [ ] Autoloading configured correctly

## Post-Migration Cleanup

1. **Remove Feature Flags**
   - Remove `HYPERPRESS_ENABLE_ALIASES`
   - Remove `HYPERPRESS_USE_HYPERBLOCKS`

2. **Clean Up Code**
   - Remove commented-out old code
   - Remove unused imports
   - Update inline comments

3. **Archive Old Code**
   - Archive old HyperPress\Blocks directory
   - Store in `archived/` directory with readme

4. **Update CI/CD**
   - Update test scripts
   - Update deployment processes
   - Update dependency checks

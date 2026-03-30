# HyperFields Transfer Extensions

This document describes the generic, extensible transfer features added to HyperFields.

Goal:
- Keep HyperFields framework-agnostic.
- Support option-backed and content-backed portability.
- Allow downstream libraries/apps to plug in custom modules without forking HyperFields.

## 1) Extended Options Export/Import

Class: `HyperFields\ExportImport`

### New capabilities

- Scalar option values are now supported in export/import.
- Import supports `mode`:
  - `merge` (default): for array options, incoming keys overwrite existing keys while preserving unrelated existing keys.
  - `replace`: for array options, incoming array replaces existing array.
- New dry-run diff helper:
  - `ExportImport::diffOptions(...)`

### API

```php
use HyperFields\ExportImport;

$json = ExportImport::exportOptions(['plugin_options']);

$diff = ExportImport::diffOptions(
    $json,
    ['plugin_options'],
    '',
    ['mode' => 'replace']
);

$result = ExportImport::importOptions(
    $json,
    ['plugin_options'],
    '',
    ['mode' => 'replace']
);
```

### Notes

- Prefix filters apply to array keys only.
- Scalar option values are skipped when a non-empty prefix is provided.
- Backups still use transient keys (`backup_keys`) and `restoreBackup()` still works.

---

## 2) Generic Pages/CPT Export/Import

Class: `HyperFields\ContentExportImport`

This is a generic content portability utility for post-like records (pages + CPT).

### Export

```php
use HyperFields\ContentExportImport;

$json = ContentExportImport::exportPosts(
    ['page', 'my_cpt'],
    [
        'post_status' => ['publish', 'draft', 'private'],
        'include_meta' => true,
        'include_private_meta' => false,
        'include_meta_keys' => [],      // optional allowlist
        'exclude_meta_keys' => ['_edit_lock'],
        'include_content' => true,
        'include_excerpt' => true,
        'include_parent' => true,
    ]
);
```

### Snapshot (for external compare workflows)

```php
$snapshot = ContentExportImport::snapshotPosts(['page', 'my_cpt']);
```

### Import

```php
$result = ContentExportImport::importPosts(
    $json,
    [
        'allowed_post_types' => ['page', 'my_cpt'],
        'dry_run' => false,
        'create_missing' => true,
        'update_existing' => true,
        'include_meta' => true,
        'meta_mode' => 'merge', // 'merge' | 'replace'
        'include_private_meta' => false,
        'include_meta_keys' => [],
        'exclude_meta_keys' => ['_edit_lock'],
    ]
);
```

### Dry-run diff

```php
$preview = ContentExportImport::diffPosts($json, [
    'allowed_post_types' => ['page', 'my_cpt'],
]);
```

Import matching rules:
- Canonical identity = `post_type + slug`.
- Existing records are resolved by `get_page_by_path($slug, OBJECT, $post_type)`.

---

## 3) Extensible Transfer Module Registry

Class: `HyperFields\Transfer\Manager`

Use this when you need HyperFields as the base transfer library, while composing project-specific modules externally.

### Register modules

```php
use HyperFields\Transfer\Manager;

$manager = new Manager();

$manager->registerModule(
    'options',
    exporter: function (array $context): array {
        return ['json' => \HyperFields\ExportImport::exportOptions($context['option_names'] ?? [])];
    },
    importer: function (array $payload, array $context): array {
        return \HyperFields\ExportImport::importOptions(
            (string) ($payload['json'] ?? ''),
            $context['allowed_options'] ?? []
        );
    },
    differ: function (array $payload, array $context): array {
        return \HyperFields\ExportImport::diffOptions(
            (string) ($payload['json'] ?? ''),
            $context['allowed_options'] ?? []
        );
    }
);
```

### Export/import/diff bundles

```php
$bundle = $manager->export();          // or export(['options'])
$diff   = $manager->diff($bundle);
$apply  = $manager->import($bundle);
```

Bundle shape:

```php
[
  'schema_version' => 1,
  'type' => 'hyperfields_transfer_bundle',
  'generated_at' => '...',
  'modules' => [
    'options' => [/* module payload */]
  ],
  'errors' => []
]
```

---

## 4) Public Facade + Helper Functions

### Facade (`HyperFields\HyperFields`)

- `HyperFields::diffOptions(...)`
- `HyperFields::exportPosts(...)`
- `HyperFields::snapshotPosts(...)`
- `HyperFields::importPosts(...)`
- `HyperFields::diffPosts(...)`
- `HyperFields::makeTransferManager()`

### Helpers (`includes/helpers.php`)

- `hf_diff_options(...)`
- `hf_export_posts(...)`
- `hf_snapshot_posts(...)`
- `hf_import_posts(...)`
- `hf_diff_posts(...)`

---

## 5) Extension Guidance

Recommended pattern for downstream integrations:

1. Keep HyperFields generic and reusable.
2. Register project-specific transfer modules in your application/plugin layer using `Transfer\Manager`.
3. Keep module payloads stable and versioned in your application contract.
4. Use `diffOptions()` / `diffPosts()` for safe dry-run previews before import.


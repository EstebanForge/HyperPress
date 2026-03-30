# WP Settings Compatibility Layer Plan

## Goal

Add a generic compatibility layer in HyperFields so plugins currently using a WP settings package can migrate incrementally with minimal rewrites, while preserving data shape, extension points, and behavior.

## Scope

In scope:

- Compatibility API for settings pages structured as tabs, sections, and options.
- Hook/filter compatibility points for extension and save lifecycle.
- Storage adapters to support legacy option layouts during migration.
- Field-type compatibility needed by existing usage patterns.
- Validation/error bridge compatible with WordPress Settings API expectations.
- Migration utilities for dual-read/write and rollback safety.

Out of scope (initially):

- Tight coupling to any plugin-specific naming or business logic.
- Gutenberg/block editor field migration behavior.
- Any non-settings UI migration outside wp-admin settings pages.

## Design Principles

- Generic naming: no product/plugin-specific terms in HyperFields core.
- Backward-safe: migration can run with fallback reads and optional dual writes.
- Incremental adoption: allow one tab/section/field group at a time.
- Deterministic behavior: explicit tab ordering, hook order, and save semantics.
- Observability first: emit metrics/log hooks for read/write/fallback paths.

## Proposed Architecture

### 1) Compatibility Facade

Add a compatibility entrypoint that converts legacy-style settings configuration into HyperFields options pages.

Suggested class:

- `HyperFields\Compatibility\WPSettingsCompatibility`

Responsibilities:

- Register pages from compatibility schema.
- Translate tabs/sections/options to HyperFields objects.
- Attach lifecycle hooks (extend, before/after save, validation, errors).

### 2) Schema Model

Add lightweight schema objects or array contracts:

- `SettingsPageSchema`
- `SettingsTabSchema`
- `SettingsSectionSchema`
- `SettingsFieldSchema`

Minimum schema support:

- tab key/title/priority/callback
- section key/title/description/link-like behavior
- field type/name/label/default/description/attributes/options/render/sanitize/validate

### 3) Storage Adapter Layer

Add pluggable storage strategies:

- `ArrayOptionStore` (single option array like `option_name[field_key]`)
- `SingleOptionStore` (one option per field)
- `FallbackReadStore` (read-new then read-legacy)
- `DualWriteStore` (write-new and write-legacy during transition)

Store interface:

- `get(string $key, mixed $default = null): mixed`
- `set(string $key, mixed $value): bool`
- `delete(string $key): bool`
- `all(): array`

### 4) Compatibility Hooks

Provide a generic hook namespace for legacy extension behavior:

- `hyperfields/settings/tabs`
- `hyperfields/settings/tab/{key}`
- `hyperfields/settings/extend`
- `hyperfields/settings/before_save`
- `hyperfields/settings/after_save`
- `hyperfields/settings/validate`
- `hyperfields/settings/errors`

Compatibility bridge should also allow mapping old hook names to these new hooks during migration.

### 5) Field Compatibility Pack

Ensure parity for commonly used legacy field types:

- `choices` (single selection/radio)
- `select_multiple`
- `code_editor` (CodeMirror-backed field)
- `custom` render callback (already partly supported)

Behavior parity requirements:

- custom attributes
- default values
- HTML descriptions
- dynamic options from callables

## Save and Validation Flow

Target flow:

1. Resolve incoming values.
2. Normalize field names to schema keys.
3. Run field sanitizers.
4. Run schema validators.
5. Trigger `before_save`.
6. Persist via configured store adapter.
7. Collect errors/warnings via bridge (`add_settings_error` support).
8. Trigger `after_save`.

Notes:

- Warnings must not block saves unless explicitly configured.
- Validation errors should preserve previous values when appropriate.

## Migration Utilities

Add migration helper service:

- `CompatibilityMigrator`

Capabilities:

- key mapping (`old_key => new_key`)
- dry-run preview and diff
- option backup snapshot
- restore/rollback
- unmapped key report
- fallback-hit report (what is still read from legacy store)

## Implementation Phases

### Phase 0: Foundation

- Create compatibility namespace and interfaces.
- Add schema contracts and store interface.
- Add base compatibility facade with no-op translation.

Exit criteria:

- Compiles, unit tests for schema and store contracts.

### Phase 1: Storage Adapters

- Implement `ArrayOptionStore`, `SingleOptionStore`, `FallbackReadStore`, `DualWriteStore`.
- Add tests for read/write/delete semantics and fallback precedence.

Exit criteria:

- Adapters tested with both scalar and array values.

### Phase 2: Settings Translation

- Implement tab/section/field translation to existing `OptionsPage` + `OptionsSection` + `Field`.
- Support deterministic tab priorities and section rendering order.

Exit criteria:

- A sample legacy schema renders correctly and persists values.

### Phase 3: Hook and Save Lifecycle

- Add compatibility hooks and old-to-new hook bridge mapping.
- Add validation + error bridge and lifecycle events.

Exit criteria:

- Hook order and save behavior covered by integration tests.

### Phase 4: Field Parity

- Implement/complete `choices`, `select_multiple`, and `code_editor` compatibility.
- Validate dynamic option callbacks and custom renderer parity.

Exit criteria:

- Existing settings forms using these field patterns migrate without functional regressions.

### Phase 5: Migration Tooling

- Add migrator with dry-run, backup, restore, and reports.
- Add CLI-friendly entrypoint (optional but recommended).

Exit criteria:

- Migration dry-run + rollback tested on fixture options datasets.

### Phase 6: Documentation and Adoption Guide

- Add docs for compatibility schema, adapters, hooks, and migration steps.
- Add cookbook: “migrate one tab”, “dual write”, “cutover”, “cleanup legacy”.

Exit criteria:

- End-to-end migration walkthrough documented.

## Testing Strategy

- Unit tests:
  - schema validation
  - storage adapter behavior
  - field compatibility transform
- Integration tests:
  - settings page render and save
  - hook execution order
  - add_settings_error interoperability
  - fallback and dual-write correctness
- Migration tests:
  - dry-run diff output
  - backup/restore
  - cutover behavior

## Rollout Strategy

- Default to fallback-read enabled and dual-write enabled for first rollout.
- Collect telemetry/hooks to confirm reads are moving to new store.
- Cut to new-only writes after stability window.
- Remove fallback once unmapped/fallback-hit report reaches zero.

## Risks and Mitigations

- Risk: subtle save behavior mismatches.
  - Mitigation: lifecycle integration tests + staged rollout.
- Risk: field rendering parity gaps.
  - Mitigation: explicit compatibility field pack and snapshot tests.
- Risk: hidden legacy dependencies in extension hooks.
  - Mitigation: old-to-new hook bridge + deprecation logs.

## Deliverables

- Compatibility layer classes/interfaces in `src/Compatibility/`.
- Storage adapters and tests.
- Hook bridge and save lifecycle integration.
- Migration helper service and tests.
- Documentation updates in `docs/`.

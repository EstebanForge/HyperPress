# WPSettings One-to-One Parity Plan (HyperFields)

## Purpose

This document defines the implementation plan to reach one-to-one behavioral parity between:

- `jeffreyvanrossum/wp-settings` (as used by downstream plugins), and
- HyperFields (`WPSettingsCompatibility` + core HyperFields rendering/saving APIs).

Goal: migrate existing WPSettings integrations to HyperFields with near-zero behavior drift, minimal plugin-side rewrites, and safe phased rollout.

Important: parity is functional, not naming-level cloning. HyperFields remains the source of truth for naming conventions, class structure, and code style.

---

## Target Outcomes

1. Existing WPSettings integrations can be expressed in HyperFields compatibility config without changing option keys.
2. Saved data shape and write semantics match WPSettings expectations.
3. Admin UI behavior (tabs/sections/section links, field rendering, code editor, notices, validation feedback) is equivalent.
4. HyperFields hooks are canonical; integrating plugins are updated to use HyperFields hook names during migration.
5. Migration can be done incrementally plugin-by-plugin and tab-by-tab with rollback support.

---

## Naming and Style Rules (HyperFields-first)

1. New core capabilities must use HyperFields naming conventions and architecture.
2. WPSettings names should only exist at adapter boundaries for migration compatibility.
3. Do not introduce WPSettings-prefixed internals unless they are explicit transition shims.
4. Prefer existing HyperFields extension points over adding parallel WPSettings-shaped APIs.
5. When both are needed:
   - canonical API = HyperFields name
   - migration work updates plugin callers to canonical names

---

## Current Baseline

Already implemented in HyperFields:

- `WPSettingsCompatibility` registration and tab/section/option translation.
- Field alias mapping (`choices`, `select-multiple`, `code-editor` -> HyperFields types).
- Lifecycle hooks (`before_save`, `validate`, `after_save`).
- Compatibility stores and migrator (`ArrayOptionStore`, `SingleOptionStore`, `FallbackReadStore`, `DualWriteStore`, `CompatibilityMigrator`).
- Public API surface:
  - `HyperFields::registerWPSettingsCompatibilityPage()`
  - `hf_register_wpsettings_compatibility_page()`
  - `hp_register_wpsettings_compatibility_page()`

Parity gaps still open are listed below as implementation work items.

---

## Parity Gaps and Implementation Plan

## 1) Section Link Navigation Parity (`as_link`, `?section=`)

### WPSettings behavior

- Sections can be marked with `as_link`.
- Active tab may show a sub-section menu (`subsubsub`) based on linked sections.
- Rendering rules:
  - If all sections are links, render first linked section by default.
  - If `section` query arg exists, render only matching linked section.
  - Otherwise render non-linked sections.

### HyperFields gap

- Current HyperFields options page handles tabs but not section-link navigation semantics.

### Implementation

1. Extend `SectionProxy` to preserve:
   - `slug`
   - `as_link` boolean
2. Extend `OptionsSection` metadata support for section-link flags.
3. Enhance `OptionsPage`:
   - Parse active section from request.
   - Render section menu when current tab has linked sections.
   - Select active render set using WPSettings-compatible rules.
4. Add compatibility helper method in `WPSettingsCompatibility` to map section args directly.

### Acceptance criteria

- Section menu appears and behaves equivalent to WPSettings.
- URLs with `&section={slug}` select same section as WPSettings.
- No regression for existing non-linked sections.

---

## 2) Option-Level Nested Storage Parity (`option_level`, dotted paths)

### WPSettings behavior

- Tab/section can be marked `option_level(true)`.
- Option key path becomes `tab.section.option` (dot path resolved into nested arrays).
- Input names map to nested option arrays.

### HyperFields gap

- HyperFields currently stores flat keys under option group; no built-in dotted key path compatibility in this flow.

### Implementation

1. Add compatibility args:
   - tab: `option_level` boolean
   - section: `option_level` boolean
2. In compatibility translation:
   - Compute canonical key path exactly as WPSettings would.
   - Keep current field name for UI id, but save using computed nested key path.
3. Add path utility:
   - `setByPath(array &$data, string $path, mixed $value): void`
   - `getByPath(array $data, string $path, mixed $default = null): mixed`
4. Integrate into sanitize/save pipeline for compatibility pages only.

### Acceptance criteria

- Nested option structures match WPSettings format byte-for-byte for equivalent inputs.
- Existing flat-key compatibility pages remain unchanged unless `option_level` is enabled.

---

## 3) `visible` Callback Parity

### WPSettings behavior

- Option can define `visible` callable; when false, field is not rendered.

### HyperFields gap

- No direct compatibility mapping for `visible` in option args.

### Implementation

1. Add `visible` support in compatibility translator:
   - If callable returns false, skip field registration for current request.
2. Add defensive fallback:
   - If callable throws, log warning and treat as visible to avoid hidden breakages.

### Acceptance criteria

- Fields with `visible => fn() => false` are absent from render and not processed on save.

---

## 4) True Code Editor Parity (`code-editor`)

### WPSettings behavior

- Enqueues WP CodeMirror/editor assets.
- Initializes code editor instance with configurable `editor_type`.
- Stores raw content (no text sanitization).

### HyperFields gap

- `code-editor` currently maps to textarea behavior.

### Implementation

1. Register built-in compatibility option type `code-editor` via `OptionTypeRegistry`:
   - Render callback outputs textarea markup with stable id.
   - Enqueue `wp-theme-plugin-editor`, `wp-codemirror`.
   - Initialize using `wp_enqueue_code_editor(['type' => ...])`.
   - Sanitize callback returns raw value (same as WPSettings code editor).
2. Keep fallback mapping to textarea only when editor scripts unavailable.

### Acceptance criteria

- Editing UX and stored value behavior matches WPSettings code editor.
- Guest checkout email template editing remains unchanged after migration.

---

## 5) Option Type Map Parity (`wp_settings_option_type_map`)

### WPSettings behavior

- Filter-driven map from type string to option implementation.
- Supports custom option types globally.

### HyperFields gap

- `OptionTypeRegistry` exists but lacks first-class bridge filter compatibility.

### Implementation

1. Keep HyperFields canonical registry API as primary:
   - `OptionTypeRegistry` remains the official extension mechanism.
2. Add optional bridge filter in `WPSettingsCompatibility` adapter:
   - Read `wp_settings_option_type_map` only inside compatibility flow.
2. Support adapter contract:
   - Map type key to render/sanitize/validate callbacks for registry.
3. Document adapter registration pattern for custom project-specific option types.

### Acceptance criteria

- Existing custom WPSettings option types can be registered once and rendered in compatibility pages.

---

## 6) CSS Argument Parity (`css.input_class`, `css.label_class`)

### WPSettings behavior

- Option args may include:
  - `css.input_class`
  - `css.label_class`

### HyperFields gap

- Partial support via attributes, but no direct parity mapping for these keys.

### Implementation

1. In compatibility translation:
   - Map `css.input_class` to field wrapper/input class arg.
   - Map `css.label_class` to label class arg.
2. Update templates to consistently consume these args.

### Acceptance criteria

- Existing UI class hooks used by plugin admin CSS keep working with no plugin code changes.

---

## 7) Description Rendering Semantics (HTML vs escaped)

### WPSettings behavior

- Descriptions are typically rendered as HTML (plugin-supplied markup).

### HyperFields gap

- Most field templates escape help/description text; some migration paths expect HTML descriptions.

### Implementation

1. Add compatibility mode flag per field/page:
   - `allow_html_descriptions` default false.
2. In compatibility pages, default to WP-safe HTML rendering via `wp_kses_post`.
3. Keep non-compatibility HyperFields behavior unchanged.

### Acceptance criteria

- WPSettings-style descriptions containing links/markup render correctly in compatibility pages.

---

## 8) Validation/Error Feedback Parity

### WPSettings behavior

- Option-level validation can add field-specific errors (`Error` container + per-field feedback).
- Global error notice shown when any validation fails.

### HyperFields gap

- Lifecycle validation exists, but inline field-level feedback parity is incomplete.

### Implementation

1. Add compatibility error collector keyed by field name.
2. During save:
   - Run field-level validators.
   - Store error messages for failed fields.
   - Preserve old value for invalid fields only.
3. Render:
   - Show inline field errors (`wps-error-feedback` equivalent class).
   - Show global summary notice when any error exists.

### Acceptance criteria

- Invalid fields produce inline feedback and block only invalid values.

---

## 9) Menu Icon/Position Parity

### WPSettings behavior

- Supports `set_menu_icon()` and `set_menu_position()`.

### HyperFields gap

- Compatibility registration currently does not map these fields.

### Implementation

1. Add config support in `WPSettingsCompatibility`:
   - `menu_icon`
   - `menu_position`
2. Propagate into `OptionsPage` registration path.

### Acceptance criteria

- Pages registered via compatibility config can match existing menu position/icon behavior.

---

## 10) Hook Migration to HyperFields Canonical Names

### WPSettings behavior

- Hooks used by plugins include:
  - `wp_settings_before_render_settings_page`
  - `wp_settings_after_render_settings_page`
  - `wp_settings_new_options`
  - `wp_settings_new_options_{name}`

### HyperFields gap

- Existing integrations may still reference WPSettings-era hook names.

### Implementation

1. Keep HyperFields hooks canonical in core behavior (`hyperfields/settings/*` + configured prefix hooks).
2. Do not emit WPSettings alias hooks from HyperFields compatibility code.
3. In each migrating plugin, rename hook registrations/callback wiring to HyperFields hook names.
4. Document hook mapping table in migration guides for plugin maintainers.

### Acceptance criteria

- Migrated plugins use HyperFields hooks only and maintain equivalent behavior/order.

---

## Implementation Sequence (Recommended)

## Phase 1: Core Behavioral Parity (blocking)

1. Section link navigation (`as_link`, `section` query)
2. Code editor parity
3. CSS arg mapping
4. Hook alias parity

## Phase 2: Data Shape and Validation Parity

1. Option-level nested path storage
2. Visible callback parity
3. Field-level validation/error rendering

## Phase 3: Extensibility and Hardening

1. `wp_settings_option_type_map` bridge
2. Menu icon/position mapping
3. HTML description compatibility mode
4. Additional docs and migration recipes

---

## Test Plan

## Unit tests

- Path resolution/set/get for option-level nested keys.
- Section selection state machine (`tab`, `section`, linked/non-linked mix).
- Type-map bridge and custom type registration behavior.
- Visibility callback skip logic.
- Lifecycle hook order and alias hook emission.

## Integration tests

- Render snapshots for:
  - linked sections
  - mixed section modes
  - code editor option
  - field errors (inline + global)
- Save behavior for:
  - valid + invalid mixed payload
  - nested option-level writes
  - fallback read / dual write stores

## Compatibility fixtures

- Add fixture configs mirroring:
  - base settings tab fragments
  - integrations section examples
  - feature-specific tab examples

Pass criteria: fixture output and persisted option arrays match WPSettings baseline.

---

## Rollout Strategy

1. Ship parity features behind compatibility-only code paths.
2. Migrate base plugin first (single tab), compare DB snapshots before/after.
3. Migrate guest checkout and financial fields.
4. Keep hook aliases active for at least one release cycle.
5. Collect migration telemetry/logging for:
   - fallback reads
   - validation failures
   - unknown option types

---

## Definition of Done

- All parity gaps in this document are implemented or explicitly deferred with rationale.
- HyperFields tests pass.
- New compatibility tests pass with fixtures.
- At least one real downstream plugin tab migrates without behavioral regression.
- Migration guide updated with examples and rollback procedure.

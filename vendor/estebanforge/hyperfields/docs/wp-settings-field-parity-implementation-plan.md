# WPSettings Field Parity Implementation Plan (HyperFields)

## Purpose

This document defines the implementation plan to achieve full field-level feature parity between:

- WPSettings field system (`jeffreyvanrossum/wp-settings`)
- HyperFields compatibility layer (`WPSettingsCompatibility`) and core rendering/saving behavior

Scope here is field parity and field-related behavior. Naming remains HyperFields-native.

---

## Goals

1. Every WPSettings field type used in real plugin settings can be represented and persisted with equivalent behavior in HyperFields.
2. Option args commonly used with WPSettings fields (validation, sanitize, attributes, editor config, media config) produce equivalent UI and storage behavior.
3. Migration from WPSettings to HyperFields requires minimal downstream code changes.
4. Parity implementation is testable, deterministic, and non-breaking for existing HyperFields users.

---

## Current State Summary

Already implemented:

- Type mapping for:
  - `choices` -> `radio`
  - `select-multiple` -> `multiselect`
  - `wp-editor` -> `rich_text`
  - `code-editor` -> custom code editor renderer
- `visible` callback support (field can be skipped)
- CSS mapping:
  - `css.input_class`
  - `css.label_class`
- HTML description rendering support via `help_is_html`
- Tabs/sections link behavior (`as_link`, `section` query behavior)

Open parity gaps are listed below.

---

## Field Parity Gaps

## 1) Missing Type Mapping for `media` and `video`

### Gap

WPSettings supports `media`, `image`, `video`. HyperFields currently does not map `media`/`video` to valid internal field types.

### Impact

- Any downstream use of WPSettings `media` or `video` fails or cannot be migrated cleanly.

### Implementation

1. Add compatibility map entries:
   - `media` -> HyperFields `file` (or a dedicated compatibility custom field preserving media ID semantics)
   - `video` -> HyperFields `file` with media library type restrictions
2. Add support args for media library constraints:
   - `media_library` passthrough (title, button text, allowed mime/type, multiple=false)
3. Ensure stored value semantics match WPSettings expectation (attachment ID where applicable).

### Acceptance Criteria

- `media`, `image`, and `video` options render and save without migration hacks.
- Value shape is stable and documented.

---

## 2) `text` Option `type` Override Not Applied in Template

### Gap

Compatibility can set `input_type` for `text` options, but templates do not consistently consume it.

### Impact

- `text` options configured as number/email/url still render as text inputs.

### Implementation

1. Standardize template behavior:
   - `field-input.php` should use `input_type` when field type is `text`.
2. Keep backward compatibility:
   - If `input_type` missing, use `text`.

### Acceptance Criteria

- `text` + `type:number|email|url|password` renders matching input element type.

---

## 3) `attributes` and `custom_attributes` Not Rendered

### Gap

Compatibility stores these args, but templates do not print arbitrary attributes.

### Impact

- Settings like `min`, `step`, `disabled`, custom data attrs are ignored.

### Implementation

1. Add shared template utility for rendering allowed HTML attributes from field args.
2. Consume it in:
   - input/textarea/select/multiselect/checkbox/radio/custom templates
3. Security:
   - allowlist attribute names and escape values
   - drop unsafe event attributes (`on*`)

### Acceptance Criteria

- Attributes from compatibility args appear in rendered markup safely.

---

## 4) Textarea `rows` and `cols` Parity

### Gap

WPSettings supports `rows` and `cols`; HyperFields currently uses fixed dimensions.

### Implementation

1. Map `rows` and `cols` from compatibility args to field args.
2. Update textarea rendering to use provided values with sane defaults.

### Acceptance Criteria

- `textarea` options respect `rows` and `cols` config values.

---

## 5) WP Editor Config Parity

### Gap

WPSettings supports a broad `wp_editor` config (`wpautop`, `teeny`, `media_buttons`, `textarea_rows`, `tinymce`, `quicktags`, etc.). Compatibility currently maps to `rich_text` but does not pass through full config.

### Implementation

1. Define `rich_text_editor_settings` arg schema in HyperFields.
2. Map WPSettings args into this schema:
   - `wpautop`, `teeny`, `media_buttons`, `default_editor`, `drag_drop_upload`, `textarea_rows`, `tabindex`, `tabfocus_elements`, `editor_css`, `editor_class`, `tinymce`, `quicktags`
3. Update `field-rich-text.php` to merge provided settings into `wp_editor` call.

### Acceptance Criteria

- Rich text options render with equivalent editor behavior and toolbar settings.

---

## 6) Custom `render` Callback Parity (Named Fields)

### Gap

Compatibility currently honors `render` only when `name` is missing.

### Impact

- Named options using custom render callbacks cannot migrate 1:1.

### Implementation

1. Always allow `render` callback when present.
2. Preserve `name`/`name_attr` handling in callback data.
3. Keep sanitize/validate behavior configurable for custom-render fields.

### Acceptance Criteria

- Named custom-render options behave the same as in WPSettings.

---

## 7) `wp_settings_option_type_map` Bridge

### Gap

WPSettings allows global custom type registration via `wp_settings_option_type_map` filter. HyperFields has `OptionTypeRegistry` but no adapter bridge.

### Implementation

1. In compatibility flow only, read `wp_settings_option_type_map` and bridge unsupported type keys into `OptionTypeRegistry` wrappers.
2. Wrapper behavior:
   - instantiate option class
   - call its render/sanitize/validate logic
3. Add guardrails:
   - only bridge known-compatible classes (or class implementing expected methods)
   - log and skip invalid mappings

### Acceptance Criteria

- Existing custom WPSettings option types work in compatibility mode without rewriting all custom types first.

---

## 8) Per-Option `validate` and `sanitize` Arg Parity

### Gap

WPSettings option args support:
- `validate` (callable or rule array with feedback)
- `sanitize` callable

Compatibility currently maps `validation` array but not WPSettings-native `validate` and `sanitize` option args for standard fields.

### Implementation

1. Add mapping for `sanitize` callable:
   - wrap into field sanitize callback path (custom or core field adapter)
2. Add mapping for `validate`:
   - callable validation
   - rule arrays with feedback callbacks
3. Integrate validation into options-page save flow (not just object API calls).

### Acceptance Criteria

- Validation failures prevent invalid writes and generate user feedback.
- Custom sanitize callback is applied before save.

---

## 9) Field-Level Error Feedback Parity

### Gap

WPSettings can show inline field errors (`wps-error-feedback`). HyperFields currently only has global settings errors in lifecycle validation.

### Implementation

1. Add compatibility-scoped validation error collector keyed by field name.
2. On failed validation:
   - keep old value for that field
   - attach field-level message
3. Render inline messages in field templates when compatibility mode active.
4. Keep global summary notice for accessibility and visibility.

### Acceptance Criteria

- Users see per-field feedback near failed fields.
- Successful fields still persist in same save operation.

---

## 10) Option-Level Nested Path Parity (`option_level`)

### Gap

WPSettings supports tab/section option-level nesting. HyperFields compatibility still saves flat keys by default.

### Implementation

1. Add option-level flags to compatibility proxies:
   - tab-level and section-level
2. Compute option key path using WPSettings-equivalent semantics:
   - `{tab_slug}.{section_slug}.{name}` (depending on enabled levels)
3. Add path getter/setter utilities for load/save:
   - hydrate field value from nested path
   - persist sanitized value to nested path
4. Preserve current behavior when option-level flags are not enabled.

### Acceptance Criteria

- Nested structures written by HyperFields match WPSettings output exactly.

---

## Implementation Order

Order by migration risk reduction:

1. `media`/`video` type mapping
2. text `input_type` + generic attributes support
3. textarea rows/cols support
4. always-on `render` callback behavior
5. WP editor settings passthrough
6. per-option sanitize/validate mapping
7. field-level error rendering
8. `wp_settings_option_type_map` bridge
9. `option_level` nested path parity

---

## Test Plan

## Unit Tests (Compatibility)

Add/extend tests for:

- all WPSettings field types:
  - text, textarea, checkbox, choices, select, select-multiple, wp-editor, code-editor, color, media, image, video
- arg mapping:
  - `type` override for text inputs
  - `rows`, `cols`
  - `attributes`, `custom_attributes`
  - `css.input_class`, `css.label_class`
  - editor args passthrough
  - `visible`, `render`, `sanitize`, `validate`
- option-level nesting:
  - read/write nested option paths
- validation behavior:
  - field rejected, old value retained, inline feedback present

## Integration Tests

1. Register a compatibility page with mixed field types and nested settings.
2. Simulate save request for active tab/section.
3. Assert output option array equals WPSettings baseline fixture.

## Regression Tests

- Existing HyperFields options pages (non-compatibility flow) unchanged.
- Existing HyperFields API tests stay green.

---

## Documentation Updates Required

When implementation is complete, update:

1. `docs/hyperfields.md`
   - Add final supported WPSettings-compat field matrix and arg map.
2. `docs/hyperfields-examples.md`
   - Add example compatibility pages using media/video/editor/validation.
3. `CHANGELOG.md`
   - Add explicit parity features under current release entry.

---

## Rollout Strategy

1. Implement parity behind compatibility-path logic only.
2. Validate on one downstream plugin with highest field-type variety.
3. Migrate remaining plugins incrementally.
4. Remove temporary migration workarounds in downstream plugins after parity is proven.

---

## Done Definition

Feature parity is complete when:

1. Every WPSettings field type used by downstream plugins works in HyperFields compatibility mode.
2. Option args used by those fields produce equivalent render/save/validation behavior.
3. Data structure written to options table matches expected baseline.
4. Full HyperFields test suite passes.
5. Documentation and changelog reflect final parity support.

# Options Page Compact Input

Reduce POST size and complexity by compacting all option fields into a single JSON payload during form submission.

Source: `assets/js/admin-options.js`

## What It Does

- Collects all inputs belonging to the active options group (based on `window.hyperpressOptions.optionName`).
- Builds a single JSON object with the option values.
- Writes that JSON into a hidden input (default name: `hyperpress_compact_input`).
- Removes original `name` attributes (keeping values intact) to drastically reduce POST vars.
- Skips hidden array sentinels used by set fields.
- Handles text inputs, textareas, radios, checkboxes (single and sets), selects (single and multiple), and array fields like `name[]`.

## Activation & Keys

- Runs only when `window.hyperpressOptions.compactInput === true`.
- Toggled server-side via constants in PHP:
  - `HYPERPRESS_COMPACT_INPUT` (default: `false`)
  - `HYPERPRESS_COMPACT_INPUT_KEY` (default: `'hyperpress_compact_input'`)
- Hidden input key used by the client is `window.hyperpressOptions.compactInputKey || 'hyperpress_compact_input'` (injected from PHP).
- Scope: forms inside `.hyperpress-options-wrap`.

## Behavior Details

- Option name: read from `window.hyperpressOptions.optionName` (e.g., `hyperpress_options`).
- Hidden field key: `window.hyperpressOptions.compactInputKey || 'hyperpress_compact_input'`.
- Keep original name: add `data-hyperpress-keep-name` to an input to exclude it from compaction.
- Array handling: `[]` fields are aggregated; checkbox sets collect only checked values; multi-select gathers selected options.
- Active tab only: values are collected from the current section wrapper `.hyperpress-fields-group` (the active tab). Other tabs are not included in the compact payload.
- Current tab tracking: a hidden input `hyperpress_active_tab` is posted so the server knows which tab to process.

## Example

```html
<form class="hyperpress-options-wrap" method="post">
  <div class="hyperpress-fields-group">
    <input name="hyperpress_options[site_tagline]" value="Hello" />
    <input type="checkbox" name="hyperpress_options[enable_feature]" value="1" />
    <select name="hyperpress_options[favorite]">
      <option value="a">A</option>
      <option value="b">B</option>
    </select>
  </div>
  <!-- Hidden field is auto-inserted/updated before submit -->
  <button type="submit">Save</button>
</form>
```

On submit, the hidden input will contain:

```json
{ "hyperpress_options": { "site_tagline": "Hello", "enable_feature": "1", "favorite": "a" } }
```

## Server Notes

- Decoding: When `HYPERPRESS_COMPACT_INPUT === true`, the save routine reconstructs `$input` from the single POST var at key `HYPERPRESS_COMPACT_INPUT_KEY` (default `'hyperpress_compact_input'`). The payload is a wrapper object keyed by the option name, e.g. `{ "hyperpress_options": { ... } }`.
- Settings API interop: A hidden dummy field `${option_name}[_compact]` is also posted to ensure the Settings API processes the option. It is marked with `data-hyperpress-keep-name` so the client does not strip it.
- Active tab processing: Only fields from the current tab are sanitized and saved; values for other tabs are preserved from existing options.
- Checkbox semantics: If a checkbox is absent in the submitted data (unchecked), it is saved as `'0'`.
- Security: Standard WordPress nonces and capability checks still apply.

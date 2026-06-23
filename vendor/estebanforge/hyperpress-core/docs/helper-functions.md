# Helper Functions

Core helpers for endpoints, responses, validation, library mode, Datastar SSE integration, and field CRUD.

Source: `src/plugins/HyperPress/includes/helpers.php`

## Endpoints

```php
hp_get_endpoint_url(string $template_path = ''): string
hp_endpoint_url(string $template_path = ''): void
```

- Always prefer these to hardcoding `/wp-html/v1/...`.
- Use inside templates and theme code.

## Responses and Errors

```php
hp_send_header_response(array $data = [], string $action = null): void
hp_die(string $message = '', bool $display_error = false): void
```

- Send structured HX-Trigger responses for noswap actions.
- `hp_die()` returns 200 to let hypermedia libraries handle errors gracefully.

## Security

```php
hp_validate_request(array $hmvals = null, string $action = null): bool
```

- Validates nonce from header or request and optional action.
- Supports both new (`hyperpress_nonce`) and legacy (`hxwp_nonce`) nonces.

## Library Mode

```php
hp_is_library_mode(): bool
```

- Detects if HyperPress is loaded as a Composer library vs active plugin.
- In library mode the `Settings → HyperPress` page is hidden by default. Re-enable it via the `hyperpress/admin/show_menu` filter — see [Developer Configuration → Re-enable the Admin Settings Page in Library Mode](./developer-configuration.md#re-enable-the-admin-settings-page-in-library-mode).

## Options

```php
hp_get_options(): array
hp_get_option(string $key, mixed $default = null): mixed
```

- Read the merged HyperPress option array, or a single key with a fallback. Both go through the `hyperpress/options` filter — see [Developer Configuration → Reading Options in Code](./developer-configuration.md#reading-options-in-code).

## Datastar (SSE / HTML) Integration

```php
hp_ds_sse(): ?ServerSentEventGenerator
hp_ds_read_signals(): array
hp_ds_patch_elements(string $html, array $options = []): void
hp_ds_remove_elements(string $selector, array $options = []): void
hp_ds_patch_signals(mixed $signals, array $options = []): void
hp_ds_execute_script(string $script, array $options = []): void
hp_ds_location(string $url): void
hp_ds_send_html(string $html): void
```

- `hp_ds_patch_*` helpers stream updates via SSE (`text/event-stream`).
- `hp_ds_send_html()` returns raw HTML (`text/html`) for Datastar `@get`/`@post` to morph by element ID, or for HTMX/Alpine AJAX. No SSE connection.

### Rate Limiting

```php
hp_is_rate_limited(array $options = []): bool
```

- Generic, side-effect-free rate limiter for **any** endpoint (HTML, HTMX, Alpine AJAX, Datastar `@get`/`@post`).
- Returns `true` when blocked. Does **not** send headers or SSE responses.
- Options: `requests_per_window`, `time_window_seconds`, `identifier`.

```php
hp_ds_is_rate_limited(array $options = []): bool
```

- Rate limiter specifically for **Datastar SSE endpoints**.
- Returns `true` when blocked. Automatically sends SSE error feedback (patched element + signals + console warning) when `send_sse_response` is `true`.
- Options include `requests_per_window`, `time_window_seconds`, `identifier`, `send_sse_response`, `error_message`, `error_selector`.

## Field CRUD

```php
hp_get_field(string $name, $source = null, array $args = [])
hp_update_field(string $name, $value, $source = null, array $args = []): bool
hp_delete_field(string $name, $source = null, array $args = []): bool
```

- Works with post/user/term meta and options, resolved by `hp_resolve_field_context()`.
- Pass `['type' => '...']` to `hp_update_field()` to enable sanitization via `Field::sanitizeValue()`.

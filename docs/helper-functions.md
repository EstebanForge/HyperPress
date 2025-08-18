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

## Datastar (SSE) Integration

```php
hp_ds_sse(): ?ServerSentEventGenerator
hp_ds_read_signals(): array
hp_ds_patch_elements(string $html, array $options = []): void
hp_ds_remove_elements(string $selector, array $options = []): void
hp_ds_patch_signals(mixed $signals, array $options = []): void
hp_ds_execute_script(string $script, array $options = []): void
hp_ds_location(string $url): void
```

- Real-time UI updates via SSE with patch/remove/signals/script/location helpers.

### Rate Limiting (SSE)

```php
hp_ds_is_rate_limited(array $options = []): bool
```

- Returns true when the request is blocked by the rate limiter.
- Sends SSE feedback (error element + updated signals) when enabled.
- Options include `requests_per_window`, `time_window_seconds`, `identifier`, `send_sse_response`, `error_message`, `error_selector`.

## Field CRUD

```php
hp_get_field(string $name, $source = null, array $args = [])
hp_update_field(string $name, $value, $source = null, array $args = []): bool
hp_delete_field(string $name, $source = null, array $args = []): bool
```

- Works with post/user/term meta and options, resolved by `hp_resolve_field_context()`.
- Pass `['type' => '...']` to `hp_update_field()` to enable sanitization via `Field::sanitize_value()`.

## Backward Compatibility

The following legacy helpers remain for backward compatibility. Avoid using them in new code:

- `hxwp_api_url()` → Use `hp_get_endpoint_url()` instead
- `hxwp_send_header_response()` → Use `hp_send_header_response()` instead
- `hxwp_die()` → Use `hp_die()` instead
- `hxwp_validate_request()` → Use `hp_validate_request()` instead

Notes:
- All global helpers use the `hp_` prefix going forward.
- Datastar rate limiting helper is `hp_ds_is_rate_limited()`; no legacy alias exists.

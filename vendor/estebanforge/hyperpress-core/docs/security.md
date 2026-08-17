# Security & Validation

## Hypermedia Security Notes

Every call to the `wp-html` endpoint, using this plugin included helpers, will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all Hypermedia requests automatically.

If you are new to Hypermedia, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Remember that Hypermedia requires you to validate and sanitize any data you receive from the user. This is something developers used to do all the time, but it seems to have been forgotten by newer generations of software developers.

If you are not familiar with how WordPress recommends handling data sanitization and escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) on [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

## Nonce Validation and Request Validation

Use `hp_validate_request(array $hmvals = null, string $action = null): bool` to validate Hypermedia requests across HTMX, Alpine Ajax, and Datastar forms.

- Supports both new (`hyperpress_nonce`) and legacy (`hxwp_nonce`) nonce formats.
- For SSE (Datastar) endpoints, validation differs because the connection model is not a standard form POST. Combine nonce checks with capability checks and rate limiting as appropriate.

## Rate Limiting

Use `hp_is_rate_limited()` for generic rate limiting on any HyperPress endpoint (HTML, HTMX, Alpine AJAX, or Datastar `@get`/`@post`). It has no side effects and will not alter response headers.

Use `hp_ds_is_rate_limited()` **only** for Datastar SSE endpoints. It internally initializes the SSE generator, which sends `text/event-stream` headers. Using it in a regular HTML endpoint will switch the response content type away from `text/html`.

```php
// Basic nonce validation (works for all hypermedia libraries)
if (!hp_validate_request()) {
    hp_die('Security check failed');
}

// Validate specific action
if (!hp_validate_request($_REQUEST, 'delete_post')) {
    hp_die('Invalid action');
}

// Validate custom data array
$custom_data = ['action' => 'save_settings', '_wpnonce' => $_POST['_wpnonce']];
if (!hp_validate_request($custom_data, 'save_settings')) {
    hp_die('Validation failed');
}

// Datastar SSE endpoint with real-time validation
// hypermedia/validate-form.hp.php
$signals = hp_ds_read_signals();
$email = $signals['email'] ?? '';
$password = $signals['password'] ?? '';

// Validate email in real-time
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    hp_ds_patch_elements('<div class="error">Valid email required</div>', ['selector' => '#email-error']);
    hp_ds_patch_signals(['email_valid' => false]);
} else {
    hp_ds_remove_elements('#email-error');
    hp_ds_patch_signals(['email_valid' => true]);
}

// Validate password strength
if (strlen($password) < 8) {
    hp_ds_patch_elements('<div class="error">Password must be 8+ characters</div>', ['selector' => '#password-error']);
    hp_ds_patch_signals(['password_valid' => false]);
} else {
    hp_ds_remove_elements('#password-error');
    hp_ds_patch_signals(['password_valid' => true]);
}
```

## REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-html`, to avoid security issues like directory traversal attacks. It will also limit access so you can't use it to access any file outside the `hypermedia` folder within your own theme.

The parameters and their values passed to the endpoint via GET or POST will be sanitized with `sanitize_key()` and `sanitize_text_field()`, respectively.

Filters `hyperpress/sanitize_param_key` and `hyperpress/sanitize_param_value` are available to modify the sanitization process if needed. For backward compatibility, the old filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are still supported but deprecated.

Do your due diligence and ensure you are not returning unsanitized data back to the user or using it in a way that could pose a security issue for your site. Hypermedia requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Strict Template Mode

By design, the `wp-html` endpoint renders any template shipped in the registered template directories — no registration step, for easy adoption. Template-level authorization stays your responsibility (`hp_validate_request()`, capability checks inside the template).

For stricter deployments, HyperPress offers opt-in **strict mode**: templates must be explicitly registered before the endpoint will load them. Unregistered requests get a "Template Not Registered" page instead of rendered content. Strict mode is OFF by default; existing sites are unaffected.

```php
// Enable strict mode (e.g. in your theme's functions.php or your plugin)
add_filter('hyperpress/render/strict_mode', '__return_true');

// Allowlist non-namespaced, theme-relative templates (exact names as requested)
add_filter('hyperpress/render/registered_templates', function (array $templates): array {
    $templates[] = 'demo/swap';        // loads hypermedia/demo/swap.hp.php
    $templates[] = 'noswap/header-update';
    return $templates;
});
```

Namespaced templates (`namespace:template`) need no extra registration: a namespace registered via `hyperpress/render/register_template_path` is already an explicit opt-in, so all templates under it remain loadable in strict mode. Note what that means: **strict mode gates namespaces, not individual files inside them** — every file under a registered namespace's base directory stays reachable, exactly as in default mode. If you need per-file control inside a namespace, do not register that namespace and list the templates theme-relative instead.

Rules:

- Allowlist entries match exactly, not by prefix. Listing `demo/swap` does not unlock `demo/other`.
- Matching is case-sensitive on the filename segment. Directory segments are normalized to lowercase by the endpoint's path sanitization, but the filename keeps its case — enter the filename exactly as the file is named (`Demo/swap.hp.php` → allowlist entry `Demo/swap`). A case mismatch refuses the template (no bypass), but it is the most likely reason an entry silently never fires.
- Enabling strict mode without registering anything refuses everything — safe default direction. Register first, then enable.
- Strict mode composes with, and does not replace, nonce and capability checks. It controls which templates can load, not what they may do.
- The refusal response is customizable via the `hyperpress/render/invalid_route_output` filter (error type `template-not-registered`).

## Reporting a Vulnerability

Please, contact me at any of the following email addresses:

esteban at attitude dot cl

Thanks!

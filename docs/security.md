# Security & Validation

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.0.1  | :white_check_mark: |
| <2.0.1 | :x:                |
 
 

## Hypermedia Security Notes

Every call to the `wp-html` endpoint, using this plugin included helpers, will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all Hypermedia requests automatically.

If you are new to Hypermedia, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Remember that Hypermedia requires you to validate and sanitize any data you receive from the user. This is something developers used to do all the time, but it seems to have been forgotten by newer generations of software developers.

If you are not familiar with how WordPress recommends handling data sanitization and escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) on [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

## Nonce Validation and Request Validation

Use `hp_validate_request(array $hmvals = null, string $action = null): bool` to validate Hypermedia requests across HTMX, Alpine Ajax, and Datastar forms.

- Supports both new (`hyperpress_nonce`) and legacy (`hxwp_nonce`) nonce formats.
- For SSE (Datastar) endpoints, validation differs because the connection model is not a standard form POST. Combine nonce checks with capability checks and rate limiting as appropriate.

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
// hypermedia/validate-form.hm.php
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

## Reporting a Vulnerability

Please, contact me at any of the following email addresses:

esteban at attitude dot cl

Thanks!

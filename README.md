# Hypermedia API for WordPress

An unofficial WordPress plugin that enables the use of [HTMX](https://htmx.org), [Alpine AJAX](https://alpine-ajax.js.org/), [Datastar](https://data-star.dev/) and other hypermedia libraries on your WordPress site, theme, and/or plugins. Intended for software developers.

Adds a new endpoint `/wp-html/v1/` from which you can load any hypermedia template.

<div align="center">

[![Hypermedia API for WordPress Demo](https://img.youtube.com/vi/6mrRA5QIcRw/0.jpg)](https://www.youtube.com/watch?v=6mrRA5QIcRw "Hypermedia API for WordPress Demo")

<small>

[Check the video](https://www.youtube.com/watch?v=6mrRA5QIcRw)

</small>

</div>

## Hypermedia what?

[Hypermedia](https://hypermedia.systems/) is a "new" concept that allows you to build modern web applications, even SPAs, without the need to write a single line of JavaScript. A forgotten concept that was popular in the 90s and early 2000s, but has been forgotten by newer generations of software developers.

HTMX, Alpine Ajax and Datastar are JavaScript libraries that allows you to access AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes, without writing any JavaScript.

Unless you're trying to build a Google Docs clone or a competitor, Hypermedia allows you to build modern web applications, even SPAs, without the need to write a single line of JavaScript.

For a better explanation and demos, check the following video:

<div align="center">

[![You don't need a frontend framework by Andrew Schmelyun](https://img.youtube.com/vi/Fuz-jLIo2g8/0.jpg)](https://www.youtube.com/watch?v=Fuz-jLIo2g8)

</div>

## Why mix it with WordPress?

Because I share the same sentiment as Carson Gross, the creator of HTMX, that the software stack used to build the web today has become too complex without good reason (most of the time). And, just like him, I also want to see the world burn.

(Seriously) Because Hypermedia is awesome, and WordPress is awesome (sometimes). So, why not?

I'm using this in production for a few projects, and it's working great, stable, and ready to use. So, I decided to share it with the world.

I took this idea out of the tangled mess it was inside a project and made it into a standalone plugin that should work for everyone.

It might have some bugs, but the idea is to open it up and improve it over time.

So, if you find any bugs, please report them.

## Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: Hypermedia API

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > Hypermedia API.

### Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use hypermedia libraries in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/hypermedia-api-wordpress
```

This plugin/library will determine which instance of itself is the newer one when WordPress is loading. Then, it will use the newer instance between all competing plugins or themes. This is to avoid conflicts with other plugins or themes that may be using the same library for their Hypermedia implementation.

## How to use

After installation, you can use hypermedia templates in any theme.

This plugin will include the active hypermedia library by default, locally from the plugin folder. Libraries like HTMX, Alpine.js, Hyperscript, and Datastar are supported.

The plugin has an opt-in option, not enforced, to include these third-party libraries from a CDN (using the unpkg.com service). You must explicitly enable this option for privacy and security reasons.

Create a `hypermedia` folder in your theme's root directory. This plugin includes a demo folder that you can copy to your theme. Don't put your templates inside the demo folder located in the plugin's directory, because it will be deleted when you update the plugin.

Inside your `hypermedia` folder, create as many templates as you want. All files must end with `.hm.php`.

For example:

```
hypermedia/live-search.hm.php
hypermedia/related-posts.hm.php
hypermedia/private/author.hm.php
hypermedia/private/author-posts.hm.php
```

Check the demo template at `hypermedia/demo.hm.php` to see how to use it.

Then, in your theme, use your Hypermedia library to GET/POST to the `/wp-html/v1/` endpoint corresponding to the template you want to load, without the file extension:

```
/wp-html/v1/live-search
/wp-html/v1/related-posts
/wp-html/v1/private/author
/wp-html/v1/private/author-posts
```

### Helper Functions

The plugin provides a comprehensive set of helper functions for developers to interact with hypermedia templates and manage responses. All functions are designed to work with HTMX, Alpine Ajax, and Datastar.

#### URL Generation Functions

**`hm_get_endpoint_url(string $template_path = ''): string`**

Generates the full URL for your hypermedia templates. Automatically adds the `/wp-html/v1/` prefix and applies proper URL formatting.

```php
// Basic usage
echo hm_get_endpoint_url('live-search');
// Output: http://your-site.com/wp-html/v1/live-search

// With subdirectories
echo hm_get_endpoint_url('admin/user-list');
// Output: http://your-site.com/wp-html/v1/admin/user-list

// With namespaced templates (plugin/theme specific)
echo hm_get_endpoint_url('my-plugin:dashboard/stats');
// Output: http://your-site.com/wp-html/v1/my-plugin:dashboard/stats
```

**`hm_endpoint_url(string $template_path = ''): void`**

Same as `hm_get_endpoint_url()` but echoes the result directly. Useful for template output.

```php
// HTMX usage
<div hx-get="<?php hm_endpoint_url('search-results'); ?>">
    Loading...
</div>

// Datastar usage
<div data-on-click="@get('<?php hm_endpoint_url('user-profile'); ?>')">
    Load Profile
</div>

// Alpine Ajax usage
<div @click="$ajax('<?php hm_endpoint_url('dashboard-stats'); ?>')">
    Refresh Stats
</div>
```

#### Response Management Functions

**`hm_send_header_response(array $data = [], string $action = null): void`**

Sends hypermedia-compatible header responses for non-visual actions. Automatically validates nonces and terminates execution. Perfect for "noswap" templates that perform backend actions without returning HTML.

```php
// Success response (works with HTMX/Alpine Ajax)
hm_send_header_response([
    'status' => 'success',
    'message' => 'User saved successfully',
    'user_id' => 123
], 'save_user');

// Error response
hm_send_header_response([
    'status' => 'error',
    'message' => 'Invalid email address'
], 'save_user');

// Silent success (no user notification)
hm_send_header_response([
    'status' => 'silent-success',
    'data' => ['updated_count' => 5]
]);

// For Datastar SSE endpoints, use the ds helpers instead:
// hypermedia/save-user-sse.hm.php

// Get user data from Datastar signals
$signals = hm_ds_read_signals();
$user_data = $signals; // Signals contain the form data
$result = save_user($user_data);

if ($result['success']) {
    // Update UI with success state
    hm_ds_patch_elements('<div class="success">User saved!</div>', ['selector' => '#message']);
    hm_ds_patch_signals(['user_saved' => true, 'user_id' => $result['user_id']]);
} else {
    // Show error message
    hm_ds_patch_elements('<div class="error">Save failed: ' . $result['error'] . '</div>', ['selector' => '#message']);
    hm_ds_patch_signals(['user_saved' => false, 'error' => $result['error']]);
}
```

**`hm_die(string $message = '', bool $display_error = false): void`**

Terminates template execution with a 200 status code (allowing hypermedia libraries to process the response) and sends error information via headers.

```php
// Die with hidden error message
hm_die('Database connection failed');

// Die with visible error message
hm_die('Please fill in all required fields', true);
```

#### Security & Validation Functions

**`hm_validate_request(array $hmvals = null, string $action = null): bool`**

Validates hypermedia requests by checking nonces and optionally validating specific actions. Supports both new (`hmapi_nonce`) and legacy (`hxwp_nonce`) nonce formats.

**Note**: This function is designed for traditional HTMX/Alpine Ajax requests. For Datastar SSE endpoints, nonce validation works differently since SSE connections don't follow the same request pattern. Consider alternative security measures for SSE endpoints (user capability checks, rate limiting, etc.).

```php
// Basic nonce validation (works for all hypermedia libraries)
if (!hm_validate_request()) {
    hm_die('Security check failed');
}

// Validate specific action
if (!hm_validate_request($_REQUEST, 'delete_post')) {
    hm_die('Invalid action');
}

// Validate custom data array
$custom_data = ['action' => 'save_settings', '_wpnonce' => $_POST['_wpnonce']];
if (!hm_validate_request($custom_data, 'save_settings')) {
    hm_die('Validation failed');
}

// Datastar SSE endpoint with real-time validation
// hypermedia/validate-form.hm.php
$signals = hm_ds_read_signals();
$email = $signals['email'] ?? '';
$password = $signals['password'] ?? '';

// Validate email in real-time
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    hm_ds_patch_elements('<div class="error">Valid email required</div>', ['selector' => '#email-error']);
    hm_ds_patch_signals(['email_valid' => false]);
} else {
    hm_ds_remove_elements('#email-error');
    hm_ds_patch_signals(['email_valid' => true]);
}

// Validate password strength
if (strlen($password) < 8) {
    hm_ds_patch_elements('<div class="error">Password must be 8+ characters</div>', ['selector' => '#password-error']);
    hm_ds_patch_signals(['password_valid' => false]);
} else {
    hm_ds_remove_elements('#password-error');
    hm_ds_patch_signals(['password_valid' => true]);
}
```

#### Library Detection Functions

**`hm_is_library_mode(): bool`**

Detects whether the plugin is running as a WordPress plugin or as a Composer library. Useful for conditional functionality.

```php
if (hm_is_library_mode()) {
    // Running as composer library - no admin interface
    // Configure via filters only
    add_filter('hmapi/default_options', function($defaults) {
        $defaults['active_library'] = 'htmx';
        return $defaults;
    });
} else {
    // Running as WordPress plugin - full functionality available
    add_action('admin_menu', 'my_admin_menu');
}

// Datastar-specific library mode configuration
if (hm_is_library_mode()) {
    // Configure Datastar for production use as library
    add_filter('hmapi/default_options', function($defaults) {
        $defaults['active_library'] = 'datastar';
        $defaults['load_from_cdn'] = false; // Use local files for reliability
        $defaults['load_datastar_backend'] = true; // Enable in wp-admin
        return $defaults;
    });

    // Register custom SSE endpoints for the plugin using this library
    add_filter('hmapi/register_template_path', function($paths) {
        $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'datastar-templates/';
        return $paths;
    });
} else {
    // Plugin mode - users can configure via admin interface
    // Add custom Datastar functionality only when running as main plugin
    add_action('wp_enqueue_scripts', function() {
        if (get_option('hmapi_active_library') === 'datastar') {
            wp_add_inline_script('datastar', 'console.log("Datastar ready for SSE!");');
        }
    });
}
```

#### Datastar Helper Functions

These functions provide direct integration with Datastar's Server-Sent Events (SSE) capabilities for real-time updates.

**`hm_ds_sse(): ?ServerSentEventGenerator`**

Gets or creates the ServerSentEventGenerator instance. Returns `null` if Datastar SDK is not available.

```php
$sse = hm_ds_sse();
if ($sse) {
    // SSE is available, proceed with real-time updates
    $sse->patchElements('<div id="status">Connected</div>');
}
```

**`hm_ds_read_signals(): array`**

Reads signals sent from the Datastar client. Returns an empty array if Datastar SDK is not available.

```php
// Read client signals
$signals = hm_ds_read_signals();
$user_input = $signals['search_query'] ?? '';
$page_number = $signals['page'] ?? 1;

// Use signals for processing
if (!empty($user_input)) {
    $results = search_posts($user_input, $page_number);
    hm_ds_patch_elements($results_html, ['selector' => '#results']);
}
```

**`hm_ds_patch_elements(string $html, array $options = []): void`**

Patches HTML elements into the DOM via SSE. Supports various patching modes and view transitions.

```php
// Basic element patching
hm_ds_patch_elements('<div id="message">Hello World</div>');

// Advanced patching with options
hm_ds_patch_elements(
    '<div class="notification">Task completed</div>',
    [
        'selector' => '.notifications',
        'mode' => 'append',
        'useViewTransition' => true
    ]
);
```

**`hm_ds_remove_elements(string $selector, array $options = []): void`**

Removes elements from the DOM via SSE.

```php
// Remove specific element
hm_ds_remove_elements('#temp-message');

// Remove with view transition
hm_ds_remove_elements('.expired-items', ['useViewTransition' => true]);
```

**`hm_ds_patch_signals(mixed $signals, array $options = []): void`**

Updates Datastar signals on the client side. Accepts JSON string or array.

```php
// Update single signal
hm_ds_patch_signals(['user_count' => 42]);

// Update multiple signals
hm_ds_patch_signals([
    'loading' => false,
    'message' => 'Data loaded successfully',
    'timestamp' => time()
]);

// Only patch if signal doesn't exist
hm_ds_patch_signals(['default_theme' => 'dark'], ['onlyIfMissing' => true]);
```

**`hm_ds_execute_script(string $script, array $options = []): void`**

Executes JavaScript code on the client via SSE.

```php
// Simple script execution
hm_ds_execute_script('console.log("Server says hello!");');

// Complex client-side operations
hm_ds_execute_script('
    document.querySelector("#progress").style.width = "100%";
    setTimeout(() => {
        location.reload();
    }, 2000);
');
```

**`hm_ds_location(string $url): void`**

Redirects the browser to a new URL via SSE.

```php
// Redirect after processing
hm_ds_location('/dashboard');

// Redirect to external URL
hm_ds_location('https://example.com/success');
```

**`hm_ds_is_rate_limited(array $options = []): bool`**

Checks if current request is rate limited for Datastar SSE endpoints to prevent abuse and protect server resources. Uses WordPress transients for persistence across requests.

```php
// Basic rate limiting (10 requests per 60 seconds)
if (hm_ds_is_rate_limited()) {
    hm_die(__('Rate limit exceeded', 'api-for-htmx'));
}

// Custom rate limiting configuration
if (hm_ds_is_rate_limited([
    'requests_per_window' => 30,      // Allow 30 requests
    'time_window_seconds' => 120,     // Per 2 minutes
    'identifier' => 'search_' . get_current_user_id(), // Custom identifier
    'error_message' => __('Search rate limit exceeded. Please wait.', 'api-for-htmx'),
    'error_selector' => '#search-errors'
])) {
    // Rate limit exceeded - SSE error already sent to client
    return;
}

// Strict rate limiting without SSE feedback
if (hm_ds_is_rate_limited([
    'requests_per_window' => 10,
    'time_window_seconds' => 60,
    'send_sse_response' => false  // Don't send SSE feedback
])) {
    hm_die(__('Too many requests', 'api-for-htmx'));
}

// Different rate limits for different actions
$action = hm_ds_read_signals()['action'] ?? '';

switch ($action) {
    case 'search':
        $rate_config = ['requests_per_window' => 20, 'time_window_seconds' => 60];
        break;
    case 'upload':
        $rate_config = ['requests_per_window' => 5, 'time_window_seconds' => 300];
        break;
    default:
        $rate_config = ['requests_per_window' => 30, 'time_window_seconds' => 60];
}

if (hm_ds_is_rate_limited($rate_config)) {
    return; // Rate limited
}
```

**Rate Limiting Options:**
- `requests_per_window` (int): Maximum requests allowed per time window. Default: 10
- `time_window_seconds` (int): Time window in seconds. Default: 60
- `identifier` (string): Custom identifier for rate limiting. Default: IP + user ID
- `send_sse_response` (bool): Send SSE error response when rate limited. Default: true
- `error_message` (string): Custom error message. Default: translatable 'Rate limit exceeded...'
- `error_selector` (string): CSS selector for error display. Default: '#rate-limit-error'

#### Complete SSE Example

Here's a practical example combining multiple Datastar helpers:

```php
// hypermedia/process-upload.hm.php
<?php
// Apply strict rate limiting for uploads (5 uploads per 5 minutes)
if (hm_ds_is_rate_limited([
    'requests_per_window' => 5,
    'time_window_seconds' => 300,
    'identifier' => 'file_upload_' . get_current_user_id(),
    'error_message' => __('Upload rate limit exceeded. You can upload 5 files every 5 minutes.', 'api-for-htmx'),
    'error_selector' => '#upload-errors'
])) {
    return; // Rate limited - error sent via SSE
}

// Initialize SSE
$sse = hm_ds_sse();
if (!$sse) {
    hm_die('SSE not available');
}

// Show progress
hm_ds_patch_elements('<div id="status">Processing upload...</div>');
hm_ds_patch_signals(['progress' => 0]);

// Simulate file processing
for ($i = 1; $i <= 5; $i++) {
    sleep(1);
    hm_ds_patch_signals(['progress' => $i * 20]);
    hm_ds_patch_elements('<div id="status">Processing... ' . ($i * 20) . '%</div>');
}

// Complete
hm_ds_patch_elements('<div id="status" class="success">Upload complete!</div>');
hm_ds_patch_signals(['progress' => 100, 'completed' => true]);

// Redirect after 2 seconds
hm_ds_execute_script('setTimeout(() => { window.location.href = "/dashboard"; }, 2000);');
?>
```

#### Complete Datastar Integration Example

Here's a complete frontend-backend example showing how all helper functions work together in a real Datastar application:

**Frontend HTML:**
```html
<!-- Live search with real-time validation -->
<div data-signals-query="" data-signals-results="[]" data-signals-loading="false">
    <h3>User Search</h3>

    <!-- Search input with live validation -->
    <input
        type="text"
        data-bind-query
        data-on-input="@get('<?php hm_endpoint_url('search-users-validate'); ?>')"
        placeholder="Search users..."
    />

    <!-- Search button -->
    <button
        data-on-click="@get('<?php hm_endpoint_url('search-users'); ?>')"
        data-bind-disabled="loading"
    >
        <span data-show="!loading">Search</span>
        <span data-show="loading">Searching...</span>
    </button>

    <!-- Results container -->
    <div id="search-results" data-show="results.length > 0">
        <!-- Results will be populated via SSE -->
    </div>

    <!-- No results message -->
    <div data-show="results.length === 0 && !loading && query.length > 0">
        No users found
    </div>
</div>
```

**Backend Template - Real-time Validation (hypermedia/search-users-validate.hm.php):**
```php
<?php
// Apply rate limiting
if (hm_ds_is_rate_limited()) {
    return; // Rate limited
}

// Get search query from signals
$signals = hm_ds_read_signals();
$query = trim($signals['query'] ?? '');

// Validate query length
if (strlen($query) < 2 && strlen($query) > 0) {
    hm_ds_patch_elements(
        '<div class="validation-error">Please enter at least 2 characters</div>',
        ['selector' => '#search-validation']
    );
    hm_ds_patch_signals(['query_valid' => false]);
} elseif (strlen($query) >= 2) {
    hm_ds_remove_elements('#search-validation .validation-error');
    hm_ds_patch_signals(['query_valid' => true]);

    // Show search suggestion
    hm_ds_patch_elements(
        '<div class="search-hint">Press Enter or click Search to find users</div>',
        ['selector' => '#search-validation']
    );
}
?>
```

**Backend Template - Search Execution (hypermedia/search-users.hm.php):**
```php
<?php
// Apply rate limiting for search operations
if (hm_ds_is_rate_limited([
    'requests_per_window' => 20,
    'time_window_seconds' => 60,
    'identifier' => 'user_search_' . get_current_user_id(),
    'error_message' => __('Search rate limit exceeded. Please wait before searching again.', 'api-for-htmx'),
    'error_selector' => '#search-errors'
])) {
    // Rate limit exceeded - error already sent to client via SSE
    return;
}

// Get search parameters
$signals = hm_ds_read_signals();
$query = sanitize_text_field($signals['query'] ?? '');

// Set loading state
hm_ds_patch_signals(['loading' => true, 'results' => []]);
hm_ds_patch_elements('<div class="loading">Searching users...</div>', ['selector' => '#search-results']);

// Simulate search delay
usleep(500000); // 0.5 seconds

// Perform user search (example with WordPress users)
$users = get_users([
    'search' => '*' . $query . '*',
    'search_columns' => ['user_login', 'user_email', 'display_name'],
    'number' => 10
]);

// Build results HTML
$results_html = '<div class="user-results">';
$results_data = [];

foreach ($users as $user) {
    $results_data[] = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email
    ];

    $results_html .= sprintf(
        '<div class="user-item" data-user-id="%d">
            <strong>%s</strong> (%s)
            <button data-on-click="@get(\'%s\', {user_id: %d})">View Details</button>
        </div>',
        $user->ID,
        esc_html($user->display_name),
        esc_html($user->user_email),
        hm_get_endpoint_url('user-details'),
        $user->ID
    );
}

$results_html .= '</div>';

// Update UI with results
if (count($users) > 0) {
    hm_ds_patch_elements($results_html, ['selector' => '#search-results']);
    hm_ds_patch_signals([
        'loading' => false,
        'results' => $results_data,
        'result_count' => count($users)
    ]);

    // Show success notification
    hm_ds_execute_script("
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.textContent = 'Found " . count($users) . " users';
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    ");
} else {
    hm_ds_patch_elements('<div class="no-results">No users found for \"' . esc_html($query) . '\"</div>', ['selector' => '#search-results']);
    hm_ds_patch_signals(['loading' => false, 'results' => []]);
}
?>
```

This example demonstrates:
- **Frontend**: Datastar signals, reactive UI, and SSE endpoint integration
- **Backend**: Real-time feedback, progressive enhancement, and signal processing
- **Helper Usage**: `hm_ds_read_signals()`, `hm_get_endpoint_url()`, and all `hm_ds_*` functions
- **Security**: Input sanitization and validation, plus rate limiting for SSE endpoints
- **UX**: Loading states, real-time validation, and user feedback

#### Rate Limiting Integration Example

Here's a complete example showing how to integrate rate limiting with user feedback:

**Frontend HTML:**
```html
<!-- Rate limit aware interface -->
<div data-signals-rate_limited="false" data-signals-requests_remaining="30">
    <h3>Real-time Chat</h3>

    <!-- Rate limit status display -->
    <div id="rate-limit-status" data-show="rate_limited">
        <div class="warning">Rate limit reached. Please wait before sending more messages.</div>
    </div>

    <!-- Requests remaining indicator -->
    <div class="rate-info" data-show="!rate_limited && requests_remaining <= 10">
        <small>Requests remaining: <span data-text="requests_remaining"></span></small>
    </div>

    <!-- Chat input -->
    <input
        type="text"
        data-bind-message
        data-on-keyup.enter="@get('<?php hm_endpoint_url('send-message'); ?>')"
        data-bind-disabled="rate_limited"
        placeholder="Type your message..."
    />

    <!-- Send button -->
    <button
        data-on-click="@get('<?php hm_endpoint_url('send-message'); ?>')"
        data-bind-disabled="rate_limited"
    >
        Send Message
    </button>

    <!-- Error display area -->
    <div id="chat-errors"></div>

    <!-- Messages area -->
    <div id="chat-messages"></div>
</div>
```

**Backend Template (hypermedia/send-message.hm.php):**
```php
<?php
// Apply rate limiting for chat messages (10 messages per minute)
if (hm_ds_is_rate_limited([
    'requests_per_window' => 10,
    'time_window_seconds' => 60,
    'identifier' => 'chat_' . get_current_user_id(),
    'error_message' => __('Message rate limit exceeded. You can send 10 messages per minute.', 'api-for-htmx'),
    'error_selector' => '#chat-errors'
])) {
    // Rate limit exceeded - user is notified via SSE
    // The rate limiting helper automatically updates signals and shows error
    return;
}

// Get message from signals
$signals = hm_ds_read_signals();
$message = trim($signals['message'] ?? '');

// Validate message
if (empty($message)) {
    hm_ds_patch_elements(
        '<div class="error">' . esc_html__('Message cannot be empty', 'api-for-htmx') . '</div>',
        ['selector' => '#chat-errors']
    );
    return;
}

if (strlen($message) > 500) {
    hm_ds_patch_elements(
        '<div class="error">' . esc_html__('Message too long (max 500 characters)', 'api-for-htmx') . '</div>',
        ['selector' => '#chat-errors']
    );
    return;
}

// Clear any errors
hm_ds_remove_elements('#chat-errors .error');

// Save message (example)
$user = wp_get_current_user();
$chat_message = [
    'user' => $user->display_name,
    'message' => esc_html($message),
    'timestamp' => current_time('H:i:s')
];

// Add message to chat
$message_html = sprintf(
    '<div class="message">
        <strong>%s</strong> <small>%s</small><br>
        %s
    </div>',
    $chat_message['user'],
    $chat_message['timestamp'],
    $chat_message['message']
);

hm_ds_patch_elements($message_html, [
    'selector' => '#chat-messages',
    'mode' => 'append'
]);

// Clear input field
hm_ds_patch_signals(['message' => '']);

// Show success feedback
hm_ds_execute_script("
    // Scroll to bottom of chat
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Brief success indicator
    const input = document.querySelector('[data-bind-message]');
    input.style.borderColor = '#28a745';
    setTimeout(() => { input.style.borderColor = ''; }, 1000);
");

// The rate limiting helper automatically updates the requests_remaining signal
// So the frontend will show the updated count automatically
?>
```

This rate limiting example shows:
- **Intuitive Function Naming**: `hm_ds_is_rate_limited()` returns true when blocked
- **Proactive Rate Limiting**: Applied before processing the request
- **Automatic User Feedback**: Rate limit helper sends SSE responses with error messages
- **Dynamic UI Updates**: Frontend reacts to rate limit signals automatically
- **Resource Protection**: Prevents abuse of SSE endpoints
- **User Experience**: Clear feedback about rate limits and remaining requests

#### Backward Compatibility

For backward compatibility, the following deprecated functions are still available but should be avoided in new development:

- `hxwp_api_url()` → Use `hm_get_endpoint_url()` instead
- `hxwp_send_header_response()` → Use `hm_send_header_response()` instead
- `hxwp_die()` → Use `hm_die()` instead
- `hxwp_validate_request()` → Use `hm_validate_request()` instead

### How to pass data to the template

You can pass data to the template using URL parameters (GET/POST). For example:

```
/wp-html/v1/live-search?search=hello
/wp-html/v1/related-posts?category_id=5
```

All of those parameters (with their values) will be available inside the template as an array named: `$hmvals`.

### No Swap response templates

Hypermedia libraries allow you to use templates that don't return any HTML but perform some processing in the background on your server. These templates can still send a response back (using HTTP headers) if desired. Check [Swapping](https://htmx.org/docs/#swapping) for more info.

For this purpose, and for convenience, you can use the `noswap/` folder/endpoint. For example:

```
/wp-html/v1/noswap/save-user?user_id=5&name=John&last_name=Doe
/wp-html/v1/noswap/delete-user?user_id=5
```

In this examples, the `save-user` and `delete-user` templates will not return any HTML, but will do some processing in the background. They will be loaded from the `hypermedia/noswap` folder.

```
hypermedia/noswap/save-user.hm.php
hypermedia/noswap/delete-user.hm.php
```

You can pass data to these templates in the exact same way as you do with regular templates.

Nothing stops you from using regular templates to do the same thing or using another folder altogether. You can mix and match or organize your templates in any way you want. This is mentioned here just as a convenience feature for those who want to use it.

### Choosing a Hypermedia Library

This plugin comes with [HTMX](https://htmx.org), [Alpine Ajax](https://alpine-ajax.js.org/) and [Datastar](https://data-star.dev/) already integrated and enabled.

You can choose which library to use in the plugin's options page: Settings > Hypermedia API.

In the case of HTMX, you can also enable any of its extensions in the plugin's options page: Settings > Hypermedia API.

#### Local vs CDN Loading

The plugin includes local copies of all libraries for privacy and offline development. You can choose to load from:

1. **Local files** (default): Libraries are served from your WordPress installation
2. **CDN**: Optional CDN loading from jsdelivr.net. Will always load the latest version of the library.

### Datastar Usage

Datastar can be used to implement Server-Sent Events (SSE) to push real-time updates from the server to the client. Here is an example of how to implement a simple SSE endpoint within a hypermedia template:

```php
// In your hypermedia template file, e.g., hypermedia/my-sse-endpoint.hm.php

// Apply rate limiting for SSE endpoint
if (hm_ds_is_rate_limited()) {
    return; // Rate limited
}

// Initialize SSE (headers are sent automatically)
$sse = hm_ds_sse();
if (!$sse) {
    hm_die('SSE not available');
}

// Read client signals
$signals = hm_ds_read_signals();
$delay = $signals['delay'] ?? 0;
$message = 'Hello, world!';

// Stream message character by character
for ($i = 0; $i < strlen($message); $i++) {
    hm_ds_patch_elements('<div id="message">' . substr($message, 0, $i + 1) . '</div>');

    // Sleep for the provided delay in milliseconds
    usleep($delay * 1000);
}

// Script will automatically exit and send the SSE stream
```

On the frontend, you can create an HTML structure to consume this SSE endpoint. The following is a minimal example adapted from the official Datastar SDK companion:

```html
<!-- Container for the Datastar component -->
<div data-signals-delay="400">
    <h1>Datastar SDK Demo</h1>
    <p>SSE events will be streamed from the backend to the frontend.</p>

    <div>
        <label for="delay">Delay in milliseconds</label>
        <input data-bind-delay id="delay" type="number" step="100" min="0" />
    </div>

    <button data-on-click="@get('<?php echo hm_get_endpoint_url('my-sse-endpoint'); ?>')">
        Start
    </button>
</div>

<!-- Target element for SSE updates -->
<div id="message">Hello, world!</div>
```

This example demonstrates how to:
- Set initial signal values with `data-signals-delay`.
- Bind signals to form inputs with `data-bind-delay`.
- Trigger the SSE stream with a button click using `data-on-click`.

The server will receive the `delay` signal and use it to control the stream speed, while the `#message` div is updated in real-time.

#### Managing Frontend Libraries

For developers, the plugin includes npm scripts to download the latest versions of all libraries locally:

```bash
# Update all libraries
npm run update-all

# Update specific library
npm run update-htmx
npm run update-alpinejs
npm run update-hyperscript
npm run update-datastar
npm run update-all
```

This ensures your local development environment stays in sync with the latest library versions.

## Using Hypermedia Libraries in your plugin

You can definitely use hypermedia libraries and this Hypermedia API for WordPress in your plugin. You are not limited to using it only in your theme.

The plugin provides the filter: `hmapi/register_template_path`

This filter allows you to register a new template path for your plugin or theme. It expects an associative array where keys are your chosen namespaces and values are the absolute paths to your template directories.

For example, if your plugin slug is `my-plugin`, you can register a new template path like this:

```php
add_filter( 'hmapi/register_template_path', function( $paths ) {
    // Ensure YOUR_PLUGIN_PATH is correctly defined, e.g., plugin_dir_path( __FILE__ )
    // 'my-plugin' is the namespace.
    $paths['my-plugin'] = YOUR_PLUGIN_PATH . 'hypermedia/';

    return $paths;
});
```

Assuming `YOUR_PLUGIN_PATH` is already defined and points to your plugin's root directory, the above code registers the `my-plugin` namespace to point to `YOUR_PLUGIN_PATH/hypermedia/`.

Then, you can use the new template path in your plugin like this, using a colon `:` to separate the namespace from the template file path (which can include subdirectories):

```php
// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/template-name.hm.php
echo hm_get_endpoint_url( 'my-plugin:template-name' );

// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/parts/header.hm.php
echo hm_get_endpoint_url( 'my-plugin:parts/header' );
```

This will output the URL for the template from the path associated with the `my-plugin` namespace. If the namespace is not registered, or the template file does not exist within that registered path (or is not allowed due to sanitization rules), the request will result in a 404 error. Templates requested with an explicit namespace do not fall back to the theme's default `hypermedia` directory.

For templates located directly in your active theme's `hypermedia` directory (or its subdirectories), you would call them without a namespace:

```php
// Loads: wp-content/themes/your-theme/hypermedia/live-search.hm.php
echo hm_get_endpoint_url( 'live-search' );

// Loads: wp-content/themes/your-theme/hypermedia/subfolder/my-listing.hm.php
echo hm_get_endpoint_url( 'subfolder/my-listing' );
```

## Using as a Composer Library (Programmatic Configuration)

If you require this project as a Composer dependency, it will automatically be loaded. The `bootstrap.php` file is registered in `composer.json` and ensures that the plugin's bootstrapping logic is safely included only once, even if multiple plugins or themes require it. You do not need to manually `require` or `include` any file.

### Detecting Library Mode

The plugin exposes a helper function `hm_is_library_mode()` to detect if it is running as a library (not as an active plugin). This is determined automatically based on whether the plugin is in the active plugins list and whether it is running in the admin area.

When in library mode, the plugin will not register its admin options/settings page in wp-admin.

### Programmatic Configuration via Filters

You can configure the plugin programmatically using WordPress filters instead of using the admin interface. This is particularly useful when the plugin is used as a library or when you want to force specific configurations.

All plugin settings can be controlled using the `hmapi/default_options` filter. This filter allows you to override any default option value:

```php
add_filter('hmapi/default_options', function($defaults) {
    // General Settings
    $defaults['active_library'] = 'htmx'; // 'htmx', 'alpinejs', or 'datastar'
    $defaults['load_from_cdn'] = false;  // `true` to use CDN, `false` for local files

    // HTMX Core Settings
    $defaults['load_hyperscript'] = true;
    $defaults['load_alpinejs_with_htmx'] = false;
    $defaults['set_htmx_hxboost'] = false;
    $defaults['load_htmx_backend'] = false;

    // Alpine Ajax Settings
    $defaults['load_alpinejs_backend'] = false;

    // Datastar Settings
    $defaults['load_datastar_backend'] = false;

    // HTMX Extensions - Enable by setting to `true`
    $defaults['load_extension_ajax-header'] = false;
    $defaults['load_extension_alpine-morph'] = false;
    $defaults['load_extension_class-tools'] = false;
    $defaults['load_extension_client-side-templates'] = false;
    $defaults['load_extension_debug'] = false;
    $defaults['load_extension_disable-element'] = false; // Note: key is 'disable-element'
    $defaults['load_extension_event-header'] = false;
    $defaults['load_extension_head-support'] = false;
    $defaults['load_extension_include-vals'] = false;
    $defaults['load_extension_json-enc'] = false;
    $defaults['load_extension_loading-states'] = false;
    $defaults['load_extension_method-override'] = false;
    $defaults['load_extension_morphdom-swap'] = false;
    $defaults['load_extension_multi-swap'] = false;
    $defaults['load_extension_path-deps'] = false;
    $defaults['load_extension_preload'] = false;
    $defaults['load_extension_remove-me'] = false;
    $defaults['load_extension_response-targets'] = false;
    $defaults['load_extension_restored'] = false;
    $defaults['load_extension_sse'] = false;
    $defaults['load_extension_ws'] = false;

    return $defaults;
});
```

#### Common Configuration Examples

**Complete HTMX Setup with Extensions:**
```php
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'htmx';
    $defaults['load_from_cdn'] = false; // Use local files
    $defaults['load_hyperscript'] = true;
    $defaults['set_htmx_hxboost'] = true; // Progressive enhancement
    $defaults['load_htmx_backend'] = true; // Use in admin too

    // Enable commonly used HTMX extensions
    $defaults['load_extension_debug'] = true;
    $defaults['load_extension_loading-states'] = true;
    $defaults['load_extension_preload'] = true;
    $defaults['load_extension_sse'] = true;

    return $defaults;
});
```

**Alpine Ajax Setup:**
```php
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'alpinejs';
    $defaults['load_from_cdn'] = true; // Use CDN for latest version
    $defaults['load_alpinejs_backend'] = true;

    return $defaults;
});
```

**Datastar Configuration:**
```php
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'datastar';
    $defaults['load_from_cdn'] = false;
    $defaults['load_datastar_backend'] = true;

    return $defaults;
});
```

**Production-Ready Configuration (CDN with specific extensions):**
```php
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'htmx';
    $defaults['load_from_cdn'] = true; // Better performance
    $defaults['load_hyperscript'] = true;
    $defaults['set_htmx_hxboost'] = true;

    // Enable production-useful extensions
    $defaults['load_extension_loading-states'] = true;
    $defaults['load_extension_preload'] = true;
    $defaults['load_extension_response-targets'] = true;

    return $defaults;
});
```

#### Register Custom Template Paths

Register custom template paths for your plugin or theme:

```php
add_filter('hmapi/register_template_path', function($paths) {
    $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'hypermedia/';
    $paths['my-theme'] = get_template_directory() . '/custom-hypermedia/';
    return $paths;
});
```

#### Customize Sanitization

Modify the sanitization process for parameters:

```php
// Customize parameter key sanitization
add_filter('hmapi/sanitize_param_key', function($sanitized_key, $original_key) {
    // Custom sanitization logic
    return $sanitized_key;
}, 10, 2);

// Customize parameter value sanitization
add_filter('hmapi/sanitize_param_value', function($sanitized_value, $original_value) {
    // Custom sanitization logic for single values
    return $sanitized_value;
}, 10, 2);

// Customize array parameter value sanitization
add_filter('hmapi/sanitize_param_array_value', function($sanitized_array, $original_array) {
    // Custom sanitization logic for array values
    return array_map('esc_html', $sanitized_array);
}, 10, 2);
```

#### Customize Asset Loading

For developers who need fine-grained control over where JavaScript libraries are loaded from, the plugin provides filters to override asset URLs for all libraries. These filters work in both plugin and library mode, giving you complete flexibility.

**Available Asset Filters:**

- `hmapi/assets/htmx_url` - Override HTMX library URL
- `hmapi/assets/htmx_version` - Override HTMX library version
- `hmapi/assets/hyperscript_url` - Override Hyperscript library URL
- `hmapi/assets/hyperscript_version` - Override Hyperscript library version
- `hmapi/assets/alpinejs_url` - Override Alpine.js library URL
- `hmapi/assets/alpinejs_version` - Override Alpine.js library version
- `hmapi/assets/alpine_ajax_url` - Override Alpine Ajax library URL
- `hmapi/assets/alpine_ajax_version` - Override Alpine Ajax library version
- `hmapi/assets/datastar_url` - Override Datastar library URL
- `hmapi/assets/datastar_version` - Override Datastar library version
- `hmapi/assets/htmx_extension_url` - Override HTMX extension URLs
- `hmapi/assets/htmx_extension_version` - Override HTMX extension versions

**Filter Parameters:**

Each filter receives the following parameters:
- `$url` - Current URL (CDN or local)
- `$load_from_cdn` - Whether CDN loading is enabled
- `$asset` - Asset configuration array with `local_url` and `local_path`
- `$is_library_mode` - Whether running in library mode

For HTMX extensions, additional parameters:
- `$ext_slug` - Extension slug (e.g., 'loading-states', 'sse')

**Common Use Cases:**

**Load from Custom CDN:**
```php
// Use your own CDN for all libraries
add_filter('hmapi/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://your-cdn.com/js/htmx@2.0.3.min.js';
}, 10, 4);

add_filter('hmapi/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://your-cdn.com/js/datastar@1.0.0.min.js';
}, 10, 4);
```

**Custom Local Paths for Library Mode:**
```php
// Override asset URLs when running as library with custom vendor structure
add_filter('hmapi/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode) {
        // Load from your custom assets directory
        return content_url('plugins/my-plugin/assets/htmx/htmx.min.js');
    }
    return $url;
}, 10, 4);

add_filter('hmapi/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode) {
        return content_url('plugins/my-plugin/assets/datastar/datastar.min.js');
    }
    return $url;
}, 10, 4);
```

**Version-Specific Loading:**
```php
// Force specific versions for compatibility
add_filter('hmapi/assets/alpinejs_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js';
}, 10, 4);

add_filter('hmapi/assets/alpinejs_version', function($version, $load_from_cdn, $asset, $is_library_mode) {
    return '3.13.0';
}, 10, 4);
```

**Conditional Loading Based on Environment:**
```php
// Different sources for different environments
add_filter('hmapi/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if (wp_get_environment_type() === 'production') {
        return 'https://your-production-cdn.com/datastar.min.js';
    } elseif (wp_get_environment_type() === 'staging') {
        return 'https://staging-cdn.com/datastar.js';
    } else {
        // Development - use local file
        return $asset['local_url'];
    }
}, 10, 4);
```

**HTMX Extensions from Custom Sources:**
```php
// Override specific HTMX extensions
add_filter('hmapi/assets/htmx_extension_url', function($url, $ext_slug, $load_from_cdn, $is_library_mode) {
    // Load SSE extension from custom source
    if ($ext_slug === 'sse') {
        return 'https://your-custom-cdn.com/htmx-extensions/sse.js';
    }

    // Load all extensions from your CDN
    return "https://your-cdn.com/htmx-extensions/{$ext_slug}.js";
}, 10, 4);
```

**Library Mode with Custom Vendor Directory Detection:**
```php
// Handle non-standard vendor directory structures
add_filter('hmapi/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode && empty($url)) {
        // Custom detection for non-standard paths
        $plugin_path = plugin_dir_path(__FILE__);
        if (strpos($plugin_path, '/vendor-custom/') !== false) {
            $custom_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $plugin_path);
            return $custom_url . 'assets/js/libs/htmx.min.js';
        }
    }
    return $url;
}, 10, 4);
```

**Complete Asset Override Example:**
```php
// Override all hypermedia library URLs for a custom setup
function my_plugin_override_hypermedia_assets() {
    $base_url = 'https://my-custom-cdn.com/hypermedia/';

    // HTMX
    add_filter('hmapi/assets/htmx_url', function() use ($base_url) {
        return $base_url . 'htmx@2.0.3.min.js';
    });

    // Hyperscript
    add_filter('hmapi/assets/hyperscript_url', function() use ($base_url) {
        return $base_url . 'hyperscript@0.9.12.min.js';
    });

    // Alpine.js
    add_filter('hmapi/assets/alpinejs_url', function() use ($base_url) {
        return $base_url . 'alpinejs@3.13.0.min.js';
    });

    // Alpine Ajax
    add_filter('hmapi/assets/alpine_ajax_url', function() use ($base_url) {
        return $base_url . 'alpine-ajax@1.3.0.min.js';
    });

    // Datastar
    add_filter('hmapi/assets/datastar_url', function() use ($base_url) {
        return $base_url . 'datastar@1.0.0.min.js';
    });

    // HTMX Extensions
    add_filter('hmapi/assets/htmx_extension_url', function($url, $ext_slug) use ($base_url) {
        return $base_url . "htmx-extensions/{$ext_slug}.js";
    }, 10, 2);
}

// Apply overrides only in library mode
add_action('plugins_loaded', function() {
    if (function_exists('hm_is_library_mode') && hm_is_library_mode()) {
        my_plugin_override_hypermedia_assets();
    }
});
```

These filters provide maximum flexibility for developers who need to:
- Host libraries on their own CDN for performance/security
- Use custom builds or versions
- Handle non-standard vendor directory structures
- Implement environment-specific loading strategies
- Ensure asset availability in complex deployment scenarios

#### Disable Admin Interface Completely

If you want to configure everything programmatically and hide the admin interface, define the `HMAPI_LIBRARY_MODE` constant in your `wp-config.php` or a custom plugin file. This will prevent the settings page from being added.

```php
// In wp-config.php or a custom plugin file
define('HMAPI_LIBRARY_MODE', true);

// You can then configure the plugin using filters as needed
add_filter('hmapi/default_options', function($defaults) {
    // Your configuration here. See above for examples.
    return $defaults;
});
```

## Security

Every call to the `wp-html` endpoint will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all Hypermedia requests automatically.

If you are new to Hypermedia, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Remember that Hypermedia requires you to validate and sanitize any data you receive from the user. This is something developers used to do all the time, but it seems to have been forgotten by newer generations of software developers.

If you are not familiar with how WordPress recommends handling data sanitization and escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) on [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

### REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-html`, to avoid security issues like directory traversal attacks. It will also limit access so you can't use it to access any file outside the `hypermedia` folder within your own theme.

The parameters and their values passed to the endpoint via GET or POST will be sanitized with `sanitize_key()` and `sanitize_text_field()`, respectively.

Filters `hmapi/sanitize_param_key` and `hmapi/sanitize_param_value` are available to modify the sanitization process if needed. For backward compatibility, the old filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are still supported but deprecated.

Do your due diligence and ensure you are not returning unsanitized data back to the user or using it in a way that could pose a security issue for your site. Hypermedia requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Examples

Check out the showcase/demo theme at [EstebanForge/Hypermedia-Theme-WordPress](https://github.com/EstebanForge/Hypermedia-Theme-WordPress).

## Suggestions, Support

Please, open [a discussion](https://github.com/EstebanForge/hypermedia-api-wordpress/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/EstebanForge/hypermedia-api-wordpress/issues).

## FAQ
[FAQ available here](https://github.com/EstebanForge/hypermedia-api-wordpress/blob/main/FAQ.md).

## Changelog

[Changelog available here](https://github.com/EstebanForge/hypermedia-api-wordpress/blob/main/CHANGELOG.md).

## Contributing

You are welcome to contribute to this plugin.

If you have a feature request or a bug report, please open an issue on the [GitHub repository](https://github.com/EstebanForge/hypermedia-api-wordpress/issues).

If you want to contribute with code, please open a pull request.

## License

This plugin is licensed under the GPLv2 or later.

You can find the full license text in the `LICENSE` file.

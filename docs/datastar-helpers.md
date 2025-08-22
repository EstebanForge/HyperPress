# Datastar Helper Functions

These functions provide direct integration with Datastar's Server-Sent Events (SSE) capabilities for real-time updates.

## Quick Start: Minimal SSE Example

This is the smallest possible example to stream updates via SSE from a hypermedia template partial and consume them on the frontend.

```php
// In your hypermedia template partial file, e.g., hypermedia/my-sse-endpoint.hp.php

// Apply rate limiting
if (hp_ds_is_rate_limited()) {
    return; // Rate limited
}

// Initialize SSE (headers are sent automatically)
$sse = hp_ds_sse();
if (!$sse) {
    hp_die('SSE not available');
}

// Read client signals
$signals = hp_ds_read_signals();
$delay = $signals['delay'] ?? 0;
$message = 'Hello, world!';

// Stream message character by character
for ($i = 0; $i < strlen($message); $i++) {
    hp_ds_patch_elements('<div id="message">' . substr($message, 0, $i + 1) . '</div>');

    // Sleep for the provided delay in milliseconds
    usleep($delay * 1000);
}

// Script will automatically exit and send the SSE stream
```

Frontend HTML to consume the SSE endpoint:

```html
<!-- Container for the Datastar component -->
<div data-signals-delay="400">
    <h1>Datastar SDK Demo</h1>
    <p>SSE events will be streamed from the backend to the frontend.</p>

    <div>
        <label for="delay">Delay in milliseconds</label>
        <input data-bind-delay id="delay" type="number" step="100" min="0" />
    </div>

    <button data-on-click="@get('<?php echo hp_get_endpoint_url('my-sse-endpoint'); ?>')">
        Start
    </button>
</div>

<!-- Target element for SSE updates -->
<div id="message">Hello, world!</div>
```

This demonstrates:
- Setting initial signal values with `data-signals-delay`.
- Binding signals to inputs with `data-bind-delay`.
- Triggering the SSE stream with a button using `data-on-click`.

The server receives the `delay` signal to control the stream speed while the `#message` div updates in real time.

**`hp_ds_sse(): ?ServerSentEventGenerator`**

Gets or creates the ServerSentEventGenerator instance. Returns `null` if Datastar SDK is not available.

```php
$sse = hp_ds_sse();
if ($sse) {
    // SSE is available, proceed with real-time updates
    $sse->patchElements('<div id="status">Connected</div>');
}
```

**`hp_ds_read_signals(): array`**

Reads signals sent from the Datastar client. Returns an empty array if Datastar SDK is not available.

```php
// Read client signals
$signals = hp_ds_read_signals();
$user_input = $signals['search_query'] ?? '';
$page_number = $signals['page'] ?? 1;

// Use signals for processing
if (!empty($user_input)) {
    $results = search_posts($user_input, $page_number);
    hp_ds_patch_elements($results_html, ['selector' => '#results']);
}
```

**`hp_ds_patch_elements(string $html, array $options = []): void`**

Patches HTML elements into the DOM via SSE. Supports various patching modes and view transitions.

```php
// Basic element patching
hp_ds_patch_elements('<div id="message">Hello World</div>');

// Advanced patching with options
hp_ds_patch_elements(
    '<div class="notification">Task completed</div>',
    [
        'selector' => '.notifications',
        'mode' => 'append',
        'useViewTransition' => true
    ]
);
```

**`hp_ds_remove_elements(string $selector, array $options = []): void`**

Removes elements from the DOM via SSE.

```php
// Remove specific element
hp_ds_remove_elements('#temp-message');

// Remove with view transition
hp_ds_remove_elements('.expired-items', ['useViewTransition' => true]);
```

**`hp_ds_patch_signals(mixed $signals, array $options = []): void`**

Updates Datastar signals on the client side. Accepts JSON string or array.

```php
// Update single signal
hp_ds_patch_signals(['user_count' => 42]);

// Update multiple signals
hp_ds_patch_signals([
    'loading' => false,
    'message' => 'Data loaded successfully',
    'timestamp' => time()
]);

// Only patch if signal doesn't exist
hp_ds_patch_signals(['default_theme' => 'dark'], ['onlyIfMissing' => true]);
```

**`hp_ds_execute_script(string $script, array $options = []): void`**

Executes JavaScript code on the client via SSE.

```php
// Simple script execution
hp_ds_execute_script('console.log("Server says hello!");');

// Complex client-side operations
hp_ds_execute_script('
    document.querySelector("#progress").style.width = "100%";
    setTimeout(() => {
        location.reload();
    }, 2000);
');
```

**`hp_ds_location(string $url): void`**

Redirects the browser to a new URL via SSE.

```php
// Redirect after processing
hp_ds_location('/dashboard');

// Redirect to external URL
hp_ds_location('https://example.com/success');
```

**`hp_ds_is_rate_limited(array $options = []): bool`**

Checks if current request is rate limited for Datastar SSE endpoints to prevent abuse and protect server resources. Uses WordPress transients for persistence across requests.

```php
// Basic rate limiting (10 requests per 60 seconds)
if (hp_ds_is_rate_limited()) {
    hp_die(__('Rate limit exceeded', 'api-for-htmx'));
}

// Custom rate limiting configuration
if (hp_ds_is_rate_limited([
    'requests_per_window' => 30,      // Allow 30 requests
    'time_window_seconds' => 120,     // Per 2 minutes
    'identifier' => 'search_' . get_current_user_id(), // Custom identifier
    'error_message' => __('Search rate limit exceeded. Please wait.', 'api-for-htmx'),
    'error_selector' => '#search-errors'
])) {
    // Rate limit exceeded - error already sent to client
    return;
}

// Strict rate limiting without SSE feedback
if (hp_ds_is_rate_limited([
    'requests_per_window' => 10,
    'time_window_seconds' => 60,
    'send_sse_response' => false  // Don't send SSE feedback
])) {
    hp_die(__('Too many requests', 'api-for-htmx'));
}

// Different rate limits for different actions
$action = hp_ds_read_signals()['action'] ?? '';

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

if (hp_ds_is_rate_limited($rate_config)) {
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

## Complete SSE Example

Here's a practical example combining multiple Datastar helpers:

```php
// hypermedia/process-upload.hp.php
<?php
// Apply strict rate limiting for uploads (5 uploads per 5 minutes)
if (hp_ds_is_rate_limited([
    'requests_per_window' => 5,
    'time_window_seconds' => 300,
    'identifier' => 'file_upload_' . get_current_user_id(),
    'error_message' => __('Upload rate limit exceeded. You can upload 5 files every 5 minutes.', 'api-for-htmx'),
    'error_selector' => '#upload-errors'
])) {
    return; // Rate limited - error sent via SSE
}

// Initialize SSE
$sse = hp_ds_sse();
if (!$sse) {
    hp_die('SSE not available');
}

// Show progress
hp_ds_patch_elements('<div id="status">Processing upload...</div>');
hp_ds_patch_signals(['progress' => 0]);

// Simulate file processing
for ($i = 1; $i <= 5; $i++) {
    sleep(1);
    hp_ds_patch_signals(['progress' => $i * 20]);
    hp_ds_patch_elements('<div id="status">Processing... ' . ($i * 20) . '%</div>');
}

// Complete
hp_ds_patch_elements('<div id="status" class="success">Upload complete!</div>');
hp_ds_patch_signals(['progress' => 100, 'completed' => true]);

// Redirect after 2 seconds
hp_ds_execute_script('setTimeout(() => { window.location.href = "/dashboard"; }, 2000);');
?>
```

## Complete Datastar Integration Example

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
        data-on-input="@get('<?php hp_endpoint_url('search-users-validate'); ?>')"
        placeholder="Search users..."
    />

    <!-- Search button -->
    <button
        data-on-click="@get('<?php hp_endpoint_url('search-users'); ?>')"
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

**Backend Template - Real-time Validation (hypermedia/search-users-validate.hp.php):**
```php
<?php
// Apply rate limiting
if (hp_ds_is_rate_limited()) {
    return; // Rate limited
}

// Get search query from signals
$signals = hp_ds_read_signals();
$query = trim($signals['query'] ?? '');

// Validate query length
if (strlen($query) < 2 && strlen($query) > 0) {
    hp_ds_patch_elements(
        '<div class="validation-error">Please enter at least 2 characters</div>',
        ['selector' => '#search-validation']
    );
    hp_ds_patch_signals(['query_valid' => false]);
} elseif (strlen($query) >= 2) {
    hp_ds_remove_elements('#search-validation .validation-error');
    hp_ds_patch_signals(['query_valid' => true]);

    // Show search suggestion
    hp_ds_patch_elements(
        '<div class="search-hint">Press Enter or click Search to find users</div>',
        ['selector' => '#search-validation']
    );
}
?>
```

**Backend Template - Search Execution (hypermedia/search-users.hp.php):**
```php
<?php
// Apply rate limiting for search operations
if (hp_ds_is_rate_limited([
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
$signals = hp_ds_read_signals();
$query = sanitize_text_field($signals['query'] ?? '');

// Set loading state
hp_ds_patch_signals(['loading' => true, 'results' => []]);
hp_ds_patch_elements('<div class="loading">Searching users...</div>', ['selector' => '#search-results']);

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
        hp_get_endpoint_url('user-details'),
        $user->ID
    );
}

$results_html .= '</div>';

// Update UI with results
if (count($users) > 0) {
    hp_ds_patch_elements($results_html, ['selector' => '#search-results']);
    hp_ds_patch_signals([
        'loading' => false,
        'results' => $results_data,
        'result_count' => count($users)
    ]);

    // Show success notification
    hp_ds_execute_script("
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.textContent = 'Found " . count($users) . " users';
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    ");
} else {
    hp_ds_patch_elements('<div class="no-results">No users found for \"' . esc_html($query) . '\"</div>', ['selector' => '#search-results']);
    hp_ds_patch_signals(['loading' => false, 'results' => []]);
}
?>
```

This example demonstrates:
- **Frontend**: Datastar signals, reactive UI, and SSE endpoint integration
- **Backend**: Real-time feedback, progressive enhancement, and signal processing
- **Helper Usage**: `hp_ds_read_signals()`, `hp_get_endpoint_url()`, and all `hp_ds_*` functions
- **Security**: Input sanitization and validation, plus rate limiting for SSE endpoints
- **UX**: Loading states, real-time validation, and user feedback

## Rate Limiting Integration Example

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
        data-on-keyup.enter="@get('<?php hp_endpoint_url('send-message'); ?>')"
        data-bind-disabled="rate_limited"
        placeholder="Type your message..."
    />

    <!-- Send button -->
    <button
        data-on-click="@get('<?php hp_endpoint_url('send-message'); ?>')"
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

**Backend Template (hypermedia/send-message.hp.php):**
```php
<?php
// Apply rate limiting for chat messages (10 messages per minute)
if (hp_ds_is_rate_limited([
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
$signals = hp_ds_read_signals();
$message = trim($signals['message'] ?? '');

// Validate message
if (empty($message)) {
    hp_ds_patch_elements(
        '<div class="error">' . esc_html__('Message cannot be empty', 'api-for-htmx') . '</div>',
        ['selector' => '#chat-errors']
    );
    return;
}

if (strlen($message) > 500) {
    hp_ds_patch_elements(
        '<div class="error">' . esc_html__('Message too long (max 500 characters)', 'api-for-htmx') . '</div>',
        ['selector' => '#chat-errors']
    );
    return;
}

// Clear any errors
hp_ds_remove_elements('#chat-errors .error');

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

hp_ds_patch_elements($message_html, [
    'selector' => '#chat-messages',
    'mode' => 'append'
]);

// Clear input field
hp_ds_patch_signals(['message' => '']);

// Show success feedback
hp_ds_execute_script("
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
- **Intuitive Function Naming**: `hp_ds_is_rate_limited()` returns true when blocked
- **Proactive Rate Limiting**: Applied before processing the request
- **Automatic User Feedback**: Rate limit helper sends SSE responses with error messages
- **Dynamic UI Updates**: Frontend reacts to rate limit signals automatically
- **Resource Protection**: Prevents abuse of SSE endpoints
- **User Experience**: Clear feedback about rate limits and remaining requests

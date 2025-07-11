<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Rate limiting check
if (hm_ds_is_rate_limited()) {
    return;
}

// Secure it.
$hmapi_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');

// Check if nonce is valid.
if (!isset($hmapi_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hmapi_nonce)), 'hmapi_nonce')) {
    hm_die('Nonce verification failed.');
}

// Action = datastar_do_something
if (!isset($hmvals['action']) || $hmvals['action'] != 'datastar_do_something') {
    hm_die('Invalid action.');
}
?>

<div class="hmapi-demo-container">
	<h3>Hello Datastar!</h3>

	<p>Demo template loaded from <code>plugins/Hypermedia-API-WordPress/<?php echo esc_html(HMAPI_TEMPLATE_DIR); ?>/datastar-demo.hm.php</code></p>

	<p>Received params ($hmvals):</p>

	<pre>
		<?php var_dump($hmvals); ?>
	</pre>

	<div class="datastar-examples"
		 data-store='{"message": "", "postData": "Hello from Datastar!", "formData": {"name": "", "email": ""}, "loading": false}'>

		<h4>Datastar Examples:</h4>

		<!-- Example 1: Simple GET request -->
		<div class="example-section">
			<h5>Example 1: GET Request</h5>
			<button
				data-on-click="$$get('<?php echo hm_get_endpoint_url('datastar-demo'); ?>?action=datastar_do_something&demo_type=simple_get&timestamp=' + Date.now())"
				data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
				data-on-load-start="loading = true"
				data-on-load-end="loading = false"
				class="button button-primary">
				<span data-show="!loading">Simple GET Request</span>
				<span data-show="loading">Loading...</span>
			</button>
			<div data-show="message" data-text="message" class="response-area"></div>
		</div>

		<!-- Example 2: POST request with data -->
		<div class="example-section">
			<h5>Example 2: POST Request with Data</h5>
			<input type="text"
				   data-model="postData"
				   placeholder="Enter some data"
				   class="regular-text">
			<button
				data-on-click="$$post('<?php echo hm_get_endpoint_url('datastar-demo'); ?>', {action: 'datastar_do_something', demo_type: 'post_with_data', user_data: postData, timestamp: Date.now()})"
				data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
				data-on-load-start="loading = true"
				data-on-load-end="loading = false"
				class="button button-primary">
				<span data-show="!loading">POST with Data</span>
				<span data-show="loading">Posting...</span>
			</button>
		</div>

		<!-- Example 3: Form submission -->
		<div class="example-section">
			<h5>Example 3: Form Submission</h5>
			<form data-on-submit="$$post('<?php echo hm_get_endpoint_url('datastar-demo'); ?>', {action: 'datastar_do_something', demo_type: 'form_submission', name: formData.name, email: formData.email})"
				  data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
				  data-on-load-start="loading = true"
				  data-on-load-end="loading = false">
				<p>
					<label for="ds-demo-name">Name:</label>
					<input type="text"
						   id="ds-demo-name"
						   data-model="formData.name"
						   required
						   class="regular-text">
				</p>
				<p>
					<label for="ds-demo-email">Email:</label>
					<input type="email"
						   id="ds-demo-email"
						   data-model="formData.email"
						   required
						   class="regular-text">
				</p>
				<button type="submit" class="button button-primary" data-bind-disabled="loading">
					<span data-show="!loading">Submit Form</span>
					<span data-show="loading">Submitting...</span>
				</button>
			</form>
		</div>

		<!-- Example 4: Real-time updates -->
		<div class="example-section">
			<h5>Example 4: Real-time Data Binding</h5>
			<p>Type in the input below and see real-time updates:</p>
			<input type="text"
				   data-model="postData"
				   placeholder="Type something..."
				   class="regular-text">
			<p>You typed: <strong data-text="postData"></strong></p>
			<p>Length: <span data-text="postData.length"></span> characters</p>
		</div>

		<!-- Example 5: Server-sent Events (SSE) simulation -->
		<div class="example-section">
			<h5>Example 5: Fetch with Merge</h5>
			<button
				data-on-click="$$get('<?php echo hm_get_endpoint_url('datastar-demo'); ?>?action=datastar_do_something&demo_type=fetch_merge&timestamp=' + Date.now())"
				data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
				data-merge-store
				class="button button-primary">
				Fetch and Merge Data
			</button>
			<div data-show="serverTime">
				<p>Server time: <span data-text="serverTime"></span></p>
			</div>
		</div>
	</div>

	<style>
		.example-section {
			margin: 20px 0;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.response-area {
			margin-top: 10px;
			padding: 10px;
			background: #f9f9f9;
			border-left: 4px solid #0073aa;
		}
		.regular-text {
			width: 300px;
			margin: 5px 0;
		}
		[data-show="false"] {
			display: none !important;
		}
	</style>
</div>

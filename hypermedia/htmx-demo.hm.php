<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Secure it.
$hp_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');

// Check if nonce is valid.
if (!isset($hp_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hp_nonce)), 'hyperpress_nonce')) {
    hp_die('Nonce verification failed.');
}

// Action = htmx_do_something
if (!isset($hp_vals['action']) || $hp_vals['action'] != 'htmx_do_something') {
    hp_die('Invalid action.');
}

// Process different demo types
$demo_type = $hp_vals['demo_type'] ?? 'default';
$processed_message = '';

switch ($demo_type) {
    case 'simple_get':
        $processed_message = __('HTMX GET request processed successfully!', 'hyperpress');
        break;
    case 'post_with_data':
        $user_data = $hp_vals['user_data'] ?? __('No data', 'hyperpress');
        $processed_message = sprintf(__('HTMX POST processed. You sent: %s', 'hyperpress'), esc_html($user_data));
        break;
    case 'form_submission':
        $name = $hp_vals['name'] ?? __('Unknown', 'hyperpress');
        $email = $hp_vals['email'] ?? __('No email', 'hyperpress');
        $processed_message = sprintf(__('Form submitted successfully! Name: %s, Email: %s', 'hyperpress'), esc_html($name), esc_html($email));
        break;
    default:
        $processed_message = __('HTMX demo template processed.', 'hyperpress');
}
?>

<div class="hyperpress-demo-container">
	<h3><?php esc_html_e('Hello HTMX!', 'hyperpress'); ?></h3>

	<p><?php esc_html_e('Demo template loaded from', 'hyperpress'); ?> <code>plugins/HyperPress/<?php echo esc_html(HPRESS_TEMPLATE_DIR); ?>/htmx-demo.hm.php</code></p>

	<?php if (!empty($processed_message)): ?>
		<div class="notice notice-success">
			<p><?php echo esc_html($processed_message); ?></p>
		</div>
	<?php endif; ?>

	<div class="htmx-examples">
		<h4><?php esc_html_e('HTMX Examples:', 'hyperpress'); ?></h4>

		<!-- Example 1: Simple GET request -->
		<div class="example-section">
			<h5><?php esc_html_e('Example 1: GET Request', 'hyperpress'); ?></h5>
			<button hx-get="<?php echo hp_get_endpoint_url('htmx-demo'); ?>?action=htmx_do_something&demo_type=simple_get&timestamp=' + Date.now()"
					hx-target="#htmx-response-1"
					hx-indicator="#htmx-loading-1"
					class="button button-primary">
				<?php esc_html_e('Simple GET Request', 'hyperpress'); ?>
			</button>
			<span id="htmx-loading-1" class="htmx-indicator" style="display:none;"><?php esc_html_e('Loading...', 'hyperpress'); ?></span>
			<div id="htmx-response-1" class="response-area"></div>
		</div>

		<!-- Example 2: POST request with data -->
		<div class="example-section">
			<h5><?php esc_html_e('Example 2: POST Request with Data', 'hyperpress'); ?></h5>
			<input type="text" id="htmx-post-data" placeholder="<?php esc_attr_e('Enter some data', 'hyperpress'); ?>" class="regular-text" value="Hello from HTMX!">
			<button hx-post="<?php echo hp_get_endpoint_url('htmx-demo'); ?>"
					hx-vals='{"action": "htmx_do_something", "demo_type": "post_with_data", "user_data": htmxDemoData.postData}'
					hx-target="#htmx-response-2"
					hx-indicator="#htmx-loading-2"
					class="button button-primary">
				<?php esc_html_e('POST with Data', 'hyperpress'); ?>
			</button>
			<span id="htmx-loading-2" class="htmx-indicator" style="display:none;"><?php esc_html_e('Posting...', 'hyperpress'); ?></span>
			<div id="htmx-response-2" class="response-area"></div>
		</div>

		<!-- Example 3: Form submission -->
		<div class="example-section">
			<h5><?php esc_html_e('Example 3: Form Submission', 'hyperpress'); ?></h5>
			<form hx-post="<?php echo hp_get_endpoint_url('htmx-demo'); ?>"
				  hx-target="#htmx-response-3"
				  hx-indicator="#htmx-loading-3">
				<input type="hidden" name="action" value="htmx_do_something">
				<input type="hidden" name="demo_type" value="form_submission">
				<p>
					<label for="htmx-demo-name"><?php esc_html_e('Name:', 'hyperpress'); ?></label>
					<input type="text" id="htmx-demo-name" name="name" required class="regular-text">
				</p>
				<p>
					<label for="htmx-demo-email"><?php esc_html_e('Email:', 'hyperpress'); ?></label>
					<input type="email" id="htmx-demo-email" name="email" required class="regular-text">
				</p>
				<button type="submit" class="button button-primary">
					<?php esc_html_e('Submit Form', 'hyperpress'); ?>
				</button>
				<span id="htmx-loading-3" class="htmx-indicator" style="display:none;"><?php esc_html_e('Submitting...', 'hyperpress'); ?></span>
			</form>
			<div id="htmx-response-3" class="response-area"></div>
		</div>

		<!-- Example 4: Auto-refresh content -->
		<div class="example-section">
			<h5><?php esc_html_e('Example 4: Auto-refresh Content', 'hyperpress'); ?></h5>
			<div hx-get="<?php echo hp_get_endpoint_url('htmx-demo'); ?>?action=htmx_do_something&demo_type=simple_get&auto_refresh=true"
				 hx-trigger="every 10s"
				 hx-target="this"
				 class="response-area">
				<p><?php esc_html_e('This content will auto-refresh every 10 seconds', 'hyperpress'); ?></p>
			</div>
		</div>
	</div>

	<h5><?php esc_html_e('Received params ($hp_vals):', 'hyperpress'); ?></h5>
	<pre><?php var_dump($hp_vals); ?></pre>

	<script>
		// Simple data store for HTMX examples
		const htmxDemoData = {
			postData: document.getElementById('htmx-post-data')?.value || 'Hello from HTMX!'
		};

		// Update post data when input changes
		document.addEventListener('DOMContentLoaded', function() {
			const postInput = document.getElementById('htmx-post-data');
			if (postInput) {
				postInput.addEventListener('input', function() {
					htmxDemoData.postData = this.value;
				});
			}
		});
	</script>

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
		.htmx-indicator {
			color: #0073aa;
			font-style: italic;
		}
		.notice {
			padding: 1px 12px;
			margin: 5px 0 15px;
			background-color: #fff;
			border-left: 4px solid #00a0d2;
		}
		.notice-success {
			border-left-color: #46b450;
		}
	</style>
</div>

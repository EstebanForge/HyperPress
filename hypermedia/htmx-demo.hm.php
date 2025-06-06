<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Secure it.
$hmapi_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');

// Check if nonce is valid.
if (!isset($hmapi_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hmapi_nonce)), 'hmapi_nonce')) {
    hmapi_die('Nonce verification failed.');
}

// Action = hmapi_do_something
if (!isset($hmvals['action']) || $hmvals['action'] != 'hmapi_do_something') {
    hmapi_die('Invalid action.');
}
?>

<div class="hmapi-demo-container">
	<h3>Hello HTMX!</h3>

	<p>Demo template loaded from <code>plugins/api-for-htmx/<?php echo esc_html(HMAPI_TEMPLATE_DIR); ?>/htmx-demo.hm.php</code></p>

	<p>Received params ($hmvals):</p>

	<pre>
		<?php var_dump($hmvals); ?>
	</pre>

</div>

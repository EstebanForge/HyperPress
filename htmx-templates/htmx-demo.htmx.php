<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Secure it.
$hxwp_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE']);

// Check if nonce is valid.
if (!isset($hxwp_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hxwp_nonce)), 'hxwp_nonce')) {
    hxwp_die('Nonce verification failed.');
}

// Action = htmx_do_something
if (!isset($hxvals['action']) || $hxvals['action'] != 'htmx_do_something') {
    hxwp_die('Invalid action.');
}
?>
<h3>Hello HTMX!</h3>

<p>Demo template loaded from <code>plugins/api-for-htmx/htmx-templates/htmx-demo.htmx.php</code></p>

<p>Received params ($hxvals):</p>

<pre>
<?php var_dump($hxvals); ?>
</pre>

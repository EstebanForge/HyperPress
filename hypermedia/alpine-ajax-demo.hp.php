<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Secure it.
$hp_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');

// Check if nonce is valid.
if (!isset($hp_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hp_nonce)), 'hyperpress_nonce')) {
    hp_die('Nonce verification failed.');
}

// Action = alpine_ajax_do_something
if (!isset($hp_vals['action']) || $hp_vals['action'] != 'alpine_ajax_do_something') {
    hp_die('Invalid action.');
}
?>

<div class="hyperpress-demo-container">
  <h3>Hello Alpine Ajax!</h3>

  <p>Demo template loaded from <code>plugins/HyperPress/<?php echo esc_html(HYPERPRESS_TEMPLATE_DIR); ?>/alpine-ajax-demo.hm.php</code></p>

  <p>Received params ($hp_vals):</p>

  <pre>
		<?php var_dump($hp_vals); ?>
	</pre>

  <div class="alpine-ajax-examples" x-data="alpineAjaxDemo()">
    <h4>Alpine Ajax Examples:</h4>

    <!-- Example 1: Simple GET request -->
    <div class="example-section">
      <h5>Example 1: GET Request</h5>
      <button @click="simpleGet()" class="button button-primary">
        Simple GET Request
      </button>
      <div x-show="response1" x-html="response1" class="response-area"></div>
    </div>

    <!-- Example 2: POST request with data -->
    <div class="example-section">
      <h5>Example 2: POST Request with Data</h5>
      <input type="text" x-model="postData" placeholder="Enter some data" class="regular-text">
      <button @click="postWithData()" class="button button-primary">
        POST with Data
      </button>
      <div x-show="response2" x-html="response2" class="response-area"></div>
    </div>

    <!-- Example 3: Form submission -->
    <div class="example-section">
      <h5>Example 3: Form Submission</h5>
      <form @submit.prevent="submitForm($event)">
        <p>
          <label for="demo-name">Name:</label>
          <input type="text" id="demo-name" name="name" required class="regular-text">
        </p>
        <p>
          <label for="demo-email">Email:</label>
          <input type="email" id="demo-email" name="email" required class="regular-text">
        </p>
        <button type="submit" class="button button-primary">Submit Form</button>
      </form>
      <div x-show="response3" x-html="response3" class="response-area"></div>
    </div>
  </div>

  <script>
    function alpineAjaxDemo() {
      return {
        response1: '',
        response2: '',
        response3: '',
        postData: 'Hello from Alpine Ajax!',

        async simpleGet() {
          try {
            const response = await this.$ajax('<?php echo hp_get_endpoint_url('alpine-ajax-demo'); ?>', {
              method: 'GET',
              params: {
                action: 'alpine_ajax_do_something',
                demo_type: 'simple_get',
                timestamp: Date.now()
              },
              headers: {
                'X-WP-Nonce': hyperpress_params.nonce
              }
            });
            this.response1 = response;
          } catch (error) {
            this.response1 = '<div class="notice notice-error"><p>Error: ' + error.message + '</p></div>';
          }
        },

        async postWithData() {
          try {
            const response = await this.$ajax('<?php echo hp_get_endpoint_url('alpine-ajax-demo'); ?>', {
              method: 'POST',
              body: {
                action: 'alpine_ajax_do_something',
                demo_type: 'post_with_data',
                user_data: this.postData,
                timestamp: Date.now()
              },
              headers: {
                'X-WP-Nonce': hyperpress_params.nonce
              }
            });
            this.response2 = response;
          } catch (error) {
            this.response2 = '<div class="notice notice-error"><p>Error: ' + error.message + '</p></div>';
          }
        },

        async submitForm(event) {
          try {
            const formData = new FormData(event.target);
            formData.append('action', 'alpine_ajax_do_something');
            formData.append('demo_type', 'form_submission');

            const response = await this.$ajax('<?php echo hp_get_endpoint_url('alpine-ajax-demo'); ?>', {
              method: 'POST',
              body: formData,
              headers: {
                'X-WP-Nonce': hyperpress_params.nonce
              }
            });
            this.response3 = response;
            event.target.reset();
          } catch (error) {
            this.response3 = '<div class="notice notice-error"><p>Error: ' + error.message + '</p></div>';
          }
        }
      }
    }
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
  </style>
</div>

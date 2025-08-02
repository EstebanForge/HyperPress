<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Basic security check - no specific action required for index
$hmapi_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');
if (!empty($_POST) && (!isset($hmapi_nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($hmapi_nonce)), 'hmapi_nonce'))) {
    hm_die('Nonce verification failed.');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HyperPress: Modern Hypermedia for WordPress - Demos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f1f1f1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
        }

        .demos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .demo-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            background: #fafafa;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .demo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .demo-title {
            color: #0073aa;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .demo-description {
            color: #666;
            margin-bottom: 20px;
        }

        .demo-examples {
            margin-top: 20px;
        }

        .example-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }

        .button {
            background: #0073aa;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 5px 0;
        }

        .button:hover {
            background: #005a87;
            color: white;
        }

        .button-secondary {
            background: #666;
        }

        .button-secondary:hover {
            background: #555;
        }

        .input-field {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .response-area {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
            min-height: 20px;
        }

        .tech-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .nonce-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        pre {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚀 HyperPress: Modern Hypermedia for WordPress</h1>
            <p>Interactive demos showcasing HTMX, Alpine Ajax, and Datastar integration with WordPress</p>
        </div>

        <div class="tech-info">
            <h3>🔐 WordPress Nonce Integration</h3>
            <p>All demos below automatically include WordPress nonces for security. The plugin validates requests using either:</p>
            <ul>
                <li><strong>HTTP Header:</strong> <code>X-WP-Nonce</code> (recommended for AJAX requests)</li>
                <li><strong>Form Parameter:</strong> <code>_wpnonce</code> (for form submissions)</li>
            </ul>
            <div class="nonce-info">
                <strong>Current Nonce:</strong> <code id="current-nonce"><?php echo wp_create_nonce('hmapi_nonce'); ?></code>
                <br><small>This nonce is automatically included in all requests by the respective libraries.</small>
            </div>
        </div>

        <div class="demos-grid">
            <!-- HTMX Demo Card -->
            <div class="demo-card">
                <h2 class="demo-title">⚡ HTMX Demo</h2>
                <p class="demo-description">
                    HTMX allows you to access modern browser features directly from HTML, using attributes to define AJAX requests, CSS transitions, WebSockets and Server Sent Events.
                </p>

                <div class="demo-examples">
                    <div class="example-item">
                        <h4>Simple GET Request</h4>
                        <button hx-get="<?php echo hm_get_endpoint_url('htmx-demo'); ?>?action=htmx_do_something&demo_type=simple_get"
                            hx-target="#htmx-response-1"
                            hx-indicator="#htmx-loading-1"
                            class="button">
                            Load Content
                        </button>
                        <span id="htmx-loading-1" class="htmx-indicator" style="display:none;">Loading...</span>
                        <div id="htmx-response-1" class="response-area"></div>
                    </div>

                    <div class="example-item">
                        <h4>POST with Form Data</h4>
                        <form hx-post="<?php echo hm_get_endpoint_url('htmx-demo'); ?>"
                            hx-target="#htmx-response-2">
                            <input type="hidden" name="action" value="htmx_do_something">
                            <input type="hidden" name="demo_type" value="form_post">
                            <input type="text" name="user_input" placeholder="Enter some text" class="input-field">
                            <button type="submit" class="button">Submit Form</button>
                        </form>
                        <div id="htmx-response-2" class="response-area"></div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo hm_get_endpoint_url('htmx-demo'); ?>?action=htmx_do_something&demo_type=full_demo"
                            class="button button-secondary" target="_blank">
                            View Full HTMX Demo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alpine Ajax Demo Card -->
            <div class="demo-card">
                <h2 class="demo-title">🏔️ Alpine Ajax Demo</h2>
                <p class="demo-description">
                    Alpine Ajax extends Alpine.js with powerful AJAX capabilities, providing a reactive approach to handling HTTP requests with automatic state management.
                </p>

                <div class="demo-examples" x-data="alpineAjaxDemoCard()">
                    <div class="example-item">
                        <h4>Simple GET Request</h4>
                        <button @click="simpleGet()" class="button" :disabled="loading">
                            <span x-show="!loading">Load Content</span>
                            <span x-show="loading">Loading...</span>
                        </button>
                        <div x-show="response1" x-html="response1" class="response-area"></div>
                    </div>

                    <div class="example-item">
                        <h4>POST with Data</h4>
                        <input type="text" x-model="inputData" placeholder="Enter some text" class="input-field">
                        <button @click="postData()" class="button" :disabled="loading">
                            <span x-show="!loading">Send Data</span>
                            <span x-show="loading">Sending...</span>
                        </button>
                        <div x-show="response2" x-html="response2" class="response-area"></div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo hm_get_endpoint_url('alpine-ajax-demo'); ?>?action=alpine_ajax_do_something&demo_type=full_demo"
                            class="button button-secondary" target="_blank">
                            View Full Alpine Ajax Demo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Datastar Demo Card -->
            <div class="demo-card">
                <h2 class="demo-title">⭐ Datastar Demo</h2>
                <p class="demo-description">
                    Datastar offers a hypermedia-driven approach with built-in state management, real-time updates, and server-sent events, all through declarative HTML attributes.
                </p>

                <div class="demo-examples"
                    data-store='{"message": "", "inputData": "Hello Datastar!", "loading": false}'>

                    <div class="example-item">
                        <h4>Simple GET Request</h4>
                        <button data-on-click="$$get('<?php echo hm_get_endpoint_url('datastar-demo'); ?>?action=datastar_do_something&demo_type=simple_get')"
                            data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
                            data-on-load-start="loading = true"
                            data-on-load-end="loading = false"
                            class="button">
                            <span data-show="!loading">Load Content</span>
                            <span data-show="loading">Loading...</span>
                        </button>
                        <div data-show="message" data-text="message" class="response-area"></div>
                    </div>

                    <div class="example-item">
                        <h4>POST with Data Binding</h4>
                        <input type="text" data-model="inputData" placeholder="Enter some text" class="input-field">
                        <button data-on-click="$$post('<?php echo hm_get_endpoint_url('datastar-demo'); ?>', {action: 'datastar_do_something', demo_type: 'post_data', user_data: inputData})"
                            data-header="X-WP-Nonce:<?php echo wp_create_nonce('hmapi_nonce'); ?>"
                            data-on-load-start="loading = true"
                            data-on-load-end="loading = false"
                            class="button">
                            <span data-show="!loading">Send Data</span>
                            <span data-show="loading">Sending...</span>
                        </button>
                        <p data-show="inputData">You typed: <strong data-text="inputData"></strong></p>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo hm_get_endpoint_url('datastar-demo'); ?>?action=datastar_do_something&demo_type=full_demo"
                            class="button button-secondary" target="_blank">
                            View Full Datastar Demo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="tech-info">
            <h3>🛠️ Technical Implementation</h3>
            <p>Each demo showcases different aspects of hypermedia integration:</p>
            <ul>
                <li><strong>Security:</strong> WordPress nonces are automatically handled</li>
                <li><strong>Template System:</strong> Server-side PHP templates in <code>/hypermedia/</code> directory</li>
                <li><strong>Validation:</strong> Built-in request validation and sanitization</li>
                <li><strong>Error Handling:</strong> Graceful error responses and user feedback</li>
            </ul>

            <h4>API Endpoints:</h4>
            <pre><code>GET/POST <?php echo hm_get_endpoint_url('{template-name}'); ?>
- HTMX Demo: <?php echo hm_get_endpoint_url('htmx-demo'); ?>
- Alpine Ajax Demo: <?php echo hm_get_endpoint_url('alpine-ajax-demo'); ?>
- Datastar Demo: <?php echo hm_get_endpoint_url('datastar-demo'); ?></code></pre>
        </div>
    </div>

    <script>
        // Alpine.js component for the demo card
        function alpineAjaxDemoCard() {
            return {
                response1: '',
                response2: '',
                inputData: 'Hello Alpine Ajax!',
                loading: false,

                async simpleGet() {
                    this.loading = true;
                    try {
                        const response = await this.$ajax('<?php echo hm_get_endpoint_url('alpine-ajax-demo'); ?>', {
                            method: 'GET',
                            params: {
                                action: 'alpine_ajax_do_something',
                                demo_type: 'simple_get',
                                timestamp: Date.now()
                            },
                            headers: {
                                'X-WP-Nonce': hmapi_params.nonce
                            }
                        });
                        this.response1 = response;
                    } catch (error) {
                        this.response1 = '<div style="color: red;">Error: ' + error.message + '</div>';
                    }
                    this.loading = false;
                },

                async postData() {
                    this.loading = true;
                    try {
                        const response = await this.$ajax('<?php echo hm_get_endpoint_url('alpine-ajax-demo'); ?>', {
                            method: 'POST',
                            body: {
                                action: 'alpine_ajax_do_something',
                                demo_type: 'post_with_data',
                                user_data: this.inputData,
                                timestamp: Date.now()
                            },
                            headers: {
                                'X-WP-Nonce': hmapi_params.nonce
                            }
                        });
                        this.response2 = response;
                    } catch (error) {
                        this.response2 = '<div style="color: red;">Error: ' + error.message + '</div>';
                    }
                    this.loading = false;
                }
            }
        }
    </script>
</body>

</html>

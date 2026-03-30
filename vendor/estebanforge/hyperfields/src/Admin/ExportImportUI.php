<?php

declare(strict_types=1);

namespace HyperFields\Admin;

use HyperFields\ExportImport;
use HyperFields\TemplateLoader;

/**
 * Reusable Export/Import UI component.
 *
 * Generates an admin page that lets users:
 *  1. Export selected option groups to a downloadable JSON file.
 *  2. Upload a JSON file and preview a visual diff of what will change.
 *  3. Confirm the import after reviewing the diff.
 *
 * The simplest integration is a single call to registerPage(), which handles
 * menu registration, asset enqueueing, and page rendering automatically:
 *
 * ```php
 * add_action('admin_menu', function () {
 *     \HyperFields\Admin\ExportImportUI::registerPage(
 *         parentSlug:           'my-plugin',
 *         pageSlug:             'my-plugin-data-tools',
 *         options:              ['my_plugin_options' => 'My Plugin Settings'],
 *         allowedImportOptions: ['my_plugin_options'],
 *         prefix:               'myp_',
 *         title:                'My Plugin – Data Tools',
 *     );
 * });
 * ```
 *
 * For manual control, call render() inside your own submenu page callback and
 * call enqueuePageAssets() from an admin_enqueue_scripts hook for the same page:
 *
 * ```php
 * add_action('admin_enqueue_scripts', function (string $hook) {
 *     if ($hook === 'my-plugin_page_my-plugin-data-tools') {
 *         \HyperFields\Admin\ExportImportUI::enqueuePageAssets();
 *     }
 * });
 *
 * add_action('admin_menu', function () {
 *     add_submenu_page(
 *         'my-plugin',
 *         'Data Tools', 'Data Tools', 'manage_options',
 *         'my-plugin-data-tools',
 *         function () {
 *             echo \HyperFields\Admin\ExportImportUI::render(
 *                 options:              ['my_plugin_options' => 'My Plugin Settings'],
 *                 allowedImportOptions: ['my_plugin_options'],
 *                 prefix:               'myp_',
 *                 title:                'My Plugin – Data Tools',
 *             );
 *         }
 *     );
 * });
 * ```
 */
class ExportImportUI
{
    /**
     * Register a submenu page and wire up all required hooks automatically.
     *
     * This is the recommended single-call API for third-party developers.
     * Call it from inside an `admin_menu` action hook.
     *
     * @param string $parentSlug           Parent menu slug (e.g. 'my-plugin' or 'options-general.php').
     * @param string $pageSlug             Unique slug for this page (e.g. 'my-plugin-data-tools').
     * @param array  $options              Associative map of WP option names to human-readable labels.
     *                                     Example: ['myplugin_options' => 'My Plugin Settings']
     * @param array  $allowedImportOptions Whitelist of option names that may be overwritten on import.
     *                                     Defaults to all keys in $options.
     * @param string $prefix               Optional key prefix applied to both export and import.
     * @param string $title                Page heading and menu label.
     * @param string $capability           Required capability. Default: 'manage_options'.
     * @param string $description          Short description shown below the heading.
     */
    public static function registerPage(
        string $parentSlug,
        string $pageSlug,
        array $options = [],
        array $allowedImportOptions = [],
        string $prefix = '',
        string $title = 'Data Export / Import',
        string $capability = 'manage_options',
        string $description = 'Export your settings to JSON or import a previously exported file.'
    ): void {
        if (empty($allowedImportOptions)) {
            $allowedImportOptions = array_keys($options);
        }

        // Determine the hook suffix WordPress will assign to this page
        $parentBase   = str_replace('.php', '', basename($parentSlug));
        $hookSuffix   = ($parentBase === 'options-general' ? 'settings' : $parentBase) . '_page_' . $pageSlug;

        // Enqueue HyperFields admin CSS + diff assets on this page only
        add_action('admin_enqueue_scripts', static function (string $hook) use ($hookSuffix): void {
            if ($hook === $hookSuffix) {
                self::enqueuePageAssets();
            }
        });

        add_submenu_page(
            $parentSlug,
            $title,
            $title,
            $capability,
            $pageSlug,
            static function () use ($options, $allowedImportOptions, $prefix, $title, $description): void {
                echo self::render(
                    options:              $options,
                    allowedImportOptions: $allowedImportOptions,
                    prefix:               $prefix,
                    title:                $title,
                    description:          $description,
                );
            }
        );
    }

    /**
     * Enqueue all assets required by this page.
     *
     * Called automatically by registerPage(). When using render() manually,
     * call this from your own admin_enqueue_scripts hook for the correct page.
     */
    public static function enqueuePageAssets(): void
    {
        TemplateLoader::enqueueAssets();
        self::enqueueDiffAssets();
    }

    /**
     * Render the complete export/import UI as an HTML string.
     *
     * Must be echo'd inside a WP admin page callback. Assets must be enqueued
     * separately via enqueuePageAssets() — use registerPage() to handle both
     * automatically.
     *
     * @param array  $options              Associative map of WP option names to human-readable labels.
     * @param array  $allowedImportOptions Whitelist of option names permitted to be overwritten on import.
     *                                     Defaults to all keys in $options.
     * @param string $prefix               Optional prefix filter applied to both export and import.
     * @param string $title                Page heading displayed at the top.
     * @param string $description          Short description shown below the heading.
     * @return string HTML ready to be echo'd inside a WP admin page callback.
     */
    public static function render(
        array $options = [],
        array $allowedImportOptions = [],
        string $prefix = '',
        string $title = 'Data Export / Import',
        string $description = 'Export your settings to JSON or import a previously exported file.'
    ): string {
        if (empty($allowedImportOptions)) {
            $allowedImportOptions = array_keys($options);
        }

        // ---------- Handle: Export ----------
        $exportJson  = '';
        $exportError = '';
        if (
            isset($_POST['hf_export_submit'])
            && isset($_POST['hf_export_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hf_export_nonce'])), 'hf_export_action')
        ) {
            $selectedNames = isset($_POST['hf_export_options']) && is_array($_POST['hf_export_options'])
                ? array_map('sanitize_text_field', array_map('strval', wp_unslash($_POST['hf_export_options'])))
                : [];

            $selectedNames = array_values(array_intersect($selectedNames, array_keys($options)));

            if (empty($selectedNames)) {
                $exportError = __('Please select at least one option group to export.', 'hyperfields');
            } else {
                $exportJson = ExportImport::exportOptions($selectedNames, $prefix);
            }
        }

        // ---------- Handle: Preview upload ----------
        $previewTransientKey = '';
        $previewError        = '';
        $currentSnapshot     = [];
        $incomingData        = [];
        if (
            isset($_POST['hf_preview_submit'])
            && isset($_POST['hf_preview_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hf_preview_nonce'])), 'hf_preview_action')
            && isset($_FILES['hf_import_file'])
            && is_array($_FILES['hf_import_file'])
        ) {
            $file          = $_FILES['hf_import_file'];
            $previewResult = self::handlePreview($file, $allowedImportOptions, $prefix, $options);

            if ($previewResult['success']) {
                $previewTransientKey = $previewResult['transient_key'];
                $currentSnapshot     = $previewResult['current'];
                $incomingData        = $previewResult['incoming'];
            } else {
                $previewError = $previewResult['message'];
            }
        }

        // ---------- Handle: Confirm import ----------
        $importMessage = '';
        $importSuccess = false;
        if (
            isset($_POST['hf_confirm_submit'])
            && isset($_POST['hf_confirm_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hf_confirm_nonce'])), 'hf_confirm_action')
        ) {
            $transientKey = isset($_POST['hf_transient_key'])
                ? sanitize_text_field(wp_unslash($_POST['hf_transient_key']))
                : '';

            $storedJson = $transientKey ? get_transient($transientKey) : false;

            if ($storedJson && is_string($storedJson)) {
                $result        = ExportImport::importOptions($storedJson, $allowedImportOptions, $prefix);
                $importSuccess = $result['success'];
                $importMessage = $result['message'];
                delete_transient($transientKey);
            } else {
                $importMessage = __('Import session expired or is invalid. Please upload the file again.', 'hyperfields');
            }
        }

        ob_start();
        self::renderHtml(
            title:               $title,
            description:         $description,
            options:             $options,
            prefix:              $prefix,
            exportJson:          $exportJson,
            exportError:         $exportError,
            previewTransientKey: $previewTransientKey,
            previewError:        $previewError,
            currentSnapshot:     $currentSnapshot,
            incomingData:        $incomingData,
            importMessage:       $importMessage,
            importSuccess:       $importSuccess,
        );

        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Enqueue jsondiffpatch CSS and JS via the WordPress asset pipeline.
     *
     * Always safe to call — wp_enqueue_* deduplicates automatically.
     */
    private static function enqueueDiffAssets(): void
    {
        wp_enqueue_style(
            'jsondiffpatch',
            'https://cdn.jsdelivr.net/npm/jsondiffpatch/lib/formatters/styles/html.min.css',
            [],
            null
        );
        if (function_exists('wp_add_inline_style')) {
            wp_add_inline_style('jsondiffpatch', <<<CSS
#hf-diff-container.hf-diff-codeblock {
    background: #0f172a;
    border: 1px solid #1f2937;
    border-radius: 8px;
    color: #e5e7eb;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 14px;
    line-height: 1.45;
    padding: 16px;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-delta {
    color: #e5e7eb;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-property-name {
    color: #d1d5db;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-added .jsondiffpatch-property-name,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-modified .jsondiffpatch-right-value pre {
    background: #0f3d24 !important;
    background-color: #0f3d24 !important;
    color: #ecfdf5 !important;
    border-radius: 3px;
    font-weight: 600;
    text-shadow: none;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-deleted .jsondiffpatch-property-name,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-modified .jsondiffpatch-left-value pre {
    background: #5f1116 !important;
    background-color: #5f1116 !important;
    color: #fff1f2 !important;
    border-radius: 3px;
    font-weight: 600;
    text-shadow: none;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-added .jsondiffpatch-value pre,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-added pre,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-textdiff-added {
    background: #0f3d24 !important;
    background-color: #0f3d24 !important;
    color: #ecfdf5 !important;
    border-radius: 3px;
    text-shadow: none;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-deleted .jsondiffpatch-value pre,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-deleted pre,
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-textdiff-deleted {
    background: #5f1116 !important;
    background-color: #5f1116 !important;
    color: #fff1f2 !important;
    border-radius: 3px;
    text-shadow: none;
}
#hf-diff-container.hf-diff-codeblock .jsondiffpatch-value pre {
    color: #e5e7eb;
}
textarea.hf-json-codeblock {
    background: #0f172a;
    border: 1px solid #1f2937;
    border-radius: 8px;
    color: #e5e7eb;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 14px;
    line-height: 1.45;
    padding: 16px;
}
textarea.hf-json-codeblock:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
}
CSS);
        }

        // Load ESM entrypoint as a module (WP 6.5+).
        wp_enqueue_script_module(
            'jsondiffpatch',
            'https://cdn.jsdelivr.net/npm/jsondiffpatch/+esm',
            [],
            null
        );
    }

    /**
     * Process an uploaded file for the diff preview.
     *
     * The uploaded JSON is stored in a transient (5-minute TTL) so the confirmation
     * step can retrieve it without re-uploading.
     *
     * @param array  $file                 The $_FILES['hf_import_file'] entry.
     * @param array  $allowedImportOptions Allowed option names.
     * @param string $prefix               Prefix filter.
     * @param array  $options              Full options map (for snapshot scope).
     * @return array{success: bool, message?: string, transient_key?: string, current?: array, incoming?: array}
     */
    private static function handlePreview(
        array $file,
        array $allowedImportOptions,
        string $prefix,
        array $options
    ): array {
        if (
            !isset($file['tmp_name'], $file['error'])
            || $file['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($file['tmp_name'])
        ) {
            return ['success' => false, 'message' => __('No valid file was uploaded.', 'hyperfields')];
        }

        $maxBytes = 2 * 1024 * 1024; // 2 MB
        if (isset($file['size']) && (int) $file['size'] > $maxBytes) {
            return ['success' => false, 'message' => __('The uploaded file exceeds the 2 MB limit.', 'hyperfields')];
        }

        $jsonString = file_get_contents($file['tmp_name']); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
        if ($jsonString === false || $jsonString === '') {
            return ['success' => false, 'message' => __('Could not read the uploaded file.', 'hyperfields')];
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => sprintf(__('Invalid JSON: %s', 'hyperfields'), json_last_error_msg())];
        }

        if (!is_array($decoded) || !isset($decoded['options']) || !is_array($decoded['options'])) {
            return ['success' => false, 'message' => __('The uploaded file does not appear to be a valid HyperFields export.', 'hyperfields')];
        }

        $filteredIncoming = [];
        foreach ($decoded['options'] as $optName => $value) {
            $optName = sanitize_text_field((string) $optName);
            if (!in_array($optName, $allowedImportOptions, true)) {
                continue;
            }
            if (!is_array($value)) {
                continue;
            }
            if ($prefix !== '') {
                $value = array_filter(
                    $value,
                    static fn($k): bool => strpos((string) $k, $prefix) === 0,
                    ARRAY_FILTER_USE_KEY
                );
            }
            $filteredIncoming[$optName] = $value;
        }

        if (empty($filteredIncoming)) {
            return ['success' => false, 'message' => __('No importable options were found in the uploaded file.', 'hyperfields')];
        }

        $currentSnapshot = ExportImport::snapshotOptions(array_keys($filteredIncoming), $prefix);

        $transientKey = 'hf_import_preview_' . md5(wp_generate_uuid4());
        set_transient($transientKey, $jsonString, 5 * MINUTE_IN_SECONDS);

        return [
            'success'       => true,
            'transient_key' => $transientKey,
            'current'       => $currentSnapshot,
            'incoming'      => $filteredIncoming,
        ];
    }

    /**
     * Output the full page HTML using HyperFields admin styles.
     */
    private static function renderHtml(
        string $title,
        string $description,
        array $options,
        string $prefix,
        string $exportJson,
        string $exportError,
        string $previewTransientKey,
        string $previewError,
        array $currentSnapshot,
        array $incomingData,
        string $importMessage,
        bool $importSuccess
    ): void {
        $hasDiff   = $previewTransientKey !== '' && !empty($incomingData);
        $cancelUrl = admin_url('admin.php?page=' . esc_attr(sanitize_text_field(wp_unslash($_GET['page'] ?? ''))));
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap">
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($description); ?></p>

            <?php if ($importMessage): ?>
                <div class="notice notice-<?php echo $importSuccess ? 'success' : 'error'; ?> is-dismissible">
                    <p><?php echo esc_html($importMessage); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$hasDiff): ?>

            <!-- ====== EXPORT SECTION ====== -->
            <h2><?php esc_html_e('Export', 'hyperfields'); ?></h2>
            <p><?php esc_html_e('Select the option groups you want to include in the exported JSON file.', 'hyperfields'); ?></p>

            <?php if ($exportError): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($exportError); ?></p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('hf_export_action', 'hf_export_nonce'); ?>
                <fieldset>
                    <legend class="screen-reader-text"><?php esc_html_e('Option groups', 'hyperfields'); ?></legend>
                    <div class="hyperpress-fields-group">
                        <?php foreach ($options as $optKey => $optLabel): ?>
                            <div class="hyperpress-field-wrapper">
                                <div class="hyperpress-field-row">
                                    <div class="hyperpress-field-label">
                                        <label for="hf_opt_<?php echo esc_attr($optKey); ?>">
                                            <?php echo esc_html($optLabel); ?>
                                        </label>
                                    </div>
                                    <div class="hyperpress-field-input-wrapper">
                                        <label>
                                            <input type="checkbox"
                                                   id="hf_opt_<?php echo esc_attr($optKey); ?>"
                                                   name="hf_export_options[]"
                                                   value="<?php echo esc_attr($optKey); ?>"
                                                   checked>
                                            <span class="description">
                                                <code><?php echo esc_html($optKey); ?></code>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <p class="submit">
                    <button type="submit" name="hf_export_submit" class="button button-primary">
                        <?php esc_html_e('Export to JSON', 'hyperfields'); ?>
                    </button>
                </p>
            </form>

            <?php if ($exportJson): ?>
                <h3><?php esc_html_e('Exported JSON', 'hyperfields'); ?></h3>
                <div class="hyperpress-field-wrapper">
                    <textarea class="large-text code hf-json-codeblock" readonly rows="12"><?php echo esc_textarea($exportJson); ?></textarea>
                </div>
                <p>
                    <a href="data:application/json;charset=utf-8,<?php echo rawurlencode($exportJson); ?>"
                       download="hyperfields-export-<?php echo esc_attr(gmdate('Y-m-d')); ?>.json"
                       class="button">
                        <?php esc_html_e('Download JSON', 'hyperfields'); ?>
                    </a>
                </p>
            <?php endif; ?>

            <hr>

            <!-- ====== IMPORT SECTION ====== -->
            <h2><?php esc_html_e('Import', 'hyperfields'); ?></h2>
            <p><?php esc_html_e('Upload a previously exported JSON file. You will be shown a preview of what will change before confirming.', 'hyperfields'); ?></p>

            <?php if ($previewError): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($previewError); ?></p></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('hf_preview_action', 'hf_preview_nonce'); ?>
                <div class="hyperpress-fields-group">
                    <div class="hyperpress-field-wrapper">
                        <div class="hyperpress-field-row">
                            <div class="hyperpress-field-label">
                                <label for="hf_import_file">
                                    <?php esc_html_e('JSON file', 'hyperfields'); ?>
                                </label>
                            </div>
                            <div class="hyperpress-field-input-wrapper">
                                <input type="file" id="hf_import_file" name="hf_import_file"
                                       accept=".json,application/json" required>
                                <p class="description">
                                    <?php esc_html_e('Maximum file size: 2 MB.', 'hyperfields'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="submit">
                    <button type="submit" name="hf_preview_submit" class="button button-secondary">
                        <?php esc_html_e('Preview Changes', 'hyperfields'); ?>
                    </button>
                </p>
            </form>

            <?php else: // Diff preview ?>

            <!-- ====== DIFF PREVIEW SECTION ====== -->
            <h2><?php esc_html_e('Import Preview', 'hyperfields'); ?></h2>
            <p><?php esc_html_e('Review the changes below. Keys highlighted in green will be added or updated; keys in red will be removed.', 'hyperfields'); ?></p>
            <p><em><?php esc_html_e('Current settings are shown on the left; imported values are on the right.', 'hyperfields'); ?></em></p>

            <div class="hyperpress-field-wrapper">
                <div id="hf-diff-container" class="hyperpress-field-input-wrapper hf-diff-codeblock" style="overflow:auto;max-height:600px;">
                    <p><?php esc_html_e('Loading diff…', 'hyperfields'); ?></p>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field('hf_confirm_action', 'hf_confirm_nonce'); ?>
                <input type="hidden" name="hf_transient_key" value="<?php echo esc_attr($previewTransientKey); ?>">
                <p class="submit">
                    <button type="submit" name="hf_confirm_submit" class="button button-primary">
                        <?php esc_html_e('Confirm Import', 'hyperfields'); ?>
                    </button>
                    <a href="<?php echo esc_url($cancelUrl); ?>"
                       class="button button-secondary">
                        <?php esc_html_e('Cancel', 'hyperfields'); ?>
                    </a>
                </p>
            </form>

            <script>
            (async function () {
                var current   = <?php echo wp_json_encode($currentSnapshot, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
                var incoming  = <?php echo wp_json_encode($incomingData, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
                var container = document.getElementById('hf-diff-container');
                var moduleUrl = 'https://cdn.jsdelivr.net/npm/jsondiffpatch/+esm';
                var formatterUrl = 'https://cdn.jsdelivr.net/npm/jsondiffpatch/formatters/html/+esm';

                if (!container) { return; }

                try {
                    var mod = await import(moduleUrl);
                    var fmt = await import(formatterUrl);
                    var delta = mod.diff(current, incoming);
                    if (!delta) {
                        container.innerHTML = '<p><strong><?php echo esc_js(__('No differences found. The uploaded file matches the current settings.', 'hyperfields')); ?></strong></p>';
                        return;
                    }
                    container.innerHTML = '';
                    var diffHtml = fmt.format(delta, current);
                    container.innerHTML = diffHtml;
                    if (typeof fmt.hideUnchanged === 'function') {
                        fmt.hideUnchanged();
                    }
                } catch (e) {
                    container.innerHTML = '<p><?php echo esc_js(__('Could not load or render diff. Please check the browser console for details.', 'hyperfields')); ?></p>';
                    console.error('jsondiffpatch error', e);
                }
            })();
            </script>

            <?php endif; ?>

        </div><!-- /.wrap -->
        <?php
    }
}

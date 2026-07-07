/**
 * HyperBlocks editor registration.
 *
 * Registers server-defined fluent blocks with the Gutenberg client so they
 * appear in the inserter and parse correctly when present in saved post
 * content.
 *
 * Blocks are dynamic (server-rendered via the render_callback wired in
 * src/WordPress/Bootstrap.php), so edit() and save() are intentional no-ops:
 * the editor relies on the server-rendered markup. This script only makes the
 * blocks known to the client; it adds no interactive editor UI.
 *
 * Block configuration is injected server-side as window.hyperBlocksConfig via
 * wp_add_inline_script() (see Bootstrap::enqueueEditorScript()).
 */
(function () {
    'use strict';

    /**
     * Register every block described in window.hyperBlocksConfig.
     */
    function registerHyperBlocks() {
        if (!window.wp || !window.wp.blocks || !Array.isArray(window.hyperBlocksConfig)) {
            return;
        }

        window.hyperBlocksConfig.forEach(function (entry) {
            if (!entry || typeof entry.name !== 'string') {
                return;
            }

            // Guard against duplicate registration when the script is included
            // more than once in the same editor session.
            if (window.wp.blocks.getBlockType(entry.name)) {
                return;
            }

            window.wp.blocks.registerBlockType(entry.name, {
                title: entry.title || entry.name,
                icon: entry.icon || 'block-default',
                edit: () => null,
                save: () => null
            });
        });
    }

    // Defer until the DOM (and thus the wp.* packages) is ready. wp.domReady is
    // the Gutenberg-preferred hook; fall back to DOMContentLoaded for safety.
    if (window.wp && typeof window.wp.domReady === 'function') {
        window.wp.domReady(registerHyperBlocks);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerHyperBlocks);
    } else {
        registerHyperBlocks();
    }
})();

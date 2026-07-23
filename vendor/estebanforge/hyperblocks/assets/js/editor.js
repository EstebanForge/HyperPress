/**
 * HyperBlocks editor registration.
 *
 * Registers server-defined fluent blocks with the Gutenberg client so they
 * appear in the inserter and parse correctly when present in saved post
 * content, and renders their server-side markup inside the editor canvas.
 *
 * Blocks are dynamic: their HTML is produced server-side by the
 * render_callback wired in src/WordPress/Bootstrap.php. The editor preview is
 * fetched via WordPress' ServerSideRender component, the canonical pattern for
 * dynamic blocks. save() returns null because the block has no static markup
 * of its own (WP stores the block comment and regenerates HTML on the front
 * end through the render_callback).
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
                // Dynamic block: fetch the server-rendered markup for the
                // editor preview via ServerSideRender. Each block instance
                // makes one REST call to render_block_core to fetch its HTML;
                // that is the standard cost of a dynamic block in Gutenberg
                // and matches how Woo, Gravity Forms, and ACF dynamic blocks
                // behave. save() returns null because the block emits no
                // static markup: the stored block comment is re-rendered
                // server-side via the render_callback on the front end.
                edit: function (props) {
                    var el = window.wp.element.createElement;
                    // useBlockProps wires block selection, focus, and the
                    // supports.* features (align, anchor, customClassName) the
                    // editor applies to the block wrapper. Without it those
                    // features silently fail and, under apiVersion 3, the block
                    // breaks entirely. Core's own dynamic blocks wrap their
                    // ServerSideRender output in useBlockProps.
                    var blockProps = window.wp.blockEditor.useBlockProps();
                    return el('div', blockProps, el(window.wp.serverSideRender, {
                        block: props.name,
                        attributes: props.attributes
                    }));
                },
                save: function () {
                    return null;
                }
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

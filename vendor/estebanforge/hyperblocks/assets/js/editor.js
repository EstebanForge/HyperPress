/**
 * HyperBlocks editor registration.
 *
 * Registers server-defined fluent blocks with the Gutenberg client so they
 * appear in the inserter and parse correctly when present in saved post
 * content, and renders their server-side markup inside the editor canvas.
 *
 * Two edit modes, chosen per block from window.hyperBlocksConfig:
 *
 * - Plain blocks (default): ServerSideRender preview, save() returns null.
 *   The block comment regenerates server-side via the render_callback.
 *
 * - Slotted blocks (entry.innerBlocks present): a hybrid canvas. The PHP
 *   template's server-rendered shell is fetched from the core block-renderer
 *   REST endpoint and mounted with its <!--hyperblocks:innerblocks--> sentinel
 *   comment (the marker Renderer emits where <InnerBlocks /> stood) swapped in
 *   place for a live wp.blockEditor InnerBlocks area. save() returns
 *   InnerBlocks.Content so nested block markup is stored between the block
 *   delimiters and handed back to renderBlock() as $content on the front end.
 *
 * Block configuration is injected server-side as window.hyperBlocksConfig via
 * wp_add_inline_script() (see Bootstrap::enqueueEditorScript()).
 */
(function () {
    'use strict';

    /**
     * Mirrors Renderer::INNER_BLOCKS_SENTINEL on the server. Keep in sync.
     */
    var INNER_BLOCKS_SENTINEL = '<!--hyperblocks:innerblocks-->';

    // Slotted blocks whose template lacks the <InnerBlocks /> marker: the
    // editor still mounts a live nested-blocks area, but the front end has no
    // marker to resolve, so nested content would silently never render. Warn
    // once per block while the author can still act on it.
    var warnedNoMarker = {};

    /**
     * Build the edit component for a slotted (inner-blocks) block.
     *
     * The server shell is fetched, then mounted imperatively with the sentinel
     * comment node swapped for a persistent slot element, so the live
     * InnerBlocks area sits exactly where the template placed the marker —
     * inside whatever container divs wrap it. Splitting the HTML around the
     * marker and rendering the halves as sibling elements would let the HTML
     * parser auto-close wrapper tags and kick the slot out of its container.
     *
     * @param {Object} entry Block config from window.hyperBlocksConfig.
     * @return {Function} React edit component.
     */
    function createSlottedEdit(entry) {
        return function (props) {
            var el = window.wp.element.createElement;
            var useState = window.wp.element.useState;
            var useEffect = window.wp.element.useEffect;
            var useRef = window.wp.element.useRef;
            var createPortal = window.wp.element.createPortal;
            var blockEditor = window.wp.blockEditor;

            var state = useState({ html: '', loading: true });
            var preview = state[0];
            var setPreview = state[1];

            var containerRef = useRef(null);
            // The slot element is created once and re-inserted on every shell
            // rebuild, so the React portal target never changes and the live
            // InnerBlocks UI is never unmounted/remounted across previews.
            var slotRef = useRef(null);

            // Stable dependency: attribute identity changes every render; the
            // serialized form only changes when values change.
    var attributesKey = JSON.stringify(props.attributes || {});
            useEffect(function () {
                var cancelled = false;

                // Debounce attribute churn like core ServerSideRender; the
                // first fetch (empty shell) stays immediate.
                var timer = setTimeout(function () {
                    var request = window.wp.apiFetch
                        ? window.wp.apiFetch({
                            path: '/wp/v2/block-renderer/' + encodeURIComponent(props.name) +
                                '?context=edit&attributes=' + encodeURIComponent(attributesKey)
                        })
                        : Promise.reject(new Error('wp.apiFetch unavailable'));

                    request.then(function (response) {
                        if (!cancelled) {
                            setPreview({ html: (response && response.rendered) || '', loading: false });
                        }
                    }).catch(function () {
                        if (!cancelled) {
                            // Keep the last good shell; the slot still mounts.
                            setPreview(function (current) {
                                return { html: current.html, loading: false };
                            });
                        }
                    });
                }, preview.html ? 350 : 0);

                return function () {
                    cancelled = true;
                    clearTimeout(timer);
                };
            }, [attributesKey]);

            var innerBlocksProps = blockEditor.useInnerBlocksProps(
                { className: 'hyperblocks-innerblocks' },
                {
                    allowedBlocks: entry.innerBlocks.allowedBlocks,
                    template: entry.innerBlocks.template,
                    templateLock: entry.innerBlocks.templateLock
                }
            );

            // Mount (or remount) the shell around the persistent slot element.
            useEffect(function () {
                var container = containerRef.current;
                if (!container) {
                    return;
                }
                var doc = container.ownerDocument;

                if (!slotRef.current) {
                    slotRef.current = doc.createElement('div');
                    slotRef.current.setAttribute('data-hyperblocks-slot', '');
                    // display:contents keeps the wrapper out of the layout so
                    // flex/grid children of the template flow unchanged.
                    slotRef.current.style.display = 'contents';
                }

                var html = preview.html;
                var frag = doc.createRange().createContextualFragment(html);

                // Swap the sentinel comment for the persistent slot element.
                var walker = doc.createTreeWalker(frag, 128 /* NodeFilter.SHOW_COMMENT */);
                var node;
                var swapped = false;
                while ((node = walker.nextNode())) {
                    if (node.nodeValue === 'hyperblocks:innerblocks') {
                        node.parentNode.replaceChild(slotRef.current, node);
                        swapped = true;
                        break;
                    }
                }

                // No marker in the template (or fetch failed): append the slot
                // so the block stays editable.
                if (!swapped) {
                    frag.appendChild(slotRef.current);
                }

                // A rendered shell with no marker means the author opted in via
                // ->innerBlocks() but the template never renders the slot; the
                // front end would drop the nested content. Warn once.
                if (!swapped && html && !warnedNoMarker[props.name]) {
                    warnedNoMarker[props.name] = true;
                    if (window.console && window.console.warn) {
                        window.console.warn(
                            '[HyperBlocks] ' + props.name + ': the template has no <InnerBlocks /> marker; nested blocks will not appear on the front end.'
                        );
                    }
                }

                container.innerHTML = '';
                container.appendChild(frag);
            }, [preview.html]);

            // useBlockProps is mandatory under apiVersion 3 (iframed canvas):
            // it wires selection, focus, and the supports.* features. Passing
            // our own ref merges with the block ref Gutenberg attaches.
            var shellProps = blockEditor.useBlockProps({ ref: containerRef });

            return el('div', shellProps,
                slotRef.current
                    ? createPortal(el('div', innerBlocksProps), slotRef.current, 'hyperblocks-slot')
                    : null
            );
        };
    }

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

            var el = window.wp.element.createElement;
            var slotted = entry.innerBlocks && typeof entry.innerBlocks === 'object';

            window.wp.blocks.registerBlockType(entry.name, {
                // Mirrors the server-side api_version (injected per block via
                // window.hyperBlocksConfig). Default 3 guards a stale config.
                apiVersion: entry.apiVersion || 3,
                title: entry.title || entry.name,
                icon: entry.icon || 'block-default',
                edit: slotted
                    ? createSlottedEdit(entry)
                    : function (props) {
                        // useBlockProps wires block selection, focus, and the
                        // supports.* features (align, anchor, customClassName)
                        // the editor applies to the block wrapper. Without it
                        // those features silently fail and, under apiVersion 3,
                        // the block breaks entirely. Core's own dynamic blocks
                        // wrap their ServerSideRender output in useBlockProps.
                        var blockProps = window.wp.blockEditor.useBlockProps();
                        return el('div', blockProps, el(window.wp.serverSideRender, {
                            block: props.name,
                            attributes: props.attributes
                        }));
                    },
                // Slotted blocks store nested markup between their delimiters;
                // empty inner blocks still serialize self-closing, so opting in
                // changes nothing for instances without children. Plain dynamic
                // blocks keep save() = null (no static markup of their own).
                save: slotted
                    ? function () {
                        return el(window.wp.blockEditor.InnerBlocks.Content);
                    }
                    : function () {
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

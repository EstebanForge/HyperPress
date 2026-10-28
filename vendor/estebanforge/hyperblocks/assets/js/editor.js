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
 * Blocks with entry.editorSidebar wrap either mode with a schema-driven
 * inspector: the field definitions injected server-side (entry.fields) render
 * as tabs -> sections -> controls, with conditional visibility, live badges,
 * and a sample-data preview toggle that fetches the render through the
 * hyperblocks render-preview route with a fetch-time sample flag (never an
 * attribute, so nothing sample-derived persists in post content).
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
                { className: 'hb-innerblocks hyperblocks-innerblocks' },
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
                    slotRef.current.setAttribute('data-hb-slot', '');
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
     * Fields whose schema types have no sidebar control in this version.
     * Rendered controls exist only for the types the mapper below knows;
     * consumers can register their own via the wp.hooks filter.
     */
    var UNSUPPORTED_CONTROL_TYPES = {};

    function warnUnsupportedType(type) {
        if (UNSUPPORTED_CONTROL_TYPES[type]) {
            return;
        }
        UNSUPPORTED_CONTROL_TYPES[type] = true;
        if (window.console && window.console.info) {
            window.console.info(
                '[HyperBlocks] no sidebar control for field type "' + type + '"; the field stays server-defined. Custom controls can hook the hyperblocks.sidebarControl filter.'
            );
        }
    }

    function sidebarAvailable() {
        return !!(
            window.wp &&
            window.wp.components &&
            window.wp.blockEditor &&
            window.wp.blockEditor.InspectorControls &&
            window.wp.element &&
            window.wp.element.Fragment
        );
    }

    /**
     * Mirrors HyperFields\ConditionalLogic::evaluate(). Groups are OR'd;
     * conditions inside a group are AND'd; no rules means visible. Unknown
     * operators and malformed rules hide the field (fail closed), matching
     * the PHP default branch.
     */
    function isEmptyValue(value) {
        return value === null || value === undefined || value === '' || value === false ||
            value === 0 || value === '0' ||
            (Array.isArray(value) && value.length === 0);
    }

    function evaluateCondition(current, operator, expected) {
        switch (operator) {
            case '=':
                return current === expected;
            case '!=':
                return current !== expected;
            case '>':
                return current > expected;
            case '<':
                return current < expected;
            case '>=':
                return current >= expected;
            case '<=':
                return current <= expected;
            case 'IN':
            case 'NOT IN': {
                var list = Array.isArray(expected) ? expected : [expected];
                var currentList = Array.isArray(current) ? current : [current];
                var found = currentList.some(function (item) {
                    // PHP array_intersect compares loosely as strings.
                    return list.some(function (candidate) {
                        return String(candidate) === String(item);
                    });
                });
                return operator === 'IN' ? found : !found;
            }
            case 'CONTAINS':
            case 'NOT CONTAINS': {
                var haystack = String(current === null || current === undefined ? '' : current);
                var contains = haystack.indexOf(String(expected)) !== -1;
                return operator === 'CONTAINS' ? contains : !contains;
            }
            case 'EMPTY':
                return isEmptyValue(current);
            case 'NOT EMPTY':
                return !isEmptyValue(current);
            default:
                return false;
        }
    }

    function evaluateLogic(logic, values) {
        if (!logic || typeof logic !== 'object') {
            return true;
        }
        var groups = Array.isArray(logic.groups) && logic.groups.length
            ? logic.groups
            : (Array.isArray(logic.conditions) && logic.conditions.length ? [logic.conditions] : []);
        if (!groups.length) {
            return true;
        }
        return groups.some(function (group) {
            if (!Array.isArray(group)) {
                return false;
            }
            return group.every(function (condition) {
                if (!condition || typeof condition.field !== 'string') {
                    return false;
                }
                return evaluateCondition(values[condition.field], condition.operator, condition.value);
            });
        });
    }

    /**
     * Tab identities key off the label, not the position, so a conditionally
     * hidden tab does not reset the selection of the remaining tabs.
     */
    function buildTabDefs(groups) {
        var seen = {};
        return groups.map(function (group) {
            var slug = String(group.label || 'general')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'general';
            var name = slug;
            var suffix = 2;
            while (seen[name]) {
                name = slug + '-' + suffix;
                suffix += 1;
            }
            seen[name] = true;
            return { name: name, group: group };
        });
    }

    /**
     * Normalize the option shapes HyperFields fields may carry: an object
     * map ({value: label}), a scalar list, or a [{value,label}] list.
     */
    function optionList(options) {
        if (!options) {
            return [];
        }
        if (Array.isArray(options)) {
            return options.map(function (option) {
                if (typeof option === 'string' || typeof option === 'number') {
                    return { value: String(option), label: String(option) };
                }
                if (option && typeof option === 'object') {
                    var value = option.value !== undefined ? option.value : option.key;
                    return { value: String(value === undefined ? '' : value), label: String(option.label !== undefined ? option.label : value) };
                }
                return { value: '', label: '' };
            }).filter(function (option) {
                return option.value !== '' || option.label !== '';
            });
        }
        return Object.keys(options).map(function (key) {
            return { value: key, label: String(options[key]) };
        });
    }

    function groupByTabs(fields) {
        var useTabs = false;
        var i;
        for (i = 0; i < fields.length; i++) {
            if (fields[i].tab) {
                useTabs = true;
                break;
            }
        }
        var tabs = [];
        for (i = 0; i < fields.length; i++) {
            var field = fields[i];
            var tabLabel = useTabs ? (field.tab || 'General') : '';
            var tab = null;
            var j;
            for (j = 0; j < tabs.length; j++) {
                if (tabs[j].label === tabLabel) {
                    tab = tabs[j];
                    break;
                }
            }
            if (!tab) {
                tab = { label: tabLabel, sections: [] };
                tabs.push(tab);
            }
            var sectionLabel = field.section || '';
            var section = null;
            for (j = 0; j < tab.sections.length; j++) {
                if (tab.sections[j].label === sectionLabel) {
                    section = tab.sections[j];
                    break;
                }
            }
            if (!section) {
                section = { label: sectionLabel, fields: [] };
                tab.sections.push(section);
            }
            section.fields.push(field);
        }
        return { useTabs: useTabs, tabs: tabs };
    }

    /**
     * Build the control for one field. Returns null for types without a
     * sidebar control in this version.
     */
    function createControl(field, value, onChange) {
        var el = window.wp.element.createElement;
        var components = window.wp.components;

        var custom = (window.wp.hooks && typeof window.wp.hooks.applyFilters === 'function')
            ? window.wp.hooks.applyFilters('hyperblocks.sidebarControl', null, field, value, onChange)
            : null;
        if (custom) {
            return custom;
        }

        var current = value === undefined || value === null ? (field.default !== undefined ? field.default : value) : value;

        switch (field.type) {
            case 'text':
            case 'email':
            case 'url':
            case 'time':
                return el(components.TextControl, {
                    type: field.type === 'text' ? 'text' : field.type,
                    label: makeLabel(el, field),
                    placeholder: field.placeholder || undefined,
                    help: field.help || undefined,
                    value: typeof current === 'string' || typeof current === 'number' ? String(current) : '',
                    onChange: onChange
                });
            case 'textarea':
                return el(components.TextareaControl, {
                    label: makeLabel(el, field),
                    placeholder: field.placeholder || undefined,
                    help: field.help || undefined,
                    value: typeof current === 'string' ? current : '',
                    onChange: onChange
                });
            case 'number':
                return el(components.__experimentalNumberControl || components.TextControl, {
                    label: makeLabel(el, field),
                    help: field.help || undefined,
                    value: current === undefined || current === null || current === '' ? '' : String(current),
                    onChange: function (next) {
                        if (next === '' || next === null || next === undefined) {
                            onChange(undefined);
                            return;
                        }
                        var parsed = parseFloat(next);
                        onChange(isNaN(parsed) ? undefined : parsed);
                    }
                });
            case 'select':
                return el(components.SelectControl, {
                    label: makeLabel(el, field),
                    help: field.help || undefined,
                    value: current === undefined || current === null ? '' : String(current),
                    options: [{ value: '', label: '—' }].concat(optionList(field.options)),
                    onChange: onChange
                });
            case 'multiselect':
                return el(components.SelectControl, {
                    label: makeLabel(el, field),
                    help: field.help || undefined,
                    multiple: true,
                    value: Array.isArray(current) ? current.map(String) : [],
                    options: optionList(field.options),
                    onChange: function (next) {
                        onChange(Array.isArray(next) ? next : []);
                    }
                });
            case 'checkbox':
                return el(components.ToggleControl, {
                    label: makeLabel(el, field),
                    help: field.help || undefined,
                    checked: current === true || current === 1 || current === '1',
                    onChange: onChange
                });
            case 'radio':
                return el(components.RadioControl, {
                    label: makeLabel(el, field),
                    help: field.help || undefined,
                    selected: current === undefined || current === null ? '' : String(current),
                    options: optionList(field.options),
                    onChange: onChange
                });
            case 'color':
                return el('div', { className: 'hb-color-control hyperblocks-color-control' },
                    el(components.BaseControl, {
                        label: makeLabel(el, field),
                        help: field.help || undefined
                    }, el(components.ColorPalette, {
                        value: typeof current === 'string' ? current : undefined,
                        onChange: function (next) {
                            var color = next && typeof next === 'object' ? next.hex : next;
                            onChange(color || '');
                        }
                    }))
                );
            case 'date':
            case 'datetime':
                return el('div', { className: 'hb-datetime-control hyperblocks-datetime-control' },
                    el(components.DateTimePicker, {
                        currentDate: typeof current === 'string' && current ? current : undefined,
                        onChange: function (next) {
                            var nextValue = next || '';
                            onChange(field.type === 'date' ? nextValue.slice(0, 10) : nextValue);
                        }
                    }),
                    el('span', { className: 'hb-field-label hyperblocks-field-label' }, makeLabel(el, field))
                );
            case 'image':
            case 'file':
                if (!window.wp.blockEditor.MediaUpload || !window.wp.components.Button) {
                    warnUnsupportedType(field.type);
                    return null;
                }
                return el(window.wp.blockEditor.MediaUpload, {
                    onSelect: function (media) {
                        if (!media) {
                            return;
                        }
                        onChange(field.type === 'image' ? (media.id || 0) : (media.url || ''));
                    },
                    allowedTypes: field.type === 'image' ? ['image'] : [],
                    value: field.type === 'image' ? (typeof current === 'number' ? current : 0) : undefined,
                    render: function (open) {
                        var hasValue = field.type === 'image' ? !!current : !!current;
                        return el('div', { className: 'hb-media-control hyperblocks-media-control' },
                            el('span', { className: 'hb-field-label hyperblocks-field-label' }, makeLabel(el, field)),
                            el(window.wp.components.Button, {
                                variant: 'secondary',
                                onClick: open.open
                            }, hasValue ? 'Replace' : 'Select'),
                            hasValue ? el(window.wp.components.Button, {
                                variant: 'link',
                                isDestructive: true,
                                onClick: function () {
                                    onChange(field.type === 'image' ? 0 : '');
                                }
                            }, 'Remove') : null
                        );
                    }
                });
            case 'separator':
                return el('hr', { className: 'hb-sidebar-separator hyperblocks-sidebar-separator' });
            case 'heading':
                return el('div', { className: 'hb-sidebar-heading hyperblocks-sidebar-heading' }, field.label || '');
            default:
                warnUnsupportedType(field.type);
                return null;
        }
    }

    function makeLabel(el, field) {
        var label = field.label || field.name;
        if (!field.live) {
            return label;
        }
        return el('span', { className: 'hb-field-label hyperblocks-field-label' },
            label,
            el('span', {
                className: 'hb-live-badge hyperblocks-live-badge',
                title: 'Changes re-render the block preview.'
            }, 'live')
        );
    }

    /**
     * Set one attribute through the block's setAttributes. Undefined values
     * drop the key so the server default applies again.
     */
    function attributeSetter(props, fieldName) {
        return function (value) {
            var change = {};
            change[fieldName] = value;
            props.setAttributes(change);
        };
    }

    function renderSections(group, props) {
        var el = window.wp.element.createElement;
        var components = window.wp.components;
        var out = [];
        group.sections.forEach(function (section, sectionIndex) {
            var controls = section.fields
                .map(function (field) {
                    var control = createControl(field, props.attributes[field.name], attributeSetter(props, field.name));
                    if (!control) {
                        return null;
                    }
                    return el(window.wp.element.Fragment, { key: field.name }, control);
                })
                .filter(Boolean);
            if (!controls.length) {
                return;
            }
            if (section.label) {
                out.push(el(components.PanelBody, {
                    key: 'section-' + sectionIndex + '-' + section.label,
                    title: section.label,
                    initialOpen: true
                }, controls));
            } else {
                out.push.apply(out, controls);
            }
        });
        return out;
    }

    function renderInspector(entry, props, sample, setSample) {
        var el = window.wp.element.createElement;
        var components = window.wp.components;
        var fields = (entry.fields || []).filter(function (field) {
            return !!(field && field.name) && evaluateLogic(field.conditional_logic, props.attributes);
        });
        var grouped = groupByTabs(fields);
        var children = [];

        if (grouped.useTabs && grouped.tabs.length) {
            var tabDefs = buildTabDefs(grouped.tabs);
            children.push(el(components.TabPanel, {
                className: 'hb-sidebar-tabs hyperblocks-sidebar-tabs',
                tabs: tabDefs.map(function (tabDef) {
                    return { name: tabDef.name, title: tabDef.group.label };
                }),
                children: function (tab) {
                    var tabDef = tabDefs.find(function (candidate) {
                        return candidate.name === tab.name;
                    });
                    var group = tabDef ? tabDef.group : { label: '', sections: [] };
                    return el('div', { className: 'hb-tab-panel hyperblocks-tab-panel' }, renderSections(group, props));
                }
            }));
        } else {
            grouped.tabs.forEach(function (group) {
                children.push.apply(children, renderSections(group, props));
            });
        }

        children.push(el(components.PanelBody, {
            title: 'Preview data',
            initialOpen: false
        }, el(components.ToggleControl, {
            label: 'Show sample data',
            help: 'Preview only. Never saved with the block.',
            checked: sample,
            onChange: setSample
        })));

        return children;
    }

    /**
     * Canvas replacement while sample mode is on: fetches the server render
     * through the hyperblocks render-preview route with the sample flag. The
     * flag is a fetch-time argument, never an attribute, so nothing sample-
     * derived can persist in post content.
     */
    function createSamplePreview(entry) {
        return function (props) {
            var el = window.wp.element.createElement;
            var useState = window.wp.element.useState;
            var useEffect = window.wp.element.useEffect;
            var blockProps = window.wp.blockEditor.useBlockProps();

            var state = useState({ html: '', loading: true });
            var preview = state[0];
            var setPreview = state[1];

            var attributesKey = JSON.stringify(props.attributes || {});
            useEffect(function () {
                var cancelled = false;
                var timer = setTimeout(function () {
                    var request = window.wp.apiFetch
                        ? window.wp.apiFetch({
                            path: '/hyperblocks/v1/render-preview',
                            method: 'POST',
                            data: {
                                blockName: entry.name,
                                attributes: props.attributes || {},
                                sample: true
                            }
                        })
                        : Promise.reject(new Error('wp.apiFetch unavailable'));

                    request.then(function (response) {
                        if (!cancelled) {
                            setPreview({ html: (response && response.html) || '', loading: false });
                        }
                    }).catch(function () {
                        if (!cancelled) {
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

            return el('div', blockProps,
                // Inline styles: under apiVersion 3 the canvas is iframed and
                // editor.css never reaches it.
                el('div', {
                    className: 'hb-sample-preview hyperblocks-sample-preview',
                    style: {
                        outline: '2px dashed #9ca3af',
                        outlineOffset: '-2px',
                        padding: '8px',
                    },
                    dangerouslySetInnerHTML: { __html: preview.html }
                }));
        };
    }

    /**
     * Wrap a canvas edit component with the schema-driven inspector: tabs,
     * sections, controls, conditional visibility, live badges, and the
     * sample-data preview toggle.
     */
    function createSidebarEdit(entry, baseEdit) {
        return function (props) {
            var el = window.wp.element.createElement;
            var useState = window.wp.element.useState;
            var useMemo = window.wp.element.useMemo;

            var sampleState = useState(false);
            var sample = sampleState[0];
            var setSample = sampleState[1];

            // Built once per block entry: creating the component inside the
            // render would change its type each render and remount it,
            // resetting preview state and defeating the debounce.
            var SamplePreview = useMemo(function () {
                return createSamplePreview(entry);
            }, [entry]);

            var inspector = el(window.wp.blockEditor.InspectorControls, {},
                renderInspector(entry, props, sample, setSample));
            var canvas = sample
                ? el(SamplePreview, props)
                : el(baseEdit, props);

            return el(window.wp.element.Fragment, {}, inspector, canvas);
        };
    }

    /**
     * Build the edit component for a plain (non-slotted) block: a
     * ServerSideRender preview inside the block wrapper.
     */
    function createPlainEdit() {
        return function (props) {
            // useBlockProps wires block selection, focus, and the
            // supports.* features (align, anchor, customClassName)
            // the editor applies to the block wrapper. Without it
            // those features silently fail and, under apiVersion 3,
            // the block breaks entirely. Core's own dynamic blocks
            // wrap their ServerSideRender output in useBlockProps.
            var blockProps = window.wp.blockEditor.useBlockProps();
            return window.wp.element.createElement('div', blockProps, window.wp.element.createElement(window.wp.serverSideRender, {
                block: props.name,
                attributes: props.attributes
            }));
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
            var useSidebar = entry.editorSidebar === true &&
                Array.isArray(entry.fields) &&
                entry.fields.length > 0 &&
                sidebarAvailable();
            var baseEdit = slotted ? createSlottedEdit(entry) : createPlainEdit();

            window.wp.blocks.registerBlockType(entry.name, {
                // Mirrors the server-side api_version (injected per block via
                // window.hyperBlocksConfig). Default 3 guards a stale config.
                apiVersion: entry.apiVersion || 3,
                title: entry.title || entry.name,
                icon: entry.icon || 'block-default',
                edit: useSidebar
                    ? createSidebarEdit(entry, baseEdit)
                    : baseEdit,
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

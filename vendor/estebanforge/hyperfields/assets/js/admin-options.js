(function () {
    'use strict';

    function normalizeText(value) {
        return String(value || '').toLowerCase().trim();
    }

    function updateFilterCount(countElement, visibleCount, totalCount) {
        if (!countElement) {
            return;
        }
        countElement.textContent = 'Showing ' + String(visibleCount) + ' of ' + String(totalCount);
    }

    function initFilter(container) {
        var input = container.querySelector('[data-hf-export-filter]');
        if (!input) {
            return;
        }

        var clearButton = container.querySelector('[data-hf-export-filter-clear]');
        var countElement = container.querySelector('[data-hf-export-filter-count]');
        var rows = Array.prototype.slice.call(
            container.querySelectorAll('.hf-export-options-table tbody tr')
        ).map(function (row) {
            return {
                row: row,
                text: normalizeText(row.textContent),
            };
        });

        var totalRows = rows.length;
        updateFilterCount(countElement, totalRows, totalRows);

        function applyFilter() {
            var term = normalizeText(input.value);
            var visibleCount = 0;

            rows.forEach(function (item) {
                var isVisible = term === '' || item.text.indexOf(term) !== -1;
                item.row.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            updateFilterCount(countElement, visibleCount, totalRows);
        }

        input.addEventListener('input', applyFilter);

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                input.value = '';
                applyFilter();
                input.focus();
            });
        }
    }

    function getCheckboxes(container) {
        return Array.prototype.slice.call(
            container.querySelectorAll('input[type="checkbox"][name="hf_export_options[]"]')
        );
    }

    function setChecked(container, checked) {
        var checkboxes = getCheckboxes(container);
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    function invertChecked(container) {
        var checkboxes = getCheckboxes(container);
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = !checkbox.checked;
        });
    }

    function getRowsByGroup(container) {
        var map = {};
        var rows = Array.prototype.slice.call(
            container.querySelectorAll('.hf-export-options-table tbody tr[data-hf-export-group]')
        );

        rows.forEach(function (row) {
            var group = String(row.getAttribute('data-hf-export-group') || '');
            if (group === '') {
                return;
            }

            var checkbox = row.querySelector('input[type="checkbox"][name="hf_export_options[]"]');
            if (!checkbox) {
                return;
            }

            if (!map[group]) {
                map[group] = [];
            }
            map[group].push(checkbox);
        });

        return map;
    }

    function updateGroupSummary(container) {
        var summaryLabel = container.querySelector('[data-hf-export-group-summary-label]');
        if (!summaryLabel) {
            return;
        }

        var selectedGroups = Array.prototype.slice.call(
            container.querySelectorAll('input[type="checkbox"][data-hf-export-group-toggle]:checked')
        ).map(function (input) {
            return String(input.value || '').trim();
        }).filter(function (value) {
            return value !== '';
        });

        if (selectedGroups.length === 0) {
            summaryLabel.textContent = 'Select option groups';
            return;
        }

        if (selectedGroups.length <= 2) {
            summaryLabel.textContent = selectedGroups.join(', ');
            return;
        }

        summaryLabel.textContent = String(selectedGroups.length) + ' groups selected';
    }

    function syncGroupTogglesFromRows(container) {
        var rowsByGroup = getRowsByGroup(container);
        var groupToggles = Array.prototype.slice.call(
            container.querySelectorAll('input[type="checkbox"][data-hf-export-group-toggle]')
        );

        groupToggles.forEach(function (toggle) {
            var group = String(toggle.value || '');
            var rowCheckboxes = rowsByGroup[group] || [];
            if (rowCheckboxes.length === 0) {
                toggle.checked = false;
                toggle.indeterminate = false;
                return;
            }

            var checkedCount = rowCheckboxes.filter(function (rowCheckbox) {
                return rowCheckbox.checked;
            }).length;

            toggle.checked = checkedCount === rowCheckboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        });

        updateGroupSummary(container);
    }

    function initGroupSelector(container) {
        var selector = container.querySelector('[data-hf-export-group-selector]');
        if (!selector) {
            return;
        }
        var summaryButton = selector.querySelector('[data-hf-export-group-summary]');
        var panel = selector.querySelector('[data-hf-export-group-panel]');

        var rowsByGroup = getRowsByGroup(container);
        var groupToggles = Array.prototype.slice.call(
            container.querySelectorAll('input[type="checkbox"][data-hf-export-group-toggle]')
        );

        function setOpen(isOpen) {
            selector.classList.toggle('is-open', isOpen);
            if (panel) {
                panel.hidden = !isOpen;
            }
            if (summaryButton) {
                summaryButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        }

        if (summaryButton) {
            summaryButton.addEventListener('click', function () {
                var isOpen = selector.classList.contains('is-open');
                setOpen(!isOpen);
            });

            document.addEventListener('click', function (event) {
                if (selector.contains(event.target)) {
                    return;
                }
                setOpen(false);
            });
        }

        groupToggles.forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var group = String(toggle.value || '');
                var rowCheckboxes = rowsByGroup[group] || [];

                rowCheckboxes.forEach(function (rowCheckbox) {
                    rowCheckbox.checked = toggle.checked;
                });

                syncGroupTogglesFromRows(container);
            });
        });

        container.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || target.getAttribute('name') !== 'hf_export_options[]') {
                return;
            }
            syncGroupTogglesFromRows(container);
        });

        container.__hfSyncGroupSelector = function () {
            syncGroupTogglesFromRows(container);
        };

        setOpen(false);
        syncGroupTogglesFromRows(container);
    }

    function hasCheckedExportOptions(form) {
        var checkboxes = Array.prototype.slice.call(
            form.querySelectorAll('input[type="checkbox"][name="hf_export_options[]"]')
        );
        return checkboxes.some(function (checkbox) {
            return checkbox.checked;
        });
    }

    function removeExportValidationNotice(form) {
        var existing = form.querySelector('.hf-export-validation-error');
        if (existing) {
            existing.remove();
        }
    }

    function showExportValidationNotice(form, message) {
        removeExportValidationNotice(form);

        var notice = document.createElement('div');
        notice.className = 'notice notice-error hf-export-validation-error';
        notice.innerHTML = '<p>' + String(message || '') + '</p>';

        var target = form.querySelector('.hf-export-options');
        if (target && target.parentNode) {
            target.parentNode.insertBefore(notice, target);
            return;
        }

        form.insertBefore(notice, form.firstChild);
    }

    function copyTextToClipboard(text) {
        return navigator.clipboard.writeText(text);
    }

    function setCopyButtonState(button, state) {
        var icon = button.querySelector('.dashicons');
        if (!icon) {
            return;
        }

        button.classList.remove('is-copied');
        icon.classList.remove('dashicons-yes-alt', 'dashicons-warning');
        icon.classList.add('dashicons-admin-page');

        if (state === 'copied') {
            button.classList.add('is-copied');
            icon.classList.remove('dashicons-admin-page');
            icon.classList.add('dashicons-yes-alt');
            button.setAttribute('title', 'Copied');
            button.setAttribute('aria-label', 'Copied');
            return;
        }

        if (state === 'error') {
            icon.classList.remove('dashicons-admin-page');
            icon.classList.add('dashicons-warning');
            button.setAttribute('title', 'Copy failed');
            button.setAttribute('aria-label', 'Copy failed');
            return;
        }

        button.setAttribute('title', 'Copy JSON to clipboard');
        button.setAttribute('aria-label', 'Copy JSON to clipboard');
    }

    function initJsonCopyButtons() {
        var copyButtons = Array.prototype.slice.call(document.querySelectorAll('[data-hf-json-copy]'));
        copyButtons.forEach(function (button) {
            var wrap = button.closest('.hf-json-copy-wrap');
            if (!wrap) {
                return;
            }

            var textarea = wrap.querySelector('textarea.hf-json-codeblock');
            if (!textarea) {
                return;
            }

            button.addEventListener('click', function () {
                var jsonText = textarea.value || '';
                copyTextToClipboard(jsonText).then(function () {
                    setCopyButtonState(button, 'copied');
                    window.setTimeout(function () {
                        setCopyButtonState(button, 'idle');
                    }, 1400);
                }).catch(function () {
                    setCopyButtonState(button, 'error');
                    window.setTimeout(function () {
                        setCopyButtonState(button, 'idle');
                    }, 1800);
                });
            });
        });
    }

    function initExportModeControls() {
        var fullGate = document.getElementById('hf-full-export-gate');
        var developerGate = document.getElementById('hf-developer-export-gate');
        var fullRadio = document.querySelector('input[name="hf_export_mode"][value="full"]');
        var developerRadio = document.querySelector('input[name="hf_export_mode"][value="developer"]');
        var templateRadio = document.querySelector('input[name="hf_export_mode"][value="template"]');
        var sensitiveRadios = Array.prototype.slice.call(
            document.querySelectorAll('input[data-hf-sensitive-export-toggle]')
        );

        if (!fullGate || !fullRadio || !developerRadio || !templateRadio || sensitiveRadios.length === 0) {
            return;
        }

        var fullConfirmCheckbox = fullGate.querySelector('input[name="hf_full_export_confirm"]');
        var developerConfirmCheckbox = developerGate
            ? developerGate.querySelector('input[name="hf_developer_export_confirm"]')
            : null;

        var exportForm = fullGate.closest('form');
        var exportButton = exportForm
            ? exportForm.querySelector('button[name="hf_export_submit"]')
            : null;

        if (!fullConfirmCheckbox || !exportButton) {
            return;
        }

        function resetSensitiveConfirmations() {
            fullConfirmCheckbox.checked = false;
            if (developerConfirmCheckbox) {
                developerConfirmCheckbox.checked = false;
            }
        }

        function updateExportButtonState() {
            var templateSelected = !!templateRadio.checked;
            var fullSelected = !!fullRadio.checked;
            var developerSelected = !!developerRadio.checked;

            if (templateSelected) {
                exportButton.disabled = false;
                exportButton.classList.remove('disabled');
                return;
            }

            var fullConfirmed = !!fullConfirmCheckbox.checked;
            var developerConfirmed = developerSelected
                ? !!(developerConfirmCheckbox && developerConfirmCheckbox.checked)
                : true;
            var enabled = (fullSelected || developerSelected) && fullConfirmed && developerConfirmed;

            exportButton.disabled = !enabled;
            exportButton.classList.toggle('disabled', !enabled);
        }

        function updateGateState() {
            var fullSelected = !!fullRadio.checked;
            var developerSelected = !!developerRadio.checked;

            fullGate.style.display = (fullSelected || developerSelected) ? 'block' : 'none';
            if (developerGate) {
                developerGate.style.display = developerSelected ? 'block' : 'none';
            }

            updateExportButtonState();
        }

        sensitiveRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!radio.checked) {
                    return;
                }
                resetSensitiveConfirmations();
                updateGateState();
            });
        });

        templateRadio.addEventListener('change', function () {
            if (!templateRadio.checked) {
                return;
            }
            updateGateState();
        });

        fullConfirmCheckbox.addEventListener('change', updateExportButtonState);
        if (developerConfirmCheckbox) {
            developerConfirmCheckbox.addEventListener('change', updateExportButtonState);
        }

        if (fullRadio.checked || developerRadio.checked) {
            resetSensitiveConfirmations();
        }
        updateGateState();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-hf-export-toggle]');
        if (!button) {
            return;
        }

        var fieldset = button.closest('.hf-export-options');
        if (!fieldset) {
            return;
        }

        var action = button.getAttribute('data-hf-export-toggle');
        if (action === 'all') {
            setChecked(fieldset, true);
            if (typeof fieldset.__hfSyncGroupSelector === 'function') {
                fieldset.__hfSyncGroupSelector();
            }
            return;
        }

        if (action === 'none') {
            setChecked(fieldset, false);
            if (typeof fieldset.__hfSyncGroupSelector === 'function') {
                fieldset.__hfSyncGroupSelector();
            }
            return;
        }

        if (action === 'invert') {
            invertChecked(fieldset);
            if (typeof fieldset.__hfSyncGroupSelector === 'function') {
                fieldset.__hfSyncGroupSelector();
            }
        }
    });

    document.addEventListener('click', function (event) {
        var row = event.target.closest('.hf-export-options-table tbody tr');
        if (!row) { return; }

        var checkbox = row.querySelector('input[type="checkbox"][name="hf_export_options[]"]');
        if (!checkbox) { return; }

        // Let native checkbox clicks pass through untouched
        if (event.target === checkbox) { return; }

        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // -------------------------------------------------------------------------
    // Diff viewer helpers — used by the inline <script> in the diff preview page.
    // Exposed on window so the inline script (which runs in page scope) can call them.
    // -------------------------------------------------------------------------

    window.setCopyButtonState = setCopyButtonState;

    window.hfDiffLoadScript = function (src, id, container, cb) {
        if (document.getElementById(id)) { cb(); return; }
        var s    = document.createElement('script');
        s.id     = id;
        s.src    = src;
        s.onload = cb;
        s.onerror = function () {
            container.innerHTML = '<p style="padding:16px;">Could not load diff library. Please check your network connection.</p>';
            console.error('hf-diff: failed to load ' + src);
        };
        document.head.appendChild(s);
    };

    window.hfDiffLoadCss = function (href, id) {
        if (document.getElementById(id)) { return; }
        var l  = document.createElement('link');
        l.id   = id;
        l.rel  = 'stylesheet';
        l.href = href;
        document.head.appendChild(l);
    };

    window.hfJsonViewerInit = function (rawEl, viewerEl) {
        function render() {
            try {
                var data = JSON.parse(rawEl.value);
                viewerEl.innerHTML = '';
                new JsonViewer({
                    value:               data,
                    theme:               'dark',
                    defaultInspectDepth: 2,
                    enableClipboard:     false,
                }).render(viewerEl);
            } catch (e) {
                viewerEl.innerHTML = '<pre style="color:#e5e7eb;margin:0;">' + rawEl.value.replace(/</g, '&lt;') + '</pre>';
                console.error('hf-json-viewer error', e);
            }
        }

        if (typeof JsonViewer !== 'undefined') {
            render();
        } else {
            var s     = document.createElement('script');
            s.src     = 'https://cdn.jsdelivr.net/npm/@textea/json-viewer@3';
            s.onload  = render;
            s.onerror = function () {
                viewerEl.innerHTML = '<pre style="color:#e5e7eb;margin:0;">' + rawEl.value.replace(/</g, '&lt;') + '</pre>';
            };
            document.head.appendChild(s);
        }
    };

    // -------------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function () {
        initJsonCopyButtons();
        initExportModeControls();

        var importConfirm = document.getElementById('hf_import_confirm_destructive');
        var confirmBtn    = document.getElementById('hf_confirm_submit_btn');
        if (importConfirm && confirmBtn) {
            importConfirm.addEventListener('change', function () {
                confirmBtn.disabled = !importConfirm.checked;
            });
        }

        var exportGroups = Array.prototype.slice.call(document.querySelectorAll('.hf-export-options'));
        exportGroups.forEach(function (container) {
            initFilter(container);
            initGroupSelector(container);
        });

        var exportButton = document.querySelector('[name="hf_export_submit"]');
        if (exportButton) {
            exportButton.closest('form').addEventListener('submit', function (event) {
                var form = exportButton.closest('form');
                if (!form) {
                    return;
                }

                removeExportValidationNotice(form);

                var developerModeSelected = !!form.querySelector(
                    'input[name="hf_export_mode"][value="developer"]:checked'
                );

                if (developerModeSelected && !hasCheckedExportOptions(form)) {
                    var firstExportOption = form.querySelector(
                        'input[type="checkbox"][name="hf_export_options[]"]'
                    );
                    if (firstExportOption) {
                        firstExportOption.checked = true;
                    }
                }

                if (!developerModeSelected && !hasCheckedExportOptions(form)) {
                    showExportValidationNotice(
                        form,
                        'Please select at least one option group before exporting.'
                    );
                    var filterInput = form.querySelector('[data-hf-export-filter]');
                    if (filterInput) {
                        filterInput.focus();
                    }
                    event.preventDefault();
                    return;
                }

                var spinner = exportButton.closest('.submit').querySelector('.spinner');
                // Defer disabling until after the browser has serialized the form,
                // so the button name/value is included in the POST payload.
                setTimeout(function () {
                    exportButton.disabled = true;
                    if (spinner) {
                        spinner.classList.add('is-active');
                    }
                }, 0);
            });
        }
    });
})();

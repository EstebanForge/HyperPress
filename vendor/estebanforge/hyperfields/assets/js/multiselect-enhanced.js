/**
 * Enhanced Multiselect for HyperFields
 * Provides search functionality and better UX for multiselect fields
 */

(function($) {
    'use strict';

    // Initialize enhanced multiselect
    function initEnhancedMultiselect() {
        $('.hf-multiselect-container:not(.hf-initialized)').each(function() {
            var $container = $(this);
            $container.addClass('hf-initialized');

            var $search = $container.find('.hf-multiselect-search');
            var $selectedList = $container.find('.hf-multiselect-selected');
            var $optionsList = $container.find('.hf-multiselect-options');
            var $options = $optionsList.find('.hf-multiselect-option');
            var fieldName = $container.data('field-name');

            // Find the hidden select element (either by class or by name as fallback)
            var $originalSelect = $container.siblings('select.hf-multiselect-hidden').first();
            if ($originalSelect.length === 0) {
                $originalSelect = $('select[name="' + fieldName + '"]');
            }

            // Search functionality
            $search.on('input', function() {
                var searchText = $(this).val().toLowerCase();
                $optionsList.find('.hf-multiselect-option').each(function() {
                    var $option = $(this);
                    var text = $option.text().toLowerCase();
                    if (text.indexOf(searchText) !== -1) {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });
            });

            // Find option by value
            function $optionFilter(value) {
                return $optionsList.find('.hf-multiselect-option').filter(function() {
                    return $(this).data('value') === value;
                });
            }

            // Option click handler - using event delegation for dynamic elements
            $optionsList.on('click', '.hf-multiselect-option', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Prevent double-click by checking if already being processed
                if ($(this).data('processing')) {
                    return;
                }

                var $option = $(this);
                var value = $option.data('value');
                var label = $option.text().trim();

                // Set processing flag
                $option.data('processing', true);

                // Toggle selection
                if ($option.hasClass('selected')) {
                    // Deselect
                    $option.removeClass('selected');
                    removeTag(value);
                    updateOriginalSelect(value, false);
                } else {
                    // Select
                    $option.addClass('selected');
                    addTag(label, value);
                    updateOriginalSelect(value, true);
                }

                // Clear processing flag after a short delay
                setTimeout(function() {
                    $option.removeData('processing');
                }, 100);
            });

            // Keyboard navigation
            var $focusedOption = null;

            $search.on('keydown', function(e) {
                var $visibleOptions = $optionsList.find('.hf-multiselect-option:visible');

                if ($visibleOptions.length === 0) {
                    return;
                }

                // Arrow down
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if ($focusedOption === null) {
                        $focusedOption = $visibleOptions.first();
                    } else {
                        $focusedOption.removeClass('hf-multiselect-option-focused');
                        var currentIndex = $visibleOptions.index($focusedOption);
                        if (currentIndex < $visibleOptions.length - 1) {
                            $focusedOption = $visibleOptions.eq(currentIndex + 1);
                        } else {
                            $focusedOption = $visibleOptions.first();
                        }
                    }
                    $focusedOption.addClass('hf-multiselect-option-focused');
                    scrollToOption($focusedOption);
                }

                // Arrow up
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if ($focusedOption === null) {
                        $focusedOption = $visibleOptions.first();
                    } else {
                        $focusedOption.removeClass('hf-multiselect-option-focused');
                        var currentIndex = $visibleOptions.index($focusedOption);
                        if (currentIndex > 0) {
                            $focusedOption = $visibleOptions.eq(currentIndex - 1);
                        } else {
                            $focusedOption = $visibleOptions.last();
                        }
                    }
                    $focusedOption.addClass('hf-multiselect-option-focused');
                    scrollToOption($focusedOption);
                }

                // Enter to select
                if (e.key === 'Enter' && $focusedOption !== null) {
                    e.preventDefault();
                    $focusedOption.trigger('click');
                    $focusedOption.removeClass('hf-multiselect-option-focused');
                    $focusedOption = null;
                }

                // Escape to close
                if (e.key === 'Escape') {
                    $optionsList.hide();
                    if ($focusedOption) {
                        $focusedOption.removeClass('hf-multiselect-option-focused');
                        $focusedOption = null;
                    }
                }
            });

            // Scroll option into view
            function scrollToOption($option) {
                var optionTop = $option.position().top;
                var optionHeight = $option.outerHeight();
                var listHeight = $optionsList.height();
                var scrollTop = $optionsList.scrollTop();

                if (optionTop < 0) {
                    $optionsList.scrollTop(scrollTop + optionTop);
                } else if (optionTop + optionHeight > listHeight) {
                    $optionsList.scrollTop(scrollTop + optionTop + optionHeight - listHeight);
                }
            }

            // Add tag to selected list
            function addTag(label, value) {
                // Remove placeholder if exists
                $selectedList.find('.hf-multiselect-placeholder').remove();

                // Check if tag already exists
                if ($selectedList.find('.hf-multiselect-tag[data-value="' + value + '"]').length === 0) {
                    var $tag = $('<span class="hf-multiselect-tag" data-value="' + value + '"></span>');
                    var $remove = $('<span class="hf-multiselect-remove">&times;</span>');

                    $tag.append(label);
                    $tag.append($remove);
                    $selectedList.append($tag);
                }
            }

            // Remove tag click handler - using event delegation
            $selectedList.on('click', '.hf-multiselect-remove', function(e) {
                e.stopPropagation();
                var val = $(this).closest('.hf-multiselect-tag').data('value');
                $optionFilter(val).removeClass('selected');
                $(this).closest('.hf-multiselect-tag').remove();
                updateOriginalSelect(val, false);
                checkPlaceholder();
            });

            // Remove tag from selected list
            function removeTag(value) {
                $selectedList.find('.hf-multiselect-tag[data-value="' + value + '"]').remove();
                checkPlaceholder();
            }

            // Check if placeholder should be shown
            function checkPlaceholder() {
                if ($selectedList.find('.hf-multiselect-tag').length === 0) {
                    if ($selectedList.find('.hf-multiselect-placeholder').length === 0) {
                        $selectedList.append('<span class="hf-multiselect-placeholder">No items selected</span>');
                    }
                }
            }

            // Update original select element
            function updateOriginalSelect(value, isSelected) {
                if ($originalSelect.length) {
                    $originalSelect.find('option[value="' + value + '"]').prop('selected', isSelected);
                    $originalSelect.trigger('change');
                }
            }

            // Show/hide options dropdown
            $search.on('focus', function() {
                $optionsList.show();
            });

            $selectedList.on('click', function() {
                $optionsList.show();
            });

            // Click outside to close
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.hf-multiselect-container').length) {
                    $optionsList.hide();
                }
            });

            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $optionsList.hide();
                }
            });
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Wait for other scripts to load
        setTimeout(function() {
            initEnhancedMultiselect();
        }, 100);
    });

    // Re-initialize after AJAX calls
    $(document).ajaxComplete(function() {
        setTimeout(function() {
            // Only reinitialize if new containers were added
            var $containers = $('.hf-multiselect-container:not(.hf-initialized)');
            if ($containers.length > 0) {
                initEnhancedMultiselect();
            }
        }, 100);
    });

})(jQuery);

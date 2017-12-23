/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var $html                   = $('html'),
        $document               = $(document),
        $top_level_elements,
        focusableElementsString = "a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]",
        previouslyFocused,
        modals                  = {
            _current: null,
            // Accessibility: Checks focus events to ensure they stay inside the modal.
            forceFocus: function (event) {
                if (PUM_Admin.modals._current && !PUM_Admin.modals._current.contains(event.target)) {
                    event.stopPropagation();
                    PUM_Admin.modals._current.focus();
                }
            },
            trapEscapeKey: function (e) {
                if (e.keyCode === 27) {
                    PUM_Admin.modals.closeAll();
                    e.preventDefault();
                }
            },
            trapTabKey: function (e) {
                // if tab or shift-tab pressed
                if (e.keyCode === 9) {
                    // get list of focusable items
                    var focusableItems         = PUM_Admin.modals._current.find('*').filter(focusableElementsString).filter(':visible'),
                        // get currently focused item
                        focusedItem            = $(':focus'),
                        // get the number of focusable items
                        numberOfFocusableItems = focusableItems.length,
                        // get the index of the currently focused item
                        focusedItemIndex       = focusableItems.index(focusedItem);

                    if (e.shiftKey) {
                        //back tab
                        // if focused on first item and user preses back-tab, go to the last focusable item
                        if (focusedItemIndex === 0) {
                            focusableItems.get(numberOfFocusableItems - 1).focus();
                            e.preventDefault();
                        }
                    } else {
                        //forward tab
                        // if focused on the last item and user preses tab, go to the first focusable item
                        if (focusedItemIndex === numberOfFocusableItems - 1) {
                            focusableItems.get(0).focus();
                            e.preventDefault();
                        }
                    }
                }
            },
            setFocusToFirstItem: function () {
                // set focus to first focusable item
                PUM_Admin.modals._current.find('.pum-modal-content *').filter(focusableElementsString).filter(':visible').first().focus();
            },
            closeAll: function (callback) {
                $('.pum-modal-background')
                    .off('keydown.pum_modal')
                    .hide(0, function () {
                        $('html').css({overflow: 'visible', width: 'auto'});

                        if ($top_level_elements) {
                            $top_level_elements.attr('aria-hidden', 'false');
                            $top_level_elements = null;
                        }

                        // Accessibility: Focus back on the previously focused element.
                        if (previouslyFocused.length) {
                            previouslyFocused.focus();
                        }

                        // Accessibility: Clears the PUM_Admin.modals._current var.
                        PUM_Admin.modals._current = null;

                        // Accessibility: Removes the force focus check.
                        $document.off('focus.pum_modal');
                        if (undefined !== callback) {
                            callback();
                        }
                    })
                    .attr('aria-hidden', 'true');

            },
            show: function (modal, callback) {
                $('.pum-modal-background')
                    .off('keydown.pum_modal')
                    .hide(0)
                    .attr('aria-hidden', 'true');

                $html
                    .data('origwidth', $html.innerWidth())
                    .css({overflow: 'hidden', 'width': $html.innerWidth()});

                // Accessibility: Sets the previous focus element.

                var $focused = $(':focus');
                if (!$focused.parents('.pum-modal-wrap').length) {
                    previouslyFocused = $focused;
                }

                // Accessibility: Sets the current modal for focus checks.
                PUM_Admin.modals._current = $(modal);

                // Accessibility: Close on esc press.
                PUM_Admin.modals._current
                    .on('keydown.pum_modal', function (e) {
                        PUM_Admin.modals.trapEscapeKey(e);
                        PUM_Admin.modals.trapTabKey(e);
                    })
                    .show(0, function () {
                        $top_level_elements = $('body > *').filter(':visible').not(PUM_Admin.modals._current);
                        $top_level_elements.attr('aria-hidden', 'true');

                        PUM_Admin.modals._current
                            .trigger('pum_init')
                            // Accessibility: Add focus check that prevents tabbing outside of modal.
                            .on('focus.pum_modal', PUM_Admin.modals.forceFocus);

                        // Accessibility: Focus on the modal.
                        PUM_Admin.modals.setFocusToFirstItem();

                        if (undefined !== callback) {
                            callback();
                        }
                    })
                    .attr('aria-hidden', 'false');

            },
            remove: function (modal) {
                $(modal).remove();
            },
            replace: function (modal, replacement) {
                PUM_Admin.modals.remove($.trim(modal));
                $('body').append($.trim(replacement));
            },
            reload: function (modal, replacement, callback) {
                PUM_Admin.modals.replace(modal, replacement);
                PUM_Admin.modals.show(modal, callback);
                $(modal).trigger('pum_init');
            }
        };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.modals = modals;

    $(document).on('click', '.pum-modal-background, .pum-modal-wrap .cancel, .pum-modal-wrap .pum-modal-close', function (e) {
        var $target = $(e.target);
        if (/*$target.hasClass('pum-modal-background') || */$target.hasClass('cancel') || $target.hasClass('pum-modal-close') || $target.hasClass('submitdelete')) {
            PUM_Admin.modals.closeAll();
            e.preventDefault();
            e.stopPropagation();
        }
    });

}(jQuery));
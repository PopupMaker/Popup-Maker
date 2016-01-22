var PUMModals;
(function ($, document, undefined) {
    "use strict";
    var $html = $('html'),
        $document = $(document),
        $top_level_elements,
        focusableElementsString = "a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]",
        previouslyFocused,
        currentModal;

    PUMModals = {
        // Accessibility: Checks focus events to ensure they stay inside the modal.
        forceFocus: function (event) {
            if (currentModal && !currentModal.contains(event.target)) {
                event.stopPropagation();
                currentModal.focus();
            }
        },
        trapEscapeKey: function (e) {
            if (e.keyCode === 27) {
                PUMModals.closeAll();
                e.preventDefault();
            }
        },
        trapTabKey: function (e) {
            // if tab or shift-tab pressed
            if (e.keyCode === 9) {
                // get list of focusable items
                var focusableItems = currentModal.find('*').filter(focusableElementsString).filter(':visible'),
                // get currently focused item
                    focusedItem = $(':focus'),
                // get the number of focusable items
                    numberOfFocusableItems = focusableItems.length,
                // get the index of the currently focused item
                    focusedItemIndex = focusableItems.index(focusedItem);

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
            currentModal.find('.pum-modal-content *').filter(focusableElementsString).filter(':visible').first().focus();
        },
        closeAll: function () {
            $('.pum-modal-background')
                .off('keydown.pum_modal')
                .hide()
                .attr('aria-hidden', 'true');

            $('html').css({overflow: 'visible', width: 'auto'});

            if ($top_level_elements) {
                $top_level_elements.attr('aria-hidden', 'false');
                $top_level_elements = null;
            }

            // Accessibility: Focus back on the previously focused element.
            if (previouslyFocused.length) {
                previouslyFocused.focus();
            }

            // Accessibility: Clears the currentModal var.
            currentModal = null;

            // Accessibility: Removes the force focus check.
            $document.off('focus.pum_modal');
        },
        show: function (modal) {
            $('.pum-modal-background')
                .off('keydown.pum_modal')
                .hide()
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
            currentModal = $(modal)
            // Accessibility: Close on esc press.
                .on('keydown.pum_modal', function (e) {
                    PUMModals.trapEscapeKey(e);
                    PUMModals.trapTabKey(e);
                })
                .show()
                .attr('aria-hidden', 'false');

            $top_level_elements = $('body > *').filter(':visible').not(currentModal);
            $top_level_elements.attr('aria-hidden', 'true');

            $document
                .trigger('pum_init')

                // Accessibility: Add focus check that prevents tabbing outside of modal.
                .on('focus.pum_modal', PUMModals.forceFocus);

            // Accessibility: Focus on the modal.
            PUMModals.setFocusToFirstItem();
        },
        remove: function (modal) {
            $(modal).remove();
        },
        replace: function (modal, replacement) {
            PUMModals.remove(modal);
            $('body').append(replacement);
        },
        reload: function (modal, replacement) {
            PUMModals.replace(modal, replacement);
            PUMModals.show(modal);
        }
    };

    $(document)
        .on('click', '.pum-modal-background, .pum-modal-wrap .cancel, .pum-modal-wrap .pum-modal-close', function (e) {
            var $target = $(e.target);
            if ($target.hasClass('pum-modal-background') || $target.hasClass('cancel') || $target.hasClass('pum-modal-close') || $target.hasClass('submitdelete')) {
                PUMModals.closeAll();
                e.preventDefault();
                e.stopPropagation();
            }
        });

}(jQuery, document));
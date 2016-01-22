/**
 * Defines the core $.popmake binds.
 * Version 1.4.0
 */
var PUM_Accessibility;
(function ($, undefined) {
    "use strict";
    var $top_level_elements,
        focusableElementsString = "a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]",
        previouslyFocused,
        currentModal;

    PUM_Accessibility = {
        // Accessibility: Checks focus events to ensure they stay inside the modal.
        forceFocus: function (e) {
            console.log(currentModal, !$.contains(currentModal, e.target));
            if (currentModal && !$.contains(currentModal, e.target)) {
                e.stopPropagation();
                PUM_Accessibility.setFocusToFirstItem();
            }
        },
        trapTabKey: function (e) {
            // if tab or shift-tab pressed
            if (e.keyCode === 9) {
                // get list of focusable items
                var focusableItems = currentModal.find('.pum-container *').filter(focusableElementsString).filter(':visible'),
                // get currently focused item
                    focusedItem = $(':focus'),
                // get the number of focusable items
                    numberOfFocusableItems = focusableItems.length,
                // get the index of the currently focused item
                    focusedItemIndex = focusableItems.index(focusedItem);

                console.log(focusableItems);

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
            currentModal.find('.pum-container *').filter(focusableElementsString).filter(':visible').first().focus();
        }
    };

    $(document)
        .on('pumInit', '.pum', function () {
            PUM.getPopup(this).find('[tabindex]').each(function () {
                var $this = $(this);
                $this
                    .data('tabindex', $this.attr('tabindex'))
                    .prop('tabindex', '0');

            });
        })


        .on('pumBeforeOpen', '.pum', function () {
            var $popup = PUM.getPopup(this),
                $focused = $(':focus');

            // Accessibility: Sets the previous focus element.
            if (!$popup.has($focused).length) {
                previouslyFocused = $focused;
            }

            // Accessibility: Sets the current modal for focus checks.
            currentModal = $popup
            // Accessibility: Trap tab key.
                .on('keydown.pum_accessibility', PUM_Accessibility.trapTabKey)
                .attr('aria-hidden', 'false');

            $top_level_elements = $('body > *').filter(':visible').not(currentModal);
            $top_level_elements.attr('aria-hidden', 'true');

            // Accessibility: Add focus check that prevents tabbing outside of modal.
            $(document).on('focus.pum_accessibility', PUM_Accessibility.forceFocus);

            // Accessibility: Focus on the modal.
            PUM_Accessibility.setFocusToFirstItem();
        })
        .on('pumAfterOpen', '.pum', function () {

        })


        .on('pumBeforeClose', '.pum', function () {

        })
        .on('pumAfterClose', '.pum', function () {
            var $popup = PUM.getPopup(this);

            $popup
                .off('keydown.pum_accessibility')
                .attr('aria-hidden', 'true');

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
            $(document).off('focus.pum_accessibility');
        })

        .on('pumSetupClose', '.pum', function () {

        })

        .on('pumOpenPrevented', '.pum', function () {

        })

        .on('pumClosePrevented', '.pum', function () {

        })

        .on('pumBeforeReposition', '.pum', function () {

        });


}(jQuery));
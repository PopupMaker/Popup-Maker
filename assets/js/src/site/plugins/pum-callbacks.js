/**
 * Defines the core $.popmake callbacks.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $.fn.popmake.callbacks = {
        reposition_using: function (position) {
            $(this).css(position);
        }
    };

}(jQuery));
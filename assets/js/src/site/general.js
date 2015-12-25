/**
 * Initialize Popup Maker.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $('.popmake').css({visibility: "visible"}).hide();

    $(document).ready(function () {
        $('.popmake').popmake();
    });
}(jQuery));
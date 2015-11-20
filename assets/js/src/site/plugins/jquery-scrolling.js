/**
 * jQuery isScrolling v0.1
 * $.isScrolling() is used to tell if the document is currently being scrolled.
 */
(function ($) {
    "use strict";

    var isScrolling = false;
    $(window)
        .on('scroll', function () {
            isScrolling = true;
        })
        .on('scrollstop', function () {
            isScrolling = false;
        });

    $.fn.isScrolling = function () {
        return isScrolling;
    };

}(jQuery));
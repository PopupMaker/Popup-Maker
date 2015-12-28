/**
 * Adds needed backward compatibility for older versions of jQuery
 */
(function ($) {
    "use strict";
    if ($.fn.on === undefined) {
        $.fn.on = function (types, sel, fn) {
            return this.delegate(sel, types, fn);
        };
    }
    if ($.fn.off === undefined) {
        $.fn.off = function (types, sel, fn) {
            return this.undelegate(sel, types, fn);
        };
    }
}(jQuery));

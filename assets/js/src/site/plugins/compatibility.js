/**
 * Adds needed backward compatibility for older versions of jQuery
 */
(function($) {
    "use strict";

    if (!$.isFunction($.fn.on)) {
        $.fn.on = function (types, sel, fn) {
            return this.delegate(sel, types, fn);
        };
        $.fn.off = function (types, sel, fn) {
            return this.undelegate(sel, types, fn);
        };
    }

}(jQuery));

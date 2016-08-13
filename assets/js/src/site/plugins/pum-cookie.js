/**
 * Defines the core $.popmake.cookie functions.
 * Version 1.4
 *
 * Defines the pm_cookie & pm_remove_cookie global functions.
 */
var pm_cookie, pm_remove_cookie;
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.cookie = {
        defaults: {},
        raw: false,
        json: true,
        pluses: /\+/g,
        encode: function (s) {
            return $.fn.popmake.cookie.raw ? s : encodeURIComponent(s);
        },
        decode: function (s) {
            return $.fn.popmake.cookie.raw ? s : decodeURIComponent(s);
        },
        stringifyCookieValue: function (value) {
            return $.fn.popmake.cookie.encode($.fn.popmake.cookie.json ? JSON.stringify(value) : String(value));
        },
        parseCookieValue: function (s) {
            if (s.indexOf('"') === 0) {
                // This is a quoted cookie as according to RFC2068, unescape...
                s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
            }

            try {
                // Replace server-side written pluses with spaces.
                // If we can't decode the cookie, ignore it, it's unusable.
                // If we can't parse the cookie, ignore it, it's unusable.
                s = decodeURIComponent(s.replace($.fn.popmake.cookie.pluses, ' '));
                return $.fn.popmake.cookie.json ? JSON.parse(s) : s;
            } catch (ignore) {
            }
        },
        read: function (s, converter) {
            var value = $.fn.popmake.cookie.raw ? s : $.fn.popmake.cookie.parseCookieValue(s);
            return $.isFunction(converter) ? converter(value) : value;
        },
        process: function (key, value, expires, path) {
            var result = key ? undefined : {},
                t = new Date(),
                cookies = document.cookie ? document.cookie.split('; ') : [],
                parts,
                name,
                cookie,
                i,
                l;
            // Write

            if (value !== undefined && !$.isFunction(value)) {

                switch (typeof expires) {
                case 'number':
                    t.setTime(+t + expires * 864e+5);
                    expires = t;
                    break;
                case 'string':
                    t.setTime($.fn.popmake.utilities.strtotime("+" + expires) * 1000);
                    expires = t;
                    break;
                }

                document.cookie = [
                    $.fn.popmake.cookie.encode(key), '=', $.fn.popmake.cookie.stringifyCookieValue(value),
                    expires ? '; expires=' + expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                    path ? '; path=' + path : ''
                ].join('');
                return;
            }

            for (i = 0, l = cookies.length; i < l; i += 1) {
                parts = cookies[i].split('=');
                name = $.fn.popmake.cookie.decode(parts.shift());
                cookie = parts.join('=');

                if (key && key === name) {
                    // If second argument (value) is a function it's a converter...
                    result = $.fn.popmake.cookie.read(cookie, value);
                    break;
                }

                // Prevent storing a cookie that we couldn't decode.
                cookie = $.fn.popmake.cookie.read(cookie);
                if (!key && cookie !== undefined) {
                    result[name] = cookie;
                }
            }

            return result;
        },
        remove: function (key) {
            if ($.pm_cookie(key) === undefined) {
                return false;
            }
            $.pm_cookie(key, '', -1);
            $.pm_cookie(key, '', -1, '/');
            return !$.pm_cookie(key);
        }
    };

    pm_cookie = $.pm_cookie = $.fn.popmake.cookie.process;
    pm_remove_cookie = $.pm_remove_cookie = $.fn.popmake.cookie.remove;

}(jQuery, document));
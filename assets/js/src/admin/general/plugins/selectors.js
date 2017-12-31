/*
 * $$ Selector Cache
 * Cache your selectors, without messy code.
 * @author Stephen Kamenar
 */
(function ($, undefined) {
    // '#a': $('#a')
    if (typeof window.$$ === 'function') {
        return;
    }

    var cache = {},
        cacheByContext = {}, // '#context': (a cache object for the element)
        tmp, tmp2; // Here for performance/minification

    window.$$ = function (selector, context) {
        if (context) {
            if (tmp = context.selector) {
                context = tmp;
            }

            // tmp2 is contextCache
            tmp2 = cacheByContext[context];

            if (tmp2 === undefined) {
                tmp2 = cacheByContext[context] = {};
            }

            tmp = tmp2[selector];

            if (tmp !== undefined) {
                return tmp;
            }

            return tmp2[selector] = $(selector, $$(context));
        }

        tmp = cache[selector];

        if (tmp !== undefined) {
            return tmp;
        }

        return cache[selector] = $(selector);
    };

    window.$$clear = function (selector, context) {
        if (context) {
            if (tmp = context.selector) {
                context = tmp;
            }

            if (selector && (tmp = cacheByContext[context])) {
                tmp[selector] = undefined;
            }

            cacheByContext[context] = undefined;
        } else {
            if (selector) {
                cache[selector] = undefined;
                cacheByContext[selector] = undefined;
            } else {
                cache = {};
                cacheByContext = {};
            }
        }
    };

    window.$$fresh = function (selector, context) {
        $$clear(selector, context);
        return $$(selector, context);
    };

}(jQuery));
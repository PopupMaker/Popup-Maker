/* jshint ignore:start */
/*!
 * Select2 4.0.2
 * https://select2.github.io
 *
 * Released under the MIT license
 * https://github.com/select2/select2/blob/master/LICENSE.md
 */
(function (factory) {
    if (typeof define === 'function' && define.amd !== undefined && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function (jQuery) {
    // This is needed so we can catch the AMD loader configuration and use it
    // The inner file should be wrapped (by `banner.start.js`) in a function that
    // returns the AMD loader references.
    var S2 =
        (function () {
            // Restore the Select2 AMD loader so it can be used
            // Needed mostly in the language files, where the loader is not inserted
            if (jQuery && jQuery.fn && jQuery.fn.pumselect2 && jQuery.fn.pumselect2.amd) {
                var S2 = jQuery.fn.pumselect2.amd;
            }
            var S2;(function () { if (!S2 || !S2.requirejs) {
                if (!S2) { S2 = {}; } else { require = S2; }
                /**
                 * @license almond 0.3.1 Copyright (c) 2011-2014, The Dojo Foundation All Rights Reserved.
                 * Available via the MIT or new BSD license.
                 * see: http://github.com/jrburke/almond for details
                 */
//Going sloppy to avoid 'use strict' string cost, but strict practices should
//be followed.
                /*jslint sloppy: true */
                /*global setTimeout: false */

                var requirejs, require, define;
                (function (undef) {
                    var main, req, makeMap, handlers,
                        defined = {},
                        waiting = {},
                        config = {},
                        defining = {},
                        hasOwn = Object.prototype.hasOwnProperty,
                        aps = [].slice,
                        jsSuffixRegExp = /\.js$/;

                    function hasProp(obj, prop) {
                        return hasOwn.call(obj, prop);
                    }

                    /**
                     * Given a relative module name, like ./something, normalize it to
                     * a real name that can be mapped to a path.
                     * @param {String} name the relative name
                     * @param {String} baseName a real name that the name arg is relative
                     * to.
                     * @returns {String} normalized name
                     */
                    function normalize(name, baseName) {
                        var nameParts, nameSegment, mapValue, foundMap, lastIndex,
                            foundI, foundStarMap, starI, i, j, part,
                            baseParts = baseName && baseName.split("/"),
                            map = config.map,
                            starMap = (map && map['*']) || {};

                        //Adjust any relative paths.
                        if (name && name.charAt(0) === ".") {
                            //If have a base name, try to normalize against it,
                            //otherwise, assume it is a top-level require that will
                            //be relative to baseUrl in the end.
                            if (baseName) {
                                name = name.split('/');
                                lastIndex = name.length - 1;

                                // Node .js allowance:
                                if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                                    name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
                                }

                                //Lop off the last part of baseParts, so that . matches the
                                //"directory" and not name of the baseName's module. For instance,
                                //baseName of "one/two/three", maps to "one/two/three.js", but we
                                //want the directory, "one/two" for this normalization.
                                name = baseParts.slice(0, baseParts.length - 1).concat(name);

                                //start trimDots
                                for (i = 0; i < name.length; i += 1) {
                                    part = name[i];
                                    if (part === ".") {
                                        name.splice(i, 1);
                                        i -= 1;
                                    } else if (part === "..") {
                                        if (i === 1 && (name[2] === '..' || name[0] === '..')) {
                                            //End of the line. Keep at least one non-dot
                                            //path segment at the front so it can be mapped
                                            //correctly to disk. Otherwise, there is likely
                                            //no path mapping for a path starting with '..'.
                                            //This can still fail, but catches the most reasonable
                                            //uses of ..
                                            break;
                                        } else if (i > 0) {
                                            name.splice(i - 1, 2);
                                            i -= 2;
                                        }
                                    }
                                }
                                //end trimDots

                                name = name.join("/");
                            } else if (name.indexOf('./') === 0) {
                                // No baseName, so this is ID is resolved relative
                                // to baseUrl, pull off the leading dot.
                                name = name.substring(2);
                            }
                        }

                        //Apply map config if available.
                        if ((baseParts || starMap) && map) {
                            nameParts = name.split('/');

                            for (i = nameParts.length; i > 0; i -= 1) {
                                nameSegment = nameParts.slice(0, i).join("/");

                                if (baseParts) {
                                    //Find the longest baseName segment match in the config.
                                    //So, do joins on the biggest to smallest lengths of baseParts.
                                    for (j = baseParts.length; j > 0; j -= 1) {
                                        mapValue = map[baseParts.slice(0, j).join('/')];

                                        //baseName segment has  config, find if it has one for
                                        //this name.
                                        if (mapValue) {
                                            mapValue = mapValue[nameSegment];
                                            if (mapValue) {
                                                //Match, update name to the new value.
                                                foundMap = mapValue;
                                                foundI = i;
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (foundMap) {
                                    break;
                                }

                                //Check for a star map match, but just hold on to it,
                                //if there is a shorter segment match later in a matching
                                //config, then favor over this star map.
                                if (!foundStarMap && starMap && starMap[nameSegment]) {
                                    foundStarMap = starMap[nameSegment];
                                    starI = i;
                                }
                            }

                            if (!foundMap && foundStarMap) {
                                foundMap = foundStarMap;
                                foundI = starI;
                            }

                            if (foundMap) {
                                nameParts.splice(0, foundI, foundMap);
                                name = nameParts.join('/');
                            }
                        }

                        return name;
                    }

                    function makeRequire(relName, forceSync) {
                        return function () {
                            //A version of a require function that passes a moduleName
                            //value for items that may need to
                            //look up paths relative to the moduleName
                            var args = aps.call(arguments, 0);

                            //If first arg is not require('string'), and there is only
                            //one arg, it is the array form without a callback. Insert
                            //a null so that the following concat is correct.
                            if (typeof args[0] !== 'string' && args.length === 1) {
                                args.push(null);
                            }
                            return req.apply(undef, args.concat([relName, forceSync]));
                        };
                    }

                    function makeNormalize(relName) {
                        return function (name) {
                            return normalize(name, relName);
                        };
                    }

                    function makeLoad(depName) {
                        return function (value) {
                            defined[depName] = value;
                        };
                    }

                    function callDep(name) {
                        if (hasProp(waiting, name)) {
                            var args = waiting[name];
                            delete waiting[name];
                            defining[name] = true;
                            main.apply(undef, args);
                        }

                        if (!hasProp(defined, name) && !hasProp(defining, name)) {
                            throw new Error('No ' + name);
                        }
                        return defined[name];
                    }

                    //Turns a plugin!resource to [plugin, resource]
                    //with the plugin being undefined if the name
                    //did not have a plugin prefix.
                    function splitPrefix(name) {
                        var prefix,
                            index = name ? name.indexOf('!') : -1;
                        if (index > -1) {
                            prefix = name.substring(0, index);
                            name = name.substring(index + 1, name.length);
                        }
                        return [prefix, name];
                    }

                    /**
                     * Makes a name map, normalizing the name, and using a plugin
                     * for normalization if necessary. Grabs a ref to plugin
                     * too, as an optimization.
                     */
                    makeMap = function (name, relName) {
                        var plugin,
                            parts = splitPrefix(name),
                            prefix = parts[0];

                        name = parts[1];

                        if (prefix) {
                            prefix = normalize(prefix, relName);
                            plugin = callDep(prefix);
                        }

                        //Normalize according
                        if (prefix) {
                            if (plugin && plugin.normalize) {
                                name = plugin.normalize(name, makeNormalize(relName));
                            } else {
                                name = normalize(name, relName);
                            }
                        } else {
                            name = normalize(name, relName);
                            parts = splitPrefix(name);
                            prefix = parts[0];
                            name = parts[1];
                            if (prefix) {
                                plugin = callDep(prefix);
                            }
                        }

                        //Using ridiculous property names for space reasons
                        return {
                            f: prefix ? prefix + '!' + name : name, //fullName
                            n: name,
                            pr: prefix,
                            p: plugin
                        };
                    };

                    function makeConfig(name) {
                        return function () {
                            return (config && config.config && config.config[name]) || {};
                        };
                    }

                    handlers = {
                        require: function (name) {
                            return makeRequire(name);
                        },
                        exports: function (name) {
                            var e = defined[name];
                            if (typeof e !== 'undefined') {
                                return e;
                            } else {
                                return (defined[name] = {});
                            }
                        },
                        module: function (name) {
                            return {
                                id: name,
                                uri: '',
                                exports: defined[name],
                                config: makeConfig(name)
                            };
                        }
                    };

                    main = function (name, deps, callback, relName) {
                        var cjsModule, depName, ret, map, i,
                            args = [],
                            callbackType = typeof callback,
                            usingExports;

                        //Use name if no relName
                        relName = relName || name;

                        //Call the callback to define the module, if necessary.
                        if (callbackType === 'undefined' || callbackType === 'function') {
                            //Pull out the defined dependencies and pass the ordered
                            //values to the callback.
                            //Default to [require, exports, module] if no deps
                            deps = !deps.length && callback.length ? ['require', 'exports', 'module'] : deps;
                            for (i = 0; i < deps.length; i += 1) {
                                map = makeMap(deps[i], relName);
                                depName = map.f;

                                //Fast path CommonJS standard dependencies.
                                if (depName === "require") {
                                    args[i] = handlers.require(name);
                                } else if (depName === "exports") {
                                    //CommonJS module spec 1.1
                                    args[i] = handlers.exports(name);
                                    usingExports = true;
                                } else if (depName === "module") {
                                    //CommonJS module spec 1.1
                                    cjsModule = args[i] = handlers.module(name);
                                } else if (hasProp(defined, depName) ||
                                    hasProp(waiting, depName) ||
                                    hasProp(defining, depName)) {
                                    args[i] = callDep(depName);
                                } else if (map.p) {
                                    map.p.load(map.n, makeRequire(relName, true), makeLoad(depName), {});
                                    args[i] = defined[depName];
                                } else {
                                    throw new Error(name + ' missing ' + depName);
                                }
                            }

                            ret = callback ? callback.apply(defined[name], args) : undefined;

                            if (name) {
                                //If setting exports via "module" is in play,
                                //favor that over return value and exports. After that,
                                //favor a non-undefined return value over exports use.
                                if (cjsModule && cjsModule.exports !== undef &&
                                    cjsModule.exports !== defined[name]) {
                                    defined[name] = cjsModule.exports;
                                } else if (ret !== undef || !usingExports) {
                                    //Use the return value from the function.
                                    defined[name] = ret;
                                }
                            }
                        } else if (name) {
                            //May just be an object definition for the module. Only
                            //worry about defining if have a module name.
                            defined[name] = callback;
                        }
                    };

                    requirejs = require = req = function (deps, callback, relName, forceSync, alt) {
                        if (typeof deps === "string") {
                            if (handlers[deps]) {
                                //callback in this case is really relName
                                return handlers[deps](callback);
                            }
                            //Just return the module wanted. In this scenario, the
                            //deps arg is the module name, and second arg (if passed)
                            //is just the relName.
                            //Normalize module name, if it contains . or ..
                            return callDep(makeMap(deps, callback).f);
                        } else if (!deps.splice) {
                            //deps is a config object, not an array.
                            config = deps;
                            if (config.deps) {
                                req(config.deps, config.callback);
                            }
                            if (!callback) {
                                return;
                            }

                            if (callback.splice) {
                                //callback is an array, which means it is a dependency list.
                                //Adjust args if there are dependencies
                                deps = callback;
                                callback = relName;
                                relName = null;
                            } else {
                                deps = undef;
                            }
                        }

                        //Support require(['a'])
                        callback = callback || function () {};

                        //If relName is a function, it is an errback handler,
                        //so remove it.
                        if (typeof relName === 'function') {
                            relName = forceSync;
                            forceSync = alt;
                        }

                        //Simulate async callback;
                        if (forceSync) {
                            main(undef, deps, callback, relName);
                        } else {
                            //Using a non-zero value because of concern for what old browsers
                            //do, and latest browsers "upgrade" to 4 if lower value is used:
                            //http://www.whatwg.org/specs/web-apps/current-work/multipage/timers.html#dom-windowtimers-settimeout:
                            //If want a value immediately, use require('id') instead -- something
                            //that works in almond on the global level, but not guaranteed and
                            //unlikely to work in other AMD implementations.
                            setTimeout(function () {
                                main(undef, deps, callback, relName);
                            }, 4);
                        }

                        return req;
                    };

                    /**
                     * Just drops the config on the floor, but returns req in case
                     * the config return value is used.
                     */
                    req.config = function (cfg) {
                        return req(cfg);
                    };

                    /**
                     * Expose module registry for debugging and tooling
                     */
                    requirejs._defined = defined;

                    define = function (name, deps, callback) {
                        if (typeof name !== 'string') {
                            throw new Error('See almond README: incorrect module build, no module name');
                        }

                        //This module may not have dependencies
                        if (!deps.splice) {
                            //deps is not an array, so probably means
                            //an object literal or factory function for
                            //the value. Adjust args.
                            callback = deps;
                            deps = [];
                        }

                        if (!hasProp(defined, name) && !hasProp(waiting, name)) {
                            waiting[name] = [name, deps, callback];
                        }
                    };

                    define.amd = {
                        jQuery: true
                    };
                }());

                S2.requirejs = requirejs;S2.require = require;S2.define = define;
            }
            }());
            S2.define("almond", function(){});

            /* global jQuery:false, $:false */
            S2.define('jquery',[],function () {
                var _$ = jQuery || $;

                if (_$ == null && console && console.error) {
                    console.error(
                        'Select2: An instance of jQuery or a jQuery-compatible library was not ' +
                        'found. Make sure that you are including jQuery before Select2 on your ' +
                        'web page.'
                    );
                }

                return _$;
            });

            S2.define('pumselect2/utils',[
                'jquery'
            ], function ($) {
                var Utils = {};

                Utils.Extend = function (ChildClass, SuperClass) {
                    var __hasProp = {}.hasOwnProperty;

                    function BaseConstructor () {
                        this.constructor = ChildClass;
                    }

                    for (var key in SuperClass) {
                        if (__hasProp.call(SuperClass, key)) {
                            ChildClass[key] = SuperClass[key];
                        }
                    }

                    BaseConstructor.prototype = SuperClass.prototype;
                    ChildClass.prototype = new BaseConstructor();
                    ChildClass.__super__ = SuperClass.prototype;

                    return ChildClass;
                };

                function getMethods (theClass) {
                    var proto = theClass.prototype;

                    var methods = [];

                    for (var methodName in proto) {
                        var m = proto[methodName];

                        if (typeof m !== 'function') {
                            continue;
                        }

                        if (methodName === 'constructor') {
                            continue;
                        }

                        methods.push(methodName);
                    }

                    return methods;
                }

                Utils.Decorate = function (SuperClass, DecoratorClass) {
                    var decoratedMethods = getMethods(DecoratorClass);
                    var superMethods = getMethods(SuperClass);

                    function DecoratedClass () {
                        var unshift = Array.prototype.unshift;

                        var argCount = DecoratorClass.prototype.constructor.length;

                        var calledConstructor = SuperClass.prototype.constructor;

                        if (argCount > 0) {
                            unshift.call(arguments, SuperClass.prototype.constructor);

                            calledConstructor = DecoratorClass.prototype.constructor;
                        }

                        calledConstructor.apply(this, arguments);
                    }

                    DecoratorClass.displayName = SuperClass.displayName;

                    function ctr () {
                        this.constructor = DecoratedClass;
                    }

                    DecoratedClass.prototype = new ctr();

                    for (var m = 0; m < superMethods.length; m++) {
                        var superMethod = superMethods[m];

                        DecoratedClass.prototype[superMethod] =
                            SuperClass.prototype[superMethod];
                    }

                    var calledMethod = function (methodName) {
                        // Stub out the original method if it's not decorating an actual method
                        var originalMethod = function () {};

                        if (methodName in DecoratedClass.prototype) {
                            originalMethod = DecoratedClass.prototype[methodName];
                        }

                        var decoratedMethod = DecoratorClass.prototype[methodName];

                        return function () {
                            var unshift = Array.prototype.unshift;

                            unshift.call(arguments, originalMethod);

                            return decoratedMethod.apply(this, arguments);
                        };
                    };

                    for (var d = 0; d < decoratedMethods.length; d++) {
                        var decoratedMethod = decoratedMethods[d];

                        DecoratedClass.prototype[decoratedMethod] = calledMethod(decoratedMethod);
                    }

                    return DecoratedClass;
                };

                var Observable = function () {
                    this.listeners = {};
                };

                Observable.prototype.on = function (event, callback) {
                    this.listeners = this.listeners || {};

                    if (event in this.listeners) {
                        this.listeners[event].push(callback);
                    } else {
                        this.listeners[event] = [callback];
                    }
                };

                Observable.prototype.trigger = function (event) {
                    var slice = Array.prototype.slice;

                    this.listeners = this.listeners || {};

                    if (event in this.listeners) {
                        this.invoke(this.listeners[event], slice.call(arguments, 1));
                    }

                    if ('*' in this.listeners) {
                        this.invoke(this.listeners['*'], arguments);
                    }
                };

                Observable.prototype.invoke = function (listeners, params) {
                    for (var i = 0, len = listeners.length; i < len; i++) {
                        listeners[i].apply(this, params);
                    }
                };

                Utils.Observable = Observable;

                Utils.generateChars = function (length) {
                    var chars = '';

                    for (var i = 0; i < length; i++) {
                        var randomChar = Math.floor(Math.random() * 36);
                        chars += randomChar.toString(36);
                    }

                    return chars;
                };

                Utils.bind = function (func, context) {
                    return function () {
                        func.apply(context, arguments);
                    };
                };

                Utils._convertData = function (data) {
                    for (var originalKey in data) {
                        var keys = originalKey.split('-');

                        var dataLevel = data;

                        if (keys.length === 1) {
                            continue;
                        }

                        for (var k = 0; k < keys.length; k++) {
                            var key = keys[k];

                            // Lowercase the first letter
                            // By default, dash-separated becomes camelCase
                            key = key.substring(0, 1).toLowerCase() + key.substring(1);

                            if (!(key in dataLevel)) {
                                dataLevel[key] = {};
                            }

                            if (k == keys.length - 1) {
                                dataLevel[key] = data[originalKey];
                            }

                            dataLevel = dataLevel[key];
                        }

                        delete data[originalKey];
                    }

                    return data;
                };

                Utils.hasScroll = function (index, el) {
                    // Adapted from the function created by @ShadowScripter
                    // and adapted by @BillBarry on the Stack Exchange Code Review website.
                    // The original code can be found at
                    // http://codereview.stackexchange.com/q/13338
                    // and was designed to be used with the Sizzle selector engine.

                    var $el = $(el);
                    var overflowX = el.style.overflowX;
                    var overflowY = el.style.overflowY;

                    //Check both x and y declarations
                    if (overflowX === overflowY &&
                        (overflowY === 'hidden' || overflowY === 'visible')) {
                        return false;
                    }

                    if (overflowX === 'scroll' || overflowY === 'scroll') {
                        return true;
                    }

                    return ($el.innerHeight() < el.scrollHeight ||
                    $el.innerWidth() < el.scrollWidth);
                };

                Utils.escapeMarkup = function (markup) {
                    var replaceMap = {
                        '\\': '&#92;',
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        '\'': '&#39;',
                        '/': '&#47;'
                    };

                    // Do not try to escape the markup if it's not a string
                    if (typeof markup !== 'string') {
                        return markup;
                    }

                    return String(markup).replace(/[&<>"'\/\\]/g, function (match) {
                        return replaceMap[match];
                    });
                };

                // Append an array of jQuery nodes to a given element.
                Utils.appendMany = function ($element, $nodes) {
                    // jQuery 1.7.x does not support $.fn.append() with an array
                    // Fall back to a jQuery object collection using $.fn.add()
                    if ($.fn.jquery.substr(0, 3) === '1.7') {
                        var $jqNodes = $();

                        $.map($nodes, function (node) {
                            $jqNodes = $jqNodes.add(node);
                        });

                        $nodes = $jqNodes;
                    }

                    $element.append($nodes);
                };

                return Utils;
            });

            S2.define('pumselect2/results',[
                'jquery',
                './utils'
            ], function ($, Utils) {
                function Results ($element, options, dataAdapter) {
                    this.$element = $element;
                    this.data = dataAdapter;
                    this.options = options;

                    Results.__super__.constructor.call(this);
                }

                Utils.Extend(Results, Utils.Observable);

                Results.prototype.render = function () {
                    var $results = $(
                        '<ul class="pumselect2-results__options" role="tree"></ul>'
                    );

                    if (this.options.get('multiple')) {
                        $results.attr('aria-multiselectable', 'true');
                    }

                    this.$results = $results;

                    return $results;
                };

                Results.prototype.clear = function () {
                    this.$results.empty();
                };

                Results.prototype.displayMessage = function (params) {
                    var escapeMarkup = this.options.get('escapeMarkup');

                    this.clear();
                    this.hideLoading();

                    var $message = $(
                        '<li role="treeitem" aria-live="assertive"' +
                        ' class="pumselect2-results__option"></li>'
                    );

                    var message = this.options.get('translations').get(params.message);

                    $message.append(
                        escapeMarkup(
                            message(params.args)
                        )
                    );

                    $message[0].className += ' pumselect2-results__message';

                    this.$results.append($message);
                };

                Results.prototype.hideMessages = function () {
                    this.$results.find('.pumselect2-results__message').remove();
                };

                Results.prototype.append = function (data) {
                    this.hideLoading();

                    var $options = [];

                    if (data.results == null || data.results.length === 0) {
                        if (this.$results.children().length === 0) {
                            this.trigger('results:message', {
                                message: 'noResults'
                            });
                        }

                        return;
                    }

                    data.results = this.sort(data.results);

                    for (var d = 0; d < data.results.length; d++) {
                        var item = data.results[d];

                        var $option = this.option(item);

                        $options.push($option);
                    }

                    this.$results.append($options);
                };

                Results.prototype.position = function ($results, $dropdown) {
                    var $resultsContainer = $dropdown.find('.pumselect2-results');
                    $resultsContainer.append($results);
                };

                Results.prototype.sort = function (data) {
                    var sorter = this.options.get('sorter');

                    return sorter(data);
                };

                Results.prototype.setClasses = function () {
                    var self = this;

                    this.data.current(function (selected) {
                        var selectedIds = $.map(selected, function (s) {
                            return s.id.toString();
                        });

                        var $options = self.$results
                            .find('.pumselect2-results__option[aria-selected]');

                        $options.each(function () {
                            var $option = $(this);

                            var item = $.data(this, 'data');

                            // id needs to be converted to a string when comparing
                            var id = '' + item.id;

                            if ((item.element != null && item.element.selected) ||
                                (item.element == null && $.inArray(id, selectedIds) > -1)) {
                                $option.attr('aria-selected', 'true');
                            } else {
                                $option.attr('aria-selected', 'false');
                            }
                        });

                        var $selected = $options.filter('[aria-selected=true]');

                        // Check if there are any selected options
                        if ($selected.length > 0) {
                            // If there are selected options, highlight the first
                            $selected.first().trigger('mouseenter');
                        } else {
                            // If there are no selected options, highlight the first option
                            // in the dropdown
                            $options.first().trigger('mouseenter');
                        }
                    });
                };

                Results.prototype.showLoading = function (params) {
                    this.hideLoading();

                    var loadingMore = this.options.get('translations').get('searching');

                    var loading = {
                        disabled: true,
                        loading: true,
                        text: loadingMore(params)
                    };
                    var $loading = this.option(loading);
                    $loading.className += ' loading-results';

                    this.$results.prepend($loading);
                };

                Results.prototype.hideLoading = function () {
                    this.$results.find('.loading-results').remove();
                };

                Results.prototype.option = function (data) {
                    var option = document.createElement('li');
                    option.className = 'pumselect2-results__option';

                    var attrs = {
                        'role': 'treeitem',
                        'aria-selected': 'false'
                    };

                    if (data.disabled) {
                        delete attrs['aria-selected'];
                        attrs['aria-disabled'] = 'true';
                    }

                    if (data.id == null) {
                        delete attrs['aria-selected'];
                    }

                    if (data._resultId != null) {
                        option.id = data._resultId;
                    }

                    if (data.title) {
                        option.title = data.title;
                    }

                    if (data.children) {
                        attrs.role = 'group';
                        attrs['aria-label'] = data.text;
                        delete attrs['aria-selected'];
                    }

                    for (var attr in attrs) {
                        var val = attrs[attr];

                        option.setAttribute(attr, val);
                    }

                    if (data.children) {
                        var $option = $(option);

                        var label = document.createElement('strong');
                        label.className = 'pumselect2-results__group';

                        var $label = $(label);
                        this.template(data, label);

                        var $children = [];

                        for (var c = 0; c < data.children.length; c++) {
                            var child = data.children[c];

                            var $child = this.option(child);

                            $children.push($child);
                        }

                        var $childrenContainer = $('<ul></ul>', {
                            'class': 'pumselect2-results__options pumselect2-results__options--nested'
                        });

                        $childrenContainer.append($children);

                        $option.append(label);
                        $option.append($childrenContainer);
                    } else {
                        this.template(data, option);
                    }

                    $.data(option, 'data', data);

                    return option;
                };

                Results.prototype.bind = function (container, $container) {
                    var self = this;

                    var id = container.id + '-results';

                    this.$results.attr('id', id);

                    container.on('results:all', function (params) {
                        self.clear();
                        self.append(params.data);

                        if (container.isOpen()) {
                            self.setClasses();
                        }
                    });

                    container.on('results:append', function (params) {
                        self.append(params.data);

                        if (container.isOpen()) {
                            self.setClasses();
                        }
                    });

                    container.on('query', function (params) {
                        self.hideMessages();
                        self.showLoading(params);
                    });

                    container.on('select', function () {
                        if (!container.isOpen()) {
                            return;
                        }

                        self.setClasses();
                    });

                    container.on('unselect', function () {
                        if (!container.isOpen()) {
                            return;
                        }

                        self.setClasses();
                    });

                    container.on('open', function () {
                        // When the dropdown is open, aria-expended="true"
                        self.$results.attr('aria-expanded', 'true');
                        self.$results.attr('aria-hidden', 'false');

                        self.setClasses();
                        self.ensureHighlightVisible();
                    });

                    container.on('close', function () {
                        // When the dropdown is closed, aria-expended="false"
                        self.$results.attr('aria-expanded', 'false');
                        self.$results.attr('aria-hidden', 'true');
                        self.$results.removeAttr('aria-activedescendant');
                    });

                    container.on('results:toggle', function () {
                        var $highlighted = self.getHighlightedResults();

                        if ($highlighted.length === 0) {
                            return;
                        }

                        $highlighted.trigger('mouseup');
                    });

                    container.on('results:select', function () {
                        var $highlighted = self.getHighlightedResults();

                        if ($highlighted.length === 0) {
                            return;
                        }

                        var data = $highlighted.data('data');

                        if ($highlighted.attr('aria-selected') == 'true') {
                            self.trigger('close', {});
                        } else {
                            self.trigger('select', {
                                data: data
                            });
                        }
                    });

                    container.on('results:previous', function () {
                        var $highlighted = self.getHighlightedResults();

                        var $options = self.$results.find('[aria-selected]');

                        var currentIndex = $options.index($highlighted);

                        // If we are already at te top, don't move further
                        if (currentIndex === 0) {
                            return;
                        }

                        var nextIndex = currentIndex - 1;

                        // If none are highlighted, highlight the first
                        if ($highlighted.length === 0) {
                            nextIndex = 0;
                        }

                        var $next = $options.eq(nextIndex);

                        $next.trigger('mouseenter');

                        var currentOffset = self.$results.offset().top;
                        var nextTop = $next.offset().top;
                        var nextOffset = self.$results.scrollTop() + (nextTop - currentOffset);

                        if (nextIndex === 0) {
                            self.$results.scrollTop(0);
                        } else if (nextTop - currentOffset < 0) {
                            self.$results.scrollTop(nextOffset);
                        }
                    });

                    container.on('results:next', function () {
                        var $highlighted = self.getHighlightedResults();

                        var $options = self.$results.find('[aria-selected]');

                        var currentIndex = $options.index($highlighted);

                        var nextIndex = currentIndex + 1;

                        // If we are at the last option, stay there
                        if (nextIndex >= $options.length) {
                            return;
                        }

                        var $next = $options.eq(nextIndex);

                        $next.trigger('mouseenter');

                        var currentOffset = self.$results.offset().top +
                            self.$results.outerHeight(false);
                        var nextBottom = $next.offset().top + $next.outerHeight(false);
                        var nextOffset = self.$results.scrollTop() + nextBottom - currentOffset;

                        if (nextIndex === 0) {
                            self.$results.scrollTop(0);
                        } else if (nextBottom > currentOffset) {
                            self.$results.scrollTop(nextOffset);
                        }
                    });

                    container.on('results:focus', function (params) {
                        params.element.addClass('pumselect2-results__option--highlighted');
                    });

                    container.on('results:message', function (params) {
                        self.displayMessage(params);
                    });

                    if ($.fn.mousewheel) {
                        this.$results.on('mousewheel', function (e) {
                            var top = self.$results.scrollTop();

                            var bottom = self.$results.get(0).scrollHeight - top + e.deltaY;

                            var isAtTop = e.deltaY > 0 && top - e.deltaY <= 0;
                            var isAtBottom = e.deltaY < 0 && bottom <= self.$results.height();

                            if (isAtTop) {
                                self.$results.scrollTop(0);

                                e.preventDefault();
                                e.stopPropagation();
                            } else if (isAtBottom) {
                                self.$results.scrollTop(
                                    self.$results.get(0).scrollHeight - self.$results.height()
                                );

                                e.preventDefault();
                                e.stopPropagation();
                            }
                        });
                    }

                    this.$results.on('mouseup', '.pumselect2-results__option[aria-selected]',
                        function (evt) {
                            var $this = $(this);

                            var data = $this.data('data');

                            if ($this.attr('aria-selected') === 'true') {
                                if (self.options.get('multiple')) {
                                    self.trigger('unselect', {
                                        originalEvent: evt,
                                        data: data
                                    });
                                } else {
                                    self.trigger('close', {});
                                }

                                return;
                            }

                            self.trigger('select', {
                                originalEvent: evt,
                                data: data
                            });
                        });

                    this.$results.on('mouseenter', '.pumselect2-results__option[aria-selected]',
                        function (evt) {
                            var data = $(this).data('data');

                            self.getHighlightedResults()
                                .removeClass('pumselect2-results__option--highlighted');

                            self.trigger('results:focus', {
                                data: data,
                                element: $(this)
                            });
                        });
                };

                Results.prototype.getHighlightedResults = function () {
                    var $highlighted = this.$results
                        .find('.pumselect2-results__option--highlighted');

                    return $highlighted;
                };

                Results.prototype.destroy = function () {
                    this.$results.remove();
                };

                Results.prototype.ensureHighlightVisible = function () {
                    var $highlighted = this.getHighlightedResults();

                    if ($highlighted.length === 0) {
                        return;
                    }

                    var $options = this.$results.find('[aria-selected]');

                    var currentIndex = $options.index($highlighted);

                    var currentOffset = this.$results.offset().top;
                    var nextTop = $highlighted.offset().top;
                    var nextOffset = this.$results.scrollTop() + (nextTop - currentOffset);

                    var offsetDelta = nextTop - currentOffset;
                    nextOffset -= $highlighted.outerHeight(false) * 2;

                    if (currentIndex <= 2) {
                        this.$results.scrollTop(0);
                    } else if (offsetDelta > this.$results.outerHeight() || offsetDelta < 0) {
                        this.$results.scrollTop(nextOffset);
                    }
                };

                Results.prototype.template = function (result, container) {
                    var template = this.options.get('templateResult');
                    var escapeMarkup = this.options.get('escapeMarkup');

                    var content = template(result, container);

                    if (content == null) {
                        container.style.display = 'none';
                    } else if (typeof content === 'string') {
                        container.innerHTML = escapeMarkup(content);
                    } else {
                        $(container).append(content);
                    }
                };

                return Results;
            });

            S2.define('pumselect2/keys',[

            ], function () {
                var KEYS = {
                    BACKSPACE: 8,
                    TAB: 9,
                    ENTER: 13,
                    SHIFT: 16,
                    CTRL: 17,
                    ALT: 18,
                    ESC: 27,
                    SPACE: 32,
                    PAGE_UP: 33,
                    PAGE_DOWN: 34,
                    END: 35,
                    HOME: 36,
                    LEFT: 37,
                    UP: 38,
                    RIGHT: 39,
                    DOWN: 40,
                    DELETE: 46
                };

                return KEYS;
            });

            S2.define('pumselect2/selection/base',[
                'jquery',
                '../utils',
                '../keys'
            ], function ($, Utils, KEYS) {
                function BaseSelection ($element, options) {
                    this.$element = $element;
                    this.options = options;

                    BaseSelection.__super__.constructor.call(this);
                }

                Utils.Extend(BaseSelection, Utils.Observable);

                BaseSelection.prototype.render = function () {
                    var $selection = $(
                        '<span class="pumselect2-selection" role="combobox" ' +
                        ' aria-haspopup="true" aria-expanded="false">' +
                        '</span>'
                    );

                    this._tabindex = 0;

                    if (this.$element.data('old-tabindex') != null) {
                        this._tabindex = this.$element.data('old-tabindex');
                    } else if (this.$element.attr('tabindex') != null) {
                        this._tabindex = this.$element.attr('tabindex');
                    }

                    $selection.attr('title', this.$element.attr('title'));
                    $selection.attr('tabindex', this._tabindex);

                    this.$selection = $selection;

                    return $selection;
                };

                BaseSelection.prototype.bind = function (container, $container) {
                    var self = this;

                    var id = container.id + '-container';
                    var resultsId = container.id + '-results';

                    this.container = container;

                    this.$selection.on('focus', function (evt) {
                        self.trigger('focus', evt);
                    });

                    this.$selection.on('blur', function (evt) {
                        self._handleBlur(evt);
                    });

                    this.$selection.on('keydown', function (evt) {
                        self.trigger('keypress', evt);

                        if (evt.which === KEYS.SPACE) {
                            evt.preventDefault();
                        }
                    });

                    container.on('results:focus', function (params) {
                        self.$selection.attr('aria-activedescendant', params.data._resultId);
                    });

                    container.on('selection:update', function (params) {
                        self.update(params.data);
                    });

                    container.on('open', function () {
                        // When the dropdown is open, aria-expanded="true"
                        self.$selection.attr('aria-expanded', 'true');
                        self.$selection.attr('aria-owns', resultsId);

                        self._attachCloseHandler(container);
                    });

                    container.on('close', function () {
                        // When the dropdown is closed, aria-expanded="false"
                        self.$selection.attr('aria-expanded', 'false');
                        self.$selection.removeAttr('aria-activedescendant');
                        self.$selection.removeAttr('aria-owns');

                        self.$selection.focus();

                        self._detachCloseHandler(container);
                    });

                    container.on('enable', function () {
                        self.$selection.attr('tabindex', self._tabindex);
                    });

                    container.on('disable', function () {
                        self.$selection.attr('tabindex', '-1');
                    });
                };

                BaseSelection.prototype._handleBlur = function (evt) {
                    var self = this;

                    // This needs to be delayed as the active element is the body when the tab
                    // key is pressed, possibly along with others.
                    window.setTimeout(function () {
                        // Don't trigger `blur` if the focus is still in the selection
                        if (
                            (document.activeElement == self.$selection[0]) ||
                            ($.contains(self.$selection[0], document.activeElement))
                        ) {
                            return;
                        }

                        self.trigger('blur', evt);
                    }, 1);
                };

                BaseSelection.prototype._attachCloseHandler = function (container) {
                    var self = this;

                    $(document.body).on('mousedown.pumselect2.' + container.id, function (e) {
                        var $target = $(e.target);

                        var $select = $target.closest('.pumselect2');

                        var $all = $('.pumselect2.pumselect2-container--open');

                        $all.each(function () {
                            var $this = $(this);

                            if (this == $select[0]) {
                                return;
                            }

                            var $element = $this.data('element');

                            $element.pumselect2('close');
                        });
                    });
                };

                BaseSelection.prototype._detachCloseHandler = function (container) {
                    $(document.body).off('mousedown.pumselect2.' + container.id);
                };

                BaseSelection.prototype.position = function ($selection, $container) {
                    var $selectionContainer = $container.find('.selection');
                    $selectionContainer.append($selection);
                };

                BaseSelection.prototype.destroy = function () {
                    this._detachCloseHandler(this.container);
                };

                BaseSelection.prototype.update = function (data) {
                    throw new Error('The `update` method must be defined in child classes.');
                };

                return BaseSelection;
            });

            S2.define('pumselect2/selection/single',[
                'jquery',
                './base',
                '../utils',
                '../keys'
            ], function ($, BaseSelection, Utils, KEYS) {
                function SingleSelection () {
                    SingleSelection.__super__.constructor.apply(this, arguments);
                }

                Utils.Extend(SingleSelection, BaseSelection);

                SingleSelection.prototype.render = function () {
                    var $selection = SingleSelection.__super__.render.call(this);

                    $selection.addClass('pumselect2-selection--single');

                    $selection.html(
                        '<span class="pumselect2-selection__rendered"></span>' +
                        '<span class="pumselect2-selection__arrow" role="presentation">' +
                        '<b role="presentation"></b>' +
                        '</span>'
                    );

                    return $selection;
                };

                SingleSelection.prototype.bind = function (container, $container) {
                    var self = this;

                    SingleSelection.__super__.bind.apply(this, arguments);

                    var id = container.id + '-container';

                    this.$selection.find('.pumselect2-selection__rendered').attr('id', id);
                    this.$selection.attr('aria-labelledby', id);

                    this.$selection.on('mousedown', function (evt) {
                        // Only respond to left clicks
                        if (evt.which !== 1) {
                            return;
                        }

                        self.trigger('toggle', {
                            originalEvent: evt
                        });
                    });

                    this.$selection.on('focus', function (evt) {
                        // User focuses on the container
                    });

                    this.$selection.on('blur', function (evt) {
                        // User exits the container
                    });

                    container.on('selection:update', function (params) {
                        self.update(params.data);
                    });
                };

                SingleSelection.prototype.clear = function () {
                    this.$selection.find('.pumselect2-selection__rendered').empty();
                };

                SingleSelection.prototype.display = function (data, container) {
                    var template = this.options.get('templateSelection');
                    var escapeMarkup = this.options.get('escapeMarkup');

                    return escapeMarkup(template(data, container));
                };

                SingleSelection.prototype.selectionContainer = function () {
                    return $('<span></span>');
                };

                SingleSelection.prototype.update = function (data) {
                    if (data.length === 0) {
                        this.clear();
                        return;
                    }

                    var selection = data[0];

                    var $rendered = this.$selection.find('.pumselect2-selection__rendered');
                    var formatted = this.display(selection, $rendered);

                    $rendered.empty().append(formatted);
                    $rendered.prop('title', selection.title || selection.text);
                };

                return SingleSelection;
            });

            S2.define('pumselect2/selection/multiple',[
                'jquery',
                './base',
                '../utils'
            ], function ($, BaseSelection, Utils) {
                function MultipleSelection ($element, options) {
                    MultipleSelection.__super__.constructor.apply(this, arguments);
                }

                Utils.Extend(MultipleSelection, BaseSelection);

                MultipleSelection.prototype.render = function () {
                    var $selection = MultipleSelection.__super__.render.call(this);

                    $selection.addClass('pumselect2-selection--multiple');

                    $selection.html(
                        '<ul class="pumselect2-selection__rendered"></ul>'
                    );

                    return $selection;
                };

                MultipleSelection.prototype.bind = function (container, $container) {
                    var self = this;

                    MultipleSelection.__super__.bind.apply(this, arguments);

                    this.$selection.on('click', function (evt) {
                        self.trigger('toggle', {
                            originalEvent: evt
                        });
                    });

                    this.$selection.on(
                        'click',
                        '.pumselect2-selection__choice__remove',
                        function (evt) {
                            // Ignore the event if it is disabled
                            if (self.options.get('disabled')) {
                                return;
                            }

                            var $remove = $(this);
                            var $selection = $remove.parent();

                            var data = $selection.data('data');

                            self.trigger('unselect', {
                                originalEvent: evt,
                                data: data
                            });
                        }
                    );
                };

                MultipleSelection.prototype.clear = function () {
                    this.$selection.find('.pumselect2-selection__rendered').empty();
                };

                MultipleSelection.prototype.display = function (data, container) {
                    var template = this.options.get('templateSelection');
                    var escapeMarkup = this.options.get('escapeMarkup');

                    return escapeMarkup(template(data, container));
                };

                MultipleSelection.prototype.selectionContainer = function () {
                    var $container = $(
                        '<li class="pumselect2-selection__choice">' +
                        '<span class="pumselect2-selection__choice__remove" role="presentation">' +
                        '&times;' +
                        '</span>' +
                        '</li>'
                    );

                    return $container;
                };

                MultipleSelection.prototype.update = function (data) {
                    this.clear();

                    if (data.length === 0) {
                        return;
                    }

                    var $selections = [];

                    for (var d = 0; d < data.length; d++) {
                        var selection = data[d];

                        var $selection = this.selectionContainer();
                        var formatted = this.display(selection, $selection);

                        $selection.append(formatted);
                        $selection.prop('title', selection.title || selection.text);

                        $selection.data('data', selection);

                        $selections.push($selection);
                    }

                    var $rendered = this.$selection.find('.pumselect2-selection__rendered');

                    Utils.appendMany($rendered, $selections);
                };

                return MultipleSelection;
            });

            S2.define('pumselect2/selection/placeholder',[
                '../utils'
            ], function (Utils) {
                function Placeholder (decorated, $element, options) {
                    this.placeholder = this.normalizePlaceholder(options.get('placeholder'));

                    decorated.call(this, $element, options);
                }

                Placeholder.prototype.normalizePlaceholder = function (_, placeholder) {
                    if (typeof placeholder === 'string') {
                        placeholder = {
                            id: '',
                            text: placeholder
                        };
                    }

                    return placeholder;
                };

                Placeholder.prototype.createPlaceholder = function (decorated, placeholder) {
                    var $placeholder = this.selectionContainer();

                    $placeholder.html(this.display(placeholder));
                    $placeholder.addClass('pumselect2-selection__placeholder')
                        .removeClass('pumselect2-selection__choice');

                    return $placeholder;
                };

                Placeholder.prototype.update = function (decorated, data) {
                    var singlePlaceholder = (
                        data.length == 1 && data[0].id != this.placeholder.id
                    );
                    var multipleSelections = data.length > 1;

                    if (multipleSelections || singlePlaceholder) {
                        return decorated.call(this, data);
                    }

                    this.clear();

                    var $placeholder = this.createPlaceholder(this.placeholder);

                    this.$selection.find('.pumselect2-selection__rendered').append($placeholder);
                };

                return Placeholder;
            });

            S2.define('pumselect2/selection/allowClear',[
                'jquery',
                '../keys'
            ], function ($, KEYS) {
                function AllowClear () { }

                AllowClear.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    if (this.placeholder == null) {
                        if (this.options.get('debug') && window.console && console.error) {
                            console.error(
                                'Select2: The `allowClear` option should be used in combination ' +
                                'with the `placeholder` option.'
                            );
                        }
                    }

                    this.$selection.on('mousedown', '.pumselect2-selection__clear',
                        function (evt) {
                            self._handleClear(evt);
                        });

                    container.on('keypress', function (evt) {
                        self._handleKeyboardClear(evt, container);
                    });
                };

                AllowClear.prototype._handleClear = function (_, evt) {
                    // Ignore the event if it is disabled
                    if (this.options.get('disabled')) {
                        return;
                    }

                    var $clear = this.$selection.find('.pumselect2-selection__clear');

                    // Ignore the event if nothing has been selected
                    if ($clear.length === 0) {
                        return;
                    }

                    evt.stopPropagation();

                    var data = $clear.data('data');

                    for (var d = 0; d < data.length; d++) {
                        var unselectData = {
                            data: data[d]
                        };

                        // Trigger the `unselect` event, so people can prevent it from being
                        // cleared.
                        this.trigger('unselect', unselectData);

                        // If the event was prevented, don't clear it out.
                        if (unselectData.prevented) {
                            return;
                        }
                    }

                    this.$element.val(this.placeholder.id).trigger('change');

                    this.trigger('toggle', {});
                };

                AllowClear.prototype._handleKeyboardClear = function (_, evt, container) {
                    if (container.isOpen()) {
                        return;
                    }

                    if (evt.which == KEYS.DELETE || evt.which == KEYS.BACKSPACE) {
                        this._handleClear(evt);
                    }
                };

                AllowClear.prototype.update = function (decorated, data) {
                    decorated.call(this, data);

                    if (this.$selection.find('.pumselect2-selection__placeholder').length > 0 ||
                        data.length === 0) {
                        return;
                    }

                    var $remove = $(
                        '<span class="pumselect2-selection__clear">' +
                        '&times;' +
                        '</span>'
                    );
                    $remove.data('data', data);

                    this.$selection.find('.pumselect2-selection__rendered').prepend($remove);
                };

                return AllowClear;
            });

            S2.define('pumselect2/selection/search',[
                'jquery',
                '../utils',
                '../keys'
            ], function ($, Utils, KEYS) {
                function Search (decorated, $element, options) {
                    decorated.call(this, $element, options);
                }

                Search.prototype.render = function (decorated) {
                    var $search = $(
                        '<li class="pumselect2-search pumselect2-search--inline">' +
                        '<input class="pumselect2-search__field" type="search" tabindex="-1"' +
                        ' autocomplete="off" autocorrect="off" autocapitalize="off"' +
                        ' spellcheck="false" role="textbox" aria-autocomplete="list" />' +
                        '</li>'
                    );

                    this.$searchContainer = $search;
                    this.$search = $search.find('input');

                    var $rendered = decorated.call(this);

                    this._transferTabIndex();

                    return $rendered;
                };

                Search.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    container.on('open', function () {
                        self.$search.trigger('focus');
                    });

                    container.on('close', function () {
                        self.$search.val('');
                        self.$search.removeAttr('aria-activedescendant');
                        self.$search.trigger('focus');
                    });

                    container.on('enable', function () {
                        self.$search.prop('disabled', false);

                        self._transferTabIndex();
                    });

                    container.on('disable', function () {
                        self.$search.prop('disabled', true);
                    });

                    container.on('focus', function (evt) {
                        self.$search.trigger('focus');
                    });

                    container.on('results:focus', function (params) {
                        self.$search.attr('aria-activedescendant', params.id);
                    });

                    this.$selection.on('focusin', '.pumselect2-search--inline', function (evt) {
                        self.trigger('focus', evt);
                    });

                    this.$selection.on('focusout', '.pumselect2-search--inline', function (evt) {
                        self._handleBlur(evt);
                    });

                    this.$selection.on('keydown', '.pumselect2-search--inline', function (evt) {
                        evt.stopPropagation();

                        self.trigger('keypress', evt);

                        self._keyUpPrevented = evt.isDefaultPrevented();

                        var key = evt.which;

                        if (key === KEYS.BACKSPACE && self.$search.val() === '') {
                            var $previousChoice = self.$searchContainer
                                .prev('.pumselect2-selection__choice');

                            if ($previousChoice.length > 0) {
                                var item = $previousChoice.data('data');

                                self.searchRemoveChoice(item);

                                evt.preventDefault();
                            }
                        }
                    });

                    // Try to detect the IE version should the `documentMode` property that
                    // is stored on the document. This is only implemented in IE and is
                    // slightly cleaner than doing a user agent check.
                    // This property is not available in Edge, but Edge also doesn't have
                    // this bug.
                    var msie = document.documentMode;
                    var disableInputEvents = msie && msie <= 11;

                    // Workaround for browsers which do not support the `input` event
                    // This will prevent double-triggering of events for browsers which support
                    // both the `keyup` and `input` events.
                    this.$selection.on(
                        'input.searchcheck',
                        '.pumselect2-search--inline',
                        function (evt) {
                            // IE will trigger the `input` event when a placeholder is used on a
                            // search box. To get around this issue, we are forced to ignore all
                            // `input` events in IE and keep using `keyup`.
                            if (disableInputEvents) {
                                self.$selection.off('input.search input.searchcheck');
                                return;
                            }

                            // Unbind the duplicated `keyup` event
                            self.$selection.off('keyup.search');
                        }
                    );

                    this.$selection.on(
                        'keyup.search input.search',
                        '.pumselect2-search--inline',
                        function (evt) {
                            // IE will trigger the `input` event when a placeholder is used on a
                            // search box. To get around this issue, we are forced to ignore all
                            // `input` events in IE and keep using `keyup`.
                            if (disableInputEvents && evt.type === 'input') {
                                self.$selection.off('input.search input.searchcheck');
                                return;
                            }

                            var key = evt.which;

                            // We can freely ignore events from modifier keys
                            if (key == KEYS.SHIFT || key == KEYS.CTRL || key == KEYS.ALT) {
                                return;
                            }

                            // Tabbing will be handled during the `keydown` phase
                            if (key == KEYS.TAB) {
                                return;
                            }

                            self.handleSearch(evt);
                        }
                    );
                };

                /**
                 * This method will transfer the tabindex attribute from the rendered
                 * selection to the search box. This allows for the search box to be used as
                 * the primary focus instead of the selection container.
                 *
                 * @private
                 */
                Search.prototype._transferTabIndex = function (decorated) {
                    this.$search.attr('tabindex', this.$selection.attr('tabindex'));
                    this.$selection.attr('tabindex', '-1');
                };

                Search.prototype.createPlaceholder = function (decorated, placeholder) {
                    this.$search.attr('placeholder', placeholder.text);
                };

                Search.prototype.update = function (decorated, data) {
                    var searchHadFocus = this.$search[0] == document.activeElement;

                    this.$search.attr('placeholder', '');

                    decorated.call(this, data);

                    this.$selection.find('.pumselect2-selection__rendered')
                        .append(this.$searchContainer);

                    this.resizeSearch();
                    if (searchHadFocus) {
                        this.$search.focus();
                    }
                };

                Search.prototype.handleSearch = function () {
                    this.resizeSearch();

                    if (!this._keyUpPrevented) {
                        var input = this.$search.val();

                        this.trigger('query', {
                            term: input
                        });
                    }

                    this._keyUpPrevented = false;
                };

                Search.prototype.searchRemoveChoice = function (decorated, item) {
                    this.trigger('unselect', {
                        data: item
                    });

                    this.$search.val(item.text);
                    this.handleSearch();
                };

                Search.prototype.resizeSearch = function () {
                    this.$search.css('width', '25px');

                    var width = '';

                    if (this.$search.attr('placeholder') !== '') {
                        width = this.$selection.find('.pumselect2-selection__rendered').innerWidth();
                    } else {
                        var minimumWidth = this.$search.val().length + 1;

                        width = (minimumWidth * 0.75) + 'em';
                    }

                    this.$search.css('width', width);
                };

                return Search;
            });

            S2.define('pumselect2/selection/eventRelay',[
                'jquery'
            ], function ($) {
                function EventRelay () { }

                EventRelay.prototype.bind = function (decorated, container, $container) {
                    var self = this;
                    var relayEvents = [
                        'open', 'opening',
                        'close', 'closing',
                        'select', 'selecting',
                        'unselect', 'unselecting'
                    ];

                    var preventableEvents = ['opening', 'closing', 'selecting', 'unselecting'];

                    decorated.call(this, container, $container);

                    container.on('*', function (name, params) {
                        // Ignore events that should not be relayed
                        if ($.inArray(name, relayEvents) === -1) {
                            return;
                        }

                        // The parameters should always be an object
                        params = params || {};

                        // Generate the jQuery event for the Select2 event
                        var evt = $.Event('pumselect2:' + name, {
                            params: params
                        });

                        self.$element.trigger(evt);

                        // Only handle preventable events if it was one
                        if ($.inArray(name, preventableEvents) === -1) {
                            return;
                        }

                        params.prevented = evt.isDefaultPrevented();
                    });
                };

                return EventRelay;
            });

            S2.define('pumselect2/translation',[
                'jquery',
                'require'
            ], function ($, require) {
                function Translation (dict) {
                    this.dict = dict || {};
                }

                Translation.prototype.all = function () {
                    return this.dict;
                };

                Translation.prototype.get = function (key) {
                    return this.dict[key];
                };

                Translation.prototype.extend = function (translation) {
                    this.dict = $.extend({}, translation.all(), this.dict);
                };

                // Static functions

                Translation._cache = {};

                Translation.loadPath = function (path) {
                    if (!(path in Translation._cache)) {
                        var translations = require(path);

                        Translation._cache[path] = translations;
                    }

                    return new Translation(Translation._cache[path]);
                };

                return Translation;
            });

            S2.define('pumselect2/diacritics',[

            ], function () {
                var diacritics = {
                    '\u24B6': 'A',
                    '\uFF21': 'A',
                    '\u00C0': 'A',
                    '\u00C1': 'A',
                    '\u00C2': 'A',
                    '\u1EA6': 'A',
                    '\u1EA4': 'A',
                    '\u1EAA': 'A',
                    '\u1EA8': 'A',
                    '\u00C3': 'A',
                    '\u0100': 'A',
                    '\u0102': 'A',
                    '\u1EB0': 'A',
                    '\u1EAE': 'A',
                    '\u1EB4': 'A',
                    '\u1EB2': 'A',
                    '\u0226': 'A',
                    '\u01E0': 'A',
                    '\u00C4': 'A',
                    '\u01DE': 'A',
                    '\u1EA2': 'A',
                    '\u00C5': 'A',
                    '\u01FA': 'A',
                    '\u01CD': 'A',
                    '\u0200': 'A',
                    '\u0202': 'A',
                    '\u1EA0': 'A',
                    '\u1EAC': 'A',
                    '\u1EB6': 'A',
                    '\u1E00': 'A',
                    '\u0104': 'A',
                    '\u023A': 'A',
                    '\u2C6F': 'A',
                    '\uA732': 'AA',
                    '\u00C6': 'AE',
                    '\u01FC': 'AE',
                    '\u01E2': 'AE',
                    '\uA734': 'AO',
                    '\uA736': 'AU',
                    '\uA738': 'AV',
                    '\uA73A': 'AV',
                    '\uA73C': 'AY',
                    '\u24B7': 'B',
                    '\uFF22': 'B',
                    '\u1E02': 'B',
                    '\u1E04': 'B',
                    '\u1E06': 'B',
                    '\u0243': 'B',
                    '\u0182': 'B',
                    '\u0181': 'B',
                    '\u24B8': 'C',
                    '\uFF23': 'C',
                    '\u0106': 'C',
                    '\u0108': 'C',
                    '\u010A': 'C',
                    '\u010C': 'C',
                    '\u00C7': 'C',
                    '\u1E08': 'C',
                    '\u0187': 'C',
                    '\u023B': 'C',
                    '\uA73E': 'C',
                    '\u24B9': 'D',
                    '\uFF24': 'D',
                    '\u1E0A': 'D',
                    '\u010E': 'D',
                    '\u1E0C': 'D',
                    '\u1E10': 'D',
                    '\u1E12': 'D',
                    '\u1E0E': 'D',
                    '\u0110': 'D',
                    '\u018B': 'D',
                    '\u018A': 'D',
                    '\u0189': 'D',
                    '\uA779': 'D',
                    '\u01F1': 'DZ',
                    '\u01C4': 'DZ',
                    '\u01F2': 'Dz',
                    '\u01C5': 'Dz',
                    '\u24BA': 'E',
                    '\uFF25': 'E',
                    '\u00C8': 'E',
                    '\u00C9': 'E',
                    '\u00CA': 'E',
                    '\u1EC0': 'E',
                    '\u1EBE': 'E',
                    '\u1EC4': 'E',
                    '\u1EC2': 'E',
                    '\u1EBC': 'E',
                    '\u0112': 'E',
                    '\u1E14': 'E',
                    '\u1E16': 'E',
                    '\u0114': 'E',
                    '\u0116': 'E',
                    '\u00CB': 'E',
                    '\u1EBA': 'E',
                    '\u011A': 'E',
                    '\u0204': 'E',
                    '\u0206': 'E',
                    '\u1EB8': 'E',
                    '\u1EC6': 'E',
                    '\u0228': 'E',
                    '\u1E1C': 'E',
                    '\u0118': 'E',
                    '\u1E18': 'E',
                    '\u1E1A': 'E',
                    '\u0190': 'E',
                    '\u018E': 'E',
                    '\u24BB': 'F',
                    '\uFF26': 'F',
                    '\u1E1E': 'F',
                    '\u0191': 'F',
                    '\uA77B': 'F',
                    '\u24BC': 'G',
                    '\uFF27': 'G',
                    '\u01F4': 'G',
                    '\u011C': 'G',
                    '\u1E20': 'G',
                    '\u011E': 'G',
                    '\u0120': 'G',
                    '\u01E6': 'G',
                    '\u0122': 'G',
                    '\u01E4': 'G',
                    '\u0193': 'G',
                    '\uA7A0': 'G',
                    '\uA77D': 'G',
                    '\uA77E': 'G',
                    '\u24BD': 'H',
                    '\uFF28': 'H',
                    '\u0124': 'H',
                    '\u1E22': 'H',
                    '\u1E26': 'H',
                    '\u021E': 'H',
                    '\u1E24': 'H',
                    '\u1E28': 'H',
                    '\u1E2A': 'H',
                    '\u0126': 'H',
                    '\u2C67': 'H',
                    '\u2C75': 'H',
                    '\uA78D': 'H',
                    '\u24BE': 'I',
                    '\uFF29': 'I',
                    '\u00CC': 'I',
                    '\u00CD': 'I',
                    '\u00CE': 'I',
                    '\u0128': 'I',
                    '\u012A': 'I',
                    '\u012C': 'I',
                    '\u0130': 'I',
                    '\u00CF': 'I',
                    '\u1E2E': 'I',
                    '\u1EC8': 'I',
                    '\u01CF': 'I',
                    '\u0208': 'I',
                    '\u020A': 'I',
                    '\u1ECA': 'I',
                    '\u012E': 'I',
                    '\u1E2C': 'I',
                    '\u0197': 'I',
                    '\u24BF': 'J',
                    '\uFF2A': 'J',
                    '\u0134': 'J',
                    '\u0248': 'J',
                    '\u24C0': 'K',
                    '\uFF2B': 'K',
                    '\u1E30': 'K',
                    '\u01E8': 'K',
                    '\u1E32': 'K',
                    '\u0136': 'K',
                    '\u1E34': 'K',
                    '\u0198': 'K',
                    '\u2C69': 'K',
                    '\uA740': 'K',
                    '\uA742': 'K',
                    '\uA744': 'K',
                    '\uA7A2': 'K',
                    '\u24C1': 'L',
                    '\uFF2C': 'L',
                    '\u013F': 'L',
                    '\u0139': 'L',
                    '\u013D': 'L',
                    '\u1E36': 'L',
                    '\u1E38': 'L',
                    '\u013B': 'L',
                    '\u1E3C': 'L',
                    '\u1E3A': 'L',
                    '\u0141': 'L',
                    '\u023D': 'L',
                    '\u2C62': 'L',
                    '\u2C60': 'L',
                    '\uA748': 'L',
                    '\uA746': 'L',
                    '\uA780': 'L',
                    '\u01C7': 'LJ',
                    '\u01C8': 'Lj',
                    '\u24C2': 'M',
                    '\uFF2D': 'M',
                    '\u1E3E': 'M',
                    '\u1E40': 'M',
                    '\u1E42': 'M',
                    '\u2C6E': 'M',
                    '\u019C': 'M',
                    '\u24C3': 'N',
                    '\uFF2E': 'N',
                    '\u01F8': 'N',
                    '\u0143': 'N',
                    '\u00D1': 'N',
                    '\u1E44': 'N',
                    '\u0147': 'N',
                    '\u1E46': 'N',
                    '\u0145': 'N',
                    '\u1E4A': 'N',
                    '\u1E48': 'N',
                    '\u0220': 'N',
                    '\u019D': 'N',
                    '\uA790': 'N',
                    '\uA7A4': 'N',
                    '\u01CA': 'NJ',
                    '\u01CB': 'Nj',
                    '\u24C4': 'O',
                    '\uFF2F': 'O',
                    '\u00D2': 'O',
                    '\u00D3': 'O',
                    '\u00D4': 'O',
                    '\u1ED2': 'O',
                    '\u1ED0': 'O',
                    '\u1ED6': 'O',
                    '\u1ED4': 'O',
                    '\u00D5': 'O',
                    '\u1E4C': 'O',
                    '\u022C': 'O',
                    '\u1E4E': 'O',
                    '\u014C': 'O',
                    '\u1E50': 'O',
                    '\u1E52': 'O',
                    '\u014E': 'O',
                    '\u022E': 'O',
                    '\u0230': 'O',
                    '\u00D6': 'O',
                    '\u022A': 'O',
                    '\u1ECE': 'O',
                    '\u0150': 'O',
                    '\u01D1': 'O',
                    '\u020C': 'O',
                    '\u020E': 'O',
                    '\u01A0': 'O',
                    '\u1EDC': 'O',
                    '\u1EDA': 'O',
                    '\u1EE0': 'O',
                    '\u1EDE': 'O',
                    '\u1EE2': 'O',
                    '\u1ECC': 'O',
                    '\u1ED8': 'O',
                    '\u01EA': 'O',
                    '\u01EC': 'O',
                    '\u00D8': 'O',
                    '\u01FE': 'O',
                    '\u0186': 'O',
                    '\u019F': 'O',
                    '\uA74A': 'O',
                    '\uA74C': 'O',
                    '\u01A2': 'OI',
                    '\uA74E': 'OO',
                    '\u0222': 'OU',
                    '\u24C5': 'P',
                    '\uFF30': 'P',
                    '\u1E54': 'P',
                    '\u1E56': 'P',
                    '\u01A4': 'P',
                    '\u2C63': 'P',
                    '\uA750': 'P',
                    '\uA752': 'P',
                    '\uA754': 'P',
                    '\u24C6': 'Q',
                    '\uFF31': 'Q',
                    '\uA756': 'Q',
                    '\uA758': 'Q',
                    '\u024A': 'Q',
                    '\u24C7': 'R',
                    '\uFF32': 'R',
                    '\u0154': 'R',
                    '\u1E58': 'R',
                    '\u0158': 'R',
                    '\u0210': 'R',
                    '\u0212': 'R',
                    '\u1E5A': 'R',
                    '\u1E5C': 'R',
                    '\u0156': 'R',
                    '\u1E5E': 'R',
                    '\u024C': 'R',
                    '\u2C64': 'R',
                    '\uA75A': 'R',
                    '\uA7A6': 'R',
                    '\uA782': 'R',
                    '\u24C8': 'S',
                    '\uFF33': 'S',
                    '\u1E9E': 'S',
                    '\u015A': 'S',
                    '\u1E64': 'S',
                    '\u015C': 'S',
                    '\u1E60': 'S',
                    '\u0160': 'S',
                    '\u1E66': 'S',
                    '\u1E62': 'S',
                    '\u1E68': 'S',
                    '\u0218': 'S',
                    '\u015E': 'S',
                    '\u2C7E': 'S',
                    '\uA7A8': 'S',
                    '\uA784': 'S',
                    '\u24C9': 'T',
                    '\uFF34': 'T',
                    '\u1E6A': 'T',
                    '\u0164': 'T',
                    '\u1E6C': 'T',
                    '\u021A': 'T',
                    '\u0162': 'T',
                    '\u1E70': 'T',
                    '\u1E6E': 'T',
                    '\u0166': 'T',
                    '\u01AC': 'T',
                    '\u01AE': 'T',
                    '\u023E': 'T',
                    '\uA786': 'T',
                    '\uA728': 'TZ',
                    '\u24CA': 'U',
                    '\uFF35': 'U',
                    '\u00D9': 'U',
                    '\u00DA': 'U',
                    '\u00DB': 'U',
                    '\u0168': 'U',
                    '\u1E78': 'U',
                    '\u016A': 'U',
                    '\u1E7A': 'U',
                    '\u016C': 'U',
                    '\u00DC': 'U',
                    '\u01DB': 'U',
                    '\u01D7': 'U',
                    '\u01D5': 'U',
                    '\u01D9': 'U',
                    '\u1EE6': 'U',
                    '\u016E': 'U',
                    '\u0170': 'U',
                    '\u01D3': 'U',
                    '\u0214': 'U',
                    '\u0216': 'U',
                    '\u01AF': 'U',
                    '\u1EEA': 'U',
                    '\u1EE8': 'U',
                    '\u1EEE': 'U',
                    '\u1EEC': 'U',
                    '\u1EF0': 'U',
                    '\u1EE4': 'U',
                    '\u1E72': 'U',
                    '\u0172': 'U',
                    '\u1E76': 'U',
                    '\u1E74': 'U',
                    '\u0244': 'U',
                    '\u24CB': 'V',
                    '\uFF36': 'V',
                    '\u1E7C': 'V',
                    '\u1E7E': 'V',
                    '\u01B2': 'V',
                    '\uA75E': 'V',
                    '\u0245': 'V',
                    '\uA760': 'VY',
                    '\u24CC': 'W',
                    '\uFF37': 'W',
                    '\u1E80': 'W',
                    '\u1E82': 'W',
                    '\u0174': 'W',
                    '\u1E86': 'W',
                    '\u1E84': 'W',
                    '\u1E88': 'W',
                    '\u2C72': 'W',
                    '\u24CD': 'X',
                    '\uFF38': 'X',
                    '\u1E8A': 'X',
                    '\u1E8C': 'X',
                    '\u24CE': 'Y',
                    '\uFF39': 'Y',
                    '\u1EF2': 'Y',
                    '\u00DD': 'Y',
                    '\u0176': 'Y',
                    '\u1EF8': 'Y',
                    '\u0232': 'Y',
                    '\u1E8E': 'Y',
                    '\u0178': 'Y',
                    '\u1EF6': 'Y',
                    '\u1EF4': 'Y',
                    '\u01B3': 'Y',
                    '\u024E': 'Y',
                    '\u1EFE': 'Y',
                    '\u24CF': 'Z',
                    '\uFF3A': 'Z',
                    '\u0179': 'Z',
                    '\u1E90': 'Z',
                    '\u017B': 'Z',
                    '\u017D': 'Z',
                    '\u1E92': 'Z',
                    '\u1E94': 'Z',
                    '\u01B5': 'Z',
                    '\u0224': 'Z',
                    '\u2C7F': 'Z',
                    '\u2C6B': 'Z',
                    '\uA762': 'Z',
                    '\u24D0': 'a',
                    '\uFF41': 'a',
                    '\u1E9A': 'a',
                    '\u00E0': 'a',
                    '\u00E1': 'a',
                    '\u00E2': 'a',
                    '\u1EA7': 'a',
                    '\u1EA5': 'a',
                    '\u1EAB': 'a',
                    '\u1EA9': 'a',
                    '\u00E3': 'a',
                    '\u0101': 'a',
                    '\u0103': 'a',
                    '\u1EB1': 'a',
                    '\u1EAF': 'a',
                    '\u1EB5': 'a',
                    '\u1EB3': 'a',
                    '\u0227': 'a',
                    '\u01E1': 'a',
                    '\u00E4': 'a',
                    '\u01DF': 'a',
                    '\u1EA3': 'a',
                    '\u00E5': 'a',
                    '\u01FB': 'a',
                    '\u01CE': 'a',
                    '\u0201': 'a',
                    '\u0203': 'a',
                    '\u1EA1': 'a',
                    '\u1EAD': 'a',
                    '\u1EB7': 'a',
                    '\u1E01': 'a',
                    '\u0105': 'a',
                    '\u2C65': 'a',
                    '\u0250': 'a',
                    '\uA733': 'aa',
                    '\u00E6': 'ae',
                    '\u01FD': 'ae',
                    '\u01E3': 'ae',
                    '\uA735': 'ao',
                    '\uA737': 'au',
                    '\uA739': 'av',
                    '\uA73B': 'av',
                    '\uA73D': 'ay',
                    '\u24D1': 'b',
                    '\uFF42': 'b',
                    '\u1E03': 'b',
                    '\u1E05': 'b',
                    '\u1E07': 'b',
                    '\u0180': 'b',
                    '\u0183': 'b',
                    '\u0253': 'b',
                    '\u24D2': 'c',
                    '\uFF43': 'c',
                    '\u0107': 'c',
                    '\u0109': 'c',
                    '\u010B': 'c',
                    '\u010D': 'c',
                    '\u00E7': 'c',
                    '\u1E09': 'c',
                    '\u0188': 'c',
                    '\u023C': 'c',
                    '\uA73F': 'c',
                    '\u2184': 'c',
                    '\u24D3': 'd',
                    '\uFF44': 'd',
                    '\u1E0B': 'd',
                    '\u010F': 'd',
                    '\u1E0D': 'd',
                    '\u1E11': 'd',
                    '\u1E13': 'd',
                    '\u1E0F': 'd',
                    '\u0111': 'd',
                    '\u018C': 'd',
                    '\u0256': 'd',
                    '\u0257': 'd',
                    '\uA77A': 'd',
                    '\u01F3': 'dz',
                    '\u01C6': 'dz',
                    '\u24D4': 'e',
                    '\uFF45': 'e',
                    '\u00E8': 'e',
                    '\u00E9': 'e',
                    '\u00EA': 'e',
                    '\u1EC1': 'e',
                    '\u1EBF': 'e',
                    '\u1EC5': 'e',
                    '\u1EC3': 'e',
                    '\u1EBD': 'e',
                    '\u0113': 'e',
                    '\u1E15': 'e',
                    '\u1E17': 'e',
                    '\u0115': 'e',
                    '\u0117': 'e',
                    '\u00EB': 'e',
                    '\u1EBB': 'e',
                    '\u011B': 'e',
                    '\u0205': 'e',
                    '\u0207': 'e',
                    '\u1EB9': 'e',
                    '\u1EC7': 'e',
                    '\u0229': 'e',
                    '\u1E1D': 'e',
                    '\u0119': 'e',
                    '\u1E19': 'e',
                    '\u1E1B': 'e',
                    '\u0247': 'e',
                    '\u025B': 'e',
                    '\u01DD': 'e',
                    '\u24D5': 'f',
                    '\uFF46': 'f',
                    '\u1E1F': 'f',
                    '\u0192': 'f',
                    '\uA77C': 'f',
                    '\u24D6': 'g',
                    '\uFF47': 'g',
                    '\u01F5': 'g',
                    '\u011D': 'g',
                    '\u1E21': 'g',
                    '\u011F': 'g',
                    '\u0121': 'g',
                    '\u01E7': 'g',
                    '\u0123': 'g',
                    '\u01E5': 'g',
                    '\u0260': 'g',
                    '\uA7A1': 'g',
                    '\u1D79': 'g',
                    '\uA77F': 'g',
                    '\u24D7': 'h',
                    '\uFF48': 'h',
                    '\u0125': 'h',
                    '\u1E23': 'h',
                    '\u1E27': 'h',
                    '\u021F': 'h',
                    '\u1E25': 'h',
                    '\u1E29': 'h',
                    '\u1E2B': 'h',
                    '\u1E96': 'h',
                    '\u0127': 'h',
                    '\u2C68': 'h',
                    '\u2C76': 'h',
                    '\u0265': 'h',
                    '\u0195': 'hv',
                    '\u24D8': 'i',
                    '\uFF49': 'i',
                    '\u00EC': 'i',
                    '\u00ED': 'i',
                    '\u00EE': 'i',
                    '\u0129': 'i',
                    '\u012B': 'i',
                    '\u012D': 'i',
                    '\u00EF': 'i',
                    '\u1E2F': 'i',
                    '\u1EC9': 'i',
                    '\u01D0': 'i',
                    '\u0209': 'i',
                    '\u020B': 'i',
                    '\u1ECB': 'i',
                    '\u012F': 'i',
                    '\u1E2D': 'i',
                    '\u0268': 'i',
                    '\u0131': 'i',
                    '\u24D9': 'j',
                    '\uFF4A': 'j',
                    '\u0135': 'j',
                    '\u01F0': 'j',
                    '\u0249': 'j',
                    '\u24DA': 'k',
                    '\uFF4B': 'k',
                    '\u1E31': 'k',
                    '\u01E9': 'k',
                    '\u1E33': 'k',
                    '\u0137': 'k',
                    '\u1E35': 'k',
                    '\u0199': 'k',
                    '\u2C6A': 'k',
                    '\uA741': 'k',
                    '\uA743': 'k',
                    '\uA745': 'k',
                    '\uA7A3': 'k',
                    '\u24DB': 'l',
                    '\uFF4C': 'l',
                    '\u0140': 'l',
                    '\u013A': 'l',
                    '\u013E': 'l',
                    '\u1E37': 'l',
                    '\u1E39': 'l',
                    '\u013C': 'l',
                    '\u1E3D': 'l',
                    '\u1E3B': 'l',
                    '\u017F': 'l',
                    '\u0142': 'l',
                    '\u019A': 'l',
                    '\u026B': 'l',
                    '\u2C61': 'l',
                    '\uA749': 'l',
                    '\uA781': 'l',
                    '\uA747': 'l',
                    '\u01C9': 'lj',
                    '\u24DC': 'm',
                    '\uFF4D': 'm',
                    '\u1E3F': 'm',
                    '\u1E41': 'm',
                    '\u1E43': 'm',
                    '\u0271': 'm',
                    '\u026F': 'm',
                    '\u24DD': 'n',
                    '\uFF4E': 'n',
                    '\u01F9': 'n',
                    '\u0144': 'n',
                    '\u00F1': 'n',
                    '\u1E45': 'n',
                    '\u0148': 'n',
                    '\u1E47': 'n',
                    '\u0146': 'n',
                    '\u1E4B': 'n',
                    '\u1E49': 'n',
                    '\u019E': 'n',
                    '\u0272': 'n',
                    '\u0149': 'n',
                    '\uA791': 'n',
                    '\uA7A5': 'n',
                    '\u01CC': 'nj',
                    '\u24DE': 'o',
                    '\uFF4F': 'o',
                    '\u00F2': 'o',
                    '\u00F3': 'o',
                    '\u00F4': 'o',
                    '\u1ED3': 'o',
                    '\u1ED1': 'o',
                    '\u1ED7': 'o',
                    '\u1ED5': 'o',
                    '\u00F5': 'o',
                    '\u1E4D': 'o',
                    '\u022D': 'o',
                    '\u1E4F': 'o',
                    '\u014D': 'o',
                    '\u1E51': 'o',
                    '\u1E53': 'o',
                    '\u014F': 'o',
                    '\u022F': 'o',
                    '\u0231': 'o',
                    '\u00F6': 'o',
                    '\u022B': 'o',
                    '\u1ECF': 'o',
                    '\u0151': 'o',
                    '\u01D2': 'o',
                    '\u020D': 'o',
                    '\u020F': 'o',
                    '\u01A1': 'o',
                    '\u1EDD': 'o',
                    '\u1EDB': 'o',
                    '\u1EE1': 'o',
                    '\u1EDF': 'o',
                    '\u1EE3': 'o',
                    '\u1ECD': 'o',
                    '\u1ED9': 'o',
                    '\u01EB': 'o',
                    '\u01ED': 'o',
                    '\u00F8': 'o',
                    '\u01FF': 'o',
                    '\u0254': 'o',
                    '\uA74B': 'o',
                    '\uA74D': 'o',
                    '\u0275': 'o',
                    '\u01A3': 'oi',
                    '\u0223': 'ou',
                    '\uA74F': 'oo',
                    '\u24DF': 'p',
                    '\uFF50': 'p',
                    '\u1E55': 'p',
                    '\u1E57': 'p',
                    '\u01A5': 'p',
                    '\u1D7D': 'p',
                    '\uA751': 'p',
                    '\uA753': 'p',
                    '\uA755': 'p',
                    '\u24E0': 'q',
                    '\uFF51': 'q',
                    '\u024B': 'q',
                    '\uA757': 'q',
                    '\uA759': 'q',
                    '\u24E1': 'r',
                    '\uFF52': 'r',
                    '\u0155': 'r',
                    '\u1E59': 'r',
                    '\u0159': 'r',
                    '\u0211': 'r',
                    '\u0213': 'r',
                    '\u1E5B': 'r',
                    '\u1E5D': 'r',
                    '\u0157': 'r',
                    '\u1E5F': 'r',
                    '\u024D': 'r',
                    '\u027D': 'r',
                    '\uA75B': 'r',
                    '\uA7A7': 'r',
                    '\uA783': 'r',
                    '\u24E2': 's',
                    '\uFF53': 's',
                    '\u00DF': 's',
                    '\u015B': 's',
                    '\u1E65': 's',
                    '\u015D': 's',
                    '\u1E61': 's',
                    '\u0161': 's',
                    '\u1E67': 's',
                    '\u1E63': 's',
                    '\u1E69': 's',
                    '\u0219': 's',
                    '\u015F': 's',
                    '\u023F': 's',
                    '\uA7A9': 's',
                    '\uA785': 's',
                    '\u1E9B': 's',
                    '\u24E3': 't',
                    '\uFF54': 't',
                    '\u1E6B': 't',
                    '\u1E97': 't',
                    '\u0165': 't',
                    '\u1E6D': 't',
                    '\u021B': 't',
                    '\u0163': 't',
                    '\u1E71': 't',
                    '\u1E6F': 't',
                    '\u0167': 't',
                    '\u01AD': 't',
                    '\u0288': 't',
                    '\u2C66': 't',
                    '\uA787': 't',
                    '\uA729': 'tz',
                    '\u24E4': 'u',
                    '\uFF55': 'u',
                    '\u00F9': 'u',
                    '\u00FA': 'u',
                    '\u00FB': 'u',
                    '\u0169': 'u',
                    '\u1E79': 'u',
                    '\u016B': 'u',
                    '\u1E7B': 'u',
                    '\u016D': 'u',
                    '\u00FC': 'u',
                    '\u01DC': 'u',
                    '\u01D8': 'u',
                    '\u01D6': 'u',
                    '\u01DA': 'u',
                    '\u1EE7': 'u',
                    '\u016F': 'u',
                    '\u0171': 'u',
                    '\u01D4': 'u',
                    '\u0215': 'u',
                    '\u0217': 'u',
                    '\u01B0': 'u',
                    '\u1EEB': 'u',
                    '\u1EE9': 'u',
                    '\u1EEF': 'u',
                    '\u1EED': 'u',
                    '\u1EF1': 'u',
                    '\u1EE5': 'u',
                    '\u1E73': 'u',
                    '\u0173': 'u',
                    '\u1E77': 'u',
                    '\u1E75': 'u',
                    '\u0289': 'u',
                    '\u24E5': 'v',
                    '\uFF56': 'v',
                    '\u1E7D': 'v',
                    '\u1E7F': 'v',
                    '\u028B': 'v',
                    '\uA75F': 'v',
                    '\u028C': 'v',
                    '\uA761': 'vy',
                    '\u24E6': 'w',
                    '\uFF57': 'w',
                    '\u1E81': 'w',
                    '\u1E83': 'w',
                    '\u0175': 'w',
                    '\u1E87': 'w',
                    '\u1E85': 'w',
                    '\u1E98': 'w',
                    '\u1E89': 'w',
                    '\u2C73': 'w',
                    '\u24E7': 'x',
                    '\uFF58': 'x',
                    '\u1E8B': 'x',
                    '\u1E8D': 'x',
                    '\u24E8': 'y',
                    '\uFF59': 'y',
                    '\u1EF3': 'y',
                    '\u00FD': 'y',
                    '\u0177': 'y',
                    '\u1EF9': 'y',
                    '\u0233': 'y',
                    '\u1E8F': 'y',
                    '\u00FF': 'y',
                    '\u1EF7': 'y',
                    '\u1E99': 'y',
                    '\u1EF5': 'y',
                    '\u01B4': 'y',
                    '\u024F': 'y',
                    '\u1EFF': 'y',
                    '\u24E9': 'z',
                    '\uFF5A': 'z',
                    '\u017A': 'z',
                    '\u1E91': 'z',
                    '\u017C': 'z',
                    '\u017E': 'z',
                    '\u1E93': 'z',
                    '\u1E95': 'z',
                    '\u01B6': 'z',
                    '\u0225': 'z',
                    '\u0240': 'z',
                    '\u2C6C': 'z',
                    '\uA763': 'z',
                    '\u0386': '\u0391',
                    '\u0388': '\u0395',
                    '\u0389': '\u0397',
                    '\u038A': '\u0399',
                    '\u03AA': '\u0399',
                    '\u038C': '\u039F',
                    '\u038E': '\u03A5',
                    '\u03AB': '\u03A5',
                    '\u038F': '\u03A9',
                    '\u03AC': '\u03B1',
                    '\u03AD': '\u03B5',
                    '\u03AE': '\u03B7',
                    '\u03AF': '\u03B9',
                    '\u03CA': '\u03B9',
                    '\u0390': '\u03B9',
                    '\u03CC': '\u03BF',
                    '\u03CD': '\u03C5',
                    '\u03CB': '\u03C5',
                    '\u03B0': '\u03C5',
                    '\u03C9': '\u03C9',
                    '\u03C2': '\u03C3'
                };

                return diacritics;
            });

            S2.define('pumselect2/data/base',[
                '../utils'
            ], function (Utils) {
                function BaseAdapter ($element, options) {
                    BaseAdapter.__super__.constructor.call(this);
                }

                Utils.Extend(BaseAdapter, Utils.Observable);

                BaseAdapter.prototype.current = function (callback) {
                    throw new Error('The `current` method must be defined in child classes.');
                };

                BaseAdapter.prototype.query = function (params, callback) {
                    throw new Error('The `query` method must be defined in child classes.');
                };

                BaseAdapter.prototype.bind = function (container, $container) {
                    // Can be implemented in subclasses
                };

                BaseAdapter.prototype.destroy = function () {
                    // Can be implemented in subclasses
                };

                BaseAdapter.prototype.generateResultId = function (container, data) {
                    var id = container.id + '-result-';

                    id += Utils.generateChars(4);

                    if (data.id != null) {
                        id += '-' + data.id.toString();
                    } else {
                        id += '-' + Utils.generateChars(4);
                    }
                    return id;
                };

                return BaseAdapter;
            });

            S2.define('pumselect2/data/select',[
                './base',
                '../utils',
                'jquery'
            ], function (BaseAdapter, Utils, $) {
                function SelectAdapter ($element, options) {
                    this.$element = $element;
                    this.options = options;

                    SelectAdapter.__super__.constructor.call(this);
                }

                Utils.Extend(SelectAdapter, BaseAdapter);

                SelectAdapter.prototype.current = function (callback) {
                    var data = [];
                    var self = this;

                    this.$element.find(':selected').each(function () {
                        var $option = $(this);

                        var option = self.item($option);

                        data.push(option);
                    });

                    callback(data);
                };

                SelectAdapter.prototype.select = function (data) {
                    var self = this;

                    data.selected = true;

                    // If data.element is a DOM node, use it instead
                    if ($(data.element).is('option')) {
                        data.element.selected = true;

                        this.$element.trigger('change');

                        return;
                    }

                    if (this.$element.prop('multiple')) {
                        this.current(function (currentData) {
                            var val = [];

                            data = [data];
                            data.push.apply(data, currentData);

                            for (var d = 0; d < data.length; d++) {
                                var id = data[d].id;

                                if ($.inArray(id, val) === -1) {
                                    val.push(id);
                                }
                            }

                            self.$element.val(val);
                            self.$element.trigger('change');
                        });
                    } else {
                        var val = data.id;

                        this.$element.val(val);
                        this.$element.trigger('change');
                    }
                };

                SelectAdapter.prototype.unselect = function (data) {
                    var self = this;

                    if (!this.$element.prop('multiple')) {
                        return;
                    }

                    data.selected = false;

                    if ($(data.element).is('option')) {
                        data.element.selected = false;

                        this.$element.trigger('change');

                        return;
                    }

                    this.current(function (currentData) {
                        var val = [];

                        for (var d = 0; d < currentData.length; d++) {
                            var id = currentData[d].id;

                            if (id !== data.id && $.inArray(id, val) === -1) {
                                val.push(id);
                            }
                        }

                        self.$element.val(val);

                        self.$element.trigger('change');
                    });
                };

                SelectAdapter.prototype.bind = function (container, $container) {
                    var self = this;

                    this.container = container;

                    container.on('select', function (params) {
                        self.select(params.data);
                    });

                    container.on('unselect', function (params) {
                        self.unselect(params.data);
                    });
                };

                SelectAdapter.prototype.destroy = function () {
                    // Remove anything added to child elements
                    this.$element.find('*').each(function () {
                        // Remove any custom data set by Select2
                        $.removeData(this, 'data');
                    });
                };

                SelectAdapter.prototype.query = function (params, callback) {
                    var data = [];
                    var self = this;

                    var $options = this.$element.children();

                    $options.each(function () {
                        var $option = $(this);

                        if (!$option.is('option') && !$option.is('optgroup')) {
                            return;
                        }

                        var option = self.item($option);

                        var matches = self.matches(params, option);

                        if (matches !== null) {
                            data.push(matches);
                        }
                    });

                    callback({
                        results: data
                    });
                };

                SelectAdapter.prototype.addOptions = function ($options) {
                    Utils.appendMany(this.$element, $options);
                };

                SelectAdapter.prototype.option = function (data) {
                    var option;

                    if (data.children) {
                        option = document.createElement('optgroup');
                        option.label = data.text;
                    } else {
                        option = document.createElement('option');

                        if (option.textContent !== undefined) {
                            option.textContent = data.text;
                        } else {
                            option.innerText = data.text;
                        }
                    }

                    if (data.id) {
                        option.value = data.id;
                    }

                    if (data.disabled) {
                        option.disabled = true;
                    }

                    if (data.selected) {
                        option.selected = true;
                    }

                    if (data.title) {
                        option.title = data.title;
                    }

                    var $option = $(option);

                    var normalizedData = this._normalizeItem(data);
                    normalizedData.element = option;

                    // Override the option's data with the combined data
                    $.data(option, 'data', normalizedData);

                    return $option;
                };

                SelectAdapter.prototype.item = function ($option) {
                    var data = {};

                    data = $.data($option[0], 'data');

                    if (data != null) {
                        return data;
                    }

                    if ($option.is('option')) {
                        data = {
                            id: $option.val(),
                            text: $option.text(),
                            disabled: $option.prop('disabled'),
                            selected: $option.prop('selected'),
                            title: $option.prop('title')
                        };
                    } else if ($option.is('optgroup')) {
                        data = {
                            text: $option.prop('label'),
                            children: [],
                            title: $option.prop('title')
                        };

                        var $children = $option.children('option');
                        var children = [];

                        for (var c = 0; c < $children.length; c++) {
                            var $child = $($children[c]);

                            var child = this.item($child);

                            children.push(child);
                        }

                        data.children = children;
                    }

                    data = this._normalizeItem(data);
                    data.element = $option[0];

                    $.data($option[0], 'data', data);

                    return data;
                };

                SelectAdapter.prototype._normalizeItem = function (item) {
                    if (!$.isPlainObject(item)) {
                        item = {
                            id: item,
                            text: item
                        };
                    }

                    item = $.extend({}, {
                        text: ''
                    }, item);

                    var defaults = {
                        selected: false,
                        disabled: false
                    };

                    if (item.id != null) {
                        item.id = item.id.toString();
                    }

                    if (item.text != null) {
                        item.text = item.text.toString();
                    }

                    if (item._resultId == null && item.id && this.container != null) {
                        item._resultId = this.generateResultId(this.container, item);
                    }

                    return $.extend({}, defaults, item);
                };

                SelectAdapter.prototype.matches = function (params, data) {
                    var matcher = this.options.get('matcher');

                    return matcher(params, data);
                };

                return SelectAdapter;
            });

            S2.define('pumselect2/data/array',[
                './select',
                '../utils',
                'jquery'
            ], function (SelectAdapter, Utils, $) {
                function ArrayAdapter ($element, options) {
                    var data = options.get('data') || [];

                    ArrayAdapter.__super__.constructor.call(this, $element, options);

                    this.addOptions(this.convertToOptions(data));
                }

                Utils.Extend(ArrayAdapter, SelectAdapter);

                ArrayAdapter.prototype.select = function (data) {
                    var $option = this.$element.find('option').filter(function (i, elm) {
                        return elm.value == data.id.toString();
                    });

                    if ($option.length === 0) {
                        $option = this.option(data);

                        this.addOptions($option);
                    }

                    ArrayAdapter.__super__.select.call(this, data);
                };

                ArrayAdapter.prototype.convertToOptions = function (data) {
                    var self = this;

                    var $existing = this.$element.find('option');
                    var existingIds = $existing.map(function () {
                        return self.item($(this)).id;
                    }).get();

                    var $options = [];

                    // Filter out all items except for the one passed in the argument
                    function onlyItem (item) {
                        return function () {
                            return $(this).val() == item.id;
                        };
                    }

                    for (var d = 0; d < data.length; d++) {
                        var item = this._normalizeItem(data[d]);

                        // Skip items which were pre-loaded, only merge the data
                        if ($.inArray(item.id, existingIds) >= 0) {
                            var $existingOption = $existing.filter(onlyItem(item));

                            var existingData = this.item($existingOption);
                            var newData = $.extend(true, {}, item, existingData);

                            var $newOption = this.option(newData);

                            $existingOption.replaceWith($newOption);

                            continue;
                        }

                        var $option = this.option(item);

                        if (item.children) {
                            var $children = this.convertToOptions(item.children);

                            Utils.appendMany($option, $children);
                        }

                        $options.push($option);
                    }

                    return $options;
                };

                return ArrayAdapter;
            });

            S2.define('pumselect2/data/ajax',[
                './array',
                '../utils',
                'jquery'
            ], function (ArrayAdapter, Utils, $) {
                function AjaxAdapter ($element, options) {
                    this.ajaxOptions = this._applyDefaults(options.get('ajax'));

                    if (this.ajaxOptions.processResults != null) {
                        this.processResults = this.ajaxOptions.processResults;
                    }

                    AjaxAdapter.__super__.constructor.call(this, $element, options);
                }

                Utils.Extend(AjaxAdapter, ArrayAdapter);

                AjaxAdapter.prototype._applyDefaults = function (options) {
                    var defaults = {
                        data: function (params) {
                            return $.extend({}, params, {
                                q: params.term
                            });
                        },
                        transport: function (params, success, failure) {
                            var $request = $.ajax(params);

                            $request.then(success);
                            $request.fail(failure);

                            return $request;
                        }
                    };

                    return $.extend({}, defaults, options, true);
                };

                AjaxAdapter.prototype.processResults = function (results) {
                    return results;
                };

                AjaxAdapter.prototype.query = function (params, callback) {
                    var matches = [];
                    var self = this;

                    if (this._request != null) {
                        // JSONP requests cannot always be aborted
                        if ($.isFunction(this._request.abort)) {
                            this._request.abort();
                        }

                        this._request = null;
                    }

                    var options = $.extend({
                        type: 'GET'
                    }, this.ajaxOptions);

                    if (typeof options.url === 'function') {
                        options.url = options.url.call(this.$element, params);
                    }

                    if (typeof options.data === 'function') {
                        options.data = options.data.call(this.$element, params);
                    }

                    function request () {
                        var $request = options.transport(options, function (data) {
                            var results = self.processResults(data, params);

                            if (self.options.get('debug') && window.console && console.error) {
                                // Check to make sure that the response included a `results` key.
                                if (!results || !results.results || !$.isArray(results.results)) {
                                    console.error(
                                        'Select2: The AJAX results did not return an array in the ' +
                                        '`results` key of the response.'
                                    );
                                }
                            }

                            callback(results);
                        }, function () {
                            self.trigger('results:message', {
                                message: 'errorLoading'
                            });
                        });

                        self._request = $request;
                    }

                    if (this.ajaxOptions.delay && params.term !== '') {
                        if (this._queryTimeout) {
                            window.clearTimeout(this._queryTimeout);
                        }

                        this._queryTimeout = window.setTimeout(request, this.ajaxOptions.delay);
                    } else {
                        request();
                    }
                };

                return AjaxAdapter;
            });

            S2.define('pumselect2/data/tags',[
                'jquery'
            ], function ($) {
                function Tags (decorated, $element, options) {
                    var tags = options.get('tags');

                    var createTag = options.get('createTag');

                    if (createTag !== undefined) {
                        this.createTag = createTag;
                    }

                    var insertTag = options.get('insertTag');

                    if (insertTag !== undefined) {
                        this.insertTag = insertTag;
                    }

                    decorated.call(this, $element, options);

                    if ($.isArray(tags)) {
                        for (var t = 0; t < tags.length; t++) {
                            var tag = tags[t];
                            var item = this._normalizeItem(tag);

                            var $option = this.option(item);

                            this.$element.append($option);
                        }
                    }
                }

                Tags.prototype.query = function (decorated, params, callback) {
                    var self = this;

                    this._removeOldTags();

                    if (params.term == null || params.page != null) {
                        decorated.call(this, params, callback);
                        return;
                    }

                    function wrapper (obj, child) {
                        var data = obj.results;

                        for (var i = 0; i < data.length; i++) {
                            var option = data[i];

                            var checkChildren = (
                                option.children != null &&
                                !wrapper({
                                    results: option.children
                                }, true)
                            );

                            var checkText = option.text === params.term;

                            if (checkText || checkChildren) {
                                if (child) {
                                    return false;
                                }

                                obj.data = data;
                                callback(obj);

                                return;
                            }
                        }

                        if (child) {
                            return true;
                        }

                        var tag = self.createTag(params);

                        if (tag != null) {
                            var $option = self.option(tag);
                            $option.attr('data-pumselect2-tag', true);

                            self.addOptions([$option]);

                            self.insertTag(data, tag);
                        }

                        obj.results = data;

                        callback(obj);
                    }

                    decorated.call(this, params, wrapper);
                };

                Tags.prototype.createTag = function (decorated, params) {
                    var term = $.trim(params.term);

                    if (term === '') {
                        return null;
                    }

                    return {
                        id: term,
                        text: term
                    };
                };

                Tags.prototype.insertTag = function (_, data, tag) {
                    data.unshift(tag);
                };

                Tags.prototype._removeOldTags = function (_) {
                    var tag = this._lastTag;

                    var $options = this.$element.find('option[data-pumselect2-tag]');

                    $options.each(function () {
                        if (this.selected) {
                            return;
                        }

                        $(this).remove();
                    });
                };

                return Tags;
            });

            S2.define('pumselect2/data/tokenizer',[
                'jquery'
            ], function ($) {
                function Tokenizer (decorated, $element, options) {
                    var tokenizer = options.get('tokenizer');

                    if (tokenizer !== undefined) {
                        this.tokenizer = tokenizer;
                    }

                    decorated.call(this, $element, options);
                }

                Tokenizer.prototype.bind = function (decorated, container, $container) {
                    decorated.call(this, container, $container);

                    this.$search =  container.dropdown.$search || container.selection.$search ||
                        $container.find('.pumselect2-search__field');
                };

                Tokenizer.prototype.query = function (decorated, params, callback) {
                    var self = this;

                    function select (data) {
                        self.trigger('select', {
                            data: data
                        });
                    }

                    params.term = params.term || '';

                    var tokenData = this.tokenizer(params, this.options, select);

                    if (tokenData.term !== params.term) {
                        // Replace the search term if we have the search box
                        if (this.$search.length) {
                            this.$search.val(tokenData.term);
                            this.$search.focus();
                        }

                        params.term = tokenData.term;
                    }

                    decorated.call(this, params, callback);
                };

                Tokenizer.prototype.tokenizer = function (_, params, options, callback) {
                    var separators = options.get('tokenSeparators') || [];
                    var term = params.term;
                    var i = 0;

                    var createTag = this.createTag || function (params) {
                            return {
                                id: params.term,
                                text: params.term
                            };
                        };

                    while (i < term.length) {
                        var termChar = term[i];

                        if ($.inArray(termChar, separators) === -1) {
                            i++;

                            continue;
                        }

                        var part = term.substr(0, i);
                        var partParams = $.extend({}, params, {
                            term: part
                        });

                        var data = createTag(partParams);

                        if (data == null) {
                            i++;
                            continue;
                        }

                        callback(data);

                        // Reset the term to not include the tokenized portion
                        term = term.substr(i + 1) || '';
                        i = 0;
                    }

                    return {
                        term: term
                    };
                };

                return Tokenizer;
            });

            S2.define('pumselect2/data/minimumInputLength',[

            ], function () {
                function MinimumInputLength (decorated, $e, options) {
                    this.minimumInputLength = options.get('minimumInputLength');

                    decorated.call(this, $e, options);
                }

                MinimumInputLength.prototype.query = function (decorated, params, callback) {
                    params.term = params.term || '';

                    if (params.term.length < this.minimumInputLength) {
                        this.trigger('results:message', {
                            message: 'inputTooShort',
                            args: {
                                minimum: this.minimumInputLength,
                                input: params.term,
                                params: params
                            }
                        });

                        return;
                    }

                    decorated.call(this, params, callback);
                };

                return MinimumInputLength;
            });

            S2.define('pumselect2/data/maximumInputLength',[

            ], function () {
                function MaximumInputLength (decorated, $e, options) {
                    this.maximumInputLength = options.get('maximumInputLength');

                    decorated.call(this, $e, options);
                }

                MaximumInputLength.prototype.query = function (decorated, params, callback) {
                    params.term = params.term || '';

                    if (this.maximumInputLength > 0 &&
                        params.term.length > this.maximumInputLength) {
                        this.trigger('results:message', {
                            message: 'inputTooLong',
                            args: {
                                maximum: this.maximumInputLength,
                                input: params.term,
                                params: params
                            }
                        });

                        return;
                    }

                    decorated.call(this, params, callback);
                };

                return MaximumInputLength;
            });

            S2.define('pumselect2/data/maximumSelectionLength',[

            ], function (){
                function MaximumSelectionLength (decorated, $e, options) {
                    this.maximumSelectionLength = options.get('maximumSelectionLength');

                    decorated.call(this, $e, options);
                }

                MaximumSelectionLength.prototype.query =
                    function (decorated, params, callback) {
                        var self = this;

                        this.current(function (currentData) {
                            var count = currentData != null ? currentData.length : 0;
                            if (self.maximumSelectionLength > 0 &&
                                count >= self.maximumSelectionLength) {
                                self.trigger('results:message', {
                                    message: 'maximumSelected',
                                    args: {
                                        maximum: self.maximumSelectionLength
                                    }
                                });
                                return;
                            }
                            decorated.call(self, params, callback);
                        });
                    };

                return MaximumSelectionLength;
            });

            S2.define('pumselect2/dropdown',[
                'jquery',
                './utils'
            ], function ($, Utils) {
                function Dropdown ($element, options) {
                    this.$element = $element;
                    this.options = options;

                    Dropdown.__super__.constructor.call(this);
                }

                Utils.Extend(Dropdown, Utils.Observable);

                Dropdown.prototype.render = function () {
                    var $dropdown = $(
                        '<span class="pumselect2-dropdown">' +
                        '<span class="pumselect2-results"></span>' +
                        '</span>'
                    );

                    $dropdown.attr('dir', this.options.get('dir'));

                    this.$dropdown = $dropdown;

                    return $dropdown;
                };

                Dropdown.prototype.bind = function () {
                    // Should be implemented in subclasses
                };

                Dropdown.prototype.position = function ($dropdown, $container) {
                    // Should be implmented in subclasses
                };

                Dropdown.prototype.destroy = function () {
                    // Remove the dropdown from the DOM
                    this.$dropdown.remove();
                };

                return Dropdown;
            });

            S2.define('pumselect2/dropdown/search',[
                'jquery',
                '../utils'
            ], function ($, Utils) {
                function Search () { }

                Search.prototype.render = function (decorated) {
                    var $rendered = decorated.call(this);

                    var $search = $(
                        '<span class="pumselect2-search pumselect2-search--dropdown">' +
                        '<input class="pumselect2-search__field" type="search" tabindex="-1"' +
                        ' autocomplete="off" autocorrect="off" autocapitalize="off"' +
                        ' spellcheck="false" role="textbox" />' +
                        '</span>'
                    );

                    this.$searchContainer = $search;
                    this.$search = $search.find('input');

                    $rendered.prepend($search);

                    return $rendered;
                };

                Search.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    this.$search.on('keydown', function (evt) {
                        self.trigger('keypress', evt);

                        self._keyUpPrevented = evt.isDefaultPrevented();
                    });

                    // Workaround for browsers which do not support the `input` event
                    // This will prevent double-triggering of events for browsers which support
                    // both the `keyup` and `input` events.
                    this.$search.on('input', function (evt) {
                        // Unbind the duplicated `keyup` event
                        $(this).off('keyup');
                    });

                    this.$search.on('keyup input', function (evt) {
                        self.handleSearch(evt);
                    });

                    container.on('open', function () {
                        self.$search.attr('tabindex', 0);

                        self.$search.focus();

                        window.setTimeout(function () {
                            self.$search.focus();
                        }, 0);
                    });

                    container.on('close', function () {
                        self.$search.attr('tabindex', -1);

                        self.$search.val('');
                    });

                    container.on('results:all', function (params) {
                        if (params.query.term == null || params.query.term === '') {
                            var showSearch = self.showSearch(params);

                            if (showSearch) {
                                self.$searchContainer.removeClass('pumselect2-search--hide');
                            } else {
                                self.$searchContainer.addClass('pumselect2-search--hide');
                            }
                        }
                    });
                };

                Search.prototype.handleSearch = function (evt) {
                    if (!this._keyUpPrevented) {
                        var input = this.$search.val();

                        this.trigger('query', {
                            term: input
                        });
                    }

                    this._keyUpPrevented = false;
                };

                Search.prototype.showSearch = function (_, params) {
                    return true;
                };

                return Search;
            });

            S2.define('pumselect2/dropdown/hidePlaceholder',[

            ], function () {
                function HidePlaceholder (decorated, $element, options, dataAdapter) {
                    this.placeholder = this.normalizePlaceholder(options.get('placeholder'));

                    decorated.call(this, $element, options, dataAdapter);
                }

                HidePlaceholder.prototype.append = function (decorated, data) {
                    data.results = this.removePlaceholder(data.results);

                    decorated.call(this, data);
                };

                HidePlaceholder.prototype.normalizePlaceholder = function (_, placeholder) {
                    if (typeof placeholder === 'string') {
                        placeholder = {
                            id: '',
                            text: placeholder
                        };
                    }

                    return placeholder;
                };

                HidePlaceholder.prototype.removePlaceholder = function (_, data) {
                    var modifiedData = data.slice(0);

                    for (var d = data.length - 1; d >= 0; d--) {
                        var item = data[d];

                        if (this.placeholder.id === item.id) {
                            modifiedData.splice(d, 1);
                        }
                    }

                    return modifiedData;
                };

                return HidePlaceholder;
            });

            S2.define('pumselect2/dropdown/infiniteScroll',[
                'jquery'
            ], function ($) {
                function InfiniteScroll (decorated, $element, options, dataAdapter) {
                    this.lastParams = {};

                    decorated.call(this, $element, options, dataAdapter);

                    this.$loadingMore = this.createLoadingMore();
                    this.loading = false;
                }

                InfiniteScroll.prototype.append = function (decorated, data) {
                    this.$loadingMore.remove();
                    this.loading = false;

                    decorated.call(this, data);

                    if (this.showLoadingMore(data)) {
                        this.$results.append(this.$loadingMore);
                    }
                };

                InfiniteScroll.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    container.on('query', function (params) {
                        self.lastParams = params;
                        self.loading = true;
                    });

                    container.on('query:append', function (params) {
                        self.lastParams = params;
                        self.loading = true;
                    });

                    this.$results.on('scroll', function () {
                        var isLoadMoreVisible = $.contains(
                            document.documentElement,
                            self.$loadingMore[0]
                        );

                        if (self.loading || !isLoadMoreVisible) {
                            return;
                        }

                        var currentOffset = self.$results.offset().top +
                            self.$results.outerHeight(false);
                        var loadingMoreOffset = self.$loadingMore.offset().top +
                            self.$loadingMore.outerHeight(false);

                        if (currentOffset + 50 >= loadingMoreOffset) {
                            self.loadMore();
                        }
                    });
                };

                InfiniteScroll.prototype.loadMore = function () {
                    this.loading = true;

                    var params = $.extend({}, {page: 1}, this.lastParams);

                    params.page++;

                    this.trigger('query:append', params);
                };

                InfiniteScroll.prototype.showLoadingMore = function (_, data) {
                    return data.pagination && data.pagination.more;
                };

                InfiniteScroll.prototype.createLoadingMore = function () {
                    var $option = $(
                        '<li ' +
                        'class="pumselect2-results__option pumselect2-results__option--load-more"' +
                        'role="treeitem" aria-disabled="true"></li>'
                    );

                    var message = this.options.get('translations').get('loadingMore');

                    $option.html(message(this.lastParams));

                    return $option;
                };

                return InfiniteScroll;
            });

            S2.define('pumselect2/dropdown/attachBody',[
                'jquery',
                '../utils'
            ], function ($, Utils) {
                function AttachBody (decorated, $element, options) {
                    this.$dropdownParent = options.get('dropdownParent') || $(document.body);

                    decorated.call(this, $element, options);
                }

                AttachBody.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    var setupResultsEvents = false;

                    decorated.call(this, container, $container);

                    container.on('open', function () {
                        self._showDropdown();
                        self._attachPositioningHandler(container);

                        if (!setupResultsEvents) {
                            setupResultsEvents = true;

                            container.on('results:all', function () {
                                self._positionDropdown();
                                self._resizeDropdown();
                            });

                            container.on('results:append', function () {
                                self._positionDropdown();
                                self._resizeDropdown();
                            });
                        }
                    });

                    container.on('close', function () {
                        self._hideDropdown();
                        self._detachPositioningHandler(container);
                    });

                    this.$dropdownContainer.on('mousedown', function (evt) {
                        evt.stopPropagation();
                    });
                };

                AttachBody.prototype.destroy = function (decorated) {
                    decorated.call(this);

                    this.$dropdownContainer.remove();
                };

                AttachBody.prototype.position = function (decorated, $dropdown, $container) {
                    // Clone all of the container classes
                    $dropdown.attr('class', $container.attr('class'));

                    $dropdown.removeClass('pumselect2');
                    $dropdown.addClass('pumselect2-container--open');

                    $dropdown.css({
                        position: 'absolute',
                        top: -999999
                    });

                    this.$container = $container;
                };

                AttachBody.prototype.render = function (decorated) {
                    var $container = $('<span></span>');

                    var $dropdown = decorated.call(this);
                    $container.append($dropdown);

                    this.$dropdownContainer = $container;

                    return $container;
                };

                AttachBody.prototype._hideDropdown = function (decorated) {
                    this.$dropdownContainer.detach();
                };

                AttachBody.prototype._attachPositioningHandler =
                    function (decorated, container) {
                        var self = this;

                        var scrollEvent = 'scroll.pumselect2.' + container.id;
                        var resizeEvent = 'resize.pumselect2.' + container.id;
                        var orientationEvent = 'orientationchange.pumselect2.' + container.id;

                        var $watchers = this.$container.parents().filter(Utils.hasScroll);
                        $watchers.each(function () {
                            $(this).data('pumselect2-scroll-position', {
                                x: $(this).scrollLeft(),
                                y: $(this).scrollTop()
                            });
                        });

                        $watchers.on(scrollEvent, function (ev) {
                            var position = $(this).data('pumselect2-scroll-position');
                            $(this).scrollTop(position.y);
                        });

                        $(window).on(scrollEvent + ' ' + resizeEvent + ' ' + orientationEvent,
                            function (e) {
                                self._positionDropdown();
                                self._resizeDropdown();
                            });
                    };

                AttachBody.prototype._detachPositioningHandler =
                    function (decorated, container) {
                        var scrollEvent = 'scroll.pumselect2.' + container.id;
                        var resizeEvent = 'resize.pumselect2.' + container.id;
                        var orientationEvent = 'orientationchange.pumselect2.' + container.id;

                        var $watchers = this.$container.parents().filter(Utils.hasScroll);
                        $watchers.off(scrollEvent);

                        $(window).off(scrollEvent + ' ' + resizeEvent + ' ' + orientationEvent);
                    };

                AttachBody.prototype._positionDropdown = function () {
                    var $window = $(window);

                    var isCurrentlyAbove = this.$dropdown.hasClass('pumselect2-dropdown--above');
                    var isCurrentlyBelow = this.$dropdown.hasClass('pumselect2-dropdown--below');

                    var newDirection = null;

                    var offset = this.$container.offset();

                    offset.bottom = offset.top + this.$container.outerHeight(false);

                    var container = {
                        height: this.$container.outerHeight(false)
                    };

                    container.top = offset.top;
                    container.bottom = offset.top + container.height;

                    var dropdown = {
                        height: this.$dropdown.outerHeight(false)
                    };

                    var viewport = {
                        top: $window.scrollTop(),
                        bottom: $window.scrollTop() + $window.height()
                    };

                    var enoughRoomAbove = viewport.top < (offset.top - dropdown.height);
                    var enoughRoomBelow = viewport.bottom > (offset.bottom + dropdown.height);

                    var css = {
                        left: offset.left,
                        top: container.bottom
                    };

                    // Determine what the parent element is to use for calciulating the offset
                    var $offsetParent = this.$dropdownParent;

                    // For statically positoned elements, we need to get the element
                    // that is determining the offset
                    if ($offsetParent.css('position') === 'static') {
                        $offsetParent = $offsetParent.offsetParent();
                    }

                    var parentOffset = $offsetParent.offset();

                    css.top -= parentOffset.top;
                    css.left -= parentOffset.left;

                    if (!isCurrentlyAbove && !isCurrentlyBelow) {
                        newDirection = 'below';
                    }

                    if (!enoughRoomBelow && enoughRoomAbove && !isCurrentlyAbove) {
                        newDirection = 'above';
                    } else if (!enoughRoomAbove && enoughRoomBelow && isCurrentlyAbove) {
                        newDirection = 'below';
                    }

                    if (newDirection == 'above' ||
                        (isCurrentlyAbove && newDirection !== 'below')) {
                        css.top = container.top - dropdown.height;
                    }

                    if (newDirection != null) {
                        this.$dropdown
                            .removeClass('pumselect2-dropdown--below pumselect2-dropdown--above')
                            .addClass('pumselect2-dropdown--' + newDirection);
                        this.$container
                            .removeClass('pumselect2-container--below pumselect2-container--above')
                            .addClass('pumselect2-container--' + newDirection);
                    }

                    this.$dropdownContainer.css(css);
                };

                AttachBody.prototype._resizeDropdown = function () {
                    var css = {
                        width: this.$container.outerWidth(false) + 'px'
                    };

                    if (this.options.get('dropdownAutoWidth')) {
                        css.minWidth = css.width;
                        css.width = 'auto';
                    }

                    this.$dropdown.css(css);
                };

                AttachBody.prototype._showDropdown = function (decorated) {
                    this.$dropdownContainer.appendTo(this.$dropdownParent);

                    this._positionDropdown();
                    this._resizeDropdown();
                };

                return AttachBody;
            });

            S2.define('pumselect2/dropdown/minimumResultsForSearch',[

            ], function () {
                function countResults (data) {
                    var count = 0;

                    for (var d = 0; d < data.length; d++) {
                        var item = data[d];

                        if (item.children) {
                            count += countResults(item.children);
                        } else {
                            count++;
                        }
                    }

                    return count;
                }

                function MinimumResultsForSearch (decorated, $element, options, dataAdapter) {
                    this.minimumResultsForSearch = options.get('minimumResultsForSearch');

                    if (this.minimumResultsForSearch < 0) {
                        this.minimumResultsForSearch = Infinity;
                    }

                    decorated.call(this, $element, options, dataAdapter);
                }

                MinimumResultsForSearch.prototype.showSearch = function (decorated, params) {
                    if (countResults(params.data.results) < this.minimumResultsForSearch) {
                        return false;
                    }

                    return decorated.call(this, params);
                };

                return MinimumResultsForSearch;
            });

            S2.define('pumselect2/dropdown/selectOnClose',[

            ], function () {
                function SelectOnClose () { }

                SelectOnClose.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    container.on('close', function () {
                        self._handleSelectOnClose();
                    });
                };

                SelectOnClose.prototype._handleSelectOnClose = function () {
                    var $highlightedResults = this.getHighlightedResults();

                    // Only select highlighted results
                    if ($highlightedResults.length < 1) {
                        return;
                    }

                    var data = $highlightedResults.data('data');

                    // Don't re-select already selected resulte
                    if (
                        (data.element != null && data.element.selected) ||
                        (data.element == null && data.selected)
                    ) {
                        return;
                    }

                    this.trigger('select', {
                        data: data
                    });
                };

                return SelectOnClose;
            });

            S2.define('pumselect2/dropdown/closeOnSelect',[

            ], function () {
                function CloseOnSelect () { }

                CloseOnSelect.prototype.bind = function (decorated, container, $container) {
                    var self = this;

                    decorated.call(this, container, $container);

                    container.on('select', function (evt) {
                        self._selectTriggered(evt);
                    });

                    container.on('unselect', function (evt) {
                        self._selectTriggered(evt);
                    });
                };

                CloseOnSelect.prototype._selectTriggered = function (_, evt) {
                    var originalEvent = evt.originalEvent;

                    // Don't close if the control key is being held
                    if (originalEvent && originalEvent.ctrlKey) {
                        return;
                    }

                    this.trigger('close', {});
                };

                return CloseOnSelect;
            });

            S2.define('pumselect2/i18n/en',[],function () {
                // English
                return {
                    errorLoading: function () {
                        return 'The results could not be loaded.';
                    },
                    inputTooLong: function (args) {
                        var overChars = args.input.length - args.maximum;

                        var message = 'Please delete ' + overChars + ' character';

                        if (overChars != 1) {
                            message += 's';
                        }

                        return message;
                    },
                    inputTooShort: function (args) {
                        var remainingChars = args.minimum - args.input.length;

                        var message = 'Please enter ' + remainingChars + ' or more characters';

                        return message;
                    },
                    loadingMore: function () {
                        return 'Loading more results…';
                    },
                    maximumSelected: function (args) {
                        var message = 'You can only select ' + args.maximum + ' item';

                        if (args.maximum != 1) {
                            message += 's';
                        }

                        return message;
                    },
                    noResults: function () {
                        return 'No results found';
                    },
                    searching: function () {
                        return 'Searching…';
                    }
                };
            });

            S2.define('pumselect2/defaults',[
                'jquery',
                'require',

                './results',

                './selection/single',
                './selection/multiple',
                './selection/placeholder',
                './selection/allowClear',
                './selection/search',
                './selection/eventRelay',

                './utils',
                './translation',
                './diacritics',

                './data/select',
                './data/array',
                './data/ajax',
                './data/tags',
                './data/tokenizer',
                './data/minimumInputLength',
                './data/maximumInputLength',
                './data/maximumSelectionLength',

                './dropdown',
                './dropdown/search',
                './dropdown/hidePlaceholder',
                './dropdown/infiniteScroll',
                './dropdown/attachBody',
                './dropdown/minimumResultsForSearch',
                './dropdown/selectOnClose',
                './dropdown/closeOnSelect',

                './i18n/en'
            ], function ($, require,

                         ResultsList,

                         SingleSelection, MultipleSelection, Placeholder, AllowClear,
                         SelectionSearch, EventRelay,

                         Utils, Translation, DIACRITICS,

                         SelectData, ArrayData, AjaxData, Tags, Tokenizer,
                         MinimumInputLength, MaximumInputLength, MaximumSelectionLength,

                         Dropdown, DropdownSearch, HidePlaceholder, InfiniteScroll,
                         AttachBody, MinimumResultsForSearch, SelectOnClose, CloseOnSelect,

                         EnglishTranslation) {
                function Defaults () {
                    this.reset();
                }

                Defaults.prototype.apply = function (options) {
                    options = $.extend(true, {}, this.defaults, options);

                    if (options.dataAdapter == null) {
                        if (options.ajax != null) {
                            options.dataAdapter = AjaxData;
                        } else if (options.data != null) {
                            options.dataAdapter = ArrayData;
                        } else {
                            options.dataAdapter = SelectData;
                        }

                        if (options.minimumInputLength > 0) {
                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                MinimumInputLength
                            );
                        }

                        if (options.maximumInputLength > 0) {
                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                MaximumInputLength
                            );
                        }

                        if (options.maximumSelectionLength > 0) {
                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                MaximumSelectionLength
                            );
                        }

                        if (options.tags) {
                            options.dataAdapter = Utils.Decorate(options.dataAdapter, Tags);
                        }

                        if (options.tokenSeparators != null || options.tokenizer != null) {
                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                Tokenizer
                            );
                        }

                        if (options.query != null) {
                            var Query = require(options.amdBase + 'compat/query');

                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                Query
                            );
                        }

                        if (options.initSelection != null) {
                            var InitSelection = require(options.amdBase + 'compat/initSelection');

                            options.dataAdapter = Utils.Decorate(
                                options.dataAdapter,
                                InitSelection
                            );
                        }
                    }

                    if (options.resultsAdapter == null) {
                        options.resultsAdapter = ResultsList;

                        if (options.ajax != null) {
                            options.resultsAdapter = Utils.Decorate(
                                options.resultsAdapter,
                                InfiniteScroll
                            );
                        }

                        if (options.placeholder != null) {
                            options.resultsAdapter = Utils.Decorate(
                                options.resultsAdapter,
                                HidePlaceholder
                            );
                        }

                        if (options.selectOnClose) {
                            options.resultsAdapter = Utils.Decorate(
                                options.resultsAdapter,
                                SelectOnClose
                            );
                        }
                    }

                    if (options.dropdownAdapter == null) {
                        if (options.multiple) {
                            options.dropdownAdapter = Dropdown;
                        } else {
                            var SearchableDropdown = Utils.Decorate(Dropdown, DropdownSearch);

                            options.dropdownAdapter = SearchableDropdown;
                        }

                        if (options.minimumResultsForSearch !== 0) {
                            options.dropdownAdapter = Utils.Decorate(
                                options.dropdownAdapter,
                                MinimumResultsForSearch
                            );
                        }

                        if (options.closeOnSelect) {
                            options.dropdownAdapter = Utils.Decorate(
                                options.dropdownAdapter,
                                CloseOnSelect
                            );
                        }

                        if (
                            options.dropdownCssClass != null ||
                            options.dropdownCss != null ||
                            options.adaptDropdownCssClass != null
                        ) {
                            var DropdownCSS = require(options.amdBase + 'compat/dropdownCss');

                            options.dropdownAdapter = Utils.Decorate(
                                options.dropdownAdapter,
                                DropdownCSS
                            );
                        }

                        options.dropdownAdapter = Utils.Decorate(
                            options.dropdownAdapter,
                            AttachBody
                        );
                    }

                    if (options.selectionAdapter == null) {
                        if (options.multiple) {
                            options.selectionAdapter = MultipleSelection;
                        } else {
                            options.selectionAdapter = SingleSelection;
                        }

                        // Add the placeholder mixin if a placeholder was specified
                        if (options.placeholder != null) {
                            options.selectionAdapter = Utils.Decorate(
                                options.selectionAdapter,
                                Placeholder
                            );
                        }

                        if (options.allowClear) {
                            options.selectionAdapter = Utils.Decorate(
                                options.selectionAdapter,
                                AllowClear
                            );
                        }

                        if (options.multiple) {
                            options.selectionAdapter = Utils.Decorate(
                                options.selectionAdapter,
                                SelectionSearch
                            );
                        }

                        if (
                            options.containerCssClass != null ||
                            options.containerCss != null ||
                            options.adaptContainerCssClass != null
                        ) {
                            var ContainerCSS = require(options.amdBase + 'compat/containerCss');

                            options.selectionAdapter = Utils.Decorate(
                                options.selectionAdapter,
                                ContainerCSS
                            );
                        }

                        options.selectionAdapter = Utils.Decorate(
                            options.selectionAdapter,
                            EventRelay
                        );
                    }

                    if (typeof options.language === 'string') {
                        // Check if the language is specified with a region
                        if (options.language.indexOf('-') > 0) {
                            // Extract the region information if it is included
                            var languageParts = options.language.split('-');
                            var baseLanguage = languageParts[0];

                            options.language = [options.language, baseLanguage];
                        } else {
                            options.language = [options.language];
                        }
                    }

                    if ($.isArray(options.language)) {
                        var languages = new Translation();
                        options.language.push('en');

                        var languageNames = options.language;

                        for (var l = 0; l < languageNames.length; l++) {
                            var name = languageNames[l];
                            var language = {};

                            try {
                                // Try to load it with the original name
                                language = Translation.loadPath(name);
                            } catch (e) {
                                try {
                                    // If we couldn't load it, check if it wasn't the full path
                                    name = this.defaults.amdLanguageBase + name;
                                    language = Translation.loadPath(name);
                                } catch (ex) {
                                    // The translation could not be loaded at all. Sometimes this is
                                    // because of a configuration problem, other times this can be
                                    // because of how Select2 helps load all possible translation files.
                                    if (options.debug && window.console && console.warn) {
                                        console.warn(
                                            'Select2: The language file for "' + name + '" could not be ' +
                                            'automatically loaded. A fallback will be used instead.'
                                        );
                                    }

                                    continue;
                                }
                            }

                            languages.extend(language);
                        }

                        options.translations = languages;
                    } else {
                        var baseTranslation = Translation.loadPath(
                            this.defaults.amdLanguageBase + 'en'
                        );
                        var customTranslation = new Translation(options.language);

                        customTranslation.extend(baseTranslation);

                        options.translations = customTranslation;
                    }

                    return options;
                };

                Defaults.prototype.reset = function () {
                    function stripDiacritics (text) {
                        // Used 'uni range + named function' from http://jsperf.com/diacritics/18
                        function match(a) {
                            return DIACRITICS[a] || a;
                        }

                        return text.replace(/[^\u0000-\u007E]/g, match);
                    }

                    function matcher (params, data) {
                        // Always return the object if there is nothing to compare
                        if ($.trim(params.term) === '') {
                            return data;
                        }

                        // Do a recursive check for options with children
                        if (data.children && data.children.length > 0) {
                            // Clone the data object if there are children
                            // This is required as we modify the object to remove any non-matches
                            var match = $.extend(true, {}, data);

                            // Check each child of the option
                            for (var c = data.children.length - 1; c >= 0; c--) {
                                var child = data.children[c];

                                var matches = matcher(params, child);

                                // If there wasn't a match, remove the object in the array
                                if (matches == null) {
                                    match.children.splice(c, 1);
                                }
                            }

                            // If any children matched, return the new object
                            if (match.children.length > 0) {
                                return match;
                            }

                            // If there were no matching children, check just the plain object
                            return matcher(params, match);
                        }

                        var original = stripDiacritics(data.text).toUpperCase();
                        var term = stripDiacritics(params.term).toUpperCase();

                        // Check if the text contains the term
                        if (original.indexOf(term) > -1) {
                            return data;
                        }

                        // If it doesn't contain the term, don't return anything
                        return null;
                    }

                    this.defaults = {
                        amdBase: './',
                        amdLanguageBase: './i18n/',
                        closeOnSelect: true,
                        debug: false,
                        dropdownAutoWidth: false,
                        escapeMarkup: Utils.escapeMarkup,
                        language: EnglishTranslation,
                        matcher: matcher,
                        minimumInputLength: 0,
                        maximumInputLength: 0,
                        maximumSelectionLength: 0,
                        minimumResultsForSearch: 0,
                        selectOnClose: false,
                        sorter: function (data) {
                            return data;
                        },
                        templateResult: function (result) {
                            return result.text;
                        },
                        templateSelection: function (selection) {
                            return selection.text;
                        },
                        theme: 'default',
                        width: 'resolve'
                    };
                };

                Defaults.prototype.set = function (key, value) {
                    var camelKey = $.camelCase(key);

                    var data = {};
                    data[camelKey] = value;

                    var convertedData = Utils._convertData(data);

                    $.extend(this.defaults, convertedData);
                };

                var defaults = new Defaults();

                return defaults;
            });

            S2.define('pumselect2/options',[
                'require',
                'jquery',
                './defaults',
                './utils'
            ], function (require, $, Defaults, Utils) {
                function Options (options, $element) {
                    this.options = options;

                    if ($element != null) {
                        this.fromElement($element);
                    }

                    this.options = Defaults.apply(this.options);

                    if ($element && $element.is('input')) {
                        var InputCompat = require(this.get('amdBase') + 'compat/inputData');

                        this.options.dataAdapter = Utils.Decorate(
                            this.options.dataAdapter,
                            InputCompat
                        );
                    }
                }

                Options.prototype.fromElement = function ($e) {
                    var excludedData = ['pumselect2'];

                    if (this.options.multiple == null) {
                        this.options.multiple = $e.prop('multiple');
                    }

                    if (this.options.disabled == null) {
                        this.options.disabled = $e.prop('disabled');
                    }

                    if (this.options.language == null) {
                        if ($e.prop('lang')) {
                            this.options.language = $e.prop('lang').toLowerCase();
                        } else if ($e.closest('[lang]').prop('lang')) {
                            this.options.language = $e.closest('[lang]').prop('lang');
                        }
                    }

                    if (this.options.dir == null) {
                        if ($e.prop('dir')) {
                            this.options.dir = $e.prop('dir');
                        } else if ($e.closest('[dir]').prop('dir')) {
                            this.options.dir = $e.closest('[dir]').prop('dir');
                        } else {
                            this.options.dir = 'ltr';
                        }
                    }

                    $e.prop('disabled', this.options.disabled);
                    $e.prop('multiple', this.options.multiple);

                    if ($e.data('pumselect2Tags')) {
                        if (this.options.debug && window.console && console.warn) {
                            console.warn(
                                'Select2: The `data-pumselect2-tags` attribute has been changed to ' +
                                'use the `data-data` and `data-tags="true"` attributes and will be ' +
                                'removed in future versions of Select2.'
                            );
                        }

                        $e.data('data', $e.data('pumselect2Tags'));
                        $e.data('tags', true);
                    }

                    if ($e.data('ajaxUrl')) {
                        if (this.options.debug && window.console && console.warn) {
                            console.warn(
                                'Select2: The `data-ajax-url` attribute has been changed to ' +
                                '`data-ajax--url` and support for the old attribute will be removed' +
                                ' in future versions of Select2.'
                            );
                        }

                        $e.attr('ajax--url', $e.data('ajaxUrl'));
                        $e.data('ajax--url', $e.data('ajaxUrl'));
                    }

                    var dataset = {};

                    // Prefer the element's `dataset` attribute if it exists
                    // jQuery 1.x does not correctly handle data attributes with multiple dashes
                    if ($.fn.jquery && $.fn.jquery.substr(0, 2) == '1.' && $e[0].dataset) {
                        dataset = $.extend(true, {}, $e[0].dataset, $e.data());
                    } else {
                        dataset = $e.data();
                    }

                    var data = $.extend(true, {}, dataset);

                    data = Utils._convertData(data);

                    for (var key in data) {
                        if ($.inArray(key, excludedData) > -1) {
                            continue;
                        }

                        if ($.isPlainObject(this.options[key])) {
                            $.extend(this.options[key], data[key]);
                        } else {
                            this.options[key] = data[key];
                        }
                    }

                    return this;
                };

                Options.prototype.get = function (key) {
                    return this.options[key];
                };

                Options.prototype.set = function (key, val) {
                    this.options[key] = val;
                };

                return Options;
            });

            S2.define('pumselect2/core',[
                'jquery',
                './options',
                './utils',
                './keys'
            ], function ($, Options, Utils, KEYS) {
                var Select2 = function ($element, options) {
                    if ($element.data('pumselect2') != null) {
                        $element.data('pumselect2').destroy();
                    }

                    this.$element = $element;

                    this.id = this._generateId($element);

                    options = options || {};

                    this.options = new Options(options, $element);

                    Select2.__super__.constructor.call(this);

                    // Set up the tabindex

                    var tabindex = $element.attr('tabindex') || 0;
                    $element.data('old-tabindex', tabindex);
                    $element.attr('tabindex', '-1');

                    // Set up containers and adapters

                    var DataAdapter = this.options.get('dataAdapter');
                    this.dataAdapter = new DataAdapter($element, this.options);

                    var $container = this.render();

                    this._placeContainer($container);

                    var SelectionAdapter = this.options.get('selectionAdapter');
                    this.selection = new SelectionAdapter($element, this.options);
                    this.$selection = this.selection.render();

                    this.selection.position(this.$selection, $container);

                    var DropdownAdapter = this.options.get('dropdownAdapter');
                    this.dropdown = new DropdownAdapter($element, this.options);
                    this.$dropdown = this.dropdown.render();

                    this.dropdown.position(this.$dropdown, $container);

                    var ResultsAdapter = this.options.get('resultsAdapter');
                    this.results = new ResultsAdapter($element, this.options, this.dataAdapter);
                    this.$results = this.results.render();

                    this.results.position(this.$results, this.$dropdown);

                    // Bind events

                    var self = this;

                    // Bind the container to all of the adapters
                    this._bindAdapters();

                    // Register any DOM event handlers
                    this._registerDomEvents();

                    // Register any internal event handlers
                    this._registerDataEvents();
                    this._registerSelectionEvents();
                    this._registerDropdownEvents();
                    this._registerResultsEvents();
                    this._registerEvents();

                    // Set the initial state
                    this.dataAdapter.current(function (initialData) {
                        self.trigger('selection:update', {
                            data: initialData
                        });
                    });

                    // Hide the original select
                    $element.addClass('pumselect2-hidden-accessible');
                    $element.attr('aria-hidden', 'true');

                    // Synchronize any monitored attributes
                    this._syncAttributes();

                    $element.data('pumselect2', this);
                };

                Utils.Extend(Select2, Utils.Observable);

                Select2.prototype._generateId = function ($element) {
                    var id = '';

                    if ($element.attr('id') != null) {
                        id = $element.attr('id');
                    } else if ($element.attr('name') != null) {
                        id = $element.attr('name') + '-' + Utils.generateChars(2);
                    } else {
                        id = Utils.generateChars(4);
                    }

                    id = id.replace(/(:|\.|\[|\]|,)/g, '');
                    id = 'pumselect2-' + id;

                    return id;
                };

                Select2.prototype._placeContainer = function ($container) {
                    $container.insertAfter(this.$element);

                    var width = this._resolveWidth(this.$element, this.options.get('width'));

                    if (width != null) {
                        $container.css('width', width);
                    }
                };

                Select2.prototype._resolveWidth = function ($element, method) {
                    var WIDTH = /^width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i;

                    if (method == 'resolve') {
                        var styleWidth = this._resolveWidth($element, 'style');

                        if (styleWidth != null) {
                            return styleWidth;
                        }

                        return this._resolveWidth($element, 'element');
                    }

                    if (method == 'element') {
                        var elementWidth = $element.outerWidth(false);

                        if (elementWidth <= 0) {
                            return 'auto';
                        }

                        return elementWidth + 'px';
                    }

                    if (method == 'style') {
                        var style = $element.attr('style');

                        if (typeof(style) !== 'string') {
                            return null;
                        }

                        var attrs = style.split(';');

                        for (var i = 0, l = attrs.length; i < l; i = i + 1) {
                            var attr = attrs[i].replace(/\s/g, '');
                            var matches = attr.match(WIDTH);

                            if (matches !== null && matches.length >= 1) {
                                return matches[1];
                            }
                        }

                        return null;
                    }

                    return method;
                };

                Select2.prototype._bindAdapters = function () {
                    this.dataAdapter.bind(this, this.$container);
                    this.selection.bind(this, this.$container);

                    this.dropdown.bind(this, this.$container);
                    this.results.bind(this, this.$container);
                };

                Select2.prototype._registerDomEvents = function () {
                    var self = this;

                    this.$element.on('change.pumselect2', function () {
                        self.dataAdapter.current(function (data) {
                            self.trigger('selection:update', {
                                data: data
                            });
                        });
                    });

                    this._sync = Utils.bind(this._syncAttributes, this);

                    if (this.$element[0].attachEvent) {
                        this.$element[0].attachEvent('onpropertychange', this._sync);
                    }

                    var observer = window.MutationObserver ||
                            window.WebKitMutationObserver ||
                            window.MozMutationObserver
                        ;

                    if (observer != null) {
                        this._observer = new observer(function (mutations) {
                            $.each(mutations, self._sync);
                        });
                        this._observer.observe(this.$element[0], {
                            attributes: true,
                            subtree: false
                        });
                    } else if (this.$element[0].addEventListener) {
                        this.$element[0].addEventListener('DOMAttrModified', self._sync, false);
                    }
                };

                Select2.prototype._registerDataEvents = function () {
                    var self = this;

                    this.dataAdapter.on('*', function (name, params) {
                        self.trigger(name, params);
                    });
                };

                Select2.prototype._registerSelectionEvents = function () {
                    var self = this;
                    var nonRelayEvents = ['toggle', 'focus'];

                    this.selection.on('toggle', function () {
                        self.toggleDropdown();
                    });

                    this.selection.on('focus', function (params) {
                        self.focus(params);
                    });

                    this.selection.on('*', function (name, params) {
                        if ($.inArray(name, nonRelayEvents) !== -1) {
                            return;
                        }

                        self.trigger(name, params);
                    });
                };

                Select2.prototype._registerDropdownEvents = function () {
                    var self = this;

                    this.dropdown.on('*', function (name, params) {
                        self.trigger(name, params);
                    });
                };

                Select2.prototype._registerResultsEvents = function () {
                    var self = this;

                    this.results.on('*', function (name, params) {
                        self.trigger(name, params);
                    });
                };

                Select2.prototype._registerEvents = function () {
                    var self = this;

                    this.on('open', function () {
                        self.$container.addClass('pumselect2-container--open');
                    });

                    this.on('close', function () {
                        self.$container.removeClass('pumselect2-container--open');
                    });

                    this.on('enable', function () {
                        self.$container.removeClass('pumselect2-container--disabled');
                    });

                    this.on('disable', function () {
                        self.$container.addClass('pumselect2-container--disabled');
                    });

                    this.on('blur', function () {
                        self.$container.removeClass('pumselect2-container--focus');
                    });

                    this.on('query', function (params) {
                        if (!self.isOpen()) {
                            self.trigger('open', {});
                        }

                        this.dataAdapter.query(params, function (data) {
                            self.trigger('results:all', {
                                data: data,
                                query: params
                            });
                        });
                    });

                    this.on('query:append', function (params) {
                        this.dataAdapter.query(params, function (data) {
                            self.trigger('results:append', {
                                data: data,
                                query: params
                            });
                        });
                    });

                    this.on('keypress', function (evt) {
                        var key = evt.which;

                        if (self.isOpen()) {
                            if (key === KEYS.ESC || key === KEYS.TAB ||
                                (key === KEYS.UP && evt.altKey)) {
                                self.close();

                                evt.preventDefault();
                            } else if (key === KEYS.ENTER) {
                                self.trigger('results:select', {});

                                evt.preventDefault();
                            } else if ((key === KEYS.SPACE && evt.ctrlKey)) {
                                self.trigger('results:toggle', {});

                                evt.preventDefault();
                            } else if (key === KEYS.UP) {
                                self.trigger('results:previous', {});

                                evt.preventDefault();
                            } else if (key === KEYS.DOWN) {
                                self.trigger('results:next', {});

                                evt.preventDefault();
                            }
                        } else {
                            if (key === KEYS.ENTER || key === KEYS.SPACE ||
                                (key === KEYS.DOWN && evt.altKey)) {
                                self.open();

                                evt.preventDefault();
                            }
                        }
                    });
                };

                Select2.prototype._syncAttributes = function () {
                    this.options.set('disabled', this.$element.prop('disabled'));

                    if (this.options.get('disabled')) {
                        if (this.isOpen()) {
                            this.close();
                        }

                        this.trigger('disable', {});
                    } else {
                        this.trigger('enable', {});
                    }
                };

                /**
                 * Override the trigger method to automatically trigger pre-events when
                 * there are events that can be prevented.
                 */
                Select2.prototype.trigger = function (name, args) {
                    var actualTrigger = Select2.__super__.trigger;
                    var preTriggerMap = {
                        'open': 'opening',
                        'close': 'closing',
                        'select': 'selecting',
                        'unselect': 'unselecting'
                    };

                    if (args === undefined) {
                        args = {};
                    }

                    if (name in preTriggerMap) {
                        var preTriggerName = preTriggerMap[name];
                        var preTriggerArgs = {
                            prevented: false,
                            name: name,
                            args: args
                        };

                        actualTrigger.call(this, preTriggerName, preTriggerArgs);

                        if (preTriggerArgs.prevented) {
                            args.prevented = true;

                            return;
                        }
                    }

                    actualTrigger.call(this, name, args);
                };

                Select2.prototype.toggleDropdown = function () {
                    if (this.options.get('disabled')) {
                        return;
                    }

                    if (this.isOpen()) {
                        this.close();
                    } else {
                        this.open();
                    }
                };

                Select2.prototype.open = function () {
                    if (this.isOpen()) {
                        return;
                    }

                    this.trigger('query', {});
                };

                Select2.prototype.close = function () {
                    if (!this.isOpen()) {
                        return;
                    }

                    this.trigger('close', {});
                };

                Select2.prototype.isOpen = function () {
                    return this.$container.hasClass('pumselect2-container--open');
                };

                Select2.prototype.hasFocus = function () {
                    return this.$container.hasClass('pumselect2-container--focus');
                };

                Select2.prototype.focus = function (data) {
                    // No need to re-trigger focus events if we are already focused
                    if (this.hasFocus()) {
                        return;
                    }

                    this.$container.addClass('pumselect2-container--focus');
                    this.trigger('focus', {});
                };

                Select2.prototype.enable = function (args) {
                    if (this.options.get('debug') && window.console && console.warn) {
                        console.warn(
                            'Select2: The `pumselect2("enable")` method has been deprecated and will' +
                            ' be removed in later Select2 versions. Use $element.prop("disabled")' +
                            ' instead.'
                        );
                    }

                    if (args == null || args.length === 0) {
                        args = [true];
                    }

                    var disabled = !args[0];

                    this.$element.prop('disabled', disabled);
                };

                Select2.prototype.data = function () {
                    if (this.options.get('debug') &&
                        arguments.length > 0 && window.console && console.warn) {
                        console.warn(
                            'Select2: Data can no longer be set using `pumselect2("data")`. You ' +
                            'should consider setting the value instead using `$element.val()`.'
                        );
                    }

                    var data = [];

                    this.dataAdapter.current(function (currentData) {
                        data = currentData;
                    });

                    return data;
                };

                Select2.prototype.val = function (args) {
                    if (this.options.get('debug') && window.console && console.warn) {
                        console.warn(
                            'Select2: The `pumselect2("val")` method has been deprecated and will be' +
                            ' removed in later Select2 versions. Use $element.val() instead.'
                        );
                    }

                    if (args == null || args.length === 0) {
                        return this.$element.val();
                    }

                    var newVal = args[0];

                    if ($.isArray(newVal)) {
                        newVal = $.map(newVal, function (obj) {
                            return obj.toString();
                        });
                    }

                    this.$element.val(newVal).trigger('change');
                };

                Select2.prototype.destroy = function () {
                    this.$container.remove();

                    if (this.$element[0].detachEvent) {
                        this.$element[0].detachEvent('onpropertychange', this._sync);
                    }

                    if (this._observer != null) {
                        this._observer.disconnect();
                        this._observer = null;
                    } else if (this.$element[0].removeEventListener) {
                        this.$element[0]
                            .removeEventListener('DOMAttrModified', this._sync, false);
                    }

                    this._sync = null;

                    this.$element.off('.pumselect2');
                    this.$element.attr('tabindex', this.$element.data('old-tabindex'));

                    this.$element.removeClass('pumselect2-hidden-accessible');
                    this.$element.attr('aria-hidden', 'false');
                    this.$element.removeData('pumselect2');

                    this.dataAdapter.destroy();
                    this.selection.destroy();
                    this.dropdown.destroy();
                    this.results.destroy();

                    this.dataAdapter = null;
                    this.selection = null;
                    this.dropdown = null;
                    this.results = null;
                };

                Select2.prototype.render = function () {
                    var $container = $(
                        '<span class="pumselect2 pumselect2-container">' +
                        '<span class="selection"></span>' +
                        '<span class="dropdown-wrapper" aria-hidden="true"></span>' +
                        '</span>'
                    );

                    $container.attr('dir', this.options.get('dir'));

                    this.$container = $container;

                    this.$container.addClass('pumselect2-container--' + this.options.get('theme'));

                    $container.data('element', this.$element);

                    return $container;
                };

                return Select2;
            });

            S2.define('pumselect2/compat/utils',[
                'jquery'
            ], function ($) {
                function syncCssClasses ($dest, $src, adapter) {
                    var classes, replacements = [], adapted;

                    classes = $.trim($dest.attr('class'));

                    if (classes) {
                        classes = '' + classes; // for IE which returns object

                        $(classes.split(/\s+/)).each(function () {
                            // Save all Select2 classes
                            if (this.indexOf('pumselect2-') === 0) {
                                replacements.push(this);
                            }
                        });
                    }

                    classes = $.trim($src.attr('class'));

                    if (classes) {
                        classes = '' + classes; // for IE which returns object

                        $(classes.split(/\s+/)).each(function () {
                            // Only adapt non-Select2 classes
                            if (this.indexOf('pumselect2-') !== 0) {
                                adapted = adapter(this);

                                if (adapted != null) {
                                    replacements.push(adapted);
                                }
                            }
                        });
                    }

                    $dest.attr('class', replacements.join(' '));
                }

                return {
                    syncCssClasses: syncCssClasses
                };
            });

            S2.define('pumselect2/compat/containerCss',[
                'jquery',
                './utils'
            ], function ($, CompatUtils) {
                // No-op CSS adapter that discards all classes by default
                function _containerAdapter (clazz) {
                    return null;
                }

                function ContainerCSS () { }

                ContainerCSS.prototype.render = function (decorated) {
                    var $container = decorated.call(this);

                    var containerCssClass = this.options.get('containerCssClass') || '';

                    if ($.isFunction(containerCssClass)) {
                        containerCssClass = containerCssClass(this.$element);
                    }

                    var containerCssAdapter = this.options.get('adaptContainerCssClass');
                    containerCssAdapter = containerCssAdapter || _containerAdapter;

                    if (containerCssClass.indexOf(':all:') !== -1) {
                        containerCssClass = containerCssClass.replace(':all:', '');

                        var _cssAdapter = containerCssAdapter;

                        containerCssAdapter = function (clazz) {
                            var adapted = _cssAdapter(clazz);

                            if (adapted != null) {
                                // Append the old one along with the adapted one
                                return adapted + ' ' + clazz;
                            }

                            return clazz;
                        };
                    }

                    var containerCss = this.options.get('containerCss') || {};

                    if ($.isFunction(containerCss)) {
                        containerCss = containerCss(this.$element);
                    }

                    CompatUtils.syncCssClasses($container, this.$element, containerCssAdapter);

                    $container.css(containerCss);
                    $container.addClass(containerCssClass);

                    return $container;
                };

                return ContainerCSS;
            });

            S2.define('pumselect2/compat/dropdownCss',[
                'jquery',
                './utils'
            ], function ($, CompatUtils) {
                // No-op CSS adapter that discards all classes by default
                function _dropdownAdapter (clazz) {
                    return null;
                }

                function DropdownCSS () { }

                DropdownCSS.prototype.render = function (decorated) {
                    var $dropdown = decorated.call(this);

                    var dropdownCssClass = this.options.get('dropdownCssClass') || '';

                    if ($.isFunction(dropdownCssClass)) {
                        dropdownCssClass = dropdownCssClass(this.$element);
                    }

                    var dropdownCssAdapter = this.options.get('adaptDropdownCssClass');
                    dropdownCssAdapter = dropdownCssAdapter || _dropdownAdapter;

                    if (dropdownCssClass.indexOf(':all:') !== -1) {
                        dropdownCssClass = dropdownCssClass.replace(':all:', '');

                        var _cssAdapter = dropdownCssAdapter;

                        dropdownCssAdapter = function (clazz) {
                            var adapted = _cssAdapter(clazz);

                            if (adapted != null) {
                                // Append the old one along with the adapted one
                                return adapted + ' ' + clazz;
                            }

                            return clazz;
                        };
                    }

                    var dropdownCss = this.options.get('dropdownCss') || {};

                    if ($.isFunction(dropdownCss)) {
                        dropdownCss = dropdownCss(this.$element);
                    }

                    CompatUtils.syncCssClasses($dropdown, this.$element, dropdownCssAdapter);

                    $dropdown.css(dropdownCss);
                    $dropdown.addClass(dropdownCssClass);

                    return $dropdown;
                };

                return DropdownCSS;
            });

            S2.define('pumselect2/compat/initSelection',[
                'jquery'
            ], function ($) {
                function InitSelection (decorated, $element, options) {
                    if (options.get('debug') && window.console && console.warn) {
                        console.warn(
                            'Select2: The `initSelection` option has been deprecated in favor' +
                            ' of a custom data adapter that overrides the `current` method. ' +
                            'This method is now called multiple times instead of a single ' +
                            'time when the instance is initialized. Support will be removed ' +
                            'for the `initSelection` option in future versions of Select2'
                        );
                    }

                    this.initSelection = options.get('initSelection');
                    this._isInitialized = false;

                    decorated.call(this, $element, options);
                }

                InitSelection.prototype.current = function (decorated, callback) {
                    var self = this;

                    if (this._isInitialized) {
                        decorated.call(this, callback);

                        return;
                    }

                    this.initSelection.call(null, this.$element, function (data) {
                        self._isInitialized = true;

                        if (!$.isArray(data)) {
                            data = [data];
                        }

                        callback(data);
                    });
                };

                return InitSelection;
            });

            S2.define('pumselect2/compat/inputData',[
                'jquery'
            ], function ($) {
                function InputData (decorated, $element, options) {
                    this._currentData = [];
                    this._valueSeparator = options.get('valueSeparator') || ',';

                    if ($element.prop('type') === 'hidden') {
                        if (options.get('debug') && console && console.warn) {
                            console.warn(
                                'Select2: Using a hidden input with Select2 is no longer ' +
                                'supported and may stop working in the future. It is recommended ' +
                                'to use a `<select>` element instead.'
                            );
                        }
                    }

                    decorated.call(this, $element, options);
                }

                InputData.prototype.current = function (_, callback) {
                    function getSelected (data, selectedIds) {
                        var selected = [];

                        if (data.selected || $.inArray(data.id, selectedIds) !== -1) {
                            data.selected = true;
                            selected.push(data);
                        } else {
                            data.selected = false;
                        }

                        if (data.children) {
                            selected.push.apply(selected, getSelected(data.children, selectedIds));
                        }

                        return selected;
                    }

                    var selected = [];

                    for (var d = 0; d < this._currentData.length; d++) {
                        var data = this._currentData[d];

                        selected.push.apply(
                            selected,
                            getSelected(
                                data,
                                this.$element.val().split(
                                    this._valueSeparator
                                )
                            )
                        );
                    }

                    callback(selected);
                };

                InputData.prototype.select = function (_, data) {
                    if (!this.options.get('multiple')) {
                        this.current(function (allData) {
                            $.map(allData, function (data) {
                                data.selected = false;
                            });
                        });

                        this.$element.val(data.id);
                        this.$element.trigger('change');
                    } else {
                        var value = this.$element.val();
                        value += this._valueSeparator + data.id;

                        this.$element.val(value);
                        this.$element.trigger('change');
                    }
                };

                InputData.prototype.unselect = function (_, data) {
                    var self = this;

                    data.selected = false;

                    this.current(function (allData) {
                        var values = [];

                        for (var d = 0; d < allData.length; d++) {
                            var item = allData[d];

                            if (data.id == item.id) {
                                continue;
                            }

                            values.push(item.id);
                        }

                        self.$element.val(values.join(self._valueSeparator));
                        self.$element.trigger('change');
                    });
                };

                InputData.prototype.query = function (_, params, callback) {
                    var results = [];

                    for (var d = 0; d < this._currentData.length; d++) {
                        var data = this._currentData[d];

                        var matches = this.matches(params, data);

                        if (matches !== null) {
                            results.push(matches);
                        }
                    }

                    callback({
                        results: results
                    });
                };

                InputData.prototype.addOptions = function (_, $options) {
                    var options = $.map($options, function ($option) {
                        return $.data($option[0], 'data');
                    });

                    this._currentData.push.apply(this._currentData, options);
                };

                return InputData;
            });

            S2.define('pumselect2/compat/matcher',[
                'jquery'
            ], function ($) {
                function oldMatcher (matcher) {
                    function wrappedMatcher (params, data) {
                        var match = $.extend(true, {}, data);

                        if (params.term == null || $.trim(params.term) === '') {
                            return match;
                        }

                        if (data.children) {
                            for (var c = data.children.length - 1; c >= 0; c--) {
                                var child = data.children[c];

                                // Check if the child object matches
                                // The old matcher returned a boolean true or false
                                var doesMatch = matcher(params.term, child.text, child);

                                // If the child didn't match, pop it off
                                if (!doesMatch) {
                                    match.children.splice(c, 1);
                                }
                            }

                            if (match.children.length > 0) {
                                return match;
                            }
                        }

                        if (matcher(params.term, data.text, data)) {
                            return match;
                        }

                        return null;
                    }

                    return wrappedMatcher;
                }

                return oldMatcher;
            });

            S2.define('pumselect2/compat/query',[

            ], function () {
                function Query (decorated, $element, options) {
                    if (options.get('debug') && window.console && console.warn) {
                        console.warn(
                            'Select2: The `query` option has been deprecated in favor of a ' +
                            'custom data adapter that overrides the `query` method. Support ' +
                            'will be removed for the `query` option in future versions of ' +
                            'Select2.'
                        );
                    }

                    decorated.call(this, $element, options);
                }

                Query.prototype.query = function (_, params, callback) {
                    params.callback = callback;

                    var query = this.options.get('query');

                    query.call(null, params);
                };

                return Query;
            });

            S2.define('pumselect2/dropdown/attachContainer',[

            ], function () {
                function AttachContainer (decorated, $element, options) {
                    decorated.call(this, $element, options);
                }

                AttachContainer.prototype.position =
                    function (decorated, $dropdown, $container) {
                        var $dropdownContainer = $container.find('.dropdown-wrapper');
                        $dropdownContainer.append($dropdown);

                        $dropdown.addClass('pumselect2-dropdown--below');
                        $container.addClass('pumselect2-container--below');
                    };

                return AttachContainer;
            });

            S2.define('pumselect2/dropdown/stopPropagation',[

            ], function () {
                function StopPropagation () { }

                StopPropagation.prototype.bind = function (decorated, container, $container) {
                    decorated.call(this, container, $container);

                    var stoppedEvents = [
                        'blur',
                        'change',
                        'click',
                        'dblclick',
                        'focus',
                        'focusin',
                        'focusout',
                        'input',
                        'keydown',
                        'keyup',
                        'keypress',
                        'mousedown',
                        'mouseenter',
                        'mouseleave',
                        'mousemove',
                        'mouseover',
                        'mouseup',
                        'search',
                        'touchend',
                        'touchstart'
                    ];

                    this.$dropdown.on(stoppedEvents.join(' '), function (evt) {
                        evt.stopPropagation();
                    });
                };

                return StopPropagation;
            });

            S2.define('pumselect2/selection/stopPropagation',[

            ], function () {
                function StopPropagation () { }

                StopPropagation.prototype.bind = function (decorated, container, $container) {
                    decorated.call(this, container, $container);

                    var stoppedEvents = [
                        'blur',
                        'change',
                        'click',
                        'dblclick',
                        'focus',
                        'focusin',
                        'focusout',
                        'input',
                        'keydown',
                        'keyup',
                        'keypress',
                        'mousedown',
                        'mouseenter',
                        'mouseleave',
                        'mousemove',
                        'mouseover',
                        'mouseup',
                        'search',
                        'touchend',
                        'touchstart'
                    ];

                    this.$selection.on(stoppedEvents.join(' '), function (evt) {
                        evt.stopPropagation();
                    });
                };

                return StopPropagation;
            });

            /*!
             * jQuery Mousewheel 3.1.13
             *
             * Copyright jQuery Foundation and other contributors
             * Released under the MIT license
             * http://jquery.org/license
             */

            (function (factory) {
                if ( typeof S2.define === 'function' && S2.define.amd ) {
                    // AMD. Register as an anonymous module.
                    S2.define('jquery-mousewheel',['jquery'], factory);
                } else if (typeof exports === 'object') {
                    // Node/CommonJS style for Browserify
                    module.exports = factory;
                } else {
                    // Browser globals
                    factory(jQuery);
                }
            }(function ($) {

                var toFix  = ['wheel', 'mousewheel', 'DOMMouseScroll', 'MozMousePixelScroll'],
                    toBind = ( 'onwheel' in document || document.documentMode >= 9 ) ?
                        ['wheel'] : ['mousewheel', 'DomMouseScroll', 'MozMousePixelScroll'],
                    slice  = Array.prototype.slice,
                    nullLowestDeltaTimeout, lowestDelta;

                if ( $.event.fixHooks ) {
                    for ( var i = toFix.length; i; ) {
                        $.event.fixHooks[ toFix[--i] ] = $.event.mouseHooks;
                    }
                }

                var special = $.event.special.mousewheel = {
                    version: '3.1.12',

                    setup: function() {
                        if ( this.addEventListener ) {
                            for ( var i = toBind.length; i; ) {
                                this.addEventListener( toBind[--i], handler, false );
                            }
                        } else {
                            this.onmousewheel = handler;
                        }
                        // Store the line height and page height for this particular element
                        $.data(this, 'mousewheel-line-height', special.getLineHeight(this));
                        $.data(this, 'mousewheel-page-height', special.getPageHeight(this));
                    },

                    teardown: function() {
                        if ( this.removeEventListener ) {
                            for ( var i = toBind.length; i; ) {
                                this.removeEventListener( toBind[--i], handler, false );
                            }
                        } else {
                            this.onmousewheel = null;
                        }
                        // Clean up the data we added to the element
                        $.removeData(this, 'mousewheel-line-height');
                        $.removeData(this, 'mousewheel-page-height');
                    },

                    getLineHeight: function(elem) {
                        var $elem = $(elem),
                            $parent = $elem['offsetParent' in $.fn ? 'offsetParent' : 'parent']();
                        if (!$parent.length) {
                            $parent = $('body');
                        }
                        return parseInt($parent.css('fontSize'), 10) || parseInt($elem.css('fontSize'), 10) || 16;
                    },

                    getPageHeight: function(elem) {
                        return $(elem).height();
                    },

                    settings: {
                        adjustOldDeltas: true, // see shouldAdjustOldDeltas() below
                        normalizeOffset: true  // calls getBoundingClientRect for each event
                    }
                };

                $.fn.extend({
                    mousewheel: function(fn) {
                        return fn ? this.bind('mousewheel', fn) : this.trigger('mousewheel');
                    },

                    unmousewheel: function(fn) {
                        return this.unbind('mousewheel', fn);
                    }
                });


                function handler(event) {
                    var orgEvent   = event || window.event,
                        args       = slice.call(arguments, 1),
                        delta      = 0,
                        deltaX     = 0,
                        deltaY     = 0,
                        absDelta   = 0,
                        offsetX    = 0,
                        offsetY    = 0;
                    event = $.event.fix(orgEvent);
                    event.type = 'mousewheel';

                    // Old school scrollwheel delta
                    if ( 'detail'      in orgEvent ) { deltaY = orgEvent.detail * -1;      }
                    if ( 'wheelDelta'  in orgEvent ) { deltaY = orgEvent.wheelDelta;       }
                    if ( 'wheelDeltaY' in orgEvent ) { deltaY = orgEvent.wheelDeltaY;      }
                    if ( 'wheelDeltaX' in orgEvent ) { deltaX = orgEvent.wheelDeltaX * -1; }

                    // Firefox < 17 horizontal scrolling related to DOMMouseScroll event
                    if ( 'axis' in orgEvent && orgEvent.axis === orgEvent.HORIZONTAL_AXIS ) {
                        deltaX = deltaY * -1;
                        deltaY = 0;
                    }

                    // Set delta to be deltaY or deltaX if deltaY is 0 for backwards compatabilitiy
                    delta = deltaY === 0 ? deltaX : deltaY;

                    // New school wheel delta (wheel event)
                    if ( 'deltaY' in orgEvent ) {
                        deltaY = orgEvent.deltaY * -1;
                        delta  = deltaY;
                    }
                    if ( 'deltaX' in orgEvent ) {
                        deltaX = orgEvent.deltaX;
                        if ( deltaY === 0 ) { delta  = deltaX * -1; }
                    }

                    // No change actually happened, no reason to go any further
                    if ( deltaY === 0 && deltaX === 0 ) { return; }

                    // Need to convert lines and pages to pixels if we aren't already in pixels
                    // There are three delta modes:
                    //   * deltaMode 0 is by pixels, nothing to do
                    //   * deltaMode 1 is by lines
                    //   * deltaMode 2 is by pages
                    if ( orgEvent.deltaMode === 1 ) {
                        var lineHeight = $.data(this, 'mousewheel-line-height');
                        delta  *= lineHeight;
                        deltaY *= lineHeight;
                        deltaX *= lineHeight;
                    } else if ( orgEvent.deltaMode === 2 ) {
                        var pageHeight = $.data(this, 'mousewheel-page-height');
                        delta  *= pageHeight;
                        deltaY *= pageHeight;
                        deltaX *= pageHeight;
                    }

                    // Store lowest absolute delta to normalize the delta values
                    absDelta = Math.max( Math.abs(deltaY), Math.abs(deltaX) );

                    if ( !lowestDelta || absDelta < lowestDelta ) {
                        lowestDelta = absDelta;

                        // Adjust older deltas if necessary
                        if ( shouldAdjustOldDeltas(orgEvent, absDelta) ) {
                            lowestDelta /= 40;
                        }
                    }

                    // Adjust older deltas if necessary
                    if ( shouldAdjustOldDeltas(orgEvent, absDelta) ) {
                        // Divide all the things by 40!
                        delta  /= 40;
                        deltaX /= 40;
                        deltaY /= 40;
                    }

                    // Get a whole, normalized value for the deltas
                    delta  = Math[ delta  >= 1 ? 'floor' : 'ceil' ](delta  / lowestDelta);
                    deltaX = Math[ deltaX >= 1 ? 'floor' : 'ceil' ](deltaX / lowestDelta);
                    deltaY = Math[ deltaY >= 1 ? 'floor' : 'ceil' ](deltaY / lowestDelta);

                    // Normalise offsetX and offsetY properties
                    if ( special.settings.normalizeOffset && this.getBoundingClientRect ) {
                        var boundingRect = this.getBoundingClientRect();
                        offsetX = event.clientX - boundingRect.left;
                        offsetY = event.clientY - boundingRect.top;
                    }

                    // Add information to the event object
                    event.deltaX = deltaX;
                    event.deltaY = deltaY;
                    event.deltaFactor = lowestDelta;
                    event.offsetX = offsetX;
                    event.offsetY = offsetY;
                    // Go ahead and set deltaMode to 0 since we converted to pixels
                    // Although this is a little odd since we overwrite the deltaX/Y
                    // properties with normalized deltas.
                    event.deltaMode = 0;

                    // Add event and delta to the front of the arguments
                    args.unshift(event, delta, deltaX, deltaY);

                    // Clearout lowestDelta after sometime to better
                    // handle multiple device types that give different
                    // a different lowestDelta
                    // Ex: trackpad = 3 and mouse wheel = 120
                    if (nullLowestDeltaTimeout) { clearTimeout(nullLowestDeltaTimeout); }
                    nullLowestDeltaTimeout = setTimeout(nullLowestDelta, 200);

                    return ($.event.dispatch || $.event.handle).apply(this, args);
                }

                function nullLowestDelta() {
                    lowestDelta = null;
                }

                function shouldAdjustOldDeltas(orgEvent, absDelta) {
                    // If this is an older event and the delta is divisable by 120,
                    // then we are assuming that the browser is treating this as an
                    // older mouse wheel event and that we should divide the deltas
                    // by 40 to try and get a more usable deltaFactor.
                    // Side note, this actually impacts the reported scroll distance
                    // in older browsers and can cause scrolling to be slower than native.
                    // Turn this off by setting $.event.special.mousewheel.settings.adjustOldDeltas to false.
                    return special.settings.adjustOldDeltas && orgEvent.type === 'mousewheel' && absDelta % 120 === 0;
                }

            }));

            S2.define('jquery.pumselect2',[
                'jquery',
                'jquery-mousewheel',

                './pumselect2/core',
                './pumselect2/defaults'
            ], function ($, _, Select2, Defaults) {
                if ($.fn.pumselect2 == null) {
                    // All methods that should return the element
                    var thisMethods = ['open', 'close', 'destroy'];

                    $.fn.pumselect2 = function (options) {
                        options = options || {};

                        if (typeof options === 'object') {
                            this.each(function () {
                                var instanceOptions = $.extend(true, {}, options);

                                var instance = new Select2($(this), instanceOptions);
                            });

                            return this;
                        } else if (typeof options === 'string') {
                            var ret;

                            this.each(function () {
                                var instance = $(this).data('pumselect2');

                                if (instance == null && window.console && console.error) {
                                    console.error(
                                        'The pumselect2(\'' + options + '\') method was called on an ' +
                                        'element that is not using Select2.'
                                    );
                                }

                                var args = Array.prototype.slice.call(arguments, 1);

                                ret = instance[options].apply(instance, args);
                            });

                            // Check if we should be returning `this`
                            if ($.inArray(options, thisMethods) > -1) {
                                return this;
                            }

                            return ret;
                        } else {
                            throw new Error('Invalid arguments for Select2: ' + options);
                        }
                    };
                }

                if ($.fn.pumselect2.defaults == null) {
                    $.fn.pumselect2.defaults = Defaults;
                }

                return Select2;
            });

            // Return the AMD loader configuration so it can be used outside of this file
            return {
                define: S2.define,
                require: S2.require
            };
        }());

    // Autoload the jQuery bindings
    // We know that all of the modules exist above this, so we're safe
    var pumselect2 = S2.require('jquery.pumselect2');

    // Hold the AMD module references on the jQuery function that was just loaded
    // This allows Select2 to use the internal loader outside of this file, such
    // as in the language files.
    jQuery.fn.pumselect2.amd = S2;

    // Return the Select2 instance for anyone who is importing it.
    return pumselect2;
}));
(function ($, document, undefined) {
    "use strict";

    $(document)
        .on('click', '#popup_reset_open_count', function () {
            var $this = $(this);
            if ($this.is(':checked') && !confirm(pum_admin.I10n.confirm_count_reset)) {
                $this.prop('checked', false);
            }
        });
}(jQuery, document));
var PUMColorPickers;
(function ($, document, undefined) {
    "use strict";
    PUMColorPickers = {
        init: function () {
            $('.color-picker').filter(':not(.initialized)')
                .addClass('initialized')
                .wpColorPicker({
                    change: function (event, ui) {
                        $(event.target).trigger('colorchange', ui);
                    },
                    clear: function (event) {
                        $(event.target).prev().trigger('colorchange').wpColorPicker('close');
                    }
                });
        }
    };

    $(document)
        .on('click', '.iris-palette', function () {
            $(this).parents('.wp-picker-active').find('input.color-picker').trigger('change');
            setTimeout(PopMakeAdmin.update_theme, 500);
        })
        .on('colorchange', function (event, ui) {
            var $input = $(event.target),
                $opacity = $input.parents('tr').next('tr.background-opacity'),
                color = '';

            if (ui !== undefined && ui.color !== undefined) {
                color = ui.color.toString();
            }

            if ($input.hasClass('background-color')) {
                if (typeof color === 'string' && color.length) {
                    $opacity.show();
                } else {
                    $opacity.hide();
                }
            }

            $input.val(color);

            if ($('form#post input#post_type').val() === 'popup_theme') {
                PopMakeAdmin.update_theme();
            }
        })
        .on('pum_init', PUMColorPickers.init);
}(jQuery, document));
var PUMConditions;
(function ($, document, undefined) {
    "use strict";

    PUMConditions = {
        templates: {},
        addGroup: function (target, not_operand) {
            var $container = $('#pum-popup-conditions'),
                data = {
                    index: $container.find('.facet-group-wrap').length,
                    conditions: [
                        {
                            target: target || null,
                            not_operand: not_operand || false,
                            settings: {}
                        }
                    ]
                };
            $container.find('.facet-groups').append(PUMConditions.templates.group(data));
            $container.find('.facet-builder').addClass('has-conditions');
            $(document).trigger('pum_init');
        },
        renumber: function () {
            $('#pum-popup-conditions .facet-group-wrap').each(function () {
                var $group = $(this),
                    groupIndex = $group.parent().children().index($group);

                $group
                    .data('index', groupIndex)
                    .find('.facet').each(function () {
                        var $facet = $(this),
                            facetIndex = $facet.parent().children().index($facet);

                        $facet
                            .data('index', facetIndex)
                            .find('[name]').each(function () {
                                var replace_with = "popup_conditions[" + groupIndex + "][" + facetIndex + "]";
                                this.name = this.name.replace(/popup_conditions\[\d*?\]\[\d*?\]/, replace_with);
                                this.id = this.name;
                            });
                    });
            });
        }
    };

    $(document)
        .on('pum_init', PUMConditions.renumber)
        .ready(function () {
            // TODO Remove this check once admin scripts have been split into popup-editor, theme-editor etc.
            if ($('body.post-type-popup form#post').length) {
                PUMConditions.templates.group = wp.template('pum-condition-group');
                PUMConditions.templates.facet = wp.template('pum-condition-facet');
                PUMConditions.templates.settings = {};

                $('script.tmpl.pum-condition-settings').each(function () {
                    var $this = $(this),
                        tmpl = $this.attr('id').replace('tmpl-', '');
                    PUMConditions.templates.settings[$this.data('condition')] = wp.template(tmpl);
                });

                PUMConditions.renumber();
            }
        })
        .on('select2:select pumselect2:select', '#pum-first-condition', function () {
            var $this = $(this),
                target = $this.val(),
                $operand = $('#pum-first-condition-operand'),
                not_operand = $operand.is(':checked') ? $operand.val() : null;

            PUMConditions.addGroup(target, not_operand);

            $this
                .val(null)
                .trigger('change');
            $operand.prop('checked', false).parents('.pum-condition-target').removeClass('not-operand-checked');
        })
        .on('click', '#pum-popup-conditions .pum-not-operand', function () {
            var $this = $(this),
                $input = $this.find('input'),
                $container = $this.parents('.pum-condition-target');

            if ($input.is(':checked')) {
                $container.removeClass('not-operand-checked');
                $input.prop('checked', false);
            } else {
                $container.addClass('not-operand-checked');
                $input.prop('checked', true);
            }
        })
        .on('change', '#pum-popup-conditions select.target', function () {
            var $this = $(this),
                target = $this.val(),
                data = {
                    index: $this.parents('.facet-group').find('.facet').length,
                    target: target,
                    settings: {}
                };

            if (target === '' || target === $this.parents('.facet').data('target') || PUMConditions.templates.settings[target] === undefined) {
                // TODO Add better error handling.
                return;
            }

            $this.parents('.facet').data('target', target).find('.facet-settings').html(PUMConditions.templates.settings[target](data));
            $(document).trigger('pum_init');
        })
        .on('click', '#pum-popup-conditions .facet-group-wrap:last-child .and .add-facet', PUMConditions.addGroup)
        .on('click', '#pum-popup-conditions .add-or .add-facet:not(.disabled)', function () {
            var $this = $(this),
                $group = $this.parents('.facet-group-wrap'),
                data = {
                    group: $group.data('index'),
                    index: $group.find('.facet').length,
                    target: null,
                    settings: {}
                };

            $group.find('.facet-list').append(PUMConditions.templates.facet(data));
            $(document).trigger('pum_init');
        })
        .on('click', '#pum-popup-conditions .remove-facet', function () {
            var $this = $(this),
                $container = $('#pum-popup-conditions'),
                $facet = $this.parents('.facet'),
                $group = $this.parents('.facet-group-wrap');

            $facet.remove();

            if ($group.find('.facet').length === 0) {
                $group.prev('.facet-group-wrap').find('.and .add-facet').removeClass('disabled');
                $group.remove();

                if ($container.find('.facet-group-wrap').length === 0) {
                    $container.find('.facet-builder').removeClass('has-conditions');
                }
            }
            PUMConditions.renumber();
        });


}(jQuery, document));
var PUMCookies;
(function ($, document, undefined) {
    "use strict";

    var I10n = pum_admin.I10n,
        defaults = pum_admin.defaults;

    PUMCookies = {
        getLabel: function (event) {
            return I10n.labels.cookies[event].name;
        },
        getSettingsDesc: function (event, values) {
            var options = {
                    evaluate:    /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable:    'data'
                },
                template = _.template(I10n.labels.cookies[event].settings_column, null, options);
            values.I10n = I10n;
            return template(values);
        },
        renumber: function () {
            $('#pum_popup_cookies_list tbody tr').each(function () {
                var $this = $(this),
                    index = $this.parent().children().index($this),
                    originalIndex = $this.data('index');

                $this.data('index', index);

                $this.find('[name]').each(function () {
                    var replace_with = "[" + index + "]";
                    this.name = this.name.replace("[" + originalIndex + "]", replace_with).replace("[]", replace_with);
                });
            });
        },
        refreshDescriptions: function () {
            $('#pum_popup_cookies_list tbody tr').each(function () {
                var $row = $(this),
                    event = $row.find('.popup_cookies_field_event').val(),
                    values = JSON.parse($row.find('.popup_cookies_field_settings:first').val());

                $row.find('td.settings-column').html(PUMCookies.getSettingsDesc(event, values));
            });
        },
        initEditForm: function () {
            PUMCookies.updateSessionsCheckbox();
        },
        updateSessionsCheckbox: function () {
            var $parent = $('.cookie-editor .pum-form'),
                sessions = $parent.find('.field.checkbox.session input[type="checkbox"]').is(':checked'),
                $otherFields = $parent.find('.field').filter('.time');

            if (sessions) {
                $otherFields.hide();
            } else {
                $otherFields.show();
            }
        },
        resetCookieKey: function () {
            var $this = $(this),
                newKey = (new Date().getTime()).toString(16);

            $this.parents('.pum-form').find('.field.text.name').data('cookiekey', newKey);
            $this.siblings('input[type="text"]:first').val(newKey);
        },
        insertDefault: function (name) {
            var event = 'on_popup_close',
                template = wp.template('pum-cookie-row'),
                data = {
                    event: event,
                    cookie_settings: defaults.cookies[event] !== undefined ? defaults.cookies[event] : {},
                    save_button_text: I10n.add,
                    index: $('#pum_popup_cookies_list tbody tr').length,
                    I10n: I10n
                },
                $new_row;

            data.cookie_settings.name = name || 'pum-' + $('#post_ID').val();

            $new_row = template(data);

            $('#pum_popup_cookies_list tbody').append($new_row);

            PUMCookies.renumber();

            $('#pum_popup_cookie_fields').addClass('has-cookies');

        }
    };

    $(document)
        .on('select2:select pumselect2:select', '#pum-first-cookie', function () {
            var $this = $(this),
                event = $this.val(),
                id = 'pum-cookie-settings-' + event,
                modalID = '#' + id.replace(/-/g,'_'),
                template = wp.template(id),
                data = {};

            data.cookie_settings = defaults.cookies[event] !== undefined ? defaults.cookies[event] : {};
            data.cookie_settings.name = 'pum-' + $('#post_ID').val();
            data.save_button_text = I10n.add;
            data.index = null;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMCookies.initEditForm();

            $this
                .val(null)
                .trigger('change');
        })
        .on('click', '.field.cookiekey button.reset', PUMCookies.resetCookieKey)
        .on('click', '.cookie-editor .pum-form .field.checkbox.session', PUMCookies.updateSessionsCheckbox)
        .on('click', '#pum_popup_cookies .add-new', function () {
            var template = wp.template('pum-cookie-add-event');
            PUMModals.reload('#pum_cookie_add_event_modal', template());
        })
        .on('click', '#pum_popup_cookies_list .edit', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first'),
                event = $row.find('.popup_cookies_field_event').val(),
                id = 'pum-cookie-settings-' + event,
                modalID = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data = {
                    index: $row.parent().children().index($row),
                    event: event,
                    cookie_settings: JSON.parse($row.find('.popup_cookies_field_settings:first').val())
                };

            e.preventDefault();

            data.save_button_text = I10n.save;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMCookies.initEditForm();
        })
        .on('click', '#pum_popup_cookies_list .remove', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first');

            e.preventDefault();

            if (window.confirm(I10n.confirm_delete_cookie)) {
                $row.remove();

                if (!$('#pum_popup_cookies_list tbody tr').length) {
                    $('#pum-first-cookie')
                        .val(null)
                        .trigger('change');

                    $('#pum_popup_cookie_fields').removeClass('has-cookies');
                }

                PUMCookies.renumber();
            }
        })
        .on('submit', '#pum_cookie_add_event_modal .pum-form', function (e) {
            var event = $('#popup_cookie_add_event').val(),
                id = 'pum-cookie-settings-' + event,
                modalID = '#' + id.replace(/-/g,'_'),
                template = wp.template(id),
                data = {};

            e.preventDefault();

            data.cookie_settings = defaults.cookies[event] !== undefined ? defaults.cookies[event] : {};
            data.cookie_settings.name = 'pum-' + $('#post_ID').val();
            data.save_button_text = I10n.add;
            data.index = null;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMCookies.initEditForm();
        })
        .on('submit', '.cookie-editor .pum-form', function (e) {
            var $form = $(this),
                event = $form.find('input.event').val(),
                values = $form.pumSerializeObject(),
                index = parseInt(values.index),
                $row = index >= 0 ? $('#pum_popup_cookies_list tbody tr').eq(index) : null,
                template = wp.template('pum-cookie-row'),
                $new_row,
                $trigger,
                trigger_settings;

            e.preventDefault();

            if (!index || index < 0) {
                values.index = $('#pum_popup_cookies_list tbody tr').length;
            }

            values.I10n = I10n;

            $new_row = template(values);

            if (!$row) {
                $('#pum_popup_cookies_list tbody').append($new_row);
            } else {
                $row.replaceWith($new_row);
            }

            PUMModals.closeAll();
            PUMCookies.renumber();

            $('#pum_popup_cookie_fields').addClass('has-cookies');

            if (PUMTriggers.new_cookie !== false && PUMTriggers.new_cookie >= 0) {
                $trigger = $('#pum_popup_triggers_list tbody tr').eq(PUMTriggers.new_cookie).find('.popup_triggers_field_settings:first');
                trigger_settings = JSON.parse($trigger.val());

                trigger_settings.cookie.name[trigger_settings.cookie.name.indexOf('add_new')] = values.cookie_settings.name;

                $trigger.val(JSON.stringify(trigger_settings));

                PUMTriggers.new_cookie = false;
                PUMTriggers.refreshDescriptions();
            }
        })
        .ready(function () {
            PUMCookies.refreshDescriptions();
            $('#pum-first-cookie')
                .val(null)
                .trigger('change');
        });

}(jQuery, document));
(function ($, document, undefined) {
    "use strict";
    var PopMakeAdminDeprecated = {
        init: function () {
            if ($('#popmake_popup_auto_open_fields, #popmake_popup_targeting_condition_fields').length) {
                PopMakeAdminDeprecated.initialize_popup_page();
                PopMakeAdminDeprecated.attachQuickSearchListeners();
                PopMakeAdminDeprecated.attachTabsPanelListeners();
            }
        },
        attachTabsPanelListeners: function () {
            $('#poststuff').bind('click', function (event) {
                var selectAreaMatch, panelId, wrapper, items,
                    target = $(event.target),
                    $parent,
                    $items,
                    $textarea,
                    $tag_area,
                    current_ids,
                    i,
                    $item,
                    id,
                    name,
                    removeItem;


                if (target.hasClass('nav-tab-link')) {
                    panelId = target.data('type');
                    wrapper = target.parents('.posttypediv, .taxonomydiv').first();
                    // upon changing tabs, we want to uncheck all checkboxes
                    $('input', wrapper).removeAttr('checked');
                    $('.tabs-panel-active', wrapper).removeClass('tabs-panel-active').addClass('tabs-panel-inactive');
                    $('#' + panelId, wrapper).removeClass('tabs-panel-inactive').addClass('tabs-panel-active');
                    $('.tabs', wrapper).removeClass('tabs');
                    target.parent().addClass('tabs');
                    // select the search bar
                    $('.quick-search', wrapper).focus();
                    event.preventDefault();
                } else if (target.hasClass('select-all')) {
                    selectAreaMatch = /#(.*)$/.exec(event.target.href);
                    if (selectAreaMatch && selectAreaMatch[1]) {
                        items = $('#' + selectAreaMatch[1] + ' .tabs-panel-active .menu-item-title input');
                        if (items.length === items.filter(':checked').length) {
                            items.removeAttr('checked');
                        } else {
                            items.prop('checked', true);
                        }
                    }
                } else if (target.hasClass('submit-add-to-menu')) {
                    $parent = target.parents('.options');
                    $items = $('.tabs-panel-active input[type="checkbox"]:checked', $parent);
                    $textarea = $('textarea', $parent);
                    $tag_area = $('.tagchecklist', $parent);
                    current_ids = $textarea.val().split(',');
                    for (i = 0; i < current_ids.length; i += 1) {
                        current_ids[i] = parseInt(current_ids[i], 10);
                    }
                    $items.each(function () {
                        $item = $(this);
                        id = parseInt($item.val(), 10);
                        name = $item.parent('label').siblings('.menu-item-title').val();
                        if ($.inArray(id, current_ids) === -1) {
                            current_ids.push(id);
                        }
                        $tag_area.append('<span><a class="ntdelbutton" data-id="' + id + '">X</a> ' + name + '</span>');
                    });
                    $textarea.text(current_ids.join(','));
                    event.preventDefault();
                } else if (target.hasClass('ntdelbutton')) {
                    $item = target;
                    removeItem = parseInt($item.data('id'), 10);
                    $parent = target.parents('.options');
                    $textarea = $('textarea', $parent);
                    $tag_area = $('.tagchecklist', $parent);
                    current_ids = $textarea.val().split(',');
                    current_ids = $.grep(current_ids, function (value) {
                        return parseInt(value, 10) !== parseInt(removeItem, 10);
                    });
                    $item.parent('span').remove();
                    $textarea.text(current_ids.join(','));
                }
            });
        },
        attachQuickSearchListeners: function () {
            var searchTimer;
            $('.quick-search').keypress(function (event) {
                var t = $(this);
                if (13 === event.which) {
                    PopMakeAdminDeprecated.updateQuickSearchResults(t);
                    return false;
                }
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                searchTimer = setTimeout(function () {
                    PopMakeAdminDeprecated.updateQuickSearchResults(t);
                }, 400);
            }).attr('autocomplete', 'off');
        },
        updateQuickSearchResults: function (input) {
            var panel, params,
                minSearchLength = 2,
                q = input.val();
            if (q.length < minSearchLength) {
                return;
            }
            panel = input.parents('.tabs-panel');
            params = {
                'action': 'menu-quick-search',
                'response-format': 'markup',
                'menu': null,
                'menu-settings-column-nonce': $('#menu-settings-column-nonce').val(),
                'q': q,
                'type': input.attr('name')
            };
            $('.spinner', panel).show();
            $.post(ajaxurl, params, function (menuMarkup) {
                PopMakeAdminDeprecated.processQuickSearchQueryResponse(menuMarkup, params, panel);
            });
        },
        processQuickSearchQueryResponse: function (resp, req, panel) {
            var matched, newID,
                form = $('form#post'),
                takenIDs = {},
                pattern = /menu-item[(\[\^]\]*/,
                $items = $('<div>').html(resp).find('li'),
                $item;

            if (!$items.length) {
                $('.categorychecklist', panel).html('<li><p>' + 'noResultsFound' + '</p></li>');
                $('.spinner', panel).hide();
                return;
            }

            $items.each(function () {
                $item = $(this);

                // make a unique DB ID number
                matched = pattern.exec($item.html());

                if (matched && matched[1]) {
                    newID = matched[1];
                    while (form.elements['menu-item[' + newID + '][menu-item-type]'] || takenIDs[newID]) {
                        newID = newID - 1;
                    }

                    takenIDs[newID] = true;
                    if (newID !== matched[1]) {
                        $item.html(
                            $item.html().replace(
                                new RegExp('menu-item\\[' + matched[1] + '\\]', 'g'),
                                'menu-item[' + newID + ']'
                            )
                        );
                    }
                }
            });

            $('.categorychecklist', panel).html($items);
            $('.spinner', panel).hide();
            $('[name^="menu-item"]').removeAttr('name');
        },
        initialize_popup_page: function () {
            var update_type_options = function ($this) {
                    var $options = $this.siblings('.options'),
                        excludes,
                        others;

                    if ($this.is(':checked')) {
                        $options.show();
                        if ($this.attr('id') === 'popup_targeting_condition_on_entire_site') {
                            excludes = $this.parents('#popmake_popup_targeting_condition_fields').find('[id^="targeting_condition-exclude_on_"]');
                            others = $this.parents('.targeting_condition').siblings('.targeting_condition');
                            others.hide();
                            $('> *', others).prop('disabled', true);
                            excludes.show();
                            $('> *', excludes).prop('disabled', false);
                        } else {
                            $('*', $options).prop('disabled', false);
                        }
                    } else {
                        $options.hide();
                        if ($this.attr('id') === 'popup_targeting_condition_on_entire_site') {
                            excludes = $this.parents('#popmake_popup_targeting_condition_fields').find('[id^="targeting_condition-exclude_on_"]');
                            others = $this.parents('.targeting_condition').siblings('.targeting_condition');
                            others.show();
                            $('> *', others).prop('disabled', false);
                            excludes.hide();
                            $('> *', excludes).prop('disabled', true);
                        } else {
                            $('*', $options).prop('disabled', true);
                        }
                    }
                },
                update_specific_checkboxes = function ($this) {
                    var $option = $this.parents('.options').find('input[type="checkbox"]:eq(0)'),
                        exclude = $option.attr('name').indexOf("exclude") >= 0,
                        type = exclude ? $option.attr('name').replace('popup_targeting_condition_exclude_on_specific_', '') : $option.attr('name').replace('popup_targeting_condition_on_specific_', ''),
                        type_box = exclude ? $('#exclude_on_specific_' + type) : $('#on_specific_' + type);

                    if ($this.is(':checked')) {
                        if ($this.val() === 'true') {
                            $option.prop('checked', true);
                            type_box.show();
                            $('*', type_box).prop('disabled', false);
                        } else if ($this.val() === '') {
                            $option.prop('checked', false);
                            type_box.hide();
                            $('*', type_box).prop('disabled', true);
                        }
                    }
                },
                auto_open_session_cookie_check = function () {
                    if ($("#popup_auto_open_session_cookie").is(":checked")) {
                        $('.not-session-cookie').hide();
                    } else {
                        $('.not-session-cookie').show();
                    }
                },
                auto_open_enabled_check = function () {
                    if ($("#popup_auto_open_enabled").is(":checked")) {
                        $('.auto-open-enabled').show();
                        auto_open_session_cookie_check();
                    } else {
                        $('.auto-open-enabled').hide();
                    }
                },
                auto_open_reset_cookie_key = function () {
                    $('#popup_auto_open_cookie_key').val((new Date().getTime()).toString(16));
                };

            $('[name^="menu-item"]').removeAttr('name');

            $('#title').prop('required', true);

            $(document)
                .on('click', "#popup_auto_open_session_cookie", function () {
                    auto_open_session_cookie_check();
                })
                .on('click', "#popup_auto_open_enabled", function () {
                    auto_open_enabled_check();
                })
                .on('click', ".popmake-reset-auto-open-cookie-key", function () {
                    auto_open_reset_cookie_key();
                });


            $('#popmake_popup_targeting_condition_fields .targeting_condition > input[type="checkbox"]')
                .on('click', function () {
                    update_type_options($(this));
                })
                .each(function () {
                    update_type_options($(this));
                });

            $('input[type="radio"][id*="popup_targeting_condition_"]')
                .on('click', function () {
                    update_specific_checkboxes($(this));
                })
                .each(function () {
                    update_specific_checkboxes($(this));
                });

            $('.posttypediv, .taxonomydiv').each(function () {
                var $this = $(this),
                    $tabs = $('> ul li'),
                    $sections = $('.tabs-panel', $this);

                $tabs.removeClass('tabs');
                $tabs.eq(0).addClass('tabs');
                $sections.removeClass('tabs-panel-active').addClass('tabs-panel-inactive').removeAttr('style');
                $sections.eq(0).removeClass('tabs-panel-inactive').addClass('tabs-panel-active');
            });

            auto_open_enabled_check();
            if ($('#popup_auto_open_cookie_key').val() === '') {
                auto_open_reset_cookie_key();
            }
        }
    };
    $(document).ready(function () {
        PopMakeAdminDeprecated.init();
        $(document).trigger('pum_init');
    });

}(jQuery, document));
function pumSelected(val1, val2, print) {
    "use strict";

    var selected = false;
    if (typeof val1 === 'object' && typeof val2 === 'string' && jQuery.inArray(val2, val1) !== -1) {
        selected = true;
    } else if (typeof val2 === 'object' && typeof val1 === 'string' && jQuery.inArray(val1, val2) !== -1) {
        selected = true;
    } else if (val1 === val2) {
        selected = true;
    }

    if (print !== undefined && print) {
        return selected ? ' selected="selected"' : '';
    }
    return selected;
}

function pumChecked(val1, val2, print) {
    "use strict";

    var checked = false;
    if (typeof val1 === 'object' && typeof val2 === 'string' && jQuery.inArray(val2, val1) !== -1) {
        checked = true;
    } else if (typeof val2 === 'object' && typeof val1 === 'string' && jQuery.inArray(val1, val2) !== -1) {
        checked = true;
    } else if (val1 === val2) {
        checked = true;
    }

    if (print !== undefined && print) {
        return checked ? ' checked="checked"' : '';
    }
    return checked;
}

var PUMMarketing;
(function ($, document, undefined) {
    "use strict";

    PUMMarketing = {
        init: function () {
            $('#menu-posts-popup ul li a[href="edit.php?post_type=popup&page=extensions"]').css({color: "#9aba27"});
        }
    };

    $(document).ready(PUMMarketing.init);
}(jQuery, document));
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

                    // Accessibility: Clears the currentModal var.
                    currentModal = null;

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
            currentModal = $(modal);

            // Accessibility: Close on esc press.
            currentModal
                .on('keydown.pum_modal', function (e) {
                    PUMModals.trapEscapeKey(e);
                    PUMModals.trapTabKey(e);
                })
                .show(0, function () {
                    $top_level_elements = $('body > *').filter(':visible').not(currentModal);
                    $top_level_elements.attr('aria-hidden', 'true');

                    currentModal
                        .trigger('pum_init')
                        // Accessibility: Add focus check that prevents tabbing outside of modal.
                        .on('focus.pum_modal', PUMModals.forceFocus);

                    // Accessibility: Focus on the modal.
                    PUMModals.setFocusToFirstItem();

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
            PUMModals.remove($.trim(modal));
            $('body').append($.trim(replacement));
        },
        reload: function (modal, replacement, callback) {
            PUMModals.replace(modal, replacement);
            PUMModals.show(modal, callback);
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
var PUMRangeSLiders;
(function ($, document, undefined) {
    "use strict";
    PUMRangeSLiders = {
        init: function () {
            var input,
                $input,
                $slider,
                $plus,
                $minus,
                slider = $('<input type="range"/>'),
                plus = $('<button type="button" class="popmake-range-plus">+</button>'),
                minus = $('<button type="button" class="popmake-range-minus">-</button>');

            $('.popmake-range-manual').filter(':not(.initialized)').each(function () {
                var $this = $(this).addClass('initialized'),
                    force = $this.data('force-minmax'),
                    min = parseInt($this.prop('min'), 0),
                    max = parseInt($this.prop('max'), 0),
                    step = parseInt($this.prop('step'), 0),
                    value = parseInt($this.val(), 0);

                $slider = slider.clone();
                $plus = plus.clone();
                $minus = minus.clone();

                if (force && value > max) {
                    value = max;
                    $this.val(value);
                }

                $slider
                    .prop({
                        min: min || 0,
                        max: ( force || (max && max > value) ) ? max : value * 1.5,
                        step: step || value * 1.5 / 100,
                        value: value
                    })
                    .on('change input', function () {
                        $this.trigger('input');
                    });
                $this.next().after($minus, $plus);
                $this.before($slider);

                input = document.createElement('input');
                input.setAttribute('type', 'range');
                if (input.type === 'text') {
                    $('input[type=range]').each(function (index, input) {
                        $input = $(input);
                        $slider = $('<div />').slider({
                            min: parseInt($input.attr('min'), 10) || 0,
                            max: parseInt($input.attr('max'), 10) || 100,
                            value: parseInt($input.attr('value'), 10) || 0,
                            step: parseInt($input.attr('step'), 10) || 1,
                            slide: function (event, ui) {
                                $(this).prev('input').val(ui.value);
                            }
                        });
                        $input.after($slider).hide();
                    });
                }
            });

        }
    };

    $(document)
        .on('pum_init', PUMRangeSLiders.init)
        /**
         * Updates the input field when the slider is used.
         */
        .on('input', 'input[type="range"]', function () {
            var $this = $(this);
            $this.siblings('.popmake-range-manual').val($this.val());
        })
        /**
         * Update sliders value, min, & max when manual entry is detected.
         */
        .on('change', '.popmake-range-manual', function () {
            var $this = $(this),
                max = parseInt($this.prop('max'), 0),
                step = parseInt($this.prop('step'), 0),
                force = $this.data('force-minmax'),
                value = parseInt($this.val(), 0),
                $slider = $this.prev();

            if (isNaN(value)) {
                value = $slider.val();
                $this.val(value);
            }

            if (force && value > max) {
                value = max;
                $this.val(value);
            }

            $slider.prop({
                'max': force || (max && max > value) ? max : value * 1.5,
                'step': step || value * 1.5 / 100,
                'value': value
            });

        })
        .on('click', '.popmake-range-plus', function (e) {
            var $this = $(this).siblings('.popmake-range-manual'),
                step = parseInt($this.prop('step'), 0),
                value = parseInt($this.val(), 0),
                val = value + step,
                $slider = $this.prev();

            e.preventDefault();

            $this.val(val).trigger('input');
            $slider.val(val);
        })
        .on('click', '.popmake-range-minus', function (e) {
            var $this = $(this).siblings('.popmake-range-manual'),
                step = parseInt($this.prop('step'), 0),
                value = parseInt($this.val(), 0),
                val = value - step,
                $slider = $this.prev();

            e.preventDefault();

            $this.val(val).trigger('input');
            $slider.val(val);
        });

}(jQuery, document));
var PUMSelect2Fields;
(function ($, document, undefined) {
    "use strict";
    // Here because some plugins load additional copies, big no-no. This is the best we can do.
    $.fn.pumselect2 = $.fn.pumselect2 || $.fn.select2;

    PUMSelect2Fields = {
        init: function () {
            $('.pum-select2 select').filter(':not(.initialized)').each(function () {
                var $this = $(this),
                    current = $this.data('current'),
                    object_type = $this.data('objecttype'),
                    object_key = $this.data('objectkey'),
                    options = {
                        multiple: false,
                        dropdownParent: $this.parent()
                    };

                if ($this.attr('multiple')) {
                    options.multiple = true;
                }

                if (object_type && object_key) {
                    options = $.extend(options, {
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    s: params.term, // search term
                                    page: params.page,
                                    action: "pum_object_search",
                                    object_type: object_type,
                                    object_key: object_key
                                };
                            },
                            processResults: function (data, params) {
                                // parse the results into the format expected by Select2
                                // since we are using custom formatting functions we do not need to
                                // alter the remote JSON data, except to indicate that infinite
                                // scrolling can be used
                                params.page = params.page || 1;

                                return {
                                    results: data.items,
                                    pagination: {
                                        more: (params.page * 10) < data.total_count
                                    }
                                };
                            },
                            cache: true
                        },
                        cache: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        }, // let our custom formatter work
                        minimumInputLength: 1,
                        templateResult: PUMSelect2Fields.formatObject,
                        templateSelection: PUMSelect2Fields.formatObjectSelection
                    });
                }

                $this
                    .addClass('initialized')
                    .pumselect2(options);

                if (current !== undefined) {

                    if ('object' !== typeof current) {
                        current = [current];
                    }

                    if (object_type && object_key) {
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: "pum_object_search",
                                object_type: object_type,
                                object_key: object_key,
                                include: current
                            },
                            dataType: "json",
                            success: function (data) {
                                $.each(data.items, function (key, item) {
                                    // Add any option that doesn't already exist
                                    if (!$this.find('option[value="' + item.id + '"]').length) {
                                        $this.prepend('<option value="' + item.id + '">' + item.text + '</option>');
                                    }
                                });
                                // Update the options
                                $this.val(current).trigger('change');
                            }
                        });
                    } else {
                        $this.val(current).trigger('change');
                    }

                }

            });
        },
        formatObject: function (object) {
            return object.text;
        },
        formatObjectSelection: function (object) {
            return object.text || object.text;
        }
    };

    $(document).on('pum_init', PUMSelect2Fields.init);

}(jQuery, document));
/**
 * jQuery.serializeObject v0.0.2
 *
 * Documentation: https://github.com/viart/jquery.serializeObject
 *
 * Artem Vitiuk (@avitiuk)
 */

(function ($, document, undefined) {

    var root = this,
        inputTypes = 'color,date,datetime,datetime-local,email,hidden,month,number,password,range,search,tel,text,time,url,week'.split(','),
        inputNodes = 'select,textarea'.split(','),
        rName = /\[([^\]]*)\]/g;

    // ugly hack for IE7-8
    function isInArray(array, needle) {
        return $.inArray(needle, array) !== -1;
    }

    function storeValue(container, parsedName, value) {

        var part = parsedName[0];

        if (parsedName.length > 1) {
            if (!container[part]) {
                // If the next part is eq to '' it means we are processing complex name (i.e. `some[]`)
                // for this case we need to use Array instead of an Object for the index increment purpose
                container[part] = parsedName[1] ? {} : [];
            }
            storeValue(container[part], parsedName.slice(1), value);
        } else {

            // Increment Array index for `some[]` case
            if (!part) {
                part = container.length;
            }

            container[part] = value;
        }
    }

    $.fn.pumSerializeObject = function (options) {
        $.extend({}, options);

        var values = {},
            settings = $.extend(true, {
                include: [],
                exclude: [],
                includeByClass: ''
            }, options);

        this.find(':input').each(function () {

            var parsedName;

            // Apply simple checks and filters
            if (!this.name || this.disabled ||
                isInArray(settings.exclude, this.name) ||
                (settings.include.length && !isInArray(settings.include, this.name)) ||
                this.className.indexOf(settings.includeByClass) === -1) {
                return;
            }

            // Parse complex names
            // JS RegExp doesn't support "positive look behind" :( that's why so weird parsing is used
            parsedName = this.name.replace(rName, '[$1').split('[');
            if (!parsedName[0]) {
                return;
            }

            if (this.checked ||
                isInArray(inputTypes, this.type) ||
                isInArray(inputNodes, this.nodeName.toLowerCase())) {

                // Simulate control with a complex name (i.e. `some[]`)
                // as it handled in the same way as Checkboxes should
                if (this.type === 'checkbox') {
                   // parsedName.push('');
                }

                // jQuery.val() is used to simplify of getting values
                // from the custom controls (which follow jQuery .val() API) and Multiple Select
                storeValue(values, parsedName, $(this).val());
            }
        });

        return values;
    };

}(jQuery, document));
var PUMTabs;
(function ($, document, undefined) {
    "use strict";
    PUMTabs = {
        init: function () {
            $('.pum-tabs-container').filter(':not(.initialized)').each(function () {
                var $this = $(this),
                    first_tab = $this.find('.tab:first');

                if ($this.hasClass('vertical-tabs')) {
                    $this.css({
                        minHeight: $this.find('.tabs').eq(0).outerHeight(true)
                    });
                }

                $this.find('.active').removeClass('active');
                first_tab.addClass('active');
                $(first_tab.find('a').attr('href')).addClass('active');
                $this.addClass('initialized');
            });
        }
    };

    $(document)
        .on('pum_init', PUMTabs.init)
        .on('click', '.pum-tabs-container .tab', function (e) {
            var $this = $(this),
                tab_group = $this.parents('.pum-tabs-container:first'),
                link = $this.find('a').attr('href');

            tab_group.find('.active').removeClass('active');

            $this.addClass('active');
            $(link).addClass('active');

            e.preventDefault();
        });
}(jQuery, document));
var PUM_Templates;
(function ($, document, undefined) {
    "use strict";

    var I10n = pum_admin.I10n;

    PUM_Templates = {
        render: function (template, data) {
            var _template = wp.template(template);

            if ('object' === typeof data.classes) {
                data.classes = data.classes.join(' ');
            }

            // Prepare the meta data for template.
            data = PUM_Templates.prepareMeta(data);

            return _template(data);
        },
        shortcode: function (args) {
            var data = $.extend({}, {
                    tag: '',
                    meta: {},
                    has_content: false,
                    content: ''
                }, args),
                template = data.has_content ? 'pum-shortcode-w-content' : 'pum-shortcode';

            return PUM_Templates.render(template, data);
        },
        modal: function (args) {
            var data = $.extend({}, {
                id: '',
                title: '',
                description: '',
                classes: '',
                save_button: I10n.save,
                cancel_button: I10n.cancel,
                content: ''
            }, args);

            return PUM_Templates.render('pum-modal', data);
        },
        tabs: function (args) {
            var classes = args.classes || [],
                data = $.extend({}, {
                    id: '',
                    vertical: true,
                    form: true,
                    classes: '',
                    tabs: {
                        general: {
                            label: 'General',
                            content: ''
                        }
                    }
                }, args);

            if (data.form) {
                classes.push('tabbed-form');
            }
            if (data.vertical) {
                classes.push('vertical-tabs');
            }

            data.classes = data.classes + ' ' + classes.join(' ');

            return PUM_Templates.render('pum-tabs', data);
        },
        section: function (args) {
            var data = $.extend({}, {
                classes: [],
                fields: []
            }, args);


            return PUM_Templates.render('pum-field-section', data);
        },
        field: function (args) {
            var fieldTemplate = 'pum-field-' + args.type,
                options = [],
                data = $.extend({}, {
                    type: 'text',
                    id: '',
                    id_prefix: '',
                    name: '',
                    label: null,
                    placeholder: '',
                    desc: null,
                    size: 'regular',
                    classes: [],
                    value: null,
                    select2: false,
                    multiple: false,
                    as_array: false,
                    options: [],
                    object_type: null,
                    object_key: null,
                    std: null,
                    min: 0,
                    max: 50,
                    step: 1,
                    unit: 'px',
                    required: false,
                    meta: {}
                }, args);

            if (!$('#tmpl-' + fieldTemplate).length) {
                if (args.type === 'objectselect' || args.type === 'postselect' || args.type === 'taxonomyselect') {
                    fieldTemplate = 'pum-field-select';
                }
                if (!$('#tmpl-' + fieldTemplate).length) {
                    return '';
                }
            }

            if (!data.value && args.std !== undefined) {
                data.value = args.std;
            }

            if ('string' === typeof data.classes) {
                data.classes = data.classes.split(' ');
            }

            if (args.class !== undefined) {
                data.classes.push(args.class);
            }

            if (data.required) {
                data.meta.required = true;
                data.classes.push('pum-required');
            }

            switch (args.type) {
            case 'select':
            case 'objectselect':
            case 'postselect':
            case 'taxonomyselect':
                if (data.options !== undefined) {
                    _.each(data.options, function (value, label) {
                        var selected = false;
                        if (data.multiple && data.value.indexOf(value) !== false) {
                            selected = 'selected';
                        } else if (!data.multiple && data.value == value) {
                            selected = 'selected';
                        }

                        options.push(
                            PUM_Templates.prepareMeta({
                                label: label,
                                value: value,
                                meta: {
                                    selected: selected
                                }
                            })
                        );

                    });

                    data.options = options;
                }

                if (data.multiple) {

                    data.meta.multiple = true;

                    if (data.as_array) {
                        data.name += '[]';
                    }

                    if (!data.value || !data.value.length) {
                        data.value = [];
                    }

                    if (typeof data.value === 'string') {
                        data.value = [data.value];
                    }

                }

                if (args.type !== 'select') {
                    data.select2 = true;
                    data.classes.push('pum-field-objectselect');
                    data.classes.push(args.type === 'postselect' ? 'pum-field-postselect' : 'pum-field-taxonomyselect');
                    data.meta['data-objecttype'] = args.type === 'postselect' ? 'post_type' : 'taxonomy';
                    data.meta['data-objectkey'] = args.type === 'postselect' ? args.post_type : args.taxonomy;
                    data.meta['data-current'] = data.value;
                }

                if (data.select2) {
                    data.classes.push('pum-select2');

                    if (data.placeholder) {
                        data.meta['data-placeholder'] = data.placeholder;
                    }
                }

                break;
            case 'multicheck':
                if (data.options !== undefined) {
                    _.each(data.options, function (value, label) {

                        options.push({
                            label: label,
                            value: value,
                            meta: {
                                checked: data.value.indexOf(value) >= 0
                            }
                        });

                    });

                    data.options = options;
                }
                break;
            case 'checkbox':
                if (parseInt(data.value, 10) === 1) {
                    //data.meta.checked = true;
                }

                data.meta.checked = !!data.value;
                break;
            case 'rangeslider':
                data.meta.step = data.step;
                data.meta.min = data.min;
                data.meta.max = data.max;
                break;
            case 'textarea':
                data.meta.cols = data.cols;
                data.meta.rows = data.rows;
                break;
            }

            data.field = PUM_Templates.render(fieldTemplate, data);

            return PUM_Templates.render('pum-field-wrapper', data);
        },
        prepareMeta: function (data) {
            // Convert meta JSON to attribute string.
            var _meta = [],
                key;

            for (key in data.meta) {
                if (data.meta.hasOwnProperty(key)) {
                    // Boolean attributes can only require attribute key, not value.
                    if ('boolean' === typeof data.meta[key]) {
                        // Only set truthy boolean attributes.
                        if (data.meta[key]) {
                            _meta.push(_.escape(key));
                        }
                    } else {
                        _meta.push(_.escape(key) + '="' + _.escape(data.meta[key]) + '"');
                    }
                }
            }

            data.meta = _meta.join(' ');
            return data;
        }

    };

}(jQuery, document));
var PUMTriggers;
(function ($, document, undefined) {
    "use strict";

    var I10n = pum_admin.I10n,
        defaults = pum_admin.defaults;

    PUMTriggers = {
        new_cookie: false,
        getLabel: function (type) {
            return I10n.labels.triggers[type].name;
        },
        getSettingsDesc: function (type, values) {
            var options = {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable: 'data'
                },
                template = _.template(I10n.labels.triggers[type].settings_column, null, options);
            values.I10n = I10n;
            return template(values);
        },
        renumber: function () {
            $('#pum_popup_triggers_list tbody tr').each(function () {
                var $this = $(this),
                    index = $this.parent().children().index($this),
                    originalIndex = $this.data('index');

                $this.data('index', index);

                $this.find('input').each(function () {
                    var replace_with = "[" + index + "]";
                    this.name = this.name.replace("[" + originalIndex + "]", replace_with).replace("[]", replace_with);
                });
            });
        },
        refreshDescriptions: function () {
            $('#pum_popup_triggers_list tbody tr').each(function () {
                var $row = $(this),
                    type = $row.find('.popup_triggers_field_type').val(),
                    values = JSON.parse($row.find('.popup_triggers_field_settings:first').val()),
                    cookie_text = PUMTriggers.cookie_column_value(values.cookie.name);

                $row.find('td.settings-column').html(PUMTriggers.getSettingsDesc(type, values));
                $row.find('td.cookie-column code').text(cookie_text);
            });
        },
        initEditForm: function (data) {
            var $form = $('.trigger-editor .pum-form'),
                type = $form.find('input[name="type"]').val(),
                $cookie = $('#name', $form),
                trigger_settings = data.trigger_settings,
                $cookies = $('#pum_popup_cookies_list tbody tr');

            if (!$cookies.length && type !== 'click_open') {
                PUMCookies.insertDefault();
                $cookies = $('#pum_popup_cookies_list tbody tr');
            }

            $cookies.each(function () {
                var settings = JSON.parse($(this).find('.popup_cookies_field_settings:first').val());
                if (!$cookie.find('option[value="' + settings.name + '"]').length) {
                    $('<option value="' + settings.name + '">' + settings.name + '</option>').appendTo($cookie);
                }
            });

            $cookie
                .val(trigger_settings.cookie.name)
                .trigger('change.pumselect2');
        },
        cookie_column_value: function (cookie_name) {
            var cookie_text = I10n.no_cookie;

            if (cookie_name instanceof Array) {
                cookie_text = cookie_name.join(', ');
            } else if (cookie_name !== null) {
                cookie_text = cookie_name;
            }
            return cookie_text;
        },
        append_click_selector_presets: function () {
            return $('#extra_selectors').each(function () {
                var $this = $(this),
                    template = _.template($('#tmpl-pum-click-selector-presets').html()),
                    $presets = $this.parents('.pum-field').find('.pum-click-selector-presets');

                if (!$presets.length) {
                    $this.before(template());
                    $presets = $this.parents('.pum-field').find('.pum-click-selector-presets');
                }

                $presets.position({
                    my: 'right center',
                    at: 'right center',
                    of: $this
                });
            });
        },
        toggle_click_selector_presets: function () {
            $(this).parent().toggleClass('open');
        },


        reset_click_selector_presets: function (e) {
            if (e !== undefined && $(e.target).parents('.pum-click-selector-presets').length) {
                return;
            }

            $('.pum-click-selector-presets').removeClass('open');
        },
        insert_click_selector_preset: function () {
            var $this = $(this),
                $input = $('#extra_selectors'),
                val = $input.val();

            if (val !== "") {
                val = val + ', ';
            }

            $input.val(val + $this.data('preset'));
            PUMTriggers.reset_click_selector_presets();
        }
    };


    PUMTriggers.refreshDescriptions();

    $(document)
        .on('pum_init', function () {
            PUMTriggers.append_click_selector_presets();
        })
        .on('click', '.pum-click-selector-presets > span', PUMTriggers.toggle_click_selector_presets)
        .on('click', '.pum-click-selector-presets li', PUMTriggers.insert_click_selector_preset)
        .on('click', PUMTriggers.reset_click_selector_presets)
        .on('select2:select pumselect2:select', '#pum-first-trigger', function () {
            var $this = $(this),
                type = $this.val(),
                id = 'pum-trigger-settings-' + type,
                modalID = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data = {};

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if ( type !== 'click_open' ) {
                data.trigger_settings.cookie.name = 'pum-' + $('#post_ID').val();
            }

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMTriggers.initEditForm(data);

            $this
                .val(null)
                .trigger('change');
        })
        .on('click', '#pum_popup_triggers .add-new', function () {
            var template = wp.template('pum-trigger-add-type');
            PUMModals.reload('#pum_trigger_add_type_modal', template());
        })
        .on('click', '#pum_popup_triggers_list .edit', function (e) {

            var $this = $(this),
                $row = $this.parents('tr:first'),
                type = $row.find('.popup_triggers_field_type').val(),
                id = 'pum-trigger-settings-' + type,
                modalID = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data = {
                    index: $row.parent().children().index($row),
                    type: type,
                    trigger_settings: JSON.parse($row.find('.popup_triggers_field_settings:first').val())
                };

            e.preventDefault();

            data.save_button_text = I10n.save;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMTriggers.initEditForm(data);
        })
        .on('click', '#pum_popup_triggers_list .remove', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first');

            e.preventDefault();

            if (window.confirm(I10n.confirm_delete_trigger)) {
                $row.remove();

                if (!$('#pum_popup_triggers_list tbody tr').length) {
                    $('#pum-first-trigger')
                        .val(null)
                        .trigger('change');
                    $('#pum_popup_trigger_fields').removeClass('has-triggers');
                }

                PUMTriggers.renumber();
            }
        })
        .on('submit', '#pum_trigger_add_type_modal .pum-form', function (e) {
            var type = $('#popup_trigger_add_type').val(),
                id = 'pum-trigger-settings-' + type,
                modalID = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data = {};

            e.preventDefault();

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if ( type !== 'click_open' ) {
                data.trigger_settings.cookie.name = 'pum-' + $('#post_ID').val();
            }

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMTriggers.initEditForm(data);
        })
        .on('submit', '.trigger-editor .pum-form', function (e) {
            var $form = $(this),
                type = $form.find('input.type').val(),
                values = $form.pumSerializeObject(),
                index = parseInt(values.index),
                $row = index >= 0 ? $('#pum_popup_triggers_list tbody tr').eq(index) : null,
                template = wp.template('pum-trigger-row'),
                $new_row;

            e.preventDefault();

            if (!index || index < 0) {
                values.index = $('#pum_popup_triggers_list tbody tr').length;
            }

            values.I10n = I10n;

            $new_row = template(values);

            if (!$row) {
                $('#pum_popup_triggers_list tbody').append($new_row);
            } else {
                $row.replaceWith($new_row);
            }

            PUMModals.closeAll();
            PUMTriggers.renumber();
            PUMTriggers.refreshDescriptions();

            $('#pum_popup_trigger_fields').addClass('has-triggers');

            if (values.trigger_settings.cookie.name !== null && values.trigger_settings.cookie.name.indexOf('add_new') >= 0) {
                PUMTriggers.new_cookie = values.index;
                $('#pum_popup_cookie_fields button.add-new').trigger('click');
            }
        })
        .ready(function () {
            PUMTriggers.refreshDescriptions();
            $('#pum-first-trigger')
                .val(null)
                .trigger('change');
        });

}(jQuery, document));
var PUMUtils;
(function ($, document, undefined) {
    "use strict";
    PUMUtils = {
        convert_meta_to_object: function (data) {
            var converted_data = {},
                element,
                property,
                key;

            for (key in data) {
                if (data.hasOwnProperty(key)) {
                    element = key.split(/_(.+)?/)[0];
                    property = key.split(/_(.+)?/)[1];
                    if (converted_data[element] === undefined) {
                        converted_data[element] = {};
                    }
                    converted_data[element][property] = data[key];
                }
            }
            return converted_data;
        },
        serialize_form: function ($form) {
            var serialized = {};
            $("[name]", $form).each(function () {
                var name = $(this).attr('name'),
                    value = $(this).val(),
                    nameBits = name.split('['),
                    previousRef = serialized,
                    i,
                    l = nameBits.length,
                    nameBit;
                for (i = 0; i < l; i += 1) {
                    nameBit = nameBits[i].replace(']', '');
                    if (!previousRef[nameBit]) {
                        previousRef[nameBit] = {};
                    }
                    if (i !== nameBits.length - 1) {
                        previousRef = previousRef[nameBit];
                    } else if (i === nameBits.length - 1) {
                        previousRef[nameBit] = value;
                    }
                }
            });
            return serialized;
        },
        convert_hex: function (hex, opacity) {
            if (undefined === hex) {
                return '';
            }
            if (undefined === opacity) {
                opacity = 100;
            }
            
            hex = hex.replace('#', '');
            var r = parseInt(hex.substring(0, 2), 16),
                g = parseInt(hex.substring(2, 4), 16),
                b = parseInt(hex.substring(4, 6), 16),
                result = 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')';
            return result;
        },
        debounce: function (callback, threshold) {
            var timeout;
            return function () {
                var context = this, params = arguments;
                window.clearTimeout(timeout);
                timeout = window.setTimeout(function () {
                    callback.apply(context, params);
                }, threshold);
            };
        },
        throttle: function (callback, threshold) {
            var suppress = false,
                clear = function () {
                    suppress = false;
                };
            return function () {
                if (!suppress) {
                    callback();
                    window.setTimeout(clear, threshold);
                    suppress = true;
                }
            };
        }
    };


    String.prototype.capitalize = function() {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };


}(jQuery, document));
/**
 * Popup Maker v1.4
 */

var PopMakeAdmin, PUM_Admin;
(function ($, document, undefined) {
    "use strict";

    var $document = $(document),
        I10n = pum_admin.I10n,
        defaults = pum_admin.defaults;

    PUM_Admin = {};

    PopMakeAdmin = {
        init: function () {
            //PopMakeAdmin.initialize_tabs();
            if ($('body.post-type-popup form#post').length) {
                PopMakeAdmin.initialize_popup_page();
            }
            if ($('body.post-type-popup_theme form#post').length) {
                PopMakeAdmin.initialize_theme_page();
            }
        },
        initialize_popup_page: function () {
            var update_size = function () {
                    if ($("#popup_display_size").val() === 'custom') {
                        $('.custom-size-only').show();
                        $('.responsive-size-only').hide();
                        if ($('#popup_display_custom_height_auto').is(':checked')) {
                            $('.custom-size-height-only').hide();
                        } else {
                            $('.custom-size-height-only').show();
                        }
                    } else {
                        $('.custom-size-only').hide();
                        if ($("#popup_display_size").val() !== 'auto') {
                            $('.responsive-size-only').show();
                            $('#popup_display_custom_height_auto').prop('checked', false);
                        } else {
                            $('.responsive-size-only').hide();
                        }
                    }
                },
                update_animation = function () {
                    $('.animation-speed, .animation-origin').hide();
                    if ($("#popup_display_animation_type").val() === 'fade') {
                        $('.animation-speed').show();
                    } else {
                        if ($("#popup_display_animation_type").val() !== 'none') {
                            $('.animation-speed, .animation-origin').show();
                        }
                    }
                },
                update_location = function () {
                    var $this = $('#popup_display_location'),
                        table = $this.parents('table'),
                        val = $this.val();
                    $('tr.top, tr.right, tr.left, tr.bottom', table).hide();
                    if (val.indexOf("top") >= 0) {
                        $('tr.top').show();
                    }
                    if (val.indexOf("left") >= 0) {
                        $('tr.left').show();
                    }
                    if (val.indexOf("bottom") >= 0) {
                        $('tr.bottom').show();
                    }
                    if (val.indexOf("right") >= 0) {
                        $('tr.right').show();
                    }
                };

            $('#popuptitlediv').insertAfter('#titlediv');

            $('#title').prop('required', true);

            $(document)
                .on('change', '#popup_theme', function () {
                    var $this = $(this),
                        $link = $('#edit_theme_link'),
                        val = $this.val();

                    $link.attr('href', $link.data('baseurl')+val);
                })
                .on('keydown', '#popuptitle', function (event) {
                    var keyCode = event.keyCode || event.which;
                    if (9 === keyCode) {
                        event.preventDefault();
                        $('#title').focus();
                    }
                })
                .on('keydown', '#title, #popuptitle', function (event) {
                    var keyCode = event.keyCode || event.which,
                        target;
                    if (!event.shiftKey && 9 === keyCode) {
                        event.preventDefault();
                        target = $(this).attr('id') === 'title' ? '#popuptitle' : '#insert-media-button';
                        $(target).focus();
                    }
                })
                .on('keydown', '#popuptitle, #insert-media-button', function (event) {
                    var keyCode = event.keyCode || event.which,
                        target;
                    if (event.shiftKey && 9 === keyCode) {
                        event.preventDefault();
                        target = $(this).attr('id') === 'popuptitle' ? '#title' : '#popuptitle';
                        $(target).focus();
                    }
                })
                .on('click', '#popup_display_custom_height_auto', function () {
                    update_size();
                })
                .on('change', "#popup_display_size", function () {
                    if ($("#popup_display_size").val() !== 'custom' && $("#popup_display_size").val() !== 'auto') {
                        $('#popup_display_position_fixed, #popup_display_scrollable_content').prop('checked', false);
                    }
                    update_size();
                })
                .on('change', "#popup_display_animation_type", function () {
                    update_animation();
                })
                .on('change', '#popup_display_location', function () {
                    update_location();
                });

            update_size();
            update_animation();
            update_location();
        },
        theme_page_listeners: function () {
            var self = this;

            $('.empreview .example-popup-overlay, .empreview .example-popup, .empreview .title, .empreview .content, .empreview .close-popup').css('cursor', 'pointer');
            $(document)
                .on('click', '.empreview .example-popup-overlay, .empreview .example-popup, .empreview .title, .empreview .content, .empreview .close-popup', function (event) {
                    var $this = $(this),
                        clicked_class = $this.attr('class'),
                        pos = 0;

                    event.preventDefault();
                    event.stopPropagation();

                    switch( clicked_class) {
                    case 'example-popup-overlay':
                        pos = $('#popmake_popup_theme_overlay').offset().top;
                        break;
                    case 'example-popup':
                        pos = $('#popmake_popup_theme_container').offset().top;
                        break;
                    case 'title':
                        pos = $('#popmake_popup_theme_title').offset().top;
                        break;
                    case 'content':
                        pos = $('#popmake_popup_theme_content').offset().top;
                        break;
                    case 'close-popup':
                        pos = $('#popmake_popup_theme_close').offset().top;
                        break;
                    }

                    $("html, body").animate({
                        scrollTop: pos + 'px'
                    });
                })
                .on('change', 'select.font-family', function () {
                    $('select.font-weight option, select.font-style option', $(this).parents('table')).prop('selected', false);
                    self.update_font_selectboxes();
                })
                .on('change', 'select.font-weight, select.font-style', function () {
                    self.update_font_selectboxes();
                })
                .on('change input focusout', 'select, input', function () {
                    self.update_theme();
                })
                .on('change', 'select.border-style', function () {
                    var $this = $(this);
                    if ($this.val() === 'none') {
                        $this.parents('table').find('.border-options').hide();
                    } else {
                        $this.parents('table').find('.border-options').show();
                    }
                })
                .on('change', '#popup_theme_close_location', function () {
                    var $this = $(this),
                        table = $this.parents('table');
                    $('tr.topleft, tr.topright, tr.bottomleft, tr.bottomright', table).hide();
                    $('tr.' + $this.val(), table).show();
                });
        },
        update_theme: function () {
            var form_values = $("[name^='popup_theme_']").serializeArray(),
                theme = {},
                i;
            for (i = 0; form_values.length > i; i += 1) {
                if (form_values[i].name.indexOf('popup_theme_') === 0) {
                    theme[form_values[i].name.replace('popup_theme_', '')] = form_values[i].value;
                }
            }
            this.retheme_popup(theme);
        },
        theme_preview_scroll: function () {
            var $preview = $('#popmake-theme-editor .empreview, body.post-type-popup_theme form#post #popmake_popup_theme_preview'),
                $parent = $preview.parent(),
                startscroll = $preview.offset().top - 50;
            $(window).on('scroll', function () {
                if ($('> .postbox:visible', $parent).index($preview) === ($('> .postbox:visible', $parent).length - 1) && $(window).scrollTop() >= startscroll) {
                    $preview.css({
                        left: $preview.offset().left,
                        width: $preview.width(),
                        height: $preview.height(),
                        position: 'fixed',
                        top: 50
                    });
                } else {
                    $preview.removeAttr('style');
                }
            });
        },
        update_font_selectboxes: function () {
            return $('select.font-family').each(function () {
                var $this = $(this),
                    $font_weight = $this.parents('table').find('select.font-weight'),
                    $font_style = $this.parents('table').find('select.font-style'),
                    $font_weight_options = $font_weight.find('option'),
                    $font_style_options = $font_style.find('option'),
                    font,
                    i;


                // Google Font Chosen
                if (popmake_google_fonts[$this.val()] !== undefined) {
                    font = popmake_google_fonts[$this.val()];

                    $font_weight_options.hide();
                    $font_style_options.hide();

                    if (font.variants.length) {
                        for (i = 0; font.variants.length > i; i += 1) {
                            if (font.variants[i] === 'regular') {
                                $('option[value=""]', $font_weight).show();
                                $('option[value=""]', $font_style).show();
                            } else {
                                if (font.variants[i].indexOf('italic') >= 0) {

                                    $('option[value="italic"]', $font_style).show();
                                }
                                $('option[value="' + parseInt(font.variants[i], 10) + '"]', $font_weight).show();
                            }
                        }
                    }
                    // Standard Font Chosen
                } else {
                    $font_weight_options.show();
                    $font_style_options.show();
                }

                $font_weight.parents('tr:first').show();
                if ($font_weight.find('option:visible').length <= 1) {
                    $font_weight.parents('tr:first').hide();
                } else {
                    $font_weight.parents('tr:first').show();
                }

                $font_style.parents('tr:first').show();
                if ($font_style.find('option:visible').length <= 1) {
                    $font_style.parents('tr:first').hide();
                } else {
                    $font_style.parents('tr:first').show();
                }
            });
        },
        convert_theme_for_preview: function (theme) {
            return;
            //$.fn.popmake.themes[popmake_default_theme] = PUMUtils.convert_meta_to_object(theme);
        },
        initialize_theme_page: function () {
            $('#popuptitlediv').insertAfter('#titlediv');

            var self = this,
                table = $('#popup_theme_close_location').parents('table');
            self.update_theme();
            self.theme_page_listeners();
            self.theme_preview_scroll();
            self.update_font_selectboxes();

            $(document)
                .on('click', '.popmake-preview', function (e) {
                    e.preventDefault();
                    $('#popmake-preview, #popmake-overlay').css({visibility: "visible"}).show();
                })
                .on('click', '.popmake-close', function () {
                    $('#popmake-preview, #popmake-overlay').hide();
                });

            $('select.border-style').each(function () {
                var $this = $(this);
                if ($this.val() === 'none') {
                    $this.parents('table').find('.border-options').hide();
                } else {
                    $this.parents('table').find('.border-options').show();
                }
            });

            $('.color-picker.background-color').each(function () {
                var $this = $(this);
                if ($this.val() === '') {
                    $this.parents('table').find('.background-opacity').hide();
                } else {
                    $this.parents('table').find('.background-opacity').show();
                }
            });

            $('tr.topleft, tr.topright, tr.bottomleft, tr.bottomright', table).hide();
            switch ($('#popup_theme_close_location').val()) {
            case "topleft":
                $('tr.topleft', table).show();
                break;
            case "topright":
                $('tr.topright', table).show();
                break;
            case "bottomleft":
                $('tr.bottomleft', table).show();
                break;
            case "bottomright":
                $('tr.bottomright', table).show();
                break;
            }
        },
        retheme_popup: function (theme) {
            var $overlay = $('.empreview .example-popup-overlay, #popmake-overlay'),
                $container = $('.empreview .example-popup, #popmake-preview'),
                $title = $('.title, .popmake-title', $container),
                $content = $('.content, .popmake-content', $container),
                $close = $('.close-popup, .popmake-close', $container),
                container_inset = theme.container_boxshadow_inset === 'yes' ? 'inset ' : '',
                close_inset = theme.close_boxshadow_inset === 'yes' ? 'inset ' : '',
                link;

            this.convert_theme_for_preview(theme);

            if (popmake_google_fonts[theme.title_font_family] !== undefined) {

                link = "//fonts.googleapis.com/css?family=" + theme.title_font_family;

                if (theme.title_font_weight !== 'normal') {
                    link += ":" + theme.title_font_weight;
                }
                if (theme.title_font_style === 'italic') {
                    if (link.indexOf(':') === -1) {
                        link += ":";
                    }
                    link += "italic";
                }
                $('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
            }
            if (popmake_google_fonts[theme.content_font_family] !== undefined) {

                link = "//fonts.googleapis.com/css?family=" + theme.content_font_family;

                if (theme.content_font_weight !== 'normal') {
                    link += ":" + theme.content_font_weight;
                }
                if (theme.content_font_style === 'italic') {
                    if (link.indexOf(':') === -1) {
                        link += ":";
                    }
                    link += "italic";
                }
                $('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
            }
            if (popmake_google_fonts[theme.close_font_family] !== undefined) {

                link = "//fonts.googleapis.com/css?family=" + theme.close_font_family;

                if (theme.close_font_weight !== 'normal') {
                    link += ":" + theme.close_font_weight;
                }
                if (theme.close_font_style === 'italic') {
                    if (link.indexOf(':') === -1) {
                        link += ":";
                    }
                    link += "italic";
                }
                $('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
            }

            $overlay.removeAttr('style').css({
                backgroundColor: PUMUtils.convert_hex(theme.overlay_background_color, theme.overlay_background_opacity)
            });
            $container.removeAttr('style').css({
                padding: theme.container_padding + 'px',
                backgroundColor: PUMUtils.convert_hex(theme.container_background_color, theme.container_background_opacity),
                borderStyle: theme.container_border_style,
                borderColor: theme.container_border_color,
                borderWidth: theme.container_border_width + 'px',
                borderRadius: theme.container_border_radius + 'px',
                boxShadow: container_inset + theme.container_boxshadow_horizontal + 'px ' + theme.container_boxshadow_vertical + 'px ' + theme.container_boxshadow_blur + 'px ' + theme.container_boxshadow_spread + 'px ' + PUMUtils.convert_hex(theme.container_boxshadow_color, theme.container_boxshadow_opacity)
            });
            $title.removeAttr('style').css({
                color: theme.title_font_color,
                lineHeight: theme.title_line_height + 'px',
                fontSize: theme.title_font_size + 'px',
                fontFamily: theme.title_font_family,
                fontStyle: theme.title_font_style,
                fontWeight: theme.title_font_weight,
                textAlign: theme.title_text_align,
                textShadow: theme.title_textshadow_horizontal + 'px ' + theme.title_textshadow_vertical + 'px ' + theme.title_textshadow_blur + 'px ' + PUMUtils.convert_hex(theme.title_textshadow_color, theme.title_textshadow_opacity)
            });
            $content.removeAttr('style').css({
                color: theme.content_font_color,
                //fontSize: theme.content_font_size+'px',
                fontFamily: theme.content_font_family,
                fontStyle: theme.content_font_style,
                fontWeight: theme.content_font_weight
            });
            $close.html(theme.close_text).removeAttr('style').css({
                padding: theme.close_padding + 'px',
                height: theme.close_height > 0 ? theme.close_height + 'px' : 'auto',
                width: theme.close_width > 0 ? theme.close_width + 'px' : 'auto',
                backgroundColor: PUMUtils.convert_hex(theme.close_background_color, theme.close_background_opacity),
                color: theme.close_font_color,
                lineHeight: theme.close_line_height + 'px',
                fontSize: theme.close_font_size + 'px',
                fontFamily: theme.close_font_family,
                fontWeight: theme.close_font_weight,
                fontStyle: theme.close_font_style,
                borderStyle: theme.close_border_style,
                borderColor: theme.close_border_color,
                borderWidth: theme.close_border_width + 'px',
                borderRadius: theme.close_border_radius + 'px',
                boxShadow: close_inset + theme.close_boxshadow_horizontal + 'px ' + theme.close_boxshadow_vertical + 'px ' + theme.close_boxshadow_blur + 'px ' + theme.close_boxshadow_spread + 'px ' + PUMUtils.convert_hex(theme.close_boxshadow_color, theme.close_boxshadow_opacity),
                textShadow: theme.close_textshadow_horizontal + 'px ' + theme.close_textshadow_vertical + 'px ' + theme.close_textshadow_blur + 'px ' + PUMUtils.convert_hex(theme.close_textshadow_color, theme.close_textshadow_opacity)
            });
            switch (theme.close_location) {
            case "topleft":
                $close.css({
                    top: theme.close_position_top + 'px',
                    left: theme.close_position_left + 'px'
                });
                break;
            case "topright":
                $close.css({
                    top: theme.close_position_top + 'px',
                    right: theme.close_position_right + 'px'
                });
                break;
            case "bottomleft":
                $close.css({
                    bottom: theme.close_position_bottom + 'px',
                    left: theme.close_position_left + 'px'
                });
                break;
            case "bottomright":
                $close.css({
                    bottom: theme.close_position_bottom + 'px',
                    right: theme.close_position_right + 'px'
                });
                break;
            }
            $(document).trigger('popmake-admin-retheme', [theme]);
        }

    };
    $document.ready(function () {
        PopMakeAdmin.init();
        $document.trigger('pum_init');
    });
}(jQuery, document));
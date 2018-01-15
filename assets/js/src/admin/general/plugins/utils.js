/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    String.prototype.capitalize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

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

    var utils = {
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
        object_to_array: function (object) {
            var array = [],
                i;

            // Convert facets to array (JSON.stringify breaks arrays).
            if (typeof object === 'object') {
                for (i in object) {
                    array.push(object[i]);
                }
                object = array;
            }

            return object;
        },
        checked: function (val1, val2, print) {
            var checked = false;
            if (typeof val1 === 'object' && typeof val2 === 'string' && jQuery.inArray(val2, val1) !== -1) {
                checked = true;
            } else if (typeof val2 === 'object' && typeof val1 === 'string' && jQuery.inArray(val1, val2) !== -1) {
                checked = true;
            } else if (val1 === val2) {
                checked = true;
            } else if (val1 == val2) {
                checked = true;
            }

            if (print !== undefined && print) {
                return checked ? ' checked="checked"' : '';
            }
            return checked;
        },
        selected: function (val1, val2, print) {
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
        },
        serializeForm: function (options) {
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
                        parsedName.push('');
                    }

                    // jQuery.val() is used to simplify of getting values
                    // from the custom controls (which follow jQuery .val() API) and Multiple Select
                    storeValue(values, parsedName, $(this).val());
                }
            });

            return values;
        }

    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.utils = utils;

    // @deprecated 1.7.0 Here for backward compatibility.
    window.PUMUtils = utils;

    $.fn.pumSerializeForm = utils.serializeForm;
}(jQuery));
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
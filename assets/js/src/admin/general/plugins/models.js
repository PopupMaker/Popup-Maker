/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";

    var models = {
        field: function (args) {
            return $.extend(true, {}, {
                type: 'text',
                id: '',
                id_prefix: '',
                name: '',
                label: null,
                placeholder: '',
                desc: null,
                dynamic_desc: null,
                size: 'regular',
                classes: [],
                dependencies: "",
                value: null,
                select2: false,
                allow_html: false,
                multiple: false,
                as_array: false,
                options: [],
                object_type: null,
                object_key: null,
                std: null,
                min: 0,
                max: 50,
                force_minmax: false,
                step: 1,
                unit: 'px',
                units: {},
                required: false,
                desc_position: 'bottom',
                meta: {}
            }, args);
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.models = models;
}(jQuery));
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
            var data = $.extend(true, {}, {
                    tag: '',
                    meta: {},
                    has_content: false,
                    content: ''
                }, args),
                template = data.has_content ? 'pum-shortcode-w-content' : 'pum-shortcode';

            return PUM_Templates.render(template, data);
        },
        modal: function (args) {
            var data = $.extend(true, {}, {
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
                data = $.extend(true, {}, {
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
            var data = $.extend(true, {}, {
                classes: [],
                fields: []
            }, args);


            return PUM_Templates.render('pum-field-section', data);
        },
        field: function (args) {
            var fieldTemplate = 'pum-field-' + args.type,
                options = [],
                data = $.extend(true, {}, {
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
                    data.meta.checked = true;
                }
                break;
            case 'rangeslider':
                data.meta.readonly = true;
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
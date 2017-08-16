var PUMTriggers;
(function ($, document, undefined) {
    "use strict";

    var I10n     = pum_admin_vars.I10n,
        defaults = pum_admin_vars.defaults,
        current_editor;

    var triggers = {
        new_cookie: false,
        get_triggers: function () {
            return window.pum_popup_settings_editor.triggers;
        },
        get_trigger: function (type) {
            var triggers = this.get_triggers(),
                trigger  = triggers[type] !== 'undefined' ? triggers[type] : false;

            if (!trigger) {
                return false;
            }

            // To help with processing older triggers still in use.
            trigger['updated'] = trigger.updated !== undefined && trigger.updated;

            if (trigger && typeof trigger === 'object' && typeof trigger.fields === 'object' && Object.keys(trigger.fields).length) {
                trigger = this.parseFields(trigger);
            }


            return trigger;
        },
        parseFields: function (trigger) {
            _.each(trigger.fields, function (fields, tabID) {
                _.each(fields, function (field, fieldID) {

                    trigger.fields[tabID][fieldID].name = 'trigger_settings[' + fieldID + ']';

                    if (trigger.fields[tabID][fieldID].id === '') {
                        trigger.fields[tabID][fieldID].id = 'trigger_settings_' + fieldID;
                    }
                });
            });

            return trigger;
        },
        parseValues: function (values, type) {


            return values;
        },
        select_list: function () {
            var i,
                conditions = PUM_Admin.utils.object_to_array(triggers.get_triggers()),
                options    = {};

            for (i = 0; i < conditions.length; i++) {
                options[conditions[i].id] = conditions[i].name;
            }

            return options;
        },
        rows: {
            add: function (editor, trigger) {
                var $editor  = $(editor),
                    data     = {
                        index: trigger.index !== null && trigger.index >= 0 ? trigger.index : $editor.find('table.list-table tbody tr').length,
                        type: trigger.type,
                        settings: trigger.settings || {}
                    },
                    $row     = $editor.find('tbody tr').eq(data.index),
                    $new_row = PUM_Admin.templates.render('pum-trigger-row', data);

                if ($row.length) {
                    $row.replaceWith($new_row);
                } else {
                    $editor.find('tbody').append($new_row);
                }

                $editor.addClass('has-list-items');

                triggers.renumber();
                triggers.refreshDescriptions();
            },
            remove: function ($trigger) {
                var $editor = $trigger.parents('.pum-popup-trigger-editor');

                $trigger.remove();
                triggers.renumber();

                if ($editor.find('table.list-table tbody tr').length === 0) {
                    $editor.removeClass('has-list-items');

                    $('#pum-first-trigger')
                        .val(null)
                        .trigger('change');
                }
            }
        },
        template: {
            form: function (type, values, callback) {
                var trigger  = triggers.get_trigger(type),
                    modalID  = '#pum_trigger_settings',
                    firstTab = Object.keys(trigger.fields)[0];

                values = values || {};
                values.type = type;
                values.index = values.index || null;

                // Add hidden index & type fields.
                trigger.fields[firstTab] = $.extend(true, trigger.fields[firstTab], {
                    index: {
                        type: 'hidden',
                        name: 'index',
                        std: null
                    },
                    type: {
                        type: 'hidden',
                        name: 'type'
                    }
                });

                PUM_Admin.modals.reload(modalID, PUM_Admin.templates.modal({
                    id: 'pum_trigger_settings',
                    title: trigger.modal_title || trigger.name,
                    classes: 'tabbed-content',
                    save_button: values.index !== null ? I10n.update : I10n.add,
                    content: PUM_Admin.forms.render({
                        id: 'pum_trigger_settings_form',
                        tabs: trigger.tabs || {},
                        fields: trigger.fields || {}
                    }, values || {})
                }));

                $(modalID + ' form').on('submit', callback || function (event) {
                    event.preventDefault();
                    PUM_Admin.modals.closeAll();
                });

                PUMTriggers.initEditForm(values);
            },
            editor: function (args) {
                var data = $.extend(true, {}, {
                    triggers: []
                }, args);

                data.triggers = PUM_Admin.utils.object_to_array(data.triggers);

                return PUM_Admin.templates.render('pum-trigger-editor', data);
            },
            row: function (args) {
                var data = $.extend(true, {}, {
                    index: '',
                    type: '',
                    settings: {
                        cookie_name: ""
                    }
                }, args);

                return PUM_Admin.templates.render('pum-trigger-row', data);
            },
            selectbox: function (args) {
                var data = $.extend(true, {}, {
                    id: null,
                    name: null,
                    type: 'select',
                    group: '',
                    index: '',
                    value: null,
                    select2: true,
                    classes: [],
                    options: triggers.select_list()
                }, args);

                if (data.id === null) {
                    data.id = 'popup_settings_triggers_' + data.index + '_type';
                }

                if (data.name === null) {
                    data.name = 'popup_settings[triggers][' + data.index + '][type]';
                }

                return PUM_Admin.templates.field(data);
            }
        },
        /* @deprecated */
        getLabel: function (type) {
            var trigger = triggers.get_trigger(type);

            if (!trigger) {
                return false;
            }

            return trigger.name;
        },
        getSettingsDesc: function (type, values) {
            var trigger = triggers.get_trigger(type),
                options = {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable: 'data'
                },
                template;

            if (!trigger) {
                return false;
            }

            template = _.template(trigger.settings_column, null, options);
            //values.I10n = I10n;
            return template(values);
        },
        renumber: function () {
            $('.pum-popup-trigger-editor table.list-table tbody tr').each(function () {
                var $this         = $(this),
                    index         = $this.parent().children().index($this),
                    originalIndex = $this.data('index');

                $this.data('index', index);

                // TODO use :input or [name].
                $this.find('input').each(function () {
                    // TODO replace this with the newer method in conditions.renumber

                    var replace_with = "[" + index + "]";
                    this.name = this.name.replace("[" + originalIndex + "]", replace_with).replace("[]", replace_with);
                });
            });
        },
        refreshDescriptions: function () {
            $('.pum-popup-trigger-editor table.list-table tbody tr').each(function () {
                var $row        = $(this),
                    type        = $row.find('.popup_triggers_field_type').val(),
                    values      = JSON.parse($row.find('.popup_triggers_field_settings:first').val()),
                    cookie_text = PUM_Admin.triggers.cookie_column_value(values.cookie_name);

                $row.find('td.settings-column').html(PUM_Admin.triggers.getSettingsDesc(type, values));
                $row.find('td.cookie-column code').text(cookie_text);
            });
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
            var $field = $('#extra_selectors'),
                template,
                $presets;

            if (!$field.length || $field.hasClass('pum-click-selector-presets-initialized')) {
                return;
            }

            template = PUM_Admin.templates.render('pum-click-selector-presets');
            $presets = $field.parents('.pum-field').find('.pum-click-selector-presets');


            if (!$presets.length) {
                $field.before(template);
                $field.addClass('pum-click-selector-presets-initialized');
                $presets = $field.parents('.pum-field').find('.pum-click-selector-presets');
            }

            $presets.position({
                my: 'right center',
                at: 'right center',
                of: $field
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
            var $this  = $(this),
                $input = $('#extra_selectors'),
                val    = $input.val();

            if (val !== "") {
                val = val + ', ';
            }

            $input.val(val + $this.data('preset'));
            PUM_Admin.triggers.reset_click_selector_presets();
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.triggers = triggers;

    $(document)
        .on('pum_init', function () {
            PUM_Admin.triggers.append_click_selector_presets();
        })
        .on('click', '.pum-click-selector-presets > span', PUM_Admin.triggers.toggle_click_selector_presets)
        .on('click', '.pum-click-selector-presets li', PUM_Admin.triggers.insert_click_selector_preset)
        .on('click', PUM_Admin.triggers.reset_click_selector_presets)
        .on('select2:select pumselect2:select', '#pum-first-trigger', function () {
            var $this   = $(this),
                $editor = $this.parents('.pum-popup-trigger-editor'),
                type    = $this.val(),
                trigger = triggers.get_trigger(type),
                values  = {};

            if (type !== 'click_open') {
                values.cookie_name = 'pum-' + $('#post_ID').val();
            }

            triggers.template.form(type, values, function (event) {
                var $form  = $(this),
                    type   = $form.find('input#type').val(),
                    values = $form.pumSerializeObject(),
                    index  = parseInt(values.index);

                event.preventDefault();

                if (!index || index < 0) {
                    index = $editor.find('tbody tr').length;
                }

                triggers.rows.add($editor, {
                    index: index,
                    type: type,
                    settings: values.trigger_settings
                });

                PUM_Admin.modals.closeAll();

                if (values.trigger_settings.cookie_name !== undefined && values.trigger_settings.cookie_name.indexOf('add_new') >= 0) {
                    PUM_Admin.triggers.new_cookie = values.index;
                    $('#pum_popup_cookie_fields button.pum-add-new').trigger('click');
                }
            });

            $this
                .val(null)
                .trigger('change');
        })
        // Add New Triggers
        .on('click', '.pum-popup-trigger-editor .pum-add-new', function () {
            current_editor = $(this).parents('.pum-popup-trigger-editor');
            var template = wp.template('pum-trigger-add-type');
            PUM_Admin.modals.reload('#pum_trigger_add_type_modal', template({I10n: I10n}));
        })
        .on('submit', '#pum_trigger_add_type_modal .pum-form', function (event) {
            var $editor = current_editor,
                type    = $('#popup_trigger_add_type').val(),
                values  = {};

            event.preventDefault();

            if (type !== 'click_open') {
                values.cookie_name = 'pum-' + $('#post_ID').val();
            }

            triggers.template.form(type, values, function (event) {
                var $form  = $(this),
                    type   = $form.find('input#type').val(),
                    values = $form.pumSerializeObject(),
                    index  = parseInt(values.index);

                event.preventDefault();

                if (!index || index < 0) {
                    index = $editor.find('tbody tr').length;
                }

                triggers.rows.add($editor, {
                    index: index,
                    type: type,
                    settings: values.trigger_settings
                });

                PUM_Admin.modals.closeAll();

                if (values.trigger_settings.cookie_name !== undefined && values.trigger_settings.cookie_name.indexOf('add_new') >= 0) {
                    PUM_Admin.triggers.new_cookie = values.index;
                    $('#pum_popup_cookie_fields button.pum-add-new').trigger('click');
                }
            });
        })


    ;

    PUMTriggers = {
        initEditForm: function (data) {
            var $form            = $('.trigger-editor .pum-form'),
                type             = $form.find('input#type').val(),
                trigger          = triggers.get_trigger(type),
                $cookie          = $('#name', $form),
                trigger_settings = data.trigger_settings,
                $cookies         = $('#pum_popup_cookies_list tbody tr');

            if (!trigger) {
                return;
            }

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
                .val(trigger_settings.cookie_name)
                .trigger('change.pumselect2');
        }

    };

    $(document)
        .on('click', '#pum_popup_triggers_list .edit', function (e) {

            var $this    = $(this),
                $row     = $this.parents('tr:first'),
                type     = $row.find('.popup_triggers_field_type').val(),
                id       = 'pum-trigger-settings-' + type,
                modalID  = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data     = {
                    index: $row.parent().children().index($row),
                    type: type,
                    trigger_settings: JSON.parse($row.find('.popup_triggers_field_settings:first').val())
                };

            e.preventDefault();

            data.save_button_text = I10n.save;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUM_Admin.modals.reload(modalID, template(data));
            PUM_Admin.triggers.initEditForm(data);
        })
        .on('click', '#pum_popup_triggers_list .remove', function (e) {
            var $this = $(this),
                $row  = $this.parents('tr:first');

            e.preventDefault();

            if (window.confirm(I10n.confirm_delete_trigger)) {
                $row.remove();

                if (!$('#pum_popup_triggers_list tbody tr').length) {
                    $('#pum-first-trigger')
                        .val(null)
                        .trigger('change');
                    $('#pum_popup_trigger_fields').removeClass('has-list-items');
                }

                PUM_Admin.triggers.renumber();
            }
        })
        .on('submit', '.trigger-editor .pum-form', function (e) {
            var $form    = $(this),
                type     = $form.find('input.type').val(),
                values   = $form.pumSerializeObject(),
                index    = parseInt(values.index),
                $row     = index >= 0 ? $('#pum_popup_triggers_list tbody tr').eq(index) : null,
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

            PUM_Admin.modals.closeAll();
            PUM_Admin.triggers.renumber();
            PUM_Admin.triggers.refreshDescriptions();

            $('#pum_popup_trigger_fields').addClass('has-list-items');

            if (values.trigger_settings.cookie_name !== null && values.trigger_settings.cookie_name.indexOf('add_new') >= 0) {
                PUM_Admin.triggers.new_cookie = values.index;
                $('#pum_popup_cookie_fields button.pum-add-new').trigger('click');
            }
        })
        .ready(function () {
            PUM_Admin.triggers.refreshDescriptions();
            $('#pum-first-trigger')
                .val(null)
                .trigger('change');
        });

}(jQuery, document));

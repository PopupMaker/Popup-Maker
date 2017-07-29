var PUMTriggers;
(function ($, document, undefined) {
    "use strict";

    var I10n     = pum_admin_vars.I10n,
        defaults = pum_admin_vars.defaults;

    var triggers = {
        get_triggers: function () {
            return window.pum_popup_settings_editor.triggers;
        },
        triggers: {
            add: function (editor, type, settings) {
                var $editor = $(editor),
                    data    = {
                        index: $editor.find('table.list-table tbody tr').length,
                        type: type,
                        settings: settings || {}
                    };


                $editor.find('table.list-table tbody').append(triggers.template.trigger(data));
                $editor.addClass('has-list-items');
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
                        cookie: {
                            name: ""
                        }
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
                    options: triggers.get_triggers()
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
            return I10n.labels.triggers[type].name;
        },
        getSettingsDesc: function (type, values) {
            var options  = {
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
                    cookie_text = PUM_Admin.triggers.cookie_column_value(values.cookie.name);

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
            // TODO is this the right selector now?
            return $('#extra_selectors').each(function () {
                var $this    = $(this),
                    template = PUM_Admin.templates.render('pum-click-selector-presets'),
                    $presets = $this.parents('.pum-field').find('.pum-click-selector-presets');

                if (!$presets.length) {
                    $this.before(template);
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
            var $this  = $(this),
                // TODO is this the right selector.
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

    PUMTriggers = {
        new_cookie: false,
        initEditForm: function (data) {
            var $form            = $('.trigger-editor .pum-form'),
                type             = $form.find('input[name="type"]').val(),
                $cookie          = $('#name', $form),
                trigger_settings = data.trigger_settings,
                $cookies         = $('#pum_popup_cookies_list tbody tr');

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

    };

    $(document)
        .on('pum_init', function () {
            PUM_Admin.triggers.append_click_selector_presets();
        })
        .on('click', '.pum-click-selector-presets > span', PUM_Admin.triggers.toggle_click_selector_presets)
        .on('click', '.pum-click-selector-presets li', PUM_Admin.triggers.insert_click_selector_preset)
        .on('click', PUM_Admin.triggers.reset_click_selector_presets)
        .on('select2:select pumselect2:select', '#pum-first-trigger', function () {
            var $this    = $(this),
                type     = $this.val(),
                id       = 'pum-trigger-settings-' + type,
                modalID  = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data     = {};

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if (type !== 'click_open') {
                data.trigger_settings.cookie.name = 'pum-' + $('#post_ID').val();
            }

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUM_Admin.modals.reload(modalID, template(data));
            PUM_Admin.triggers.initEditForm(data);

            $this
                .val(null)
                .trigger('change');
        })
        .on('click', '#pum_popup_triggers .add-new', function () {
            var template = wp.template('pum-trigger-add-type');
            PUM_Admin.modals.reload('#pum_trigger_add_type_modal', template());
        })
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
        .on('submit', '#pum_trigger_add_type_modal .pum-form', function (e) {
            var type     = $('#popup_trigger_add_type').val(),
                id       = 'pum-trigger-settings-' + type,
                modalID  = '#' + id.replace(/-/g, '_'),
                template = wp.template(id),
                data     = {};

            e.preventDefault();

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if (type !== 'click_open') {
                data.trigger_settings.cookie.name = 'pum-' + $('#post_ID').val();
            }

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUM_Admin.modals.reload(modalID, template(data));
            PUM_Admin.triggers.initEditForm(data);
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

            if (values.trigger_settings.cookie.name !== null && values.trigger_settings.cookie.name.indexOf('add_new') >= 0) {
                PUM_Admin.triggers.new_cookie = values.index;
                $('#pum_popup_cookie_fields button.add-new').trigger('click');
            }
        })
        .ready(function () {
            PUM_Admin.triggers.refreshDescriptions();
            $('#pum-first-trigger')
                .val(null)
                .trigger('change');
        });

}(jQuery, document));
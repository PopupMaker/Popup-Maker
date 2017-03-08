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

            if (PUMTriggers.new_cookie >= 0) {
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
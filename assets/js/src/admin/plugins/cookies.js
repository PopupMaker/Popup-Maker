var PUMCookies;
(function ($) {
    "use strict";

    var I10n = pum_admin.I10n,
        defaults = pum_admin.defaults;

    PUMCookies = {
        getLabel: function (type) {
            return I10n.labels.cookies[type].name;
        },
        getSettingsDesc: function (type, values) {
            var template = _.template(I10n.labels.cookies[type].settings_column);
            values.I10n = I10n;
            return template(values);
        },
        renumber: function () {
            $('#pum_popup_cookies_list tbody tr').each(function () {
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
            $('#pum_popup_cookies_list tbody tr').each(function () {
                var $row = $(this),
                    type = $row.find('.popup_cookies_field_type').val(),
                    values = JSON.parse($row.find('.popup_cookies_field_settings:first').val());

                $row.find('td:eq(1)').html(PUMCookies.getSettingsDesc(type, values));
            });
        }
    };

    PUMCookies.refreshDescriptions();

    $(document)
        .on('click', '#pum_popup_cookies .add-new', function () {
            var template = _.template($('script#pum_cookie_add_type_templ').html());
            PUMModals.reload('#pum_cookie_add_type_modal', template());
        })
        .on('click', '#pum_popup_cookies_list .edit', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first'),
                type = $row.find('.popup_cookies_field_type').val(),
                id = '#pum_cookie_settings_' + type,
                template = _.template($('script' + id + '_templ').html()),
                data = {
                    index: $row.parent().children().index($row),
                    type: type,
                    cookie_settings: JSON.parse($row.find('.popup_cookies_field_settings:first').val())
                };

            e.preventDefault();

            data.save_button_text = I10n.save;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(id, template(data));
        })
        .on('click', '#pum_popup_cookies_list .remove', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first'),
                index = $row.parent().children().index($row);

            e.preventDefault();

            if (window.confirm(I10n.confirm_delete_cookie)) {
                $row.remove();
                PUMCookies.renumber();
            }
        })
        .on('submit', '#pum_cookie_add_type_modal .pum-form', function (e) {
            var type = $('#popup_cookie_add_type').val(),
                id = '#pum_cookie_settings_' + type,
                template = _.template($('script' + id + '_templ').html()),
                data = {};

            e.preventDefault();

            data.cookie_settings = defaults.cookies[type] !== undefined ? defaults.cookies[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(id, template(data));
        })
        .on('submit', '.cookie-editor .pum-form', function (e) {
            var $form = $(this),
                type = $form.find('input.type').val(),
                values = $form.serializeObject(),
                index = parseInt(values.index),
                $row = index >= 0 ? $('#pum_popup_cookies_list tbody tr').eq(index) : null,
                template = _.template($('script#pum_cookie_row_templ').html()),
                $new_row;

            e.preventDefault();

            if (!(index >= 0)) {
                values.index = $('#pum_popup_cookies_list tbody tr').length;
            }

            values.I10n = I10n;

            $new_row = template(values);

            if (!$row) {
                $('#pum_popup_cookies_list tbody').append($new_row);
            }
            else {
                $row.replaceWith($new_row);
            }

            PUMModals.closeAll();
            PUMCookies.renumber();
        })
        .ready(PUMCookies.refreshDescriptions);

}(jQuery));
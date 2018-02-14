var cookies;
(function ($, document, undefined) {
    "use strict";

    var I10n = pum_admin_vars.I10n,
        current_editor,
        cookies = {
            get_cookies: function () {
                return window.pum_popup_settings_editor.cookies;
            },
            get_cookie: function (event) {
                var cookies = this.get_cookies(),
                    cookie = cookies[event] !== 'undefined' ? cookies[event] : false;

                if (!cookie) {
                    return false;
                }

                if (cookie && typeof cookie === 'object' && typeof cookie.fields === 'object' && Object.keys(cookie.fields).length) {
                    cookie = this.parseFields(cookie);
                }

                return cookie;
            },
            parseFields: function (cookie) {
                _.each(cookie.fields, function (fields, tabID) {
                    _.each(fields, function (field, fieldID) {
                        cookie.fields[tabID][fieldID].name = 'cookie_settings[' + fieldID + ']';

                        if (cookie.fields[tabID][fieldID].id === '') {
                            cookie.fields[tabID][fieldID].id = 'cookie_settings_' + fieldID;
                        }
                    });
                });

                return cookie;
            },
            parseValues: function (values, type) {
                return values;
            },
            select_list: function () {
                var i,
                    _cookies = PUM_Admin.utils.object_to_array(cookies.get_cookies()),
                    options = {};

                for (i = 0; i < _cookies.length; i++) {
                    options[_cookies[i].id] = _cookies[i].name;
                }

                return options;
            },
            /**
             * @deprecated
             *
             * @param event
             */
            getLabel: function (event) {
                var cookie = cookies.get_cookie(event);

                if (!cookie) {
                    return false;
                }

                return cookie.name;
            },
            /**
             * @param event
             * @param values
             */
            getSettingsDesc: function (event, values) {
                var cookie = cookies.get_cookie(event);

                if (!cookie) {
                    return false;
                }

                return PUM_Admin.templates.renderInline(cookie.settings_column, values);
            },
            /**
             * Refresh all cookie row descriptions.
             */
            refreshDescriptions: function () {
                $('.pum-popup-cookie-editor table.list-table tbody tr').each(function () {
                    var $row = $(this),
                        event = $row.find('.popup_cookies_field_event').val(),
                        values = JSON.parse($row.find('.popup_cookies_field_settings:first').val());

                    $row.find('td.settings-column').html(cookies.getSettingsDesc(event, values));
                });
            },
            /**
             * Insert a new cookie when needed.
             *
             * @param $editor
             * @param args
             */
            insertCookie: function ($editor, args) {
                args = $.extend(true, {}, {
                    event: 'on_popup_close',
                    settings: {
                        name: name || 'pum-' + $('#post_ID').val()
                    }
                }, args);

                cookies.rows.add($editor, args);
            },
            template: {
                form: function (event, values, callback) {
                    var cookie = cookies.get_cookie(event),
                        modalID = 'pum_cookie_settings',
                        firstTab = Object.keys(cookie.fields)[0];

                    values = values || {};
                    values.event = event;
                    values.index = values.index >= 0 ? values.index : null;

                    // Add hidden index & event fields.
                    cookie.fields[firstTab] = $.extend(true, cookie.fields[firstTab], {
                        index: {
                            type: 'hidden',
                            name: 'index'
                        },
                        event: {
                            type: 'hidden',
                            name: 'event'
                        }
                    });

                    if (typeof values.key !== 'string' || values.key === '') {
                        delete cookie.fields.advanced.key;
                    }

                    PUM_Admin.modals.reload('#' + modalID, PUM_Admin.templates.modal({
                        id: modalID,
                        title: cookie.modal_title || cookie.name,
                        classes: 'tabbed-content',
                        save_button: values.index !== null ? I10n.update : I10n.add,
                        content: PUM_Admin.forms.render({
                            id: 'pum_cookie_settings_form',
                            tabs: cookie.tabs || {},
                            fields: cookie.fields || {}
                        }, values || {})
                    }));

                    $('#' + modalID + ' form').on('submit', callback || function (e) {
                        e.preventDefault();
                        PUM_Admin.modals.closeAll();
                    });
                },
                editor: function (args) {
                    var data = $.extend(true, {}, {
                        cookies: [],
                        name: ''
                    }, args);

                    data.cookies = PUM_Admin.utils.object_to_array(data.cookies);

                    return PUM_Admin.templates.render('pum-cookie-editor', data);
                },
                row: function (args) {
                    var data = $.extend(true, {}, {
                        index: '',
                        event: '',
                        name: '',
                        settings: {
                            name: "",
                            key: "",
                            session: false,
                            time: '30 days',
                            path: true
                        }
                    }, args);

                    return PUM_Admin.templates.render('pum-cookie-row', data);
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
                        options: cookies.select_list()
                    }, args);

                    if (data.id === null) {
                        data.id = 'popup_settings_cookies_' + data.index + '_event';
                    }

                    if (data.name === null) {
                        data.name = 'popup_settings[cookies][' + data.index + '][event]';
                    }

                    return PUM_Admin.templates.field(data);
                }
            },
            rows: {
                add: function (editor, cookie) {
                    var $editor = $(editor),
                        data = {
                            index: cookie.index !== null && cookie.index >= 0 ? cookie.index : $editor.find('table.list-table tbody tr').length,
                            event: cookie.event,
                            name: $editor.data('field_name'),
                            settings: cookie.settings || {}
                        },
                        $row = $editor.find('tbody tr').eq(data.index),
                        $new_row = PUM_Admin.templates.render('pum-cookie-row', data);

                    if ($row.length) {
                        $row.replaceWith($new_row);
                    } else {
                        $editor.find('tbody').append($new_row);
                    }

                    $editor.addClass('has-list-items');

                    cookies.rows.renumber();
                    cookies.refreshDescriptions();
                },
                /**
                 * Remove a cookie editor table row.
                 *
                 * @param $cookie
                 */
                remove: function ($cookie) {
                    var $editor = $cookie.parents('.pum-popup-cookie-editor');

                    $cookie.remove();
                    cookies.rows.renumber();

                    if ($editor.find('table.list-table tbody tr').length === 0) {
                        $editor.removeClass('has-list-items');

                        $('#pum-first-cookie')
                            .val(null)
                            .trigger('change');
                    }
                },
                /**
                 * Renumber all rows for all editors.
                 */
                renumber: function () {
                    $('.pum-popup-cookie-editor table.list-table tbody tr').each(function () {
                        var $this = $(this),
                            index = $this.parent().children().index($this);

                        $this.attr('data-index', index).data('index', index);

                        $this.find(':input, [name]').each(function () {
                            if (this.name && this.name !== '') {
                                this.name = this.name.replace(/\[\d*?\]/, "[" + index + "]");
                            }
                        });
                    });
                }
            }
        };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.cookies = cookies;

    $(document)
        .on('pum_init', function () {
            cookies.refreshDescriptions();
        })
        .on('select2:select pumselect2:select', '#pum-first-cookie', function () {
            var $this = $(this),
                $editor = $this.parents('.pum-popup-cookie-editor'),
                event = $this.val(),
                values = {
                    indes: $editor.find('table.list-table tbody tr').length,
                    name: 'pum-' + $('#post_ID').val()
                };

            $this
                .val(null)
                .trigger('change');

            cookies.template.form(event, values, function (e) {
                var $form = $(this),
                    event = $form.find('input#event').val(),
                    index = $form.find('input#index').val(),
                    values = $form.pumSerializeForm();

                e.preventDefault();

                if (!index || index < 0) {
                    index = $editor.find('tbody tr').length;
                }

                cookies.rows.add($editor, {
                    index: index,
                    event: event,
                    settings: values.cookie_settings
                });

                PUM_Admin.modals.closeAll();
            });
        })
        .on('click', '.pum-popup-cookie-editor .pum-add-new', function () {
            current_editor = $(this).parents('.pum-popup-cookie-editor');
            var template = wp.template('pum-cookie-add-event');
            PUM_Admin.modals.reload('#pum_cookie_add_event_modal', template({I10n: I10n}));
        })
        .on('click', '.pum-popup-cookie-editor .edit', function (e) {
            var $this = $(this),
                $editor = $this.parents('.pum-popup-cookie-editor'),
                $row = $this.parents('tr:first'),
                event = $row.find('.popup_cookies_field_event').val(),
                values = _.extend({}, JSON.parse($row.find('.popup_cookies_field_settings:first').val()), {
                    index: $row.parent().children().index($row),
                    event: event
                });

            e.preventDefault();

            cookies.template.form(event, values, function (e) {
                var $form = $(this),
                    event = $form.find('input#event').val(),
                    index = $form.find('input#index').val(),
                    values = $form.pumSerializeForm();

                e.preventDefault();

                if (index === false || index < 0) {
                    index = $editor.find('tbody tr').length;
                }

                cookies.rows.add($editor, {
                    index: index,
                    event: event,
                    settings: values.cookie_settings
                });

                PUM_Admin.modals.closeAll();
            });
        })
        .on('click', '.pum-popup-cookie-editor .remove', function (e) {
            var $this = $(this),
                $row = $this.parents('tr:first');

            e.preventDefault();

            if (window.confirm(I10n.confirm_delete_cookie)) {
                cookies.rows.remove($row);
            }
        })
        .on('click', '.pum-field-cookie_key button.reset', function (e) {
            var $this = $(this),
                newKey = (new Date().getTime()).toString(16);

            $this.siblings('input[type="text"]:first').val(newKey);
        })
        .on('submit', '#pum_cookie_add_event_modal .pum-form', function (e) {
            var $editor = current_editor,
                event = $('#popup_cookie_add_event').val(),
                values = {
                    index: $editor.find('table.list-table tbody tr').length,
                    name: 'pum-' + $('#post_ID').val(),
                    path: '1'
                };

            e.preventDefault();

            cookies.template.form(event, values, function (e) {
                var $form = $(this),
                    event = $form.find('input#event').val(),
                    index = $form.find('input#index').val(),
                    values = $form.pumSerializeForm();

                e.preventDefault();

                if (index === false || index < 0) {
                    index = $editor.find('tbody tr').length;
                }

                cookies.rows.add($editor, {
                    index: index,
                    event: event,
                    settings: values.cookie_settings
                });

                PUM_Admin.modals.closeAll();

                if (typeof PUM_Admin.triggers !== 'undefined' && PUM_Admin.triggers.new_cookie !== false && PUM_Admin.triggers.new_cookie >= 0) {
                    var $trigger = PUM_Admin.triggers.current_editor.find('tbody tr').eq(PUM_Admin.triggers.new_cookie).find('.popup_triggers_field_settings:first'),
                        trigger_settings = JSON.parse($trigger.val());

                    if (typeof trigger_settings.cookie_name === 'string') {
                        trigger_settings.cookie_name = trigger_settings.cookie_name.replace('add_new', values.cookie_settings.name);
                    } else {
                        trigger_settings.cookie_name[trigger_settings.cookie_name.indexOf('add_new')] = values.cookie_settings.name;
                        trigger_settings.cookie_name = trigger_settings.cookie_name.filter(function(element, index, array) {
                            return element in this ? false : this[element] = true;
                        }, {});
                    }

                    $trigger.val(JSON.stringify(trigger_settings));

                    PUM_Admin.triggers.new_cookie = false;
                    PUM_Admin.triggers.refreshDescriptions();
                }
            });
        });

}(jQuery, document));
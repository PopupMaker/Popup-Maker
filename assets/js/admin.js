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
                    change: function (e) {
                        var $this = $(this),
                            $input = $(e.currentTarget);
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').show();
                        }

                        $this.trigger('change.update');

                        if ($('form#post input#post_type').val() === 'popup_theme') {
                            PopMakeAdmin.update_theme();
                        }
                    },
                    clear: function (e) {
                        var $input = $(e.currentTarget).prev();
                        if ($input.hasClass('background-color')) {
                            $input.parents('table').find('.background-opacity').hide();
                        }

                        $(this).prev('input').trigger('change.clear').wpColorPicker('close');

                        if ($('form#post input#post_type').val() === 'popup_theme') {
                            PopMakeAdmin.update_theme();
                        }
                    }
                });
        }
    };

    $(document)
        .on('click', '.iris-palette', function () {
            $(this).parents('.wp-picker-active').find('input.color-picker').trigger('change');
            setTimeout(PopMakeAdmin.update_theme, 500);
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
        .on('select2:select', '#pum-first-condition', function () {
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
        }
    };

    $(document)
        .on('select2:select', '#pum-first-cookie', function () {
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
                values = $form.serializeObject(),
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

            if (PUMTriggers.new_cookie && PUMTriggers.new_cookie >= 0) {
                $trigger = $('#pum_popup_triggers_list tbody tr').eq(PUMTriggers.new_cookie).find('.popup_triggers_field_settings:first');
                console.log($trigger, $trigger.val());
                trigger_settings = JSON.parse($trigger.val());
                trigger_settings.cookie.name[trigger_settings.cookie.name.indexOf('add_new')] = values.cookie_settings.name;

                $trigger.val(JSON.stringify(trigger_settings));

                PUMTriggers.new_cookie = -1;
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
                        'min': min || 0,
                        'max': force || (max && max > value) ? max : value * 1.5,
                        'step': step || value * 1.5 / 100,
                        'value': value
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
        .on('input', 'input[type="range"]', function () {
            var $this = $(this);
            $this.siblings('.popmake-range-manual').val($this.val());
        })
        .on('click', '.popmake-range-manual', function () {
            var $this = $(this);
            $this.prop('readonly', false);
        })
        .on('focusout', '.popmake-range-manual', function () {
            var $this = $(this);
            $this.prop('readonly', true);
        })
        .on('change', '.popmake-range-manual', function () {
            var $this = $(this),
                max = parseInt($this.prop('max'), 0),
                step = parseInt($this.prop('step'), 0),
                force = $this.data('force-minmax'),
                value = parseInt($this.val(), 0),
                $slider = $this.prev();

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
    $.fn.pumSelect2 = $.fn.select2;

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
                    .pumSelect2(options);

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

    $.fn.serializeObject = function (options) {
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
var PUMTriggers;
(function ($, document, undefined) {
    "use strict";

    var I10n = pum_admin.I10n,
        defaults = pum_admin.defaults;

    PUMTriggers = {
        new_cookie: null,
        getLabel: function (type) {
            return I10n.labels.triggers[type].name;
        },
        getSettingsDesc: function (type, values) {
            var options = {
                    evaluate:    /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable:    'data'
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
                $cookie = $('#name', $form),
                trigger_settings = data.trigger_settings;

            $('#pum_popup_cookies_list tbody tr').each(function () {
                var settings = JSON.parse($(this).find('.popup_cookies_field_settings:first').val());
                if (!$cookie.find('option[value="' + settings.name + '"]').length) {
                    $('<option value="' + settings.name + '">' + settings.name + '</option>').appendTo($cookie);
                }
            });

            $cookie.val(trigger_settings.cookie.name);

            $cookie.trigger("chosen:updated");
        },
        cookie_column_value: function (cookie_name) {
            var cookie_text = I10n.no_cookie;

            if (cookie_name instanceof Array) {
                cookie_text = cookie_name.join(', ');
            } else if (cookie_name !== null) {
                cookie_text = cookie_name;
            }
            return cookie_text;
        }
    };

    PUMTriggers.refreshDescriptions();

    $(document)
        .on('select2:select', '#pum-first-trigger', function () {
            var $this = $(this),
                type = $this.val(),
                id = 'pum-trigger-settings-' + type,
                modalID = '#' + id.replace(/-/g,'_'),
                template = wp.template(id),
                data = {};

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

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
                modalID = '#' + id.replace(/-/g,'_'),
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
                modalID = '#' + id.replace(/-/g,'_'),
                template = wp.template(id),
                data = {};

            e.preventDefault();

            data.trigger_settings = defaults.triggers[type] !== undefined ? defaults.triggers[type] : {};
            data.save_button_text = I10n.add;
            data.index = null;

            if (!template.length) {
                alert('Something went wrong. Please refresh and try again.');
            }

            PUMModals.reload(modalID, template(data));
            PUMTriggers.initEditForm(data);
        })
        .on('submit', '.trigger-editor .pum-form', function (e) {
            var $form = $(this),
                type = $form.find('input.type').val(),
                values = $form.serializeObject(),
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
            $(document)
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
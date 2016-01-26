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
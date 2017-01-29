/**
 * Popup Maker v1.3.6
 */

var PopMakeAdmin;
(function () {
    "use strict";
    PopMakeAdmin = {
        init: function () {
            //PopMakeAdmin.initialize_tabs();
            if (jQuery('body.post-type-popup form#post').length) {
                PopMakeAdmin.initialize_popup_page();
                PopMakeAdmin.attachQuickSearchListeners();
                PopMakeAdmin.attachTabsPanelListeners();
            }
            if (jQuery('body.post-type-popup_theme form#post').length) {
                PopMakeAdmin.initialize_theme_page();
            }
            PopMakeAdmin.initialize_color_pickers();
            PopMakeAdmin.initialize_range_sliders();

            PopMakeAdmin.initialize_marketing();


            jQuery(document).keydown(function (event) {
                if ((event.which === '115' || event.which === '83') && (event.ctrlKey || event.metaKey)) {
                    event.preventDefault();
                    jQuery('body.post-type-popup form#post, body.post-type-popup_theme form#post').submit();
                    return false;
                }
                return true;
            });
        },
        initialize_marketing: function () {
            jQuery('#menu-posts-popup ul li:eq(-1)').addClass('popmake-menu-highlight');

            jQuery('.popmake-newsletter-optin').show();

            // Modal & Theme Indexes
            if (jQuery('#posts-filter').length) {
                jQuery('#wpbody-content > .wrap > h2:first').after(jQuery('.popmake-newsletter-optin'));

                // Modal & Theme Editors
            } else if (jQuery('#titlediv').length) {
                jQuery('#titlediv').append(jQuery('.popmake-newsletter-optin'));

                // Welcome & Similar Pages
            } else if (jQuery('.about-text').length && jQuery('.popmake-badge').length) {
                jQuery('.nav-tab-wrapper').after(jQuery('.popmake-newsletter-optin'));

                // Settings & Other Tabbed Pages
            } else if (jQuery('#poststuff .tabwrapper').length) {
                jQuery('#poststuff .tabwrapper').prepend(jQuery('.popmake-newsletter-optin'));

                // Settings & Other Tabbed Pages
            } else if (jQuery('#poststuff').length) {
                jQuery('#poststuff').prepend(jQuery('.popmake-newsletter-optin'));
            }

            jQuery('.popmake-optin-dismiss').on('click', function (event) {
                var $this = jQuery(this);
                event.preventDefault();
                jQuery
                    .ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: "popmake_optin",
                            popmake_nonce: popmake_admin_ajax_nonce,
                            optin_dismiss: true,
                            optin_name: $this.data('optin-name'),
                            optin_type: $this.data('optin-type')
                        }
                    })
                    .done(function (data) {
                        if (data.success) {
                            $this.parents('.popmake-optin').slideUp(function () {
                                jQuery(this).remove();
                            });
                        }
                    });
            });
        },
        attachTabsPanelListeners: function () {
            jQuery('#poststuff').bind('click', function (event) {
                var selectAreaMatch, panelId, wrapper, items,
                    target = jQuery(event.target),
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
                    jQuery('input', wrapper).removeAttr('checked');
                    jQuery('.tabs-panel-active', wrapper).removeClass('tabs-panel-active').addClass('tabs-panel-inactive');
                    jQuery('#' + panelId, wrapper).removeClass('tabs-panel-inactive').addClass('tabs-panel-active');
                    jQuery('.tabs', wrapper).removeClass('tabs');
                    target.parent().addClass('tabs');
                    // select the search bar
                    jQuery('.quick-search', wrapper).focus();
                    event.preventDefault();
                } else if (target.hasClass('select-all')) {
                    selectAreaMatch = /#(.*)$/.exec(event.target.href);
                    if (selectAreaMatch && selectAreaMatch[1]) {
                        items = jQuery('#' + selectAreaMatch[1] + ' .tabs-panel-active .menu-item-title input');
                        if (items.length === items.filter(':checked').length) {
                            items.removeAttr('checked');
                        } else {
                            items.prop('checked', true);
                        }
                    }
                } else if (target.hasClass('submit-add-to-menu')) {
                    $parent = target.parents('.options');
                    $items = jQuery('.tabs-panel-active input[type="checkbox"]:checked', $parent);
                    $textarea = jQuery('textarea', $parent);
                    $tag_area = jQuery('.tagchecklist', $parent);
                    current_ids = $textarea.val().split(',');
                    for (i = 0; i < current_ids.length; i += 1) {
                        current_ids[i] = parseInt(current_ids[i], 10);
                    }
                    $items.each(function () {
                        $item = jQuery(this);
                        id = parseInt($item.val(), 10);
                        name = $item.parent('label').siblings('.menu-item-title').val();
                        if (jQuery.inArray(id, current_ids) === -1) {
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
                    $textarea = jQuery('textarea', $parent);
                    $tag_area = jQuery('.tagchecklist', $parent);
                    current_ids = $textarea.val().split(',');
                    current_ids = jQuery.grep(current_ids, function (value) {
                        return parseInt(value, 10) !== parseInt(removeItem, 10);
                    });
                    $item.parent('span').remove();
                    $textarea.text(current_ids.join(','));
                }
            });
        },
        attachQuickSearchListeners: function () {
            var searchTimer;
            jQuery('.quick-search').keypress(function (event) {
                var t = jQuery(this);
                if (13 === event.which) {
                    PopMakeAdmin.updateQuickSearchResults(t);
                    return false;
                }
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                searchTimer = setTimeout(function () {
                    PopMakeAdmin.updateQuickSearchResults(t);
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
                'menu-settings-column-nonce': jQuery('#menu-settings-column-nonce').val(),
                'q': q,
                'type': input.attr('name')
            };
            jQuery('.spinner', panel).show();
            jQuery.post(ajaxurl, params, function (menuMarkup) {
                PopMakeAdmin.processQuickSearchQueryResponse(menuMarkup, params, panel);
            });
        },
        processQuickSearchQueryResponse: function (resp, req, panel) {
            var matched, newID,
                form = jQuery('form#post'),
                takenIDs = {},
                pattern = /menu-item[(\[\^]\]*/,
                $items = jQuery('<div>').html(resp).find('li'),
                $item;

            if (!$items.length) {
                jQuery('.categorychecklist', panel).html('<li><p>' + 'noResultsFound' + '</p></li>');
                jQuery('.spinner', panel).hide();
                return;
            }

            $items.each(function () {
                $item = jQuery(this);

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

            jQuery('.categorychecklist', panel).html($items);
            jQuery('.spinner', panel).hide();
            jQuery('[name^="menu-item"]').removeAttr('name');
        },
        initialize_color_pickers: function () {
            var self = this;
            jQuery('.color-picker').wpColorPicker({
                change: function (event) {
                    self.throttle(setTimeout(function () {
                        self.update_theme();
                    }, 5), 250);
                    var $input = jQuery(event.currentTarget);
                    if ($input.hasClass('background-color')) {
                        $input.parents('table').find('.background-opacity').show();
                    }
                },
                clear: function (event) {
                    self.update_theme();
                    var $input = jQuery(event.currentTarget).prev();
                    if ($input.hasClass('background-color')) {
                        $input.parents('table').find('.background-opacity').hide();
                    }
                }
            });
        },
        initialize_range_sliders: function () {

            var input,
                $input,
                $slider,
                $plus,
                $minus,
                slider = jQuery('<input type="range"/>'),
                plus = jQuery('<button class="popmake-range-plus">+</button>'),
                minus = jQuery('<button class="popmake-range-minus">-</button>');

            jQuery(document).on('input', 'input[type="range"]', function () {
                var $this = jQuery(this);
                $this.siblings('.popmake-range-manual').val($this.val());
            });
            jQuery('.popmake-range-manual').each(function () {
                var $this = jQuery(this),
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

            });

            jQuery(document)
                .on('change', '.popmake-range-manual', function () {

                    var $this = jQuery(this),
                        max = parseInt($this.prop('max'), 0),
                        step = parseInt($this.prop('step'), 0),
                        force = $this.data('force-minmax'),
                        value = parseInt($this.val(), 0);

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
                .on('click', '.popmake-range-plus', function (event) {

                    event.preventDefault();

                    var $this = jQuery(this).siblings('.popmake-range-manual'),
                        step = parseInt($this.prop('step'), 0),
                        value = parseInt($this.val(), 0),
                        val = value + step;

                    $slider = $this.prev();

                    $this.val(val).trigger('input');
                    $slider.val(val);

                })
                .on('click', '.popmake-range-minus', function (event) {

                    event.preventDefault();

                    var $this = jQuery(this).siblings('.popmake-range-manual'),
                        step = parseInt($this.prop('step'), 0),
                        value = parseInt($this.val(), 0),
                        val = value - step;

                    $slider = $this.prev();

                    $this.val(val).trigger('input');
                    $slider.val(val);

                });


            input = document.createElement('input');
            input.setAttribute('type', 'range');
            if (input.type === 'text') {
                jQuery('input[type=range]').each(function (index, input) {
                    $input = jQuery(input);
                    $slider = jQuery('<div />').slider({
                        min: parseInt($input.attr('min'), 10) || 0,
                        max: parseInt($input.attr('max'), 10) || 100,
                        value: parseInt($input.attr('value'), 10) || 0,
                        step: parseInt($input.attr('step'), 10) || 1,
                        slide: function (event, ui) {
                            jQuery(this).prev('input').val(ui.value);
                        }
                    });
                    $input.after($slider).hide();
                });
            }
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
                            jQuery('> *', others).prop('disabled', true);
                            excludes.show();
                            jQuery('> *', excludes).prop('disabled', false);
                        } else {
                            jQuery('*', $options).prop('disabled', false);
                        }
                    } else {
                        $options.hide();
                        if ($this.attr('id') === 'popup_targeting_condition_on_entire_site') {
                            excludes = $this.parents('#popmake_popup_targeting_condition_fields').find('[id^="targeting_condition-exclude_on_"]');
                            others = $this.parents('.targeting_condition').siblings('.targeting_condition');
                            others.show();
                            jQuery('> *', others).prop('disabled', false);
                            excludes.hide();
                            jQuery('> *', excludes).prop('disabled', true);
                        } else {
                            jQuery('*', $options).prop('disabled', true);
                        }
                    }
                },
                update_specific_checkboxes = function ($this) {
                    var $option = $this.parents('.options').find('input[type="checkbox"]:eq(0)'),
                        exclude = $option.attr('name').indexOf("exclude") >= 0,
                        type = exclude ? $option.attr('name').replace('popup_targeting_condition_exclude_on_specific_', '') : $option.attr('name').replace('popup_targeting_condition_on_specific_', ''),
                        type_box = exclude ? jQuery('#exclude_on_specific_' + type) : jQuery('#on_specific_' + type);

                    if ($this.is(':checked')) {
                        if ($this.val() === 'true') {
                            $option.prop('checked', true);
                            type_box.show();
                            jQuery('*', type_box).prop('disabled', false);
                        } else if ($this.val() === '') {
                            $option.prop('checked', false);
                            type_box.hide();
                            jQuery('*', type_box).prop('disabled', true);
                        }
                    }
                },
                update_size = function () {
                    if (jQuery("#popup_display_size").val() === 'custom') {
                        jQuery('.custom-size-only').show();
                        jQuery('.responsive-size-only').hide();
                        if (jQuery('#popup_display_custom_height_auto').is(':checked')) {
                            jQuery('.custom-size-height-only').hide();
                        } else {
                            jQuery('.custom-size-height-only').show();
                        }
                    } else {
                        jQuery('.custom-size-only').hide();
                        if (jQuery("#popup_display_size").val() !== 'auto') {
                            jQuery('.responsive-size-only').show();
                            jQuery('#popup_display_custom_height_auto').prop('checked', false);
                        } else {
                            jQuery('.responsive-size-only').hide();
                        }
                    }
                },
                update_animation = function () {
                    jQuery('.animation-speed, .animation-origin').hide();
                    if (jQuery("#popup_display_animation_type").val() === 'fade') {
                        jQuery('.animation-speed').show();
                    } else {
                        if (jQuery("#popup_display_animation_type").val() !== 'none') {
                            jQuery('.animation-speed, .animation-origin').show();
                        }
                    }
                },
                update_location = function () {
                    var $this = jQuery('#popup_display_location'),
                        table = $this.parents('table'),
                        val = $this.val();
                    jQuery('tr.top, tr.right, tr.left, tr.bottom', table).hide();
                    if (val.indexOf("top") >= 0) {
                        jQuery('tr.top').show();
                    }
                    if (val.indexOf("left") >= 0) {
                        jQuery('tr.left').show();
                    }
                    if (val.indexOf("bottom") >= 0) {
                        jQuery('tr.bottom').show();
                    }
                    if (val.indexOf("right") >= 0) {
                        jQuery('tr.right').show();
                    }
                },
                auto_open_session_cookie_check = function () {
                    if (jQuery("#popup_auto_open_session_cookie").is(":checked")) {
                        jQuery('.not-session-cookie').hide();
                    } else {
                        jQuery('.not-session-cookie').show();
                    }
                },
                auto_open_enabled_check = function () {
                    if (jQuery("#popup_auto_open_enabled").is(":checked")) {
                        jQuery('.auto-open-enabled').show();
                        auto_open_session_cookie_check();
                    } else {
                        jQuery('.auto-open-enabled').hide();
                    }
                },
                auto_open_reset_cookie_key = function () {
                    jQuery('#popup_auto_open_cookie_key').val((new Date().getTime()).toString(16));
                },
                update_popup_preview_title = function () {
                    if (jQuery('#popuptitle').val() !== '') {
                        jQuery('#popmake-preview .popmake-title').show().html(jQuery('#popuptitle').val());
                    } else {
                        jQuery('#popmake-preview .popmake-title').hide();
                    }
                },
                update_popup_preview_content = function () {
                    var content = '';

                    if (jQuery("#wp-content-wrap").hasClass("tmce-active")) {
                        content = tinyMCE.activeEditor.getContent();
                    } else {
                        content = jQuery('#content').val();
                    }

                    jQuery
                        .ajax({
                            url: ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: "popmake_popup_preview_content",
                                popmake_nonce: popmake_admin_ajax_nonce,
                                popup_id: jQuery('#post_ID').val(),
                                popup_content: content
                            }
                        })
                        .done(function (data) {
                            if (data.success) {
                                jQuery('#popmake-preview .popmake-content').html(data.content);
                                jQuery('#popmake-preview').popmake('open');
                            }
                        });
                },
                update_popup_preview_data = function () {
                    var form_values = jQuery("[name^='popup_display_']").serializeArray(),
                        i,
                        $popup = jQuery('#popmake-preview'),
                        data = $popup.data('popmake');

                    for (i = 0; form_values.length > i; i += 1) {
                        if (form_values[i].name.indexOf('popup_display_') === 0) {
                            data.meta.display[form_values[i].name.replace('popup_display_', '')] = form_values[i].value;
                        }
                    }

                    $popup.removeClass('theme-' + data.theme_id);

                    data.theme_id = jQuery('#popup_theme').val();

                    jQuery('#popmake-preview')
                        .addClass('theme-' + data.theme_id)
                        .data('popmake', data);
                };

            jQuery('#popuptitlediv').insertAfter('#titlediv');
            jQuery('[name^="menu-item"]').removeAttr('name');

            jQuery('#trigger-popmake-preview')
                .on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    update_popup_preview_title();
                    update_popup_preview_data();
                    update_popup_preview_content();
                    return false;
                });
            jQuery(document)
                .on('keydown', '#popuptitle', function (event) {
                    var keyCode = event.keyCode || event.which;
                    if (9 === keyCode) {
                        event.preventDefault();
                        jQuery('#title').focus();
                    }
                })
                .on('keydown', '#title, #popuptitle', function (event) {
                    var keyCode = event.keyCode || event.which,
                        target;
                    if (!event.shiftKey && 9 === keyCode) {
                        event.preventDefault();
                        target = jQuery(this).attr('id') === 'title' ? '#popuptitle' : '#insert-media-button';
                        jQuery(target).focus();
                    }
                })
                .on('keydown', '#popuptitle, #insert-media-button', function (event) {
                    var keyCode = event.keyCode || event.which,
                        target;
                    if (event.shiftKey && 9 === keyCode) {
                        event.preventDefault();
                        target = jQuery(this).attr('id') === 'popuptitle' ? '#title' : '#popuptitle';
                        jQuery(target).focus();
                    }
                })
                .on('submit', '#post', function (event) {
                    var title = jQuery('#title').val();
                    if (title.length === 0 || title.replace(/\s/g, '').length === 0) {
                        event.preventDefault();
                        jQuery('div#notice').remove();
                        jQuery("<div id='notice' class='error below-h2'><p>A name is required for all popups.</p></div>").insertAfter('h2');
                        jQuery('#title').focus();
                        jQuery('#publishing-action .spinner').removeClass('is-active');
                        jQuery('#publish').removeClass('disabled');
                        jQuery('#title').prop('required', 'required');
                    }
                })
                .on('click', '#popup_display_custom_height_auto', function () {
                    update_size();
                })
                .on('click', "#popup_auto_open_session_cookie", function () {
                    auto_open_session_cookie_check();
                })
                .on('click', "#popup_auto_open_enabled", function () {
                    auto_open_enabled_check();
                })
                .on('click', ".popmake-reset-auto-open-cookie-key", function () {
                    auto_open_reset_cookie_key();
                })
                .on('change', "#popup_display_size", function () {
                    if (jQuery("#popup_display_size").val() !== 'custom' && jQuery("#popup_display_size").val() !== 'auto') {
                        jQuery('#popup_display_position_fixed, #popup_display_scrollable_content').prop('checked', false);
                    }
                    update_size();
                })
                .on('change', "#popup_display_animation_type", function () {
                    update_animation();
                })
                .on('change', '#popup_display_location', function () {
                    update_location();
                });


            jQuery('#popmake_popup_targeting_condition_fields .targeting_condition > input[type="checkbox"]')
                .on('click', function () {
                    update_type_options(jQuery(this));
                })
                .each(function () {
                    update_type_options(jQuery(this));
                });

            jQuery('input[type="radio"][id*="popup_targeting_condition_"]')
                .on('click', function () {
                    update_specific_checkboxes(jQuery(this));
                })
                .each(function () {
                    update_specific_checkboxes(jQuery(this));
                });

            jQuery('.posttypediv, .taxonomydiv').each(function () {
                var $this = jQuery(this),
                    $tabs = jQuery('> ul li'),
                    $sections = jQuery('.tabs-panel', $this);

                $tabs.removeClass('tabs');
                $tabs.eq(0).addClass('tabs');
                $sections.removeClass('tabs-panel-active').addClass('tabs-panel-inactive').removeAttr('style');
                $sections.eq(0).removeClass('tabs-panel-inactive').addClass('tabs-panel-active');
            });

            update_size();
            update_animation();
            update_location();

            auto_open_enabled_check();
            if (jQuery('#popup_auto_open_cookie_key').val() === '') {
                auto_open_reset_cookie_key();
            }
        },
        theme_page_listeners: function () {
            var self = this;
            jQuery(document)
                .on('change', 'select.font-family', function () {
                    jQuery('select.font-weight option, select.font-style option', jQuery(this).parents('table')).prop('selected', false);
                    self.update_font_selectboxes();
                })
                .on('change', 'select.font-weight, select.font-style', function () {
                    self.update_font_selectboxes();
                })
                .on('change input focusout', 'select, input', function () {
                    self.update_theme();
                })
                .on('change', 'select.border-style', function () {
                    var $this = jQuery(this);
                    if ($this.val() === 'none') {
                        $this.parents('table').find('.border-options').hide();
                    } else {
                        $this.parents('table').find('.border-options').show();
                    }
                })
                .on('change', '#popup_theme_close_location', function () {
                    var $this = jQuery(this),
                        table = $this.parents('table');
                    jQuery('tr.topleft, tr.topright, tr.bottomleft, tr.bottomright', table).hide();
                    jQuery('tr.' + $this.val(), table).show();
                });
        },
        update_theme: function () {
            var form_values = jQuery("[name^='popup_theme_']").serializeArray(),
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
            var $preview = jQuery('#popmake-theme-editor .empreview, body.post-type-popup_theme form#post #popmake_popup_theme_preview'),
                $parent = $preview.parent(),
                startscroll = $preview.offset().top - 50;
            jQuery(window).on('scroll', function () {
                if (jQuery('> .postbox:visible', $parent).index($preview) === (jQuery('> .postbox:visible', $parent).length - 1) && jQuery(window).scrollTop() >= startscroll) {
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
            return jQuery('select.font-family').each(function () {
                var $this = jQuery(this),
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
                                jQuery('option[value=""]', $font_weight).show();
                                jQuery('option[value=""]', $font_style).show();
                            } else {
                                if (font.variants[i].indexOf('italic') >= 0) {
                                    jQuery('option[value="italic"]', $font_style).show();
                                }
                                jQuery('option[value="' + parseInt(font.variants[i], 10) + '"]', $font_weight).show();
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
            //jQuery.fn.popmake.themes[popmake_default_theme] = this.convert_meta_to_object(theme);
        },
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
        initialize_theme_page: function () {
            jQuery('#popuptitlediv').insertAfter('#titlediv');

            var self = this,
                table = jQuery('#popup_theme_close_location').parents('table');
            self.update_theme();
            self.theme_page_listeners();
            self.theme_preview_scroll();
            self.update_font_selectboxes();

            jQuery(document)
                .on('click', '.popmake-preview', function (e) {
                    e.preventDefault();
                    jQuery('#popmake-preview, #popmake-overlay').css({visibility: "visible"}).show();
                })
                .on('click', '.popmake-close', function () {
                    jQuery('#popmake-preview, #popmake-overlay').hide();
                });

            jQuery('select.border-style').each(function () {
                var $this = jQuery(this);
                if ($this.val() === 'none') {
                    $this.parents('table').find('.border-options').hide();
                } else {
                    $this.parents('table').find('.border-options').show();
                }
            });

            jQuery('.color-picker.background-color').each(function () {
                var $this = jQuery(this);
                if ($this.val() === '') {
                    $this.parents('table').find('.background-opacity').hide();
                } else {
                    $this.parents('table').find('.background-opacity').show();
                }
            });

            jQuery('tr.topleft, tr.topright, tr.bottomleft, tr.bottomright', table).hide();
            switch (jQuery('#popup_theme_close_location').val()) {
                case "topleft":
                    jQuery('tr.topleft', table).show();
                    break;
                case "topright":
                    jQuery('tr.topright', table).show();
                    break;
                case "bottomleft":
                    jQuery('tr.bottomleft', table).show();
                    break;
                case "bottomright":
                    jQuery('tr.bottomright', table).show();
                    break;
            }
        },
        retheme_popup: function (theme) {
            var $overlay = jQuery('.empreview .example-popup-overlay, #popmake-overlay'),
                $container = jQuery('.empreview .example-popup, #popmake-preview'),
                $title = jQuery('.title, .popmake-title', $container),
                $content = jQuery('.content, .popmake-content', $container),
                $close = jQuery('.close-popup, .popmake-close', $container),
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
                jQuery('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
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
                jQuery('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
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
                jQuery('body').append('<link href="' + link + '" rel="stylesheet" type="text/css">');
            }

            $overlay.removeAttr('style').css({
                backgroundColor: this.convert_hex(theme.overlay_background_color, theme.overlay_background_opacity)
            });
            $container.removeAttr('style').css({
                padding: theme.container_padding + 'px',
                backgroundColor: this.convert_hex(theme.container_background_color, theme.container_background_opacity),
                borderStyle: theme.container_border_style,
                borderColor: theme.container_border_color,
                borderWidth: theme.container_border_width + 'px',
                borderRadius: theme.container_border_radius + 'px',
                boxShadow: container_inset + theme.container_boxshadow_horizontal + 'px ' + theme.container_boxshadow_vertical + 'px ' + theme.container_boxshadow_blur + 'px ' + theme.container_boxshadow_spread + 'px ' + this.convert_hex(theme.container_boxshadow_color, theme.container_boxshadow_opacity)
            });
            $title.removeAttr('style').css({
                color: theme.title_font_color,
                lineHeight: theme.title_line_height + 'px',
                fontSize: theme.title_font_size + 'px',
                fontFamily: theme.title_font_family,
                fontStyle: theme.title_font_style,
                fontWeight: theme.title_font_weight,
                textAlign: theme.title_text_align,
                textShadow: theme.title_textshadow_horizontal + 'px ' + theme.title_textshadow_vertical + 'px ' + theme.title_textshadow_blur + 'px ' + this.convert_hex(theme.title_textshadow_color, theme.title_textshadow_opacity)
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
                backgroundColor: this.convert_hex(theme.close_background_color, theme.close_background_opacity),
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
                boxShadow: close_inset + theme.close_boxshadow_horizontal + 'px ' + theme.close_boxshadow_vertical + 'px ' + theme.close_boxshadow_blur + 'px ' + theme.close_boxshadow_spread + 'px ' + this.convert_hex(theme.close_boxshadow_color, theme.close_boxshadow_opacity),
                textShadow: theme.close_textshadow_horizontal + 'px ' + theme.close_textshadow_vertical + 'px ' + theme.close_textshadow_blur + 'px ' + this.convert_hex(theme.close_textshadow_color, theme.close_textshadow_opacity)
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
            jQuery(document).trigger('popmake-admin-retheme', [theme]);
        },
        serialize_form: function ($form) {
            var serialized = {};
            jQuery("[name]", $form).each(function () {
                var name = jQuery(this).attr('name'),
                    value = jQuery(this).val(),
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
    jQuery(document).ready(function () {
        PopMakeAdmin.init();
    });
}());
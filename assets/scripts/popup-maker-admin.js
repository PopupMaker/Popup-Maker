(function () {
    "use strict";
    var PopMakeAdmin = {
        init: function () {
            PopMakeAdmin.initialize_tabs();
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

            jQuery(document).keydown(function (event) {
                if ((event.which === '115' || event.which === '83') && (event.ctrlKey || event.metaKey)) {
                    event.preventDefault();
                    jQuery('#popmake-theme-editor, #popmake-popup-editor, body.post-type-popup form#post, body.post-type-popup_theme form#post').submit();
                    return false;
                }
                return true;
            });

        },
        attachTabsPanelListeners : function () {

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

                } else if (target.hasClass('ntdelbutton')) {
                    $item = target;
                    removeItem = parseInt($item.data('id'), 10);
                    $parent = target.parents('.options');
                    $textarea = jQuery('textarea', $parent);
                    $tag_area = jQuery('.tagchecklist', $parent);
                    current_ids = $textarea.val().split(',');

                    current_ids = jQuery.grep(current_ids, function (value) {
                        return value !== removeItem;
                    });

                    $item.parent('span').remove();
                    $textarea.text(current_ids.join(','));
                }

            });

        },
        attachQuickSearchListeners : function () {
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
        updateQuickSearchResults : function (input) {
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
        processQuickSearchQueryResponse : function (resp, req, panel) {
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
                        $item.html($item.html().replace(new RegExp(
                            'menu-item\\[' + matched[1] + '\\]',
                            'g'
                        ),
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
                plus    = jQuery('<button class="popmake-range-plus">+</button>'),
                minus   = jQuery('<button class="popmake-range-minus">-</button>');

            jQuery(document).on('input', 'input[type="range"]', function () {
                var $this = jQuery(this);
                $this.siblings('.popmake-range-manual').val($this.val());
            });
            jQuery('.popmake-range-manual').each(function () {
                var $this   = jQuery(this),
                    force   = $this.data('force-minmax'),
                    min     = parseInt($this.prop('min'), 0),
                    max     = parseInt($this.prop('max'), 0),
                    step    = parseInt($this.prop('step'), 0),
                    value   = parseInt($this.val(), 0);

                $slider = slider.clone();
                $plus   = plus.clone();
                $minus  = minus.clone();

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
                .on('click', '.popmake-range-manual', function () {
                    var $this = jQuery(this);
                    $this.removeProp('readonly');
                })
                .on('focusout', '.popmake-range-manual', function () {
                    var $this = jQuery(this);
                    $this.prop('readonly', true);
                })
                .on('change', '.popmake-range-manual', function () {

                    var $this   = jQuery(this),
                        max     = parseInt($this.prop('max'), 0),
                        step     = parseInt($this.prop('step'), 0),
                        force   = $this.data('force-minmax'),
                        value     = parseInt($this.val(), 0);

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

                    var $this   = jQuery(this).siblings('.popmake-range-manual'),
                        step    = parseInt($this.prop('step'), 0),
                        value   = parseInt($this.val(), 0),
                        val     = value + step;

                    $slider = $this.prev();

                    $this.val(val).trigger('input');
                    $slider.val(val);

                })
                .on('click', '.popmake-range-minus', function (event) {

                    event.preventDefault();

                    var $this   = jQuery(this).siblings('.popmake-range-manual'),
                        step    = parseInt($this.prop('step'), 0),
                        value   = parseInt($this.val(), 0),
                        val     = value - step;

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
        initialize_tabs: function () {
            //var active_tab = window.location.hash.replace('#top#','');
            var active_tab = window.location.hash;
            if (active_tab === '') {
                active_tab = '#' + jQuery('.popmake-tab-content').eq(0).attr('id');
            }

            jQuery('.popmake-tab-content').hide();
            jQuery(active_tab).show();
            jQuery(active_tab + '-tab').addClass('nav-tab-active');
            jQuery(window).scrollTop(0);


            jQuery('#popmake-tabs .nav-tab').click(function (event) {
                event.preventDefault();

                jQuery('.popmake-tab-content').hide();
                jQuery('.popmake-tab').removeClass('nav-tab-active');

                var id = jQuery(this).attr('href');
                jQuery(id).show();
                jQuery(this).addClass('nav-tab-active');

                if (history.pushState) {
                    history.pushState(null, null, id);
                } else {
                    location.hash = id;
                    jQuery(window).scrollTop(0);
                }
            });
        },
        initialize_popup_page: function () {

            var update_type_options = function ($this) {
                    var $options = $this.siblings('.options'),
                        excludes,
                        others;

                    if ($this.is(':checked')) {
                        $options.show();

                        if ($this.attr('id') === 'popup_loading_condition_on_entire_site') {
                            excludes = $this.parents('#popmake_popup_loading_condition_fields').find('[id^="loading_condition-exclude_on_"]');
                            others = $this.parents('.loading_condition').siblings('.loading_condition');
                            others.hide();
                            jQuery('> *', others).prop('disabled', true);
                            excludes.show();
                            jQuery('> *', excludes).removeProp('disabled');
                        } else {
                            jQuery('*', $options).removeProp('disabled');
                        }
                    } else {
                        $options.hide();
                        if ($this.attr('id') === 'popup_loading_condition_on_entire_site') {
                            excludes = $this.parents('#popmake_popup_loading_condition_fields').find('[id^="loading_condition-exclude_on_"]');
                            others = $this.parents('.loading_condition').siblings('.loading_condition');
                            others.show();
                            jQuery('> *', others).removeProp('disabled');
                            excludes.hide();
                            jQuery('> *', excludes).prop('disabled', true);
                        } else {
                            jQuery('*', $options).prop('disabled', true);
                        }
                    }
                },
                update_specific_checkboxes = function ($this) {
                    var $option = $this.parent().siblings('input[type="checkbox"]:first'),
                        exclude = $option.attr('name').indexOf("exclude") >= 0,
                        type = exclude ? $option.attr('name').replace('popup_loading_condition_exclude_on_specific_', '') : $option.attr('name').replace('popup_loading_condition_on_specific_', ''),
                        type_box = exclude ? jQuery('#exclude_on_specific_' + type) : jQuery('#on_specific_' + type);
                    if ($this.is(':checked')) {
                        if ($this.val() === 'true') {
                            $option.prop('checked', true);
                            type_box.show();
                            jQuery('*', type_box).removeProp('disabled');
                        } else if ($this.val() === '') {
                            $option.removeProp('checked');
                            type_box.hide();
                            jQuery('*', type_box).prop('disabled', true);
                        }
                    }
                },
                update_size = function () {
                    if (jQuery("#popup_display_size").val() !== 'custom') {
                        jQuery('.custom-size-only').hide();
                    } else {
                        jQuery('.custom-size-only').show();
                        if (jQuery('#popup_display_custom_height_auto').is(':checked')) {
                            jQuery('.custom-size-height-only').hide();
                        } else {
                            jQuery('.custom-size-height-only').show();
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
                };

            jQuery('#popuptitlediv').insertAfter('#titlediv');
            jQuery('[name^="menu-item"]').removeAttr('name');

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
                        jQuery('#ajax-loading').hide();
                        jQuery('#publish').removeClass('button-primary-disabled');
                        jQuery('#title').prop('required', 'required');
                    }
                })
                .on('change', "#popup_display_size", function () { update_size(); })
                .on('click', '#popup_display_custom_height_auto', function () { update_size(); })
                .on('change', "#popup_display_animation_type", function () { update_animation(); })
                .on('change', '#popup_display_location', function () { update_location(); });



            jQuery('#popmake_popup_loading_condition_fields .loading_condition > input[type="checkbox"]')
                .on('click', function () { update_type_options(jQuery(this)); })
                .each(function () { update_type_options(jQuery(this)); });

            jQuery('input[type="radio"][name*="radio_checkbox_"]')
                .on('click', function () { update_specific_checkboxes(jQuery(this)); })
                .each(function () { update_specific_checkboxes(jQuery(this)); });

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
        },
        theme_page_listeners: function () {
            var self = this;
            jQuery(document)
                .on('change input focusout', 'select, input:not(.color-picker)', function () {
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
                })
                .on('change, irischange', '.color-picker', function (event) {
                    self.update_theme();
                    var $input = jQuery(event.currentTarget);
                    if ($input.hasClass('background-color')) {
                        $input.parents('table').find('.background-opacity').show();
                    }
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
        initialize_theme_page: function () {
            jQuery('#popuptitlediv').insertAfter('#titlediv');

            var self = this,
                table = jQuery('#popup_theme_close_location').parents('table');
            self.update_theme();
            self.theme_page_listeners();
            self.theme_preview_scroll();

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
            var $overlay = jQuery('.empreview .example-popup-overlay'),
                $container = jQuery('.empreview .example-popup'),
                $title = jQuery('.title', $container),
                $content = jQuery('.content', $container),
                $close = jQuery('.close-popup', $container),
                container_inset = theme.container_boxshadow_inset === 'yes' ? 'inset ' : '',
                close_inset = theme.close_boxshadow_inset === 'yes' ? 'inset ' : '';

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
                fontSize: theme.title_font_size + 'px',
                fontFamily: theme.title_font_family,
                textAlign: theme.title_text_align,
                textShadow: theme.title_textshadow_horizontal + 'px ' + theme.title_textshadow_vertical + 'px ' + theme.title_textshadow_blur + 'px ' + this.convert_hex(theme.title_textshadow_color, theme.title_textshadow_opacity)
            });
            $content.removeAttr('style').css({
                color: theme.content_font_color,
                //fontSize: theme.content_font_size+'px',
                fontFamily: theme.content_font_family
            });
            $close.html(theme.close_text).removeAttr('style').css({
                padding: theme.close_padding + 'px',
                backgroundColor: this.convert_hex(theme.close_background_color, theme.close_background_opacity),
                color: theme.close_font_color,
                fontSize: theme.close_font_size + 'px',
                fontFamily: theme.close_font_family,
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
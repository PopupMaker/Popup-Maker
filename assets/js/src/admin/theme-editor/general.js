/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/
/* global PUM_Admin, jQuery, pum_google_fonts, pum_theme_settings_editor */
(function ($) {
    "use strict";

    window.PUM_Admin = window.PUM_Admin || {};

    window.pum_theme_settings_editor = window.pum_theme_settings_editor || {
        form_args: {},
        current_values: {}
    };

    PUM_Admin.themeEditor = {
        preview_fixed_scroll: function () {
            var $preview = $('#pum_theme_preview'),
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
        update_font_options: function (prefix) {
            var $font_family_select = $('select[id="' + prefix + '_font_family"]'),
                $font_weight_select = $('select[id="' + prefix + '_font_weight"]'),
                $font_style_select = $('select[id="' + prefix + '_font_style"]'),
                $font_weight_options = $font_weight_select.find('option'),
                $font_style_options = $font_style_select.find('option'),
                font_weights = [400, 300, 700, 100, 200, 500, 600, 800, 900],
                font_styles = ['', 'italic'],
                chosen_font = $font_family_select.val(),
                chosen_weight = $font_weight_select.val(),
                chosen_style = $font_style_select.val(),
                font,
                i;


            // Google Font Chosen
            if (pum_google_fonts[chosen_font] !== undefined) {

                font = pum_google_fonts[chosen_font];

                $font_weight_options.prop('disabled', true);
                $font_style_options.prop('disabled', true);

                if (font.variants.length) {
                    for (i = 0; font.variants.length > i; i += 1) {
                        if (font.variants[i] === 'regular') {
                            $('option[value="400"]', $font_weight_select).prop('disabled', false);
                            $('option[value=""]', $font_style_select).prop('disabled', false);
                        } else {
                            if (font.variants[i].indexOf('italic') >= 0) {
                                $('option[value="italic"]', $font_style_select).prop('disabled', false);
                            }
                            $('option[value="' + parseInt(font.variants[i], 10) + '"]', $font_weight_select).prop('disabled', false);
                        }
                    }
                }
                // Standard Font Chosen
            } else {
                $font_weight_options.prop('disabled', false);
                $font_style_options.prop('disabled', false);
            }

            if (chosen_weight === '' || $font_weight_options.filter('[value="' + chosen_weight + '"]').is(':disabled')) {
                for (i = 0; font_weights.length > i; i += 1) {
                    if (!$font_weight_options.filter('[value="' + font_weights[i] + '"]').is(':disabled')) {
                        $font_weight_select.val(font_weights[i]);
                        break;
                    }
                }
            }

            if ($font_style_options.filter('[value="' + chosen_style + '"]').is(':disabled')) {
                for (i = 0; font_styles.length > i; i += 1) {
                    if (!$font_style_options.filter('[value="' + font_styles[i] + '"]').is(':disabled')) {
                        $font_style_select.val(font_styles[i]);
                        break;
                    }
                }
            }

            // Update Select2 if enabled.
            $font_family_select.trigger('change.select2');
            $font_weight_select.trigger('change.select2');
            $font_family_select.trigger('change.select2');

            $font_weight_select = $font_weight_select.parents('.pum-field');
            if ($font_weight_options.filter(':not(:disabled)').length > 1) {
                $font_weight_select.show();
            } else {
                $font_weight_select.hide();
            }

            $font_family_select = $font_style_select.parents('.pum-field');
            if ($font_style_options.filter(':not(:disabled)').length > 1) {
                $font_family_select.show();
            } else {
                $font_family_select.hide();
            }
        },
        update_font_selectboxes: function () {
            return $('select[id$="_font_family"]').each(function () {
                PUM_Admin.themeEditor.update_font_options($(this).attr('id').replace('_font_family', ''));
            });
        },
        refresh_preview: function () {
            var form_values = $('#pum-theme-settings-container').pumSerializeObject(),
                theme = form_values.theme_settings,
                i;

            debugger;

            this.restyle_preview(theme);
        },
        restyle_preview: function (theme) {
            var $overlay = $('.pum-popup-overlay'),
                $container = $('.pum-popup-container'),
                $title = $('.pum-popup-title', $container),
                $content = $('.pum-popup-content', $container),
                $close = $('.pum-popup-close', $container),
                container_inset = theme.container_boxshadow_inset === 'yes' ? 'inset ' : '',
                close_inset = theme.close_boxshadow_inset === 'yes' ? 'inset ' : '',
                link;

            if (pum_google_fonts[theme.title_font_family] !== undefined) {

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

            if (pum_google_fonts[theme.content_font_family] !== undefined) {

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

            if (pum_google_fonts[theme.close_font_family] !== undefined) {

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
                backgroundColor: window.PUM_Admin.utils.convert_hex(theme.overlay_background_color, theme.overlay_background_opacity)
            });
            $container.removeAttr('style').css({
                padding: theme.container_padding + 'px',
                backgroundColor: window.PUM_Admin.utils.convert_hex(theme.container_background_color, theme.container_background_opacity),
                borderStyle: theme.container_border_style,
                borderColor: theme.container_border_color,
                borderWidth: theme.container_border_width + 'px',
                borderRadius: theme.container_border_radius + 'px',
                boxShadow: container_inset + theme.container_boxshadow_horizontal + 'px ' + theme.container_boxshadow_vertical + 'px ' + theme.container_boxshadow_blur + 'px ' + theme.container_boxshadow_spread + 'px ' + window.PUM_Admin.utils.convert_hex(theme.container_boxshadow_color, theme.container_boxshadow_opacity)
            });
            $title.removeAttr('style').css({
                color: theme.title_font_color,
                lineHeight: theme.title_line_height + 'px',
                fontSize: theme.title_font_size + 'px',
                fontFamily: theme.title_font_family,
                fontStyle: theme.title_font_style,
                fontWeight: theme.title_font_weight,
                textAlign: theme.title_text_align,
                textShadow: theme.title_textshadow_horizontal + 'px ' + theme.title_textshadow_vertical + 'px ' + theme.title_textshadow_blur + 'px ' + window.PUM_Admin.utils.convert_hex(theme.title_textshadow_color, theme.title_textshadow_opacity)
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
                backgroundColor: window.PUM_Admin.utils.convert_hex(theme.close_background_color, theme.close_background_opacity),
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
                boxShadow: close_inset + theme.close_boxshadow_horizontal + 'px ' + theme.close_boxshadow_vertical + 'px ' + theme.close_boxshadow_blur + 'px ' + theme.close_boxshadow_spread + 'px ' + window.PUM_Admin.utils.convert_hex(theme.close_boxshadow_color, theme.close_boxshadow_opacity),
                textShadow: theme.close_textshadow_horizontal + 'px ' + theme.close_textshadow_vertical + 'px ' + theme.close_textshadow_blur + 'px ' + window.PUM_Admin.utils.convert_hex(theme.close_textshadow_color, theme.close_textshadow_opacity)
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

            /** @deprecated 1.8.0 */
            $(document).trigger('popmake-admin-retheme', [theme]);

            $(document).trigger('pumRestylePreview', [theme]);
        }
    };

    $(document)
        .ready(function () {
            $(this).trigger('pum_init');

            var $container = $('#pum-theme-settings-container'),
                args = pum_theme_settings_editor.form_args || {},
                values = pum_theme_settings_editor.current_values || {};

            if ($container.length) {
                $container.find('.pum-no-js').hide();
                PUM_Admin.forms.render(args, values, $container);
            }

            PUM_Admin.themeEditor.preview_fixed_scroll();
            PUM_Admin.themeEditor.refresh_preview();
        })
        .on('change', 'select[id$="_font_family"]', function () {
            var prefix = $(this).attr('id').replace('_font_family', '');

            PUM_Admin.themeEditor.update_font_options(prefix);
        })
        /**
         * Change to the appropriate tab when an element is clicked. IE click the close button in preview and trigger the close tab.
         */
        .on('click', '.pum-popup-overlay, .pum-popup-container, .pum-popup-title, .pum-popup-content, .pum-popup-close', function (event) {
            var $this = $(this),
                clicked_class = $this.attr('class');

            event.preventDefault();
            event.stopPropagation();

            switch (clicked_class) {
            case 'pum-popup-overlay':
                $('a[href="#pum-theme-settings_overlay"]')[0].click();
                break;
            case 'pum-popup-container':
                $('a[href="#pum-theme-settings_container"]')[0].click();
                break;
            case 'pum-popup-title':
                $('a[href="#pum-theme-settings_title"]')[0].click();
                break;
            case 'pum-popup-content':
                $('a[href="#pum-theme-settings_content"]')[0].click();
                break;
            case 'pum-popup-close':
                $('a[href="#pum-theme-settings_close"]')[0].click();
                break;
            }

            $("html, body").animate({
                scrollTop: ($('#pum_theme_settings').offset().top - 40) + 'px'
            });
        })
        /**
         * Trigger preview update after any field change.
         */
        .on('change input focusout', 'select, input', function () {
            PUM_Admin.themeEditor.refresh_preview();
        });
}(jQuery));

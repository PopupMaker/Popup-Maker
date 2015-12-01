/**
 * Defines the core $.popmake function which will load the proper methods.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $.fn.popmake = function (method) {
        // Method calling logic
        if ($.fn.popmake.methods[method]) {
            return $.fn.popmake.methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        if (typeof method === 'object' || !method) {
            return $.fn.popmake.methods.init.apply(this, arguments);
        }
        $.error('Method ' + method + ' does not exist on $.fn.popmake');
    };

    // Defines the current version.
    $.fn.popmake.version = 1.4;

    // Stores the last open popup.
    $.fn.popmake.last_open_popup = null;

    // Defines the core $.popmake methods.

     $.fn.popmake.methods = {
        init: function (options) {
            return this.each(function () {
                var $this = $(this),
                    settings = $.extend(true, {}, $.fn.popmake.defaults, $this.data('popmake'), options);

                if (!(settings.theme_id > 0)) {
                    settings.theme_id = popmake_default_theme;
                }

                if (!$('#' + settings.overlay.attr.id).length) {
                    $('<div>').attr(settings.overlay.attr).appendTo('body');
                }

                $(window).on('resize', function () {
                    if ($this.hasClass('active')) {
                        $.fn.popmake.utilities.throttle(setTimeout(function () {
                            $this.popmake('reposition');
                        }, 25), 500, false);
                    }
                });

                if (typeof popmake_powered_by === 'string' && popmake_powered_by !== '') {
                    $('.popmake-content', $this).append($(popmake_powered_by));
                }

                $this
                    .data('popmake', settings)
                    .trigger('popmakeInit')
                    .trigger('pumInit');
                return this;
            });
        },
        setup_close: function () {
            var $this = $(this),
                settings = $this.data('popmake'),
                $overlay = $('#popmake-overlay'),
                $close = $('.popmake-close', $this);

            $close
                .off('click.popmake')
                .on("click.popmake", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $.fn.popmake.last_close_trigger = 'Close Button';
                    $this.popmake('close');
                });

            if (settings.meta.close.esc_press || settings.meta.close.f4_press) {
                $(window)
                    .off('keyup.popmake')
                    .on('keyup.popmake', function (e) {
                        if (e.keyCode === 27 && settings.meta.close.esc_press) {
                            $.fn.popmake.last_close_trigger = 'ESC Key';
                            $this.popmake('close');
                        }
                        if (e.keyCode === 115 && settings.meta.close.f4_press) {
                            $.fn.popmake.last_close_trigger = 'F4 Key';
                            $this.popmake('close');
                        }
                    });
            }

            if (settings.meta.close.overlay_click) {
                $overlay
                    .off('click.popmake')
                    .on('click.popmake', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        $.fn.popmake.last_close_trigger = 'Overlay Click';
                        $this.popmake('close');

                    });
            }

            $this.trigger('popmakeSetupClose');
            return this;
        },
        open: function (callback) {
            var $this = $(this),
                settings = $this.data('popmake');

            if (!settings.meta.display.stackable) {
                $this.popmake('close_all');
            }

            $this
                .css({visibility: "visible"})
                .hide()
                .addClass('active')
                .popmake('setup_close')
                .popmake('reposition')
                .trigger('pumBeforeOpen')
                .trigger('popmakeBeforeOpen');

            if (settings.meta.close.button_delay > 0) {
                $this.find('.popmake-content + .popmake-close').fadeOut(0);
            }

            if ($this.hasClass('preventOpen')) {
                $this
                    .removeClass('preventOpen')
                    .removeClass('active');
                return this;
            }

            $('#popmake-overlay')
                .prop('class', 'popmake-overlay theme-' + settings.theme_id)
                .css({'z-index': settings.meta.display.overlay_zindex || 1999999998});

            $this
                .css({'z-index': settings.meta.display.zindex || 1999999999})
                .popmake('animate', settings.meta.display.animation_type, function () {

                    if (settings.meta.close.button_delay > 0) {
                        setTimeout(function () {
                            $this.find('.popmake-content + .popmake-close').fadeIn();
                        }, settings.meta.close.button_delay);
                    }

                    $this
                        .trigger('pumAfterOpen')
                        .trigger('popmakeAfterOpen');
                    $.fn.popmake.last_open_popup = $this;
                    if (callback !== undefined) {
                        callback();
                    }
                });
            return this;
        },
        close: function (callback) {
            return this.each(function () {
                var $this = $(this),
                    $overlay = $('#popmake-overlay'),
                    $close = $('.popmake-close', $this),
                    settings = $this.data('popmake');

                $this
                    .trigger('pumBeforeClose')
                    .trigger('popmakeBeforeClose');

                if ($this.hasClass('preventClose')) {
                    $this.removeClass('preventClose');
                    return this;
                }

                $this
                    .fadeOut(settings.close.close_speed, function () {

                        if ($overlay.length && $overlay.is(":visible")) {
                            $overlay.fadeOut(settings.close.close_speed);
                        }

                        $(window).off('keyup.popmake');
                        $overlay.off('click.popmake');
                        $close.off('click.popmake');

                        $this
                            .removeClass('active')
                            .trigger('pumAfterClose')
                            .trigger('popmakeAfterClose');

                        $('iframe', $this).filter('[src*="youtube"],[src*="vimeo"]').each(function () {
                            var $iframe = $(this),
                                src = $iframe.attr('src')
                                    // Remove autoplay so video doesn't start playing again.
                                    .replace('autoplay=1', '1=1');
                            $iframe.attr('src', '').attr('src', src);
                        });

                        $('video', $this).each(function () {
                            this.pause();
                        });

                        if (callback !== undefined) {
                            callback();
                        }
                    });
                return this;
            });
        },
        close_all: function () {
            $('.popmake.active').popmake('close');
            return this;
        },
        reposition: function (callback) {
            $(this).trigger('popmakeBeforeReposition');
            var $this = $(this),
                settings = $this.data('popmake'),
                display = settings.meta.display,
                location = display.location,
                reposition = {
                    my: "",
                    at: ""
                },
                opacity = false;

            if (location.indexOf('left') >= 0) {
                reposition = {
                    my: reposition.my + " left" + (display.position_left !== 0 ? "+" + display.position_left : ""),
                    at: reposition.at + " left"
                };
            }
            if (location.indexOf('right') >= 0) {
                reposition = {
                    my: reposition.my + " right" + (display.position_right !== 0 ? "-" + display.position_right : ""),
                    at: reposition.at + " right"
                };
            }
            if (location.indexOf('center') >= 0) {
                if (location === 'center') {
                    reposition = {
                        my: "center",
                        at: "center"
                    };
                } else {
                    reposition = {
                        my: reposition.my + " center",
                        at: reposition.at + " center"
                    };
                }
            }
            if (location.indexOf('top') >= 0) {
                reposition = {
                    my: reposition.my + " top" + (display.position_top !== 0 ? "+" + ($('body').hasClass('admin-bar') ? parseInt(display.position_top, 10) + 32 : display.position_top) : ""),
                    at: reposition.at + " top"
                };
            }
            if (location.indexOf('bottom') >= 0) {
                reposition = {
                    my: reposition.my + " bottom" + (display.position_bottom !== 0 ? "-" + display.position_bottom : ""),
                    at: reposition.at + " bottom"
                };
            }


            reposition.my = $.trim(reposition.my);
            reposition.at = $.trim(reposition.at);
            reposition.of = window;
            reposition.collision = 'none';
            reposition.using = typeof callback === "function" ? callback : $.fn.popmake.callbacks.reposition_using;

            if ($this.is(':hidden')) {
                opacity = $this.css("opacity");
                $this.css({
                    opacity: 0
                }).show();
            }

            $this
                .removeClass('responsive size-nano size-micro size-tiny size-small size-medium size-normal size-large size-xlarge fixed custom-position')
                .addClass('size-' + settings.meta.display.size);


            if (display.position_fixed) {
                $this.addClass('fixed');
            }
            if (settings.meta.display.size === 'custom') {
                $this.css({
                    width: settings.meta.display.custom_width + settings.meta.display.custom_width_unit,
                    height: settings.meta.display.custom_height_auto ? 'auto' : settings.meta.display.custom_height + settings.meta.display.custom_height_unit
                });
            } else {
                if (settings.meta.display.size !== 'auto') {
                    $this
                        .addClass('responsive')
                        .css({
                            minWidth: settings.meta.display.responsive_min_width !== '' ? settings.meta.display.responsive_min_width + settings.meta.display.responsive_min_width_unit : 'auto',
                            maxWidth: settings.meta.display.responsive_max_width !== '' ? settings.meta.display.responsive_max_width + settings.meta.display.responsive_max_width_unit : 'auto'
                        });
                }
            }

            $this
                .addClass('custom-position')
                .position(reposition)
                .trigger('popmakeAfterReposition');

            if (opacity) {
                $this.css({
                    opacity: opacity
                }).hide();
            }
            return this;
        },
        retheme: function (theme) {
            $(this).trigger('popmakeBeforeRetheme');
            var $this = $(this),
                settings = $this.data('popmake'),
                $overlay = $('#' + settings.overlay.attr.id),
                $container = $this,
                $title = $('.' + settings.title.attr.class, $container),
                $content = $('> .' + settings.content.attr.class, $container),
                $close = $('> .' + settings.close.attr.class, $container),
                container_inset,
                close_inset;

            if (theme === undefined) {
                theme = $.fn.popmake.themes[settings.theme_id];
                if (theme === undefined) {
                    theme = $.fn.popmake.themes[1];
                }
            }

            container_inset = theme.container.boxshadow_inset === 'yes' ? 'inset ' : '';
            close_inset = theme.close.boxshadow_inset === 'yes' ? 'inset ' : '';

            $overlay.removeAttr('style').css({
                backgroundColor: $.fn.popmake.utilities.convert_hex(theme.overlay.background_color, theme.overlay.background_opacity),
                zIndex: settings.meta.display.overlay_zindex || 998
            });
            $container.css({
                padding: theme.container.padding + 'px',
                backgroundColor: $.fn.popmake.utilities.convert_hex(theme.container.background_color, theme.container.background_opacity),
                borderStyle: theme.container.border_style,
                borderColor: theme.container.border_color,
                borderWidth: theme.container.border_width + 'px',
                borderRadius: theme.container.border_radius + 'px',
                boxShadow: container_inset + theme.container.boxshadow_horizontal + 'px ' + theme.container.boxshadow_vertical + 'px ' + theme.container.boxshadow_blur + 'px ' + theme.container.boxshadow_spread + 'px ' + $.fn.popmake.utilities.convert_hex(theme.container.boxshadow_color, theme.container.boxshadow_opacity),
                zIndex: settings.meta.display.zindex || 999
            });
            $title.css({
                color: theme.title.font_color,
                lineHeight: theme.title.line_height + 'px',
                fontSize: theme.title.font_size + 'px',
                fontFamily: theme.title.font_family,
                fontWeight: theme.title.font_weight,
                fontStyle: theme.title.font_style,
                textAlign: theme.title.text_align,
                textShadow: theme.title.textshadow_horizontal + 'px ' + theme.title.textshadow_vertical + 'px ' + theme.title.textshadow_blur + 'px ' + $.fn.popmake.utilities.convert_hex(theme.title.textshadow_color, theme.title.textshadow_opacity)
            });
            $content.css({
                color: theme.content.font_color,
                //fontSize: theme.content.font_size+'px',
                fontFamily: theme.content.font_family,
                fontWeight: theme.content.font_weight,
                fontStyle: theme.content.font_style
            });
            $('p, label', $content).css({
                color: theme.content.font_color,
                //fontSize: theme.content.font_size+'px',
                fontFamily: theme.content.font_family
            });
            $close.html(theme.close.text).css({
                padding: theme.close.padding + 'px',
                height: theme.close.height + 'px',
                width: theme.close.width + 'px',
                backgroundColor: $.fn.popmake.utilities.convert_hex(theme.close.background_color, theme.close.background_opacity),
                color: theme.close.font_color,
                lineHeight: theme.close.line_height + 'px',
                fontSize: theme.close.font_size + 'px',
                fontWeight: theme.close.font_weight,
                fontStyle: theme.close.font_style,
                fontFamily: theme.close.font_family,
                borderStyle: theme.close.border_style,
                borderColor: theme.close.border_color,
                borderWidth: theme.close.border_width + 'px',
                borderRadius: theme.close.border_radius + 'px',
                boxShadow: close_inset + theme.close.boxshadow_horizontal + 'px ' + theme.close.boxshadow_vertical + 'px ' + theme.close.boxshadow_blur + 'px ' + theme.close.boxshadow_spread + 'px ' + $.fn.popmake.utilities.convert_hex(theme.close.boxshadow_color, theme.close.boxshadow_opacity),
                textShadow: theme.close.textshadow_horizontal + 'px ' + theme.close.textshadow_vertical + 'px ' + theme.close.textshadow_blur + 'px ' + $.fn.popmake.utilities.convert_hex(theme.close.textshadow_color, theme.close.textshadow_opacity),
                left: 'auto',
                right: 'auto',
                bottom: 'auto',
                top: 'auto'
            });
            switch (theme.close.location) {
                case "topleft":
                    $close.css({
                        top: theme.close.position_top + 'px',
                        left: theme.close.position_left + 'px'
                    });
                    break;
                case "topright":
                    $close.css({
                        top: theme.close.position_top + 'px',
                        right: theme.close.position_right + 'px'
                    });
                    break;
                case "bottomleft":
                    $close.css({
                        bottom: theme.close.position_bottom + 'px',
                        left: theme.close.position_left + 'px'
                    });
                    break;
                case "bottomright":
                    $close.css({
                        bottom: theme.close.position_bottom + 'px',
                        right: theme.close.position_right + 'px'
                    });
                    break;
            }
            $this.trigger('popmakeAfterRetheme', [theme]);
            return this;
        },
        animation_origin: function (origin) {
            var $this = $(this),
                start = {
                    my: "",
                    at: ""
                };

            switch (origin) {
                case 'top':
                    start = {
                        my: "left+" + $this.offset().left + " bottom-100",
                        at: "left top"
                    };
                    break;
                case 'bottom':
                    start = {
                        my: "left+" + $this.offset().left + " top+100",
                        at: "left bottom"
                    };
                    break;
                case 'left':
                    start = {
                        my: "right top+" + $this.offset().top,
                        at: "left top"
                    };
                    break;
                case 'right':
                    start = {
                        my: "left top+" + $this.offset().top,
                        at: "right top"
                    };
                    break;
                default:
                    if (origin.indexOf('left') >= 0) {
                        start = {
                            my: start.my + " right",
                            at: start.at + " left"
                        };
                    }
                    if (origin.indexOf('right') >= 0) {
                        start = {
                            my: start.my + " left",
                            at: start.at + " right"
                        };
                    }
                    if (origin.indexOf('center') >= 0) {
                        start = {
                            my: start.my + " center",
                            at: start.at + " center"
                        };
                    }
                    if (origin.indexOf('top') >= 0) {
                        start = {
                            my: start.my + " bottom-100",
                            at: start.at + " top"
                        };
                    }
                    if (origin.indexOf('bottom') >= 0) {
                        start = {
                            my: start.my + " top+100",
                            at: start.at + " bottom"
                        };
                    }
                    start.my = $.trim(start.my);
                    start.at = $.trim(start.at);
                    break;
            }
            start.of = window;
            start.collision = 'none';
            return start;
        }
    };

}(jQuery));
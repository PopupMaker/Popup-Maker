/**
 * Defines the core $.popmake function which will load the proper methods.
 * Version 1.4.0
 */
var PUM;
(function ($, document, undefined) {
    "use strict";

    PUM = {
        getPopup: function (el) {
            var $this = $(el);
            return $this.hasClass('popmake') ? $this.parents('.pum-overlay') : $this;
        }
    };

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

    // Defines the core $.popmake methods.
    $.fn.popmake.methods = {
        init: function (options) {
            return this.each(function () {
                var $popup = PUM.getPopup(this),
                    settings = $.extend(true, {}, $.fn.popmake.defaults, $popup.data('popmake'), options);

                if (!(settings.theme_id > 0)) {
                    settings.theme_id = popmake_default_theme;
                }

                $(window).on('resize', function () {
                    if ($popup.hasClass('pum-active') || $popup.find('.popmake.active').length) {
                        $.fn.popmake.utilities.throttle(setTimeout(function () {
                            $popup.popmake('reposition');
                        }, 25), 500, false);
                    }
                });

                if (typeof popmake_powered_by === 'string' && popmake_powered_by !== '') {
                    $popup.popmake('getContent').append($(popmake_powered_by));
                }


                // Added popmake settings to the container for temporary backward compatibility with extensions.
                // TODO Once extensions updated remove this.
                $popup.find('.pum-container').data('popmake', settings);

                $popup
                    .data('popmake', settings)
                    .trigger('pumInit');

                return this;
            });
        },
        getOverlay: function () {
            return $(this);
        },
        getContainer: function () {
            return $(this).find('.pum-container');
        },
        getTitle: function () {
            return $(this).find('.pum-title') || null;
        },
        getContent: function () {
            return $(this).find('.pum-content') || null;
        },
        getClose: function () {
            return $(this).find('.pum-content + .pum-close') || null;
        },
        getSettings: function () {
            return $(this).data('popmake');
        },
        open: function (callback) {
            var $popup = PUM.getPopup(this),
                $container = $popup.popmake('getContainer'),
                $close = $popup.popmake('getClose'),
                settings = $popup.popmake('getSettings');

            if (!settings.meta.display.stackable) {
                $popup.popmake('close_all');
            }

            $popup
                .addClass('pum-active')
                .popmake('setup_close')
                .popmake('reposition')
                .trigger('pumBeforeOpen');


            // TODO: Remove this after testing for its neccessity.
            /*
             $container
             .css({visibility: "visible"})
             .hide();
             */

            if (settings.meta.close.button_delay > 0) {
                $close.fadeOut(0);
            }

            if ($popup.hasClass('preventOpen') || $container.hasClass('preventOpen')) {
                $popup
                    .removeClass('preventOpen')
                    .removeClass('pum-active')
                    .trigger('pumOpenPrevented');

                return this;
            }

            $('html').addClass('pum-open');

            $popup
            // TODO: Remove this.
                .css({'z-index': settings.meta.display.overlay_zindex || 1999999998})
                .popmake('animate', settings.meta.display.animation_type, function () {

                    if (settings.meta.close.button_delay > 0) {
                        setTimeout(function () {
                            $close.fadeIn();
                        }, settings.meta.close.button_delay);
                    }

                    $popup.trigger('pumAfterOpen');

                    $.fn.popmake.last_open_popup = $popup;

                    // Fire user passed callback.
                    if (callback !== undefined) {
                        callback();
                        // TODO Test this new method. Then remove the above.
                        //callback.apply(this);
                    }
                });

            return this;
        },
        setup_close: function () {
            var $popup = PUM.getPopup(this),
                $close = $popup.popmake('getClose'),
                settings = $popup.popmake('getSettings');

            // TODO: Move to a global $(document).on type bind. Possibly look for an inactive class to fail on.
            $close
                .off('click.popmake')
                .on("click.popmake", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $.fn.popmake.last_close_trigger = 'Close Button';
                    $popup.popmake('close');
                });

            if (settings.meta.close.esc_press || settings.meta.close.f4_press) {
                // TODO: Move to a global $(document).on type bind. Possibly look for a class to succeed on.
                $(window)
                    .off('keyup.popmake')
                    .on('keyup.popmake', function (e) {
                        if (e.keyCode === 27 && settings.meta.close.esc_press) {
                            $.fn.popmake.last_close_trigger = 'ESC Key';
                            $popup.popmake('close');
                        }
                        if (e.keyCode === 115 && settings.meta.close.f4_press) {
                            $.fn.popmake.last_close_trigger = 'F4 Key';
                            $popup.popmake('close');
                        }
                    });
            }

            if (settings.meta.close.overlay_click) {
                // TODO: Move to a global $(document).on type bind. Possibly look for a class to succeed on.
                $popup
                    .off('click.popmake')
                    .on('click.popmake', function (e) {
                        if (e.target !== $popup[0]) {
                            return;
                        }

                        $.fn.popmake.last_close_trigger = 'Overlay Click';
                        $popup.popmake('close');
                    });
            }

            $popup.trigger('pumSetupClose');

            return this;
        },
        close: function (callback) {
            return this.each(function () {
                var $popup = PUM.getPopup(this),
                    $container = $popup.popmake('getContainer'),
                    $close = $popup.popmake('getClose');

                $popup.trigger('pumBeforeClose');

                if ($popup.hasClass('preventClose') || $container.hasClass('preventClose')) {
                    $popup
                        .removeClass('preventClose')
                        .trigger('pumClosePrevented');

                    return this;
                }

                $container
                    .fadeOut('fast', function () {

                        if ($popup.is(":visible")) {
                            $popup.fadeOut('fast');
                        }

                        $(window).off('keyup.popmake');

                        $popup.off('click.popmake');

                        $close.off('click.popmake');

                        $('html').removeClass('pum-open');

                        $popup
                            .removeClass('pum-active')
                            .trigger('pumAfterClose');

                        // TODO: Move this to its own event binding to keep this method clean and simple.
                        $container.find('iframe').filter('[src*="youtube"],[src*="vimeo"]').each(function () {
                            var $iframe = $(this),
                                src = $iframe.attr('src'),
                            // Remove autoplay so video doesn't start playing again.
                                new_src = src.replace('autoplay=1', '1=1');

                            if (new_src !== src) {
                                src = new_src;
                            }

                            $iframe.prop('src', src);
                        });

                        // TODO: Move this to its own event binding to keep this method clean and simple.
                        $container.find('video').each(function () {
                            this.pause();
                        });

                        // Fire user passed callback.
                        if (callback !== undefined) {
                            callback();
                            // TODO Test this new method. Then remove the above.
                            //callback.apply(this);
                        }
                    });
                return this;
            });
        },
        close_all: function () {
            $('.pum-active').popmake('close');
            return this;
        },
        reposition: function (callback) {
            var $popup = PUM.getPopup(this).trigger('pumBeforeReposition'),
                $container = $popup.popmake('getContainer'),
                settings = $popup.popmake('getSettings'),
                display = settings.meta.display,
                location = display.location,
                reposition = {
                    my: "",
                    at: ""
                },
                opacity = {overlay: null, container: null};

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

            if ($popup.is(':hidden')) {
                opacity.overlay = $popup.css("opacity");
                $popup.css({opacity: 0}).show();
            }

            if ($container.is(':hidden')) {
                opacity.container = $container.css("opacity");
                $container.css({opacity: 0}).show();
            }

            // TODO: Check for neccessity and remove if not needed.
            //$container
            //.removeClass('responsive size-nano size-micro size-tiny size-small size-medium size-normal size-large size-xlarge fixed custom-position')
            //.addClass('size-' + settings.meta.display.size);


            if (display.position_fixed) {
                $container.addClass('fixed');
            }
            if (settings.meta.display.size === 'custom') {
                $container.css({
                    width: settings.meta.display.custom_width + settings.meta.display.custom_width_unit,
                    height: settings.meta.display.custom_height_auto ? 'auto' : settings.meta.display.custom_height + settings.meta.display.custom_height_unit
                });
            } else {
                if (settings.meta.display.size !== 'auto') {
                    $container
                        .addClass('responsive')
                        .css({
                            minWidth: settings.meta.display.responsive_min_width !== '' ? settings.meta.display.responsive_min_width + settings.meta.display.responsive_min_width_unit : 'auto',
                            maxWidth: settings.meta.display.responsive_max_width !== '' ? settings.meta.display.responsive_max_width + settings.meta.display.responsive_max_width_unit : 'auto'
                        });
                }
            }

            // TODO: Remove the add class and migrate the trigger to the $popup with pum prefix.
            $container
                .addClass('custom-position')
                .position(reposition)
                .trigger('popmakeAfterReposition');

            if (opacity.overlay) {
                $popup.css({opacity: opacity.overlay}).hide();
            }
            if (opacity.container) {
                $container.css({opacity: opacity.container}).hide();
            }
            return this;
        },
        /**
         * @deprecated 1.3.0
         *
         * @param theme
         * @returns {$.fn.popmake.methods}
         */
        retheme: function (theme) {
            $(this).trigger('popmakeBeforeRetheme');
            var $popup = PUM.getPopup(this),
                $container = $popup.popmake('getContainer'),
                $title = $popup.popmake('getTitle'),
                $content = $popup.popmake('getContent'),
                $close = $popup.popmake('getClose'),
                settings = $popup.popmake('getSettings'),
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

            $popup.removeAttr('style').css({
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
            $popup.trigger('popmakeAfterRetheme', [theme]);
            return this;
        },
        animation_origin: function (origin) {
            var $popup = PUM.getPopup(this),
                $container = $popup.popmake('getContainer'),
                start = {
                    my: "",
                    at: ""
                };

            switch (origin) {
            case 'top':
                start = {
                    my: "left+" + $container.offset().left + " bottom-100",
                    at: "left top"
                };
                break;
            case 'bottom':
                start = {
                    my: "left+" + $container.offset().left + " top+100",
                    at: "left bottom"
                };
                break;
            case 'left':
                start = {
                    my: "right top+" + $container.offset().top,
                    at: "left top"
                };
                break;
            case 'right':
                start = {
                    my: "left top+" + $container.offset().top,
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

}(jQuery, document));
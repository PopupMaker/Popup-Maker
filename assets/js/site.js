/**
 * Adds needed backward compatibility for older versions of jQuery
 */
(function ($) {
    "use strict";
    if ($.fn.on === undefined) {
        $.fn.on = function (types, sel, fn) {
            return this.delegate(sel, types, fn);
        };
    }
    if ($.fn.off === undefined) {
        $.fn.off = function (types, sel, fn) {
            return this.undelegate(sel, types, fn);
        };
    }

    if ($.fn.bindFirst === undefined) {
        $.fn.bindFirst = function (which, handler) {
            var $el = $(this),
                events,
                registered;

            $el.unbind(which, handler);
            $el.bind(which, handler);

            events = $._data($el[0]).events;
            registered = events[which];
            registered.unshift(registered.pop());

            events[which] = registered;
        };
    }

    if ($.fn.outerHtml === undefined) {
        $.fn.outerHtml = function () {
            var $el = $(this).clone(),
                $temp = $('<div/>').append($el);

            return $temp.html();
        };
    }

    if (Date.now === undefined) {
        Date.now = function () {
            return new Date().getTime();
        };
    }
}(jQuery));

/**
 * Defines the core $.popmake function which will load the proper methods.
 * Version 1.4
 */
var PUM;
(function ($, document, undefined) {
    "use strict";

    PUM = {
        getPopup: function (el) {
            var $this = $(el);

            if ($this.hasClass('pum-overlay')) {
                return $this;
            }

            if ($this.hasClass('popmake')) {
                return $this.parents('.pum-overlay');
            }

            return $this.parents('.pum-overlay').length ? $this.parents('.pum-overlay') : $();
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

                if (settings.theme_id <= 0) {
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
                settings = $popup.popmake('getSettings'),
                $html = $('html');

            if (!settings.meta.display.stackable) {
                $popup.popmake('close_all');
            }

            $popup
                .addClass('pum-active')
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

            $html.addClass('pum-open');

            if (settings.meta.display.overlay_disabled) {
                $html.addClass('pum-open-overlay-disabled');
            } else {
                $html.addClass('pum-open-overlay');
            }

            if (settings.meta.display.position_fixed !== undefined && settings.meta.display.position_fixed) {
                $html.addClass('pum-open-fixed');
            } else {
                $html.addClass('pum-open-scrollable');
            }

            $popup
                .popmake('setup_close')
                .popmake('reposition')
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
                $close = $popup.popmake('getClose')
                    // Add For backward compatiblitiy.
                    .add($('.popmake-close', $popup)),
                settings = $popup.popmake('getSettings');

            // TODO: Move to a global $(document).on type bind. Possibly look for an inactive class to fail on.
            $close
                .off('click.popmake click.pum')
                .on("click.popmake click.pum", function (e) {
                    e.preventDefault();
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
                    $close = $popup.popmake('getClose').add($('.popmake-close', $popup));

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

                        $('html')
                            .removeClass('pum-open')
                            .removeClass('pum-open-scrollable')
                            .removeClass('pum-open-overlay-disabled')
                            .removeClass('pum-open-fixed');

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
/**
 * Defines the core $.popmake binds.
 * Version 1.4
 */
var PUM_Accessibility;
(function ($, document, undefined) {
    "use strict";
    var $top_level_elements,
        focusableElementsString = "a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]",
        previouslyFocused,
        currentModal;

    PUM_Accessibility = {
        // Accessibility: Checks focus events to ensure they stay inside the modal.
        forceFocus: function (e) {
            if (currentModal && !$.contains(currentModal, e.target)) {
                e.stopPropagation();
                PUM_Accessibility.setFocusToFirstItem();
            }
        },
        trapTabKey: function (e) {
            // if tab or shift-tab pressed
            if (e.keyCode === 9) {
                // get list of focusable items
                var focusableItems = currentModal.find('.pum-container *').filter(focusableElementsString).filter(':visible'),
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
            currentModal.find('.pum-container *').filter(focusableElementsString).filter(':visible').filter(':not(.pum-close)').first().focus();
        }
    };

    $(document)
        .on('pumInit', '.pum', function () {
            PUM.getPopup(this).find('[tabindex]').each(function () {
                var $this = $(this);
                $this
                    .data('tabindex', $this.attr('tabindex'))
                    .prop('tabindex', '0');

            });
        })


        .on('pumBeforeOpen', '.pum', function () {
            var $popup = PUM.getPopup(this),
                $focused = $(':focus');

            // Accessibility: Sets the previous focus element.
            if (!$popup.has($focused).length) {
                previouslyFocused = $focused;
            }

            // Accessibility: Sets the current modal for focus checks.
            currentModal = $popup
            // Accessibility: Trap tab key.
                .on('keydown.pum_accessibility', PUM_Accessibility.trapTabKey)
                .attr('aria-hidden', 'false');

            $top_level_elements = $('body > *').filter(':visible').not(currentModal);
            $top_level_elements.attr('aria-hidden', 'true');

            // Accessibility: Add focus check that prevents tabbing outside of modal.
            $(document).on('focus.pum_accessibility', PUM_Accessibility.forceFocus);

            // Accessibility: Focus on the modal.
            PUM_Accessibility.setFocusToFirstItem();
        })
        .on('pumAfterOpen', '.pum', function () {

        })


        .on('pumBeforeClose', '.pum', function () {

        })
        .on('pumAfterClose', '.pum', function () {
            var $popup = PUM.getPopup(this);

            $popup
                .off('keydown.pum_accessibility')
                .attr('aria-hidden', 'true');

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
            $(document).off('focus.pum_accessibility');
        })

        .on('pumSetupClose', '.pum', function () {

        })

        .on('pumOpenPrevented', '.pum', function () {

        })

        .on('pumClosePrevented', '.pum', function () {

        })

        .on('pumBeforeReposition', '.pum', function () {

        });


}(jQuery, document));
/**
 * Defines the core pum analytics methods.
 * Version 1.4
 */

var PUM_Analytics;
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.last_open_trigger = null;
    $.fn.popmake.last_close_trigger = null;
    $.fn.popmake.conversion_trigger = null;

    PUM_Analytics = {
        send: function (data, callback) {
            var img = (new Image());

            data = $.extend({}, {
                'action': 'pum_analytics'
            }, data);

            // Add Cache busting.
            data._cache = (+(new Date()));

            // Method 1
            if (callback !== undefined) {
                img.addEventListener('load', function () {
                    callback(data);
                });
            }
            img.src = pum_vars.ajaxurl + '?' + $.param(data);

            return;
            /*
             Method 2 - True AJAX
             $.get({
             type: 'POST',
             dataType: 'json',
             url: pum_vars.ajaxurl,
             data: data,
             success: function (data) {
             if (callback !== undefined) {
             callback(data);
             }
             }
             });
             */
        }

    };

    // Only popups from the editor should fire analytics events.
    $(document)

    /**
     * Track opens for popups.
     */
        .on('pumAfterOpen.core_analytics', 'body > .pum', function () {
            var $popup = PUM.getPopup(this),
                data = {
                    pid: parseInt($popup.popmake('getSettings').id, 10) || null,
                    type: 'open'
                };

            if (data.pid > 0 && !$('body').hasClass('single-popup')) {
                PUM_Analytics.send(data);
            }
        });
}(jQuery, document));
/**
 * Defines the core $.popmake animations.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.methods.animate_overlay = function (style, duration, callback) {
        // Method calling logic
        var settings = PUM.getPopup(this).popmake('getSettings');

        if (settings.meta.display.overlay_disabled) {
            return $.fn.popmake.overlay_animations.none.apply(this, [duration, callback]);
        }

        if ($.fn.popmake.overlay_animations[style]) {
            return $.fn.popmake.overlay_animations[style].apply(this, [duration, callback]);
        }
        $.error('Animation style ' + style + ' does not exist.');

        return this;
    };

    $.fn.popmake.methods.animate = function (style) {
        // Method calling logic
        if ($.fn.popmake.animations[style]) {
            return $.fn.popmake.animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Animation style ' + style + ' does not exist.');
        return this;
    };

    $.fn.popmake.animations = {
        none: function (callback) {
            PUM.getPopup(this)
                .popmake('animate_overlay', 'none', 0, function () {
                    // Fire user passed callback.
                    if (callback !== undefined) {
                        callback();
                        // TODO Test this new method. Then remove the above.
                        //callback.apply(this);
                    }
                });
            return this;
        },
        slide: function (callback) {
            var $popup = PUM.getPopup(this).show(0).css({opacity: 0}),
                $container = $popup.popmake('getContainer').show(0).css({opacity: 0}),
                settings = $popup.popmake('getSettings'),
                speed = settings.meta.display.animation_speed / 2,
                start = $popup.popmake('animation_origin', settings.meta.display.animation_origin);

            $container
                .position(start)
                .css({opacity: 1});

            $popup
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $container.popmake('reposition', function (position) {
                        $container.animate(position, speed, 'swing', function () {
                            // Fire user passed callback.
                            if (callback !== undefined) {
                                callback();
                                // TODO Test this new method. Then remove the above.
                                //callback.apply(this);
                            }
                        });
                    });
                });
            return this;
        },
        fade: function (callback) {
            var $popup = PUM.getPopup(this),
                $container = $popup.popmake('getContainer'),
                settings = $popup.popmake('getSettings'),
                speed = settings.meta.display.animation_speed / 2;

            $container
                .show(0)
                .css({opacity: 0});

            $popup.popmake('animate_overlay', 'fade', speed, function () {
                $container.animate({opacity: 1}, speed, 'swing', function () {
                    // Fire user passed callback.
                    if (callback !== undefined) {
                        callback();
                        // TODO Test this new method. Then remove the above.
                        //callback.apply(this);
                    }
                });
            });
            return this;
        },
        fadeAndSlide: function (callback) {
            var $popup = PUM.getPopup(this).show(0).css({opacity: 0}),
                $container = $popup.popmake('getContainer').show(0).css({opacity: 0}),
                settings = $popup.popmake('getSettings'),
                speed = settings.meta.display.animation_speed / 2,
                start = $popup.popmake('animation_origin', settings.meta.display.animation_origin);

            $container.position(start);

            $popup
                .hide()
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $container.popmake('reposition', function (position) {

                        position.opacity = 1;
                        $container.animate(position, speed, 'swing', function () {
                            // Fire user passed callback.
                            if (callback !== undefined) {
                                callback();
                                // TODO Test this new method. Then remove the above.
                                //callback.apply(this);
                            }
                        });

                    });
                });
            return this;
        },
        /**
         * TODO: Remove these and let import script replace them.
         * @deprecated
         * @returns {$.fn.popmake.animations}
         */
        grow: function (callback) {
            return $.fn.popmake.animations.fade.apply(this, arguments);
        },
        /**
         * @deprecated
         * @returns {$.fn.popmake.animations}
         */
        growAndSlide: function (callback) {
            return $.fn.popmake.animations.fadeAndSlide.apply(this, arguments);
        }
    };

    $.fn.popmake.overlay_animations = {
        none: function (duration, callback) {
            PUM.getPopup(this).show(duration, callback);
        },
        fade: function (duration, callback) {
            PUM.getPopup(this).fadeIn(duration, callback);
        },
        slide: function (duration, callback) {
            PUM.getPopup(this).slideDown(duration, callback);
        }
    };

}(jQuery, document));
/**
 * Defines the core $.popmake binds.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $(document)
    // Backward Compatibility
    // TODO: Add check for compatibility mode once available.
        .on('pumInit', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeInit');
        })


        /**
         * Fires the deprecated popmakeBeforeOpen event
         */
        .on('pumBeforeOpen', '.pum', function () {
            $(this).popmake('getContainer')
                .addClass('active')
                .trigger('popmakeBeforeOpen');
        })
        /**
         * Fires the deprecated popmakeAfterOpen event
         */
        .on('pumAfterOpen', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeAfterOpen');
        })


        /**
         * Fires the deprecated popmakeBeforeClose event
         */
        .on('pumBeforeClose', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeBeforeClose');
        })
        /**
         * Fires the deprecated popmakeAfterClose event
         */
        .on('pumAfterClose', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('active')
                .trigger('popmakeAfterClose');
        })


        /**
         * Fires the deprecated popmakeSetupClose event
         */
        .on('pumSetupClose', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeSetupClose');
        })


        /**
         * Removes the prevent open classes if they exist.
         */
        .on('pumOpenPrevented', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('preventOpen')
                .removeClass('active');
        })
        /**
         * Removes the prevent close classes if they exist.
         */
        .on('pumClosePrevented', '.pum', function () {
            $(this).popmake('getContainer')
                .removeClass('preventClose');
        })


        /**
         * Fires the deprecated popmakeBeforeReposition event
         */
        .on('pumBeforeReposition', '.pum', function () {
            $(this).popmake('getContainer').trigger('popmakeBeforeReposition');
        });


}(jQuery, document));
/**
 * Defines the core $.popmake callbacks.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.callbacks = {
        reposition_using: function (position) {
            $(this).css(position);
        }
    };

}(jQuery, document));
/**
 * Defines the core $.popmake.cookie functions.
 * Version 1.4
 *
 * Defines the pm_cookie & pm_remove_cookie global functions.
 */
var pm_cookie, pm_remove_cookie;
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.cookie = {
        defaults: {},
        raw: false,
        json: true,
        pluses: /\+/g,
        encode: function (s) {
            return $.fn.popmake.cookie.raw ? s : encodeURIComponent(s);
        },
        decode: function (s) {
            return $.fn.popmake.cookie.raw ? s : decodeURIComponent(s);
        },
        stringifyCookieValue: function (value) {
            return $.fn.popmake.cookie.encode($.fn.popmake.cookie.json ? JSON.stringify(value) : String(value));
        },
        parseCookieValue: function (s) {
            if (s.indexOf('"') === 0) {
                // This is a quoted cookie as according to RFC2068, unescape...
                s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
            }

            try {
                // Replace server-side written pluses with spaces.
                // If we can't decode the cookie, ignore it, it's unusable.
                // If we can't parse the cookie, ignore it, it's unusable.
                s = decodeURIComponent(s.replace($.fn.popmake.cookie.pluses, ' '));
                return $.fn.popmake.cookie.json ? JSON.parse(s) : s;
            } catch (ignore) {
            }
        },
        read: function (s, converter) {
            var value = $.fn.popmake.cookie.raw ? s : $.fn.popmake.cookie.parseCookieValue(s);
            return $.isFunction(converter) ? converter(value) : value;
        },
        process: function (key, value, expires, path) {
            var result = key ? undefined : {},
                t = new Date(),
                cookies = document.cookie ? document.cookie.split('; ') : [],
                parts,
                name,
                cookie,
                i,
                l;
            // Write

            if (value !== undefined && !$.isFunction(value)) {

                switch (typeof expires) {
                case 'number':
                    t.setTime(+t + expires * 864e+5);
                    expires = t;
                    break;
                case 'string':
                    t.setTime($.fn.popmake.utilities.strtotime("+" + expires) * 1000);
                    expires = t;
                    break;
                }

                document.cookie = [
                    $.fn.popmake.cookie.encode(key), '=', $.fn.popmake.cookie.stringifyCookieValue(value),
                    expires ? '; expires=' + expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                    path ? '; path=' + path : ''
                ].join('');
                return;
            }

            for (i = 0, l = cookies.length; i < l; i += 1) {
                parts = cookies[i].split('=');
                name = $.fn.popmake.cookie.decode(parts.shift());
                cookie = parts.join('=');

                if (key && key === name) {
                    // If second argument (value) is a function it's a converter...
                    result = $.fn.popmake.cookie.read(cookie, value);
                    break;
                }

                // Prevent storing a cookie that we couldn't decode.
                cookie = $.fn.popmake.cookie.read(cookie);
                if (!key && cookie !== undefined) {
                    result[name] = cookie;
                }
            }

            return result;
        },
        remove: function (key) {
            if ($.pm_cookie(key) === undefined) {
                return false;
            }
            $.pm_cookie(key, '', -1);
            return !$.pm_cookie(key);
        }
    };

    pm_cookie = $.pm_cookie = $.fn.popmake.cookie.process;
    pm_remove_cookie = $.pm_remove_cookie = $.fn.popmake.cookie.remove;

}(jQuery, document));
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.methods.addCookie = function (type) {
        // Method calling logic
        if ($.fn.popmake.cookies[type]) {
            return $.fn.popmake.cookies[type].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Cookie type ' + type + ' does not exist.');
        return this;
    };

    $.fn.popmake.methods.setCookie = function (settings) {
        $.pm_cookie(
            settings.name,
            true,
            settings.session ? null : settings.time,
            settings.path ? '/' : null
        );
    };

    $.fn.popmake.cookies = {
        on_popup_open: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumAfterOpen', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        on_popup_close: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumBeforeClose', function () {
                $popup.popmake('setCookie', settings);
            });
        },
        manual: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pumSetCookie', function () {
                $popup.popmake('setCookie', settings);
            });
        }
    };

    // Register All Cookies for a Popup
    $(document)
        .on('pumInit', '.pum', function () {
            var $popup = PUM.getPopup(this),
                settings = $popup.popmake('getSettings'),
                cookies = settings.cookies,
                cookie = null,
                i;

            if (cookies !== undefined && cookies.length) {
                for (i = 0; cookies.length > i; i += 1) {
                    cookie = cookies[i];
                    $popup.popmake('addCookie', cookie.event, cookie.settings);
                }
            }
        });

}(jQuery, document));
/**
 * Defines the core $.popmake defaults.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.defaults = {
        meta: {
            display: {
                stackable: 0,
                overlay_disabled: 0,
                size: 'medium',
                responsive_max_width: '',
                responsive_max_width_unit: '%',
                responsive_min_width: '',
                responsive_min_width_unit: '%',
                custom_width: '',
                custom_width_unit: '%',
                custom_height: '',
                custom_height_unit: 'em',
                custom_height_auto: 0,
                location: 'center top',
                position_top: 100,
                position_left: 0,
                position_bottom: 0,
                position_right: 0,
                position_fixed: 0,
                animation_type: 'fade',
                animation_speed: 350,
                animation_origin: 'center top'
            },
            close: {
                overlay_click: 0,
                esc_press: 0,
                f4_press: 0
            }
        },
        // TODO Remove these once extensions have all been updated.
        container: {
            active_class: 'active',
            attr: {
                class: "popmake"
            }
        },
        title: {
            attr: {
                class: "popmake-title"
            }
        },
        content: {
            attr: {
                class: "popmake-content"
            }
        },
        close: {
            close_speed: 0,
            attr: {
                class: "popmake-close"
            }
        },
        overlay: {
            attr: {
                id: "popmake-overlay",
                class: "popmake-overlay"
            }
        }
    };

}(jQuery, document));
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.methods.addTrigger = function (type) {
        // Method calling logic
        if ($.fn.popmake.triggers[type]) {
            return $.fn.popmake.triggers[type].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Trigger type ' + type + ' does not exist.');
        return this;
    };

    $.fn.popmake.methods.checkCookies = function (settings) {
        var i;

        if (settings.cookie === undefined || settings.cookie.name === undefined || settings.cookie.name === null) {
            return false;
        }

        switch (typeof settings.cookie.name) {
        case 'object':
        case 'array':
            for (i = 0; settings.cookie.name.length > i; i += 1) {
                if ($.pm_cookie(settings.cookie.name[i]) !== undefined) {
                    return true;
                }
            }
            break;
        case 'string':
            if ($.pm_cookie(settings.cookie.name) !== undefined) {
                return true;
            }
            break;
        }

        return false;
    };

    $.fn.popmake.triggers = {
        auto_open: function (settings) {
            var $popup = PUM.getPopup(this);

            // Set a delayed open.
            setTimeout(function () {

                // If the popup is already open return.
                if ($popup.hasClass('pum-open') || $popup.popmake('getContainer').hasClass('active')) {
                    return;
                }

                // If cookie exists return.
                if ($popup.popmake('checkCookies', settings)) {
                    return;
                }

                // Set the global last open trigger to the a text description of the trigger.
                $.fn.popmake.last_open_trigger = 'Auto Open - Delay: ' + settings.delay;

                // Open the popup.
                $popup.popmake('open');

            }, settings.delay);
        },
        click_open: function (settings) {
            var $popup = PUM.getPopup(this),
                popup_settings = $popup.popmake('getSettings'),
                trigger_selector = '.popmake-' + popup_settings.id + ', .popmake-' + decodeURIComponent(popup_settings.slug);

            if (settings.extra_selectors !== '') {
                trigger_selector += ', ' + settings.extra_selectors;
            }

            $(trigger_selector)
                .addClass('pum-trigger')
                .css({cursor: "pointer"});

            $(document)
                .on('click.pumTrigger', trigger_selector, function (e) {

                    // If trigger is inside of the popup that it opens, do nothing.
                    if ($popup.has(this).length > 0) {
                        return;
                    }

                    // If cookie exists return.
                    if ($popup.popmake('checkCookies', settings)) {
                        return;
                    }

                    // If trigger has the class do-default we don't prevent default actions.
                    if (!$(e.target).hasClass('do-default')) {
                        e.preventDefault();
                        e.stopPropagation();
                    }

                    // Set the global last open trigger to the clicked element.
                    $.fn.popmake.last_open_trigger = this;

                    // Open the popup.
                    $popup.popmake('open');

                });
        },
        admin_debug: function () {
            PUM.getPopup(this).popmake('open');
        }
    };

    // Register All Triggers for a Popup
    $(document)
        .on('pumInit', '.pum', function () {
            var $popup = PUM.getPopup(this),
                settings = $popup.popmake('getSettings'),
                triggers = settings.triggers,
                trigger = null,
                i;

            if (triggers !== undefined && triggers.length) {
                for (i = 0; triggers.length > i; i += 1) {
                    trigger = triggers[i];
                    $popup.popmake('addTrigger', trigger.type, trigger.settings);
                }
            }
        });

}(jQuery, document));
/**
 * Defines the core $.popmake.utilites methods.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.utilities = {
        convert_hex: function (hex, opacity) {
            hex = hex.replace('#', '');
            var r = parseInt(hex.substring(0, 2), 16),
                g = parseInt(hex.substring(2, 4), 16),
                b = parseInt(hex.substring(4, 6), 16);
            return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')';
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
                    callback.apply(this, arguments);
                    window.setTimeout(clear, threshold);
                    suppress = true;
                }
            };
        },
        getXPath: function (element) {
            var path = [],
                current,
                id,
                classes,
                tag,
                eq;

            $.each($(element).parents(), function (index, value) {
                current = $(value);
                id = current.attr("id") || '';
                classes = current.attr("class") || '';
                tag = current.get(0).tagName.toLowerCase();
                eq = current.parent().children(tag).index(current);
                if (tag === 'body') {
                    return false;
                }
                if (classes.length > 0) {
                    classes = classes.split(' ');
                    classes = classes[0];
                }
                path.push(tag + (id.length > 0 ? "#" + id : (classes.length > 0 ? "." + classes.split(' ').join('.') : ':eq(' + eq + ')')));
            });
            return path.reverse().join(' > ');
        },
        strtotime: function (text, now) {
            //  discuss at: http://phpjs.org/functions/strtotime/
            //     version: 1109.2016
            // original by: Caio Ariede (http://caioariede.com)
            // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
            // improved by: Caio Ariede (http://caioariede.com)
            // improved by: A. Matas Quezada (http://amatiasq.com)
            // improved by: preuter
            // improved by: Brett Zamir (http://brett-zamir.me)
            // improved by: Mirko Faber
            //    input by: David
            // bugfixed by: Wagner B. Soares
            // bugfixed by: Artur Tchernychev
            //        note: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
            //   example 1: strtotime('+1 day', 1129633200);
            //   returns 1: 1129719600
            //   example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
            //   returns 2: 1130425202
            //   example 3: strtotime('last month', 1129633200);
            //   returns 3: 1127041200
            //   example 4: strtotime('2009-05-04 08:30:00 GMT');
            //   returns 4: 1241425800
            var parsed, match, today, year, date, days, ranges, len, times, regex, i, fail = false;
            if (!text) {
                return fail;
            }
            // Unecessary spaces
            text = text.replace(/^\s+|\s+$/g, '')
                .replace(/\s{2,}/g, ' ')
                .replace(/[\t\r\n]/g, '')
                .toLowerCase();
            // in contrast to php, js Date.parse function interprets:
            // dates given as yyyy-mm-dd as in timezone: UTC,
            // dates with "." or "-" as MDY instead of DMY
            // dates with two-digit years differently
            // etc...etc...
            // ...therefore we manually parse lots of common date formats
            match = text.match(/^(\d{1,4})([\-\.\/\:])(\d{1,2})([\-\.\/\:])(\d{1,4})(?:\s(\d{1,2}):(\d{2})?:?(\d{2})?)?(?:\s([A-Z]+)?)?$/);
            if (match && match[2] === match[4]) {
                if (match[1] > 1901) {
                    switch (match[2]) {
                    case '-':
                        // YYYY-M-D
                        if (match[3] > 12 || match[5] > 31) {
                            return fail;
                        }
                        return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    case '.':
                        // YYYY.M.D is not parsed by strtotime()
                        return fail;
                    case '/':
                        // YYYY/M/D
                        if (match[3] > 12 || match[5] > 31) {
                            return fail;
                        }
                        return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    }
                } else if (match[5] > 1901) {
                    switch (match[2]) {
                    case '-':
                        // D-M-YYYY
                        if (match[3] > 12 || match[1] > 31) {
                            return fail;
                        }
                        return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    case '.':
                        // D.M.YYYY
                        if (match[3] > 12 || match[1] > 31) {
                            return fail;
                        }
                        return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    case '/':
                        // M/D/YYYY
                        if (match[1] > 12 || match[3] > 31) {
                            return fail;
                        }
                        return new Date(match[5], parseInt(match[1], 10) - 1, match[3],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    }
                } else {
                    switch (match[2]) {
                    case '-':
                        // YY-M-D
                        if (match[3] > 12 || match[5] > 31 || (match[1] < 70 && match[1] > 38)) {
                            return fail;
                        }
                        year = match[1] >= 0 && match[1] <= 38 ? +match[1] + 2000 : match[1];
                        return new Date(year, parseInt(match[3], 10) - 1, match[5],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    case '.':
                        // D.M.YY or H.MM.SS
                        if (match[5] >= 70) { // D.M.YY
                            if (match[3] > 12 || match[1] > 31) {
                                return fail;
                            }
                            return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
                                    match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                        }
                        if (match[5] < 60 && !match[6]) { // H.MM.SS
                            if (match[1] > 23 || match[3] > 59) {
                                return fail;
                            }
                            today = new Date();
                            return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                                    match[1] || 0, match[3] || 0, match[5] || 0, match[9] || 0) / 1000;
                        }
                        return fail; // invalid format, cannot be parsed
                    case '/':
                        // M/D/YY
                        if (match[1] > 12 || match[3] > 31 || (match[5] < 70 && match[5] > 38)) {
                            return fail;
                        }
                        year = match[5] >= 0 && match[5] <= 38 ? +match[5] + 2000 : match[5];
                        return new Date(year, parseInt(match[1], 10) - 1, match[3],
                                match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000;
                    case ':':
                        // HH:MM:SS
                        if (match[1] > 23 || match[3] > 59 || match[5] > 59) {
                            return fail;
                        }
                        today = new Date();
                        return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                                match[1] || 0, match[3] || 0, match[5] || 0) / 1000;
                    }
                }
            }
            // other formats and "now" should be parsed by Date.parse()
            if (text === 'now') {
                return now === null || isNaN(now) ? new Date()
                    .getTime() / 1000 || 0 : now || 0;
            }
            parsed = Date.parse(text);
            if (!isNaN(parsed)) {
                return parsed / 1000 || 0;
            }
            date = now ? new Date(now * 1000) : new Date();
            days = {
                'sun': 0,
                'mon': 1,
                'tue': 2,
                'wed': 3,
                'thu': 4,
                'fri': 5,
                'sat': 6
            };
            ranges = {
                'yea': 'FullYear',
                'mon': 'Month',
                'day': 'Date',
                'hou': 'Hours',
                'min': 'Minutes',
                'sec': 'Seconds'
            };

            function lastNext(type, range, modifier) {
                var diff, day = days[range];
                if (day !== undefined) {
                    diff = day - date.getDay();
                    if (diff === 0) {
                        diff = 7 * modifier;
                    } else if (diff > 0 && type === 'last') {
                        diff -= 7;
                    } else if (diff < 0 && type === 'next') {
                        diff += 7;
                    }
                    date.setDate(date.getDate() + diff);
                }
            }

            function process(val) {
                var splt = val.split(' '),
                    type = splt[0],
                    range = splt[1].substring(0, 3),
                    typeIsNumber = /\d+/.test(type),
                    ago = splt[2] === 'ago',
                    num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1);
                if (typeIsNumber) {
                    num *= parseInt(type, 10);
                }
                if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
                    return date['set' + ranges[range]](date['get' + ranges[range]]() + num);
                }
                if (range === 'wee') {
                    return date.setDate(date.getDate() + (num * 7));
                }
                if (type === 'next' || type === 'last') {
                    lastNext(type, range, num);
                } else if (!typeIsNumber) {
                    return false;
                }
                return true;
            }

            times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
                '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
                '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)';
            regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?';
            match = text.match(new RegExp(regex, 'gi'));
            if (!match) {
                return fail;
            }
            for (i = 0, len = match.length; i < len; i += 1) {
                if (!process(match[i])) {
                    return fail;
                }
            }
            // ECMAScript 5 only
            // if (!match.every(process))
            //    return false;
            return (date.getTime() / 1000);
        }
    };

    // Deprecated fix. utilies was renamed because of typo.
    $.fn.popmake.utilies = $.fn.popmake.utilities;

}(jQuery, document));
/**
 * Initialize Popup Maker.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";
    // Defines the current version.
    $.fn.popmake.version = 1.4;

    // Stores the last open popup.
    $.fn.popmake.last_open_popup = null;

    $(document).ready(function () {
        $('.popmake').popmake();
    });
}(jQuery));
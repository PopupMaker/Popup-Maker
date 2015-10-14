/**
 * Popup Maker v1.3.6
 */
var pm_cookie, pm_remove_cookie;
(function (jQuery) {
    "use strict";
    var isScrolling = false;
    jQuery(window)
        .on('scroll', function () {
            isScrolling = true;
        })
        .on('scrollstop', function () {
            isScrolling = false;
        });


    if (!jQuery.isFunction(jQuery.fn.on)) {
        jQuery.fn.on = function (types, sel, fn) {
            return this.delegate(sel, types, fn);
        };
        jQuery.fn.off = function (types, sel, fn) {
            return this.undelegate(sel, types, fn);
        };
    }


    jQuery.fn.popmake = function (method) {
        // Method calling logic
        if (jQuery.fn.popmake.methods[method]) {
            return jQuery.fn.popmake.methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        if (typeof method === 'object' || !method) {
            return jQuery.fn.popmake.methods.init.apply(this, arguments);
        }
        jQuery.error('Method ' + method + ' does not exist on jQuery.fn.popmake');
    };

    jQuery.fn.popmake.version = 1.3;

    jQuery.fn.popmake.last_open_popup = null;
    jQuery.fn.popmake.last_open_trigger = null;
    jQuery.fn.popmake.last_close_trigger = null;

    jQuery.fn.popmake.methods = {
        init: function (options) {
            return this.each(function () {
                var $this = jQuery(this),
                    settings = jQuery.extend(true, {}, jQuery.fn.popmake.defaults, $this.data('popmake'), options);

                if (!(settings.theme_id > 0)) {
                    settings.theme_id = popmake_default_theme;
                }

                if (!jQuery('#' + settings.overlay.attr.id).length) {
                    jQuery('<div>').attr(settings.overlay.attr).appendTo('body');
                }

                jQuery(window).on('resize', function () {
                    if ($this.hasClass('active')) {
                        jQuery.fn.popmake.utilities.throttle(setTimeout(function () {
                            $this.popmake('reposition');
                        }, 25), 500, false);
                    }
                });

                if (typeof popmake_powered_by === 'string' && popmake_powered_by !== '') {
                    jQuery('.popmake-content', $this).append(jQuery(popmake_powered_by));
                }

                $this
                    .data('popmake', settings)
                    .trigger('popmakeInit');
                return this;
            });
        },
        setup_close: function () {
            var $this = jQuery(this),
                settings = $this.data('popmake'),
                $overlay = jQuery('#popmake-overlay'),
                $close = jQuery('.popmake-close', $this);

            $close
                .off('click.popmake')
                .on("click.popmake", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    jQuery.fn.popmake.last_close_trigger = 'Close Button';
                    $this.popmake('close');
                });

            if (settings.meta.close.esc_press || settings.meta.close.f4_press) {
                jQuery(window)
                    .off('keyup.popmake')
                    .on('keyup.popmake', function (e) {
                        if (e.keyCode === 27 && settings.meta.close.esc_press) {
                            jQuery.fn.popmake.last_close_trigger = 'ESC Key';
                            $this.popmake('close');
                        }
                        if (e.keyCode === 115 && settings.meta.close.f4_press) {
                            jQuery.fn.popmake.last_close_trigger = 'F4 Key';
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

                        jQuery.fn.popmake.last_close_trigger = 'Overlay Click';
                        $this.popmake('close');

                    });
            }

            $this.trigger('popmakeSetupClose');
            return this;
        },
        open: function (callback) {
            var $this = jQuery(this),
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

            jQuery('#popmake-overlay')
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

                    $this.trigger('popmakeAfterOpen');
                    jQuery.fn.popmake.last_open_popup = $this;
                    if (callback !== undefined) {
                        callback();
                    }
                });
            return this;
        },
        close: function (callback) {
            return this.each(function () {
                var $this = jQuery(this),
                    $overlay = jQuery('#popmake-overlay'),
                    $close = jQuery('.popmake-close', $this),
                    settings = $this.data('popmake');

                $this.trigger('popmakeBeforeClose');

                if ($this.hasClass('preventClose')) {
                    $this.removeClass('preventClose');
                    return this;
                }

                $this
                    .fadeOut(settings.close.close_speed, function () {

                        if ($overlay.length && $overlay.is(":visible")) {
                            $overlay.fadeOut(settings.close.close_speed);
                        }

                        jQuery(window).off('keyup.popmake');
                        $overlay.off('click.popmake');
                        $close.off('click.popmake');

                        $this
                            .removeClass('active')
                            .trigger('popmakeAfterClose');

                        jQuery('iframe', $this).filter('[src*="youtube"],[src*="vimeo"]').each(function () {
                            var $iframe = jQuery(this),
                                src = $iframe.attr('src')
                                    // Remove autoplay so video doesn't start playing again.
                                    .replace('autoplay=1', '1=1');
                            $iframe.attr('src', '').attr('src', src);
                        });

                        jQuery('video', $this).each(function () {
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
            jQuery('.popmake.active').popmake('close');
            return this;
        },
        reposition: function (callback) {
            jQuery(this).trigger('popmakeBeforeReposition');
            var $this = jQuery(this),
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
                    my: reposition.my + " top" + (display.position_top !== 0 ? "+" + (jQuery('body').hasClass('admin-bar') ? parseInt(display.position_top, 10) + 32 : display.position_top) : ""),
                    at: reposition.at + " top"
                };
            }
            if (location.indexOf('bottom') >= 0) {
                reposition = {
                    my: reposition.my + " bottom" + (display.position_bottom !== 0 ? "-" + display.position_bottom : ""),
                    at: reposition.at + " bottom"
                };
            }


            reposition.my = jQuery.trim(reposition.my);
            reposition.at = jQuery.trim(reposition.at);
            reposition.of = window;
            reposition.collision = 'none';
            reposition.using = typeof callback === "function" ? callback : jQuery.fn.popmake.callbacks.reposition_using;

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
            jQuery(this).trigger('popmakeBeforeRetheme');
            var $this = jQuery(this),
                settings = $this.data('popmake'),
                $overlay = jQuery('#' + settings.overlay.attr.id),
                $container = $this,
                $title = jQuery('.' + settings.title.attr.class, $container),
                $content = jQuery('> .' + settings.content.attr.class, $container),
                $close = jQuery('> .' + settings.close.attr.class, $container),
                container_inset,
                close_inset;

            if (theme === undefined) {
                theme = jQuery.fn.popmake.themes[settings.theme_id];
                if (theme === undefined) {
                    theme = jQuery.fn.popmake.themes[1];
                }
            }

            container_inset = theme.container.boxshadow_inset === 'yes' ? 'inset ' : '';
            close_inset = theme.close.boxshadow_inset === 'yes' ? 'inset ' : '';

            $overlay.removeAttr('style').css({
                backgroundColor: jQuery.fn.popmake.utilities.convert_hex(theme.overlay.background_color, theme.overlay.background_opacity),
                zIndex: settings.meta.display.overlay_zindex || 998
            });
            $container.css({
                padding: theme.container.padding + 'px',
                backgroundColor: jQuery.fn.popmake.utilities.convert_hex(theme.container.background_color, theme.container.background_opacity),
                borderStyle: theme.container.border_style,
                borderColor: theme.container.border_color,
                borderWidth: theme.container.border_width + 'px',
                borderRadius: theme.container.border_radius + 'px',
                boxShadow: container_inset + theme.container.boxshadow_horizontal + 'px ' + theme.container.boxshadow_vertical + 'px ' + theme.container.boxshadow_blur + 'px ' + theme.container.boxshadow_spread + 'px ' + jQuery.fn.popmake.utilities.convert_hex(theme.container.boxshadow_color, theme.container.boxshadow_opacity),
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
                textShadow: theme.title.textshadow_horizontal + 'px ' + theme.title.textshadow_vertical + 'px ' + theme.title.textshadow_blur + 'px ' + jQuery.fn.popmake.utilities.convert_hex(theme.title.textshadow_color, theme.title.textshadow_opacity)
            });
            $content.css({
                color: theme.content.font_color,
                //fontSize: theme.content.font_size+'px',
                fontFamily: theme.content.font_family,
                fontWeight: theme.content.font_weight,
                fontStyle: theme.content.font_style
            });
            jQuery('p, label', $content).css({
                color: theme.content.font_color,
                //fontSize: theme.content.font_size+'px',
                fontFamily: theme.content.font_family
            });
            $close.html(theme.close.text).css({
                padding: theme.close.padding + 'px',
                height: theme.close.height + 'px',
                width: theme.close.width + 'px',
                backgroundColor: jQuery.fn.popmake.utilities.convert_hex(theme.close.background_color, theme.close.background_opacity),
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
                boxShadow: close_inset + theme.close.boxshadow_horizontal + 'px ' + theme.close.boxshadow_vertical + 'px ' + theme.close.boxshadow_blur + 'px ' + theme.close.boxshadow_spread + 'px ' + jQuery.fn.popmake.utilities.convert_hex(theme.close.boxshadow_color, theme.close.boxshadow_opacity),
                textShadow: theme.close.textshadow_horizontal + 'px ' + theme.close.textshadow_vertical + 'px ' + theme.close.textshadow_blur + 'px ' + jQuery.fn.popmake.utilities.convert_hex(theme.close.textshadow_color, theme.close.textshadow_opacity),
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
        animate_overlay: function (style, duration, callback) {
            // Method calling logic
            var $this = jQuery(this),
                settings = $this.data('popmake');
            if (settings.meta.display.overlay_disabled) {
                callback();
            } else {
                if (jQuery.fn.popmake.overlay_animations[style]) {
                    return jQuery.fn.popmake.overlay_animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
                }
                jQuery.error('Animation style ' + jQuery.fn.popmake.overlay_animations + ' does not exist.');
            }
            return this;
        },
        animate: function (style, callback) {
            // Method calling logic
            if (jQuery.fn.popmake.animations[style]) {
                return jQuery.fn.popmake.animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            jQuery.error('Animation style ' + jQuery.fn.popmake.animations + ' does not exist.');
            return this;
        },
        animation_origin: function (origin) {
            var $this = jQuery(this),
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
                start.my = jQuery.trim(start.my);
                start.at = jQuery.trim(start.at);
                break;
            }
            start.of = window;
            start.collision = 'none';
            return start;
        }
    };

    jQuery.fn.popmake.callbacks = {
        reposition_using: function (position) {
            jQuery(this).css(position);
        }
    };

    jQuery.fn.popmake.cookie = {
        defaults: {},
        raw: false,
        json: true,
        pluses: /\+/g,
        encode: function (s) {
            return jQuery.fn.popmake.cookie.raw ? s : encodeURIComponent(s);
        },
        decode: function (s) {
            return jQuery.fn.popmake.cookie.raw ? s : decodeURIComponent(s);
        },
        stringifyCookieValue: function (value) {
            return jQuery.fn.popmake.cookie.encode(jQuery.fn.popmake.cookie.json ? JSON.stringify(value) : String(value));
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
                s = decodeURIComponent(s.replace(jQuery.fn.popmake.cookie.pluses, ' '));
                return jQuery.fn.popmake.cookie.json ? JSON.parse(s) : s;
            } catch (ignore) {
            }
        },
        read: function (s, converter) {
            var value = jQuery.fn.popmake.cookie.raw ? s : jQuery.fn.popmake.cookie.parseCookieValue(s);
            return jQuery.isFunction(converter) ? converter(value) : value;
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

            if (value !== undefined && !jQuery.isFunction(value)) {

                switch (typeof expires) {
                case 'number':
                    t.setTime(+t + expires * 864e+5);
                    expires = t;
                    break;
                case 'string':
                    t.setTime(jQuery.fn.popmake.utilities.strtotime("+" + expires) * 1000);
                    expires = t;
                    break;
                }

                document.cookie = [
                    jQuery.fn.popmake.cookie.encode(key), '=', jQuery.fn.popmake.cookie.stringifyCookieValue(value),
                    expires ? '; expires=' + expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                    path ? '; path=' + path : ''
                ].join('');
                return;
            }

            for (i = 0, l = cookies.length; i < l; i += 1) {
                parts = cookies[i].split('=');
                name = jQuery.fn.popmake.cookie.decode(parts.shift());
                cookie = parts.join('=');

                if (key && key === name) {
                    // If second argument (value) is a function it's a converter...
                    result = jQuery.fn.popmake.cookie.read(cookie, value);
                    break;
                }

                // Prevent storing a cookie that we couldn't decode.
                cookie = jQuery.fn.popmake.cookie.read(cookie);
                if (!key && cookie !== undefined) {
                    result[name] = cookie;
                }
            }

            return result;
        },
        remove: function (key) {
            if (jQuery.pm_cookie(key) === undefined) {
                return false;
            }
            jQuery.pm_cookie(key, '', -1);
            return !jQuery.pm_cookie(key);
        }
    };

    pm_cookie = jQuery.pm_cookie = jQuery.fn.popmake.cookie.process;
    pm_remove_cookie = jQuery.pm_remove_cookie = jQuery.fn.popmake.cookie.remove;

    jQuery.fn.popmake.utilities = {
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

            jQuery.each(jQuery(element).parents(), function (index, value) {
                current = jQuery(value);
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
            // improved by: A. Matías Quezada (http://amatiasq.com)
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
    jQuery.fn.popmake.utilies = jQuery.fn.popmake.utilities;

    jQuery.fn.popmake.defaults = {
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

    jQuery.fn.popmake.overlay_animations = {
        none: function (duration, callback) {
            jQuery('#popmake-overlay').show(duration, callback);
        },
        fade: function (duration, callback) {
            jQuery('#popmake-overlay').fadeIn(duration, callback);
        },
        slide: function (duration, callback) {
            jQuery('#popmake-overlay').slideDown(duration, callback);
        }
    };

    jQuery.fn.popmake.animations = {
        none: function (callback) {
            var $this = jQuery(this);
            $this.popmake('animate_overlay', 'none', 0, function () {
                $this.css({display: 'block'});
                if (callback !== undefined) {
                    callback();
                }
            });
            return this;
        },
        slide: function (callback) {
            var $this = jQuery(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !isScrolling) {
                jQuery('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                jQuery('html').css('overflow-x', 'inherit');
                            }
                            if (callback !== undefined) {
                                callback();
                            }
                        });

                    });
                });
            return this;
        },
        fade: function (callback) {
            var $this = jQuery(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2;

            $this
                .popmake('animate_overlay', 'fade', speed, function () {

                    $this.animate({opacity: 1}, speed, 'swing', function () {
                        if (callback !== undefined) {
                            callback();
                        }
                    });

                });
            return this;
        },
        fadeAndSlide: function (callback) {
            var $this = jQuery(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !isScrolling) {
                jQuery('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        position.opacity = 1;
                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                jQuery('html').css('overflow-x', 'inherit');
                            }
                            if (callback !== undefined) {
                                callback();
                            }
                        });

                    });
                });
            return this;
        },
        grow: function (callback) {
            /*            var $this = jQuery(this).show(0).css({ opacity: 0 }),
             settings = $this.data('popmake'),
             speed = settings.meta.display.animation_speed / 2,
             origin = settings.meta.display.animation_origin,
             original_size = {height: $this.height(), width: $this.width()};

             if (origin === 'top' || origin === 'bottom') {
             origin = 'center ' + origin;
             }
             if (origin === 'left' || origin === 'right') {
             origin = origin + ' center';
             }

             $this.css({
             opacity: 1
             });

             $this.popmake('animate_overlay', 'fade', speed, function () {
             // Reposition with callback. position returns default positioning.
             $this.popmake('reposition', function (position) {

             position.height = original_size.height;
             position.width = original_size.width;
             $this.css({
             height: 0,
             width: 0
             }).animate(position, speed, 'swing', function () {
             if (callback !== undefined) {
             callback();
             }
             });

             });
             });
             return this;
             */
            var $this = jQuery(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !isScrolling) {
                jQuery('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                jQuery('html').css('overflow-x', 'inherit');
                            }
                            if (callback !== undefined) {
                                callback();
                            }
                        });

                    });
                });
            return this;

        },
        growAndSlide: function (callback) {
            var $this = jQuery(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !isScrolling) {
                jQuery('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                jQuery('html').css('overflow-x', 'inherit');
                            }
                            if (callback !== undefined) {
                                callback();
                            }
                        });

                    });
                });
            return this;
            /*
             var $this = jQuery(this).show(0).css({ opacity: 0 }),
             settings = $this.data('popmake'),
             speed = settings.meta.display.animation_speed / 2000,
             origin = settings.meta.display.animation_origin,
             start = $this.popmake('animation_origin', origin);

             if (!settings.meta.display.position_fixed && !isScrolling) {
             jQuery('html').css('overflow-x', 'hidden');
             }

             $this.position(start);

             TweenLite.to($this, 0, { scale: 0, opacity: 1, transformOrigin: '0 0' });

             $this.popmake('animate_overlay', 'fade', speed * 1000, function () {
             $this.popmake('reposition', function (position) {

             TweenLite.to($this, speed, jQuery.extend(position, {
             scale: 1,
             transformOrigin: '50% 50%',
             onComplete: function () {
             if (!settings.meta.display.position_fixed) {
             jQuery('html').css('overflow-x', 'inherit');
             }
             if (callback !== undefined) {
             callback();
             }
             }
             }));

             });
             });
             return this;
             */
        }
    };

    jQuery('.popmake').css({visibility: "visible"}).hide();

    jQuery(document).ready(function () {
        jQuery('.popmake')
            .popmake()
            .each(function () {
                var $this = jQuery(this),
                    settings = $this.data('popmake'),
                    click_open = settings.meta.click_open,
                    trigger_selector = '.popmake-' + settings.id + ', .popmake-' + settings.slug,
                    admin_debug = settings.meta.admin_debug,
                    auto_open = settings.meta.auto_open,
                    cookie_name = "popmake-auto-open-" + settings.id,
                    noCookieCheck;

                if (click_open !== undefined && click_open.extra_selectors !== '') {
                    trigger_selector += ', ' + click_open.extra_selectors;
                }

                jQuery(trigger_selector).css({cursor: "pointer"});
                jQuery(document).on('click.popmakeOpen', trigger_selector, function (event) {
                    if (!jQuery(event.target).hasClass('do-default')) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    jQuery.fn.popmake.last_open_trigger = this; //jQuery.fn.popmake.utilities.getXPath(this);
                    $this.popmake('open');
                });

                if (admin_debug !== undefined && admin_debug.enabled) {
                    $this.popmake('open');
                    return;
                }

                if (auto_open !== undefined && auto_open.enabled) {

                    if (auto_open.cookie_key !== undefined && auto_open.cookie_key !== '') {
                        cookie_name = cookie_name + "-" + auto_open.cookie_key;
                    }

                    noCookieCheck = function () {
                        return jQuery.pm_cookie(cookie_name) === undefined;
                    };

                    $this.on('popmakeSetCookie.auto-open', function () {
                        if (auto_open.cookie_time !== '' && noCookieCheck()) {
                            jQuery.pm_cookie(
                                cookie_name,
                                true,
                                auto_open.session_cookie ? null : auto_open.cookie_time,
                                auto_open.cookie_path
                            );
                        }
                    });

                    switch (auto_open.cookie_trigger) {
                    case "open":
                        $this.on('popmakeAfterOpen', function () {
                            $this.trigger('popmakeSetCookie');
                        });
                        break;
                    case "close":
                        $this.on('popmakeBeforeClose', function () {
                            $this.trigger('popmakeSetCookie');
                        });
                        break;
                    }

                    setTimeout(function () {
                        if (noCookieCheck()) {
                            if (!$this.hasClass('active')) {
                                jQuery.fn.popmake.last_open_trigger = 'Auto Open Popups ID-' + settings.id;
                                $this.popmake('open');
                            }
                        }
                    }, auto_open.delay);
                }
            });
    });
}(jQuery));
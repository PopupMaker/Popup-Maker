/**
 * Defines the core $.popmake function which will load the proper methods.
 * Version 1.4
 */
var PUM;
(function ($, document, undefined) {
    "use strict";

    window.pum_vars = window.pum_vars || {
        // TODO Add defaults.
        default_theme: '0',
        home_url: '/',
        version: 1.7,
        ajaxurl: '',
        restapi: false,
        rest_nonce: null,
        debug_mode: false,
        disable_tracking: true,
        message_position: 'top',
        core_sub_forms_enabled: true,
        popups: {}
    };

    window.pum_popups = window.pum_popups || {};

    // Backward compatibility fill.
    window.pum_vars.popups = window.pum_popups;

    function isInt(value) {
        return !isNaN(value) && parseInt(Number(value)) === parseInt(value) && !isNaN(parseInt(value, 10));
    }

    function Selector_Cache() {
        var elementCache = {};

        var get_from_cache = function (selector, $ctxt, reset) {

            if ('boolean' === typeof $ctxt) {
                reset = $ctxt;
                $ctxt = false;
            }
            var cacheKey = $ctxt ? $ctxt.selector + ' ' + selector : selector;

            if (undefined === elementCache[cacheKey] || reset) {
                elementCache[cacheKey] = $ctxt ? $ctxt.find(selector) : jQuery(selector);
            }

            return elementCache[cacheKey];
        };

        get_from_cache.elementCache = elementCache;
        return get_from_cache;
    }

    function string_to_ref(object, reference) {
        function arr_deref(o, ref, i) {
            return !ref ? o : (o[ref.slice(0, i ? -1 : ref.length)]);
        }

        function dot_deref(o, ref) {
            return !ref ? o : ref.split('[').reduce(arr_deref, o);
        }

        return reference.split('.').reduce(dot_deref, object);
    }

    PUM = {
        get: new Selector_Cache(),
        getPopup: function (el) {
            var $this;

            // Quick Shortcuts
            if (isInt(el)) {
                $this = PUM.get('#pum-' + el);
            } else if (el === 'current') {
                $this = PUM.get('.pum-overlay.pum-active:eq(0)', true);
            } else if (el === 'open') {
                $this = PUM.get('.pum-overlay.pum-active', true);
            } else if (el === 'closed') {
                $this = PUM.get('.pum-overlay:not(.pum-active)', true);
            } else if (el instanceof jQuery) {
                $this = el;
            } else {
                $this = $(el);
            }

            if ($this.hasClass('pum-overlay')) {
                return $this;
            }

            if ($this.hasClass('popmake')) {
                return $this.parents('.pum-overlay');
            }

            return $this.parents('.pum-overlay').length ? $this.parents('.pum-overlay') : $();
        },
        open: function (el, callback) {
            PUM.getPopup(el).popmake('open', callback);
        },
        close: function (el, callback) {
            PUM.getPopup(el).popmake('close', callback);
        },
        preventOpen: function (el) {
            PUM.getPopup(el).addClass('preventOpen');
        },
        getSettings: function (el) {
            var $popup = PUM.getPopup(el);

            return $popup.popmake('getSettings');
        },
        getSetting: function (el, key, _default) {
            var settings = PUM.getSettings(el),
                value = string_to_ref(settings, key);

            return typeof value !== 'undefined' ? value : (_default !== undefined ? _default : null);
        },
        checkConditions: function (el) {
            return PUM.getPopup(el).popmake('checkConditions');
        },
        getCookie: function (cookie_name) {
            return $.pm_cookie(cookie_name);
        },
        getJSONCookie: function (cookie_name) {
            return $.pm_cookie_json(cookie_name);
        },
        setCookie: function (el, settings) {
            var $popup = PUM.getPopup(el);

            $popup.popmake('setCookie', jQuery.extend({
                name: 'pum-' + PUM.getSetting(el, 'id'),
                expires: '+30 days'
            }, settings));
        },
        clearCookie: function (cookie_name, callback) {
            $.pm_remove_cookie(cookie_name);

            if (typeof callback === 'function') {
                callback();
            }
        },
        clearCookies: function (el, callback) {
            var $popup = PUM.getPopup(el),
                settings = $popup.popmake('getSettings'),
                cookies = settings.cookies,
                cookie = null,
                i;

            if (cookies !== undefined && cookies.length) {
                for (i = 0; cookies.length > i; i += 1) {
                    $.pm_remove_cookie(cookies[i].settings.name);
                }
            }

            if (typeof callback === 'function') {
                callback();
            }
        },
        getClickTriggerSelector: function (el, trigger_settings) {
            var $popup = PUM.getPopup(el),
                settings = PUM.getSettings(el),
                trigger_selectors = [
                    '.popmake-' + settings.id,
                    '.popmake-' + decodeURIComponent(settings.slug),
                    'a[href$="#popmake-' + settings.id + '"]'
                ];

            if (trigger_settings.extra_selectors && trigger_settings.extra_selectors !== '') {
                trigger_selectors.push(trigger_settings.extra_selectors);
            }

            trigger_selectors = pum.hooks.applyFilters('pum.trigger.click_open.selectors', trigger_selectors, trigger_settings, $popup);

            return trigger_selectors.join(', ');
        },
        disableClickTriggers: function (el, trigger_settings) {
            if (el === undefined) {
                // disable all triggers. Not available yet.
                return;
            }

            if (trigger_settings !== undefined) {
                var selector = PUM.getClickTriggerSelector(el, trigger_settings);
                $(selector).removeClass('pum-trigger');
                $(document).off('click.pumTrigger click.popmakeOpen', selector)
            } else {
                var triggers = PUM.getSetting(el, 'triggers', []);
                if (triggers.length) {
                    for (var i = 0; triggers.length > i; i++) {
                        // If this isn't an explicitly allowed click trigger type skip it.
                        if (pum.hooks.applyFilters('pum.disableClickTriggers.clickTriggerTypes', ['click_open']).indexOf(triggers[i].type) === -1) {
                            continue;
                        }

                        var selector = PUM.getClickTriggerSelector(el, triggers[i].settings);
                        $(selector).removeClass('pum-trigger');
                        $(document).off('click.pumTrigger click.popmakeOpen', selector)
                    }
                }
            }
        }

    };

    $.fn.popmake = function (method) {
        // Method calling logic
        if ($.fn.popmake.methods[method]) {
            $(document).trigger('pumMethodCall', arguments);
            return $.fn.popmake.methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        if (typeof method === 'object' || !method) {
            return $.fn.popmake.methods.init.apply(this, arguments);
        }
        if (window.console) {
            console.warn('Method ' + method + ' does not exist on $.fn.popmake');
        }
    };

    // Defines the core $.popmake methods.
    $.fn.popmake.methods = {
        init: function () {
            return this.each(function () {
                var $popup = PUM.getPopup(this),
                    settings = $popup.popmake('getSettings');

                if (settings.theme_id <= 0) {
                    settings.theme_id = pum_vars.default_theme;
                }

                // TODO Move this to be a single global $(window) function that looks at any open popup.
                if (settings.disable_reposition === undefined || !settings.disable_reposition) {
                    $(window).on('resize', function () {
                        if ($popup.hasClass('pum-active') || $popup.find('.popmake.active').length) {
                            $.fn.popmake.utilities.throttle(setTimeout(function () {
                                $popup.popmake('reposition');
                            }, 25), 500, false);
                        }
                    });
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
            return PUM.getPopup(this);
        },
        getContainer: function () {
            return PUM.getPopup(this).find('.pum-container');
        },
        getTitle: function () {
            return PUM.getPopup(this).find('.pum-title') || null;
        },
        getContent: function () {
            return PUM.getPopup(this).find('.pum-content') || null;
        },
        getClose: function () {
            return PUM.getPopup(this).find('.pum-content + .pum-close') || null;
        },
        getSettings: function () {
            var $popup = PUM.getPopup(this);
            return $.extend(true, {}, $.fn.popmake.defaults, $popup.data('popmake') || {}, typeof pum_popups === 'object' && typeof pum_popups[$popup.attr('id')] !== 'undefined' ? pum_popups[$popup.attr('id')] : {});
        },
        state: function (test) {
            var $popup = PUM.getPopup(this);

            if (undefined !== test) {
                switch (test) {
                case 'isOpen':
                    return $popup.hasClass('pum-open') || $popup.popmake('getContainer').hasClass('active');
                case 'isClosed':
                    return !$popup.hasClass('pum-open') && !$popup.popmake('getContainer').hasClass('active');
                }
            }
        },
        open: function (callback) {
            var $popup = PUM.getPopup(this),
                $container = $popup.popmake('getContainer'),
                $close = $popup.popmake('getClose'),
                settings = $popup.popmake('getSettings'),
                $html = $('html');

            $popup.trigger('pumBeforeOpen');

            /**
             * Allow for preventing popups from opening.
             */
            if ($popup.hasClass('preventOpen') || $container.hasClass('preventOpen')) {
                console.log('prevented');
                $popup
                    .removeClass('preventOpen')
                    .removeClass('pum-active')
                    .trigger('pumOpenPrevented');

                return this;
            }

            /**
             * If popup isn't stackable close all others.
             */
            if (!settings.stackable) {
                $popup.popmake('close_all');
            }

            $popup.addClass('pum-active');

            /**
             * Hide the close button if delay is active.
             */
            if (settings.close_button_delay > 0) {
                $close.fadeOut(0);
            }

            $html.addClass('pum-open');

            /**
             * Check for and disable the overlay.
             */
            if (settings.overlay_disabled) {
                $html.addClass('pum-open-overlay-disabled');
            } else {
                $html.addClass('pum-open-overlay');
            }

            /**
             * Set position fixed when active.
             */
            if (settings.position_fixed) {
                $html.addClass('pum-open-fixed');
            } else {
                $html.addClass('pum-open-scrollable');
            }

            $popup
                .popmake('setup_close')
                .popmake('reposition')
                .popmake('animate', settings.animation_type, function () {

                    /**
                     * Fade the close button in after specified delay.
                     */
                    if (settings.close_button_delay > 0) {
                        setTimeout(function () {
                            $close.fadeIn();
                        }, settings.close_button_delay);
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

            // Add For non built in close buttons and backward compatibility.
            $close = $close.add($('.popmake-close, .pum-close', $popup).not($close));

            // TODO: Move to a global $(document).on type bind. Possibly look for an inactive class to fail on.
            $close
                .off('click.pum')
                .on("click.pum", function (event) {
                    var $this = $(this),
                        do_default = $this.hasClass('pum-do-default') || ($this.data('do-default') !== undefined && $this.data('do-default'));

                    if (!do_default) {
                        event.preventDefault();
                    }

                    $.fn.popmake.last_close_trigger = 'Close Button';
                    $popup.popmake('close');
                });

            if (settings.close_on_esc_press || settings.close_on_f4_press) {
                // TODO: Move to a global $(document).on type bind. Possibly look for a class to succeed on.
                $(window)
                    .off('keyup.popmake')
                    .on('keyup.popmake', function (e) {
                        if (e.keyCode === 27 && settings.close_on_esc_press) {
                            $.fn.popmake.last_close_trigger = 'ESC Key';
                            $popup.popmake('close');
                        }
                        if (e.keyCode === 115 && settings.close_on_f4_press) {
                            $.fn.popmake.last_close_trigger = 'F4 Key';
                            $popup.popmake('close');
                        }
                    });
            }

            if (settings.close_on_overlay_click) {
                $popup.on('pumAfterOpen', function () {
                    $(document).on('click.pumCloseOverlay', function (e) {
                        var $target = $(e.target),
                            $container = $target.closest('.pum-container');

                        if (!$container.length) {
                            $.fn.popmake.last_close_trigger = 'Overlay Click';
                            $popup.popmake('close');
                        }
                    });
                });

                $popup.on('pumAfterClose', function () {
                    $(document).off('click.pumCloseOverlay');
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

                $close = $close.add($('.popmake-close, .pum-close', $popup).not($close));

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

                        /**
                         * Clear global event spaces.
                         */
                        $(window).off('keyup.popmake');
                        $popup.off('click.popmake');
                        $close.off('click.popmake');

                        // Only re-enable scrolling for the document when the last popup has closed.
                        if ($('.pum-active').length === 1) {
                            $('html')
                                .removeClass('pum-open')
                                .removeClass('pum-open-scrollable')
                                .removeClass('pum-open-overlay')
                                .removeClass('pum-open-overlay-disabled')
                                .removeClass('pum-open-fixed');
                        }

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
                location = settings.location,
                reposition = {
                    my: "",
                    at: "",
                    of: window,
                    collision: 'none',
                    using: typeof callback === "function" ? callback : $.fn.popmake.callbacks.reposition_using
                },
                opacity = {overlay: null, container: null},
                $last_trigger = null;

            try {
                $last_trigger = $($.fn.popmake.last_open_trigger);
            } catch (error) {
                $last_trigger = $();
            }

            if (settings.position_from_trigger && $last_trigger.length) {

                reposition.of = $last_trigger;

                if (location.indexOf('left') >= 0) {
                    reposition.my += " right";
                    reposition.at += " left" + (settings.position_left !== 0 ? "-" + settings.position_left : "");
                }
                if (location.indexOf('right') >= 0) {
                    reposition.my += " left";
                    reposition.at += " right" + (settings.position_right !== 0 ? "+" + settings.position_right : "");
                }
                if (location.indexOf('center') >= 0) {
                    reposition.my = location === 'center' ? "center" : reposition.my + " center";
                    reposition.at = location === 'center' ? "center" : reposition.at + " center";
                }
                if (location.indexOf('top') >= 0) {
                    reposition.my += " bottom";
                    reposition.at += " top" + (settings.position_top !== 0 ? "-" + settings.position_top : "");
                }
                if (location.indexOf('bottom') >= 0) {
                    reposition.my += " top";
                    reposition.at += " bottom" + (settings.position_bottom !== 0 ? "+" + settings.position_bottom : "");
                }
            } else {
                if (location.indexOf('left') >= 0) {
                    reposition.my += " left" + (settings.position_left !== 0 ? "+" + settings.position_left : "");
                    reposition.at += " left";
                }
                if (location.indexOf('right') >= 0) {
                    reposition.my += " right" + (settings.position_right !== 0 ? "-" + settings.position_right : "");
                    reposition.at += " right";
                }
                if (location.indexOf('center') >= 0) {
                    reposition.my = location === 'center' ? "center" : reposition.my + " center";
                    reposition.at = location === 'center' ? "center" : reposition.at + " center";
                }
                if (location.indexOf('top') >= 0) {
                    reposition.my += " top" + (settings.position_top !== 0 ? "+" + ($('body').hasClass('admin-bar') ? parseInt(settings.position_top, 10) + 32 : settings.position_top) : "");
                    reposition.at += " top";
                }
                if (location.indexOf('bottom') >= 0) {
                    reposition.my += " bottom" + (settings.position_bottom !== 0 ? "-" + settings.position_bottom : "");
                    reposition.at += " bottom";
                }
            }

            reposition.my = $.trim(reposition.my);
            reposition.at = $.trim(reposition.at);

            if ($popup.is(':hidden')) {
                opacity.overlay = $popup.css("opacity");
                $popup.css({opacity: 0}).show(0);
            }

            if ($container.is(':hidden')) {
                opacity.container = $container.css("opacity");
                $container.css({opacity: 0}).show(0);
            }

            if (settings.position_fixed) {
                $container.addClass('fixed');
            }

            if (settings.size === 'custom') {
                $container.css({
                    width: settings.custom_width,
                    height: settings.custom_height_auto ? 'auto' : settings.custom_height
                });
            } else {
                if (settings.size !== 'auto') {
                    $container
                        .addClass('responsive')
                        .css({
                            minWidth: settings.responsive_min_width !== '' ? settings.responsive_min_width : 'auto',
                            maxWidth: settings.responsive_max_width !== '' ? settings.responsive_max_width : 'auto'
                        });
                }
            }

            $popup.trigger('pumAfterReposition');

            // TODO: Remove the add class and migrate the trigger to the $popup with pum prefix.
            $container
                .addClass('custom-position')
                .position(reposition)
                .trigger('popmakeAfterReposition');

            if (opacity.overlay) {
                $popup.css({opacity: opacity.overlay}).hide(0);
            }
            if (opacity.container) {
                $container.css({opacity: opacity.container}).hide(0);
            }
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
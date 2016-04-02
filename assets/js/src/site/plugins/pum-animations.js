/**
 * Defines the core $.popmake animations.
 * Version 1.4.0
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
        $.error('Animation style ' + $.fn.popmake.overlay_animations + ' does not exist.');

        return this;
    };

    $.fn.popmake.methods.animate = function (style) {
        // Method calling logic
        if ($.fn.popmake.animations[style]) {
            return $.fn.popmake.animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Animation style ' + $.fn.popmake.animations + ' does not exist.');
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
        grow: function () {
            return $.fn.popmake.animations.fade.apply(this, Array.prototype.slice.call(arguments, 1));
        },
        /**
         * @deprecated
         * @returns {$.fn.popmake.animations}
         */
        growAndSlide: function () {
            return $.fn.popmake.animations.fadeAndSlide.apply(this, Array.prototype.slice.call(arguments, 1));
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
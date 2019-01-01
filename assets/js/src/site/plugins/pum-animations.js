/**
 * Defines the core $.popmake animations.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.methods.animate_overlay = function (style, duration, callback) {
        // Method calling logic
        var settings = PUM.getPopup(this).popmake('getSettings');

        if (settings.overlay_disabled) {
            return $.fn.popmake.overlay_animations.none.apply(this, [duration, callback]);
        }

        if ($.fn.popmake.overlay_animations[style]) {
            return $.fn.popmake.overlay_animations[style].apply(this, [duration, callback]);
        }

        if (window.console) {
            console.warn('Animation style ' + style + ' does not exist.');
        }
        return this;
    };

    $.fn.popmake.methods.animate = function (style) {
        // Method calling logic
        if ($.fn.popmake.animations[style]) {
            return $.fn.popmake.animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        if (window.console) {
            console.warn('Animation style ' + style + ' does not exist.');
        }
        return this;
    };

    $.fn.popmake.animations = {
        none: function (callback) {
            var $popup = PUM.getPopup(this);

            // Ensure the container is visible immediately.
            $popup.popmake('getContainer').show(0);

            $popup.popmake('animate_overlay', 'none', 0, function () {
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
            var $popup = PUM.getPopup(this).css({opacity: 0}).show(0),
                $container = $popup.popmake('getContainer').css({opacity: 0}).show(0),
                settings = $popup.popmake('getSettings'),
                speed = settings.animation_speed / 2,
                start = $popup.popmake('animation_origin', settings.animation_origin);

            $container
                .position(start)
                .css({opacity: 1});

            $popup.popmake('animate_overlay', 'fade', speed, function () {
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
            var $popup = PUM.getPopup(this).css({opacity: 0}).show(0),
                $container = $popup.popmake('getContainer').css({opacity: 0}).show(0),
                settings = $popup.popmake('getSettings'),
                speed = settings.animation_speed / 2;

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
            var $popup = PUM.getPopup(this).css({opacity: 0}).show(0),
                $container = $popup.popmake('getContainer').css({opacity: 0}).show(0),
                settings = $popup.popmake('getSettings'),
                speed = settings.animation_speed / 2,
                start = $popup.popmake('animation_origin', settings.animation_origin);

            $container.position(start);

            $popup
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
            PUM.getPopup(this).animate({opacity: 1}, duration, 'swing', callback);
        },
        slide: function (duration, callback) {
            PUM.getPopup(this).slideDown(duration, callback);
        }
    };

}(jQuery, document));
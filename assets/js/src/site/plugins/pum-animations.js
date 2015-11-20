/**
 * Defines the core $.popmake animations.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $.fn.popmake.methods.animate_overlay = function (style, duration, callback) {
        // Method calling logic
        var $this = $(this),
            settings = $this.data('popmake');
        if (settings.meta.display.overlay_disabled) {
            callback();
        } else {
            if ($.fn.popmake.overlay_animations[style]) {
                return $.fn.popmake.overlay_animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            $.error('Animation style ' + $.fn.popmake.overlay_animations + ' does not exist.');
        }
        return this;
    };

    $.fn.popmake.methods.animate = function (style, callback) {
        // Method calling logic
        if ($.fn.popmake.animations[style]) {
            return $.fn.popmake.animations[style].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Animation style ' + $.fn.popmake.animations + ' does not exist.');
        return this;
    };

    $.fn.popmake.animations = {
        none: function (callback) {
            var $this = $(this);
            $this.popmake('animate_overlay', 'none', 0, function () {
                $this.css({display: 'block'});
                if (callback !== undefined) {
                    callback();
                }
            });
            return this;
        },
        slide: function (callback) {
            var $this = $(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !$.fn.isScrolling()) {
                $('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                $('html').css('overflow-x', 'inherit');
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
            var $this = $(this).show(0).css({opacity: 0}),
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
            var $this = $(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !$.fn.isScrolling()) {
                $('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        position.opacity = 1;
                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                $('html').css('overflow-x', 'inherit');
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
            /*            var $this = $(this).show(0).css({ opacity: 0 }),
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
            var $this = $(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !$.fn.isScrolling()) {
                $('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                $('html').css('overflow-x', 'inherit');
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
            var $this = $(this).show(0).css({opacity: 0}),
                settings = $this.data('popmake'),
                speed = settings.meta.display.animation_speed / 2,
                start = $this.popmake('animation_origin', settings.meta.display.animation_origin);

            if (!settings.meta.display.position_fixed && !$.fn.isScrolling()) {
                $('html').css('overflow-x', 'hidden');
            }

            $this
                .position(start)
                .css({opacity: 1})
                .popmake('animate_overlay', 'fade', speed, function () {
                    $this.popmake('reposition', function (position) {

                        $this.animate(position, speed, 'swing', function () {
                            if (!settings.meta.display.position_fixed) {
                                $('html').css('overflow-x', 'inherit');
                            }
                            if (callback !== undefined) {
                                callback();
                            }
                        });

                    });
                });
            return this;
            /*
             var $this = $(this).show(0).css({ opacity: 0 }),
             settings = $this.data('popmake'),
             speed = settings.meta.display.animation_speed / 2000,
             origin = settings.meta.display.animation_origin,
             start = $this.popmake('animation_origin', origin);

             if (!settings.meta.display.position_fixed && !$.fn.isScrolling()) {
             $('html').css('overflow-x', 'hidden');
             }

             $this.position(start);

             TweenLite.to($this, 0, { scale: 0, opacity: 1, transformOrigin: '0 0' });

             $this.popmake('animate_overlay', 'fade', speed * 1000, function () {
             $this.popmake('reposition', function (position) {

             TweenLite.to($this, speed, $.extend(position, {
             scale: 1,
             transformOrigin: '50% 50%',
             onComplete: function () {
             if (!settings.meta.display.position_fixed) {
             $('html').css('overflow-x', 'inherit');
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

    $.fn.popmake.overlay_animations = {
        none: function (duration, callback) {
            $('#popmake-overlay').show(duration, callback);
        },
        fade: function (duration, callback) {
            $('#popmake-overlay').fadeIn(duration, callback);
        },
        slide: function (duration, callback) {
            $('#popmake-overlay').slideDown(duration, callback);
        }
    };

}(jQuery));
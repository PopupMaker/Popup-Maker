/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    'use strict';
    var rangesliders = {
        cloneables: {
            slider: $('<input type="range" class="pum-range-slider" />'),
            plus: $('<button type="button" class="pum-range-plus">+</button>'),
            minus: $('<button type="button" class="pum-range-minus">-</button>')
        },
        init: function () {
            $('.pum-field-rangeslider:not(.pum-rangeslider-initialized)').each(function () {
                var $this    = $(this).addClass('pum-rangeslider-initialized'),
                    $input   = $this.find('input.pum-range-manual'),
                    $slider  = rangesliders.cloneables.slider.clone(),
                    $plus    = rangesliders.cloneables.plus.clone(),
                    $minus   = rangesliders.cloneables.minus.clone(),
                    settings = {
                        force: $input.data('force-minmax'),
                        min: parseInt($input.attr('min'), 10) || 0,
                        max: parseInt($input.attr('max'), 10) || 100,
                        step: parseInt($input.attr('step'), 10) || 1,
                        value: parseInt($input.attr('value'), 10) || 0
                    };

                if (settings.force && settings.value > settings.max) {
                    settings.value = settings.max;
                    $input.val(settings.value);
                }

                $slider.prop({
                    min: settings.min || 0,
                    max: ( settings.force || (settings.max && settings.max > settings.value) ) ? settings.max : settings.value *
                        1.5,
                    step: settings.step || settings.value * 1.5 / 100,
                    value: settings.value
                }).on('change input', function () {
                    $input.trigger('input');
                });

                $input.next().after($minus, $plus);
                $input.before($slider);

            });
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.rangesliders = rangesliders;

    $(document)
        .on('pum_init', PUM_Admin.rangesliders.init)
        /**
         * Updates the input field when the slider is used.
         */
        .on('input', '.pum-field-rangeslider.pum-rangeslider-initialized .pum-range-slider', function () {
            var $slider = $(this);
            $slider.siblings('.pum-range-manual').val($slider.val());
        })
        /**
         * Update sliders value, min, & max when manual entry is detected.
         */
        .on('change', '.pum-range-manual', function () {
            var $input  = $(this),
                max     = parseInt($input.prop('max'), 0),
                min     = parseInt($input.prop('min'), 0),
                step    = parseInt($input.prop('step'), 0),
                force   = $input.data('force-minmax'),
                value   = parseInt($input.val(), 0),
                $slider = $input.prev();

            if (isNaN(value)) {
                value = $slider.val();
            }

            if (force && value > max) {
                value = max;
            } else if (force && value < min) {
                value = min;
            }

            $input.val(value).trigger('input');

            $slider.prop({
                'max': force || (max && max > value) ? max : value * 1.5,
                'step': step || value * 1.5 / 100,
                'value': value
            });
        })
        .on('click', '.pum-range-plus', function (event) {
            var $input  = $(this).siblings('.pum-range-manual'),
                max     = parseInt($input.prop('max'), 0),
                step    = parseInt($input.prop('step'), 0),
                force   = $input.data('force-minmax'),
                value   = parseInt($input.val(), 0),
                $slider = $input.prev();

            event.preventDefault();

            value += step;

            if (isNaN(value)) {
                value = $slider.val();
            }

            if (force && value > max) {
                value = max;
            }

            $input.val(value).trigger('input');
            $slider.val(value);
        })
        .on('click', '.pum-range-minus', function (event) {
            var $input  = $(this).siblings('.pum-range-manual'),
                min     = parseInt($input.prop('min'), 0),
                step    = parseInt($input.prop('step'), 0),
                force   = $input.data('force-minmax'),
                value   = parseInt($input.val(), 0),
                $slider = $input.prev();

            event.preventDefault();

            value -= step;

            if (isNaN(value)) {
                value = $slider.val();
            }

            if (force && value < min) {
                value = min;
            }

            $input.val(value).trigger('input');
            $slider.val(value);
        });

}(jQuery));
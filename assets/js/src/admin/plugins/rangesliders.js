var PUMRangeSLiders;
(function ($, document, undefined) {
    "use strict";
    PUMRangeSLiders = {
        init: function () {
            var input,
                $input,
                $slider,
                $plus,
                $minus,
                slider = $('<input type="range"/>'),
                plus = $('<button type="button" class="popmake-range-plus">+</button>'),
                minus = $('<button type="button" class="popmake-range-minus">-</button>');

            $('.popmake-range-manual').filter(':not(.initialized)').each(function () {
                var $this = $(this).addClass('initialized'),
                    force = $this.data('force-minmax'),
                    min = parseInt($this.prop('min'), 0),
                    max = parseInt($this.prop('max'), 0),
                    step = parseInt($this.prop('step'), 0),
                    value = parseInt($this.val(), 0);

                $slider = slider.clone();
                $plus = plus.clone();
                $minus = minus.clone();

                if (force && value > max) {
                    value = max;
                    $this.val(value);
                }

                $slider
                    .prop({
                        min: min || 0,
                        max: ( force || (max && max > value) ) ? max : value * 1.5,
                        step: step || value * 1.5 / 100,
                        value: value
                    })
                    .on('change input', function () {
                        $this.trigger('input');
                    });
                $this.next().after($minus, $plus);
                $this.before($slider);

                input = document.createElement('input');
                input.setAttribute('type', 'range');
                if (input.type === 'text') {
                    $('input[type=range]').each(function (index, input) {
                        $input = $(input);
                        $slider = $('<div />').slider({
                            min: parseInt($input.attr('min'), 10) || 0,
                            max: parseInt($input.attr('max'), 10) || 100,
                            value: parseInt($input.attr('value'), 10) || 0,
                            step: parseInt($input.attr('step'), 10) || 1,
                            slide: function (event, ui) {
                                $(this).prev('input').val(ui.value);
                            }
                        });
                        $input.after($slider).hide();
                    });
                }
            });

        }
    };

    $(document)
        .on('pum_init', PUMRangeSLiders.init)
        .on('input', 'input[type="range"]', function () {
            var $this = $(this);
            $this.siblings('.popmake-range-manual').val($this.val());
        })
        .on('change', '.popmake-range-manual', function () {
            var $this = $(this),
                max = parseInt($this.prop('max'), 0),
                step = parseInt($this.prop('step'), 0),
                force = $this.data('force-minmax'),
                value = parseInt($this.val(), 0),
                $slider = $this.prev();

            if (force && value > max) {
                value = max;
                $this.val(value);
            }

            $slider.prop({
                'max': force || (max && max > value) ? max : value * 1.5,
                'step': step || value * 1.5 / 100,
                'value': value
            });

        })
        .on('click', '.popmake-range-plus', function (e) {
            var $this = $(this).siblings('.popmake-range-manual'),
                step = parseInt($this.prop('step'), 0),
                value = parseInt($this.val(), 0),
                val = value + step,
                $slider = $this.prev();

            e.preventDefault();

            $this.val(val).trigger('input');
            $slider.val(val);
        })
        .on('click', '.popmake-range-minus', function (e) {
            var $this = $(this).siblings('.popmake-range-manual'),
                step = parseInt($this.prop('step'), 0),
                value = parseInt($this.val(), 0),
                val = value - step,
                $slider = $this.prev();

            e.preventDefault();

            $this.val(val).trigger('input');
            $slider.val(val);
        });

}(jQuery, document));
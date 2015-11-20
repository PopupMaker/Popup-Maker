(function ($) {
    "use strict";

    $.fn.popmake.last_open_trigger = null;
    $.fn.popmake.last_close_trigger = null;

    $.fn.popmake.methods.add_trigger = function (type, settings) {
        // Method calling logic
        if ($.fn.popmake.triggers[type]) {
            return $.fn.popmake.triggers[type].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        $.error('Trigger type ' + $.fn.popmake.triggers + ' does not exist.');
        return this;
    };


    $.fn.popmake.triggers = {
        auto_open: function (settings) {
            var $popup = $(this);

            // Set a delayed open.
            setTimeout(function () {

                // If the popup is already open return.
                if ($popup.hasClass('active') || $popup.hasClass('pum-open')) {
                    return;
                }

                // Set the global last open trigger to the a text description of the trigger.
                $.fn.popmake.last_open_trigger = 'Auto Open - Delay: ' + settings.delay;

                // Open the popup.
                $popup.popmake('open');

            }, settings.delay);
        },
        click_open: function (settings) {
            var $popup = $(this),
                popup_settings = $popup.data('popmake'),
                trigger_selector = '.popmake-' + popup_settings.id + ', .popmake-' + popup_settings.slug;

            if (settings.extra_selectors !== '') {
                trigger_selector += ', ' + settings.extra_selectors;
            }

            $(trigger_selector)
                .addClass('pum-trigger')
                .css({cursor: "pointer"});

            $(document)
                .on('click.pumTrigger', trigger_selector, function (e) {

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
            $(this).popmake('open');
        }
    };

    // Register All Triggers for a Popup
    $(document)
        .on('pumInit', '.popmake', function (e) {
            var $popup = $(this),
                settings = $popup.data('popmake'),
                triggers = settings.triggers,
                trigger = null;

            for (var i = 0; triggers.length > i; i++) {
                trigger = triggers[i];
                $popup.popmake('add_trigger', trigger.type, trigger.settings);
            }
        });

}(jQuery));
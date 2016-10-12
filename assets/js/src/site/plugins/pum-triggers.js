(function ($, document, undefined) {
    "use strict";

    $.extend($.fn.popmake.methods, {
        addTrigger: function (type) {
            // Method calling logic
            if ($.fn.popmake.triggers[type]) {
                return $.fn.popmake.triggers[type].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            if (window.console) {
                console.warn('Trigger type ' + type + ' does not exist.');
            }
            return this;
        }
    });

    $.fn.popmake.triggers = {
        auto_open: function (settings) {
            var $popup = PUM.getPopup(this);

            // Set a delayed open.
            setTimeout(function () {

                // If the popup is already open return.
                if ($popup.hasClass('pum-open') || $popup.popmake('getContainer').hasClass('active')) {
                    return;
                }

                // If cookie exists or conditions fail return.
                if ($popup.popmake('checkCookies', settings) || !$popup.popmake('checkConditions')) {
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
                trigger_selectors = [
                    '.popmake-' + popup_settings.id,
                    '.popmake-' + decodeURIComponent(popup_settings.slug),
                    'a[href$="#popmake-' + popup_settings.id + '"]'
                ],
                trigger_selector;


            if (settings.extra_selectors && settings.extra_selectors !== '') {
                trigger_selectors.push(settings.extra_selectors);
            }

            trigger_selectors = pum.hooks.applyFilters('pum.trigger.click_open.selectors', trigger_selectors, settings, $popup);

            trigger_selector = trigger_selectors.join(', ');

            $(trigger_selector)
                .addClass('pum-trigger')
                .css({cursor: "pointer"});

            $(document)
                .on('click.pumTrigger', trigger_selector, function (event) {
                    var $this = $(this),
                        do_default = settings.do_default;

                    // If trigger is inside of the popup that it opens, do nothing.
                    if ($popup.has($this).length > 0) {
                        return;
                    }

                    // If cookie exists or conditions fail return.
                    if ($popup.popmake('checkCookies', settings) || !$popup.popmake('checkConditions')) {
                        return;
                    }

                    if ($this.data('do-default')) {
                        do_default = $this.data('do-default');
                    } else if ($this.hasClass('do-default')) {
                        do_default = true;
                    }

                    // If trigger has the class do-default we don't prevent default actions.
                    if (!pum.hooks.applyFilters('pum.trigger.click_open.do_default', do_default, settings, $popup)) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    // Set the global last open trigger to the clicked element.
                    $.fn.popmake.last_open_trigger = $this;

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
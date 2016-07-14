(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.methods.addTrigger = function (type) {
        // Method calling logic
        if ($.fn.popmake.triggers[type]) {
            return $.fn.popmake.triggers[type].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        if (window.console) {
            console.warn('Trigger type ' + type + ' does not exist.');
        }
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
                trigger_selectors = [
                    '.popmake-' + popup_settings.id,
                    '.popmake-' + decodeURIComponent(popup_settings.slug),
                    'a[href="#popmake-' + popup_settings.id + '"]'
                ],
                trigger_selector;


            if (settings.extra_selectors !== '') {
                trigger_selectors.push(settings.extra_selectors);
            }

            trigger_selector = trigger_selectors.join(', ');

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
                    if (!settings.do_default && !$(e.target).hasClass('do-default')) {
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
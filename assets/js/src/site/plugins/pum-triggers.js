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
                if ($popup.popmake('state', 'isOpen')) {
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

            $(document).on('click.pumTrigger', trigger_selector, function (event) {
                var $trigger = $(this),
                    do_default = settings.do_default || false;

                // If trigger is inside of the popup that it opens, do nothing.
                if ($popup.has($trigger).length > 0) {
                    return;
                }

                // If the popup is already open return.
                if ($popup.popmake('state', 'isOpen')) {
                    return;
                }

                // If cookie exists or conditions fail return.
                if ($popup.popmake('checkCookies', settings) || !$popup.popmake('checkConditions')) {
                    return;
                }

                if ($trigger.data('do-default')) {
                    do_default = $trigger.data('do-default');
                } else if ($trigger.hasClass('do-default') || $trigger.hasClass('popmake-do-default') || $trigger.hasClass('pum-do-default')) {
                    do_default = true;
                }

                // If trigger has the class do-default we don't prevent default actions.
                if (!event.ctrlKey && !pum.hooks.applyFilters('pum.trigger.click_open.do_default', do_default, $popup, $trigger)) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                // Set the global last open trigger to the clicked element.
                $.fn.popmake.last_open_trigger = $trigger;

                // Open the popup.
                $popup.popmake('open');
            });
        },
		form_submission: function (settings) {
			var $popup = PUM.getPopup(this);

			settings = $.extend({
				form: '',
				formInstanceId: '',
				delay: 0
			}, settings);

			var onSuccess = function () {
				setTimeout(function () {
					// If the popup is already open return.
					if ($popup.popmake('state', 'isOpen')) {
						return;
					}

					// If cookie exists or conditions fail return.
					if ($popup.popmake('checkCookies', settings) || !$popup.popmake('checkConditions')) {
						return;
					}

					// Set the global last open trigger to the a text description of the trigger.
					$.fn.popmake.last_open_trigger = 'Form Submission';

					// Open the popup.
					$popup.popmake('open');
				}, settings.delay);
			};

			// Listen for integrated form submissions.
			PUM.hooks.addAction('pum.integration.form.success', function (form, args) {
				if (!settings.form.length) {
					return;
				}

				var lookingFor = settings.form;
				var instanceId = '' === settings.formInstanceId ? settings.formInstanceId : false;
				// Check if the submitted form matches trigger requirements.
				var checks = [
					// Any supported form.
					lookingFor === 'any',

					// Any provider form. ex. `ninjaforms_any`
					lookingFor === args.formProvider + '_any',

					// Specific provider form with or without instance ID. ex. `ninjaforms_1` or `ninjaforms_1_*`
					// Only run this test if not checking for a specific instanceId.
					!instanceId && new RegExp('^' + lookingFor + '(_[\d]*)?').test(args.formKey),

					// Specific provider form with specific instance ID. ex `ninjaforms_1_1` or `calderaforms_jbakrhwkhg_1`
					// Only run this test if we are checking for specific instanceId.
					!!instanceId && lookingFor + '_' + instanceId === args.formKey
				];

				// If any check is true, trigger the popup.
				if (-1 !== checks.indexOf(true)) {
					onSuccess();
				}
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
                triggers = settings.triggers || [],
                trigger = null,
                i;

            if (triggers.length) {
                for (i = 0; triggers.length > i; i += 1) {
                    trigger = triggers[i];
                    $popup.popmake('addTrigger', trigger.type, trigger.settings);
                }
            }
        });

}(jQuery, document));

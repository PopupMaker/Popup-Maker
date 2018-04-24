/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var gFormSettings = {},
        pumNFController = false;

    debugger;

    function initialize_nf_support() {
        /** Ninja Forms Support */
        if (typeof Marionette !== 'undefined' && typeof nfRadio !== 'undefined') {
            pumNFController = Marionette.Object.extend({
                initialize: function () {
                    this.listenTo(nfRadio.channel('forms'), 'submit:response', this.popupMaker)
                },
                popupMaker: function (response, textStatus, jqXHR, formID) {
                    var $form = $('#nf-form-' + formID + '-cont'),
                        settings = {};

                    if (response.errors.length) {
                        return;
                    }

                    if ('undefined' !== typeof response.data.actions) {
                        settings.openpopup = 'undefined' !== typeof response.data.actions.openpopup;
                        settings.openpopup_id = settings.openpopup ? parseInt(response.data.actions.openpopup) : 0;
                        settings.closepopup = 'undefined' !== typeof response.data.actions.closepopup;
                        settings.closedelay = settings.closepopup ? parseInt(response.data.actions.closepopup) : 0;
                        if (settings.closepopup && response.data.actions.closedelay) {
                            settings.closedelay = parseInt(response.data.actions.closedelay);
                        }
                    }

                    window.PUM.forms.success($form, settings);
                }
            });
        }
    }


    $(document)
        .ready(function () {
            /** Ninja Forms Support */
            if (pumNFController === false) {
                initialize_nf_support();
            }

            if (pumNFController !== false) {
                new pumNFController();
            }

            /** Gravity Forms Support */
            $('.gform_wrapper > form').each(function () {
                var $form = $(this),
                    form_id = $form.attr('id').replace('gform_', ''),
                    $settings = $form.find('input.gforms-pum'),
                    settings = $settings.length ? JSON.parse($settings.val()) : false;

                if (!settings || typeof settings !== 'object') {
                    return;
                }

                if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
                    settings['closedelay'] = settings.closedelay / 1000;
                }

                gFormSettings[form_id] = settings;
            });
        })
        /** Gravity Forms Support */
        .on('gform_confirmation_loaded', function (event, form_id) {
            var $form = $('#gform_confirmation_wrapper_' + form_id + ',#gforms_confirmation_message_' + form_id),
                settings = gFormSettings[form_id] || false;

            window.PUM.forms.success($form, settings);
        })
        /** Contact Form 7 Support */
        .on('wpcf7:mailsent', '.wpcf7', function (event) {
            var $form = $(event.target),
                $settings = $form.find('input.wpcf7-pum'),
                settings = $settings.length ? JSON.parse($settings.val()) : false;

            if (typeof settings === 'object' && settings.closedelay !== undefined && settings.closedelay.toString().length >= 3) {
                settings['closedelay'] = settings.closedelay / 1000;
            }

            window.PUM.forms.success($form, settings);
        });

}(jQuery));
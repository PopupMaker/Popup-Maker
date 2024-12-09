/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    'use strict';

    if (pum_vars && pum_vars.core_sub_forms_enabled !== undefined && !pum_vars.core_sub_forms_enabled) {
        return;
    }

    window.PUM = window.PUM || {};
    window.PUM.newsletter = window.PUM.newsletter || {};

    $.extend(window.PUM.newsletter, {
        form: $.extend({}, window.PUM.forms.form, {
            submit: function (event) {
                var $form = $(this),
                    values = $form.pumSerializeObject();

                event.preventDefault();
                event.stopPropagation();

                window.PUM.newsletter.form.beforeAjax($form);

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: pum_vars.ajaxurl,
                    data: {
                        action: 'pum_sub_form',
                        values: values
                    }
                })
                    .always(function () {
                        window.PUM.newsletter.form.afterAjax($form);
                    })
                    .done(function (response) {
                        window.PUM.newsletter.form.responseHandler($form, response);
                    })
                    .error(function (jqXHR, textStatus, errorThrown) {
                        console.log('Error: type of ' + textStatus + ' with message of ' + errorThrown);
                    });
            }

        })
    });

    $(document)
        .on('submit', 'form.pum-sub-form', window.PUM.newsletter.form.submit)
        .on('success', 'form.pum-sub-form', function (event, data) {
            var $form = $( event.target ),
                settings = $form.data( 'settings' ) || {},
                values = $form.pumSerializeObject(),
                popup = PUM.getPopup($form),
                formId = PUM.getSetting(popup, 'id'),
                formInstanceId = $( 'form.pum-sub-form', popup).index( $form ) + 1;

            // All the magic happens here.
            window.PUM.integrations.formSubmission( $form, {
                formProvider: 'pumsubform',
                formId: formId,
                formInstanceId: formInstanceId,
                extras: {
                    data: data,
                    values: values,
                    settings: settings
                }
            } );

            $form
                .trigger('pumNewsletterSuccess', [data])
                .addClass('pum-newsletter-success');

            $form[0].reset();

            window.PUM.hooks.doAction('pum-sub-form.success', data, $form);

            if (typeof settings.redirect === 'string') {
                if (settings.redirect !== '') {
                    settings.redirect = atob(settings.redirect);
                }
            }

            window.PUM.forms.success($form, settings);
        })
        .on('error', 'form.pum-sub-form', function (event, data) {
            var $form = $(event.target);

            $form.trigger('pumNewsletterError', [data]);

            window.PUM.hooks.doAction('pum-sub-form.errors', data, $form);
        });

}(jQuery));

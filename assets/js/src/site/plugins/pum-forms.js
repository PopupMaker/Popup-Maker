/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var defaults = {
        openpopup: false,
        openpopup_id: 0,
        closepopup: false,
        closedelay: 0,
        redirect_enabled: false,
        redirect: ''
    };

    window.PUM = window.PUM || {};
    window.PUM.forms = window.PUM.forms || {};

    $.extend(window.PUM.forms, {
        form: {
            validation: {
                errors: []
            },
            responseHandler: function ($form, response) {
                var data = response.data;

                if (response.success) {
                    /**
                     * If there are no errors process the successful submission.
                     */
                    window.PUM.forms.form.success($form, data);
                } else {
                    /**
                     * Process any errors
                     */
                    window.PUM.forms.form.errors($form, data);
                }
            },
            display_errors: function ($form, errors) {
                window.PUM.forms.messages.add($form, errors || this.validation.errors, 'error');
            },
            beforeAjax: function ($form) {
                var $btn = $form.find('[type="submit"]'),
                    $loading = $btn.find('.pum-form__loader');

                window.PUM.forms.messages.clear_all($form);

                if (!$loading.length) {
                    $loading = $('<span class="pum-form__loader"></span>');
                    if ($btn.attr('value') !== '') {
                        $loading.insertAfter($btn);
                    } else {
                        $btn.append($loading);
                    }
                }

                $btn.prop('disabled', true);
                $loading.show();

                $form
                    .addClass('pum-form--loading')
                    .removeClass('pum-form--errors');
            },
            afterAjax: function ($form) {
                var $btn = $form.find('[type="submit"]'),
                    $loading = $btn.find('.pum-form__loader');

                $btn.prop('disabled', false);
                $loading.hide();

                $form.removeClass('pum-form--loading');
            },
            success: function ($form, data) {
                if (data.message !== undefined && data.message !== '') {
                    window.PUM.forms.messages.add($form, [{message: data.message}]);
                }

                $form.trigger('success', [data]);

                if (!$form.data('noredirect') && $form.data('redirect_enabled') !== undefined && data.redirect) {
                    if (data.redirect !== '') {
                        window.location = data.redirect;
                    } else {
                        window.location.reload(true);
                    }
                }
            },
            errors: function ($form, data) {
                if (data.errors !== undefined && data.errors.length) {
                    console.log(data.errors);

                    window.PUM.forms.form.display_errors($form, data.errors);

                    window.PUM.forms.messages.scroll_to_first($form);

                    $form
                        .addClass('pum-form--errors')
                        .trigger('errors', [data]);
                }
            },
            submit: function (event) {
                var $form = $(this),
                    values = $form.pumSerializeObject();

                event.preventDefault();
                event.stopPropagation();

                window.PUM.forms.form.beforeAjax($form);

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: pum_vars.ajaxurl,
                    data: {
                        action: 'pum_form',
                        values: values
                    }
                })
                    .always(function () {
                        window.PUM.forms.form.afterAjax($form);
                    })
                    .done(function (response) {
                        window.PUM.forms.form.responseHandler($form, response);
                    })
                    .error(function (jqXHR, textStatus, errorThrown) {
                        console.log('Error: type of ' + textStatus + ' with message of ' + errorThrown);
                    });
            }
        },
        /**
         * Functions to manage form messages.
         */
        messages: {
            /**
             * Process & add messages to a form.
             *
             * @param $form
             * @param messages
             * @param type
             */
            add: function ($form, messages, type) {
                var $messages = $form.find('.pum-form__messages'),
                    i = 0;

                type = type || 'success';
                messages = messages || [];

                if (!$messages.length) {
                    $messages = $('<div class="pum-form__messages">').hide();
                    switch (pum_vars.message_position) {
                    case 'bottom':
                        $form.append($messages.addClass('pum-form__messages--bottom'));
                        break;
                    case 'top':
                        $form.prepend($messages.addClass('pum-form__messages--top'));
                        break;
                    }
                }

                if (['bottom', 'top'].indexOf(pum_vars.message_position) >= 0) {
                    for (; messages.length > i; i++) {
                        this.add_message($messages, messages[i].message, type);
                    }
                } else {
                    /**
                     * Per Field Messaging
                     */
                    for (; messages.length > i; i++) {

                        if (messages[i].field !== undefined) {
                            this.add_field_error($form, messages[i]);
                        } else {
                            this.add_message($messages, messages[i].message, type);
                        }
                    }
                }

                if ($messages.is(':hidden') && $('.pum-form__message', $messages).length) {
                    $messages.slideDown();
                }
            },
            add_message: function ($container, message, type) {
                var $message = $('<p class="pum-form__message">').html(message);

                type = type || 'success';

                $message.addClass('pum-form__message--' + type);

                $container.append($message);

                if ($container.is(':visible')) {
                    $message.hide().slideDown();
                }
            },
            add_field_error: function ($form, error) {
                var $field = $('[name="' + error.field + '"]', $form),
                    $wrapper = $field.parents('.pum-form__field').addClass('pum-form__field--error');

                this.add_message($wrapper, error.message, 'error');
            },
            clear_all: function ($form, hide) {
                var $messages = $form.find('.pum-form__messages'),
                    messages = $messages.find('.pum-form__message'),
                    $errors = $form.find('.pum-form__field.pum-form__field--error');

                hide = hide || false;

                // Remove forms main messages container.
                if ($messages.length) {
                    messages.slideUp('fast', function () {
                        $(this).remove();

                        if (hide) {
                            $messages.hide();
                        }
                    });

                }

                // Remove per field messages.
                if ($errors.length) {
                    $errors.removeClass('pum-form__field--error').find('p.pum-form__message').remove();
                }
            },
            scroll_to_first: function ($form) {
                window.PUM.utilities.scrollTo($('.pum-form__field.pum-form__field--error', $form).eq(0));
            }
        },
        /**
         * Used to process success actions for forms inside popups.
         *
         * @param $form
         * @param settings
         */
        success: function ($form, settings) {
            settings = $.extend({}, defaults, settings);

            if (!settings) {
                return;
            }

            var $parentPopup = $form.parents('.pum'),
                redirect = function () {
                    if (settings.redirect_enabled) {
                        if (settings.redirect !== '') {
                            // Redirect to the destination url.
                            window.location = settings.redirect;
                        } else {
                            // Refresh with force true.
                            window.location.reload(true);
                        }
                    }
                },
                callback = function () {
                    if (settings.openpopup && PUM.getPopup(settings.openpopup_id).length) {
                        PUM.open(settings.openpopup_id);
                    } else {
                        redirect();
                    }
                };

            if ($parentPopup.length) {
                $parentPopup.trigger('pumFormSuccess');
            }

            if ($parentPopup.length && settings.closepopup) {
                setTimeout(function () {
                    $parentPopup.popmake('close', callback);
                }, parseInt(settings.closedelay) * 1000);
            } else {
                callback();
            }
        }
    });


}(jQuery));
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

"use strict";
(function ($) {

    /**
     * Batch Processor.
     *
     * @since 1.7.0
     */
    var batch = {
            form: {
                beforeSubmit: function ($form) {
                    var $submit = $form.find('.pum-field-submit input[type="submit"]'),
                        $messages = $form.find('.pum-upgrade-messages'),
                        $progress = $form.find('.pum-batch-progress'),
                        // Handle the Are You Sure (AYS) if present on the form element.
                        ays = $form.data('ays');

                    if (!$submit.hasClass('button-disabled')) {

                        if (ays !== undefined && !confirm(ays)) {
                            return false;
                        }

                        $progress.removeClass('pum-batch-progress--active');
                        $progress.find('progress').prop('value', null);

                        // Clear messages.
                        $messages.html('');

                        // Disable the button.
                        $submit.addClass('button-disabled');

                        // Add the spinner.
                        $('<span class="spinner is-active"></span>').insertAfter($submit);

                        return true;
                    }

                    return false;
                }
            },
            complete: function ($form) {
                var $notice = $form.parents('.notice');

                $form.find('.pum-field-submit, progress').hide();
                $('p.pum-upgrade-notice').hide();
                $notice.removeClass('notice-info').addClass('notice-success  notice-alt');
                $notice.prepend('<h2>' + pum_batch_vars.complete + '</h2>')
            },
            action: 'pum_process_batch_request',
            /**
             * Processes a single batch of data.
             *
             * @param {integer|number|string} step Step in the process.
             * @param {object} data Form data.
             */
            process_step: function (step, data) {
                var self = this;

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        batch_id: data.batch_id,
                        action: self.action,
                        nonce: data.nonce,
                        form: data.form,
                        step: parseInt(step),
                        data: data
                    },
                    dataType: "json",
                    success: function (response) {

                        if (response.data.done || response.data.error) {

                            var batchSelector = response.data.mapping ? '.pum-batch-import-form' : '.pum-batch-form',
                                // We need to get the actual in progress form, not all forms on the page
                                $batchForm = $(batchSelector),
                                spinner = $batchForm.find('.spinner'),
                                notice_wrap = $batchForm.find('.notice-wrap');

                            $batchForm.find('.button-disabled').removeClass('button-disabled');

                            if (response.data.error) {

                                spinner.remove();
                                notice_wrap.html('<div class="updated error"><p>' + response.data.error + '</p></div>');

                            } else if (response.data.done) {

                                spinner.remove();
                                notice_wrap.html('<div id="pum-batch-success" class="updated notice"><p class="pum-batch-success">' + response.data.message + '</p></div>');

                                if (response.data.url) {
                                    window.location = response.data.url;
                                }

                            } else {

                                notice_wrap.remove();

                            }
                        } else {
                            $('.pum-batch-progress div').animate({
                                width: response.data.percentage + '%'
                            }, 50);

                            self.process_step(response.data.step, data);
                        }
                    }
                }).fail(function (response) {
                    if (window.console && window.console.log) {
                        console.log(response);
                    }
                });
            }
        },
        batch_import = $.extend({}, batch, {
            action: 'pum_process_batch_import',
            before_submit: function (arr, $form) {
                var $import_form = $('.pum-batch-import-form').find('.pum-batch-progress').parent().parent(),
                    notice_wrap = $import_form.find('.notice-wrap');

                $form.find('.notice-wrap').remove();
                $form.append('<div class="notice-wrap"><span class="spinner is-active"></span><div class="pum-batch-progress"><div></div>');

                // Check whether client browser fully supports all File API.
                if (window.File && window.FileReader && window.FileList && window.Blob) {

                    // HTML5 File API is supported by browser

                } else {
                    $import_form.find('.button-disabled').removeClass('button-disabled');

                    // Error for older unsupported browsers that doesn't support HTML5 File API.
                    notice_wrap.html('<div class="update error"><p>' + pum_batch_vars.unsupported_browser + '</p></div>');
                    return false;

                }

            },
            success: function (responseText, statusText, xhr, $form) {
                console.log($form);
            },
            complete: function (xhr) {

                var response = jQuery.parseJSON(xhr.responseText),
                    self = this;

                if (response.success) {

                    // Select only the current form.
                    var $form = $('.pum-batch-import-form .notice-wrap').parent(),
                        select = $form.find('select.pum-import-csv-column'),
                        options = '',
                        selectName, field, tableRow,
                        columns = response.data.columns.sort(function (a, b) {
                            if (a < b) return -1;
                            if (a > b) return 1;
                            return 0;
                        });

                    $form.find('.pum-import-file-wrap, .notice-wrap').remove();
                    $form.find('.pum-import-options').slideDown();

                    $.each(select, function (selectKey, selectValue) {
                        var $currentSelect = $(this);

                        selectName = $(selectValue).attr('name');

                        $.each(columns, function (columnKey, columnValue) {
                            var processedColumnValue = columnValue.toLowerCase().replace(/ /g, '_'),
                                columnRegex = new RegExp("\\[" + processedColumnValue + "\\]"),
                                selected = selectName.length && selectName.match(columnRegex) ? ' selected="selected"' : false;

                            if (selected) {
                                // Update the preview if there's a first-row value.
                                $currentSelect.parent().next().html(false !== response.data.first_row[columnValue] ? response.data.first_row[columnValue] : '');
                            }

                            // If the column matches a select, auto-map it. Boom.
                            options += '<option value="' + columnValue + '"' + selected + '>' + columnValue + '</option>';
                        });

                        // Add the options markup to the select.
                        $currentSelect.append(options);

                        // Reset options.
                        options = '';
                    });

                    select.on('change', function () {
                        var $this = $(this),
                            val = $this.val(),
                            html = '';

                        if (val && false !== response.data.first_row[val]) {
                            html = response.data.first_row[val];
                        }

                        $this.parent().next().html(html);
                    });

                    $('body').on('click', '.pum-import-proceed', function (event) {

                        event.preventDefault();

                        // Validate for required fields.
                        if ($form.data('required')) {
                            var required = $form.data('required'),
                                requiredFields = [];

                            if (required.indexOf(',')) {
                                requiredFields = required.split(',');
                            } else {
                                requiredFields = [required];
                            }

                            var triggerValidation = false;

                            $.each(requiredFields, function (key, value) {
                                field = $("select[name='pum-import-field[" + value + "]']");
                                tableRow = field.parent().parent();

                                // Remove the validation class if this is a repeat click.
                                tableRow.removeClass('pum-required-import-field');

                                // If nothing is mapped, trigger validation.
                                if (field.val() === '') {
                                    triggerValidation = true;

                                    tableRow.addClass('pum-required-import-field');
                                    field.parent().next().html(pum_batch_vars.import_field_required);
                                }
                            });

                            // If validation has been triggered, bail from submitting the form.
                            if (triggerValidation) {
                                return;
                            }

                        }

                        $form.find('.notice-wrap').remove();

                        // Add the spinner.
                        $(this).parent().append('<span class="spinner is-active"></span>');

                        $form.append('<div class="notice-wrap"><div class="pum-batch-progress"><div></div>');

                        response.data.mapping = $form.serialize();
                        response.data.form = $form.serializeAssoc();

                        batch.process_step(1, response.data);
                    });

                } else {

                    self.error(xhr);

                }

            },
            error: function (xhr) {

                // Something went wrong. This will display error on form
                var response = jQuery.parseJSON(xhr.responseText),
                    import_form = $('.pum-batch-import-form').find('.pum-batch-progress').parent().parent(),
                    notice_wrap = import_form.find('.notice-wrap');

                import_form.find('.button-disabled').removeClass('button-disabled');

                if (response.data.error) {

                    notice_wrap.html('<div class="update error"><p>' + response.data.error + '</p></div>');

                } else {

                    notice_wrap.remove();

                }
            }

        }),
        batch_upgrades = $.extend(true, {}, batch, {
            action: 'pum_process_upgrade_request',
            /**
             * Processes a que of batch upgrades.
             *
             * @param {integer|number|string} step Step in the process.
             * @param {object} data Form data.
             */
            process_step: function (step, data) {
                var self = this;

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        upgrade_id: data.upgrade_id,
                        action: self.action,
                        nonce: data.nonce,
                        form: data.form,
                        step: parseInt(step),
                        data: data
                    },
                    dataType: "json",
                    success: function (response) {
                        var $form = $('.pum-upgrade-form'), // We need to get the actual in progress form, not all forms on the page
                            $spinner = $form.find('.spinner'),
                            $submit = $form.find('.button-disabled'),
                            $messages = $form.find('.pum-upgrade-messages');

                        if (response.data.done || response.data.error) {


                            // Reset submit button.
                            $submit.removeClass('button-disabled');

                            if (response.data.error) {
                                $spinner.remove();
                                $messages.prepend('<div class="notice notice-error notice-alt"><p>' + response.data.error + '</p></div>');
                            } else if (response.data.done) {

                                $messages.prepend('<div class="notice notice-success"><p><strong>' + response.data.message + '</strong></p></div>');

                                if (response.data.next) {
                                    $form
                                        .data('upgrade_id', response.data.next)
                                        .data('step', 1)
                                        .data('ays', false);

                                    self.process_step(1, {
                                        upgrade_id: response.data.next,
                                        nonce: data.nonce,
                                        form: data.form
                                    });
                                } else {
                                    $submit.parent().hide();
                                    $spinner.remove();

                                    batch.complete($form);
                                }

                                if (response.data.url) {
                                    window.location = response.data.url;
                                }

                            } else {
                                if (response.data.message !== '') {
                                    $messages.prepend('<div class="notice"><p class="">' + response.data.message + '</p></div>');
                                }
                            }
                        } else {

                            if (response.data.message !== '') {
                                $messages.prepend('<div class="notice"><p class="">' + response.data.message + '</p></div>');
                            }

                            $('.pum-batch-progress').addClass('pum-batch-progress--active');

                            $('.pum-batch-progress progress.pum-task-progress').addClass('active').val(response.data.percentage);

                            self.process_step(response.data.step, data);
                        }
                    }
                }).fail(function (response) {
                    if (window.console && window.console.log) {
                        console.log(response);
                    }
                });
            }

        });

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.batch = batch;
    window.PUM_Admin.batch_import = batch_import;
    window.PUM_Admin.batch_upgrades = batch_upgrades;

    /**
     * Handles form submission preceding batch processing.
     */
    $(document)
        .on('submit', '.pum-batch-form[data-batch_id]', function (event) {
            var $this = $(this),
                submitButton = $this.find('input[type="submit"]'),
                // Handle the Are You Sure (AYS) if present on the form element.
                ays = $this.data('ays'),
                data = {
                    batch_id: $this.data('batch_id'),
                    nonce: $this.data('nonce'),
                    form: $this.serializeAssoc(),
                    test: $this.pumSerializeObject()
                };

            event.preventDefault();

            if (!submitButton.hasClass('button-disabled')) {

                if (ays !== undefined && !confirm(ays)) {
                    return;
                }

                // Remove existing notice & progress bars.
                $this.find('.notice-wrap').remove();

                // Add the progress bar.
                $this.append($('<div class="notice-wrap"><div class="pum-batch-progress"><div></div>'));

                // Disable the button.
                submitButton.addClass('button-disabled');

                // Add the spinner.
                submitButton.parent().append('<span class="spinner is-active"></span>');

                // Start the process.
                batch.process_step(1, data);
            }
        })
        .on('submit', '.pum-batch-form.pum-upgrade-form[data-upgrade_id]', function (event) {
            var $form = $(this),
                data = {
                    upgrade_id: $form.data('upgrade_id'),
                    nonce: $form.data('nonce'),
                    form: $form.serializeAssoc(),
                    test: $form.pumSerializeObject()
                };

            event.preventDefault();

            // Process presubmit actions like showing progress data and validating info.
            if (batch_upgrades.form.beforeSubmit($form)) {
                // Start the process.
                batch_upgrades.process_step($form.data('step') || 1, data);
            }

        })
        .ready(function () {
            // Handle multiple importers on the same screen.
            $('.pum-batch-import-form').each(function () {
                var $this = $(this);

                $this.ajaxForm({
                    beforeSubmit: batch_import.before_submit,
                    complete: batch_import.complete,
                    dataType: 'json',
                    error: batch_import.error,
                    data: {
                        action: batch_import.action,
                        batch_id: $this.data('batch_id'),
                        nonce: $this.data('nonce')
                    },
                    url: ajaxurl,
                    resetForm: true
                });
            });
        });

}(jQuery));

jQuery(document).ready(function ($) {

    $.extend({
        arrayMerge: function () {
            var a = {};
            var n = 0;
            var argv = $.arrayMerge.arguments;
            for (var i = 0; i < argv.length; i++) {
                if (Array.isArray(argv[i])) {
                    for (var j = 0; j < argv[i].length; j++) {
                        a[n++] = argv[i][j];
                    }
                    a = $.makeArray(a);
                } else {
                    for (var k in argv[i]) {
                        if (argv[i].hasOwnProperty(k)) {
                            if (isNaN(k)) {
                                var v = argv[i][k];
                                if (typeof v === 'object' && a[k]) {
                                    v = $.arrayMerge(a[k], v);
                                }
                                a[k] = v;
                            } else {
                                a[n++] = argv[i][k];
                            }
                        }
                    }
                }
            }
            return a;
        },
        count: function (arr) {
            return Array.isArray(arr) ? arr.length : typeof arr === 'object' ? Object.keys(arr).length : false;
        }
    });

    $.fn.extend({
        serializeAssoc: function () {
            var o = {
                aa: {},
                add: function (name, value) {
                    var tmp = name.match(/^(.*)\[([^\]]*)]$/),
                        v = {};

                    if (tmp) {
                        if (tmp[2])
                            v[tmp[2]] = value;
                        else
                            v[$.count(v)] = value;
                        this.add(tmp[1], v);
                    }
                    else if (typeof value === 'object') {
                        if (typeof this.aa[name] !== 'object') {
                            this.aa[name] = {};
                        }
                        this.aa[name] = $.arrayMerge(this.aa[name], value);
                    }
                    else {
                        this.aa[name] = value;
                    }
                }
            };
            var a = $(this).serializeArray();
            for (var i = 0; i < a.length; i++) {
                o.add(a[i].name, a[i].value);
            }
            return o.aa;
        }
    });

});

var PUMChosenFields;
(function ($) {
    "use strict";

    // Variables for setting up the typing timer
    var typingTimer,               // Timer identifier
        doneTypingInterval = 464;  // Time in ms, Slow - 521ms, Moderate - 342ms, Fast - 300ms

    PUMChosenFields = {
        init: function () {
            $('.pum-chosen select').filter(':not(.initialized)').each(function () {
                var $this = $(this);
                console.log($this.attr('placeholder'));
                $this
                    .addClass('initialized')
                    .chosen({
                        allow_single_deselect: true,
                        width: '100%',
                        placeholder_text_multiple: $this.attr('placeholder')
                    });
            });
        }
    };


    $(document)
        .on('pum_init', PUMChosenFields.init)
        // Replace options with search results
        .on('keyup', '.pum-objectselect .chosen-container .chosen-search input, .pum-objectselect .chosen-container .search-field input', function (e) {
            var $this = $(this),
                $field = $this.parents('.pum-field'),
                val = $this.val(),
                container = $field.find('.chosen-container'),
                menu_id = container.attr('id').replace('_chosen', ''),
                lastKey = e.which,
                object_type= $field.find('[data-objecttype]').data('objecttype'),
                object_key = $field.find('[data-objectkey]').data('objectkey');

            // Don't fire if short or is a modifier key (shift, ctrl, apple command key, or arrow keys)
            if (
                (val.length <= 2) || (
                    lastKey === 16 ||
                    lastKey === 13 ||
                    lastKey === 91 ||
                    lastKey === 17 ||
                    lastKey === 37 ||
                    lastKey === 38 ||
                    lastKey === 39 ||
                    lastKey === 40 ||
                    lastKey === 8
                )
            ) {
                return;
            }

            clearTimeout(typingTimer);
            typingTimer = setTimeout(
                function () {
                    $.ajax({
                        type: 'GET',
                        url: ajaxurl,
                        data: {
                            action: "pum_object_search",
                            object_type: object_type,
                            object_key: object_key,
                            s: val,
                            current_id: pum_admin.post_id
                        },
                        dataType: "json",
                        beforeSend: function () {
                            $('ul.chosen-results').empty();
                        },
                        success: function (data) {
                            // Remove all options but those that are selected
                            $('#' + menu_id + ' option:not(:selected)').remove();
                            $.each(data, function (key, item) {
                                // Add any option that doesn't already exist
                                if (!$('#' + menu_id + ' option[value="' + item.id + '"]').length) {
                                    $('#' + menu_id).prepend('<option value="' + item.id + '">' + item.name + '</option>');
                                }
                            });
                            // Update the options
                            $('.pum-chosen select').trigger('chosen:updated');
                            $('#' + menu_id).next().find('input').val(val);
                        }
                    }).fail(function (response) {
                        if (window.console && window.console.log) {
                            console.log(response);
                        }
                    }).done(function (response) {

                    });
                },
                doneTypingInterval
            );
        });

}(jQuery));
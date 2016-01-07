var PUMChosenFields;
(function ($) {
    "use strict";

    // Variables for setting up the typing timer
    var typingTimer,               // Timer identifier
        doneTypingInterval = 464;  // Time in ms, Slow - 521ms, Moderate - 342ms, Fast - 300ms

    PUMChosenFields = {
        init: function () {
            $('.pum-chosen select').filter(':not(.initialized)').each(function () {
                var $this = $(this),
                    current = $this.data('current'),
                    object_type = $this.data('objecttype'),
                    object_key = $this.data('objectkey');

                $this
                    .addClass('initialized')
                    .chosen({
                        allow_single_deselect: true,
                        width: $this.is(':visible') ? $this.outerWidth(true) + 'px' : '200px',
                        placeholder_text_multiple: $this.attr('title')
                    });

                $.ajax({
                    type: 'GET',
                    url: ajaxurl,
                    data: {
                        action: "pum_object_search",
                        object_type: object_type,
                        object_key: object_key,
                        include: current,
                        current_id: pum_admin.post_id
                    },
                    async: true,
                    dataType: "json",
                    success: function (data) {
                        $.each(data, function (key, item) {
                            // Add any option that doesn't already exist
                            if (!$this.find('option[value="' + item.id + '"]').length) {
                                $this.prepend('<option value="' + item.id + '">' + item.name + '</option>');
                            }
                        });
                        // Update the options
                        $this.val(current);
                        $this.trigger('chosen:updated');
                    }
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
                $select = $field.find('select:first'),
                val = $this.val(),
                lastKey = e.which,
                object_type = $select.data('objecttype'),
                object_key = $select.data('objectkey');

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
                            $field.find('ul.chosen-results').empty();
                        },
                        success: function (data) {
                            // Remove all options but those that are selected
                            $select.find('option:not(:selected)').remove();
                            $.each(data, function (key, item) {
                                // Add any option that doesn't already exist
                                if (!$select.find('option[value="' + item.id + '"]').length) {
                                    $select.prepend('<option value="' + item.id + '">' + item.name + '</option>');
                                }
                            });
                            // Update the options
                            $select.trigger('chosen:updated');
                            $this.val(val);
                        }
                    });
                },
                doneTypingInterval
            );
        });

}(jQuery));
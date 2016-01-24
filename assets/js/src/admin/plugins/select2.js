var PUMSelect2Fields;
(function ($, document, undefined) {
    "use strict";

    PUMSelect2Fields = {
        init: function () {
            $('.pum-select2 select').filter(':not(.initialized)').each(function () {
                var $this = $(this),
                    current = $this.data('current'),
                    object_type = $this.data('objecttype'),
                    object_key = $this.data('objectkey'),
                    options = {
                        dropdownParent: $this.parent()
                    };

                if ($this.attr('multiple')) {
                    options.multiple = true;
                }

                if (object_type && object_key) {
                    options = $.extend(options, {
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    s: params.term, // search term
                                    page: params.page,
                                    action: "pum_object_search",
                                    object_type: object_type,
                                    object_key: object_key
                                };
                            },
                            processResults: function (data, params) {
                                // parse the results into the format expected by Select2
                                // since we are using custom formatting functions we do not need to
                                // alter the remote JSON data, except to indicate that infinite
                                // scrolling can be used
                                params.page = params.page || 1;

                                return {
                                    results: data.items,
                                    pagination: {
                                        more: (params.page * 10) < data.total_count
                                    }
                                };
                            },
                            cache: true
                        },
                        cache: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        }, // let our custom formatter work
                        minimumInputLength: 1,
                        templateResult: PUMSelect2Fields.formatObject,
                        templateSelection: PUMSelect2Fields.formatObjectSelection
                    });
                }

                $this
                    .addClass('initialized')
                    .select2(options);

                if (current !== undefined && object_type && object_key) {

                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: "pum_object_search",
                            object_type: object_type,
                            object_key: object_key,
                            include: $this.data('current')
                        },
                        dataType: "json",
                        success: function (data) {
                            $.each(data.items, function (key, item) {
                                // Add any option that doesn't already exist
                                if (!$this.find('option[value="' + item.id + '"]').length) {
                                    $this.prepend('<option value="' + item.id + '">' + item.text + '</option>');
                                }
                            });
                            // Update the options
                            $this.val(current).trigger('change');
                        }
                    });

                } else if (current !== undefined) {
                    $this.val(current).trigger('change');
                }

            });
        },
        formatObject: function (object) {
            return object.text;
        },
        formatObjectSelection: function (object) {
            return object.text || object.text;
        }
    };

    $(document).on('pum_init', PUMSelect2Fields.init);

}(jQuery, document));
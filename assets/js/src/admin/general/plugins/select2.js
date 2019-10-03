/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";

    // Here because some plugins load additional copies, big no-no. This is the best we can do.
    $.fn.pumselect2 = $.fn.pumselect2 || $.fn.select2;

    var select2 = {
        init: function () {
            $('.pum-field-select2 select').filter(':not(.pumselect2-initialized)').each(function () {
                var $this = $(this),
                    current = $this.data('current') || $this.val(),
                    object_type = $this.data('objecttype'),
                    object_key = $this.data('objectkey'),
                    object_excludes = $this.data('objectexcludes') || null,
                    options = {
                        width: '100%',
                        multiple: false,
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
                                    paged: params.page,
                                    action: "pum_object_search",
                                    object_type: object_type,
                                    object_key: object_key,
                                    exclude: object_excludes
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
                        maximumInputLength: 20,
                        closeOnSelect: !options.multiple,
                        templateResult: PUM_Admin.select2.formatObject,
                        templateSelection: PUM_Admin.select2.formatObjectSelection
                    });
                }

                $this
                    .addClass('pumselect2-initialized')
                    .pumselect2(options);

                if (current !== null && current !== undefined) {

                    if (options.multiple && 'object' !== typeof current && current !== "") {
                        current = [current];
                    } else if (!options.multiple && current === '') {
                        current = null;
                    }
                } else {
                    current = null;
                }

                if (object_type && object_key && current !== null && (typeof current === 'number' || current.length)) {
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: "pum_object_search",
                            object_type: object_type,
                            object_key: object_key,
                            exclude: object_excludes,
                            include: current && current.length ? (typeof current === 'string' || typeof current === 'number') ? [current] : current : null
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
                } else if (current && ((options.multiple && current.length) || (!options.multiple && current !== ""))) {
                    $this.val(current).trigger('change');
                } else if (current === null) {
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

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.select2 = select2;

    $(document)
        .on('pum_init', function () {
            PUM_Admin.select2.init();
        });
}(jQuery));
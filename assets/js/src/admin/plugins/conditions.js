var PUMConditions;
(function ($, document, undefined) {
    "use strict";

    PUMConditions = {
        templates: {},
        addGroup: function (target, not_operand) {
            var $container = $('#pum-popup-conditions'),
                data = {
                    index: $container.find('.facet-group-wrap').length,
                    conditions: [
                        {
                            target: target || null,
                            not_operand: not_operand || false,
                            settings: {}
                        }
                    ]
                };
            $container.find('.facet-groups').append(PUMConditions.templates.group(data));
            $container.find('.facet-builder').addClass('has-conditions');
            $(document).trigger('pum_init');
        },
        renumber: function () {
            $('#pum-popup-conditions .facet-group-wrap').each(function () {
                var $group = $(this),
                    groupIndex = $group.parent().children().index($group);

                $group
                    .data('index', groupIndex)
                    .find('.facet').each(function () {
                        var $facet = $(this),
                            facetIndex = $facet.parent().children().index($facet);

                        $facet
                            .data('index', facetIndex)
                            .find('[name]').each(function () {
                                var replace_with = "popup_conditions[" + groupIndex + "][" + facetIndex + "]";
                                this.name = this.name.replace(/popup_conditions\[\d*?\]\[\d*?\]/, replace_with);
                                this.id = this.name;
                            });
                    });
            });
        }
    };

    $(document)
        .on('pum_init', PUMConditions.renumber)
        .ready(function () {
            // TODO Remove this check once admin scripts have been split into popup-editor, theme-editor etc.
            if ($('body.post-type-popup form#post').length) {
                PUMConditions.templates.group = _.template($('#pum_condition_group_templ').text());
                PUMConditions.templates.facet = _.template($('#pum_condition_facet_templ').text());
                PUMConditions.templates.settings = {};

                $('script.templ.pum-condition-settings').each(function () {
                    var $this = $(this);
                    PUMConditions.templates.settings[$this.data('condition')] = _.template($this.text());
                });

                PUMConditions.renumber();
            }
        })
        .on('change', '#pum-first-condition', function () {
            var $this = $(this),
                target = $this.val(),
                $operand = $('#pum-first-condition-operand'),
                not_operand = $operand.is(':checked') ? $operand.val() : null;

            PUMConditions.addGroup(target, not_operand);

            $this.val('').trigger('chosen:updated');
            $operand.prop('checked', false).parents('.pum-condition-target').removeClass('not-operand-checked');
        })
        .on('click', '#pum-popup-conditions .pum-not-operand', function () {
            var $this = $(this),
                $input = $this.find('input'),
                $container = $this.parents('.pum-condition-target');

            if ($input.is(':checked')) {
                $container.removeClass('not-operand-checked');
                $input.prop('checked', false);
            } else {
                $container.addClass('not-operand-checked');
                $input.prop('checked', true);
            }
        })
        .on('change', '#pum-popup-conditions select.target', function () {
            var $this = $(this),
                target = $this.val(),
                data = {
                    index: $this.parents('.facet-group').find('.facet').length,
                    target: target,
                    settings: {}
                };

            if (target === '' || target === $this.parents('.facet').data('target') || PUMConditions.templates.settings[target] === undefined) {
                // TODO Add better error handling.
                return;
            }

            $this.parents('.facet').data('target', target).find('.facet-settings').html(PUMConditions.templates.settings[target](data));
            $(document).trigger('pum_init');
        })
        .on('click', '#pum-popup-conditions .facet-group-wrap:last-child .and .add-facet', PUMConditions.addGroup)
        .on('click', '#pum-popup-conditions .add-or .add-facet:not(.disabled)', function () {
            var $this = $(this),
                $group = $this.parents('.facet-group-wrap'),
                data = {
                    group: $group.data('index'),
                    index: $group.find('.facet').length,
                    target: null,
                    settings: {}
                };

            $group.find('.facet-list').append(PUMConditions.templates.facet(data));
            $(document).trigger('pum_init');
        })
        .on('click', '#pum-popup-conditions .remove-facet', function () {
            var $this = $(this),
                $container = $('#pum-popup-conditions'),
                $facet = $this.parents('.facet'),
                $group = $this.parents('.facet-group-wrap');

            $facet.remove();

            if ($group.find('.facet').length === 0) {
                $group.prev('.facet-group-wrap').find('.and .add-facet').removeClass('disabled');
                $group.remove();

                if ($container.find('.facet-group-wrap').length === 0) {
                    $container.find('.facet-builder').removeClass('has-conditions');
                }
            }
            PUMConditions.renumber();
        });


}(jQuery, document));
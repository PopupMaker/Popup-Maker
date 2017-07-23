/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

(function ($) {
    "use strict";

    var forms = {
        init: function () {
            forms.checkDependencies();
        },
        /**
         * dependencies should look like this:
         *
         * {
                 *   field_name_1: value, // Select, radio etc.
                 *   field_name_2: true // Checkbox
                 * }
         *
         * Support for Multiple possible values of one field
         *
         * {
                 *   field_name_1: [ value_1, value_2 ]
                 * }
         *
         */
        checkDependencies: function ($dependent_fields) {
            var _fields = $($dependent_fields);

            // If no fields passed, only do those not already initialized.
            $dependent_fields = _fields.length ? _fields : $("[data-pum-dependencies]:not([data-pum-processed-dependencies])");

            $dependent_fields.each(function () {
                var $dependent      = $(this),
                    dependentID     = $dependent.data('id'),
                    // The dependency object for this field.
                    dependencies    = $dependent.data("pum-processed-dependencies") || {},
                    // Total number of fields this :input is dependent on.
                    requiredCount   = Object.keys(dependencies).length,
                    // Current count of fields this :input matched properly.
                    count           = 0,
                    // An array of fields this :input is dependent on.
                    dependentFields = $dependent.data("pum-dependent-fields"),
                    // Early declarations.
                    key;

                // Clean up & pre-process dependencies so we don't need to rebuild each time.
                if (!$dependent.data("pum-processed-dependencies")) {
                    dependencies = $dependent.data("pum-dependencies");
                    if (typeof dependencies === 'string') {
                        dependencies = JSON.parse(dependencies);
                    }

                    // Convert each key to an array of acceptable values.
                    for (key in dependencies) {
                        if (dependencies.hasOwnProperty(key)) {
                            if (typeof dependencies[key] === "string") {
                                // Leave boolean values alone as they are for checkboxes or checking if an input has any value.

                                if (dependencies[key].indexOf(',') !== -1) {
                                    dependencies[key] = dependencies[key].split(',');
                                } else {
                                    dependencies[key] = [dependencies[key]];
                                }
                            }
                        }
                    }

                    // Update cache & counts.
                    requiredCount = Object.keys(dependencies).length;
                    $dependent.data("pum-processed-dependencies", dependencies).attr("data-pum-processed-dependencies", dependencies);
                }

                if (!dependentFields) {
                    dependentFields = $.map(dependencies, function (value, index) {
                        var $wrapper = $('.pum-field[data-id="' + index + '"]');

                        return $wrapper.length ? $wrapper.eq(0) : null;
                    });

                    $dependent.data("pum-dependent-fields", dependentFields);
                }

                $(dependentFields).each(function () {
                    var $wrapper                   = $(this),
                        $field                     = $(this).find(':input:first'),
                        id                         = $wrapper.data("id"),
                        value                      = $field.val(),
                        required                   = dependencies[id] || null,
                        matched,
                        // Used for limiting the fields that get updated when this field is changed.
                        all_this_fields_dependents = $wrapper.data('pum-field-dependents');

                    if (!all_this_fields_dependents) {
                        all_this_fields_dependents = [];
                    }

                    if (all_this_fields_dependents.indexOf(dependentID) === -1) {
                        all_this_fields_dependents.push(dependentID);
                        $wrapper.data('pum-field-dependents', all_this_fields_dependents);
                    }

                    // If no required values found bail early.
                    if (required === null) {
                        $dependent.hide();
                        // Effectively breaks the .each for this $dependent and hides it.
                        return false;
                    }

                    if ($wrapper.hasClass('pum-field-radio')) {
                        value = $wrapper.find(':input:checked').val();
                    }

                    // Check if the value matches required values.
                    if ($wrapper.hasClass('pum-field-select') || $wrapper.hasClass('pum-field-radio')) {
                        matched = required.indexOf(value) !== -1;
                    } else if ($wrapper.hasClass('pum-field-checkbox')) {
                        matched = required === $field.is(':checked');
                    }

                    if (matched) {
                        count++;
                    } else {
                        $dependent.hide();
                        // Effectively breaks the .each for this $dependent and hides it.
                        return false;
                    }

                    if (count === requiredCount) {
                        $dependent.show();
                    }
                });
            });
        },
        form_check: function () {
            $(document).trigger('pum_form_check');
        },
        is_field: function (data) {
            if (typeof data !== 'object') {
                return false;
            }

            var field_tests = [
                data.id !== undefined,
                data.label !== undefined,
                data.type !== undefined,
                data.options !== undefined,
                data.desc !== undefined
            ];

            return field_tests.indexOf(true) >= 0;

        },
        render: function ($container, args, values) {
            var tabs              = {},
                data              = $.extend({
                    id: "",
                    tabs: {},
                    sections: {},
                    fields: {}
                }, args),
                form_fields       = {},
                sections,
                field,
                container_classes = ['pum-dynamic-form'],
                container_content;

            if (undefined === values) {
                values = {};
            }

            if (Object.keys(data.tabs).length) {
                container_classes.push('tabbed-content');

                if (undefined === tabs) {
                    tabs = {};
                }

                if (Object.keys(data.sections).length) {

                    if (undefined === sections) {
                        sections = {};
                    }

                    // Fields come already arranged by section. Loop Sections then Fields.
                    _.each(data.fields, function (tabSections, tabID) {

                        if (typeof tabSections !== 'object' || !Object.keys(tabSections).length) {
                            return;
                        }

                        if (undefined === tabs[tabID]) {
                            tabs[tabID] = {
                                id: data.id + '-' + tabID + '-subtabs',
                                classes: ['link-tabs','sub-tabs'],
                                tabs: {},
                                meta: {
                                    'data-min-height': 250
                                }
                            };
                        }

                        sections = {};

                        // Fields come already arranged by section. Loop Sections then Fields.
                        _.each(tabSections, function (sectionFields, sectionID) {

                            if (typeof sectionFields !== 'object' || !Object.keys(sectionFields).length) {
                                return;
                            }


                            if (forms.is_field(sectionFields)) {
                                var newSectionFields = {};
                                newSectionFields[sectionID] = sectionFields;
                                sectionID = 'main';
                                sectionFields = newSectionFields;
                            }

                            if (undefined === sections[sectionID]) {
                                sections[sectionID] = {
                                    fields: []
                                };
                            }

                            // Push rendered fields into the section array.
                            _.each(sectionFields, function (fieldArgs) {

                                // Store the field by id for easy lookup later.
                                form_fields[fieldArgs.id] = fieldArgs;

                                field = fieldArgs;

                                if (undefined !== values[fieldArgs.id]) {
                                    field.value = values[fieldArgs.id];
                                }

                                sections[sectionID].fields.push(PUM_Admin.templates.field(field));
                            });

                        });

                        _.each(sections, function (section, sectionID) {
                            // Render the section into the content of a new tab.
                            tabs[tabID].tabs[sectionID] = {
                                label: data.sections[tabID][sectionID],
                                content: PUM_Admin.templates.section(sections[sectionID])
                            };
                        });


                        // Render subtab sections into this top level tab's content.
                        tabs[tabID] = {
                            label: data.tabs[tabID],

                            content: PUM_Admin.templates.tabs(tabs[tabID])
                        };
                    });

                    // Render Tabs
                    tabs = PUM_Admin.templates.tabs({
                        id: data.id,
                        classes: '',
                        tabs: tabs,
                        vertical: true,
                        form: true,
                        meta: {
                            'data-min-height': 250
                        }
                    });

                    container_content = tabs;
                }

            }
            else if (Object.keys(data.sections).length) {
                container_classes.push('tabbed-content');

                if (undefined === sections) {
                    sections = {};
                }

                // Fields come already arranged by section. Loop Sections then Fields.
                _.each(data.fields, function (sectionFields, sectionID) {

                    if (typeof sectionFields !== 'object' || !Object.keys(sectionFields).length) {
                        return;
                    }

                    if (undefined === sections[sectionID]) {
                        sections[sectionID] = [];
                    }

                    // Push rendered fields into the section array.
                    _.each(sectionFields, function (fieldArgs) {

                        // Store the field by id for easy lookup later.
                        form_fields[fieldArgs.id] = fieldArgs;


                        field = fieldArgs;

                        if (undefined !== values[fieldArgs.id]) {
                            field.value = values[fieldArgs.id];
                        }

                        sections[sectionID].push(PUM_Admin.templates.field(field));
                    });

                    // Render the section.
                    sections[sectionID] = PUM_Admin.templates.section({
                        fields: sections[sectionID]
                    });
                });

                // Generate Tab List
                _.each(sections, function (section, id) {

                    tabs[id] = {
                        label: data.sections[id],
                        content: section
                    };

                });

                // Render Tabs
                tabs = PUM_Admin.templates.tabs({
                    id: data.id,
                    classes: '',
                    tabs: tabs,
                    meta: {
                        'data-min-height': 250
                    }
                });

                container_content = tabs;
            } else {
                if (undefined === sections) {
                    sections = [];
                }

                // Replace the array with rendered fields.
                _.each(data.fields, function (fieldArgs) {
                    // Store the field by id for easy lookup later.
                    form_fields[fieldArgs.id] = fieldArgs;

                    field = fieldArgs;
                    if (undefined !== values[fieldArgs.id]) {
                        field.value = values[fieldArgs.id];
                    }

                    sections.push(PUM_Admin.templates.field(field));
                });

                // Render the section.
                container_content = PUM_Admin.templates.section({
                    fields: sections
                });

            }


            $container
                .addClass(container_classes.join('  '))
                .data('form_fields', form_fields)
                .html(container_content)
                .trigger('pum_init');
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.forms = forms;

    $(document)
        .on('pum_init  pum_form_check', function () {
            forms.init();
        })
        .on('pumFieldChanged', '.pum-field', function () {
            var $wrapper            = $(this),
                dependent_field_ids = $wrapper.data('pum-field-dependents') || [],
                $dependent_fields   = $(),
                i;

            if (!dependent_field_ids || dependent_field_ids.length <= 0) {
                return;
            }

            for (i = 0; i < dependent_field_ids.length; i++) {
                $dependent_fields = $dependent_fields.add('.pum-field[data-id="' + dependent_field_ids[i] + '"]');
            }

            forms.checkDependencies($dependent_fields);
        })
        .on('pumFieldChanged', '.pum-field-dynamic-desc', function () {
            var $this       = $(this),
                $input      = $this.find(':input'),
                val         = $input.val(),
                form_fields = $this.data('form_fields') || {},
                field       = form_fields[$this.data('id')] || {},
                $desc       = $this.find('.pum-desc'),
                desc        = $this.data('pum-dynamic-desc');

            switch (field.type) {
            case 'radio':
                val = $this.find(':input:checked').val();
                break;
            }

            field.value = val;

            if (desc && desc.length) {
                $desc.html(PUM_Admin.templates.renderInline(desc, field));
            }
        })
        .on('change', '.pum-field-select select', function () {
            $(this).parents('.pum-field').trigger('pumFieldChanged');
        })
        .on('click', '.pum-field-checkbox input', function () {
            $(this).parents('.pum-field').trigger('pumFieldChanged');
        })
        .on('click', '.pum-field-radio input', function (event) {
            var $this     = $(this),
                $selected = $this.parents('li'),
                $wrapper  = $this.parents('.pum-field');

            $wrapper.trigger('pumFieldChanged');

            $wrapper.find('li.pum-selected').removeClass('pum-selected');

            $selected.addClass('pum-selected');
        });

}(jQuery));
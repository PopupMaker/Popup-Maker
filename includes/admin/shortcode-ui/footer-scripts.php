<script type="text/javascript">
    var pum_shortcode_ui = {
        shortcodes: <?php echo json_encode( PUM_Admin_Shortcode_UI::instance()->shortcode_ui_var() ); ?>
    };
    (function ($, undefined) {
        "use strict";

        var I10n = pum_admin.I10n || pum_shortcode_ui.I10n || {},
            shortcodes = pum_shortcode_ui.shortcodes || {},
            base = {
                shortcode_args: {},
                shortcode_data: {},
                initialize: function (options) {
                },
                cleanAttrs: function (attrs) {
                    _.each(attrs, function (v, k) {
                        if (null === v || '' === v) {
                            delete attrs[k];
                        }

                        // Multicheck converts keys to array.
                        if (typeof v === 'object') {
                            attrs[k] = Object.keys(v);
                        }
                    });

                    return attrs;
                },
                template: function (options) {
                    var template = $('#tmpl-pum-shortcode-view-' + this.type),
                        template_opts = {
                            evaluate: /<#([\s\S]+?)#>/g,
                            interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                            escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                            variable: 'attr'
                        },
                        _template;

                    if (!template.length) {
                        return this.text;
                    }

                    _template = _.template(template.html(), null, template_opts);

                    if (options.class) {
                        options.classes = options.class;
                        delete options.class;
                    }

                    options = this.cleanAttrs(options);

                    return _template(options);
                },
                getShortcodeValues: function () {
                    if (typeof this.shortcode === 'undefined' || typeof this.shortcode.attrs === 'undefined') {
                        return {};
                    }

                    return _.extend({}, this.shortcode.attrs.named || {});
                },
                getShortcodeContent: function () {
                    if (typeof this.shortcode === 'undefined') {
                        return '';
                    }

                    return this.shortcode.content || '';
                },
                /**
                 * Return the preview HTML.
                 * If empty, fetches data.
                 *
                 * @return string
                 */
                getContent: function () {
                    if (!this.content) {
                        this.fetch();
                    }
                    return this.content;
                },
                formatShortcode: function (values) {
                    var has_content = this.shortcode_args.has_content,
                        content = this.getShortcodeContent();

                    values = values || this.getShortcodeValues();

                    if (has_content && typeof values._inner_content !== 'undefined') {
                        content = values._inner_content;
                        delete values._inner_content;
                    }

                    values = this.cleanAttrs(values);

                    return PUM_Templates.shortcode({
                        tag: this.type,
                        meta: values,
                        has_content: has_content,
                        content: content
                    })
                },
                /**
                 * Fetch preview.
                 * Async. Sets this.content and calls this.render.
                 *
                 * @return undefined
                 */
                fetch: function () {
                    var self = this;

                    if (!this.fetching) {

                        this.fetching = true;

                        var $template = $('#tmpl-pum-shortcode-view-' + this.type),
                            values = this.getShortcodeValues(),
                            data = {};

                        if (this.shortcode_args.has_content) {
                            values._inner_content = this.getShortcodeContent();
                        }

                        if (!this.shortcode_args.ajax_rendering && $template.length) {
                            this.content = this.template(values);
                            delete this.fetching;
                            this.render();
                        } else {
                            data = {
                                action: 'pum_do_shortcode',
                                post_id: $('#post_ID').val(),
                                tag: this.type,
                                shortcode: this.formatShortcode(),
                                nonce: '<?php echo wp_create_nonce( "pum-shortcode-ui-nonce" ); ?>'
                            };

                            $.post(ajaxurl, data)
                                .done(function (response) {
                                    self.content = response.data;
                                })
                                .fail(function () {
                                    self.content = '<span class="pum_shortcode_ui_error">' + I10n.error_loading_shortcode_preview + '</span>';
                                })
                                .always(function () {
                                    delete self.fetching;
                                    self.render();
                                });
                        }
                    }
                },
                edit: function (text, update) {
                    var values = _.extend({}, this.getShortcodeValues());

                    if (this.shortcode_args.has_content) {
                        values._inner_content = this.getShortcodeContent();
                    }

                    this.renderForm(values, update);
                },
                setLoader: function () {
                    this.setContent(
                        '<div class="loading-placeholder">' +
                        '<div class="dashicons dashicons-admin-generic"></div>' +
                        '<div class="wpview-loading"><ins></ins></div>' +
                        '</div>'
                    );
                },
                renderForm: function (values, callback) {
                    var self = this,
                        editor = tinyMCE.activeEditor,
                        modal,
                        tabs = {},
                        sections = {},
                        field,
                        data = $.extend(true, {}, {
                            tag: this.type,
                            id: 'pum-shortcode-editor-' + this.type,
                            label: '',
                            fields: {}
                        }, self.shortcode_args);

                    if (undefined === values) {
                        values = {};
                    }

                    // Fields come already arranged by section. Loop Sections then Fields.
                    _.each(data.fields, function (sectionFields, sectionID) {

                        if (undefined === sections[sectionID]) {
                            sections[sectionID] = [];
                        }

                        // Replace the array with rendered fields.
                        _.each(sectionFields, function (fieldArgs, fieldKey) {
                            field = fieldArgs;
                            if (undefined !== values[fieldArgs.id]) {
                                field.value = values[fieldArgs.id];
                            }

                            // Add unique prefix to IDs to prevent bad behavior.
                            field.id = 'pum_shortcode_attrs_' + field.id;

                            sections[sectionID].push(PUM_Templates.field(field));
                        });

                        // Render the section.
                        sections[sectionID] = PUM_Templates.section({
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
                    tabs = PUM_Templates.tabs({
                        id: data.id,
                        classes: '',
                        tabs: tabs
                    });

                    // Render Modal
                    modal = PUM_Templates.modal({
                        id: data.id,
                        title: data.label,
                        description: data.description,
                        save_button: undefined === values ? I10n.insert : I10n.update,
                        classes: 'tabbed-content pum-shortcode-editor',
                        content: tabs,
                        meta: {
                            'data-shortcode_tag': this.type
                        }
                    });

                    PUMModals.reload('#' + data.id, modal, function () {
                        var modal = $('#' + data.id);

                        modal.find('.pum-form').submit(function (event) {

                            event.preventDefault();

                            var $form = $(this),
                                values = $form.pumSerializeObject(),
                                content = self.formatShortcode(values.attrs);

                            callback(content);

                            PUMModals.closeAll(function () {
                                $(modal).remove();
                            });
                        });
                    });
                }
            };

        $(document).ready(function () {
            wp.mce = wp.mce || {};
            wp.mce.pum_shortcodes = wp.pum_shortcodes || {};

            $.each(shortcodes, function (tag, args) {
                var extend = _.extend({}, base, {
                    shortcode_args: args,
                    View: { // before WP 4.2:
                        type: tag,
                        template: function (options) {
                            return wp.mce.pum_shortcodes[this.type].template(options);
                        },
                        postID: $('#post_ID').val(),
                        initialize: function (options) {
                            this.shortcode = options.shortcode;
                            wp.mce.pum_shortcodes[this.type].shortcode_data = this.shortcode;
                        },
                        getHtml: function () {
                            var values = this.shortcode.attrs.named;
                            if (this.shortcode_args.has_content) {
                                values['_inner_content'] = this.shortcode.content;
                            }
                            return this.template(values);
                        }
                    }
                });

                wp.mce.pum_shortcodes[tag] = extend;

                wp.mce.views.register(tag, extend);
            });

        });

    }(jQuery));
</script>
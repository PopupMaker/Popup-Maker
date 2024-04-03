/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

(function ($) {
    "use strict";

    if (window.pum_shortcode_ui_vars === undefined) {
        return;
    }

    var I10n = pum_shortcode_ui_vars.I10n || {
            error_loading_shortcode_preview: '',
            shortcode_ui_button_tooltip: '',
            insert: '',
            update: ''
        },
        shortcodes = pum_shortcode_ui_vars.shortcodes || {},
        base = {
            version: 1,
            shortcode_args: {},
            shortcode_data: {},
            initialize: function (options) {
            },
            /**
             * Returns cleaned attributes object.
             *
             * @param attrs
             * @returns {*}
             */
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
            /**
             * Renders preview from template when available.
             *
             * @param attrs
             */
            template: function (attrs) {
                var template = 'pum-shortcode-view-' + this.type,
                    _template,
                    options = {
                        evaluate: /<#([\s\S]+?)#>/g,
                        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                        escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                        variable: 'attrs'
                    };

                if (this.version === 1) {
                    options.variable = 'attr';
                }


                if (!$('#tmpl-' + template).length) {
                    return this.text;
                }

                _template = _.template($('#tmpl-' + template).html(), options);

                if (attrs.class) {
                    attrs.classes = attrs.class;
                    delete attrs.class;
                }

                attrs = this.cleanAttrs(attrs);

                return _template(attrs);
            },
            /**
             * Get shortcode attr values.
             *
             * @returns {*}
             */
            getShortcodeValues: function () {
                if (typeof this.shortcode === 'undefined' || typeof this.shortcode.attrs === 'undefined') {
                    return {};
                }

                var values = {};

                if (typeof this.shortcode.attrs.named !== 'undefined') {
                    values = _.extend(values, this.shortcode.attrs.named || {});
                }

                if (typeof this.shortcode.attrs.numeric !== 'undefined') {
                    for (var i = 0; i < this.shortcode.attrs.numeric.length; i++) {
                        values[this.shortcode.attrs.numeric[i]] = true;
                    }
                }

                return values;
            },
            /**
             * Get shortcode raw content.
             *
             * @returns {string}
             */
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
            /**
             * Format shortcode for text tab.
             *
             * @param values
             */
            formatShortcode: function (values) {
                var has_content = this.shortcode_args.has_content,
                    content = this.getShortcodeContent();

                values = values || this.getShortcodeValues();

                if (has_content && typeof values._inner_content !== 'undefined') {
                    content = values._inner_content;
                    delete values._inner_content;
                }

                values = this.cleanAttrs(values);

                return PUM_Admin.templates.shortcode({
                    tag: this.type,
                    meta: values,
                    has_content: has_content,
                    content: content
                });
            },
            /**
             * Fetch preview.
             * Async. Sets this.content and calls this.render.
             *
             * @return undefined
             */
            fetch: function () {
                var self = this,
                    values = self.getShortcodeValues(),
                    data = {
                        action: 'pum_do_shortcode',
                        post_id: $('#post_ID').val(),
                        tag: self.type,
                        shortcode: self.formatShortcode(),
                        nonce: pum_shortcode_ui_vars.nonce
                    };

                if (!self.fetching) {

                    self.fetching = true;

                    /*
                     * If shortcode has inner content, pass that to the renderer.
                     */
                    if (self.shortcode_args.has_content) {
                        values._inner_content = self.getShortcodeContent();
                    }

                    /*
                     * Render templates immediately when available.
                     * Otherwise request rendering via ajax.
                     */
                    if (!self.shortcode_args.ajax_rendering) {
                        self.content = self.template(values);
                        delete self.fetching;
                        self.render();
                    } else {
                        $.post(ajaxurl, data)
                            .done(function (response) {
                                self.content = response.data;
                            })
                            .fail(function () {
                                self.content = '<span class="pum_shortcode_ui_vars_error">' + I10n.error_loading_shortcode_preview + '</span>';
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
            /**
             * Renders loading placeholder.
             */
            setLoader: function () {
                this.setContent(
                    '<div class="loading-placeholder">' +
                    '<div class="dashicons dashicons-admin-generic"></div>' +
                    '<div class="wpview-loading"><ins></ins></div>' +
                    '</div>'
                );
            },
            /**
             * Render the shortcode edit form.
             *
             * @param values
             * @param callback
             */
            renderForm: function (values, callback) {
                var self = this,
                    data = $.extend(true, {}, {
                        tag: this.type,
                        id: 'pum-shortcode-editor-' + this.type,
                        label: '',
                        tabs: {},
                        sections: {},
                        fields: {}
                    }, self.shortcode_args);

                values = values || {};

                PUM_Admin.modals.reload('#' + data.id, PUM_Admin.templates.modal({
                    id: data.id,
                    title: data.label,
                    description: data.description,
                    classes: 'tabbed-content pum-shortcode-editor',
                    save_button: undefined === values ? I10n.insert : I10n.update,
                    content: PUM_Admin.forms.render({
                        id: 'pum-shortcode-editor-' + this.type,
                        tabs: data.tabs || {},
                        sections: data.sections || {},
                        fields: data.fields || {}
                    }, values || {}),
                    meta: {
                        'data-shortcode_tag': this.type
                    }
                }));

                $('#' + data.id + ' form').on('submit', function (event) {
                    event.preventDefault();

                    var $form = $(this),
                        raw_values = $form.pumSerializeObject(),
                        values = PUM_Admin.forms.parseValues($form.pumSerializeObject().attrs, PUM_Admin.forms.flattenFields(data)),
                        content;

                    content = self.formatShortcode(values);

                    if (typeof callback === 'function') {
                        callback(content);
                    }

                    PUM_Admin.modals.closeAll();
                });


            }
        };

    $(document)
        .on('pumFormDependencyMet pumFormDependencyUnmet', '.pum-shortcode-editor .pum-field', function (event) {
            var $input = $(this).find(':input');

            if (event.type.toString() === 'pumFormDependencyUnmet') {
                $input.prop('disabled', true);
            } else {
                $input.prop('disabled', false);
            }
        });

	// Initiate when ready.
	$(function () {
		window.wp = window.wp || {};
		window.wp.mce = window.wp.mce || {};
		window.wp.mce.pum_shortcodes = window.wp.mce.pum_shortcodes || {};

		_.each(shortcodes, function (args, tag) {

			/**
			 * Create and store a view object for each shortcode.
			 *
			 * @type Object
			 */
			wp.mce.pum_shortcodes[tag] = _.extend({}, base, {
				version: args.version || 1,
				shortcode_args: args,
				/**
				 * For compatibility with WP prior to v4.2:
				 */
				View: { //
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
							values._inner_content = this.shortcode.content;
						}
						return this.template(values);
					}
				}

			});

			/**
			 * Register each view with MCE.
			 */
			if (typeof wp.mce.views !== 'undefined' && typeof wp.mce.views.register === 'function') {
				wp.mce.views.register(tag, wp.mce.pum_shortcodes[tag]);
			}
		});
	});

}(jQuery));

/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {

    if (typeof window.pum_newsletter_initialized !== 'undefined') {
        return;
    }

    window.pum_newsletter_initialized = true;

    /**
     * Checks shortcode editor provider field and hides/shows the appropriate subtab for that provider.
     */
    function check_provider() {
        var $provider = $('#pum-shortcode-editor-pum_sub_form #provider'),
            provider = $provider.val() !== '' && $provider.val() !== 'none' ? $provider.val() : pum_admin_vars.default_provider,
            $provider_tabs = $('.pum-modal-content .tabs .tab a[href^="#pum-shortcode-editor-pum_sub_form_provider_"]'),
            $provider_contents = $('[id^="pum-shortcode-editor-pum_sub_form_provider_"]'),
            $selected_tab = $provider_tabs.filter('[href="#pum-shortcode-editor-pum_sub_form_provider_' + provider + '"]'),
            $selected_contents = $provider_contents.filter('[id="pum-shortcode-editor-pum_sub_form_provider_' + provider + '"]');

        $provider_tabs.each(function () {
            $(this).parent().hide();
        });

        $provider_contents.find(':input').attr('disable', true);

        if ($selected_tab.length) {
            $selected_tab.parent().show();
            $selected_contents.find(':input').attr('disable', false);
        }
    }

    $(document)
        .on('pum_init', '#pum-shortcode-editor-pum_sub_form', check_provider)
        .on('change', '#pum-shortcode-editor-pum_sub_form #provider', check_provider);

    /**
     * Here for compatibility with the MC extension prior to v1.3.0
     */
    function check_list() {
        var $list_id = $('#pum-shortcode-editor-pum_sub_form_provider_mailchimp #list_id'),
            list_id = $list_id.val(),
            $list_options = $('#pum-mci-list-' + list_id+',.pum-mci-list-' + list_id),
            $all_options = $('.pum-mci-list-options');

        $all_options.hide();
        $all_options.find('input[type="checkbox"]').attr('disabled', true);

        if ($list_options.length) {
            $list_options.show();
            $list_options.find('input[type="checkbox"]').attr('disabled', false);
        }
    }

    /**
     * Check API key when the "Check" button is clicked.
     */
    $(document)
        .on('pumInit pum_init', '#pum-shortcode-editor-pum_sub_form', check_list)
        .on('change', '#pum-shortcode-editor-pum_sub_form_provider_mailchimp #list_id', check_list);


}(jQuery));
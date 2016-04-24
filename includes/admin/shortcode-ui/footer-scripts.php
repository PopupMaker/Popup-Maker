<script type="text/javascript">
	var pum_shortcode_ui = {
		shortcodes: <?php echo json_encode( PUM_Admin_Shortcode_UI::instance()->shortcode_ui_var() ); ?>
	};
	(function ($, undefined) {
		"use strict";

		var media = wp.media,
			I10n = pum_admin.I10n || pum_shortcode_ui.I10n || {},
			shortcodes = pum_shortcode_ui.shortcodes || {};

		wp.mce = wp.mce || {};

		$.each(shortcodes, function (tag, args) {

			wp.mce[tag] = {
				shortcode_args: args,
				shortcode_data: {},
				cleanAttrs: function (attrs) {

					_.each(attrs, function (v, k) {
						if (null === v || '' === v) {
							delete attrs[k];
						}
					});

					return attrs;
				},
				template: function (options) {
					var template = $('#tmpl-pum-shortcode-view-' + tag),
						template_opts = {
							evaluate:    /<#([\s\S]+?)#>/g,
							interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
							escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
							variable:    'attr'
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
				getContent: function () {
					var values = this.shortcode.attrs.named;
					if (this.shortcode_args.has_content) {
						values['_inner_content'] = this.shortcode.content;
					}
					return this.template(values);
				},
				View: { // before WP 4.2:
					template: function (options) {
						return wp.mce[tag].template(options);
					},
					postID: $('#post_ID').val(),
					initialize: function (options) {
						this.shortcode = options.shortcode;
						wp.mce[tag].shortcode_data = this.shortcode;
					},
					getHtml: function () {
						var values = this.shortcode.attrs.named;
						if (this.shortcode_args.has_content) {
							values['_inner_content'] = this.shortcode.content;
						}
						return this.template(values);
					}
				},
				edit: function (data, update) {
					var shortcode_data = wp.shortcode.next(tag, data),
						values = shortcode_data.shortcode.attrs.named;

					if (this.shortcode_args.has_content) {
						values['_inner_content'] = shortcode_data.shortcode.content;
					}
					wp.mce[tag].openModal(tinyMCE.activeEditor, values);
				},
				// this is called from our tinymce plugin, also can call from our "edit" function above
				// wp.mce[tag].popupwindow(tinyMCE.activeEditor, "bird");
				openModal: function (editor, values) {
					return this.renderForm(editor, values);
				},
				renderForm: function (editor, values) {
					var self = this,
						modal,
						tabs = {},
						sections = {},
						field,
						data = $.extend(true, {}, {
							tag: tag,
							id: 'pum-shortcode-editor-' + tag,
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
							'data-shortcode_tag': tag
						}
					});

					PUMModals.reload('#' + data.id, modal, function () {
						var modal = $('#' + data.id);

						modal.find('.pum-form').submit(function (e) {
							e.preventDefault();
							self.updateShortcode(this, editor);
							PUMModals.closeAll(function () {
								$(modal).remove();
							});
						});
					});
				},
				updateShortcode: function (form, editor) {
					var self = this,
						$form = $(form),
						values = $form.serializeObject().attrs,
						has_content = self.shortcode_args.has_content,
						content;

					if (has_content) {
						content = values._inner_content;
						delete values._inner_content;
					}

					values = self.cleanAttrs(values);

					//insert shortcode to tinymce
					editor.insertContent(PUM_Templates.shortcode({
						tag: tag,
						meta: values,
						has_content: has_content,
						content: content
					}));
				}

			};

			if (wp.mce.views !== undefined && typeof wp.mce.views.register === 'function') {
				wp.mce.views.register(tag, wp.mce[tag]);
			}
		});

	}(jQuery));
</script>
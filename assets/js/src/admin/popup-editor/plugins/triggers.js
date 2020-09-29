(function($, document, undefined) {
	"use strict";

	var I10n = pum_admin_vars.I10n;

	var triggers = {
		current_editor: null,
		new_cookie: false,
		get_triggers: function() {
			return window.pum_popup_settings_editor.triggers;
		},
		get_trigger: function(type) {
			var triggers = this.get_triggers(),
				trigger =
					triggers[type] !== "undefined" ? triggers[type] : false;

			if (!trigger) {
				return false;
			}

			if (
				trigger &&
				typeof trigger === "object" &&
				typeof trigger.fields === "object" &&
				Object.keys(trigger.fields).length
			) {
				trigger = this.parseFields(trigger);
			}

			return trigger;
		},
		parseFields: function(trigger) {
			_.each(trigger.fields, function(fields, tabID) {
				_.each(fields, function(field, fieldID) {
					trigger.fields[tabID][fieldID].name =
						"trigger_settings[" + fieldID + "]";

					if (trigger.fields[tabID][fieldID].id === "") {
						trigger.fields[tabID][fieldID].id =
							"trigger_settings_" + fieldID;
					}
				});
			});

			return trigger;
		},
		parseValues: function(values, type) {
			for (var key in values) {
				if (!values.hasOwnProperty(key)) {
					continue;
				}

				// Clean measurement fields.
				if (values.hasOwnProperty(key + "_unit")) {
					values[key] += values[key + "_unit"];
					delete values[key + "_unit"];
				}
			}

			return values;
		},
		select_list: function() {
			var i,
				_triggers = PUM_Admin.utils.object_to_array(
					triggers.get_triggers()
				),
				options = {};

			for (i = 0; i < _triggers.length; i++) {
				options[_triggers[i].id] = _triggers[i].name;
			}

			return options;
		},
		rows: {
			add: function(editor, trigger) {
				var $editor = $(editor),
					data = {
						index:
							trigger.index !== null && trigger.index >= 0
								? trigger.index
								: $editor.find("table.list-table tbody tr")
										.length,
						type: trigger.type,
						name: $editor.data("field_name"),
						settings: trigger.settings || {}
					},
					$row = $editor.find("tbody tr").eq(data.index),
					$new_row = PUM_Admin.templates.render(
						"pum-trigger-row",
						data
					);

				if ($row.length) {
					$row.replaceWith($new_row);
				} else {
					$editor.find("tbody").append($new_row);
				}

				$editor.addClass("has-list-items");

				triggers.renumber();
				triggers.refreshDescriptions();
			},
			remove: function($trigger) {
				var $editor = $trigger.parents(".pum-popup-trigger-editor");

				$trigger.remove();
				triggers.renumber();

				if ($editor.find("table.list-table tbody tr").length === 0) {
					$editor.removeClass("has-list-items");

					$("#pum-first-trigger")
						.val(null)
						.trigger("change");
				}
			}
		},
		template: {
			form: function(type, values, callback) {
				var trigger = triggers.get_trigger(type),
					modalID = "pum_trigger_settings",
					firstTab = Object.keys(trigger.fields)[0],
					$cookies = $(".pum-field-cookies .list-table tbody tr");

				values = values || {};
				values.type = type;
				values.index = values.index >= 0 ? values.index : null;

				// Add hidden index & type fields.
				trigger.fields[firstTab] = $.extend(
					true,
					trigger.fields[firstTab],
					{
						index: {
							type: "hidden",
							name: "index"
						},
						type: {
							type: "hidden",
							name: "type"
						}
					}
				);

				$cookies.each(function() {
					var settings = JSON.parse(
						$(this)
							.find(".popup_cookies_field_settings:first")
							.val()
					);
					if (
						typeof trigger.fields[firstTab].cookie_name.options[
							settings.name
						] === "undefined"
					) {
						trigger.fields[firstTab].cookie_name.options[
							settings.name
						] = settings.name;
					}
				});

				PUM_Admin.modals.reload(
					"#" + modalID,
					PUM_Admin.templates.modal({
						id: modalID,
						title: trigger.modal_title || trigger.name,
						classes: "tabbed-content",
						save_button:
							values.index !== null ? I10n.update : I10n.add,
						content: PUM_Admin.forms.render(
							{
								id: "pum_trigger_settings_form",
								tabs: trigger.tabs || {},
								fields: trigger.fields || {}
							},
							values || {}
						)
					})
				);

				$("#" + modalID + " form").on(
					"submit",
					callback ||
						function(event) {
							event.preventDefault();
							PUM_Admin.modals.closeAll();
						}
				);
			},
			editor: function(args) {
				var data = $.extend(
					true,
					{},
					{
						triggers: [],
						name: ""
					},
					args
				);

				data.triggers = PUM_Admin.utils.object_to_array(data.triggers);

				return PUM_Admin.templates.render("pum-trigger-editor", data);
			},
			row: function(args) {
				var data = $.extend(
					true,
					{},
					{
						index: "",
						type: "",
						name: "",
						settings: {
							cookie_name: ""
						}
					},
					args
				);

				return PUM_Admin.templates.render("pum-trigger-row", data);
			},
			selectbox: function(args) {
				var data = $.extend(
					true,
					{},
					{
						id: null,
						name: null,
						type: "select",
						group: "",
						index: "",
						value: null,
						select2: true,
						classes: [],
						options: triggers.select_list()
					},
					args
				);

				if (data.id === null) {
					data.id = "popup_settings_triggers_" + data.index + "_type";
				}

				if (data.name === null) {
					data.name =
						"popup_settings[triggers][" + data.index + "][type]";
				}

				return PUM_Admin.templates.field(data);
			}
		},
		/* @deprecated */
		getLabel: function(type) {
			var trigger = triggers.get_trigger(type);

			if (!trigger) {
				return false;
			}

			return trigger.name;
		},
		getSettingsDesc: function(type, values) {
			var trigger = triggers.get_trigger(type);

			if (!trigger) {
				return false;
			}

			return PUM_Admin.templates.renderInline(
				trigger.settings_column,
				values
			);
		},
		renumber: function() {
			$(".pum-popup-trigger-editor table.list-table tbody tr").each(
				function() {
					var $this = $(this),
						index = $this
							.parent()
							.children()
							.index($this);

					$this.attr("data-index", index).data("index", index);

					$this.find(":input, [name]").each(function() {
						if (this.name && this.name !== "") {
							this.name = this.name.replace(
								/\[\d*?\]/,
								"[" + index + "]"
							);
						}
					});
				}
			);
		},
		refreshDescriptions: function() {
			$(".pum-popup-trigger-editor table.list-table tbody tr").each(
				function() {
					var $row = $(this),
						type = $row.find(".popup_triggers_field_type").val(),
						values = JSON.parse(
							$row
								.find(".popup_triggers_field_settings:first")
								.val()
						),
						cookie_text = PUM_Admin.triggers.cookie_column_value(
							values.cookie_name
						);

					$row.find("td.settings-column").html(
						PUM_Admin.triggers.getSettingsDesc(type, values)
					);
					$row.find("td.cookie-column code").text(cookie_text);
				}
			);
		},
		cookie_column_value: function(cookie_name) {
			var cookie_text = I10n.no_cookie;

			if (cookie_name instanceof Array) {
				cookie_text = cookie_name.join(", ");
			} else if (
				cookie_name !== null &&
				cookie_name !== undefined &&
				cookie_name !== ""
			) {
				cookie_text = cookie_name;
			}
			return cookie_text;
		},
		append_click_selector_presets: function() {
			var $field = $("#extra_selectors"),
				template,
				$presets;

			if (
				!$field.length ||
				$field.hasClass("pum-click-selector-presets-initialized")
			) {
				return;
			}

			template = PUM_Admin.templates.render("pum-click-selector-presets");
			$presets = $field
				.parents(".pum-field")
				.find(".pum-click-selector-presets");

			if (!$presets.length) {
				$field.before(template);
				$field.addClass("pum-click-selector-presets-initialized");
				$presets = $field
					.parents(".pum-field")
					.find(".pum-click-selector-presets");
			}

			$presets.position({
				my: "right center",
				at: "right center",
				of: $field
			});
		},
		toggle_click_selector_presets: function() {
			$(this)
				.parent()
				.toggleClass("open");
		},
		reset_click_selector_presets: function(e) {
			if (
				e !== undefined &&
				$(e.target).parents(".pum-click-selector-presets").length
			) {
				return;
			}

			$(".pum-click-selector-presets").removeClass("open");
		},
		insert_click_selector_preset: function() {
			var $this = $(this),
				$input = $("#extra_selectors"),
				val = $input.val();

			if (val !== "") {
				val = val + ", ";
			}

			$input.val(val + $this.data("preset"));
			PUM_Admin.triggers.reset_click_selector_presets();
		}
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.triggers = triggers;

	$(document)
		.on("pum_init", function() {
			PUM_Admin.triggers.append_click_selector_presets();
			PUM_Admin.triggers.refreshDescriptions();
		})
		.on(
			"click",
			".pum-click-selector-presets > span",
			PUM_Admin.triggers.toggle_click_selector_presets
		)
		.on(
			"click",
			".pum-click-selector-presets li",
			PUM_Admin.triggers.insert_click_selector_preset
		)
		.on("click", PUM_Admin.triggers.reset_click_selector_presets)
		/**
		 * @deprecated 1.7.0
		 */
		.on(
			"select2:select pumselect2:select",
			"#pum-first-trigger",
			function() {
				var $this = $(this),
					$editor = $this.parents(".pum-popup-trigger-editor"),
					type = $this.val(),
					values = {};

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				if (type !== "click_open") {
					values.cookie_name = "pum-" + $("#post_ID").val();
				}

				triggers.template.form(type, values, function(event) {
					var $form = $(this),
						type = $form.find("input#type").val(),
						values = $form.pumSerializeObject(),
						trigger_settings = triggers.parseValues(
							values.trigger_settings || {}
						),
						index = parseInt(values.index);

					event.preventDefault();

					if (index === false || index < 0) {
						index = $editor.find("tbody tr").length;
					}

					triggers.rows.add($editor, {
						index: index,
						type: type,
						settings: trigger_settings
					});

					PUM_Admin.modals.closeAll();

					if (
						trigger_settings.cookie_name !== undefined &&
						trigger_settings.cookie_name !== null &&
						(trigger_settings.cookie_name === "add_new" ||
							trigger_settings.cookie_name.indexOf("add_new") >=
								0)
					) {
						PUM_Admin.triggers.new_cookie = values.index;
						$(
							"#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new"
						).trigger("click");
					}
				});

				$this.val(null).trigger("change");
			}
		)
		// Add New Triggers
		.on("click", ".pum-popup-trigger-editor .pum-add-new", function() {
			PUM_Admin.triggers.current_editor = $(this).parents(
				".pum-popup-trigger-editor"
			);
			var template = wp.template("pum-trigger-add-type");
			PUM_Admin.modals.reload(
				"#pum_trigger_add_type_modal",
				template({ I10n: I10n })
			);
		})
		.on("click", ".pum-popup-trigger-editor .edit", function(event) {
			var $this = $(this),
				$editor = $this.parents(".pum-popup-trigger-editor"),
				$row = $this.parents("tr:first"),
				type = $row.find(".popup_triggers_field_type").val(),
				values = _.extend(
					{},
					JSON.parse(
						$row.find(".popup_triggers_field_settings:first").val()
					),
					{
						index: $row
							.parent()
							.children()
							.index($row),
						type: type
					}
				);

			event.preventDefault();

			triggers.template.form(type, values, function(event) {
				var $form = $(this),
					type = $form.find("input#type").val(),
					index = $form.find("input#index").val(),
					values = $form.pumSerializeObject(),
					trigger_settings = triggers.parseValues(
						values.trigger_settings || {}
					);

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				event.preventDefault();

				if (index === false || index < 0) {
					index = $editor.find("tbody tr").length;
				}

				triggers.rows.add($editor, {
					index: index,
					type: type,
					settings: trigger_settings
				});

				PUM_Admin.modals.closeAll();

				if (
					trigger_settings.cookie_name !== undefined &&
					trigger_settings.cookie_name !== null &&
					(trigger_settings.cookie_name === "add_new" ||
						trigger_settings.cookie_name.indexOf("add_new") >= 0)
				) {
					PUM_Admin.triggers.new_cookie = values.index;
					$(
						"#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new"
					).trigger("click");
				}
			});
		})
		.on("click", ".pum-popup-trigger-editor .remove", function(event) {
			var $this = $(this),
				$editor = $this.parents(".pum-popup-trigger-editor"),
				$row = $this.parents("tr:first");

			// Set Current Editor.
			PUM_Admin.triggers.current_editor = $editor;

			event.preventDefault();

			if (window.confirm(I10n.confirm_delete_trigger)) {
				triggers.rows.remove($row);
			}
		})
		.on("submit", "#pum_trigger_add_type_modal .pum-form", function(event) {
			var $editor = PUM_Admin.triggers.current_editor,
				$cookie_editor = $editor
					.parents("#pum-popup-settings-triggers-subtabs_main")
					.find(".pum-field-cookies .pum-popup-cookie-editor"),
				type = $("#popup_trigger_add_type").val(),
				add_cookie = $("#popup_trigger_add_cookie").is(":checked"),
				add_cookie_event = $("#popup_trigger_add_cookie_event").val(),
				values = {};

			event.preventDefault();

			if (add_cookie) {
				values.cookie_name = "pum-" + $("#post_ID").val();
				PUM_Admin.cookies.insertCookie($cookie_editor, {
					event: add_cookie_event,
					settings: {
						time: "1 month",
						path: "1",
						name: values.cookie_name
					}
				});
			}

			triggers.template.form(type, values, function(event) {
				var $form = $(this),
					type = $form.find("input#type").val(),
					values = $form.pumSerializeObject(),
					trigger_settings = triggers.parseValues(
						values.trigger_settings || {}
					),
					index = parseInt(values.index);

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				event.preventDefault();

				if (!index || index < 0) {
					index = $editor.find("tbody tr").length;
				}

				triggers.rows.add($editor, {
					index: index,
					type: type,
					settings: trigger_settings
				});

				PUM_Admin.modals.closeAll();

				if (
					trigger_settings.cookie_name !== undefined &&
					trigger_settings.cookie_name !== null &&
					(trigger_settings.cookie_name === "add_new" ||
						trigger_settings.cookie_name.indexOf("add_new") >= 0)
				) {
					PUM_Admin.triggers.new_cookie = values.index;
					$(
						"#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new"
					).trigger("click");
				}
			});
		});
})(jQuery, document);

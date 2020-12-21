(function($) {
	$(document).on('ready', function() {
		PUM_Admin.modals.reload(
			"#template-wizard",
			PUM_Admin.templates.modal({
				id: 'template-wizard',
				title: "Create New Popup",
				classes: "tabbed-content",
				cancel_button: "Skip Wizard",
				save_button: false,
				content: "<div class='template-selectors'><p>Select a template for your popup to get started.</p><label style='display:block;font-size:16px;font-weight:bold;'>Search</label><input style='width:100%;' type='text'><button style='width:30%;min-height:50px;margin-right:5px;' type='button' class='template-selector'>Blank popup</button><button style='width:30%;min-height:50px;' type='button' class='template-selector'>Auto-open popup</button></div>"
			})
		);
		$('#template-wizard').on( 'click', '.template-selector', function(e){
			e.preventDefault();
			$('.template-selectors').replaceWith('<div class="spinner"></div>');
			var $container = $(
				"#pum-popup-settings-container"
			);
			var presetValues = {
				triggers: [{
					type: 'auto_open',
					settings: {
						delay: '500'
					}
				}],
				cookies: [{
					event: "on_popup_close",
					settings: {
						name: 'exmaple',
						path: '1',
						session: false,
						time: '1 month'
					}
				}]
			};
			var args = pum_popup_settings_editor.form_args || {};
			var originalValues = pum_popup_settings_editor.current_values || {};

			var newValues = Object.assign(
				{},
				originalValues,
				presetValues
			);

			// Re-render form using updated settings.
			PUM_Admin.forms.render(args, newValues, $container);

			PUM_Admin.modals.closeAll();
			PUM_Admin.modals.reload(
				"#template-wizard-success",
				PUM_Admin.templates.modal({
					id: 'template-wizard-success',
					title: "Template Applied",
					classes: "tabbed-content",
					save_button: 'Thanks!',
					content: "<div style='padding:10px 5px;'>Popup template has been applied! You can now edit your popup's content.</div>"
				})
			);
			$('#template-wizard-success .pum-submit button').on( 'click', function(e){
				e.preventDefault();
				PUM_Admin.modals.closeAll();
			});
		});
	});
})(jQuery)

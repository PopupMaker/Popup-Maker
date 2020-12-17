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
				content: "<div><label>Search</label><input type='text'><button type='button' class='template-selector'>Auto-open popup</button></div>"
			})
		);
		$('#template-wizard').on( 'click', '.template-selector', function(e){
			e.preventDefault();
			PUM_Admin.modals.closeAll();
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
		});
	});
})(jQuery)

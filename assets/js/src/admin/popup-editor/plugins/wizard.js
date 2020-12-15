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
				content: "<div><label>Search</label><input type='text'></div>"
			})
		);
	});
})(jQuery)

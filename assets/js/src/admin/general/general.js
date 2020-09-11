/********************************************
 * Copyright (c) 2020, Code Atlantic LLC
 ********************************************/

(function($) {
	$(function() {
		$(".pum-active-toggle-button").on("click", function(e) {
			e.preventDefault();
			var popupID = $(this).data("popup-id");
			$.ajax({
				type: "POST",
				dataType: "json",
				url: pum_vars.ajaxurl,
				data: {
					action: "pum_save_active_state",
					popupID: popupID,
					active: 0,
				}
			});
		});
	});
})(jQuery);

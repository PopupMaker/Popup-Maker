/********************************************
 * Copyright (c) 2020, Code Atlantic LLC
 ********************************************/

(function($) {
	/**
	 * Changes the current enabled state of supplied popup
	 *
	 * @param {number} popupID The ID for the popup.
	 * @param {number} enabledState 1 for active, 0 for inactive.
	 * @param {string} nonce The nonce for the action.
	 */
	function changeEnabledState(popupID, enabledState, nonce) {
		$.ajax({
			type: "POST",
			dataType: "json",
			// eslint-disable-next-line no-undef
			url: ajaxurl,
			data: {
				action: "pum_save_enabled_state",
				nonce: nonce,
				popupID: popupID,
				enabled: enabledState
			}
		});
	}

	$(function() {
		$(".pum-enabled-toggle-button").on("change", function(e) {
			e.preventDefault();
			var $button = $(this);
			var newState = 0;
			if (true === e.target.checked) {
				newState = 1;
			}
			changeEnabledState(
				$button.data("popup-id"),
				newState,
				$button.data("nonce")
			);
		});
		$("#screen-meta-links, #screen-meta")
			.prependTo("#pum-header-temp")
			.show();
	});
})(jQuery);

/********************************************
 * Copyright (c) 2020, Code Atlantic LLC
 ********************************************/

(function($) {
	/**
	 * Changes the current active state of supplied popup
	 *
	 * @param {number} popupID The ID for the popup.
	 * @param {number} activeState 1 for active, 0 for inactive.
	 * @param {string} nonce The nonce for the action.
	 */
	function changeActiveState(popupID, activeState, nonce) {
		$.ajax({
			type: "POST",
			dataType: "json",
			// eslint-disable-next-line no-undef
			url: ajaxurl,
			data: {
				action: "pum_save_active_state",
				nonce: nonce,
				popupID: popupID,
				active: activeState
			}
		});
	}

	$(function() {
		$(".pum-active-toggle-button").on("change", function(e) {
			e.preventDefault();
			var $button = $(this);
			var newState = 0;
			if (true === e.target.checked) {
				newState = 1;
			}
			changeActiveState(
				$button.data("popup-id"),
				newState,
				$button.data("nonce")
			);
		});
	});
})(jQuery);

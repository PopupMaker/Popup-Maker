/*******************************************************************************
 * Copyright (c) 2020, Code Atlantic LLC
 ******************************************************************************/
(function($) {
	"use strict";

	window.PUM_Admin = window.PUM_Admin || {};

	// Initiate when ready.
	$(function() {
		$('a[href="edit.php?post_type=popup&page=pum-extensions"]').css({
			color: "#a0d468"
		});
	});
})(jQuery);

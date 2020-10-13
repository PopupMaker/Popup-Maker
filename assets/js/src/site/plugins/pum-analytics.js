/**
 * Defines the core pum analytics methods.
 * Version 1.4
 */

var PUM_Analytics;
(function($) {
	"use strict";

	$.fn.popmake.last_open_trigger = null;
	$.fn.popmake.last_close_trigger = null;
	$.fn.popmake.conversion_trigger = null;

	var rest_enabled = !!(
		typeof pum_vars.restapi !== "undefined" && pum_vars.restapi
	);

	PUM_Analytics = {
		beacon: function(data, callback) {
			var beacon = new Image(),
				url = rest_enabled ? pum_vars.restapi : pum_vars.ajaxurl,
				opts = {
					route: pum.hooks.applyFilters(
						"pum.analyticsBeaconRoute",
						"/analytics/"
					),
					data: pum.hooks.applyFilters(
						"pum.AnalyticsBeaconData",
						$.extend(
							true,
							{
								event: "open",
								pid: null,
								_cache: +new Date()
							},
							data
						)
					),
					callback:
						typeof callback === "function"
							? callback
							: function() {}
				};

			if (!rest_enabled) {
				opts.data.action = "pum_analytics";
			} else {
				url += opts.route;
			}

			// Create a beacon if a url is provided
			if (url) {
				// Attach the event handlers to the image object
				$(beacon).on("error success load done", opts.callback);

				// Attach the src for the script call
				beacon.src = url + "?" + $.param(opts.data);
			}
		}
	};

	if (
		typeof pum_vars.disable_tracking === "undefined" ||
		!pum_vars.disable_tracking
	) {
		// Only popups from the editor should fire analytics events.
		$(document)
			/**
			 * Track opens for popups.
			 */
			.on("pumAfterOpen.core_analytics", ".pum", function() {
				var $popup = PUM.getPopup(this),
					data = {
						pid:
							parseInt($popup.popmake("getSettings").id, 10) ||
							null
					};

				// Shortcode popups use negative numbers, and single-popup (preview mode) shouldn't be tracked.
				if (data.pid > 0 && !$("body").hasClass("single-popup")) {
					PUM_Analytics.beacon(data);
				}
			});
		/**
		 * Track form submission conversions
		 */
		$(function() {
			PUM.hooks.addAction("pum.integration.form.success", function(form, args ) {
				// If the submission has already been counted in the backend, we can bail early.
				if (args.ajax === false) {
					return;
				}
				var $popup = PUM.getPopup(form),
					data = {
						pid:
							parseInt($popup.popmake("getSettings").id, 10) ||
							null,
						event: "conversion"
					};

				// Shortcode popups use negative numbers, and single-popup (preview mode) shouldn't be tracked.
				if (
					$popup.length &&
					data.pid > 0 &&
					!$("body").hasClass("single-popup")
				) {
					PUM_Analytics.beacon(data);
				}
			});
		});
	}
})(jQuery);

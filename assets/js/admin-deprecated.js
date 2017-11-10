/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    window.PUMModals = window.PUM_Admin.modals;
    window.PUMColorPickers = window.PUM_Admin.colorpicker;
    window.PUM_Templates = window.PUM_Admin.templates;
    window.PUMUtils = window.PUM_Admin.utils;

    /** Specific fixes for extensions that may break or need updating. */
    window.PUMTriggers = window.PUM_Admin.triggers || {};
    window.PUMCookies = window.PUM_Admin.cookies || {};

    /* Fix for pum-schedules js error. Remove once updated. */
    window.PUMTriggers.new_schedule = -1;

}(jQuery));
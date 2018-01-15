/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/
(function ($) {

    if (typeof window.pum_newsletter_initialized !== 'undefined') {
        return;
    }

    window.pum_newsletter_initialized = true;

    /**
     * Checks shortcode editor provider field and hides/shows the appropriate subtab for that provider.
     */
    function check_provider() {
        var $provider = $('#pum-shortcode-editor-pum_sub_form #provider'),
            provider = $provider.val() !== '' ? $provider.val() : pum_admin_vars.default_provider,
            $provider_tabs = $('.pum-modal-content .tabs .tab a[href^="#pum-shortcode-editor-pum_sub_form_provider_"]'),
            $provider_contents = $('[id^="pum-shortcode-editor-pum_sub_form_provider_"]'),
            $selected_tab = $provider_tabs.filter('[href="#pum-shortcode-editor-pum_sub_form_provider_' + provider + '"]'),
            $selected_contents = $provider_contents.filter('[id="pum-shortcode-editor-pum_sub_form_provider_' + provider + '"]');

        $provider_tabs.each(function () {
            $(this).parent().hide();
        });

        $provider_contents.find(':input').attr('disable', true);

        if ($selected_tab.length) {
            $selected_tab.parent().show();
            $selected_contents.find(':input').attr('disable', false);
        }
    }

    $(document)
        .on('pum_init', '#pum-shortcode-editor-pum_sub_form', check_provider)
        .on('change', '#pum-shortcode-editor-pum_sub_form #provider', check_provider);

}(jQuery));
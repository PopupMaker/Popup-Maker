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

    /**
     * Here for compatibility with the MC extension prior to v1.3.0
     */
    function check_list() {
        var $list_id = $('#pum-shortcode-editor-pum_sub_form_provider_mailchimp #list_id'),
            list_id = $list_id.val(),
            $list_options = $('#pum-mci-list-' + list_id+',.pum-mci-list-' + list_id),
            $all_options = $('.pum-mci-list-options');

        $all_options.hide();
        $all_options.find('input[type="checkbox"]').attr('disabled', true);

        if ($list_options.length) {
            $list_options.show();
            $list_options.find('input[type="checkbox"]').attr('disabled', false);
        }
    }

    /**
     * Check API key when the "Check" button is clicked.
     */
    $(document)
        .on('pumInit pum_init', '#pum-shortcode-editor-pum_sub_form', check_list)
        .on('change', '#pum-shortcode-editor-pum_sub_form_provider_mailchimp #list_id', check_list);


}(jQuery));
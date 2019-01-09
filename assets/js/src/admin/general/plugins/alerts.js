/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    function dismiss(alert, reason) {
        $.ajax({
            method: "POST",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: 'pum_alerts_action',
                nonce: window.pum_alerts_nonce,
                code: alert.data('code')
            }
        });
    }

    var $alerts = $('.pum-alerts'),
        $notice_counts = $('.pum-alert-count'),
        count = parseInt($notice_counts.eq(0).text());

    $(document)
        .on('click', '.pum-alert-holder .pum-dismiss', function () {
            var $this = $(this),
                alert = $this.parents('.pum-alert-holder');

            count--;

            $notice_counts.text(count);

            alert.fadeTo(100, 0, function () {
                alert.slideUp(100, function () {
                    alert.remove();

                    if ($alerts.find('.pum-alert-holder').length === 0) {
                        $alerts.slideUp(100, function () {
                            $alerts.remove();
                        });

                        $('#menu-posts-popup .wp-menu-name .update-plugins').fadeOut();
                    }
                });
            });

            dismiss(alert);
        });

}(jQuery));
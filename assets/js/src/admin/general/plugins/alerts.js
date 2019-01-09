/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    function dismissAlert($alert) {
        $.ajax({
            method: "POST",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: 'pum_alerts_action',
                nonce: window.pum_alerts_nonce,
                code: $alert.data('code')
            }
        });
    }

    function dismissReviewRequest(reason) {
        $.ajax({
            method: "POST",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: 'pum_review_action',
                nonce: window.pum_review_nonce,
                group: window.pum_review_trigger.group,
                code: window.pum_review_trigger.code,
                pri: window.pum_review_trigger.pri,
                reason: reason
            }
        });

        if (typeof window.pum_review_api_url !== 'undefined') {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: window.pum_review_api_url,
                data: {
                    trigger_group: window.pum_review_trigger.group,
                    trigger_code: window.pum_review_trigger.code,
                    reason: reason,
                    uuid: window.pum_review_uuid || null
                }
            });
        }
    }

    var $alerts = $('.pum-alerts'),
        $notice_counts = $('.pum-alert-count'),
        count = parseInt($notice_counts.eq(0).text());

    function removeAlert($alert) {
        count--;

        $notice_counts.text(count);

        $alert.fadeTo(100, 0, function () {
            $alert.slideUp(100, function () {
                $alert.remove();

                if ($alerts.find('.pum-alert-holder').length === 0) {
                    $alerts.slideUp(100, function () {
                        $alerts.remove();
                    });

                    $('#menu-posts-popup .wp-menu-name .update-plugins').fadeOut();
                }
            });
        });
    }

    $(document)
        .on('click', '.pum-alert-holder .pum-dismiss', function () {
            var $this = $(this),
                $alert = $this.parents('.pum-alert-holder'),
                reason = $this.data('reason') || 'maybe_later';

            if ( 'review_request' !== $alert.data('code')) {
                dismissAlert($alert);
            } else {
                dismissReviewRequest(reason);
            }

            removeAlert($alert);

        });
}(jQuery));
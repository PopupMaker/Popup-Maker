(function () {
    "use strict";
    jQuery(document)
        .on('popmakeInit', '.popmake', function (event) {
            var $this = jQuery(this),
                settings = $this.data('popmake'),
                emodal_id = settings.old_easy_modal_id,
                emodal_trigger = '.eModal-' + emodal_id;
            if (emodal_id !== undefined) {
                jQuery(emodal_trigger).css({cursor: "pointer"});
                jQuery(document).on('click', emodal_trigger, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    jQuery.fn.popmake.last_open_trigger = jQuery.fn.popmake.utilities.getXPath(this);
                    $this.popmake('open');
                });
            }
        })
        .on('popmakeInit', '.popmake', function () {
            jQuery(this).trigger('emodalInit');
        })
        .on('popmakeBeforeOpen', '.popmake', function () {
            jQuery(this).trigger('emodalBeforeOpen');
        })
        .on('popmakeAfterOpen', '.popmake', function () {
            jQuery(this).trigger('emodalAfterOpen');
        })
        .on('popmakeBeforeClose', '.popmake', function () {
            jQuery(this).trigger('emodalBeforeClose');
        })
        .on('popmakeAfterClose', '.popmake', function () {
            jQuery(this).trigger('emodalAfterClose');
        })
        .on('popmakeBeforeReposition', '.popmake', function () {
            jQuery(this).trigger('emodalBeforeReposition');
        })
        .on('popmakeAfterReposition', '.popmake', function () {
            jQuery(this).trigger('emodalAfterReposition');
        })
        .on('popmakeBeforeRetheme', '.popmake', function () {
            jQuery(this).trigger('emodalBeforeRetheme');
        })
        .on('popmakeAfterRetheme', function () {
            jQuery(this).trigger('emodalAfterRetheme');
        })
        .on('popmakeSetupClose', function () {
            jQuery(this).trigger('emodalSetupClose');
        });
}());
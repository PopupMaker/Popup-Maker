(function () {
    "use strict";
    jQuery(document)
        .on('pumInit', '.pum', function (event) {
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
        .on('pumInit', '.pum', function () {
            jQuery(this).trigger('emodalInit');
        })
        .on('pumBeforeOpen', '.pum', function () {
            jQuery(this).trigger('emodalBeforeOpen');
        })
        .on('pumAfterOpen', '.pum', function () {
            jQuery(this).trigger('emodalAfterOpen');
        })
        .on('pumBeforeClose', '.pum', function () {
            jQuery(this).trigger('emodalBeforeClose');
        })
        .on('pumAfterClose', '.pum', function () {
            jQuery(this).trigger('emodalAfterClose');
        })
        .on('pumBeforeReposition', '.pum', function () {
            jQuery(this).trigger('emodalBeforeReposition');
        })
        .on('pumAfterReposition', '.pum', function () {
            jQuery(this).trigger('emodalAfterReposition');
        })
        .on('pumBeforeRetheme', '.pum', function () {
            jQuery(this).trigger('emodalBeforeRetheme');
        })
        .on('pumAfterRetheme', function () {
            jQuery(this).trigger('emodalAfterRetheme');
        })
        .on('pumSetupClose', function () {
            jQuery(this).trigger('emodalSetupClose');
        });
}());
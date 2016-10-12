(function ($) {
    "use strict";

    if (typeof Marionette === 'undefined') {
        return;
    }

    var pumNFController = Marionette.Object.extend({
        initialize: function () {
            this.listenTo(nfRadio.channel('forms'), 'submit:response', this.closePopup);
            this.listenTo(nfRadio.channel('forms'), 'submit:response', this.openPopup);
            this.listenTo(nfRadio.channel('forms'), 'submit:response', this.popupTriggers);
        },
        popupTriggers: function (response, textStatus, jqXHR, formID) {
            var $popup;

            $popup = $('#nf-form-' + formID + '-cont').parents('.pum');

            if ($popup.length) {
                $popup.trigger('pum_nf.success');

                if (response.errors.length) {
                    $popup.trigger('pum_nf.error');
                } else {
                    $popup.trigger('pum_nf.success');
                }
            }
        },
        closePopup: function (response, textStatus, jqXHR, formID) {
            var $popup;

            if ('undefined' === typeof response.data.actions || response.errors.length) {
                return;
            }

            if ('undefined' === typeof response.data.actions.closepopup) {
                return;
            }

            $popup = $('#nf-form-' + formID + '-cont').parents('.pum');

            if ($popup.length) {
                setTimeout(function () {
                    $popup.popmake('close');
                }, parseInt(response.data.actions.closepopup));
            }
        },
        openPopup: function (response) {
            var $popup;

            if ('undefined' === typeof response.data.actions || response.errors.length) {
                return;
            }

            if ('undefined' === typeof response.data.actions.openpopup) {
                return;
            }

            $popup = $('#pum-' + parseInt(response.data.actions.openpopup));

            if ($popup.length) {
                $popup.popmake('open');
            }
        }

    });

    jQuery(document).ready(function () {
        new pumNFController();
    });
}(jQuery));
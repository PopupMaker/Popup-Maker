var PUMModals;
(function ($) {
    "use strict";
    var $html = $('html'),
        $document = $(document);

    PUMModals = {
        closeAll: function () {
            $('.pum-modal-background').hide();
            $('html').css({overflow: 'visible', width: 'auto'});
        },
        show: function (modal) {
            PUMModals.closeAll();
            $html.data('origwidth', $html.innerWidth()).css({overflow: 'hidden', 'width': $html.innerWidth()});
            $(modal).show();
            $document.trigger('pum_init');
        },
        remove: function (modal) {
            $(modal).remove();
        },
        replace: function (modal, replacement) {
            PUMModals.remove(modal);
            $('body').append(replacement);
        },
        reload: function (modal, replacement) {
            PUMModals.replace(modal, replacement);
            PUMModals.show(modal);
        }
    };

    $(document)
        .on('click', '.pum-modal-background, .pum-modal-wrap .cancel, .pum-modal-wrap .pum-modal-close', function (e) {
            var $target = $(e.target);
            if ($target.hasClass('pum-modal-background') || $target.hasClass('cancel') || $target.hasClass('pum-modal-close') || $target.hasClass('submitdelete') ) {
                PUMModals.closeAll();
                e.preventDefault();
                e.stopPropagation();
            }
        });

}(jQuery));
var PUMMarketing;
(function ($, document, undefined) {
    "use strict";

    PUMMarketing = {
        init: function () {
            $('#menu-posts-popup ul li a[href="edit.php?post_type=popup&page=extensions"]').css({color: "#9aba27"});
        }
    };

    $(document).ready(PUMMarketing.init);
}(jQuery, document));
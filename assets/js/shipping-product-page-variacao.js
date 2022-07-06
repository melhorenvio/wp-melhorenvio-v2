(function (jQuery) {
    'use strict';
    jQuery(function () {
        jQuery(document).ready(function () {
            jQuery(document).change(function () {
                let variation_id = jQuery('.variation_id').val();
                if (variation_id) {
                    jQuery('#id_produto').val(variation_id);
                }
            });
        })
    });
})(jQuery);

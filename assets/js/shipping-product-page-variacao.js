(function (jQuery) {
    'use strict';

    jQuery(function () {
        jQuery(document).ready(function () {
            if (!jQuery('.variations_form')) {
                return;
            }
            var variations = jQuery('.variations_form').data('product_variations');
            updateVariation(variations);
            jQuery('.variations select').change(function () {
                updateVariation(variations);
            });
        })
    });

    function updateVariation(variations) {
        var attribbutes = new Array();
        if (jQuery('.variations select').length == 0) {
            return;
        }

        jQuery('.variations select').each(function () {
            var key = jQuery(this).attr('data-attribute_name');
            var value = this.value;
            if (value == "") {
                return;
            }
            attribbutes.push({
                'key': key,
                'value': value
            });
        });

        if (typeof attribbutes == 'undefined' || attribbutes.length == 0) {
            return;
        }

        var selected;
        if (jQuery('.variations select').length == attribbutes.length) {
            variations.map(function (variant, index) {
                attribbutes.map(function (attr) {
                    if (variant.attributes[attr.key] == attr.value) {
                        selected = index;
                    }
                });
            });
        }

        if (typeof selected == 'number') {
            console.log(variations[selected].weight)
            jQuery('#id_produto').val(variations[selected].variation_id)
            jQuery('#calculo_frete_produto_altura').val(variations[selected].dimensions.height)
            jQuery('#calculo_frete_produto_largura').val(variations[selected].dimensions.width)
            jQuery('#calculo_frete_produto_comprimento').val(variations[selected].dimensions.length)
            jQuery('#calculo_frete_produto_peso').val(variations[selected].weight)
        }
    }
})(jQuery);

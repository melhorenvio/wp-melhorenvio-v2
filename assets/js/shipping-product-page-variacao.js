(function (jQuery) {
    'use strict';

    jQuery(function () {
        jQuery(document).ready(function () {
            toggleCalculator();
            if (!jQuery('.variations_form')) {
                return;
            }
            let variations = jQuery('.variations_form').data('product_variations');
            updateVariation(variations);
            jQuery('.variations select').change(function () {
                updateVariation(variations);
            });
        })
    });

    function updateVariation(variations) {
        let attribbutes = new Array();
        if (jQuery('.variations select').length == 0) {
            return;
        }

        jQuery('.variations select').each(function () {
            let key = jQuery(this).attr('data-attribute_name');
            let value = this.value;
            if (value == "") {
                return;
            }
            attribbutes.push({
                'key': key,
                'value': value
            });
        });

        if (typeof attribbutes == 'undefined' || attribbutes.length == 0) {
            setFormData(variations[0]);
            return;
        }

        let selected = 0;
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
            setFormData(variations[selected]);
        }
    }
    function setFormData(variation) {
        jQuery('#id_produto').val(variation.variation_id)
        jQuery('#calculo_frete_produto_altura').val(variation.dimensions.height)
        jQuery('#calculo_frete_produto_largura').val(variation.dimensions.width)
        jQuery('#calculo_frete_produto_comprimento').val(variation.dimensions.length)
        jQuery('#calculo_frete_produto_peso').val(variation.weight)
        toggleCalculator();
    }
})(jQuery);

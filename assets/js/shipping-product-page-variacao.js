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
            resetFormData();
            return;
        }

        let selected = 0;
        if (jQuery('.variations select').length == attribbutes.length) {           
            variations.map(function (variant, index) { 
                let matches = []
                attribbutes.map(function (attr) {
                    if (variant.attributes[attr.key] == attr.value) {
                        matches.push( attr.key )
                    }
                });
                if( matches.length === attribbutes.length ) selected = index;
            });
        }

        if (typeof selected == 'number') {
            setFormData(variations[selected])
        }
    }
    function setFormData(variation) {
        if (!variation.dimensions.width ||
            !variation.dimensions.height ||
            !variation.dimensions.length ||
            variation.dimensions.width == 0 ||
            variation.dimensions.height == 0 ||
            variation.dimensions.length == 0) {
            resetFormData();
            return;
        }

        jQuery('#id_produto').val(variation.variation_id)
        jQuery('#calculo_frete_produto_altura').val(variation.dimensions.height)
        jQuery('#calculo_frete_produto_largura').val(variation.dimensions.width)
        jQuery('#calculo_frete_produto_comprimento').val(variation.dimensions.length)
        jQuery('#calculo_frete_produto_peso').val(variation.weight)
        toggleCalculator();
    }

    function resetFormData() {
        jQuery('#id_produto').val(null)
        jQuery('#calculo_frete_produto_altura').val(null)
        jQuery('#calculo_frete_produto_largura').val(null)
        jQuery('#calculo_frete_produto_comprimento').val(null)
        jQuery('#calculo_frete_produto_peso').val(null)
        toggleCalculator();
    }
})(jQuery);

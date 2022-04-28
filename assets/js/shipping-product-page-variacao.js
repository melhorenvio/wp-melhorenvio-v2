(function (jQuery) {
    'use strict';

    jQuery(function () {
        jQuery(document).ready(function () {
            toggleCalculator();
            setProduct();
            if (!jQuery('.variations_form')) {
                return;
            }
            let variations = jQuery('.variations_form').data('product_variations');
            updateVariation(variations);
            jQuery('.variations select').change(function () {
                setProduct();
                updateVariation(variations);
            });
        })
    });

    function setProduct()
    {
        setTimeout(function() { 
            let variantion_id = jQuery('.variation_id').val();
            let product_id = jQuery('.cart').data('product_id');

            let selected_product = variantion_id;
            if (variantion_id == 0) {
                selected_product = product_id;
            }

           if (typeof product_id != "undefined" && typeof variantion_id != "undefined")
            jQuery('#id_produto').val(selected_product)
         }, 200);
    }

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
                return attribbutes.map(function (attr) {
                    if (variant.attributes[attr.key] == attr.value) {
                        selected = index;
                        return selected;
                    }
                });
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

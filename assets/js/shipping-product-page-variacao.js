(function (jQuery) {
    'use strict';

    jQuery(function () {
        jQuery(document).ready(function () {
            showOrHideCalculatorLessDimensions();
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
            return;
        }

        let selected;
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
            jQuery('#id_produto').val(variations[selected].variation_id)
            jQuery('#calculo_frete_produto_altura').val(variations[selected].dimensions.height)
            jQuery('#calculo_frete_produto_largura').val(variations[selected].dimensions.width)
            jQuery('#calculo_frete_produto_comprimento').val(variations[selected].dimensions.length)
            jQuery('#calculo_frete_produto_peso').val(variations[selected].weight)
            showOrHideCalculatorLessDimensions();
        }
    }

    function getDimensions() {
        let dimensions = {
            'heigth': jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_altura').val(),
            'width': jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_largura').val(),
            'length': jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_comprimento').val(),
            'weight': jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_peso').val()
        }
        return dimensions;
    }

    function showOrHideCalculatorLessDimensions() {
        let dimensions = getDimensions();
        if (!dimensions.width
            || !dimensions.heigth
            || !dimensions.length
            || !dimensions.weight
        ) {
            jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto').css('display', 'none');
            return;
        }
        jQuery('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto').css('display', 'block');
    }

})(jQuery);

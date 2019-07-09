(function( jQuery ) {
	'use strict';

	jQuery(function() {

		jQuery(document).ready(function() {  

			if (typeof(jQuery('.variations_form') == 'undefined') || jQuery('.variations_form').length == 0) {
				return;
			}

			var variations = jQuery('.variations_form').data('product_variations');
			updateVariation(variations);
			
			jQuery('.variations select').change(function() {	
				updateVariation(variations);
			});
		})

	});

	function updateVariation(variations)
	{	
		var attribbutes = new Array();

		if (jQuery('.variations select').length == 0) {
			return;
		}

		jQuery('.variations select').each(function() {
				
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

		if (typeof attribbutes == 'undefined' || attribbutes.length == 0 ) {
			return;
		}

		var selected;
		if (jQuery('.variations select').length ==  attribbutes.length) {
			variations.map(function(variant, index) {
				attribbutes.map(function(attr) {
					if(variant.attributes[attr.key] ==  attr.value) {
						selected = index;
					}
				});
			});
		}

		if(typeof selected == 'number') {
			jQuery('#calculo_frete_produto_altura').val(variations[selected].dimensions.height)
			jQuery('#calculo_frete_produto_largura').val(variations[selected].dimensions.width)
			jQuery('#calculo_frete_produto_comprimento').val(variations[selected].dimensions.length)
			jQuery('#calculo_frete_produto_peso').val(getWeigth(variations[selected]))
		}
	}

	function getWeigth(variant)
	{
		var unit = variant.weight_html;
		var unit = unit.split(' ');

		if (unit[1] == 'g') {
			return eval(variant.weight * 0.001);
		}

		return variant.weigth;
	}

})( jQuery );

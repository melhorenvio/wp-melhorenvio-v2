( function ( $ ) {
	'use strict';

	$( function () { init(); } );

	function init() {
		var $container = $( '#me-cep-calc' );

		if ( ! $container.length ) {
			return;
		}

		var $input       = $( '#me-cep-input' );
		var $result      = $( '#me-cep-result' );
		var xhr          = null;
		var requoteTimer = null;

		$input.on( 'input', function () {
			var digits = $( this ).val().replace( /\D/g, '' );

			if ( digits.length > 8 ) {
				digits = digits.slice( 0, 8 );
			}

			var masked = digits.length > 5
				? digits.slice( 0, 5 ) + '-' + digits.slice( 5 )
				: digits;

			$( this ).val( masked );

			if ( digits.length < 8 ) {
				if ( xhr ) {
					xhr.abort();
					xhr = null;
				}
				$result.html( '' );
				return;
			}

			quote( digits, $result );
		} );

		// Recotar automaticamente quando a quantidade ou a seleção de itens do
		// bundle/composição mudar - 'woosb_save_ids'/'wooco_save_ids' são eventos
		// próprios dos plugins, disparados no `document` assim que eles terminam de
		// atualizar os campos escondidos 'woosb_ids'/'wooco_ids' com a seleção atual.
		$( document ).on( 'input change', 'input.qty', function () {
			scheduleRequote();
		} );

		$( document ).on( 'woosb_save_ids wooco_save_ids', function () {
			scheduleRequote();
		} );

		function scheduleRequote() {
			var digits = $input.val().replace( /\D/g, '' );

			if ( digits.length !== 8 ) {
				return;
			}

			if ( requoteTimer ) {
				clearTimeout( requoteTimer );
			}

			requoteTimer = setTimeout( function () {
				quote( digits, $result );
			}, 300 );
		}

		function quote( cep, $result ) {
			if ( xhr ) {
				xhr.abort();
			}

			$result.html( '<p class="me-cep-calc__loading">Calculando...</p>' );

			var $form = $( 'button[name="add-to-cart"][value="' + meSC.productId + '"]' ).closest( 'form.cart' );
			if ( ! $form.length ) {
				$form = $( 'form.cart' ).first();
			}

			xhr = $.ajax( {
				url:    meSC.ajaxUrl,
				method: 'POST',
				data:   {
					action:     'me_quote',
					nonce:      meSC.nonce,
					cep:        cep,
					product_id: meSC.productId,
					quantity:   $form.find( 'input[name="quantity"]' ).val() || 1,
					woosb_ids:  $form.find( 'input[name="woosb_ids"]' ).val() || '',
					wooco_ids:  $form.find( 'input[name="wooco_ids"]' ).val() || '',
				},
				success: function ( response ) {
					if ( ! response.success || ! response.data || ! response.data.length ) {
						$result.html( '<p class="me-cep-calc__error">Nenhuma opção disponível para este CEP.</p>' );
						return;
					}

					renderResults( $result, response.data );
				},
				error: function ( jqXHR ) {
					if ( jqXHR.statusText === 'abort' ) {
						return;
					}
					$result.html( '<p class="me-cep-calc__error">Erro ao calcular frete. Tente novamente.</p>' );
				},
				complete: function () {
					xhr = null;
				},
			} );
		}
	}

	function renderResults( $container, services ) {
		var html = '<div class="me-cep-calc__table-wrap"><table class="me-cep-calc__table">';
		html += '<thead><tr>';
		html += '<th>Transportadora</th>';
		html += '<th>Serviço</th>';
		html += '<th>Prazo</th>';
		html += '<th>Valor</th>';
		html += '</tr></thead>';
		html += '<tbody>';

		$.each( services, function ( i, s ) {
			var deadline = s.delivery_time > 0
				? s.delivery_time + ' dia(s) útil(eis)'
				: 'Consultar';

			var price = parseFloat( s.price ).toLocaleString( 'pt-BR', {
				style: 'currency',
				currency: 'BRL',
			} );

			var name = escHtml( s.name );
			if ( s.observation ) {
				name += '<br><small class="me-cep-calc__note">' + escHtml( s.observation ) + '</small>';
			}

			html += '<tr>';
			html += '<td>' + escHtml( s.company ) + '</td>';
			html += '<td>' + name + '</td>';
			html += '<td>' + deadline + '</td>';
			html += '<td>' + price + '</td>';
			html += '</tr>';
		} );

		html += '</tbody></table></div>';

		$container.html( html );
	}

	function escHtml( str ) {
		return $( '<div>' ).text( str ).html();
	}
} )( jQuery );

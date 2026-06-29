( function ( $ ) {
	'use strict';

	$( function () { init(); } );

	function init() {
		var $container = $( '#me-cep-calc' );

		if ( ! $container.length ) {
			return;
		}

		var $input  = $( '#me-cep-input' );
		var $result = $( '#me-cep-result' );
		var xhr     = null;

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

		function quote( cep, $result ) {
			if ( xhr ) {
				xhr.abort();
			}

			$result.html( '<p class="me-cep-calc__loading">Calculando...</p>' );

			xhr = $.ajax( {
				url:    meSC.ajaxUrl,
				method: 'POST',
				data:   {
					action:     'me_quote',
					nonce:      meSC.nonce,
					cep:        cep,
					product_id: meSC.productId,
					quantity:   $( 'input.qty' ).val() || 1,
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

			html += '<tr>';
			html += '<td>' + escHtml( s.company ) + '</td>';
			html += '<td>' + escHtml( s.name ) + '</td>';
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

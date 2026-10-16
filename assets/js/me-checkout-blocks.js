( function () {
	'use strict';

	// O WooCommerce prefixa id/name do campo com "billing-"/"shipping-" (ex:
	// id="shipping-melhor-envio-cotacao-billing-document"), então usamos sufixo ($=) em vez
	// de valor exato - a classe (usada só como reforço) não leva esse prefixo.
	var FIELD_SELECTOR = [
		'[id$="melhor-envio-cotacao-billing-document"]',
		'[name$="melhor-envio-cotacao/billing-document"]',
		'.wc-block-components-address-form__melhor-envio-cotacao-billing-document input',
	].join( ', ' );

	function isDocumentField( el ) {
		return !! ( el && el.matches && el.matches( FIELD_SELECTOR ) );
	}

	function getClean( v ) {
		return v.replace( /[^0-9A-Za-z]/g, '' ).toUpperCase();
	}

	function applyCpfMask( v ) {
		var d = v.replace( /\D/g, '' ).slice( 0, 11 );
		if ( d.length > 9 ) return d.slice( 0, 3 ) + '.' + d.slice( 3, 6 ) + '.' + d.slice( 6, 9 ) + '-' + d.slice( 9 );
		if ( d.length > 6 ) return d.slice( 0, 3 ) + '.' + d.slice( 3, 6 ) + '.' + d.slice( 6 );
		if ( d.length > 3 ) return d.slice( 0, 3 ) + '.' + d.slice( 3 );
		return d;
	}

	function applyCnpjMask( v ) {
		var raw  = v.replace( /[^0-9A-Za-z]/g, '' ).toUpperCase();
		var base = raw.slice( 0, 12 );
		var dv   = '';
		for ( var i = 12; i < raw.length && dv.length < 2; i++ ) {
			if ( /\d/.test( raw[ i ] ) ) {
				dv += raw[ i ];
			}
		}
		var full = base + dv;
		var n    = full.length;
		if ( n <= 2 )  return full;
		if ( n <= 5 )  return full.slice( 0, 2 ) + '.' + full.slice( 2 );
		if ( n <= 8 )  return full.slice( 0, 2 ) + '.' + full.slice( 2, 5 ) + '.' + full.slice( 5 );
		if ( n <= 12 ) return full.slice( 0, 2 ) + '.' + full.slice( 2, 5 ) + '.' + full.slice( 5, 8 ) + '/' + full.slice( 8 );
		return full.slice( 0, 2 ) + '.' + full.slice( 2, 5 ) + '.' + full.slice( 5, 8 ) + '/' + full.slice( 8, 12 ) + '-' + full.slice( 12 );
	}

	document.addEventListener( 'input', function ( event ) {
		if ( ! isDocumentField( event.target ) ) {
			return;
		}

		var input  = event.target;
		var clean  = getClean( input.value );
		var masked = ( /[A-Z]/.test( clean ) || clean.length > 11 )
			? applyCnpjMask( input.value )
			: applyCpfMask( input.value );

		if ( masked !== input.value ) {
			input.value = masked;
		}
	} );
} )();

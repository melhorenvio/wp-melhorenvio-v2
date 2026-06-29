document.addEventListener( 'DOMContentLoaded', function () {
	var blockFound   = false;
	var fieldsActive = false;

	function isBrazilSelected() {
		var f = document.querySelector( '#billing-country' ) ||
		        document.querySelector( '#shipping-country' ) ||
		        document.querySelector( 'select[name="billing_country"]' );
		return f ? f.value === 'BR' : false;
	}

	function isUsingSameAddress() {
		var cb = document.querySelector( 'input[type="checkbox"][id^="checkbox-control"]' );
		if ( cb && cb.closest( '.wc-block-checkout__use-address-for-billing' ) ) {
			return cb.checked;
		}
		return false;
	}

	function getTargetContainer() {
		return isUsingSameAddress()
			? document.querySelector( '#shipping' )
			: document.querySelector( '#billing' );
	}

	function getContainerType() {
		return isUsingSameAddress() ? 'shipping' : 'billing';
	}

	function sendToStoreApi( data ) {
		try {
			var dispatch = wp.data.dispatch( 'wc/store/checkout' );
			if ( dispatch && dispatch.setExtensionData ) {
				dispatch.setExtensionData( 'melhor_envio_person_type', data );
			}
		} catch ( e ) {}
		if ( window.wc && window.wc.blocksCheckout &&
			typeof window.wc.blocksCheckout.extensionCartUpdate === 'function' ) {
			window.wc.blocksCheckout.extensionCartUpdate( {
				namespace: 'melhor_envio_person_type',
				data: data,
			} );
		}
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

	function validateCpf( cpf ) {
		if ( cpf.length !== 11 || /^(\d)\1{10}$/.test( cpf ) ) return false;
		for ( var t = 9; t < 11; t++ ) {
			var sum = 0;
			for ( var i = 0; i < t; i++ ) sum += parseInt( cpf[ i ] ) * ( t + 1 - i );
			var d = ( 10 * sum % 11 ) % 10;
			if ( parseInt( cpf[ t ] ) !== d ) return false;
		}
		return true;
	}

	function validateCnpj( cnpj ) {
		if ( cnpj.length !== 14 || /^(.)\1{13}$/.test( cnpj ) ) return false;
		if ( ! /^\d$/.test( cnpj[ 12 ] ) || ! /^\d$/.test( cnpj[ 13 ] ) ) return false;
		var w1 = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
		var w2 = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
		function dv( weights, len ) {
			var s = 0;
			for ( var i = 0; i < len; i++ ) s += ( cnpj.charCodeAt( i ) - 48 ) * weights[ i ];
			var r = s % 11;
			return r < 2 ? 0 : 11 - r;
		}
		return parseInt( cnpj[ 12 ] ) === dv( w1, 12 ) && parseInt( cnpj[ 13 ] ) === dv( w2, 13 );
	}

	function syncData( docInput, ptInput, cpfInput, cnpjInput ) {
		var clean = getClean( docInput.value );
		var pt, cpf, cnpj;
		if ( clean.length <= 11 && ! /[A-Z]/.test( clean ) ) {
			pt = '1'; cpf = docInput.value; cnpj = '';
		} else {
			pt = '2'; cpf = ''; cnpj = docInput.value;
		}
		ptInput.value   = pt;
		cpfInput.value  = cpf;
		cnpjInput.value = cnpj;
		sendToStoreApi( { billing_persontype: pt, billing_cpf: cpf, billing_cnpj: cnpj } );
	}

	function removeFields() {
		var wrap = document.querySelector( '.me-billing-document-wrap' );
		if ( wrap ) wrap.remove();
		var pt = document.getElementById( 'me-billing-persontype' );
		if ( pt ) pt.remove();
		var cpf = document.getElementById( 'me-billing-cpf' );
		if ( cpf ) cpf.remove();
		var cnpj = document.getElementById( 'me-billing-cnpj' );
		if ( cnpj ) cnpj.remove();
		fieldsActive = false;
		blockFound   = false;
	}

	function injectFields( container, containerType ) {
		if ( document.getElementById( 'me-billing-document' ) ) return;

		var anchorId = containerType + '-last_name';
		var anchor   = container.querySelector( '#' + anchorId );
		if ( ! anchor ) return;

		var wrap       = document.createElement( 'div' );
		wrap.className = 'wc-block-components-text-input wc-block-components-address-form__document me-billing-document-wrap';

		var input = document.createElement( 'input' );
		input.type = 'text';
		input.id   = 'me-billing-document';
		input.setAttribute( 'autocomplete', 'off' );
		input.setAttribute( 'aria-label', 'CPF / CNPJ' );
		input.setAttribute( 'autocapitalize', 'none' );

		var label = document.createElement( 'label' );
		label.setAttribute( 'for', 'me-billing-document' );
		label.textContent = 'CPF / CNPJ';

		var ptInput    = document.createElement( 'input' );
		ptInput.type   = 'hidden';
		ptInput.id     = 'me-billing-persontype';

		var cpfInput   = document.createElement( 'input' );
		cpfInput.type  = 'hidden';
		cpfInput.id    = 'me-billing-cpf';

		var cnpjInput  = document.createElement( 'input' );
		cnpjInput.type = 'hidden';
		cnpjInput.id   = 'me-billing-cnpj';

		var errorDiv       = document.createElement( 'div' );
		errorDiv.className = 'wc-block-components-validation-error me-billing-doc-error';
		errorDiv.setAttribute( 'role', 'alert' );
		errorDiv.style.display = 'none';
		var errorP = document.createElement( 'p' );
		errorDiv.appendChild( errorP );

		wrap.appendChild( input );
		wrap.appendChild( label );
		wrap.appendChild( errorDiv );

		anchor.parentElement.insertAdjacentElement( 'afterend', wrap );
		wrap.insertAdjacentElement( 'afterend', ptInput );
		ptInput.insertAdjacentElement( 'afterend', cpfInput );
		cpfInput.insertAdjacentElement( 'afterend', cnpjInput );

		input.addEventListener( 'focus', function () { wrap.classList.add( 'is-active' ); } );
		input.addEventListener( 'blur',  function () {
			if ( ! input.value ) wrap.classList.remove( 'is-active' );
		} );

		input.addEventListener( 'input', function () {
			var clean  = getClean( this.value );
			var masked = ( /[A-Z]/.test( clean ) || clean.length > 11 )
				? applyCnpjMask( this.value )
				: applyCpfMask( this.value );
			this.value = masked;
			if ( ! masked ) wrap.classList.remove( 'is-active' );
			else wrap.classList.add( 'is-active' );
			syncData( input, ptInput, cpfInput, cnpjInput );
			errorDiv.style.display = 'none';
		} );

		// Bind submit button validation (once)
		var btn = document.querySelector( '.wc-block-checkout__actions_row button' );
		if ( btn && ! btn.dataset.meDocListener ) {
			btn.addEventListener( 'click', function ( e ) {
				if ( ! isBrazilSelected() ) return;
				var docEl = document.getElementById( 'me-billing-document' );
				if ( ! docEl ) return;
				var clean = getClean( docEl.value );
				var ok = false, msg = '';
				if ( clean.length === 11 ) {
					ok  = validateCpf( clean );
					msg = ok ? '' : 'CPF inválido.';
				} else if ( clean.length === 14 ) {
					ok  = validateCnpj( clean );
					msg = ok ? '' : 'CNPJ inválido.';
				} else {
					msg = 'Informe um CPF (11 dígitos) ou CNPJ (14 caracteres).';
				}
				if ( ! ok ) {
					e.stopPropagation();
					e.preventDefault();
					var errP = document.querySelector( '.me-billing-doc-error p' );
					var errD = document.querySelector( '.me-billing-doc-error' );
					if ( errP ) errP.textContent = msg;
					if ( errD ) errD.style.display = 'block';
					docEl.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				}
			}, true );
			btn.dataset.meDocListener = 'true';
		}

		fieldsActive = true;
	}

	function tryInject() {
		if ( ! isBrazilSelected() ) return;
		var container     = getTargetContainer();
		var containerType = getContainerType();
		if ( ! container || blockFound ) return;

		var editBtn = document.querySelector(
			'span.wc-block-components-address-card__edit[aria-controls="' + containerType + '"]'
		);
		if ( editBtn && editBtn.getAttribute( 'aria-expanded' ) !== 'true' ) {
			editBtn.click();
		}
		setTimeout( function () {
			injectFields( container, containerType );
			blockFound = true;
		}, 300 );
	}

	function bindSameAddressCheckbox() {
		var cb = document.querySelector( 'input[type="checkbox"][id^="checkbox-control"]' );
		if ( cb && cb.closest( '.wc-block-checkout__use-address-for-billing' ) && ! cb.dataset.meListener ) {
			cb.addEventListener( 'change', function () {
				setTimeout( function () {
					removeFields();
					tryInject();
				}, 300 );
			} );
			cb.dataset.meListener = 'true';
		}
	}

	var observer = new MutationObserver( function () {
		bindSameAddressCheckbox();
		tryInject();
	} );

	observer.observe( document.body, { childList: true, subtree: true } );
} );

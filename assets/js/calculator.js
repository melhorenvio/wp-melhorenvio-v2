function toggleCalculator() {

    let widthProduct = document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_altura');
    if (!widthProduct) {
        return;
    }

    let dimensions = getDimension();
    if (!dimensions.width
        || !dimensions.heigth
        || !dimensions.length
        || !dimensions.weight
        || dimensions.width == 0
        || dimensions.heigth == 0
        || dimensions.length == 0
        || dimensions.weight == 0
    ) {
        document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto').style.display = 'none';
        return;
    }
    document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto').style.display = 'block';
}

function getDimension() {
    let dimensions = {
        'heigth': document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_altura').value,
        'width': document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_largura').value,
        'length': document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_comprimento').value,
        'weight': document.querySelector('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_peso').value
    }
    return dimensions;
}

/**
 *  mask to replace non number 
 * @param {string} content - number to format
 * @returns {string}
 * @example
 * const valueToFormat = '12345678'
 * numberMask(valueToFormat);
 */
function numberMask(content) {
    return content.replace(/[^0-9-]+/g, "");
}

/**
 *  mask to format postal code 
 * @param {string} content - postal code number
 * @returns {string}
 * @example
 * const valueToFormat = '12345678'
 * postalCodeMask(valueToFormat);
 */
 function postalCodeMask(content, input) {
    let value = content;
    let formatedValue = '';

    if(isNaN(content)) {
        input.value = numberMask(content);
        
        return;
    }
    
    //regex to add " - " in position 5 of cep: EX:123456-78
    formatedValue = value.replace(/(\d{5})(\d{1,2})$/, "$1-$2"); 
    input.value = formatedValue;
}

/**
 *  this function is used to apply the postal code mask in the input to calculate the quotation
 */
function usePostalCodeMask(evt='') { 
    const inputDefault = evt ? evt.target : evt;
    const inputShortcode = document.querySelector('.iptCepShortcode');
    

    if(inputDefault) {
        const content = inputDefault.value;
        postalCodeMask(content, inputDefault);
    }

    if(inputShortcode) {
        const content = inputShortcode.value;
        postalCodeMask(content, inputShortcode);
    }
}

function validateNumber(event) {
    var key = window.event ? event.keyCode : event.which;
    if (event.keyCode === 8 || event.keyCode === 46) {
        return true;
    } else if (key < 48 || key > 57) {
        return false;
    } else {
        return true;
    }
};
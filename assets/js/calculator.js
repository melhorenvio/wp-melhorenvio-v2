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

function mascara(t, mask) { 
    var i = t.value.length;
    var carac = mask.substring(i, i+1);
    var prox_char = mask.substring(i+1, i+2);
    if(i == 0 && carac != '#'){
        insereCaracter(t, carac);
        if(prox_char != '#')insereCaracter(t, prox_char);
    }
    else if(carac != '#'){
        insereCaracter(t, carac);
        if(prox_char != '#')insereCaracter(t, prox_char);
    }
    function insereCaracter(t, char){
        t.value += char;
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
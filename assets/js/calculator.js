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
    let postal_code = t.value.substr(t.value.length - 1);
    if (!isNaN(postal_code)) {
        let i = t.value.length;
        let saida = mask.substring(1, 0);
        let texto = mask.substring(i);
        if (texto.substring(0, 1) != saida) {
            t.value += texto.substring(0, 1);
        }
    } else {
        t.value = t.value.slice(0, -1);
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
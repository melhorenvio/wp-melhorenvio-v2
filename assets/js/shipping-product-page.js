(function ($) {
    'use strict';

    $(function () {

        $(document).on('keyup', '.iptCep', function (e) {

            jQuery('.observation-shipping-free').hide();

            resetarTabela();

            if ($(this).val().length === 9) {

                if ($(e.target).is('a#cfpp_credits')) { return; }

                var url = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_endpoint_url').val();
                var cep = $('.iptCep').val();
                var altura = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_altura').val();
                var largura = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_largura').val();
                var comprimento = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_comprimento').val();
                var peso = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_peso').val();
                var preco = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_preco').val();
                var id_produto = $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #id_produto').val();

                var errors = [];

                if (altura == 0) {
                    errors.push('Produto com altura não informada');
                }

                if (largura == 0) {
                    errors.push('Produto com largura não informada');
                }

                if (comprimento == 0) {
                    errors.push('Produto com comprimento não informada');
                }

                if (peso == 0) {
                    errors.push('Produto com peso não informado');
                }

                if (errors.length > 0) {

                    var row = '';
                    row = '<tr><td colspan="3">Ocorreu um erro ao obter informações sobre o valor do frete</td></tr>';

                    errors.map(item => {
                        row += `<tr><td colspan="3">${item}</td></tr>`;
                    });

                    $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto .resultado-frete table tbody').append(row);
                    esconderLoader();
                    exibirTabela();

                    return false;
                }

                let inpCEP = $(this);
                inpCEP.attr('disabled', 'disabled');

                exibirLoader();
                esconderTabela();
                resetarTabela();

                let qty = 1;
                let inpQty = $('.quantity .qty:visible').val();
                if (typeof inpQty != 'undefined') {
                    qty = inpQty;
                }

                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        'action': 'cotation_product_page',
                        'data': {
                            'cep_origem': cep,
                            'produto_altura': altura,
                            'produto_largura': largura,
                            'produto_comprimento': comprimento,
                            'produto_peso': peso,
                            'produto_preco': preco,
                            'id_produto': id_produto,
                            'quantity': qty
                        }
                    },
                    error: function (jqXHR, exception) {
                        inpCEP.removeAttr('disabled');
                        inpCEP.val('');
                        alert(jqXHR.responseJSON.error);
                        esconderLoader();
                        esconderTabela();
                        resetarTabela();
                        return false;
                    },
                    success: function (response) {
                        var row = '';
                        let { data } = response;

                        data.map(item => {

                            if (item.observations && item.observations !== 'Frete Grátis') {
                                jQuery('.observation-shipping-free').show();
                                jQuery('.observation-shipping-free').html(item.observations);
                            }

                            let name = item.name

                            if (!item.delivery_time) {
                                item.delivery_time = '';
                            }

                            row += `<tr><td>${name} ${item.delivery_time}: ${item.price}</td></tr>`;
                        });

                        if (row == '') {
                            row = '<tr><td colspan="3">Desculpe, o cálculo de frete para este produto só está disponível no Carrinho, por favor, prossiga com a compra normalmente.</td></tr>';
                        }

                        $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto .resultado-frete table tbody').append(row);
                        esconderLoader();
                        exibirTabela();
                        inpCEP.removeAttr('disabled');
                        inpCEP.val('');
                    }
                });
            }
        })

        function exibirLoader() {
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calcular-frete').css('display', 'none');
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calcular-frete-loader').css('display', 'inline-block');
        }

        function esconderLoader() {
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calcular-frete').css('display', 'inline-block');
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calcular-frete-loader').css('display', 'none');
        }

        function exibirTabela() {
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto .resultado-frete').show();
        }

        function esconderTabela() {
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto .resultado-frete').hide();
        }

        function resetarTabela() {
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto .resultado-frete table tbody').html('');
        }

        $(".single_variation_wrap").on("show_variation", function (event, variation) {
            resetarTabela();
            esconderTabela();
            $('#woocommerce-correios-calculo-de-frete-na-pagina-do-produto #calculo_frete_produto_preco').val(variation.display_price.toFixed(2));
        });

    });
})(jQuery);

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

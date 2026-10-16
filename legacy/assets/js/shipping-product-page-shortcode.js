(function ($) {
    'use strict';
    $(function () {
        $('.observation-shipping-free-shortcode').hide();
        $(document).on('keyup', '.iptCepShortcode', function (e) {
            if ($('.iptCepShortcode').val().length === 9) {
                resetarTabela();
                if ($(e.target).is('a#cfpp_credits')) { return; }
                var cep = $('.iptCepShortcode').val();
                var id = $('#calculo_frete_produto_id').val();
                var url = $('#calculo_frete_url').val();

                var errors = [];
                if (errors.length > 0) {
                    var row = '';
                    row = '<tr><td colspan="3">Ocorreu um erro ao obter informações sobre o valor do frete</td></tr>';

                    errors.map(item => {
                        row += `<tr><td colspan="3">${item}</td></tr>`;
                        return row;
                    });

                    $('#melhor-envio-shortcode .resultado-frete table tbody').append(row);
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
                            'id_produto': id,
                            'cep_origem': cep,
                            'quantity': qty
                        }
                    },
                    error: function (jqXHR, exception) {
                        inpCEP.removeAttr('disabled');
                        alert(jqXHR.responseJSON.message);
                        esconderLoader();
                        esconderTabela();
                        resetarTabela();
                        return false;
                    },
                    success: function (response) {
                        var row = '';
                        let  data  = response.data.quotations;
                        data.map(item => {

                            if (item.observations && item.observations !== 'Frete Grátis') {
                                $('.observation-shipping-free-shortcode').show();
                                $('.observation-shipping-free-shortcode').html(item.observations);
                            }

                            let name = item.name.split('(');
                            name = name[0];
                            if (!item.delivery_time) {
                                item.delivery_time = '';
                            }
                            row += `<tr><td>${name} ${item.delivery_time}: ${item.price}</td></tr>`;
                        });

                        if (row == '') {
                            row = '<tr><td colspan="3">Desculpe, o cálculo de frete para este produto só está disponível no Carrinho, por favor, prossiga com a compra normalmente.</td></tr>';
                        }
                        $('#melhor-envio-shortcode .resultado-frete table tbody').append(row);
                        esconderLoader();
                        exibirTabela();
                        inpCEP.removeAttr('disabled');
                    }
                });
            }
        })

        function exibirLoader() {
            $('#melhor-envio-shortcode #calcular-frete').css('display', 'none');
            $('#melhor-envio-shortcode #calcular-frete-loader').css('display', 'inline-block');
        }

        function esconderLoader() {
            $('#melhor-envio-shortcode #calcular-frete').css('display', 'inline-block');
            $('#melhor-envio-shortcode #calcular-frete-loader').css('display', 'none');
        }

        function exibirTabela() {
            $('#melhor-envio-shortcode .resultado-frete').show();
        }

        function esconderTabela() {
            $('#melhor-envio-shortcode .resultado-frete').hide();
        }

        function resetarTabela() {
            $('#melhor-envio-shortcode .resultado-frete table tbody').html('');
        }

    });
})(jQuery);
<?php

namespace Services;

/**
 * Class responsible for the shortcode service
 */
class ShortCodeService
{

    public $product;

    /**
     * Constructor
     *
     * @param int $productId
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    public function shortcode()
    {
        $this->addCalculoDeFrete();
    }

    /**
     * Adiciona o HTML do cálculo de frete na página do produto
     */
    public function addCalculoDeFrete()
    {

        $this->inline_js();
        echo '
            <style>
                #melhor-envio-shortcode .border-none , tr, td{
                    border: 0px; 
                }
            </style>
            <div id="melhor-envio-shortcode" class="containerCalculator">
                <form>
                    <input type="hidden" id="calculo_frete_produto_id" value="' . $this->product->get_id() . ' " />
                    <input type="hidden" id="calculo_frete_produto_largura" value="' . $this->product->get_width() . ' " />
                    <input type="hidden" id="calculo_frete_produto_altura" value="' . $this->product->get_height() . '" />
                    <input type="hidden" id="calculo_frete_produto_comprimento" value="' . $this->product->get_length() . '" />
                    <input type="hidden" id="calculo_frete_produto_peso" value="' . $this->product->get_weight() . '" />
                    <input type="hidden" id="calculo_frete_produto_preco" value="' . $this->product->get_price() . '" /> 
                    <input type="hidden" id="calculo_frete_produto_shipping_class_id" value="' . $this->product->get_shipping_class_id() . '" /> 
                    <input type="hidden" id="calculo_frete_url" value="' . admin_url('admin-ajax.php') . '" /> 
                    <div>
                        <table class="border-none">
                            <tr>
                                <td>
                                    <input type="text" maxlength="9" class="iptCepShortcode" placeholder="Informe seu cep" onkeydown="return mascara(this, "#####-###");>
                                </td>
                                <td>
                                <input type="submit" value="Calcular">
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>

                <div id="calcular-frete-loader" style="display:none;">
                    <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/loader.gif" />
                </div>

                <div class="resultado-frete" style="display:none;">
                    <table>
                        <thead>
                            <tr>
                                <td><strong>Forma de envio</strong></td>
                                <td><strong>Custo estimado</strong></td>
                                <td><strong>Entrega estimada</strong></td>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>';
    }

    private function inline_js()
    {
        echo '
            <script>
                /**
                *   Função de Máscara em Javascript
                */
                function mascara(t, mask) {
                    var digitou_agora = t.value.substr(t.value.length - 1);
                    if (!isNaN(digitou_agora)) {
                        var i = t.value.length;
                        var saida = mask.substring(1,0);
                        var texto = mask.substring(i);
                        if (texto.substring(0,1) != saida){
                            t.value += texto.substring(0,1);
                        }
                    } else {
                        t.value = t.value.slice(0, -1);
                    }
                }
            </script>
        ';
    }
}

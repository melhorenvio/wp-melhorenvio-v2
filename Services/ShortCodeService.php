<?php

namespace Services;

class ShortCodeService{

    public $product;

    public function __construct($product_id)
    {
        $this->product = wc_get_product( $product_id );
    }

    public function shortcode()
    {   
        $this->add_calculo_de_frete();
        $this->enqueue_css_js_frontend();
    }

    /**
    * Adiciona o HTML do cálculo de frete na página do produto
    */
    public function add_calculo_de_frete() {

        $this->inline_js();
        echo '
            <style>
                #melhor-envio-shortcode .border-none , tr, td{
                    border: 0px; 
                }
            </style>
            <div id="melhor-envio-shortcode" class="containerCalculator">
                <form>
                    <input type="hidden" id="calculo_frete_produto_largura" value="' . $this->product->width .' " />
                    <input type="hidden" id="calculo_frete_produto_altura" value="' . $this->product->height . '" />
                    <input type="hidden" id="calculo_frete_produto_comprimento" value="' . $this->product->length . '" />
                    <input type="hidden" id="calculo_frete_produto_peso" value="' . $this->product->weight . '" />
                    <input type="hidden" id="calculo_frete_produto_preco" value="' . $this->product->price . '" /> 
                    <input type="hidden" id="calculo_frete_url" value="' . admin_url( 'admin-ajax.php' ) . '" /> 
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

    private function inline_js() {
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

    private function enqueue_css_js_frontend() {
        wp_enqueue_script( 'produto-shortcode', BASEPLUGIN_ASSETS . '/js/shipping-product-page-shortcode.js', 'jquery');
    }
}
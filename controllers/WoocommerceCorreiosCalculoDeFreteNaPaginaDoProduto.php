<?php

namespace Controllers;

class WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto {

    protected $is_product;
    protected $cep_destino;
    protected $produto_altura_final;
    protected $produto_largura_final;
    protected $produto_comprimento_final;
    protected $produto_peso_final;
    protected $height;
    protected $width;
    protected $length;
    protected $weight;
    protected $cep_remetente;
    protected $base_path;
    protected $base_url;
    protected $metodos_de_entrega;

    public function __construct() {

        $this->base_path = __DIR__;
        $this->base_url =  plugin_dir_url( __FILE__ );

        // Hooks
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_css_js_frontend') );

        // Escuta AJAX de usuários logados e deslogados
        add_action( 'wp_ajax_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete') );
        add_action( 'wp_ajax_nopriv_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete') );
    }

    public function enqueue_css_js_frontend() {
        wp_enqueue_script( 'produto', plugins_url('melhor-envio-beta/assets/js/shipping-product-page.js'), 'jquery');
    }

    public function run() {
        add_action( 'woocommerce_before_add_to_cart_button', array($this, 'is_produto_single'));
    }

    public function is_produto_single() {
        global $product;
        if (is_product()) {
            $this->prepara_produto($product);
            add_action('woocommerce_before_add_to_cart_button', array($this, 'add_calculo_de_frete'), 11);
        }
    }

    public function formata_peso_produto($peso) {
        $medida = get_option('woocommerce_weight_unit');
        switch ($medida) {
            case 'g':
                $fator = 1000;
                break;
            default:
                $fator = 1;
                break;
        }
        return number_format($peso / $fator, 2, '.', ',');
    }

    public function valida_peso_produto($peso) {
        $medida = get_option('woocommerce_weight_unit');
        switch ($medida) {
            case 'g':
                return $peso <= 30000;
                break;
            default:
                return $peso <= 30;
                break;
        }
    }

    public function prepara_produto($product) {
        $this->product = $product;
        $this->height = $product->get_height();
        $this->width = $product->get_width();
        $this->length = $product->get_length();
        $this->weight = $this->formata_peso_produto($product->get_weight());
        $this->price = $product->get_price();
        $this->id = $product->get_id();
    }

    /**
     * Verifica se o produto têm os dados necessários para cálculo de frete
     */
    public function verifica_produto() {
        // Vamos ver se todos os fatores estão de acordo com as regras dos correios.
        $erros = [];

        // Altura
        if (!is_numeric($this->height)) {
            $erros[] = 'Altura inválida ou não preenchida.';
        } elseif (is_numeric($this->height) && $this->height > 105) {
            $erros[] = 'Altura ('.$this->height.'cm) ultrapassa o máximo permitido pelos correios (105cm).';
        } elseif (is_numeric($this->height) && $this->height < 2) {
            $erros[] = 'Altura ('.$this->height.'cm) é menor do que o mínimo permitido pelos correios (2cm).';
        }

        // Largura
        if (!is_numeric($this->width)) {
            $erros[] = 'Largura inválida ou não preenchida.';
        } elseif (is_numeric($this->width) && $this->width > 105) {
            $erros[] = 'Largura ('.$this->width.'cm) ultrapassa o máximo permitido pelos correios (105cm).';
        } elseif (is_numeric($this->width) && $this->width < 11) {
            $erros[] = 'Largura ('.$this->width.'cm) é menor do que o mínimo permitido pelos correios (11cm).';
        }

        // Comprimento
        if (!is_numeric($this->length)) {
            $erros[] = 'Comprimento inválido ou não preenchido.';
        } elseif (is_numeric($this->length) && $this->length > 105) {
            $erros[] = 'Comprimento ('.$this->length.'cm) ultrapassa o máximo permitido pelos correios (105cm).';
        } elseif (is_numeric($this->length) && $this->length < 11) {
            $erros[] = 'Comprimento ('.$this->length.'cm) é menor do que o mínimo permitido pelos correios (16cm).';
        }

        // Soma da Altura, Largura e Comprimento
        if (is_numeric($this->height) && is_numeric($this->width) && is_numeric($this->length)) {
            if ($this->height + $this->width + $this->length > 200) {
                $erros[] = 'Soma da Altura, Largura e Comprimento ('.$this->height + $this->width + $this->length.') ultrapassa o máximo permitido pelos correios (200cm).';
            }
        }

        // Peso
        if (!is_numeric($this->weight)) {
            $erros[] = 'Peso inválido ou não preenchido. ('.$this->weight.')';
        } elseif (is_numeric($this->weight) && !$this->valida_peso_produto($this->weight)) {
            $erros[] = 'Peso ('.$this->weight.'kg) ultrapassa o máximo permitido pelos correios (30kg).';
        }

        // Preço
        if (!is_numeric($this->price)) {
            $erros[] = 'Preço inválido ou não preenchido. ('.$this->price.')';
        } elseif (is_numeric($this->price) && $this->price > 10000) {
            $erros[] = 'Preço (R$ '.$this->price.') ultrapassa o máximo permitido pelos correios (R$ 10.000,00).';
        }

        // Tem algum erro nos métodos de entrega?
        if (!empty($this->metodos_de_entrega['erro'])) {
            $erros[] = $this->metodos_de_entrega['erro'];
        }

        if (!empty($erros)) {
            $string = '';
            foreach ($erros as $erro) {
                $string .= '<li>'.$erro.'</li>';
            }
            return $string;
        }
        return true;
    }

    /**
    * Adiciona o HTML do cálculo de frete na página do produto
    */
    public function add_calculo_de_frete() {
        $verifica_produto = $this->verifica_produto();
        if ($verifica_produto !== true):
            // Se o produto estiver com dimensões inválidas, exibe uma mensagem apenas para o admin
            if (current_user_can('manage_options')):
                echo '<div id="woocommerce-correios-calculo-de-frete-na-pagina-do-produto-incompleto">
                    <p>Atenção</p>
                    <p>O cálculo de frete na página do produto não está sendo exibido aqui pois:</p>
                    <p><ul>'.$verifica_produto.'</ul></p>
                    <p>(Não se preocupe, somente administradores do site vêem esta mensagem)</p>
                </div>';
            endif;
        else:
            ?>
                <?php echo $this->inline_js(); ?>
                <div id="woocommerce-correios-calculo-de-frete-na-pagina-do-produto">
                    <style>
                        div#woocommerce-correios-calculo-de-frete-na-pagina-do-produto div.calculo-de-frete div#calcular-frete svg {fill:<?php echo $this->options['cor_do_texto']?>;}
                        div#woocommerce-correios-calculo-de-frete-na-pagina-do-produto div.calculo-de-frete div#calcular-frete {color:<?php echo $this->options['cor_do_texto']?>;}
                        div#woocommerce-correios-calculo-de-frete-na-pagina-do-produto div.calculo-de-frete div#calcular-frete {background-color:<?php echo $this->options['cor_do_botao']?>;}
                        #calcular-frete {
                            display:inline-block;
                            color:#444;
                            border:1px solid #CCC;
                            background:#DDD;
                            box-shadow: 0 0 5px -1px rgba(0,0,0,0.2);
                            cursor:pointer;
                            vertical-align:middle;
                            padding: 9px;
                            text-align: center;
                        }
                    </style>
                    <?php wp_nonce_field('solicita_calculo_frete', 'solicita_calculo_frete'); ?>
                    <input type="hidden" id="calculo_frete_endpoint_url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                    <input type="hidden" id="calculo_frete_produto_altura" value="<?php echo $this->height;?>">
                    <input type="hidden" id="calculo_frete_produto_largura" value="<?php echo $this->width;?>">
                    <input type="hidden" id="calculo_frete_produto_comprimento" value="<?php echo $this->length;?>">
                    <input type="hidden" id="calculo_frete_produto_peso" value="<?php echo $this->weight;?>">
                    <input type="hidden" id="calculo_frete_produto_preco" value="<?php echo $this->price;?>">
                    <input type="hidden" id="id_produto" value="<?php echo $this->id;?>">
                    <div class="calculo-de-frete">
                        <input type="text" maxlength="9" onkeydown="return mascara(this, '#####-###');">
                        <div id="calcular-frete">
                            Calcular Frete
                        </div>
                    </div>
                    <div id="calcular-frete-loader" style="display:none;">
                            <img src="<?php echo  plugins_url('melhor-envio-beta/assets/img/loader.gif') ?>" />
                        </div>
                    <div class="resultado-frete">
                        <table>
                            <thead>
                                <tr>
                                    <td>Forma de envio</td>
                                    <td>Custo estimado</td>
                                    <td>Entrega estimada</td>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
        endif;
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

}

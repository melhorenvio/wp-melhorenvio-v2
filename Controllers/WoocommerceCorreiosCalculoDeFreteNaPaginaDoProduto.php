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
    protected $where_show_calculator;

    public function __construct() {

        $this->base_path = __DIR__; 
        $this->base_url =  plugin_dir_url( __FILE__ );
        $this->where_show_calculator = (new ConfigurationController())->getWhereCalculatorValue();

        // Hooks
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_css_js_frontend') );

        // Escuta AJAX de usuários logados e deslogados
        add_action( 'wp_ajax_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete') );
        add_action( 'wp_ajax_nopriv_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete') );
    }

    public function enqueue_css_js_frontend() {
        wp_enqueue_script( 'produto', BASEPLUGIN_ASSETS . '/js/shipping-product-page.js', 'jquery');
        wp_enqueue_script( 'produto-variacao', BASEPLUGIN_ASSETS . '/js/shipping-product-page-variacao.js', 'jquery');
        wp_enqueue_script( 'produto-shortcode', BASEPLUGIN_ASSETS . '/js/shipping-product-page-shortcode.js', 'jquery');
    }

    public function run() {
        //woocommerce_before_add_to_cart_button
        add_action( $this->where_show_calculator, array($this, 'is_produto_single'));
    }

    public function is_produto_single() {
        
        global $product;

        if (is_product() && !$product-> is_virtual('yes')) {
            $this->prepara_produto($product);
            add_action($this->where_show_calculator, array($this, 'add_calculo_de_frete'), 11);
        }
    }

    public function formata_peso_produto($peso) {

        $peso = floatval($peso);

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
    * Adiciona o HTML do cálculo de frete na página do produto
    */
    public function add_calculo_de_frete() {

        $calculo_de_frete = get_option('calculo_de_frete');
        $input_calculo_frete = get_option('input_calculo_frete');
        $botao_calculo_frete = get_option('botao_calculo_frete');
        $botao_imagem_calculo_frete = get_option('botao_imagem_calculo_frete');
        $botao_texto_calculo_frete = get_option('botao_texto_calculo_frete');


        echo $this->inline_js(); ?>
                <style>
                    /* Style inputs, select elements and textareas */
                    .containerCalculator  input[type=text], .containerCalculator select, .containerCalculator textarea{
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-sizing: border-box;
                    resize: vertical;
                    }

                    /* Style the label to display next to the inputs */
                    .containerCalculator   label {
                    padding: 12px 12px 12px 0;
                    display: inline-block;
                    }

                    /* Style the submit button */
                    .containerCalculator   input[type=submit] {
                    background-color: #333333;
                    color: white;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    float: right;
                    }

                    /* Style the containerCalculator */
                    .containerCalculator {
                    border-radius: 5px;
                    background-color: #f2f2f2;
                    padding: 20px;
                    margin-top: 10px;
                    margin-bottom: 20px;
                    width: 100%;
                    }

                    /* Floating column for labels: 25% width */
                    .containerCalculator  .col-25 {
                    float: left;
                    width: 25%;
                    margin-top: 6px;
                    }

                    /* Floating column for inputs: 75% width */
                    .containerCalculator  .col-75 {
                    float: left;
                    width: 100%;
                    margin-top: 6px;
                    }

                    /* Clear floats after the columns */
                    .containerCalculator  .row:after {
                    content: "";
                    display: table;
                    clear: both;
                    }

                    /* Responsive layout - when the screen is less than 600px wide, make the two columns stack on top of each other instead of next to each other */
                    @media screen and (max-width: 600px) {
                    .containerCalculator .col-25, .containerCalculator .col-75, input[type=submit], .containerCalculator input[type=text] {
                        width: 100%;
                        margin-top: 10;
                    }
                    }

                </style>

            <div id="woocommerce-correios-calculo-de-frete-na-pagina-do-produto" class="containerCalculator">
                
                <?php wp_nonce_field('solicita_calculo_frete', 'solicita_calculo_frete'); ?>

                <input type="hidden" id="calculo_frete_endpoint_url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>" >
                <input type="hidden" id="calculo_frete_produto_altura" value="<?php echo $this->height;?>">
                <input type="hidden" id="calculo_frete_produto_largura" value="<?php echo $this->width;?>">
                <input type="hidden" id="calculo_frete_produto_comprimento" value="<?php echo $this->length;?>">
                <input type="hidden" id="calculo_frete_produto_peso" value="<?php echo $this->weight;?>">
                <input type="hidden" id="calculo_frete_produto_preco" value="<?php echo $this->price;?>">
                <input type="hidden" id="id_produto" value="<?php echo $this->id;?>">
                
                <div style="width:100%">
                    <div class="row">
                        <div class="col-75">
                            <p>Simulação de frete</p>
                            <input type="text" maxlength="9" class="iptCep" placeholder="Informe seu cep" onkeydown="return mascara(this, '#####-###');">
                        </div>
                    </div>

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
                </div>

            </div>
        <?php
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

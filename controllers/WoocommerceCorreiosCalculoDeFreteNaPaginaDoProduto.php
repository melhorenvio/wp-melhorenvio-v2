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

    public function escutar_solicitacoes_de_frete() {
        // Verifica se estamos solicitando um cálculo de frete...
        if (!empty($_POST['data']) && is_array($_POST['data']) && count($_POST['data']) == 8) {
            $cep_destinatario = $_POST['data']['cep_origem'];
            $produto_altura = $_POST['data']['produto_altura'];
            $produto_largura = $_POST['data']['produto_largura'];
            $produto_comprimento = $_POST['data']['produto_comprimento'];
            $produto_peso = $_POST['data']['produto_peso'];
            $produto_preco = $_POST['data']['produto_preco'];
            $solicita_calculo_frete = $_POST['data']['solicita_calculo_frete'];
        } else {
            die('Erro no "escutar_solicitacoes_de_frete", verifique o que está vindo em $_POST["data"]');
        }
        if (
            !empty($cep_destinatario)
            &&
            !empty($produto_altura)
            &&
            !empty($produto_largura)
            &&
            !empty($produto_comprimento)
            &&
            !empty($produto_peso)
            &&
            !empty($produto_preco) || $produto_preco == '0'
        ) {
            if (wp_verify_nonce($solicita_calculo_frete, 'solicita_calculo_frete')) {
                $this->prepara_calculo_de_frete($cep_destinatario, $produto_altura, $produto_largura, $produto_comprimento, $produto_peso, $produto_preco);
            } else {
                die('NONCE FAIL');
            }
        } else {
            die('Algum valor está vazio.');
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
                        <div id="calcular-frete-loader"></div>
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

    /**
     * Retorna um JSON com os preços do frete
     */
    public function prepara_calculo_de_frete($cep_destino, $altura, $largura, $comprimento, $peso, $preco) {
        $erro = false;
        $cep = preg_replace('/[^0-9]/', '', $cep_destino);
        if (strlen($cep) !== 8) {
            $erro = true;
            $result['status'] = 'erro';
            $result['mensagem'] = 'Por favor, informe um CEP válido.';
            $this->retornar_json($result);
        }
        if (!is_numeric($altura) || !is_numeric($largura) || !is_numeric($comprimento) || !is_numeric($peso) || !is_numeric($preco)) {
            $erro = true;
            $result['status'] = 'erro';
            $result['mensagem'] = 'Por favor, informe dimensões válidas.';
            $this->retornar_json($result);
        }
        if (!$erro) {
            // Temos dados válidos
            $this->cep_destino = $cep_destino;
            $this->produto_altura_final = $altura;
            $this->produto_largura_final = $largura;
            $this->produto_comprimento_final = $comprimento;
            $this->produto_peso_final = $peso;
            $this->produto_preco_final = $preco;
            $this->id_produto = $id_produto;

            /**
            *   Pega a lista de Shipping Zones cadastradas no WooCommerce e preenche o array de métodos de entrega
            */
            $shipping_zones = new CFPP_Shipping_Zones();
            $this->metodos_de_entrega = $shipping_zones->get_metodos_de_entrega($cep_destino, $preco);

            $this->calcula_frete();
        }
    }

    /**
     * Retorna o JSON
     */
    protected function retornar_json(array $output) {
        header("Content-type: application/json");
        die(json_encode($output, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Envia os dados para a API do WC_Correios e retorna o JSON
     */
    public function calcula_frete() {
        $output = array();

        $output['retirar_no_local'] = $this->metodos_de_entrega['retirar_no_local'];
        $output['frete_gratis'] = $this->metodos_de_entrega['frete_gratis'];

        // Pega os valores propriamente dito
        foreach ($this->metodos_de_entrega['shipping_methods'] as $key => $metodo_de_entrega) {
            if ($metodo_de_entrega['cep_destinatario_permitido']) {
                $output['shipping_methods'][$key] = (array) $this->get_valor_frete_wc_correios($metodo_de_entrega);
                $output['shipping_methods'][$key] = $this->verifica_retorno_wc_correios($output['shipping_methods'][$key]);
                $output['shipping_methods'][$key]['Nome'] = $metodo_de_entrega['title'];
            }
        }

        if (empty($output['shipping_methods']) && $output['retirar_no_local'] == false) {
            $output['status']['erro'] = 'Desculpe, não existem métodos de entrega disponiveis para esta região.';
        }

        $this->retornar_json($output);
    }

    /**
     * Envia os dados para a API do WC_Correios e retorna o valor do frete
     */
    protected function get_valor_frete_wc_correios($metodo_de_entrega) {
        $correiosWebService = new WC_Correios_Webservice;

        // Preenche a variável $this->cep_remetente
        $this->check_cep_remetente_valido();

        $correiosWebService->set_height($this->produto_altura_final);
        $correiosWebService->set_width($this->produto_largura_final);
        $correiosWebService->set_length($this->produto_comprimento_final);
        $correiosWebService->set_weight($this->produto_peso_final);
        $correiosWebService->set_destination_postcode($this->cep_destino);
        $correiosWebService->set_origin_postcode($this->cep_remetente);
        $correiosWebService->set_service($metodo_de_entrega['code']);

        // Agora vamos setar os condicionais...

        // Valor declarado
        if ('yes' == $metodo_de_entrega['declare_value'] && $this->produto_preco_final > 18.50) {
            $correiosWebService->set_declared_value($this->produto_preco_final);
        }

        // Mão Propria
        if (!empty($metodo_de_entrega['own_hands'])) {
            $correiosWebService->set_own_hands = 'S';
        }

        // Peso extra
        if (!empty($metodo_de_entrega['extra_weight'])) {
            $correiosWebService->set_extra_weight($metodo_de_entrega['extra_weight']);
        }

        // Aviso de recebimento
        if ('yes' == $metodo_de_entrega['receipt_notice']) {
            $correiosWebService->set_receipt_notice('S');
        }

        $entrega = $correiosWebService->get_shipping();

        // Dias adicionais
        if (!empty($metodo_de_entrega['additional_time'])) {
            $entrega->PrazoEntrega = $entrega->PrazoEntrega + $metodo_de_entrega['additional_time'];
            $entrega->DiasAdicionais = $metodo_de_entrega['additional_time'];
        }

        // Custo adicional
        if (!empty($metodo_de_entrega['fee'])) {
            if (substr($metodo_de_entrega['fee'], -1) == '%') {
                $porcentagem = preg_replace('/[^0-9]/', '', $metodo_de_entrega['fee']);
                $entrega->Valor = ($entrega->Valor/100)*(100+$porcentagem);
                $entrega->Fee = $porcentagem.'%';
            } else {
                $entrega->Valor = $entrega->Valor + $metodo_de_entrega['fee'];
                $entrega->Fee = $metodo_de_entrega['fee'];
            }
        }

        return $entrega;

    }

    /**
     * Verifica se o retorno do WC_Correios é válido
     */
    public function verifica_retorno_wc_correios($array) {
        if (!is_array($array)) {
            return array('status' => 'erro', 'mensagem' => 'Erro desconhecido');
        }
        if (!array_key_exists('Valor', $array) || !array_key_exists('PrazoEntrega', $array)) {
            return array('status' => 'erro', 'mensagem' => 'Erro desconhecido.');
        }
        return $array;
    }

}

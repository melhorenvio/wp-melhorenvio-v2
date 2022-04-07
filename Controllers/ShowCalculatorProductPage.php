<?php

namespace Controllers;

/**
 * Controller to manage the product's paged calculator
 */
class ShowCalculatorProductPage
{
    protected $product;
    protected $height;
    protected $width;
    protected $length;
    protected $weight;
    protected $price;
    protected $id;
    protected $basePath;
    protected $baseUrl;
    protected $whereShowCalculator;

    public function __construct()
    {
        $this->basePath = __DIR__;
        $this->baseUrl =  plugin_dir_url(__FILE__);
        $this->whereShowCalculator = (new ConfigurationController())->getWhereCalculatorValue();

        add_action('wp_enqueue_scripts', array($this, 'enqueueCssJsFrontend'));
        add_action('wp_ajax_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete'));
        add_action('wp_ajax_nopriv_escutar_solicitacoes_de_frete', array($this, 'escutar_solicitacoes_de_frete'));
    }

    /**
     * Function to insert CSS and JS.
     *
     * @return void
     */
    public function enqueueCssJsFrontend()
    {
        wp_enqueue_script('produto', BASEPLUGIN_ASSETS . '/js/shipping-product-page.js', array('jquery'));
        wp_enqueue_script('produto-variacao', BASEPLUGIN_ASSETS . '/js/shipping-product-page-variacao.js', array('jquery'));
        wp_enqueue_script('calculator', BASEPLUGIN_ASSETS . '/js/calculator.js', array('jquery'));
    }

    /**
     * Function to display the calculator on the product page 
     * based on the location selected by the user
     *
     * @return void
     */
    public function insertCalculator()
    {
        add_action($this->whereShowCalculator, array($this, 'isProductSingle'));
    }

    /**
     * Function to check if is a single producu.
     *
     * @return void
     */
    public function isProductSingle()
    {
        global $product;
        if (is_product() && !$product->is_virtual('yes')) {
            $this->prepareProduct($product);
            add_action($this->whereShowCalculator, array($this, 'addCalculateShipping'), 11);
        }
    }

    /**
     * Function to define product properties in the calculator form
     *
     * @param object $product
     * @return void
     */
    public function prepareProduct($product)
    {
        $this->product = $product;
        $this->height = $product->get_height();
        $this->width = $product->get_width();
        $this->length = $product->get_length();
        $this->weight = $product->get_weight();
        $this->price = $product->get_price();
        $this->id = $product->get_id();
    }

    /**
     * Adiciona o HTML do cálculo de frete na página do produto
     */
    public function addCalculateShipping()
    {
        wp_enqueue_style('calculator-style', BASEPLUGIN_ASSETS . '/css/calculator.css');
        wp_enqueue_script('calculator-script', BASEPLUGIN_ASSETS . '/js/calculator.js');

        echo sprintf(
            "<div id='woocommerce-correios-calculo-de-frete-na-pagina-do-produto' class='containerCalculator'>
            <?php wp_nonce_field('solicita_calculo_frete', 'solicita_calculo_frete'); ?>
            <input type='hidden' id='calculo_frete_endpoint_url' value='%s'>
            <input type='hidden' id='calculo_frete_produto_altura' value='%f'>
            <input type='hidden' id='calculo_frete_produto_largura' value='%f'>
            <input type='hidden' id='calculo_frete_produto_comprimento' value='%f'>
            <input type='hidden' id='calculo_frete_produto_peso' value='%f'>
            <input type='hidden' id='calculo_frete_produto_preco' value='%f'>
            <input type='hidden' id='id_produto' value='%d'>
            <div class='calculatorRow'>
                <div class='row'>
                    <div class='col-75'>
                        <p>Simulação de frete</p>
                        <input 
                            type='text' 
                            maxlength='9' 
                            class='iptCep calculatorRow' 
                            id='inputCep' 
                            placeholder='Informe seu cep' 
                            onkeyup='%s'
                        >
                    </div>
                </div>
                <div id='calcular-frete-loader'>
                    <img src='https://s3.amazonaws.com/wordpress-v2-assets/img/loader.gif' />
                </div>
                <div class=resultado-frete tableResult>
                    <table>
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <small id='destiny-shipping-mehor-envio'></small></br>
                    <small class='observation-shipping-free'></small>
                </div>
            </div>
        </div>",
            admin_url('admin-ajax.php'),
            $this->height,
            $this->width,
            $this->length,
            $this->weight,
            $this->price,
            $this->id,
            'return usePostalCodeMask()'
        );
    }
}

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
        wp_enqueue_script('produto', BASEPLUGIN_ASSETS . '/js/shipping-product-page.js', 'jquery');
        wp_enqueue_script('produto-variacao', BASEPLUGIN_ASSETS . '/js/shipping-product-page-variacao.js', 'jquery');
        wp_enqueue_script('calculator', BASEPLUGIN_ASSETS . '/js/calculator.js', 'jquery');
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
                        <p>Calcule o frete e o prazo de entrega estimados para sua região</p>
						<p><strong style='color:Red;'>Frete grátis</strong> para pedido acima de R$120,00! <a href='https://makobaby.com.br/regra-frete-gratis/' style='text-decoration:underline;'>Consulte Regiões</a></p>
                        <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' version='1.1' id='delivery_truck' x='0px' y='0px' width='70px' viewBox='0 0 612 612' style='enable-background:new 0 0 612 612;' xml:space='preserve'><g><path d='M150.424,392.577c-31.865,0-57.697,25.832-57.697,57.697s25.832,57.697,57.697,57.697s57.697-25.832,57.697-57.697   S182.29,392.577,150.424,392.577z M150.424,479.123c-15.933,0-28.848-12.916-28.848-28.848c0-15.933,12.916-28.849,28.848-28.849   c15.932,0,28.848,12.916,28.848,28.849C179.272,466.207,166.357,479.123,150.424,479.123z M452.303,392.577   c-31.865,0-57.696,25.832-57.696,57.697s25.831,57.697,57.696,57.697c31.866,0,57.697-25.832,57.697-57.697   S484.168,392.577,452.303,392.577z M452.303,479.123c-15.933,0-28.848-12.916-28.848-28.848c0-15.933,12.916-28.849,28.848-28.849   c15.933,0,28.849,12.916,28.849,28.849C481.151,466.207,468.236,479.123,452.303,479.123z M602.438,371.778h-9.562v-87.295   c0-10.068-7.806-18.413-17.853-19.083L539.008,263c-11.154-0.744-21.201-7.007-26.778-16.694l-27.115-60.879   c-23.866-57.444-57.487-81.397-90.442-81.397H43.031C19.266,104.029,0,123.294,0,147.06v258.188   c0,23.766,19.266,43.031,43.031,43.031h31.251c1.07-41.109,34.774-74.246,76.141-74.246c41.368,0,75.071,33.137,76.141,74.246   h149.598c1.07-41.109,34.773-74.246,76.141-74.246c41.368,0,75.071,33.137,76.142,74.246h73.993c5.281,0,9.562-4.281,9.562-9.562   v-57.375C612,376.06,607.719,371.778,602.438,371.778z M449.664,257.607H346.04c-5.121,0-9.272-4.151-9.272-9.272v-83.503   c0-5.122,4.151-9.272,9.272-9.272h54.545c6.916,0,13.259,3.849,16.451,9.985l40.854,78.511   C461.102,250.227,456.622,257.607,449.664,257.607z'></path></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
						<input type='text' maxlength='9' class='iptCep calculatorRow' placeholder='Informe seu cep' onkeydown='%s'><a href='http://www.buscacep.correios.com.br/sistemas/buscacep/' target='_blank'>Não sei meu CEP</a>
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
            'return mascara(this, "#####-###")'
        );
    }
}

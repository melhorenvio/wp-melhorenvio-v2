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

    public function enqueueCssJsFrontend()
    {
        wp_enqueue_script('produto', BASEPLUGIN_ASSETS . '/js/shipping-product-page.js', 'jquery');
        wp_enqueue_script('produto-variacao', BASEPLUGIN_ASSETS . '/js/shipping-product-page-variacao.js', 'jquery');
        wp_enqueue_script('produto-shortcode', BASEPLUGIN_ASSETS . '/js/shipping-product-page-shortcode.js', 'jquery');
    }

    /**
     * Function to display the calculator on the product page 
     * based on the location selected by the user
     *
     * @return void
     */
    public function insertCalculator()
    {
        add_action($this->whereShowCalculator, array($this, 'isProdutoSingle'));
    }

    public function isProdutoSingle()
    {
        global $product;
        if (is_product() && !$product->is_virtual('yes')) {
            $this->preparaProduto($product);
            add_action($this->whereShowCalculator, array($this, 'addCalculateShipping'), 11);
        }
    }

    public function preparaProduto($product)
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

?>
        <div id="woocommerce-correios-calculo-de-frete-na-pagina-do-produto" class="containerCalculator">
            <?php wp_nonce_field('solicita_calculo_frete', 'solicita_calculo_frete'); ?>
            <input type="hidden" id="calculo_frete_endpoint_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" id="calculo_frete_produto_altura" value="<?php echo $this->height; ?>">
            <input type="hidden" id="calculo_frete_produto_largura" value="<?php echo $this->width; ?>">
            <input type="hidden" id="calculo_frete_produto_comprimento" value="<?php echo $this->length; ?>">
            <input type="hidden" id="calculo_frete_produto_peso" value="<?php echo $this->weight; ?>">
            <input type="hidden" id="calculo_frete_produto_preco" value="<?php echo $this->price; ?>">
            <input type="hidden" id="id_produto" value="<?php echo $this->id; ?>">
            <div class="calculatorRow">
                <div class="row">
                    <div class="col-75">
                        <p>Simulação de frete</p>
                        <input type="text" maxlength="9" class="iptCep calculatorRow" placeholder="Informe seu cep" onkeydown="return mascara(this, '#####-###');">
                    </div>
                </div>
                <div id="calcular-frete-loader">
                    <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/loader.gif" />
                </div>
                <div class="resultado-frete tableResult">
                    <table>
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <small class="observation-shipping-free"></small>
                </div>
            </div>
        </div>
<?php
    }
}

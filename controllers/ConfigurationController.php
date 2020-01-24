<?php

namespace Controllers;
use Models\Address;
use Models\Agency;
use Models\Store;
use Models\CalculatorShow;
use Models\JadlogAgenciesShow;
use Models\Method;

class ConfigurationController 
{
    /**
     * @param [type] $tokenUser
     * @return void
     */
    public function saveToken($tokenUser) 
    {
        $token = get_option('melhorenvio_token');
        if (!$token or empty($token)) {
            add_option('melhorenvio_token', $tokenUser);
        }

        update_option('melhorenvio_token', $tokenUser,true);
        return get_option('melhorenvio_token');
    }

    /**
     * @return void
     */
    public function getAddressShopping() 
    {
        echo json_encode((new Address())->getAddressesShopping());
        die;
    }

    /**
     * @return void
     */
    public function setAgencyJadlog() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o ID da agência'
            ]);
            die;
        }

        echo json_encode((new Agency())->setAgency($_GET['id']));
        die;
    }

    /**
     * @return void
     */
    public function getAgencyJadlog() 
    {
        echo json_encode((new Agency())->getAgencies());
        die;
    }

    /**
     * @return void
     */
    public function getStories() 
    {
        echo json_encode((new Store())->getStories());
        die;
    }

    /**
     * @return void
     */
    public function setStore() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o ID da loja'
            ]);
            die;
        }

        echo json_encode((new Store())->setStore($_GET['id']));
        die;
    }

    /**
     * @return void
     */
    public function get_calculator_show() 
    {
        echo json_encode((new CalculatorShow())->get());
        die;
    }

    /**
     * @return void
     */
    public function set_calculator_show() 
    {
        if (!isset($_GET['data'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o parametro data ("true" ou "false")'
            ]);
            die;
        }

        echo json_encode((new CalculatorShow())->set($_GET['data']));
        die;
    }

    /**
     * @return void
     */
    public function getMethodsEnables()
    {   
        $methods = [];

        $options = $this->getOptionsShipments();

        $enableds = (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }
            if (in_array($method->id, $enableds)) {
                $methods[] = [
                    'code' => $method->code,
                    'title' => str_replace(' (Melhor Envio)', '', $method->method_title),
                    'name' =>  (isset($options[$method->code]['name']) && $options[$method->code]['name'] != "undefined" && $options[$method->code]['name'] != "" ) ? $options[$method->code]['name'] : str_replace(' (Melhor Envio)', '', $method->method_title),
                    'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0 ,
                    'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0,
                    'perc' => (isset($options[$method->code]['perc'])) ? floatval($options[$method->code]['perc']) : 0, 
                    'ar' => (isset($options[$method->code]['ar']) && $options[$method->code]['ar'] == "true") ? true : false ,
                    'mp' => (isset($options[$method->code]['mp']) && $options[$method->code]['mp'] == "true") ? true : false
                ];
            }
        }

        echo json_encode($methods);die;
    }

    /**
     * @return array
     */
    public function getMethodsEnablesArray()
    {   
        $methods = [];

        $options = $this->getOptionsShipments();

        $enableds =  (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();

        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }

            if (in_array($method->id, $enableds)) {
                $methods[] = [
                    'code' => $method->code,
                    'title' => str_replace(' (Melhor Envio)', '', $method->method_title),
                    'name' =>  (isset($options[$method->code]['name']) && $options[$method->code]['name'] != "undefined" && $options[$method->code]['name'] != "" ) ? $options[$method->code]['name'] : str_replace(' (Melhor Envio)', '', $method->method_title),
                    'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0 ,
                    'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0,
                    'perc' => (isset($options[$method->code]['perc'])) ? floatval($options[$method->code]['perc']) : 0,
                    'ar' => (isset($options[$method->code]['ar']) && $options[$method->code]['ar'] == "true") ? true : false ,
                    'mp' => (isset($options[$method->code]['mp']) && $options[$method->code]['mp'] == "true") ? true : false 
                ];
            }
        }
                
        return $methods;
    }

    public function getStyle()
    {
        $style = [
            'calculo_de_frete' => [
                'style' => (get_option('calculo_de_frete')) ? get_option('calculo_de_frete') : '',
                'name'  => 'Div cálculo de frete',
                'id' => 'calculo_de_frete'
            ],
            'input_calculo_frete' => [
                'style' => (get_option('input_calculo_frete')) ? get_option('input_calculo_frete') : '',
                'name'  => 'Input cálculo de frete',
                'id'    => 'input_calculo_frete',
            ],
            'botao_calculo_frete' => [
                'style' => (get_option('botao_calculo_frete')) ? get_option('botao_calculo_frete') : '',
                'name'  => 'Botão cálculo de frete',
                'id' => 'botao_calculo_frete',
            ],
            'botao_imagem_calculo_frete' => [
                'style' => (get_option('botao_imagem_calculo_frete')) ? get_option('botao_imagem_calculo_frete') : '',
                'name'  => 'Imagem cálculo de frete',
                'id' => 'botao_imagem_calculo_frete',
            ],
            'botao_texto_calculo_frete' => [
                'style' => (get_option('botao_texto_calculo_frete')) ? get_option('botao_texto_calculo_frete') : '',
                'name'  => 'Texto do botão do cálculo de frete',
                'id' => 'botao_texto_calculo_frete',
            ]
        ];

        echo json_encode($style);die;
    }

    public function getStyleArray()
    {
        $style = [
            'calculo_de_frete' => [
                'style' => (get_option('calculo_de_frete')) ? get_option('calculo_de_frete') : '',
                'name'  => 'Div cálculo de frete',
                'id' => 'calculo_de_frete'
            ],
            'input_calculo_frete' => [
                'style' => (get_option('input_calculo_frete')) ? get_option('input_calculo_frete') : '',
                'name'  => 'Input cálculo de frete',
                'id'    => 'input_calculo_frete',
            ],
            'botao_calculo_frete' => [
                'style' => (get_option('botao_calculo_frete')) ? get_option('botao_calculo_frete') : '',
                'name'  => 'Botão cálculo de frete',
                'id' => 'botao_calculo_frete',
            ],
            'botao_imagem_calculo_frete' => [
                'style' => (get_option('botao_imagem_calculo_frete')) ? get_option('botao_imagem_calculo_frete') : '',
                'name'  => 'Imagem cálculo de frete',
                'id' => 'botao_imagem_calculo_frete',
            ],
            'botao_texto_calculo_frete' => [
                'style' => (get_option('botao_texto_calculo_frete')) ? get_option('botao_texto_calculo_frete') : '',
                'name'  => 'Texto do botão do cálculo de frete',
                'id' => 'botao_texto_calculo_frete',
            ]
        ];

        return $style;
    }

    public function saveStyle()
    {
        delete_option($_GET['id']);
        add_option($_GET['id'], $_GET['style']);
    }

    public function savePathPlugins()
    {
        if(empty($_GET['path'])) {
            delete_option('melhor_envio_path_plugins');
            die;
        }

        delete_option('melhor_envio_path_plugins');
        add_option('melhor_envio_path_plugins', $_GET['path']);
    }

    public function setPathPlugins($path)
    {
        delete_option('melhor_envio_path_plugins');
        add_option('melhor_envio_path_plugins', $path);

        return get_option('melhor_envio_path_plugins');
    }

    public function getPathPlugins()
    {
        $path = get_option('melhor_envio_path_plugins');

        if (!$path) {
            $path = ABSPATH . 'wp-content/plugins';
        }

        echo json_encode([
            'path' => $path
        ]); 
        die;
    }

    public function getPathPluginsArray()
    {
        $path = get_option('melhor_envio_path_plugins');

        if (!$path) {
            $path = ABSPATH . 'wp-content/plugins';
        }

        return $path;
    }

    public function saveoptionsMethod($item)
    {
        $id = $item['id'];

        delete_option('melhor_envio_option_method_shipment_' . $id);
        add_option('melhor_envio_option_method_shipment_' . $id, $item);

        return get_option('melhor_envio_option_method_shipment_' . $id);
    }

    public function getOptionsShipments()
    {   
        $codeStore = md5(get_option('home'));

        global $wpdb;
        $sql = "select * from " . $wpdb->prefix . "options where option_name like '%melhor_envio_option_method_shipment_%'";
        $results = $wpdb->get_results($sql);


        if (empty($results)) {
            return false;
        }

        $options = [];
        foreach ($results as $item) {

            if (empty($item->option_value)) {
                continue;
            }

            $data = unserialize($item->option_value);

            if (isset($data['id'])) {

                $options[$data['id']] = [
                    'name'       => $data['name'],
                    'tax'        => $data['tax'],
                    'time'       => $data['time'],
                    'perc'       => $data['perc'],
                    'mp'         => $data['mp'],
                    'ar'         => $data['ar'],
                    'code_modal' => 'code_shiping_' . $data['id']
                ];
            }
        }

        $_SESSION[$codeStore]['melhorenvio_options'] = $options;

        return $options;
    }


    public function setWhereCalculator($option) {

        delete_option('melhor_envio_option_where_show_calculator');
        add_option('melhor_envio_option_where_show_calculator', $option);

        return [
            'success' => true,
            'option' => $option
        ];
    }

    public function getWhereCalculator()
    {
        $option = get_option('melhor_envio_option_where_show_calculator');

        if (!$option) {
            echo json_encode([
                'option' => 'woocommerce_before_add_to_cart_button'
            ]);die;
        }
    
        echo json_encode([
            'option' => $option
        ]);die;
    }

    public function getWhereCalculatorValue()
    {
        $option = get_option('melhor_envio_option_where_show_calculator');
        if (!$option) {
            return 'woocommerce_before_add_to_cart_button';
        }
        return $option;
    }

    public function setOptionsCalculator($options)
    {
        delete_option('melhorenvio_ar');
        delete_option('melhorenvio_mp');

        add_option('melhorenvio_ar', $options['ar'], true);
        add_option('melhorenvio_mp', $options['mp'], true);

        return [
            'success' => true,
            'options' => [
                'ar' => (get_option('melhorenvio_ar', true) == 'true') ? true : false ,
                'mp' => (get_option('melhorenvio_mp', true) == 'true') ? true : false
            ]
        ];
    }

    public function getOptionsCalculator()
    {   
        $ar = get_option('melhorenvio_ar');
        $mp = get_option('melhorenvio_mp');

        return [
            'ar' => ($ar == 'true') ? true : false,
            'mp' => ($mp == 'true') ? true : false,
        ];
        die;
    }

    /**
     * Function to save all configs
     *
     * @param Array $data
     * @return json
     */
    public function saveAll($data)
    {   
        $response = [];

        if (isset($data['address'])) {
            $response['address'] = (new Address())->setAddressShopping($data['address']);
        }

        if (isset($data['store'])) {
            $response['store'] = (new Store())->setStore($data['store']);
        }

        if (isset($data['agency'])) {
            $response['agency'] = (new Agency())->setAgency($data['agency']);
        }

        if (isset($data['show_calculator'])) {
            $response['show_calculator'] = (new CalculatorShow())->set($data['show_calculator']);
        }

        if (isset($data['show_all_agencies_jadlog'])) {
            $response['show_all_agencies_jadlog'] = (new JadlogAgenciesShow())->set($data['show_all_agencies_jadlog']);
        }

        if (isset($data['methods_shipments'])) {
            foreach ($data['methods_shipments'] as $key => $method) {
                $response['method'][$key] = $this->saveoptionsMethod($method);
            }
        }

        if (isset($data['where_calculator'])) {
            $response['where_calculator'] = $this->setWhereCalculator($data['where_calculator']);
        }

        if (isset($data['path_plugins'])) {
            $response['path_plugins'] = $this->setPathPlugins($data['path_plugins']);
        }

        if (isset($data['options_calculator'])) {
            $response['options_calculator'] = $this->setOptionsCalculator($data['options_calculator']);
        }
        return wp_send_json($response, 200);
    }
    
}

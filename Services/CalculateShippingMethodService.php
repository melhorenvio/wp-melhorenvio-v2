<?php 

namespace Services;

use Helpers\MoneyHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;

class CalculateShippingMethodService
{
    /**
     * function to calculate shipping each shipping method.
     *
     * @param array $package
     * @param string $code
     * @param string $id
     * @param string $company
     * @return array
     */
    public function calculate_shipping( $package = [], $code, $id, $company)
    {
        $to = preg_replace('/\D/', '', $package['destination']['postcode']);

        $products = (isset($package['cotationProduct'])) ? $package['cotationProduct'] : (new CartWooCommerceService())->getProducts();

        $result = (new QuotationService())->calculateQuotationByProducts($products, $to, $code);

        if ($result) {

            if (isset($result->name) && isset($result->price)) {

                $method = (new OptionsHelper())->getName($result->id, $result->name, null, null);

                return [
                    'id' => $id,
                    'label' => $method['method'] . (new TimeHelper)->setLabel($result->delivery_range, $code, $result->custom_delivery_range),
                    'cost' => (new MoneyHelper())->setprice($result->price, $code),
                    'calc_tax' => 'per_item',
                    'meta_data' => [
                        'delivery_time' => $result->delivery_range,
                        'company' => $company,
                        'name' => $method['method']
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @param  array $package Cart package.
     * @param int $shippingClassId
     * @return bool
     */
    public function hasOnlySelectedShippingClass( $package, $shippingClassId ) 
    {    
        $onlySelected = true;

        if(-1 === $shippingClassId){
            return $onlySelected;
        }

        foreach ( $package['contents'] as $values ) {
            $product = $values['data'];
            $qty     = $values['quantity'];

            if($product->get_shipping_class_id() == 0 ){
                $onlySelected = true;
                break;
            }

            if($qty > 0 && $product->needs_shipping()){
                if ( $shippingClassId !== $product->get_shipping_class_id() ) {
                    $onlySelected = false;
                    break;
                }
            }
        }

        return $onlySelected;
    }

    /**
     * Get shipping classes options.
     *
     * @return array
     */
    public function getShippingClassesOptions() {
        $shipping_classes = WC()->shipping->get_shipping_classes();
        $options          = array(
            '-1' => 'Qualquer classe de entrega',
            '0'  => 'Sem classe de entrega',
        );

        if ( ! empty( $shipping_classes ) ) {
            $options += wp_list_pluck( $shipping_classes, 'name', 'term_id' );
        }

        return $options;
    }
}
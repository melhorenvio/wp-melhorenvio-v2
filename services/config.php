<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpmelhorenvio_getPrefixService($service_id) {

    switch ($service_id) {
        case 1:
            return 'woocommerce_pac_';
            break;
        case 2:
            return 'woocommerce_sedex_';
            break;
        case 3:
            return 'woocommerce_jadlog_package_';
            break;
        case 4:
            return 'woocommerce_jadlog_com_';
            break;
        case 7  :
            return 'wpmelhorenvio_Jamef_Rodoviário_';
            break;
        case 9  :
            return 'wpmelhorenvio_via_brasil_';
            break;
        default:
            return null;
    }
}

function getCodeServiceByMethodId($name) {
    
    //TODO check it
    if ($name == 'wpmelhorenvio_Correios_PAC') {
        return 1;
    }

    if ($name == 'wpmelhorenvio_Correios_SEDEX') {
        return 2;
    }

    if ($name == 'wpmelhorenvio_Correios_EXPRESSO') {
        return 2;
    }

    if ($name == 'wpmelhorenvio_JadLog_.Package') {
        return 3;
    }

    if ($name == 'wpmelhorenvio_JadLog_.Com') {
        return 4;
    }

    if ($name == 'wpmelhorenvio_Jamef_Rodoviário') {
        return 7;
    }

    if ($name == 'wpmelhorenvio_via_brasil') {
        return 9;
    }

    if ($name == 'pac') {
        return 1;
    }

    if ($name == 'sedex') {
        return 2;
    }

    if ($name == 'jadlog_package') {
        return 3;
    }

    if ($name == 'jadlog_com') {
        return 4;
    }

    if ($name == 'via_brasil') {
        return 9;
    }

    return null;
}

function getnameServiceByCode($code) {

    if ($code == 1) {
        return 'wpmelhorenvio_Correios_PAC';
    }

    if ($code == 2) {
        return 'wpmelhorenvio_Correios_SEDEX';
    }

    if ($code == 2) {
        return 'wpmelhorenvio_Correios_EXPRESSO';
    }

    if ($code == 3) {
        return 'wpmelhorenvio_JadLog_.Package';
    }

    if ($code == 4) {
        return 'wpmelhorenvio_JadLog_.Com';
    }

    if ($code == 7) {
        return 'wpmelhorenvio_Jamef_Rodoviário';
    }

    if ($code == 9) {
        return 'wpmelhorenvio_via_brasil';
    }

    return null;
}

function getnameDisplayServiceByCode($code) {

    if ($code == 1) {
        return 'Pac';
    }

    if ($code == 2) {
        return 'Sedex';
    }

    if ($code == 3) {
        return 'Jadlog Package';
    }

    if ($code == 4) {
        return 'Jadlog .Com';
    }

    if ($code == 7) {
        return 'Jamef';
    }

    if ($code == 9) {
        return 'Via Brasil';
    }

    return null;
}

function getPrefixServiceByCode($code) {

    if ($code == 1) {
        return 'pac';
    }

    if ($code == 2) {
        return 'sedex';
    }

    if ($code == 3) {
        return 'jadlog_package';
    }

    if ($code == 4) {
        return 'jadlog_com';
    }

    if ($code == 7) {
        return 'jamef';
    }

    if ($code == 9) {
        return 'via_brasil';
    }

    return null;
}

function getServicesActive() {
    return ['1', '2', '3', '4', '7', '9'];
}

/**
 * Get shipping classes options.
 *
 * @return array
 */
function get_shipping_classes_options() {
    $shipping_classes = WC()->shipping->get_shipping_classes();
    $options          = array(
        '' => 'Selecione o tipo de classe de entrega',
    );

    if ( ! empty( $shipping_classes ) ) {
        $options += wp_list_pluck( $shipping_classes, 'name', 'slug' );
    }

    return $options;
}

function getCustomName($service_id) {
    $prefix = getPrefixServiceByCode($service_id);
    $name = get_option('woocommerce_' . $prefix . '_title_custom_shipping');

    if (!$name) {
        return getnameDisplayServiceByCode($service_id);
    }

    return $name;
}

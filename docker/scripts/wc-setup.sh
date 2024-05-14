#!/bin/bash

WOOCOMMERCE='wp --allow-root wc --user=melhorenvio'

# Create shipping zone
$WOOCOMMERCE shipping_zone create --user=melhorenvio --name="Padrão"

# Create shipping zone methods for Melhor Envio
$WOOCOMMERCE shipping_method list --user=melhorenvio --field=id | grep melhorenvio | xargs -I {} $WOOCOMMERCE --user=melhorenvio shipping_zone_method create 1 --method_id={}

# Enable every payment methods
$WOOCOMMERCE --user=melhorenvio payment_gateway list --field=id | xargs -I {} $WOOCOMMERCE --user=melhorenvio payment_gateway update {} --enabled=true

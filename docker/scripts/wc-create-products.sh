#!/bin/bash

WOOCOMMERCE='wp --allow-root wc --user=melhorenvio'

# Create fake products
$WOOCOMMERCE --user=melhorenvio product create --name="Camisa" --regular_price="49.99"
$WOOCOMMERCE --user=melhorenvio product create --name="Tênis" --regular_price="199.99"
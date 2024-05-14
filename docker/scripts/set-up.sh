#!/bin/bash

echo 'Setting up...'

# Install dependencies
cd wp-content/plugins/melhor-envio-cotacao
rm -rf vendor node_modules composer.lock package-lock.json
composer install
npm i
npm run build 
cd /var/www/html

# Set up Wordpress
WORDPRESS='wp --allow-root'

$WORDPRESS core install --url=127.0.0.1:$HOST_PORT --title="Loja de teste" --admin_user=melhorenvio --admin_password=melhorenvio --admin_email=squad-integrations@melhorenvio.com --skip-email

# Fix permissions to allow instalation of plugins from the web page
mkdir /var/www/html/wp-content/upgrade
chown -R www-data:www-data /var/www/html/wp-content/upgrade
chown -R www-data:www-data /var/www/html/wp-content/uploads

# Delete example plugins
$WORDPRESS plugin delete --all --exclude=melhor-envio-cotacao

# Install plugins
$WORDPRESS plugin install woocommerce
$WORDPRESS plugin install woocommerce-extra-checkout-fields-for-brazil
$WORDPRESS plugin install wpc-composite-products
$WORDPRESS plugin install woo-product-bundle
$WORDPRESS plugin install query-monitor

# Activate plugins
$WORDPRESS plugin activate woocommerce
$WORDPRESS plugin activate woocommerce-extra-checkout-fields-for-brazil
$WORDPRESS plugin activate melhor-envio-cotacao
$WORDPRESS plugin activate query-monitor

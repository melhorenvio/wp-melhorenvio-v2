#!/bin/bash

set -e

WORDPRESS='wp --allow-root'
PLUGIN_PATH='/var/www/html/wp-content/plugins/melhor-envio-cotacao'

echo "Installing Composer dependencies..."
cd "$PLUGIN_PATH"
composer install --no-interaction --no-progress
cd /var/www/html

echo "Installing WordPress..."
$WORDPRESS core install \
    --url="${WORDPRESS_URL:-http://localhost}" \
    --title="${WORDPRESS_TITLE:-Melhor Envio Cotação}" \
    --admin_user="${WORDPRESS_ADMIN_USER:-melhorenvio}" \
    --admin_password="${WORDPRESS_ADMIN_PASSWORD:-melhorenvio}" \
    --admin_email="${WORDPRESS_ADMIN_EMAIL:-dev@melhorenvio.com}" \
    --skip-email

echo "Setting permalink structure..."
$WORDPRESS rewrite structure '/%postname%/' --hard

echo "Installing WooCommerce..."
if ! $WORDPRESS plugin is-installed woocommerce 2>/dev/null; then
    $WORDPRESS plugin install woocommerce --activate
else
    echo "WooCommerce already installed"
    $WORDPRESS plugin activate woocommerce 2>/dev/null || true
fi

echo "Activating plugin..."
$WORDPRESS plugin activate melhor-envio-cotacao 2>/dev/null || true

echo "Waiting for WooCommerce to be ready..."
max_attempts=10
attempt=0
while [ $attempt -lt $max_attempts ]; do
    result=$($WORDPRESS eval "echo (class_exists('WooCommerce') && function_exists('WC') && function_exists('wc_get_product_id_by_sku')) ? '1' : '0';" 2>/dev/null | tr -d '\r\n' || echo "0")
    if [ "$result" = "1" ]; then
        break
    fi
    echo "Waiting... ($((attempt+1))/$max_attempts)"
    sleep 2
    attempt=$((attempt+1))
done

echo "Running seeders..."
$WORDPRESS eval-file "$PLUGIN_PATH/bin/seed.php" 2>/dev/null || echo "Warning: Seeders failed or already seeded"

echo "Setup complete!"

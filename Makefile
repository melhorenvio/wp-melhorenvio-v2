.PHONY: help init up down attach shell install wc-setup wc-create-products patch-woocommerce tunnel-up tunnel-down mi-up mi-network mi-wp-config mi-info mi-trust-ca mi-down mi-clean

# Variables
COMPOSE         := docker compose
APP_CONTAINER   := wp-melhorenvio-cotacao
PLUGIN_PATH     := /var/www/html/wp-content/plugins/melhor-envio-cotacao
SAIL_NET        := tray-native_sail
WP_EXEC         := $(COMPOSE) exec -T --user www-data wordpress
BACK_DIR        := ../tray-native
FRONT_DIR       := ../melhor-integrador-app
BACK_MYSQL      := docker compose -f $(BACK_DIR)/compose.yml exec -T mysql mysql -u tray_user -ptray_password tray_native

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Full reset: down -v + rebuild + setup
	$(COMPOSE) down -v
	$(COMPOSE) up -d --build --force-recreate
	docker exec $(APP_CONTAINER) $(PLUGIN_PATH)/scripts/setup.sh

up: ## Start containers (HTTP mode)
	$(COMPOSE) up -d

down: ## Stop all containers
	$(COMPOSE) down

attach: ## Attach bash to WP container (root)
	docker exec -it $(APP_CONTAINER) bash

shell: ## Open shell in WP container (www-data)
	$(COMPOSE) exec --user www-data wordpress /bin/bash

install: ## Install Composer + npm dependencies
	$(COMPOSE) exec --user www-data wordpress sh -c "cd $(PLUGIN_PATH) && composer install && npm i && npm run build"

wc-setup: ## Create WooCommerce shipping zones and payment methods
	docker exec $(APP_CONTAINER) $(PLUGIN_PATH)/scripts/wc-setup.sh

wc-create-products: ## Create sample products in WooCommerce
	docker exec $(APP_CONTAINER) $(PLUGIN_PATH)/scripts/wc-create-products.sh

patch-woocommerce: ## Patch WooCommerce for local dev (remove SSL validation on callback_url)
	docker compose exec --user root -T wordpress sh -c "patch --forward --reject-file=- -p2 -d /var/www/html/wp-content/plugins" < patches/wc-auth-remove-ssl-check.patch || true

tunnel-up: ## Start tunnel and update WordPress URLs to tunnel address
	@if [ -f .env ]; then export $$(grep -v '^#' .env | grep -v '^$$' | xargs); fi; \
	TUNNEL_URL=$${TUNNEL_URL:-https://cotacao.lt.melhorenvio.work}; \
	echo "Updating WordPress URLs to tunnel address..."; \
	$(WP_EXEC) wp option update siteurl $$TUNNEL_URL --quiet > /dev/null 2>&1; \
	$(WP_EXEC) wp option update home $$TUNNEL_URL --quiet > /dev/null 2>&1; \
	$(WP_EXEC) wp config set WP_HOME $$TUNNEL_URL --type=constant --quiet > /dev/null 2>&1; \
	$(WP_EXEC) wp config set WP_SITEURL $$TUNNEL_URL --type=constant --quiet > /dev/null 2>&1; \
	echo "Starting localtunnel..."; \
	$(COMPOSE) up localtunnel; \
	echo "Tunnel started and WordPress URLs updated to: $$TUNNEL_URL";

tunnel-down: ## Stop tunnel and restore WordPress URLs to localhost
	@if [ -f .env ]; then export $$(grep -v '^#' .env | grep -v '^$$' | xargs); fi; \
	LOCAL_URL=$${WORDPRESS_SITEURL:-https://cotacao.localhost}; \
	echo "Stopping localtunnel and restoring WordPress URLs..."; \
	$(COMPOSE) stop localtunnel; \
	$(WP_EXEC) wp option update siteurl $$LOCAL_URL --quiet > /dev/null 2>&1; \
	$(WP_EXEC) wp option update home $$LOCAL_URL --quiet > /dev/null 2>&1; \
	$(WP_EXEC) wp config delete WP_HOME --type=constant --quiet > /dev/null 2>&1 || true; \
	$(WP_EXEC) wp config delete WP_SITEURL --type=constant --quiet > /dev/null 2>&1 || true; \
	echo "Tunnel stopped and WordPress URLs restored to: $$LOCAL_URL";

# =====================================================================
# Fluxo de onboard embedado (WordPress + WooCommerce) — proxy caddy local
# =====================================================================
# Hostnames estaveis que resolvem igual do browser e de qualquer container:
#   browser (Windows/host):   *.localhost  -> 127.0.0.1 -> caddy
#   container backend (sail): *.localhost  -> docker DNS -> caddy
#   container WordPress:      *.me.test    -> docker DNS -> caddy
#                             (*.localhost sofre hijack de loopback no glibc 2.33+)
# Uso: make mi-up   (sobe tudo)   |   make mi-down   (derruba o que e local)

mi-up: ## Sobe o fluxo completo (backend + frontend + WP + caddy + rede + config)
	@echo "==> [1/5] Backend (tray-native / sail)"
	-cd $(BACK_DIR) && docker compose up -d
	@echo "==> [2/5] Frontend (melhor-integrador-app: wordpress)"
	-cd $(FRONT_DIR) && docker compose up -d wordpress
	@echo "==> [3/5] WordPress + DB + caddy"
	$(COMPOSE) up -d wordpress db caddy
	@$(MAKE) --no-print-directory mi-network
	@$(MAKE) --no-print-directory mi-wp-config
	@$(MAKE) --no-print-directory mi-info

mi-network: ## Conecta frontend + me-proxy + plugin a rede compartilhada (idempotente)
	@echo "==> [4/5] Conectando containers a $(SAIL_NET)"
	-@docker network connect --alias wordpress $(SAIL_NET) melhor-integrador-app-wordpress-1 2>/dev/null || true
	-@docker network connect --alias int-1-proxy $(SAIL_NET) melhor-integrador-app-int-1-proxy-1 2>/dev/null || true
	-@docker network connect $(SAIL_NET) $(APP_CONTAINER) 2>/dev/null || true
	@echo "    ok"

mi-wp-config: ## Garante WooCommerce + plugin + siteurl/home do WP
	@echo "==> [5/5] Configurando WordPress (siteurl/home, WooCommerce, plugin)"
	@echo "    Aguardando WordPress inicializar..."
	@i=0; until $(WP_EXEC) wp --info 2>/dev/null | grep -q 'WP-CLI'; do \
		i=$$((i+1)); if [ $$i -ge 30 ]; then echo "ERRO: WordPress nao iniciou em 90s" >&2; exit 1; fi; \
		echo "    ... aguardando ($$i/30)"; sleep 3; \
	done
	@if [ -f .env ]; then export $$(grep -v '^#' .env | grep -v '^$$' | xargs) 2>/dev/null; fi; \
	$(WP_EXEC) wp core is-installed 2>/dev/null || $(WP_EXEC) wp core install \
		--url="$${WORDPRESS_SITEURL:-https://cotacao.localhost}" \
		--title="$${WORDPRESS_TITLE:-Melhor Envio Cotação}" \
		--admin_user="$${WORDPRESS_ADMIN_USER:-melhorenvio}" \
		--admin_password="$${WORDPRESS_ADMIN_PASSWORD:-melhorenvio}" \
		--admin_email="$${WORDPRESS_ADMIN_EMAIL:-dev@melhorenvio.com}" \
		--skip-email; true
	-docker exec $(APP_CONTAINER) sh -c "cd $(PLUGIN_PATH) && composer install --no-interaction --no-progress"
	@if [ -f .env ]; then export $$(grep -v '^#' .env | grep -v '^$$' | xargs) 2>/dev/null; fi; \
	WP_URL=$${WORDPRESS_SITEURL:-https://cotacao.localhost}; \
	$(WP_EXEC) wp option update siteurl $$WP_URL && \
	$(WP_EXEC) wp option update home $$WP_URL
	-$(WP_EXEC) wp plugin is-installed woocommerce || $(WP_EXEC) wp plugin install woocommerce --activate
	-$(WP_EXEC) wp plugin activate woocommerce
	-$(WP_EXEC) wp plugin activate melhor-envio-cotacao
	-$(MAKE) --no-print-directory patch-woocommerce
	@echo "    Executando seeders..."
	-$(WP_EXEC) wp eval-file $(PLUGIN_PATH)/bin/seed.php

mi-info: ## Mostra as URLs de acesso
	@echo ""
	@echo "  Ambiente Melhor Envio Cotacao no ar:"
	@echo "     WordPress admin : https://cotacao.localhost/wp-admin  (melhorenvio / melhorenvio)"
	@echo "     Backend (API)   : https://app.localhost"
	@echo "     Frontend (SPA)  : https://front.localhost"
	@echo ""
	@echo "  Menu 'Melhor Integrador' sob WooCommerce abre o iframe de integracao."
	@echo "  (HTTPS usa CA interna do caddy — rode 'make mi-trust-ca' e confie na CA.)"

mi-trust-ca: ## Exporta a CA interna do caddy p/ confiar no navegador (HTTPS .localhost)
	docker cp caddy:/data/caddy/pki/authorities/local/root.crt ./caddy/caddy-root-ca.crt
	@echo ""
	@echo "  CA exportada para: ./caddy/caddy-root-ca.crt"
	@echo ""
	@echo "  Windows (WSL2):"
	@echo "    (no WSL)            cp ./caddy/caddy-root-ca.crt /mnt/c/Users/<seu-usuario>/"
	@echo '    (PowerShell Admin)  certutil -addstore -f "ROOT" C:\\Users\\<seu-usuario>\\caddy-root-ca.crt'
	@echo ""
	@echo "  Linux:"
	@echo "    sudo cp ./caddy/caddy-root-ca.crt /usr/local/share/ca-certificates/ && sudo update-ca-certificates"
	@echo ""
	@echo "  Depois reinicie o navegador."

mi-down: ## Derruba caddy + WordPress local + frontend (mantem backend)
	-$(COMPOSE) stop caddy wordpress
	-cd $(FRONT_DIR) && docker compose stop wordpress
	@echo "Ambiente parado (backend mantido)."

mi-clean: ## Reseta dados do onboard para novo fluxo (nao derruba containers)
	@echo "==> [1/4] WP: deletando options do integrador..."
	-$(WP_EXEC) wp option delete melhor_envio_integrador_secret 2>/dev/null || true
	-$(WP_EXEC) wp option delete melhor_envio_integrador_quotation_token 2>/dev/null || true
	-$(WP_EXEC) wp option delete melhor_envio_integrador_signature_key 2>/dev/null || true
	@echo "==> [2/4] WP: deletando consumer keys WooCommerce..."
	-docker exec wp-melhorenvio-cotacao-db mysql -u wordpress -pwordpress wordpress \
	    -e "DELETE FROM wp_woocommerce_api_keys;" 2>/dev/null || true
	@echo "==> [3/4] Backend: deletando stores WP + dados associados..."
	-$(BACK_MYSQL) -e \
	    "SET FOREIGN_KEY_CHECKS=0; \
	     DELETE FROM credentials WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM addresses WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM marketplace_store WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM store_default_carriers WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM store_shipping_settings WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM synchronization_settings WHERE store_id IN (SELECT id FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'); \
	     DELETE FROM stores WHERE identity LIKE '%localhost%' OR identity LIKE '%.me.test'; \
	     SET FOREIGN_KEY_CHECKS=1;" 2>/dev/null || true
	@echo "==> [4/4] Backend: limpando sessoes..."
	-docker compose -f $(BACK_DIR)/compose.yml exec -T app sh -c \
	    "rm -f /var/www/html/storage/framework/sessions/*" 2>/dev/null || true
	@echo ""
	@echo "  Ambiente limpo. Pode iniciar novo onboard."

APP_CONTAINER_NAME = wp-plugin-app

init:
	@docker compose -f ./docker/docker-compose.yml down -v
	@docker compose -f ./docker/docker-compose.yml up -d --build --force-recreate
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/setup.sh

up:
	@docker compose -f ./docker/docker-compose.yml up -d

down:
	@docker compose -f ./docker/docker-compose.yml down

attach:
	@docker exec -it $(APP_CONTAINER_NAME) bash

wc-setup:
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/wc-setup.sh

wc-create-fake-products:
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/wc-create-fake-products.sh

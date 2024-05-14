APP_CONTAINER_NAME = wp-plugin-app

init:
	@docker compose -f ./docker/docker-compose.yml down -v
	@docker compose -f ./docker/docker-compose.yml up -d --build --force-recreate
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/set-up.sh

up:
	@docker compose -f ./docker/docker-compose.yml up -d

down:
	@docker compose -f ./docker/docker-compose.yml down

attach:
	@docker exec -it $(APP_CONTAINER_NAME) bash

wc-setup:
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/wc-set-up.sh

wc-create-example-products:
	@docker exec $(APP_CONTAINER_NAME) wp-content/plugins/melhor-envio-cotacao/docker/scripts/wc-create-example-products.sh
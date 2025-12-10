.PHONY: help test test-all test-php84 test-php83 test-php82 clean build shell

# Expo t UID and GID for docker-compose
export GID := $(shell id -g)

help: ## Mostra este help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Constrói as imagens Docker
	docker compose build

test: ## Roda os testes com PHP 8.3 e Laravel 12 (padrão)
	docker compose run --rm php83-laravel12

test-all: ## Roda os testes em todas as combinações
	@echo "🧪 Testando PHP 8.4 + Laravel 12..."
	@docke -compose run --rm php84-laravel12 || true
	@echo "\n🧪 Testando PHP 8.3 + Laravel 12..."
	@docke -compose run --rm php83-laravel12 || true
	@echo "\n🧪 Testando PHP 8.3 + Laravel 11..."
	@docke -compose run --rm php83-laravel11 || true
	@echo "\n🧪 Testando PHP 8.2 + Laravel 11..."
	@docke -compose run --rm php82-laravel11 || true

test-php84: ## Testa apenas com PHP 8.4
	docker compose run --rm php84-laravel12

test-php83-l12: ## Testa com PHP 8.3 e Laravel 12
	docker compose run --rm php83-laravel12

test-php83-l11: ## Testa com PHP 8.3 e Laravel 11
	docker compose run --rm php83-laravel11

test-php82: ## Testa com PHP 8.2 e Laravel 11
	docker compose run --rm php82-laravel11

test-filter: ## Roda teste específico. Use: make test-filter FILTER="nome do teste"
	@if [ -z "$(FILTER)" ]; then \
		echo "❌ Erro: Especifique um filtro. Exemplo: make test-filter FILTER='can upload'"; \
	else \
		docker compose run --rm php83-laravel12 vendor/bin/pest --filter="$(FILTER)"; \
	fi

test-group: ## Roda grupo de testes. Use: make test-group GROUP="nome do grupo"
	@if [ -z "$(GROUP)" ]; then \
		echo "❌ Erro: Especifique um grupo. Exemplo: make test-group GROUP='gallery-media-field'"; \
	else \
		docker compose run --rm php83-laravel12 vendor/bin/pest --group="$(GROUP)"; \
	fi

test-file: ## Roda arquivo específico. Use: make test-file FILE="caminho/do/arquivo"
	@if [ -z "$(FILE)" ]; then \
		echo "❌ Erro: Especifique um arquivo. Exemplo: make test-file FILE='tests/Feature/ImageModelTest.php'"; \
	else \
		docker compose run --rm php83-laravel12 vendor/bin/pest "$(FILE)"; \
	fi

shell: ## Abre shell no container
	docker compose run --rm php-cli bash

clean: ## Remove containers e imagens
	docker compose down -v
	docker system prune -f

coverage: ## Gera relatório de cobertura de testes (requer Xdebug)
	docker compose run --rm -e XDEBUG_MODE=coverage php83-laravel12 vendor/bin/pest --coverage

coverage-html: ## Gera relatório de cobertura em HTML (requer Xdebug)
	docker compose run --rm -e XDEBUG_MODE=coverage php83-laravel12 vendor/bin/pest --coverage --coverage-html=coverage
	@echo "Relatório gerado em: coverage/index.html"

install: ## Instala as dependências
	docker compose run --rm php-cli composer install

update: ## Atualiza as dependências
	docker compose run --rm php-cli composer update

composer: ## Executa comando composer. Use: make composer CMD="require pacote/nome"
	@if [ -z "$(CMD)" ]; then \
		echo "❌ Erro: Especifique um comando. Exemplo: make composer CMD='require pacote/nome'"; \
	else \
		docker compose run --rm php-cli composer $(CMD); \
	fi

ps: ## Lista containers em execução
	docker compose ps

logs: ## Mostra logs dos containers
	docker compose logs -f

stop: ## Para todos os containers
	docker compose stop

restart: ## Reinicia os containers
	docker compose restart

rebuild: ## Reconstrói tudo do zero
	docker compose down -v
	docker compose build --no-cache
	docker compose run --rm php-cli composer install

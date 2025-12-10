#!/bin/bash

# Script para rodar testes localmente sem Docker (alternativa)

set -e

echo "🧪 Rodando testes do Filament Media Gallery"
echo "=========================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para verificar se o composer está instalado
check_composer() {
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}❌ Composer não encontrado. Instale o Composer primeiro.${NC}"
        exit 1
    fi
}

# Função para verificar versão do PHP
check_php_version() {
    local required_version=$1
    local current_version=$(php -r 'echo PHP_VERSION;' | cut -d. -f1,2)

    echo -e "${BLUE}ℹ️  PHP versão atual: ${current_version}${NC}"

    if [ "$required_version" != "any" ]; then
        echo -e "${YELLOW}⚠️  Requer PHP ${required_version}${NC}"
    fi
}

# Função para rodar testes
run_tests() {
    local php_version=$1
    local laravel_version=$2
    local testbench_version=$3

    echo -e "\n${YELLOW}Testando com Laravel ${laravel_version}${NC}"
    echo "----------------------------------------"

    check_php_version "$php_version"

    # Backup do composer.lock atual
    if [ -f "composer.lock" ]; then
        cp composer.lock composer.lock.backup
    fi

    # Instala dependências específicas
    echo -e "${BLUE}📦 Instalando dependências...${NC}"
    composer require "laravel/framework:${laravel_version}" \
                     "orchestra/testbench:${testbench_version}" \
                     "nesbot/carbon:3.*" \
                     "nunomaduro/collision:8.*" \
                     --no-interaction --no-update --dev

    composer update --prefer-stable --prefer-dist --no-interaction

    # Roda os testes
    echo -e "\n${BLUE}🧪 Executando testes...${NC}"
    if vendor/bin/pest --ci; then
        echo -e "${GREEN}✓ Testes passaram!${NC}"
        RESULT=0
    else
        echo -e "${RED}✗ Testes falharam!${NC}"
        RESULT=1
    fi

    # Restaura composer.lock
    if [ -f "composer.lock.backup" ]; then
        mv composer.lock.backup composer.lock
    fi

    return $RESULT
}

# Função para mostrar ajuda
show_help() {
    echo ""
    echo "Uso: ./test.sh [comando] [argumentos]"
    echo ""
    echo "Comandos disponíveis:"
    echo "  all              - Roda testes em todas as versões do Laravel"
    echo "  laravel12        - Testa com Laravel 12"
    echo "  laravel11        - Testa com Laravel 11"
    echo "  quick            - Roda testes com configuração atual (mais rápido)"
    echo "  filter 'texto'   - Filtra testes por nome"
    echo "  group 'nome'     - Roda grupo específico de testes"
    echo "  file 'caminho'   - Roda arquivo específico"
    echo "  coverage         - Gera relatório de cobertura"
    echo "  watch            - Modo watch (reexecuta ao salvar)"
    echo "  parallel         - Roda testes em paralelo"
    echo "  help             - Mostra esta ajuda"
    echo ""
    echo "Exemplos:"
    echo "  ./test.sh                           # Roda todos os testes"
    echo "  ./test.sh quick                     # Roda testes com config atual"
    echo "  ./test.sh laravel12                 # Testa Laravel 12"
    echo "  ./test.sh filter 'can upload'       # Filtra testes"
    echo "  ./test.sh group 'gallery-media-field' # Roda grupo"
    echo "  ./test.sh file tests/Feature/ImageModelTest.php"
    echo "  ./test.sh coverage                  # Gera cobertura"
    echo "  ./test.sh watch                     # Modo watch"
    echo ""
}

# Verifica composer
check_composer

# Menu de opções
case "${1:-quick}" in
    "all")
        echo -e "${BLUE}🚀 Rodando todos os testes...${NC}"
        FAILED=0

        echo -e "\n${YELLOW}═══════════════════════════════════════${NC}"
        echo -e "${YELLOW}  Testando Laravel 12${NC}"
        echo -e "${YELLOW}═══════════════════════════════════════${NC}"
        run_tests "8.3" "12.*" "10.*" || FAILED=1

        echo -e "\n${YELLOW}═══════════════════════════════════════${NC}"
        echo -e "${YELLOW}  Testando Laravel 11${NC}"
        echo -e "${YELLOW}═══════════════════════════════════════${NC}"
        run_tests "8.2" "11.*" "9.*" || FAILED=1

        if [ $FAILED -eq 0 ]; then
            echo -e "\n${GREEN}✓✓✓ Todos os testes passaram! ✓✓✓${NC}"
        else
            echo -e "\n${RED}✗✗✗ Alguns testes falharam! ✗✗✗${NC}"
            exit 1
        fi
        ;;

    "laravel12")
        run_tests "8.3" "12.*" "10.*"
        ;;

    "laravel11")
        run_tests "8.2" "11.*" "9.*"
        ;;

    "quick")
        echo -e "${BLUE}⚡ Modo rápido - usando configuração atual...${NC}"
        check_php_version "any"
        vendor/bin/pest
        ;;

    "filter")
        if [ -z "$2" ]; then
            echo -e "${RED}❌ Erro: Especifique um filtro${NC}"
            echo "Uso: ./test.sh filter 'nome do teste'"
            exit 1
        fi
        echo -e "${BLUE}🔍 Filtrando testes por: ${YELLOW}$2${NC}"
        vendor/bin/pest --filter="$2"
        ;;

    "group")
        if [ -z "$2" ]; then
            echo -e "${RED}❌ Erro: Especifique um grupo${NC}"
            echo "Uso: ./test.sh group 'nome-do-grupo'"
            exit 1
        fi
        echo -e "${BLUE}📦 Rodando grupo: ${YELLOW}$2${NC}"
        vendor/bin/pest --group="$2"
        ;;

    "file")
        if [ -z "$2" ]; then
            echo -e "${RED}❌ Erro: Especifique um arquivo${NC}"
            echo "Uso: ./test.sh file tests/Feature/ImageModelTest.php"
            exit 1
        fi
        echo -e "${BLUE}📄 Testando arquivo: ${YELLOW}$2${NC}"
        vendor/bin/pest "$2"
        ;;

    "coverage")
        echo -e "${BLUE}📊 Gerando relatório de cobertura...${NC}"
        vendor/bin/pest --coverage --min=80
        ;;

    "coverage-html")
        echo -e "${BLUE}📊 Gerando relatório HTML de cobertura...${NC}"
        vendor/bin/pest --coverage --coverage-html=coverage
        echo -e "${GREEN}✓ Relatório gerado em: coverage/index.html${NC}"

        # Tenta abrir o relatório automaticamente
        if command -v xdg-open &> /dev/null; then
            xdg-open coverage/index.html
        elif command -v open &> /dev/null; then
            open coverage/index.html
        fi
        ;;

    "watch")
        echo -e "${BLUE}👀 Modo watch - salvando arquivos para reexecutar...${NC}"
        echo -e "${YELLOW}Pressione Ctrl+C para sair${NC}"
        vendor/bin/pest --watch
        ;;

    "parallel")
        echo -e "${BLUE}⚡ Rodando testes em paralelo...${NC}"
        vendor/bin/pest --parallel
        ;;

    "profile")
        echo -e "${BLUE}⏱️  Mostrando testes mais lentos...${NC}"
        vendor/bin/pest --profile
        ;;

    "list")
        echo -e "${BLUE}📋 Listando todos os testes...${NC}"
        vendor/bin/pest --list-tests
        ;;

    "help"|"-h"|"--help")
        show_help
        ;;

    *)
        echo -e "${RED}❌ Comando desconhecido: $1${NC}"
        show_help
        exit 1
        ;;
esac

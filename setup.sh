#!/bin/bash

# Script de setup inicial do ambiente de testes

set -e

echo "🚀 Configurando ambiente de testes..."
echo "======================================"

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Verificar se Docker está instalado
echo -e "\n${BLUE}1. Verificando Docker...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker não encontrado. Por favor, instale o Docker primeiro.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker encontrado${NC}"

# 2. Verificar se Docker Compose está instalado
echo -e "\n${BLUE}2. Verificando Docker Compose...${NC}"
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker Compose não encontrado. Por favor, instale o Docker Compose primeiro.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker Compose encontrado${NC}"

# 3. Criar arquivo .env.docker se não existir
echo -e "\n${BLUE}3. Configurando variáveis de ambiente...${NC}"
if [ ! -f ".env.docker" ]; then
    cat > .env.docker << EOF
# Docker Environment Variables
UID=$(id -u)
GID=$(id -g)
EOF
    echo -e "${GREEN}✓ Arquivo .env.docker criado${NC}"
else
    echo -e "${GREEN}✓ Arquivo .env.docker já existe${NC}"
fi

# 4. Dar permissões aos scripts
echo -e "\n${BLUE}4. Configurando permissões...${NC}"
chmod +x dtest test.sh setup.sh 2>/dev/null || true
echo -e "${GREEN}✓ Permissões configuradas${NC}"

# 5. Criar diretório Docker se não existir
echo -e "\n${BLUE}5. Verificando estrutura de diretórios...${NC}"
if [ ! -d "Docker" ]; then
    mkdir -p Docker
    echo -e "${YELLOW}⚠️  Diretório Docker/ criado. Mova o Dockerfile e .dockerignore para lá!${NC}"
else
    echo -e "${GREEN}✓ Diretório Docker/ existe${NC}"
fi

# 6. Verificar arquivos necessários
echo -e "\n${BLUE}6. Verificando arquivos necessários...${NC}"
MISSING_FILES=()

if [ ! -f "Docker/Dockerfile" ]; then
    MISSING_FILES+=("Docker/Dockerfile")
fi

if [ ! -f "docker-compose.yml" ]; then
    MISSING_FILES+=("docker-compose.yml")
fi

if [ ${#MISSING_FILES[@]} -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Arquivos faltando:${NC}"
    for file in "${MISSING_FILES[@]}"; do
        echo "   - $file"
    done
    echo ""
    echo -e "${YELLOW}Por favor, crie esses arquivos antes de continuar.${NC}"
    exit 1
else
    echo -e "${GREEN}✓ Todos os arquivos necessários existem${NC}"
fi

# 7. Build das imagens Docker
echo -e "\n${BLUE}7. Construindo imagens Docker...${NC}"
echo -e "${YELLOW}Isso pode levar alguns minutos na primeira vez...${NC}"

export GID=$(id -g)

if docker-compose build; then
    echo -e "${GREEN}✓ Imagens construídas com sucesso${NC}"
else
    echo -e "${YELLOW}⚠️  Erro ao construir imagens. Tentando com 'docker compose'...${NC}"
    if docker compose build; then
        echo -e "${GREEN}✓ Imagens construídas com sucesso${NC}"
    else
        echo -e "${YELLOW}⚠️  Erro ao construir imagens${NC}"
        exit 1
    fi
fi

# 8. Teste rápido
echo -e "\n${BLUE}8. Executando teste rápido...${NC}"
if ./dtest 2>/dev/null || make test 2>/dev/null; then
    echo -e "${GREEN}✓ Teste rápido passou!${NC}"
else
    echo -e "${YELLOW}⚠️  Teste rápido falhou. Verifique os logs acima.${NC}"
fi

# 9. Resumo
echo -e "\n${GREEN}======================================"
echo "✓ Setup concluído com sucesso!"
echo "======================================${NC}"
echo ""
echo "Próximos passos:"
echo ""
echo "  1. Rodar testes:        ./dtest"
echo "  2. Testar tudo:         ./dtest all"
echo "  3. Shell no container:  ./dtest shell"
echo "  4. Ver comandos:        ./dtest help"
echo ""
echo "Ou use o Makefile:"
echo ""
echo "  make test"
echo "  make test-all"
echo "  make help"
echo ""
echo -e "${BLUE}📚 Documentação: QUICK-START.md${NC}"
echo ""

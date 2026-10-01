# Guia de Implantação (Deployment)

## Visão Geral

O TaskFlow é totalmente conteinerizado, permitindo implantação simples em qualquer ambiente que suporte Docker e Docker Compose ou Kubernetes.

## Implantação com Docker Compose em Produção

1. Configure as variáveis de produção no arquivo `.env`:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://seu-dominio.com
   DB_PASSWORD=sua_senha_super_segura
   ```

2. Gere o build otimizado dos containers:
   ```bash
   docker compose -f docker-compose.yml up -d --build
   ```

3. Execute as rotinas de cache do Laravel:
   ```bash
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   ```

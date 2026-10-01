# Guia de Implantação em Produção (Deployment) — TaskFlow

Este guia documenta o procedimento completo de implantação do **TaskFlow** em ambientes corporativos de produção, utilizando Docker Compose, Nginx, PHP-FPM e PostgreSQL.

---

## 1. Topologia de Infraestrutura

A infraestrutura é orquestrada através do `docker-compose.yml`:

```
               [ Internet / Clientes ]
                         │
                         ▼ HTTPS (443)
              ┌─────────────────────┐
              │    Nginx Alpine     │  Reverse Proxy & SSL Termination
              │  (Portas 80 / 443)  │  Serve assets estáticos do Vue 3
              └──────────┬──────────┘
                         │
        ┌────────────────┴────────────────┐
        │                                 │
        ▼ FastCGI (9000)                  ▼ Static Dist (/var/www/frontend)
┌───────────────┐                 ┌────────────────┐
│   PHP-FPM     │                 │ Frontend Build │
│ (Laravel 12)  │                 │  (HTML/JS/CSS) │
└───────┬───────┘                 └────────────────┘
        │
        ▼ TCP (5432)
┌───────────────┐
│ PostgreSQL 16 │
│ (Persistent)  │
└───────────────┘
```

---

## 2. Checklist Pré-Implantação & Variáveis de Ambiente

Crie o arquivo de ambiente de produção `.env` na raiz e em `backend/.env`:

```ini
# Configurações Globais da Aplicação
APP_NAME=TaskFlow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://taskflow.seu-dominio.com

# Chave Criptográfica Gerada via 'php artisan key:generate'
APP_KEY=base64:COLOQUE_AQUI_UMA_CHAVE_CRIPTOGRAFICA_SEGURA_32_BYTES

# Configurações do Banco de Dados PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=taskflow_production
DB_USERNAME=taskflow_prod_user
DB_PASSWORD=DEFINA_UMA_SENHA_COMPLEXA_E_ALEATORIA_AQUI

# Cache e Sessão
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

# Política de CORS & Sanctum
SANCTUM_STATEFUL_DOMAINS=taskflow.seu-dominio.com
SESSION_DOMAIN=.seu-dominio.com
```

---

## 3. Passos de Implantação

### 3.1 Build e Inicialização dos Containers
```bash
# 1. Clone o repositório no servidor de produção
git clone git@github.com:aurelio-muzzi/taskflow.git /var/www/taskflow
cd /var/www/taskflow

# 2. Inicialize e faça o build dos containers
docker compose up -d --build

# 3. Verifique o status dos serviços
docker compose ps
```

### 3.2 Execução de Migrações
```bash
# Executa as migrations com proteção de ambiente de produção
docker compose exec app php artisan migrate --force
```

### 3.3 Otimização de Performance do Laravel
Execute as rotinas de cache do Laravel para eliminar overhead de reflexão e leitura de arquivos em tempo de execução:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache
```

---

## 4. Configuração de SSL / HTTPS com Nginx & Certbot

Para habilitar certificados SSL gratuitos e renovação automática via Let's Encrypt:

```bash
# Instalação do Certbot no servidor host
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-nginx

# Obtenção do certificado SSL
sudo certbot certonly --webroot -w /var/www/taskflow/frontend/dist -d taskflow.seu-dominio.com
```

No bloco do servidor em `docker/nginx/default.conf`:
```nginx
server {
    listen 80;
    server_name taskflow.seu-dominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name taskflow.seu-dominio.com;

    ssl_certificate /etc/letsencrypt/live/taskflow.seu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/taskflow.seu-dominio.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Frontend SPA
    root /var/www/frontend;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API Proxy
    location /api {
        fastcgi_pass app:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/backend/public/index.php;
    }
}
```

---

## 5. Rotina Automatizada de Backup do PostgreSQL

Crie um script de backup em `/usr/local/bin/taskflow-backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/taskflow"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="$BACKUP_DIR/taskflow_backup_$TIMESTAMP.sql.gz"

mkdir -p "$BACKUP_DIR"

# Executa dump comprimido diretamente do container
docker compose -f /var/www/taskflow/docker-compose.yml exec -T db pg_dump -U taskflow_prod_user taskflow_production | gzip > "$FILENAME"

# Mantém apenas backups dos últimos 30 dias
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +30 -delete

echo "[$(date)] Backup concluído com sucesso: $FILENAME"
```

Configure execução diária via `crontab -e`:
```cron
0 2 * * * /usr/local/bin/taskflow-backup.sh >> /var/log/taskflow_backup.log 2>&1
```

---

## 6. Monitoramento de Saúde (Health Checks)

A API provê o endpoint público de checagem `/api/v1/health`. Ele pode ser configurado em ferramentas de monitoramento como Uptime Kuma, Datadog ou Prometheus/Blackbox:

```bash
curl -I https://taskflow.seu-dominio.com/api/v1/health
```
Resposta esperada: `HTTP/1.1 200 OK` com payload `{"success": true, "data": {"status": "ok", "database": "connected"}}`.

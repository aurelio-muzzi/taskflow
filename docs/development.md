# Guia de Desenvolvimento

## Requisitos Locais

* PHP 8.3+
* Composer 2+
* Node.js 22+ & npm 10+
* Docker & Docker Compose

## Rodando Localmente sem Docker

1. **Backend:**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve --port=8080
   ```

2. **Frontend:**
   ```bash
   cd frontend
   npm install
   npm run dev
   ```

## Verificação de Qualidade

* **Pint (Linter/Formatter):**
  ```bash
  cd backend
  ./vendor/bin/pint --test
  ```
* **Testes Backend:**
  ```bash
  cd backend
  php artisan test
  ```
* **Frontend Type-Check & Build:**
  ```bash
  cd frontend
  npm run build
  ```

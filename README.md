# TaskFlow — Sistema Fullstack de Gestão de Tarefas e Projetos

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![Vue Version](https://img.shields.io/badge/Vue.js-3.5-4FC08D?logo=vuedotjs)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.6-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?logo=docker)](https://www.docker.com)

---

## 1. Visão Geral

O **TaskFlow** é uma aplicação web fullstack corporativa projetada para demonstrar engenharia de software de alto nível. Mais do que um simples gerenciador de tarefas, a plataforma implementa controle de acesso granular em dois níveis (RBAC Global + Contextual de Projeto), trilha contínua de auditoria de alterações, arquitetura orientada a Ações/Serviços, tratamento uniforme de erros e orquestração completa em Docker.

---

## 2. Diagrama de Arquitetura

```text
                  ┌─────────────────┐
                  │    Vue 3 SPA    │
                  │ (Pinia/Router)  │
                  └────────┬────────┘
                           │
                         Axios (Bearer Token)
                           │
                           ▼
                  ┌─────────────────┐
                  │   Nginx / API   │
                  │  (Proxy Reverso)│
                  └────────┬────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │ Laravel 12 API  │
                  │(Actions/Policies│
                  └────────┬────────┘
                           │
             ┌─────────────┼─────────────┐
             │             │             │
             ▼             ▼             ▼
         PostgreSQL      Redis        Storage
        (Relacional)  (Cache/Queue)   (Uploads)
```

---

## 3. Tecnologias Empregadas

### Backend
* **Linguagem & Framework:** PHP 8.3+ / Laravel 12
* **Autenticação & Sessões:** Laravel Sanctum
* **Banco de Dados:** PostgreSQL 16+ com Eloquent ORM, Migrations, Seeders e Índices
* **Cache & Mensageria:** Redis 7+
* **Qualidade & Formatação:** Laravel Pint, PHPUnit / Pest
* **Padrões de Projeto:** Actions, DTOs, Policies, Observers, API Resources

### Frontend
* **Core:** Vue.js 3 (Composition API com `<script setup>`)
* **Linguagem:** TypeScript com tipagem estrita
* **Gerenciamento de Estado:** Pinia
* **Roteamento:** Vue Router 4 com Navigation Guards
* **Estilização:** Tailwind CSS 3.4
* **Comunicação HTTP:** Axios com interceptors padronizados
* **Ícones:** Lucide Icons (`@lucide/vue`)

### Infraestrutura & DevOps
* **Containers:** Docker & Docker Compose
* **Servidor Web:** Nginx Alpine
* **Integração Contínua (CI):** GitHub Actions

---

## 4. Estrutura do Repositório

```text
taskflow/
│
├── backend/                  # API REST Laravel 12
│   ├── app/
│   │   ├── Actions/          # Regras de negócio atômicas
│   │   ├── DTOs/             # Objetos de transferência de dados
│   │   ├── Http/
│   │   │   ├── Controllers/  # Controladores finos versionados
│   │   │   ├── Requests/     # Validação estrita via Form Requests
│   │   │   ├── Resources/    # Transformação de saída JSON
│   │   │   └── Responses/    # Envelopes uniformes de resposta
│   │   ├── Models/           # Modelos Eloquent com Soft Deletes
│   │   ├── Policies/         # Autorização RBAC granular
│   │   └── Services/         # Serviços de agregação e dashboard
│   ├── database/             # Migrations, factories e seeders
│   └── tests/                # Testes Unitários e de Integração (Feature)
│
├── frontend/                 # Single Page Application (SPA)
│   ├── src/
│   │   ├── components/       # Componentes visuais modulares
│   │   ├── composables/      # Hooks de lógica reativa reutilizável
│   │   ├── layouts/          # Layouts de aplicação e autenticação
│   │   ├── router/           # Configuração de rotas e guardiões
│   │   ├── services/         # Camada de comunicação com a API
│   │   ├── stores/           # Pinia stores reativas
│   │   ├── types/            # Tipos e contratos TypeScript
│   │   └── views/            # Páginas da aplicação
│
├── docker/                   # Arquivos de configuração de containers
│   ├── nginx/                # Configurações do Nginx
│   ├── php/                  # Dockerfile do PHP-FPM
│   └── frontend/             # Dockerfile do ambiente frontend
│
├── docs/                     # Documentação técnica e arquitetural
├── .github/workflows/        # Pipelines de CI/CD
├── docker-compose.yml        # Orquestrador de serviços
└── README.md
```

---

## 5. Como Executar o Projeto

### Pré-requisitos
* [Docker](https://www.docker.com/) e Docker Compose instalados.

### Executando com Docker Compose

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/seu-usuario/taskflow.git
   cd taskflow
   ```

2. **Configure as variáveis de ambiente:**
   ```bash
   cp .env.example .env
   cp backend/.env.example backend/.env
   ```

3. **Inicie os containers:**
   ```bash
   docker compose up -d --build
   ```

4. **Execute as migrations e seed inicial:**
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

5. **Acesse as interfaces:**
   * **Frontend SPA:** [http://localhost:5173](http://localhost:5173)
   * **Backend API:** [http://localhost:8080/api/v1/health](http://localhost:8080/api/v1/health)

---

## 6. Comandos Úteis do Docker

```bash
# Visualizar logs em tempo real
docker compose logs -f

# Parar todos os serviços
docker compose down

# Status dos containers
docker compose ps
```

---

## 7. Execução dos Testes Automatizados

### Backend
```bash
docker compose exec app php artisan test
# ou localmente na pasta backend:
php artisan test
```

### Análise Estática de Código (Pint)
```bash
docker compose exec app ./vendor/bin/pint --test
# ou localmente na pasta backend:
./vendor/bin/pint --test
```

### Frontend (Type-Check & Build)
```bash
docker compose exec frontend npm run build
# ou localmente na pasta frontend:
npm run build
```

---

## 8. Roadmap de Implementação

- [x] **Etapa 1:** Estrutura base do projeto, Docker Compose, Laravel 12 API, PostgreSQL e SPA Vue 3.
- [x] **Etapa 2:** Autenticação (Sanctum), perfis de usuários e RBAC global.
- [x] **Etapa 3:** Gestão de Projetos e equipe de membros.
- [x] **Etapa 4:** Gerenciamento completo de Tarefas, status e prioridades.
- [x] **Etapa 5:** Comentários, Notificações internas e Trilha de Auditoria.
- [x] **Etapa 6:** Dashboard interativo, busca e filtros combinados.
- [x] **Etapa 7:** Visão Kanban com drag-and-drop e refinamentos de UX.
- [ ] **Etapa 8:** Cobertura de testes end-to-end, documentação técnica em `/docs` e CI/CD.
- [ ] **Etapa 9:** Revisão final de portfólio e auditoria de segurança.

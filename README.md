# TaskFlow — Sistema Fullstack de Gestão de Tarefas e Projetos

[![CI Workflow](https://github.com/aurelio-muzzi/taskflow/actions/workflows/ci.yml/badge.svg)](https://github.com/aurelio-muzzi/taskflow/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![Vue Version](https://img.shields.io/badge/Vue.js-3.5-4FC08D?logo=vuedotjs)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.6-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)](https://www.docker.com)
[![Code Style](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-00D26A)](https://laravel.com/docs/pint)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## 1. Visão Geral

O **TaskFlow** é uma aplicação web fullstack corporativa projetada para demonstrar engenharia de software de alto nível. Mais do que um simples gerenciador de tarefas, a plataforma implementa controle de acesso granular em dois níveis (RBAC Global + Contextual de Projeto), trilha contínua de auditoria de alterações, arquitetura orientada a Ações/Serviços, tratamento uniforme de respostas JSON, auditoria de segurança contra ataques de força bruta, e orquestração completa em Docker com esteira automatizada de CI/CD via GitHub Actions.

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
* **Autenticação & Sessões:** Laravel Sanctum (Tokens Bearer)
* **Banco de Dados:** PostgreSQL 16+ com Eloquent ORM, Migrations, Seeders e Índices Compostos
* **Cache & Mensageria:** Redis 7+
* **Qualidade & Formatação:** Laravel Pint, PHPUnit
* **Padrões de Projeto:** Actions, DTOs, Policies, Observers, API Resources, Custom Response Envelopes
* **Segurança:** Rate Limiting granular (`throttle:15,1` no endpoint de autenticação), Headers de Segurança Nginx (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, ocultação de `X-Powered-By`), proteção CSRF/CORS estrita.

### Frontend
* **Core:** Vue.js 3 (Composition API com `<script setup>`)
* **Linguagem:** TypeScript com tipagem estrita (`vue-tsc`)
* **Gerenciamento de Estado:** Pinia com stores desacopladas
* **Roteamento:** Vue Router 4 com Navigation Guards e verificação de autenticação
* **Estilização:** Tailwind CSS 3.4 com design system responsivo e moderno
* **Comunicação HTTP:** Axios com interceptors padronizados para injeção de token e captura de erros
* **Ícones:** Lucide Icons (`@lucide/vue`)
* **UX/Interação:** Kanban com Drag-and-Drop nativo (HTML5 Drag and Drop API) com atualização otimista e rollback automático, Spotlight Search global (`Ctrl+K`).

### Infraestrutura & DevOps
* **Containers:** Docker & Docker Compose
* **Servidor Web:** Nginx Alpine com reverse proxy e headers de segurança
* **Integração Contínua (CI):** GitHub Actions com matriz de versões (PHP 8.3 e 8.4), cache de dependências Composer, execução de testes automatizados e análise estática via Laravel Pint.

---

## 4. Documentação Técnica

O repositório conta com documentação aprofundada em [`/docs`](file:///c:/Projeto/taskflow/docs):

* 📘 [**Arquitetura Geral** (`docs/architecture.md`)](file:///c:/Projeto/taskflow/docs/architecture.md) — Visão detalhada de camadas, padrões (Actions, DTOs, Policies, Responses) e fluxo de dados.
* 📗 [**Contrato da API REST** (`docs/api.md`)](file:///c:/Projeto/taskflow/docs/api.md) — Especificação de todos os endpoints, parâmetros, payloads de requisição e envelope uniforme de respostas.
* 📕 [**Matriz de RBAC & Permissões** (`docs/rbac.md`)](file:///c:/Projeto/taskflow/docs/rbac.md) — Hierarquia de papéis do sistema e papéis contextuais de projeto.
* 📙 [**Estrutura do Banco de Dados** (`docs/database.md`)](file:///c:/Projeto/taskflow/docs/database.md) — Diagrama conceitual de tabelas, chaves estrangeiras, índices e relacionamentos.
* 📓 [**Guia de Deploy & Operações** (`docs/deployment.md`)](file:///c:/Projeto/taskflow/docs/deployment.md) — Diretrizes para ambientes de produção, staging e manutenção.
* 🛡️ [**Políticas de Autorização** (`docs/authorization.md`)](file:///c:/Projeto/taskflow/docs/authorization.md) — Detalhamento das regras das Laravel Policies.

---

## 5. Estrutura do Repositório

```text
taskflow/
│
├── backend/                  # API REST Laravel 12
│   ├── app/
│   │   ├── Actions/          # Regras de negócio atômicas (Single-Responsibility)
│   │   ├── DTOs/             # Objetos de transferência de dados tipados
│   │   ├── Enums/            # Enums nativos (RoleEnum, TaskStatus, TaskPriority, etc.)
│   │   ├── Http/
│   │   │   ├── Controllers/  # Controladores finos versionados (/v1)
│   │   │   ├── Requests/     # Validação estrita via Form Requests
│   │   │   ├── Resources/    # Transformação de saída JSON
│   │   │   └── Responses/    # Envelopes uniformes de resposta (ApiResponse)
│   │   ├── Models/           # Modelos Eloquent com Soft Deletes
│   │   ├── Policies/         # Autorização RBAC granular (Global + Projeto)
│   │   └── Services/         # Serviços de agregação, auditoria e métricas
│   ├── database/             # Migrations, factories e seeders
│   └── tests/                # Testes Unitários, de Feature e End-to-End (E2E)
│
├── frontend/                 # Single Page Application (SPA)
│   ├── src/
│   │   ├── components/       # Componentes visuais modulares e Kanban Board
│   │   ├── composables/      # Hooks de lógica reativa reutilizável
│   │   ├── layouts/          # Layouts de aplicação (Navbar, Sidebar, Spotlight)
│   │   ├── router/           # Configuração de rotas e guardiões de autenticação
│   │   ├── services/         # Camada de comunicação com a API (Axios client)
│   │   ├── stores/           # Pinia stores reativas (auth, projects, tasks, etc.)
│   │   ├── types/            # Tipos e contratos TypeScript
│   │   └── views/            # Páginas da aplicação (Dashboard, Kanban, Tasks, etc.)
│
├── docker/                   # Arquivos de configuração de containers
│   ├── nginx/                # Configurações do Nginx (Proxy, Security Headers)
│   ├── php/                  # Dockerfile do PHP-FPM 8.3
│   └── frontend/             # Dockerfile do ambiente frontend
│
├── docs/                     # Documentação técnica e arquitetural completa
├── .github/workflows/        # Pipelines de CI/CD automatizadas
├── docker-compose.yml        # Orquestrador de serviços (App, DB, Redis, Nginx, SPA)
└── README.md
```

---

## 6. Como Executar o Projeto

### Pré-requisitos
* [Docker](https://www.docker.com/) e Docker Compose instalados.

### Executando com Docker Compose

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/aurelio-muzzi/taskflow.git
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

4. **Inicie os containers:**
   ```bash
   docker compose exec app composer install
   ```
   
5. **Execute as migrations e seed inicial de dados:**
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

6. **Acesse as interfaces:**
   * **Frontend SPA:** [http://localhost:5173](http://localhost:5173)
   * **Backend API Healthcheck:** [http://localhost:8080/api/v1/health](http://localhost:8080/api/v1/health)

---

## 7. Credenciais Padrão de Acesso (Seed)

Após rodar o seed do banco de dados, você pode acessar o sistema com os seguintes perfis pré-configurados:

| Papel (Role) | E-mail | Senha Padrão | Escopo de Acesso |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@taskflow.dev` | `password` | Acesso total ao sistema, gerenciamento de usuários, projetos e auditoria |
| **Gerente** | `manager@taskflow.dev` | `password` | Criação e gestão de projetos, atribuição de tarefas e relatórios |
| **Membro / Usuário** | `user@taskflow.dev` | `password` | Visualização de projetos atribuídos, movimentação de tarefas no Kanban |

---

## 8. Comandos Úteis do Docker

```bash
# Visualizar logs de todos os serviços em tempo real
docker compose logs -f

# Visualizar logs apenas da API
docker compose logs -f app

# Parar todos os serviços
docker compose down

# Status dos containers em execução
docker compose ps
```

---

## 9. Execução dos Testes Automatizados

O sistema conta com 59 testes automatizados e 278 asserções cobrindo cenários de autenticação, RBAC, fluxos de tarefas, comentários, auditoria, notificações e testes end-to-end de ciclo de vida completo.

### Backend (Testes de Unidade, Feature e E2E)
```bash
docker compose exec app php artisan test
# ou localmente na pasta backend:
php artisan test
```

### Análise Estática de Código (Laravel Pint)
```bash
docker compose exec app ./vendor/bin/pint --test
# ou localmente na pasta backend:
./vendor/bin/pint --test
```

### Frontend (Type-Check Estrito & Build de Produção)
```bash
docker compose exec frontend npm run build
# ou localmente na pasta frontend:
npm run build
```

---

## 10. Roadmap de Implementação

- [x] **Etapa 1:** Estrutura base do projeto, Docker Compose, Laravel 12 API, PostgreSQL e SPA Vue 3.
- [x] **Etapa 2:** Autenticação (Sanctum), perfis de usuários e RBAC global.
- [x] **Etapa 3:** Gestão de Projetos e equipe de membros.
- [x] **Etapa 4:** Gerenciamento completo de Tarefas, status e prioridades.
- [x] **Etapa 5:** Comentários, Notificações internas e Trilha de Auditoria.
- [x] **Etapa 6:** Dashboard interativo, busca e filtros combinados.
- [x] **Etapa 7:** Visão Kanban com drag-and-drop e refinamentos de UX.
- [x] **Etapa 8:** Cobertura de testes end-to-end, documentação técnica em `/docs` e CI/CD.
- [x] **Etapa 9:** Revisão final de portfólio e auditoria de segurança (Rate limiting, Security Headers, Sanitização de ambiente).

---

## 11. Licença

Este projeto está sob a licença [MIT](LICENSE).

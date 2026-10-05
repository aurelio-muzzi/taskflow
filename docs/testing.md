# Estratégia e Guia de Testes — TaskFlow

O TaskFlow adota a pirâmide de testes e práticas de engenharia de software voltadas a alta confiabilidade, determinismo e velocidade de execução.

---

## 1. Estrutura da Suíte de Testes

A suíte de testes automatizados é dividida entre **Testes Unitários**, **Testes de Integração / Feature** e **Testes de Ciclo de Vida End-to-End (E2E)** no backend, além de **Type-Checking Estrito e Validação de Bundling** no frontend.

```text
backend/tests/
├── TestCase.php
├── Unit/
│   ├── EnumAndResponseTest.php          # Testes unitários de Enums, labels e ApiResponse
│   └── ExampleTest.php
└── Feature/
    ├── HealthCheckTest.php              # Status da API, runtime e conexão com banco
    ├── FoundationTest.php               # Migrations, permissões RBAC e banco
    ├── AuthTest.php                     # Login, logout, status, /me, recuperação de senha, RBAC
    ├── UserManagementTest.php           # CRUD de usuários, toggle status e soft delete
    ├── ProjectManagementTest.php        # Criação, equipe, papeis, permissões e restrições
    ├── TaskManagementTest.php           # CRUD de tarefas, reordenação Kanban, status e prazos
    ├── CommentAndAuditTest.php          # Comentários, moderação, trilha de auditoria e notificações
    ├── DashboardTest.php                # Métricas globais, escopo por usuário e busca global
    └── FullProjectLifecycleE2ETest.php  # Fluxo completo de ponta a ponta (E2E)
```

---

## 2. Cobertura por Módulo

### 2.1 Autenticação e Autorização (`AuthTest.php`, `FoundationTest.php`)
* Autenticação com credenciais válidas e emissão de Sanctum Personal Access Token;
* Rejeição de credenciais incorretas (401);
* Bloqueio imediato de usuários desativados (`status: INACTIVE`);
* Revogação de token no logout (`POST /api/v1/auth/logout`);
* Obtenção dos dados do usuário autenticado (`GET /api/v1/auth/me`);
* Recuperação de senha por e-mail com geração de token seguro;
* Redefinição de senha com validação de token e confirmação;
* Catálogo de roles e permissões com restrição a perfis autorizados.

### 2.2 Gestão de Usuários (`UserManagementTest.php`)
* Listagem paginada com paginação padrão e metadados (`items`, `pagination`, `meta`);
* Filtro por nome e e-mail com busca sensível a contexto;
* Criação de usuários com validação de unicidade de e-mail e força de senha;
* Atualização de dados cadastrais e alteração de perfil (Role);
* Ativação e inativação de contas (com proteção contra auto-desativação);
* Exclusão lógica (*Soft Deletes*) com integridade referencial.

### 2.3 Gestão de Projetos e Equipe (`ProjectManagementTest.php`)
* Criação e validação de unicidade do código do projeto (`code`);
* Impedimento de associação de usuário inativo como proprietário (`owner_id`);
* Escopo de visualização: Administradores e Gerentes possuem visibilidade ampliada; usuários comuns visualizam somente projetos dos quais são membros;
* Adição, atualização de papel e remoção de membros com verificação de autorização;
* Proteção contra exclusão ou desassociação do proprietário da equipe;
* Exclusão lógica (*Soft Delete*) de projetos.

### 2.4 Tarefas e Fluxo Kanban (`TaskManagementTest.php`)
* Criação de tarefas associadas a projetos ativos com validação de responsáveis;
* Proibição de atribuição de tarefas a usuários inativos ou não pertencentes ao projeto;
* Transições de status válidas (`todo` -> `in_progress` -> `review` -> `done`);
* Registro automático de data/hora de conclusão (`completed_at`) ao atingir `done`;
* Reordenação em lote para movimentação de colunas no quadro Kanban (`POST /api/v1/projects/{project}/tasks/reorder`);
* Filtros combinados por projeto, status, prioridade, responsável e ordenação customizada;
* Proteção de permissões: visualizadores (*VIEWER*) não podem criar, mover ou editar tarefas.

### 2.5 Comentários, Auditoria e Notificações (`CommentAndAuditTest.php`)
* Criação de comentários vinculados a tarefas;
* Edição e exclusão de comentários pelo próprio autor;
* Moderação de comentários ofensivos ou indevidos por Administradores;
* Registro automático de eventos de auditoria (*AuditLog*) para criação, edição e exclusão de entidades;
* Sanitização estrita de dados sensíveis (senhas, segredos e tokens nunca são persistidos em logs);
* Consulta de trilha de auditoria restrita a perfis autorizados;
* Disparo, listagem e marcação de notificações como lidas individualmente e em lote (`PATCH /api/v1/notifications/read-all`).

### 2.6 Dashboard e Pesquisa Global (`DashboardTest.php`)
* Agregação de métricas de contagem total de projetos, tarefas e membros;
* Contagem precisa de projetos e tarefas atrasadas (`overdue`);
* Indicadores de produtividade: tarefas concluídas por período, distribuição por prioridade e por status;
* Busca global transversal em projetos, tarefas e usuários respeitando o escopo de autorização do solicitante.

### 2.7 Ciclo de Vida Completo E2E (`FullProjectLifecycleE2ETest.php`)
* Cenário real de ponta a ponta simulando um fluxo operacional completo:
  1. Criação do projeto pelo Gerente;
  2. Inclusão de desenvolvedores e analistas na equipe;
  3. Criação de tarefas com estimativas e prazos;
  4. Movimentação de tarefas no Kanban;
  5. Inserção de comentários e acompanhamento de notificações;
  6. Finalização das tarefas e verificação das métricas no Dashboard.

---

## 3. Execução dos Testes

### 3.1 Backend
Os testes utilizam o banco de dados em memória SQLite (`:memory:`) localmente para execução instantânea (< 2s) sem dependência externa, enquanto a pipeline de CI valida com PostgreSQL real.

```bash
# Executar todos os testes
cd backend
php artisan test

# Executar com relatório de testes detalhado
php artisan test --testdox

# Executar apenas testes de uma classe específica
php artisan test tests/Feature/TaskManagementTest.php

# Executar apenas testes unitários
php artisan test tests/Unit/
```

### 3.2 Verificação de Estilo de Código (Pint)
```bash
cd backend
./vendor/bin/pint --test
```

### 3.3 Frontend (Validação de Tipagem e Build)
```bash
cd frontend
npm run build
```

---

## 4. Integração Contínua (CI/CD)

Todo push ou pull request para os branches `main` e `develop` executa automaticamente a pipeline no GitHub Actions configurada em `.github/workflows/ci.yml`:
1. Instalação de dependências do Composer;
2. Validação estrita de formatação com **Laravel Pint**;
3. Execução completa dos testes com **PHPUnit / Pest** em matriz multi-versão PHP (8.3 e 8.4) contra container PostgreSQL 16;
4. Instalação das dependências Node.js via `npm ci`;
5. Verificação estrita de tipagem TypeScript via `vue-tsc` e build de produção com Vite.

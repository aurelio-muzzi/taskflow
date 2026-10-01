# Especificação da API RESTful — TaskFlow (/api/v1)

A API do TaskFlow é totalmente versionada sob o prefixo `/api/v1` e segue estritamente as convenções arquiteturais REST, utilizando JSON como formato de tráfego de dados e códigos de status HTTP semânticos.

---

## 1. Padrões de Comunicação

### 1.1 Headers Obrigatórios
Para requisições à API, os seguintes cabeçalhos devem ser enviados:
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <seu_token_sanctum>
```

### 1.2 Formatos Padronizados de Resposta

#### Sucesso (200 OK / 201 Created)
```json
{
  "success": true,
  "data": { ... },
  "message": "Operação realizada com sucesso."
}
```

#### Sucesso Paginado (200 OK)
```json
{
  "success": true,
  "data": {
    "items": [ ... ],
    "pagination": {
      "current_page": 1,
      "last_page": 4,
      "per_page": 15,
      "total": 52
    }
  },
  "message": "Itens recuperados com sucesso."
}
```

#### Erro de Validação de Entrada (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Erro de validação nos dados fornecidos.",
  "errors": {
    "title": ["O título da tarefa é obrigatório."],
    "due_date": ["A data de entrega deve ser posterior ou igual a hoje."]
  }
}
```

#### Erro de Autenticação (401 Unauthorized)
```json
{
  "success": false,
  "message": "Não autenticado."
}
```

#### Erro de Autorização / Permissão Negada (403 Forbidden)
```json
{
  "success": false,
  "message": "Você não tem permissão para realizar esta ação."
}
```

#### Recurso Não Encontrado (404 Not Found)
```json
{
  "success": false,
  "message": "Recurso solicitado não encontrado."
}
```

---

## 2. Catálogo de Endpoints

### 2.1 Verificação de Integridade (Health Check)
* **`GET /api/v1/health`**
  * **Autenticação:** Pública.
  * **Retorno:** Status da API, latência do banco de dados e versão do runtime.

---

### 2.2 Autenticação & Gestão de Sessão (Auth)
* **`POST /api/v1/auth/login`**
  * **Autenticação:** Pública.
  * **Payload:** `{ "email": "admin@taskflow.local", "password": "password" }`
  * **Retorno:** Token de acesso Sanctum e objeto do usuário com perfil global.
* **`POST /api/v1/auth/logout`**
  * **Autenticação:** Obrigatória (`auth:sanctum`).
  * **Ação:** Revoga o token atual do dispositivo.
* **`GET /api/v1/auth/me`**
  * **Autenticação:** Obrigatória (`auth:sanctum`).
  * **Retorno:** Dados completos do usuário logado.
* **`PUT /api/v1/auth/profile`**
  * **Autenticação:** Obrigatória (`auth:sanctum`).
  * **Payload:** `{ "name": "Nome Atualizado", "email": "novo@email.com" }`
* **`PUT /api/v1/auth/change-password`**
  * **Autenticação:** Obrigatória (`auth:sanctum`).
  * **Payload:** `{ "current_password": "...", "new_password": "...", "new_password_confirmation": "..." }`

---

### 2.3 Perfis de Usuário (Roles)
* **`GET /api/v1/roles`**
  * **Autenticação:** Obrigatória.
  * **Retorno:** Lista de perfis globais disponíveis (`admin`, `manager`, `user`).

---

### 2.4 Gestão de Usuários (Users — Acesso Restrito a Administradores)
* **`GET /api/v1/users`**
  * **Query Params:** `q`, `role_id`, `status`, `page`, `per_page`.
  * **Retorno:** Listagem paginada de usuários da plataforma.
* **`POST /api/v1/users`**
  * **Payload:** `{ "name": "...", "email": "...", "password": "...", "role_id": 2 }`
* **`GET /api/v1/users/{id}`**
  * **Retorno:** Detalhes de um usuário específico.
* **`PUT /api/v1/users/{id}`**
  * **Payload:** `{ "name": "...", "email": "...", "role_id": 2 }`
* **`PATCH /api/v1/users/{id}/toggle-status`**
  * **Ação:** Alterna o status do usuário entre `ACTIVE` e `INACTIVE`. Administrador não pode inativar a si mesmo.
* **`DELETE /api/v1/users/{id}`**
  * **Ação:** Soft delete da conta de usuário.

---

### 2.5 Gestão de Projetos (Projects)
* **`GET /api/v1/projects`**
  * **Escopo:** Administrador lista todos os projetos; Gerentes e Usuários listam apenas projetos que possuem ou pertencem.
  * **Query Params:** `q`, `status`, `page`, `per_page`.
* **`POST /api/v1/projects`**
  * **Permissão:** `ADMIN` ou `MANAGER`.
  * **Payload:** `{ "name": "...", "code": "PRJ-01", "description": "...", "status": "ACTIVE", "start_date": "2026-10-01", "due_date": "2026-11-01" }`
* **`GET /api/v1/projects/{id}`**
  * **Permissão:** Membro do projeto ou `ADMIN`.
* **`PUT /api/v1/projects/{id}`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto ou `ADMIN`.
* **`DELETE /api/v1/projects/{id}`**
  * **Permissão:** `OWNER` do projeto ou `ADMIN` (Soft delete com arquivamento).

---

### 2.6 Equipe do Projeto (Project Members)
* **`GET /api/v1/projects/{project}/members`**
  * **Retorno:** Lista de membros associados e seus níveis de permissão (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`).
* **`POST /api/v1/projects/{project}/members`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto ou `ADMIN`.
  * **Payload:** `{ "user_id": 3, "role": "MEMBER" }`
* **`PUT /api/v1/projects/{project}/members/{user}`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto ou `ADMIN`.
  * **Payload:** `{ "role": "MANAGER" }`
* **`DELETE /api/v1/projects/{project}/members/{user}`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto ou `ADMIN`. O proprietário (`OWNER`) não pode ser removido.

---

### 2.7 Gestão de Tarefas (Tasks & Kanban)
* **`GET /api/v1/tasks`**
  * **Query Params:** `q`, `project_id`, `status`, `priority`, `assigned_to`, `sort_by`, `direction`, `page`, `per_page`.
* **`GET /api/v1/projects/{project}/tasks`**
  * **Query Params:** Filtros combinados e `all=true` para retorno de todas as tarefas para o quadro Kanban.
* **`POST /api/v1/projects/{project}/tasks`**
  * **Permissão:** `OWNER`, `MANAGER`, `MEMBER` do projeto ou `ADMIN`.
  * **Payload:** `{ "title": "...", "description": "...", "priority": "high", "assigned_to": 4, "due_date": "2026-10-15", "estimated_hours": 8 }`
* **`GET /api/v1/tasks/{id}`**
  * **Permissão:** Membro do projeto ou `ADMIN`.
* **`PUT /api/v1/tasks/{id}`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto, criador da tarefa, usuário atribuído ou `ADMIN`.
* **`PATCH /api/v1/tasks/{id}/status`**
  * **Payload:** `{ "status": "in_progress", "order": 2 }`
  * **Ação:** Atualiza status, calcula `completed_at`, gera log de auditoria e notifica o responsável.
* **`POST /api/v1/projects/{project}/tasks/reorder`**
  * **Finalidade:** Reordenação e movimentação em lote do quadro Kanban.
  * **Payload:**
    ```json
    {
      "tasks": [
        { "id": 10, "order": 0, "status": "in_progress" },
        { "id": 14, "order": 1, "status": "in_progress" }
      ]
    }
    ```
* **`DELETE /api/v1/tasks/{id}`**
  * **Permissão:** `OWNER`, `MANAGER` do projeto, criador da tarefa ou `ADMIN`.

---

### 2.8 Comentários & Discussão (Comments)
* **`GET /api/v1/tasks/{task}/comments`**
  * **Retorno:** Histórico cronológico de comentários da demanda.
* **`POST /api/v1/tasks/{task}/comments`**
  * **Payload:** `{ "content": "Texto do comentário..." }`
  * **Ação:** Cria comentário, registra auditoria e notifica o responsável pela tarefa.
* **`DELETE /api/v1/comments/{id}`**
  * **Permissão:** Autor do comentário ou `ADMIN`.

---

### 2.9 Trilha de Auditoria (Audit Logs)
* **`GET /api/v1/audit-logs`**
  * **Permissão:** `ADMIN` e `MANAGER`.
  * **Retorno:** Eventos e alterações do sistema com snapshots `old_values` e `new_values`.
* **`GET /api/v1/tasks/{task}/audit-logs`**
  * **Retorno:** Histórico de auditoria específico da tarefa.

---

### 2.10 Notificações Internas (Notifications)
* **`GET /api/v1/notifications`**
  * **Retorno:** Notificações do usuário autenticado com indicação de não lidas.
* **`PATCH /api/v1/notifications/{id}/read`**
  * **Ação:** Marca notificação específica como lida.
* **`POST /api/v1/notifications/mark-all-read`**
  * **Ação:** Marca todas as notificações pendentes do usuário como lidas.

---

### 2.11 Dashboard Executivo & Busca Global
* **`GET /api/v1/dashboard/metrics`**
  * **Retorno:** Métricas agregadas de projetos ativos, distribuição de tarefas por status e prioridade, taxa de conclusão percentual, tarefas atrasadas, demandas pessoais do usuário, prazos dos próximos 7 dias e atividades recentes.
* **`GET /api/v1/search`**
  * **Query Params:** `q` (mínimo 2 caracteres), `limit` (padrão 5).
  * **Retorno:** Resultados agrupados categoricamente em `projects`, `tasks` e `users`, respeitando estritamente o escopo de visualização do usuário.

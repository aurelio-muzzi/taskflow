# Controle de Acesso e Matriz RBAC — TaskFlow

O **TaskFlow** implementa um modelo de autorização em **dois níveis complementares** (*Two-Tier Role-Based Access Control*), garantindo segregação de privilégios de alto nível e colaboração contextual refinada dentro de cada projeto.

---

## 1. Nível 1: Perfis Globais de Sistema (System Roles)

Associados diretamente ao usuário na tabela `users` via `role_id`:

1. **`ADMIN` (Administrador):**
   * Acesso irrestrito a todos os recursos da aplicação.
   * Criação, edição, bloqueio e auditoria de usuários.
   * Visualização de trilhas de auditoria globais do sistema.
   * Possui *bypass* automático em todas as verificações de permissão através do método `before` das *Policies*.
2. **`MANAGER` (Gerente):**
   * Criação e gerenciamento de projetos.
   * Definição de metas, prazos e orçamento de horas.
   * Acesso a métricas do dashboard e relatórios de auditoria dos seus projetos.
3. **`USER` (Usuário Padrão):**
   * Execução de tarefas nas quais foi designado.
   * Acesso restrito apenas aos projetos e tarefas em que é membro formal da equipe.

---

## 2. Nível 2: Funções Contextuais de Projeto (Project Roles)

Definidas na tabela `project_members` para cada associação `(project_id, user_id)`:

1. **`OWNER` (Proprietário do Projeto):**
   * Papel concedido automaticamente ao criador do projeto.
   * Controle total do projeto: edição de metadados, exclusão/arquivamento, gestão de membros e funções.
   * Não pode ser removido da equipe por outros gestores.
2. **`MANAGER` (Gerente do Projeto):**
   * Gerenciamento operacional da equipe (adicionar/remover membros, atualizar papéis, exceto `OWNER`).
   * Criação, atribuição, reordenação e exclusão de tarefas no projeto.
3. **`MEMBER` (Membro Executor):**
   * Criação de novas tarefas no projeto.
   * Colaboração ativa: alteração de status e reordenação de cartões no quadro Kanban.
   * Edição de tarefas atribuídas a si ou criadas por si.
   * Publicação de comentários e acompanhamento de discussões.
4. **`VIEWER` (Visualizador):**
   * Papel estritamente de leitura (*Read-Only*).
   * Visualização de painéis, tarefas, prazos e comentários.
   * Bloqueado contra movimentação de cartões, criação ou alteração de status de tarefas.

---

## 3. Matriz Completa de Permissões por Recurso

### 3.1 Gestão do Sistema e Usuários

| Ação | Endpoint | ADMIN | MANAGER | USER |
|---|---|:---:|:---:|:---:|
| Listar todos os usuários | `GET /api/v1/users` | Sim | Não | Não |
| Criar novo usuário | `POST /api/v1/users` | Sim | Não | Não |
| Editar usuário | `PUT /api/v1/users/{id}` | Sim | Não | Não |
| Ativar / Inativar usuário | `PATCH /api/v1/users/{id}/toggle-status` | Sim | Não | Não |
| Excluir usuário (Soft Delete) | `DELETE /api/v1/users/{id}` | Sim | Não | Não |
| Visualizar Auditoria Global | `GET /api/v1/audit-logs` | Sim | Sim | Não |
| Listar Perfis do Sistema | `GET /api/v1/roles` | Sim | Sim | Sim |

---

### 3.2 Projetos e Equipes

| Ação | Endpoint | ADMIN | OWNER | MANAGER | MEMBER | VIEWER |
|---|---|:---:|:---:|:---:|:---:|:---:|
| Criar novo projeto | `POST /api/v1/projects` | Sim | N/A | Sim | Não | Não |
| Visualizar detalhes do projeto | `GET /api/v1/projects/{id}` | Sim | Sim | Sim | Sim | Sim |
| Editar dados do projeto | `PUT /api/v1/projects/{id}` | Sim | Sim | Sim | Não | Não |
| Excluir/Arquivar projeto | `DELETE /api/v1/projects/{id}` | Sim | Sim | Não | Não | Não |
| Listar membros da equipe | `GET /api/v1/projects/{id}/members` | Sim | Sim | Sim | Sim | Sim |
| Adicionar membro à equipe | `POST /api/v1/projects/{id}/members` | Sim | Sim | Sim | Não | Não |
| Alterar papel de membro | `PUT /api/v1/projects/{id}/members/{u}` | Sim | Sim | Sim | Não | Não |
| Remover membro da equipe | `DELETE /api/v1/projects/{id}/members/{u}` | Sim | Sim | Sim | Não | Não |

---

### 3.3 Tarefas e Quadro Kanban

| Ação | Endpoint | ADMIN | OWNER | MANAGER | MEMBER | VIEWER |
|---|---|:---:|:---:|:---:|:---:|:---:|
| Listar tarefas do projeto | `GET /api/v1/projects/{id}/tasks` | Sim | Sim | Sim | Sim | Sim |
| Criar tarefa no projeto | `POST /api/v1/projects/{id}/tasks` | Sim | Sim | Sim | Sim | Não |
| Visualizar tarefa | `GET /api/v1/tasks/{id}` | Sim | Sim | Sim | Sim | Sim |
| Editar dados completos da tarefa | `PUT /api/v1/tasks/{id}` | Sim | Sim | Sim | Somente Autor/Atribuído | Não |
| Atualizar status (Kanban move) | `PATCH /api/v1/tasks/{id}/status` | Sim | Sim | Sim | Sim | Não |
| Reordenar tarefas em lote | `POST /api/v1/projects/{id}/tasks/reorder` | Sim | Sim | Sim | Sim | Não |
| Excluir tarefa | `DELETE /api/v1/tasks/{id}` | Sim | Sim | Sim | Somente Autor | Não |

---

### 3.4 Comentários e Colaboração

| Ação | Endpoint | ADMIN | OWNER | MANAGER | MEMBER | VIEWER |
|---|---|:---:|:---:|:---:|:---:|:---:|
| Listar comentários da tarefa | `GET /api/v1/tasks/{id}/comments` | Sim | Sim | Sim | Sim | Sim |
| Publicar comentário | `POST /api/v1/tasks/{id}/comments` | Sim | Sim | Sim | Sim | Não |
| Excluir comentário | `DELETE /api/v1/comments/{id}` | Sim | Não | Não | Somente Autor | Não |

---

## 4. Implementação nas Policies do Laravel

Todas as regras de autorização são declaradas em classes de política sob `app/Policies/`:

```php
class TaskPolicy
{
    // Bypass automático para Administradores
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    // Permissão colaborativa para atualização de status no Kanban
    public function updateStatus(User $user, Task $task): bool
    {
        $project = $task->project;

        if ($project->owner_id === $user->id) return true;
        if ($task->created_by === $user->id || $task->assigned_to === $user->id) return true;

        $role = $project->getUserRole($user);
        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }
}
```

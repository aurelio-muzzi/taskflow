# Arquitetura do Banco de Dados — PostgreSQL

## Visão Geral

O banco de dados do TaskFlow é modelado em **PostgreSQL 16**, utilizando chaves estrangeiras com integridade referencial estrita, índices calculados para otimização de consultas e **Soft Deletes** em entidades fundamentais (`users`, `projects`, `tasks`, `comments`).

## Diagrama Entidade-Relacionamento

```
roles ───< users ───< projects ───< project_members
             │             │
             │             └───< tasks ───< comments
             │                     │
             ├───< audit_logs      │
             │                     │
             └───< notifications ──┘
```

## Tabelas Principais

| Tabela | Responsabilidade | Índices Principais |
|---|---|---|
| `roles` | Perfis globais do sistema (`admin`, `manager`, `user`) | `slug` (UNIQUE) |
| `users` | Contas de usuário, credenciais e status | `email` (UNIQUE), `status`, `role_id` |
| `projects` | Projetos sob gestão, metas de prazo e status | `code` (UNIQUE), `status`, `owner_id`, `due_date` |
| `project_members` | Associação e papel de cada usuário no projeto | UNIQUE `(project_id, user_id)` |
| `tasks` | Itens de trabalho, prazos, estimativas e status | `(project_id, status)`, `(assignee_id, status)`, `priority` |
| `comments` | Histórico e discussões dentro de tarefas | `(task_id, created_at)` |
| `notifications` | Notificações internas direcionadas a usuários | `(user_id, read_at)` |
| `audit_logs` | Trilha de auditoria com snapshots de `old_values` e `new_values` | `(entity_type, entity_id)`, `user_id`, `created_at` |

# Autorização e Modelo RBAC — TaskFlow

O TaskFlow implementa um modelo híbrido e refinado de **Controle de Acesso Baseado em Papéis (RBAC)** em duas camadas complementares:
1. **Papéis Globais de Sistema (System Roles):** Escopo de toda a plataforma;
2. **Papéis Contextuais por Projeto (Project Roles):** Escopo específico dentro de cada equipe e projeto.

Para a matriz matricial detalhada de permissões por endpoint, consulte também:
👉 **[docs/rbac.md](file:///c:/Projeto/taskflow/docs/rbac.md)**

---

## 1. Camada 1 — Papéis Globais de Sistema (`roles`)

Cada usuário registrado possui obrigatoriamente um perfil de sistema (`role_id` na tabela `users`):

| Papel Global | Slug | Descrição e Nível de Acesso |
| :--- | :--- | :--- |
| **Administrador** | `admin` | Acesso total e irrestrito ao sistema. Pode gerenciar usuários, visualizar auditoria global, moderar comentários e acessar qualquer projeto e métricas. |
| **Gerente** | `manager` | Pode criar novos projetos, gerenciar equipes, alocar membros e visualizar métricas gerenciais e dashboards consolidados. |
| **Usuário** | `user` | Operador padrão. Participa de projetos onde for explicitamente incluído, gerencia e move suas tarefas atribuídas e comenta demandas. |

### Permissões Globais Mapeadas no Banco
A tabela `permissions` e a tabela pivot `role_permissions` associam capacidades específicas aos perfis:
* `users.view`, `users.create`, `users.edit`, `users.delete`: Gestão de contas;
* `projects.create`, `projects.view_all`: Criação e visibilidade global de projetos;
* `audit.view`: Visualização de registros e trilha de auditoria;
* `comments.moderate`: Capacidade de editar/excluir qualquer comentário impróprio.

---

## 2. Camada 2 — Papéis por Projeto (`project_user`)

Dentro de um projeto específico, os membros recebem um papel que define sua capacidade operacional no contexto desse projeto:

| Papel no Projeto | Enum | Capacidades |
| :--- | :--- | :--- |
| **Proprietário** | `OWNER` | Criador do projeto. Controle total sobre configurações, exclusão lógica, adição/remoção de gerentes e membros. Não pode ser removido da equipe. |
| **Gerente do Projeto**| `MANAGER` | Coordena demandas, cria e atribui tarefas, reordena o quadro Kanban e adiciona membros à equipe. |
| **Membro** | `MEMBER` | Cria tarefas, atualiza status e reordena tarefas no Kanban, adiciona comentários e acompanha notificações. |
| **Visualizador** | `VIEWER` | Acesso somente-leitura. Pode acompanhar o Kanban, ler tarefas e comentários, mas não tem permissão para alterar, criar ou reordenar itens. |

---

## 3. Implementação Técnica

### 3.1 Laravel Policies & Gates
A autorização é aplicada de forma descentralizada e coesa através de Policies dedicadas:
* `UserPolicy`: Regras de administração de contas de usuários;
* `ProjectPolicy`: Regras de visualização, criação, edição, deleção e gestão de membros;
* `TaskPolicy`: Regras de manipulação de tarefas e reordenação no Kanban;
* `TaskCommentPolicy`: Regras de criação, edição e exclusão de comentários (com direito de autor e moderação de administradores);
* `AuditLogPolicy`: Acesso restrito a relatórios e trilha de auditoria.

### 3.2 Validação nas Actions e Form Requests
Toda operação protegida passa por dupla camada de defesa:
1. **Form Request (`authorize()`):** Valida pré-condições imediatas de perfil;
2. **Action / Policy:** Checa pertinência e escopo de pertinência no banco de dados (ex.: validar se o usuário é membro do projeto ao atribuir uma tarefa).

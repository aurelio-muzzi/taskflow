# Autorização e RBAC — TaskFlow

Para a documentação completa, matriz de permissões por perfil e implementação de regras de segurança, consulte:

👉 **[docs/rbac.md](file:///c:/Projeto/taskflow/docs/rbac.md)**

---

### Resumo Rápido

1. **Perfis Globais (System Roles):**
   * `ADMIN`: Acesso global e irrestrito.
   * `MANAGER`: Criação e gestão de projetos e relatórios.
   * `USER`: Participação em projetos e tarefas atribuídas.

2. **Perfis de Projeto (Project Roles):**
   * `OWNER`: Dono e criador do projeto.
   * `MANAGER`: Gestor operacional da equipe e tarefas.
   * `MEMBER`: Executor com permissão de criação, movimentação no Kanban e comentários.
   * `VIEWER`: Apenas leitura sem capacidade de alteração.

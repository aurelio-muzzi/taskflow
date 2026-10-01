# Autorização e RBAC

O TaskFlow implementa autorização em dois níveis complementares:

## 1. Nível Global de Sistema (System Roles)

* **`ADMIN`**: Acesso completo ao sistema, relatórios gerenciais, usuários e auditoria.
* **`MANAGER`**: Pode criar projetos e gerenciar projetos aos quais é associado.
* **`USER`**: Pode participar de projetos em que foi incluído e interagir em suas tarefas.

## 2. Nível Contextual do Projeto (Project Roles)

Dentro de cada projeto, um usuário possui uma função em `project_members`:
* **`OWNER`**: Criador/dono do projeto.
* **`MANAGER`**: Gestor do projeto.
* **`MEMBER`**: Membro executor.
* **`VIEWER`**: Apenas visualizador.

## 3. Policies

Cada entidade possui uma Policy dedicada (`UserPolicy`, `ProjectPolicy`, `TaskPolicy`, `CommentPolicy`) que avalia as permissões tanto globais quanto contextuais.

# Autenticação — TaskFlow (Laravel Sanctum)

O TaskFlow utiliza o **Laravel Sanctum** para autenticação segura baseada em tokens de acesso pessoal (Personal Access Tokens) para a SPA Vue 3 e consumidores de API.

---

## 1. Visão Geral da Arquitetura de Autenticação

* **Mecanismo:** Bearer Token via header HTTP `Authorization: Bearer <token>`;
* **Emissão:** `POST /api/v1/auth/login` valida credenciais e gera token criptografado na tabela `personal_access_tokens`;
* **Status do Usuário:** Somente contas ativas (`status == 'ACTIVE'`) conseguem se autenticar ou manter sessão;
* **Expiração e Revogação:** `POST /api/v1/auth/logout` invalida imediatamente o token atual;
* **Senhas:** Criptografadas com `bcrypt` (fator de custo padrão do Laravel 12).

---

## 2. Endpoints do Módulo de Autenticação

### 2.1 Login
* **Rota:** `POST /api/v1/auth/login`
* **Acesso:** Público
* **Payload:**
  ```json
  {
    "email": "admin@taskflow.local",
    "password": "password"
  }
  ```
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "message": "Autenticação realizada com sucesso.",
    "data": {
      "token": "1|eX4mpleT0k3nS4nctumStR1ng...",
      "user": {
        "id": 1,
        "name": "Administrador",
        "email": "admin@taskflow.local",
        "role": {
          "id": 1,
          "name": "Administrador",
          "slug": "admin"
        },
        "status": "ACTIVE"
      }
    }
  }
  ```

### 2.2 Logout
* **Rota:** `POST /api/v1/auth/logout`
* **Acesso:** Autenticado (`auth:sanctum`)
* **Headers:** `Authorization: Bearer <token>`
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "message": "Sessão encerrada com sucesso."
  }
  ```

### 2.3 Dados do Usuário Autenticado
* **Rota:** `GET /api/v1/auth/me`
* **Acesso:** Autenticado (`auth:sanctum`)
* **Headers:** `Authorization: Bearer <token>`
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "name": "Administrador",
      "email": "admin@taskflow.local",
      "role": {
        "id": 1,
        "name": "Administrador",
        "slug": "admin"
      },
      "status": "ACTIVE"
    },
    "message": "Dados do usuário autenticado."
  }
  ```

### 2.4 Recuperação de Senha (Esqueci Minha Senha)
* **Rota:** `POST /api/v1/auth/forgot-password`
* **Acesso:** Público
* **Payload:**
  ```json
  {
    "email": "dev@taskflow.local"
  }
  ```
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "message": "Instruções para recuperação de senha foram geradas com sucesso.",
    "data": {
      "reset_token": "64_character_hex_secure_token..."
    }
  }
  ```

### 2.5 Redefinição de Senha
* **Rota:** `POST /api/v1/auth/reset-password`
* **Acesso:** Público
* **Payload:**
  ```json
  {
    "token": "64_character_hex_secure_token...",
    "email": "dev@taskflow.local",
    "password": "NovaSenhaSegura123!",
    "password_confirmation": "NovaSenhaSegura123!"
  }
  ```
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "message": "Senha redefinida com sucesso. Você já pode autenticar-se."
  }
  ```

### 2.6 Alteração de Senha pelo Usuário Logado
* **Rota:** `PUT /api/v1/auth/password`
* **Acesso:** Autenticado (`auth:sanctum`)
* **Payload:**
  ```json
  {
    "current_password": "SenhaAtual123!",
    "password": "NovaSenhaSegura123!",
    "password_confirmation": "NovaSenhaSegura123!"
  }
  ```
* **Resposta de Sucesso (200 OK):**
  ```json
  {
    "success": true,
    "message": "Senha alterada com sucesso."
  }
  ```

---

## 3. Segurança e Boas Práticas

1. **Proteção contra Brute Force:** Limitação de taxa de requisições configurada para endpoints sensíveis de autenticação.
2. **Sanitização de Respostas:** O hash de senhas e tokens de redefinição brutos nunca são retornados em endpoints comuns ou armazenados em logs de auditoria.
3. **Validação Estrita de Senhas:** Exigência de comprimento mínimo, caracteres alfanuméricos e confirmação explícita de campos de senha.
4. **Verificação de Conta Ativa:** A cada requisição autenticada, caso a conta do usuário seja desativada por um administrador, o acesso à API é imediatamente rejeitado com status 403 Forbidden.

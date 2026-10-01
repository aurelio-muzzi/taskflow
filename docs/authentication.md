# Autenticação — Laravel Sanctum

O sistema utiliza **Laravel Sanctum** com suporte a tokens pessoais (Bearer Tokens).

## Fluxo de Autenticação

1. O cliente envia `POST /api/v1/auth/login` com `email` e `password`.
2. A API valida as credenciais e confirma se o usuário possui `status == 'ACTIVE'`.
3. Se válido, a API emite um Personal Access Token via Sanctum.
4. As requisições subsequentes autenticadas enviam o header:
   ```http
   Authorization: Bearer <token>
   ```
5. Para encerrar a sessão, `POST /api/v1/auth/logout` revoga o token corrente.

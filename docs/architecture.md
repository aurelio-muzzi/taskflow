# Arquitetura do TaskFlow

## Visão Geral

O **TaskFlow** adota uma arquitetura em camadas orientada a casos de uso atômicos (Action/Service-Driven Architecture), com foco em desacoplamento entre apresentação, regras de domínio e persistência.

```
Frontend (Vue 3 + TS)
         │ HTTP/REST (JSON)
         ▼
Nginx (Proxy Reverso)
         │ FastCGI
         ▼
Laravel Routing & Middleware (Sanctum / RBAC)
         │
         ▼
Form Request (Validação Estrita)
         │
         ▼
Controllers (Thin Controllers)
         │
         ▼
DTOs & Actions / Services (Lógica de Negócio & Domínio)
         │
         ▼
Eloquent Models & Database (PostgreSQL + AuditObserver)
         │
         ▼
API Resources (Transformação JSON)
```

## Diretrizes de Camadas

1. **Controllers Finos**:
   * Responsáveis exclusivamente pelo ciclo de requisição/resposta HTTP.
   * Não contêm lógica de negócio, persistência direta nem formatação manual de dados.
   
2. **Form Requests**:
   * Validam e tipam as entradas do usuário antes que qualquer lógica de domínio seja invocada.
   * Mensagens padronizadas e regras estritas.

3. **Actions & Services**:
   * **Actions**: Ações únicas e atômicas (ex: `CreateTaskAction`, `AssignMemberAction`).
   * **Services**: Orquestrações mais complexas ou consultas agregadas (ex: `DashboardService`, `AuditService`).

4. **DTOs (Data Transfer Objects)**:
   * Encapsulam os dados validados com tipagem rígida, eliminando arrays associativos despadronizados.

5. **API Resources & Envelopes**:
   * Todo endpoint da API retorna o padrão consistente:
     * Sucesso: `{ "success": true, "data": ..., "message": "..." }`
     * Erro: `{ "success": false, "message": "...", "errors": { ... } }`

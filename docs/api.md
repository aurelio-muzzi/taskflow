# Documentação da API REST — /api/v1

## Padrão de Resposta

Todas as respostas da API respeitam o padrão:

### Sucesso (200, 201)
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully."
}
```

### Erro de Validação (422)
```json
{
  "success": false,
  "message": "Validation error.",
  "errors": {
    "field": ["Mensagem descritiva do erro."]
  }
}
```

### Erro de Autorização (403)
```json
{
  "success": false,
  "message": "You are not authorized to perform this action."
}
```

## Endpoints Disponíveis (Etapa 1)

* `GET /api/v1/health`: Retorna o status de saúde e conectividade dos serviços.

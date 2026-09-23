# Arquitetura do Web

O Web é uma SPA React/TypeScript executada com Vite.

## Responsabilidades

- componentes reutilizáveis para UI e formulários;
- contextos para autenticação e notificações;
- chamadas HTTP centralizadas no cliente Axios;
- tipos gerados a partir de `docs/openapi.yaml`;
- build independente da API.

O frontend não acessa o banco diretamente. Alterações no contrato da API devem atualizar o OpenAPI e regenerar `src/types/api.generated.ts`.

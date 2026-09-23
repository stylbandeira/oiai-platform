# Release migration guides

Esta pasta contém instruções para atualizações que exigem ações adicionais de desenvolvedores ou consumidores da API.

Não crie um guia para toda release. Use esta área somente quando houver, por exemplo:

- endpoint removido ou alterado de forma incompatível;
- mudança no formato de uma resposta ou requisição;
- variável de ambiente nova ou removida;
- migration obrigatória;
- biblioteca ou serviço substituído;
- alteração arquitetural que exija adaptação;
- mudança de comportamento que não possa ser comunicada apenas pelo CHANGELOG.

## Convenção de nomes

Use o componente e a versão afetada:

```text
api-v2-migration.md
web-v3-migration.md
```

Cada GitHub Release com breaking change deve apontar para o guia correspondente.

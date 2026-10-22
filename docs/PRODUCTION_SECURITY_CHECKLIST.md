# Checklist de validação de produção

Use este checklist antes de cada publicação importante. Marque um item somente
quando houver evidência no ambiente de destino.

## Fase 1 — Infraestrutura crítica

- [ ] `APP_ENV=production` confirmado no container.
- [ ] `APP_DEBUG=false` confirmado no container.
- [ ] HTTPS ativo e certificado TLS válido.
- [ ] HTTP redireciona para HTTPS.
- [ ] SSH funciona por chave em uma segunda sessão.
- [ ] Login SSH por senha desabilitado.
- [ ] Firewall ativo; somente SSH, HTTP e HTTPS expostos.
- [ ] MySQL não publicado nem acessível externamente.
- [ ] Redis não publicado nem acessível externamente.
- [ ] Queue e scheduler executam como `www-data`, nunca como `root`.

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml exec app printenv APP_ENV APP_DEBUG
sudo ufw status verbose
sudo ss -lntup
curl -I http://api.example.com
curl -I https://api.example.com
openssl s_client -connect api.example.com:443 -servername api.example.com </dev/null
```

## Fase 2 — Segurança da aplicação

- [ ] CORS permite somente as origens oficiais.
- [ ] `localhost` não está permitido em produção.
- [ ] Security headers presentes e CSP compatível com os assets.
- [ ] HSTS ativo somente após HTTPS confirmado.
- [ ] Rate limits retornam `429` quando excedidos.
- [ ] Recuperação de senha e reenvio de verificação não revelam usuários.
- [ ] Verificação de e-mail funcionando.
- [ ] Usuário não aprovado não acessa área privada.
- [ ] Policies cobrem `401`, `403` e acesso autorizado.
- [ ] Ownership impede acesso entre usuários.
- [ ] Mass assignment e campos administrativos revisados.

```bash
curl -sSI https://api.example.com/api/health
curl -sSI -H 'Origin: https://origem-nao-autorizada.example' https://api.example.com/api/lists
docker compose -f docker-compose.prod.yml exec app php artisan config:show cors
```

## Fase 3 — Auditoria e recuperação

- [ ] Logs de autenticação funcionando.
- [ ] Logs não contêm senhas, tokens, cookies ou `Authorization`.
- [ ] Aprovações, rejeições, bloqueios e reativações possuem ator, alvo e timestamp.
- [ ] Backup automático está agendado.
- [ ] Retenção de 7 diários, 4 semanais e 3 mensais confirmada.
- [ ] Backup foi restaurado em banco temporário com sucesso.
- [ ] Existe cópia externa e criptografada do backup.

```bash
sudo ls -l /backups/oiai
sudo tail -n 100 /var/log/oiai-backup.log
RESTORE_TEST_CONFIRM=YES ./scripts/restore-mysql-backup-test.sh /backups/oiai/ARQUIVO.sql.gz
```

## Fase 4 — Garantia

- [ ] Suíte automatizada da API passou.
- [ ] Pint/lint passou.
- [ ] Análise estática passou.
- [ ] Build do frontend passou.
- [ ] Healthcheck da aplicação, banco, fila e busca passou.
- [ ] Teste externo de portas confirmou somente as portas esperadas.
- [ ] Rollback conhecido está disponível.

```bash
cd apps/api
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress
cd ../web
npm run build
```

## Definition of Done

O Oiaí está pronto para exposição inicial somente quando todos os itens
obrigatórios estiverem marcados com evidência. Qualquer item não confirmado
deve bloquear a publicação ou ser formalmente aceito como risco pelo
responsável técnico.

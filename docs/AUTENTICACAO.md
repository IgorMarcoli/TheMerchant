# Verificação de e-mail e recuperação de acesso (RF03 / #27)

## Responsabilidade de cada serviço

O Laravel gerencia as contas de `users`, a sessão, a assinatura dos links, o hash
das senhas e os tokens de recuperação. O Supabase é o PostgreSQL da aplicação,
no schema configurado por `DB_SCHEMA`; **Supabase Auth não participa deste fluxo**.
O envio usa o mailer do Laravel e um provedor SMTP. Não criar uma segunda conta
em `auth.users` nem usar a chave de serviço do Supabase para enviar e-mails.

## Fluxos disponíveis

- Cadastro: envia a confirmação pela fila. O usuário também pode acessar
  `/verificar-email` pelo perfil e reenviar uma vez por minuto.
- Confirmação: link assinado, válido por 60 minutos, exige sessão da mesma conta
  ativa. Link adulterado, vencido, de outra conta ou do endereço anterior é recusado.
- Mudança de e-mail: remove a confirmação, apaga tokens de recuperação vinculados
  ao endereço antigo/novo e envia confirmação para o novo endereço.
- Recuperação: “Esqueci minha senha” no login. A resposta é igual para contas
  existentes, inexistentes e suspensas. A consulta da conta acontece no worker.
- Redefinição: token armazenado com hash, validade de 60 minutos, uso único.
  A senha exige no mínimo 8 caracteres e confirmação. O token é consumido junto
  com a atualização da senha, rotação do remember token e exclusão das sessões
  em banco, sob transação e bloqueio da conta. O usuário precisa entrar novamente.
- Limites: recuperação permite 3 requisições/minuto por e-mail e 10 por IP;
  confirmação permite 6 acessos/minuto por conta. O broker também limita a
  emissão de tokens a um por minuto. Contas suspensas não confirmam e-mail nem
  recuperam senha.

A confirmação fica visível no perfil. Esta entrega não altera as permissões de
compra/venda existentes nem exige confirmação para todas as rotas autenticadas.
Recuperar a senha não marca o e-mail como confirmado.

## Preparar o ambiente

1. Executar `php artisan migrate`. As migrations adicionam `password_reset_tokens`
   e `failed_jobs`; `jobs` e `sessions` já possuem migrations no projeto.
   Não executar `migrate:fresh` em banco com dados.
2. Configurar `APP_URL` com a URL real, incluindo HTTPS na publicação. O worker
   usa essa URL para gerar links. Manter `APP_KEY` estável: os jobs das notificações
   e da solicitação de recuperação são criptografados na fila.
3. Manter `QUEUE_CONNECTION=database` e `SESSION_DRIVER=database`.
4. Para entrega real, configurar no `.env`:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtp
   MAIL_HOST=host-do-provedor
   MAIL_PORT=587
   MAIL_USERNAME=usuario-do-provedor
   MAIL_PASSWORD=segredo-do-provedor
   MAIL_FROM_ADDRESS=contato@seu-dominio.com
   MAIL_FROM_NAME="TheMerchant"
   ```

   Usar host, porta, esquema e remetente autorizado fornecidos pelo provedor.
   `MAIL_MAILER=log` é só desenvolvimento: não entrega mensagens e grava links
   sensíveis nos logs. `sync` não oferece processamento assíncrono.
5. Após alterar configuração, executar `php artisan config:clear` e reiniciar o
   worker com `php artisan queue:restart`. Manter em execução, supervisionado no
   servidor:

   ```shell
   php artisan queue:work --tries=3 --timeout=60
   ```

6. Executar o scheduler do Laravel (`php artisan schedule:run` a cada minuto no
   servidor, ou `php artisan schedule:work` no desenvolvimento). Ele remove tokens
   expirados a cada hora. A expiração é validada mesmo antes dessa limpeza.

Notificações só entram na fila após commit. O worker descarta envios se a conta
foi suspensa, o endereço mudou, o e-mail já foi confirmado ou o token deixou de
ser válido. Falhas ficam em `failed_jobs`; inspecionar com `php artisan queue:failed`
e corrigir o provedor/configuração antes de repetir o job.

## Validação

```shell
php artisan test --filter=AccountRecoveryTest
php artisan test --filter=AccountNotificationQueueTest
```

Os testes usam SQLite em memória e mailer `array`, sem envio externo. Incluem
HTTP, adulteração/expiração de links, troca de endereço, suspensão, respostas
genéricas, limites, token reutilizado, revogação de sessões e execução real do
worker sobre a fila em banco. O teste de fila verifica o envio após processamento,
o payload criptografado e o descarte de notificações obsoletas.

Para validar a publicação, cadastrar uma conta de teste, consumir o link recebido,
sair, recuperar a senha, testar o login com a nova senha e a recusa do mesmo token
pela segunda vez. Essa etapa depende do SMTP e do worker do ambiente.

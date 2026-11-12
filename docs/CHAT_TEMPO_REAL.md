# Chat privado em tempo real

[Voltar ao README](../README.md) · [Execução](EXECUCAO.md) · [Deploy no Render](DEPLOY_RENDER.md) · [Testes](TESTES.md)

Issue #30 · Requisitos `RF21`, `RF22`, `RF23`, `RNF03`, `RNF06`, `RNF07`, `RNF08`, `RNF09`.

## 1. Como funciona

```text
Navegador A ──POST /chat/{id}/mensagens──▶ Laravel ──grava (DB::transaction)──▶ messages
                                              │
                                     após o commit
                                              ▼
                               MessageSent (fila) ──▶ Reverb ──private-chat.{id}──▶ Navegador B
                                                                                      │
                               { conversation_id, message_id, sender_id }   (só ids)  │
                                                                                      ▼
Navegador B ◀──────────── GET /chat/{id}/mensagens?after_id=N ───────────────────────┘
               (sessão, conta ativa e participação revalidadas a cada leitura)
```

- **Conversa única** por comprador/interessado, vendedor e anúncio (índice único `conversations_participants_listing_unique`). O remetente vem sempre da sessão; o payload nunca define quem envia.
- **Quem pode abrir:** usuário ativo, em anúncio publicado de outro vendedor. Quem comprou o item (pedido `pago`, `em_andamento` ou `concluido`) retoma a conversa mesmo com o anúncio vendido ou pausado; o vendedor só inicia conversa com quem comprou. Conversa consigo mesmo é bloqueada.
- **Autorização:** `ConversationPolicy` (participante com conta ativa). Administrador **não** lê conversas pelo papel.
- **Persistir antes de transmitir:** a mensagem é gravada em transação; `MessageSent` implementa `ShouldDispatchAfterCommit`, então rollback não transmite nada.
- **Sinal, não conteúdo:** o WebSocket leva apenas ids. O texto sempre vem do HTTP, que passa por `auth`, `EnsureAccountIsActive` e pela policy. Nenhum conteúdo vai para canal público.
- **Idempotência:** o navegador gera um UUID por envio e o repete nas novas tentativas. A constraint `messages_client_unique (conversation_id, sender_id, client_uuid)` e o `firstOrCreate` (que trata a violação de unicidade) garantem uma única mensagem, mesmo com requisições simultâneas. A repetição responde `200` com a mensagem original; a primeira gravação responde `201`.
- **Validação:** texto simples de 1 a 2000 caracteres (quebras de linha permitidas, caracteres de controle rejeitados). A saída é escapada (`x-text` no Alpine, `{{ }}` no Blade).
- **Limites:** 20 envios por minuto por conta (`throttle:chat-messages`, inclui novas tentativas), 10 aberturas de conversa por minuto (`throttle:chat-start`) e 60 autorizações de canal por minuto.
- **Leitura:** cada participante tem seu cursor (`buyer_last_read_message_id`, `seller_last_read_message_id`), que só avança. O contador soma mensagens recebidas depois do cursor.
- **Histórico paginado** (`GET /chat/{id}/mensagens`), 50 por página:
  - sem parâmetro: as 50 mais recentes;
  - `before_id=X`: as anteriores a `X` (botão "Carregar mensagens anteriores");
  - `after_id=X`: as posteriores a `X`, em ordem, com `has_more` (sincronização e reconexão).

## 2. Revogação de acesso (conta suspensa)

O Reverb não reavalia permissões de uma conexão já aberta. Por isso a revogação não depende do WebSocket:

1. **Suspensão** pelo painel administrativo (#22) grava `users.status = suspended` (regra #2) e apaga as sessões da conta (`SellerAccountService::updatePermissions`).
2. **Leitura e envio:** toda rota do chat passa por `auth` + `EnsureAccountIsActive`, que relê o status no banco. A sessão antiga recebe `401`/`403` na próxima requisição.
3. **Canal já assinado:** continua recebendo apenas sinais com ids, sem texto. Para ler, o navegador precisa do HTTP, que já está negado.
4. **Reconexão:** o `pusher-js` pede nova autorização em `/broadcasting/auth` a cada reconexão. A rota exige sessão válida e conta ativa, e o canal (`App\Broadcasting\ConversationChannel`) aplica a mesma policy. A reassinatura é negada.
5. **Navegador:** ao receber `401`, `403` ou `419`, a tela sai do canal, para a sincronização, desabilita o envio e mostra "Acesso encerrado". Com o WebSocket ativo há também uma verificação HTTP a cada 15 s, então a revogação aparece mesmo sem novas mensagens.

O teste `test_suspension_revokes_read_send_and_reconnection_for_an_established_session` comprova o fluxo com duas sessões. O teste de regressão dedicado fica na #32.

## 3. Variáveis de ambiente

Nenhum valor real vai para o repositório. Gere ID, chave e segredo com `php artisan reverb:install` (ou com qualquer gerador aleatório) e guarde-os só no `.env` ou no painel do Render.

| Variável | Onde | Exemplo local | Produção |
| :--- | :--- | :--- | :--- |
| `BROADCAST_CONNECTION` | app | `reverb` (`log` desliga o tempo real; o chat sincroniza por HTTP a cada 4 s) | `reverb` |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | app e Reverb (iguais) | valores gerados | valores gerados, secretos |
| `REVERB_HOST` | app e Reverb | `localhost` | domínio público do serviço Reverb |
| `REVERB_PORT` | app e Reverb | `8080` | `443` |
| `REVERB_SCHEME` | app e Reverb | `http` | `https` |
| `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT` | Reverb | `0.0.0.0`, `8080` | `0.0.0.0`; a porta vem de `$PORT` no entrypoint |
| `REVERB_ALLOWED_ORIGINS` | Reverb | `localhost,127.0.0.1` | domínio da aplicação, ex. `themerchant-p2be.onrender.com` |
| `QUEUE_CONNECTION` | app | `sync` | `database` (o entrypoint sobe `queue:work`) |

Somente `REVERB_APP_KEY`, host, porta e esquema chegam ao navegador. O segredo nunca sai do servidor.

`REVERB_ALLOWED_ORIGINS` aceita hosts separados por vírgula (sem protocolo) e curingas do `Str::is`, como `*.onrender.com`. Sem a variável, vale o host de `APP_URL`. Não use `*` em produção. Eventos de cliente (whisper) ficam desligados (`REVERB_APP_ACCEPT_CLIENT_EVENTS_FROM=none`) e o limite de mensagens por conexão do Reverb fica ligado.

## 4. Executar localmente

São três processos, cada um em um terminal:

```bash
php artisan serve
```

```bash
php artisan reverb:start --debug
```

```bash
php artisan queue:work
```

O terceiro só é necessário com `QUEUE_CONNECTION` diferente de `sync`. Com `sync`, o sinal é enviado ao Reverb na própria requisição, depois do commit.

A tela do chat carrega o `pusher-js` 8.4.0 e o `laravel-echo` 2.1.6 do jsDelivr, com SRI, apenas quando `BROADCAST_CONNECTION=reverb` e a chave estão configurados. O indicador no topo mostra "Tempo real ativo" com o WebSocket conectado e "Sincronizado" no modo HTTP.

## 5. Produção (Render)

O Reverb roda como **segundo Web Service** com a mesma imagem Docker:

1. Crie um Web Service a partir do mesmo repositório e branch (runtime Docker).
2. Defina `APP_ROLE=reverb`. O `docker/entrypoint.sh` pula Apache, migrations e seeds e executa `php artisan reverb:start --host=0.0.0.0 --port=$PORT`.
3. Copie `APP_KEY`, `APP_ENV`, `REVERB_APP_ID`, `REVERB_APP_KEY` e `REVERB_APP_SECRET` do serviço web. Defina `REVERB_ALLOWED_ORIGINS` com o domínio da aplicação.
4. No serviço web, defina `BROADCAST_CONNECTION=reverb`, `REVERB_HOST` (domínio do serviço Reverb), `REVERB_PORT=443` e `REVERB_SCHEME=https`.

**TLS:** o Render termina o TLS no proxy e encaminha a conexão ao Reverb sem criptografia, dentro da rede dele. O navegador sempre usa `wss://` (porta 443) e o Laravel publica eventos por `https://`. Em servidor próprio, coloque o Reverb atrás de Nginx/Caddy com certificado e encaminhe `Upgrade`/`Connection`. Não exponha a porta 8080 diretamente.

**Worker:** `MessageSent` é um evento enfileirado (`ShouldBroadcast`). Com `QUEUE_CONNECTION=database`, o entrypoint do serviço web já sobe `queue:work`. Sem worker, as mensagens continuam gravadas e aparecem pela sincronização HTTP, só que sem o aviso instantâneo.

## 6. Roteiro manual com duas sessões

1. Abra duas janelas isoladas (uma anônima) e entre com o comprador em uma e com o vendedor na outra.
2. Comprador: em um anúncio publicado, clique em "Falar com o vendedor" e envie uma mensagem. Ela aparece para o vendedor sem recarregar a página.
3. Responda pelo vendedor e confira o contador de não lidas do comprador antes e depois de abrir a conversa.
4. Pare o `reverb:start`, envie mensagens e suba o servidor de novo: o indicador volta para "Tempo real ativo" e as mensagens perdidas aparecem.
5. Desligue a rede do navegador durante um envio e use "Tentar novamente": a mensagem aparece uma vez só.
6. Entre com uma terceira conta e abra `/chat/{id}/mensagens` da conversa: `403`.
7. Com a conversa aberta nas duas janelas, suspenda o vendedor pelo painel administrativo. A janela dele mostra "Acesso encerrado" em até 15 s e não envia nem lê mais nada.
8. Com `QUEUE_CONNECTION=database`, pare o `queue:work` e envie mensagens: elas são gravadas e aparecem pela sincronização HTTP. Suba o worker de novo: os avisos pendentes são transmitidos e nada é duplicado.
9. Repita os passos 2, 3 e 5 com a largura de um celular e usando só o teclado (Tab até o campo, Enter envia, Shift+Enter quebra a linha).

## 7. Testes automatizados

```bash
php artisan test --filter=Chat
```

```bash
node --test tests/js/chat.test.mjs
```

- `tests/Feature/ChatTest.php` (#30): abertura de conversa, bloqueio consigo mesmo, retomada após compra, terceiros e administrador sem acesso, idempotência por UUID, rollback sem sinal, sinal sem conteúdo, validação de texto simples, limite de envios, histórico paginado e reconexão, autorização do canal privado (participante, terceiro, administrador, suspenso, visitante) e revogação com duas sessões.
- `tests/Feature/ChatRegressionTest.php` (#32):
  - criação concorrente de conversa e reenvio concorrente com o mesmo UUID, simulando a outra requisição entre o `SELECT` e o `INSERT`: resulta em uma conversa, uma mensagem e um único sinal;
  - constraints únicas no banco e UUID com escopo por remetente e por conversa;
  - isolamento entre conversas no histórico, na tela e no cursor de leitura;
  - cursores independentes por participante, que nunca retrocedem, e contador de não lidas somando várias conversas e ignorando as próprias mensagens;
  - matriz de acesso HTTP de todas as rotas do chat para visitante (`401`), terceiro e conta suspensa (`403`) e participante (`200`), além da reativação da conta sem perda de histórico;
  - escape de texto, título e nome contra XSS na caixa de entrada e no payload inicial (só `x-text`, nunca `x-html`);
  - limites de 2000 caracteres contados em caracteres (não em bytes), `trim` e texto só com espaços;
  - limite de aberturas de conversa por conta e evento sem texto, e-mail ou nome.
- `tests/js/chat.test.mjs`: comportamento do navegador (envio, nova tentativa com o mesmo UUID após falha de rede, sincronização, queda e volta da conexão, Shift+Enter e composição IME).

Os testes usam SQLite em memória e broadcaster simulado. A entrega em tempo real com Reverb e worker reais, o uso no celular e por teclado e a queda do worker dependem do roteiro da seção 6, com duas pessoas.

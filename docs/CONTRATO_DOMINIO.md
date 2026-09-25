# Contrato de domínio v1 — disponibilidade, sessões e conclusão por item

Definição da issue [#29](https://github.com/IgorMarcoli/TheMerchant/issues/29), em 25/09/2026.
Requisitos: RF05, RF06, RF09, RF10, RF14, RF15 e RNF06.
Este contrato define o comportamento alvo; sua aceitação não comprova estoque,
reservas, pagamento, entrega ou chat funcionando. As implementações e migrations
cabem às issues consumidoras listadas ao final. Não depende de #5 nem de M3/M4.

## 1. Tipos e elegibilidade

- Cosmético (`cosmetic`): unidade única, quantidade obrigatoriamente 1, capacidade
  inicial 1 e duração nula. Após consumo pago, capacidade 0 e anúncio vendido.
- Serviço (`service`): coaching vendido em sessões inteiras, quantidade >= 1,
  duração por sessão em minutos inteiros > 0 e capacidade inicial inteira > 0,
  configuradas pelo vendedor. Preço unitário é por sessão; total = preço × quantidade.
  Não se aceitam sessões fracionadas ou quantidade superior ao disponível.
- Publicação/novo checkout exigem vendedor ativo com perfil aprovado, anúncio
  publicado, jogo ativo e categoria ativa. Categoria deve ser global (game_id nulo)
  ou pertencer ao jogo escolhido; o tipo deriva da categoria. Não se pode alterar
  jogo/categoria/tipo de um anúncio com reservas ou vendas; cria-se novo anúncio.
- Venda de contas está fora da v1 e de seus seeds. Sua inclusão exige decisão
  versionada separada sobre escopo e regras; não é tratada como cosmético.
- Agenda automática, seleção de slots e sincronização de calendários estão fora da v1.

## 2. Capacidade, reserva e concorrência

`capacity` é o saldo ainda não consumido por pagamentos, não a oferta original.
`reserved(now)` soma quantidades de reservas active com expires_at > now.
`available(now) = capacity - reserved(now)`, sempre >= 0.
Expiradas deixam de bloquear imediatamente, mesmo antes do job de limpeza.

Carrinho não reserva. Checkout revalida elegibilidade, preço e quantidades e,
numa única DB::transaction, bloqueia anúncios por id crescente (lockForUpdate),
libera reservas vencidas, valida todos os saldos e cria pedido, itens e reservas.
Se qualquer item falhar, toda a operação é revertida. Não há pedido parcial.
Repetir o checkout com a mesma chave idempotente retorna a mesma tentativa.

Reserva: id, listing_id, order_item_id UNIQUE, quantity inteira positiva,
expires_at UTC, status active/confirmed/released, timestamps e motivo de liberação.
Prazo v1: 15 minutos a partir da criação no servidor; expires_at não é prorrogado
por refresh, repetição de requisição ou webhook. Em now == expires_at já expirou.
Cada tentativa nova exige novo pedido/item, sem reativar reserva released.

O gateway é acionado fora do lock prolongado de banco, com chave idempotente.
Falha definitiva de criação libera reservas; timeout com resultado desconhecido
exige consulta/reconciliação ao provedor. Retry não pode duplicar cobrança.

Aprovação verificada, com todas as reservas ainda válidas, consome o pedido
atomicamente: capacity -= quantity; active -> confirmed; pedido pago e itens
em_entrega. Repetição não consome novamente. Serviço com capacity > 0 permanece
publicado; com saldo 0 fica vendido. Reserva de todo o saldo pode deixá-lo
temporariamente indisponível sem marcar vendido.

Expiração, recusa ou cancelamento antes do pagamento: active -> released,
sem subtrair capacity. Reserva confirmed nunca é liberada pelo job de expiração
nem por evento antigo de recusa. Webhooks devem validar autenticidade, transação,
pedido, moeda e valor, deduplicar evento e transição, e processar sob os mesmos locks.

Pagamento recebido/processado após expires_at ou após liberação não readquire
capacidade, mesmo que haja saldo; registra pagamento tardio em reconciliação,
sem marcar itens em_entrega. #13/#14 devem solicitar estorno idempotente, persistir
status/identificador no provedor e permitir retry de falhas. Se um item expirou,
não se consome parcialmente o pedido. Horário anterior informado pelo provedor
não autoriza tomar capacidade já liberada. Pagamento confirmado anteriormente
e webhook duplicado tardio permanecem confirmados.

Pausa, bloqueio ou suspensão impedem novas compras; preservam reservas válidas,
snapshots e obrigações já adquiridas. Não apagam histórico nem repõem saldo.
Redução manual de capacidade nunca pode ficar abaixo das reservas válidas.
Cancelamento/estorno depois de confirmado não repõe saldo automaticamente:
exige reconciliação e decisão auditável que evite revender algo já entregue.

## 3. Snapshot obrigatório do item

No checkout, copiar e tornar imutáveis:

| Campo lógico | Regra |
| --- | --- |
| listing_id, seller_id, game_id, category_id e títulos | Identificação e contexto da oferta original |
| unit_price e currency | Decimal de duas casas/centavos, BRL, mínimo R$ 1,00; nunca float para totais |
| type | cosmetic ou service |
| quantity | 1 para cosmético; número inteiro de sessões para coaching |
| session_duration_minutes | Nulo para cosmético; inteiro positivo para serviço |
| delivery_instructions / conditions | Forma, prazo, pré-requisitos e condições de entrega anunciadas |
| scheduling_mode | by_chat para coaching; not_applicable para cosmético |
| scheduled_at / timezone | Instante UTC ou nulo quando a combinar; fuso IANA explícito, ex. America/Sao_Paulo |

Instruções e fuso são definidos pelo vendedor e mostrados antes do checkout.
Horário ainda não combinado deve aparecer como “A combinar pelo chat”, nunca
como agendamento confirmado. Combinações posteriores ficam em registro associado
ao item, com participantes, instante UTC, fuso e histórico; não sobrescrevem
as condições comerciais congeladas. Mudanças no anúncio não alteram itens antigos.

## 4. Entrega, sessões e avaliação

Cada item evolui aguardando_pagamento -> em_entrega -> entregue.
Pagamento confirmado é pré-condição de entrega. Para serviço, registrar sessões
concluídas entre 0 e quantity, de maneira idempotente por sessão/identificador.
Sessões parciais mantêm em_entrega; somente todas as sessões concluídas permitem
entregue e delivered_at. Para cosmético, entrega da unidade conclui o item.
Ação do vendedor responsável é autorizada por Policy; idempotência protege
retry/conclusão concorrente, sem nova baixa de capacidade na entrega.

Pedido agregado só fica concluido quando todos os itens estão entregues,
mas isso não bloqueia avaliação de item individual: comprador do pedido pode
avaliar cada order_item_id pago e entregue exatamente uma vez, inclusive
quando outro vendedor ainda não entregou. Vínculo item/pedido deve ser validado.
UNIQUE(order_item_id), transação, Policy e recálculo da reputação protegem
concorrência. Quantidade 3 de sessões no mesmo item dá uma avaliação, não três.

Horários de coaching serão combinados no chat #30/#31, na M4, respeitando acesso
dos participantes e referência ao item/pedido. Aceitar este contrato exige
definição desse fluxo, não implementação do chat.

## 5. Exemplos verificáveis

Considere T0 = 25/09/2026 12:00:00 UTC, TTL 15 minutos, vendedor/jogo/categoria
elegíveis. Cada cenário é independente, salvo quando indicado.

| ID | Entradas / sequência | Resultado esperado |
| --- | --- | --- |
| D01 | Cosmético C=1, q=1, R$ 80; reserva T0; aprovação T0+5min | Antes: C=1, reservado=1, disponível=0; após: C=0, confirmed, vendido, item em_entrega, total R$ 80 |
| D02 | Serviço C=5, q=3, duração 60min, R$ 50/sessão; pagamento válido | Reserva deixa disponível=2; após pagar C=2, reservado=0, publicado; snapshot q=3, 60min, total R$ 150 |
| D03 | Serviço C=2; solicita q=3 ou q=1,5 | Rejeição; nenhuma reserva/pedido persistido ou cobrança; C=2 |
| D04 | Cosmético C=1; A e B tentam q=1 simultaneamente | Lock serializa: exatamente um reserva; outro recebe indisponibilidade, sem cobrança; nunca saldo negativo |
| D05 | Serviço C=5, reserva q=3 T0; nenhuma aprovação até T0+15min | Disponível=5 no instante da expiração; job muda active para released; repetição mantém C=5 |
| D06 | D05; outro compra saldo; chega aprovação antiga T0+16min | Pagamento antigo em reconciliação/estorno idempotente, sem consumir saldo nem entregar; nova compra preservada |
| D07 | Pedido com itens A e B de vendedores diferentes, pago; A entregue, B em_entrega | Comprador pode avaliar A uma vez; B não; pedido não concluído; ao entregar B, avaliação B liberada |
| D08 | D02 pago; completar sessões 1 e 2; repetir sessão 2; completar 3 | Contador 2 após repetição; ainda em_entrega e sem avaliação; após 3: entregue, uma avaliação permitida |
| D09 | D01 recebe aprovação duplicada, depois recusa antiga/expiração | C continua 0, confirmed; item não regride, não há nova baixa nem reposição |
| D10 | Serviço reservado q=2, preço R$ 40, 45min, fuso America/Sao_Paulo; vendedor edita oferta | Item mantém preço R$ 40, duração 45min, condições/fuso originais e total R$ 80 |
| D11 | Categoria de outro jogo, inativa, conta à venda ou vendedor não aprovado | Publicação/novo checkout rejeitado; nenhum consumo ou reserva |
| D12 | Pedido com dois itens, um sem capacidade | Rollback integral; nenhuma reserva remanescente nem cobrança iniciada |
| D13 | T0+15min, aprovação e expiração concorrentes | Prazo vencido: liberação/reconciliação, nunca confirmação por disputa de ordem de execução |
| D14 | Serviço C=3, reserva q=2; reduzir C para 1 ou pausar oferta | Redução rejeitada; pausa impede novas reservas e mantém a existente válida e o histórico |

## 6. Rastreabilidade e aceite

| Consumidor | Obrigação / exemplos |
| --- | --- |
| #5 | Schema, tipos, capacidade e integridade; D01–D04, D11, D14 |
| #6 | Publicação/edição por tipo, instruções, duração e fuso; D10, D11, D14 |
| #11/#12 | Quantidades e totais de carrinho, sem reserva; D01–D03 |
| #13/#14 | Migrations de snapshots/reservas, locks, pagamento e reconciliação; D01–D06, D09–D14 |
| #15/#17 | Exibir condições congeladas, estados, sessões e horário a combinar; D02, D07, D08, D10 |
| #18/#19 | Conclusão idempotente e avaliação por item; D07–D09 |
| #16/#21/#24 | Automatizar cenários integrados nas fases respectivas; D01–D14 |
| #30/#31 | Combinação de horário pelo chat na M4, sem agenda automática; D02, D10 |

Aceite de #29: contrato e exemplos acima versionados, consistentes com
requirements.md/specs.md e vinculados aos consumidores. Implementações consumidoras
devem apontar quais cenários cobrem e registrar eventuais decisões de revisão.

# Guia de testes e validações

[Voltar ao README](../README.md) · [Executar o projeto](EXECUCAO.md)

Este guia separa verificações disponíveis hoje dos critérios das próximas entregas. Uma rota existente, HTTP 200 ou teste de instanciação não comprovam a regra de negócio completa.

## 1. Preparação

- Use dados fictícios: uma conta deve comprar e vender, outra fornece anúncios e uma conta administrativa aprova solicitações. Teste também vendedor pendente e suspenso.
- Registre branch e commit com `git branch --show-current` e `git rev-parse --short HEAD`.
- Os testes de feature usam `RefreshDatabase`: nunca aponte para desenvolvimento compartilhado ou produção.
- Registre cenários como **PASSOU**, **FALHOU**, **BLOQUEADO** ou **NÃO EXECUTADO**. Funcionalidade ausente não é sucesso presumido.

## 2. Testes automatizados no estado atual

Não há `phpunit.xml` nem `tests/Pest.php` versionado nesta revisão. `php artisan test` existe, mas não configura sozinho isolamento e descoberta. Use bootstrap e caminho explícitos.

### Teste unitário sem banco

```powershell
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/CheckoutServiceTest.php
```

Esse teste apenas instancia `CheckoutService` com mock do gateway. Não verifica preço, transação, concorrência ou pagamento.

### Feature com SQLite em memória

Abra **um terminal dedicado** na raiz do projeto:

```powershell
$env:APP_ENV = 'testing'
$env:APP_KEY = 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
$env:DB_URL = ''
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:SESSION_DRIVER = 'array'
$env:CACHE_STORE = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'
$env:APP_CONFIG_CACHE = Join-Path $env:TEMP ('themerchant-testing-' + [guid]::NewGuid().ToString('N') + '.php')
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Feature
```

A chave é pública e exclusiva de teste. `APP_CONFIG_CACHE` aponta para um caminho novo inexistente, evitando configuração cacheada de desenvolvimento; `DB_URL` não deve sobrepor SQLite. **Feche esse terminal depois dos testes** antes de iniciar a aplicação, pois as variáveis continuam válidas nele. O `.env` não é alterado.

Para toda a suíte, no mesmo terminal isolado:

```powershell
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php tests
```

No Linux/macOS, use variáveis restritas ao processo:

```bash
APP_ENV=testing \
APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' \
DB_URL='' DB_CONNECTION=sqlite DB_DATABASE=':memory:' \
SESSION_DRIVER=array CACHE_STORE=array QUEUE_CONNECTION=sync MAIL_MAILER=array \
APP_CONFIG_CACHE="/tmp/themerchant-testing-$$-$(date +%s)-config.php" \
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php tests
```

SQLite em memória serve ao primeiro diagnóstico. Locks/concorrência e SQL específicos também precisam ser testados em MySQL com banco exclusivo, por exemplo `themerchant_testing`, e credencial restrita a ele. Não reutilize `themerchant` para esses testes.

### Resultado observado em 18/09/2026

| Verificação | Resultado |
| :--- | :--- |
| Composer validate / check-platform-reqs | Sucesso no ambiente inspecionado |
| PHP / Laravel | 8.2.33 / 11.56.1 |
| route:list --except-vendor | Executou; 56 rotas exibidas |
| CheckoutServiceTest | 1 teste, 1 assertion, sucesso |
| Feature isolado | 2 testes, 2 assertions, 1 erro: `Database\Factories\UserFactory` ausente |
| WebhookPaymentTest | Passou; verifica resposta HTTP/JSON, não pagamento real nem fila assíncrona |

Não foram executados migrations/seeds no banco de desenvolvimento, build Vite, gateway real ou chat E2E nesta revisão documental.

### Pendências da infraestrutura

- [ ] Criar `database/factories/UserFactory.php` compatível com o schema.
- [ ] Versionar configuração PHPUnit com bootstrap, suites e isolamento explícito.
- [ ] Revisar campos obrigatórios dos dados de teste de categorias/anúncios.
- [ ] Separar testes de dispatch (fila simulada) de processamento do job.
- [ ] Validar integração com worker real e banco exclusivo.
- [ ] Após configurar a suíte, padronizar `php artisan test` no CI.

## 3. Validação estática e inicialização

```powershell
composer validate --no-check-publish
composer check-platform-reqs
php artisan --version
php artisan route:list --except-vendor
php vendor/bin/pint --test
git diff --check
```

`pint --test` verifica estilo sem alterar arquivos. Registrar falhas não equivale a reformatar alterações de outra pessoa. Em base local preparada, `php artisan migrate:status` consulta migrations, mas não homologa regras de negócio.

## 4. Roteiro de homologação

### M1 — Acesso e segurança

| Cenário | Passos | Resultado esperado |
| :--- | :--- | :--- |
| Cadastro/sessão | Criar conta, entrar, sair e tentar acessar perfil | Validação e bloqueio após logout; RF01–RF02 |
| Perfil/senha | Trocar dados; fornecer senha atual incorreta | Validação e credenciais coerentes |
| Permissões | Conta sem perfil aprovado tenta vender; vendedor tenta administrar | Negação sem alterar dados; administrador sem perfil também não publica; RF04 |
| Recuperação/verificação | Recuperar acesso sem login; expirar/reutilizar link | Fluxo seguro; RF03/#27, ainda pendente |
| Diagnósticos | Revisar autorização com dados fictícios | Sem acesso público a dados internos; #28 |

### M2 — Catálogo

| Cenário | Passos | Resultado esperado |
| :--- | :--- | :--- |
| Anúncio | Publicar com imagem/preço e tentar editar como outro vendedor | Validação e autorização; #5–#6 |
| Busca | Combinar jogo/categoria/preço/reputação, paginar e limpar | Filtros preservados, resultados corretos; #7–#8 |
| Visibilidade | Pausar/bloquear anúncio; acessar como visitante | Compra/acesso indevidos bloqueados no servidor |
| Denúncia | Enviar motivo autenticado; repetir como visitante | Registro persistido; visitante impedido; #10/RF16 |
| Coaching | Definir sessões, duração e capacidade | Condições explícitas; #29 ainda planejado |

### M3 — Compra e pagamentos

| Cenário | Passos | Resultado esperado |
| :--- | :--- | :--- |
| Carrinho | Adicionar/remover, tentar anúncio próprio, alterar sessões | Quantidade/preço validados; #11–#12 |
| Preço histórico | Criar pedido e alterar preço do anúncio | Pedido preserva preço; #13 |
| Concorrência | Duas sessões compram cosmético único | Uma reserva, sem venda dupla |
| Expiração | Abandonar pagamento e expirar reserva | Liberação uma única vez |
| Aprovação | Gateway sandbox confirma | Pedido pago após verificação no provedor; #14/#16 |
| Idempotência | Reenviar evento e depois novo estado da mesma transação | Sem duplicação; pending → approved permitido |
| Assinatura/dados | Enviar assinatura inválida ou valor/moeda divergentes | Rejeição sem alteração indevida |
| Falhas | Recusa, gateway indisponível, aprovação tardia | Reconciliação e estado consistente |

**Limitação atual:** o gateway é simulado; não comprova verificação de assinatura no provedor. Abrir a tela de sucesso não representa aprovação real.

Para teste exploratório **somente local**, crie um pedido fictício, configure `QUEUE_CONNECTION=sync` e substitua o número:

```powershell
$payload = @{
    action = 'payment.created'
    data = @{ id = 'demo-local-001' }
    external_reference = 'SUBSTITUA_PELO_NUMERO_DO_PEDIDO_FICTICIO'
} | ConvertTo-Json -Depth 3
Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/api/webhooks/payment' -ContentType 'application/json' -Body $payload
```

Esse envio **altera o pedido fictício** no comportamento atual. Repetir o mesmo payload ajuda a explorar duplicidade; conferir pagamentos e status no banco local. HTTP 200 confirma recebimento, não prova que um pedido foi processado. A ausência de assinatura é limitação da simulação, nunca o contrato de produção.

### M4 — Pós-venda e chat

| Cenário | Resultado esperado |
| :--- | :--- |
| Pedido alheio | Comprador não acessa dados de outra conta; #17 |
| Entrega antes de pagar | Negada; vendedor vê apenas seus itens; #18 |
| Coaching | Todas as sessões do item concluídas antes da avaliação; #29 |
| Avaliação | Uma por item pago/entregue, sem aguardar outro vendedor; #19–#21 |
| Chat em duas sessões | Mensagem aparece sem reload e persiste; #30–#32, pendente |
| Terceiro ou suspenso | Não lê/envia/assina canal privado |
| Reconexão/retry | Recupera mensagens perdidas sem duplicar |
| Não lidas | Contador/cursor independentes, leitura após exibição |

O chat precisa de E2E com Reverb/worker reais após implementação; mocks não comprovam entrega em tempo real.

### M5 — Administração e apresentação

- [ ] Gestão de usuários/jogos/categorias preserva integridade (#22).
- [ ] Suspensão impede operações protegidas, incluindo chat.
- [ ] Denúncia recebe parecer; bloquear/restaurar exige permissão e elegibilidade (#23).
- [ ] Logs não expõem credenciais ou conteúdo privado (#24).
- [ ] PDF/diagramas, requisitos e backlog descrevem o mesmo escopo (#33).
- [ ] Apresentação demonstra compra, reserva, pagamento, entrega, reputação, chat e moderação (#25).

## 5. Usabilidade e desempenho

- Testar desktop/mobile, incluindo 320–375px, sem rolagem horizontal.
- Navegar por teclado: foco visível, labels, erros associados e modais acessíveis.
- Conferir estados vazio, carregando, erro e sucesso; interromper rede para testar recuperação.
- Verificar imagens ausentes e indisponibilidade do CDN.
- Para a meta de busca < 500ms, registrar volume da base, filtros, quantidade de requisições e concorrência; separar execuções frias/aquecidas e reportar mediana/p95.
- Não declarar desempenho com base em uma única requisição ou base vazia.

## 6. Evidências e conclusão

Modelo para relatório ou PR:

```text
Issue / requisitos:
Branch / commit:
Data e ambiente (PHP, Laravel, banco, navegador):
Cenário e pré-condições:
Passos ou comando:
Resultado esperado:
Resultado obtido:
Estado: PASSOU | FALHOU | BLOQUEADO | NÃO EXECUTADO
Evidência (log sanitizado, screenshot ou saída de teste):
Pendências:
```

Concluir uma issue exige executar seus critérios, registrar evidências, avaliar regressões e atualizar a documentação do comportamento entregue. Explicitar falhas e cenários não executados.

A release depende de RF03, revisão dos diagnósticos, pagamento/estoque, testes do chat e revisão documental. Abrir a aplicação no navegador não basta para concluir M1 ou a entrega final.

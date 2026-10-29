# Guia de testes e validações

[Voltar ao README](../README.md) · [Executar o projeto](EXECUCAO.md)

Este guia separa verificações disponíveis hoje dos critérios das próximas entregas. Uma rota existente, HTTP 200 ou teste de instanciação não comprovam a regra de negócio completa.

## 1. Preparação

- Use dados fictícios: uma conta deve comprar e vender, outra fornece anúncios e uma conta administrativa aprova solicitações. Teste também vendedor pendente e suspenso.
- Registre branch e commit com `git branch --show-current` e `git rev-parse --short HEAD`.
- Os testes de feature usam `RefreshDatabase`: nunca aponte para desenvolvimento compartilhado ou produção.
- Registre cenários como **PASSOU**, **FALHOU**, **BLOQUEADO** ou **NÃO EXECUTADO**. Funcionalidade ausente não é sucesso presumido.

## 2. Testes automatizados no estado atual

O `phpunit.xml` versionado configura bootstrap, as suítes `Unit` e `Feature` e SQLite em memória. Os comandos abaixo também permitem executar os testes em um terminal isolado, sem reutilizar a configuração do banco de desenvolvimento.

### GitHub Actions

O workflow [Testes PHP](../.github/workflows/php-tests.yml) executa `php artisan test` em PHP 8.2 e 8.3 a cada push e pull request. Ele também permite execução manual pela aba **Actions → Testes PHP → Run workflow**, depois que o arquivo estiver na branch padrão.

Cada execução audita e instala as dependências do `composer.lock`, valida os requisitos do PHP, verifica a formatação PSR-12 com Pint e prepara um ambiente novo com chave própria, SQLite em memória, cache/sessão em array e e-mails em array. Não são necessários secrets ou acesso ao Supabase. As migrations são executadas pelos testes que usam `RefreshDatabase`. A plataforma de resolução do Composer é PHP 8.2.0, compatível com o mínimo do projeto e com o Dockerfile; `composer check-platform-reqs` continua verificando o PHP e as extensões reais. A base usa Laravel 12, Pest 3 e PHPUnit 11; a atualização segue o [guia oficial do Laravel](https://laravel.com/docs/12.x/upgrade).

Os arquivos `*Test.php` em `tests/Unit` e `tests/Feature` são descobertos automaticamente; basta adicionar novos testes nessas pastas. Uma falha faz o check da versão correspondente falhar, com detalhes no log da etapa de testes. Um job independente com Node.js 22 instala o `package-lock.json` com `npm ci`, executa `npm test` e valida `npm run build`. Os roteiros manuais deste guia ficam fora do workflow.

Para executar a mesma suíte localmente, no terminal isolado preparado abaixo:

```powershell
php artisan test
```

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

### Resultados observados

Validação das correções em **29/10/2026**, na branch `João`, com PHP 8.3.33, Laravel 12.70.2, PHPUnit 11.5.56 e Node.js 24.19.0:

| Verificação | Resultado |
| :--- | :--- |
| `php artisan test --compact` | 118 testes aprovados, 602 assertions, SQLite em memória |
| `npm ci` / `npm test` | Instalação reproduzível e 18 testes JavaScript aprovados |
| `npm run build` | Manifest e bundles CSS/JS gerados em `public/build` |
| `composer validate --strict --no-check-publish` | Aprovado sem avisos |
| `composer check-platform-reqs` | Aprovado no PHP instalado |
| `composer audit --locked` | Nenhum aviso de vulnerabilidade encontrado |
| `php vendor/bin/pint --test` | Aprovado com o preset PSR-12 versionado |
| `git diff --check` | Aprovado |

O [run #30 do Actions](https://github.com/IgorMarcoli/TheMerchant/actions/runs/40928942542), no commit `1fb3709`, falhou em `Validar dependências` nos dois jobs (PHP 8.2 e 8.3). Os logs indicam lock desatualizado e aviso sobre a restrição exata `3.28` do adaptador S3; os testes não chegaram a executar.

O Composer 2.11.0 usado pelo runner inclui `config.policy` no cálculo do [hash do lockfile](https://github.com/composer/composer/blob/2.11.0/src/Composer/Package/Locker.php#L123). Por isso, o lock gerado com Composer 2.10.3 também precisava de ajuste. A configuração explícita da política foi removida para usar o [bloqueio de versões vulneráveis habilitado por padrão](https://getcomposer.org/doc/06-config.md#policy), e o lock foi regenerado com Composer 2.11.0, sem alterar novamente as versões dos pacotes. A validação estrita agora passa em 2.10.3 e 2.11.0; o workflow fixa a versão 2.11.0.

Revalidação local do pipeline: actionlint 1.7.12 sem erros, Composer 2.11.0 com validação/auditoria/requisitos aprovados e instalação em dry-run sem alterações, 118 testes PHP/602 assertions, 18 testes JavaScript e build Vite aprovados. A validação estrita permanece ativa no CI. A execução remota depende da publicação dessas correções.

Foram corrigidos o arquivo de configuração de e-mail incompleto, a dependência incompatível com PHP 8.2/8.3, o pipeline Vite sem configuração e a geração de URLs de imagens que ignorava o disco usado no upload. A atualização para Laravel 12/Pest 3 e os patches de CommonMark/Mime eliminam os avisos identificados pela auditoria PHP nesta data. A resolução de dependências volta a bloquear versões com avisos de segurança.

Os testes de imagens usam discos simulados e cobrem armazenamento público, S3 e URLs externas. Os testes de notificações usam o mailer em memória; SMTP, S3, gateway e banco Supabase reais não foram exercitados. O `.env` e o banco configurado para desenvolvimento foram preservados. A execução em PHP 8.2/Linux fica a cargo do CI; o layout segue usando CDN, conforme [o guia de execução e assets](EXECUCAO.md).

Validação da suíte para o CI em **05/10/2026**, com PHP 8.3.33 e SQLite em memória: `php artisan test` passou com **110 testes e 568 assertions**; `composer validate --strict --no-check-publish` e `composer check-platform-reqs` também passaram. A execução em PHP 8.2 e no runner Linux será verificada pelo próprio Actions após o envio do workflow.

O registro abaixo corresponde à revisão anterior de 18/09/2026:

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

- [x] Criar `database/factories/UserFactory.php` compatível com o schema.
- [x] Versionar configuração PHPUnit com bootstrap, suites e isolamento explícito.
- [ ] Revisar campos obrigatórios dos dados de teste de categorias/anúncios.
- [ ] Separar testes de dispatch (fila simulada) de processamento do job.
- [ ] Validar integração com worker real e banco exclusivo.
- [x] Após configurar a suíte, padronizar `php artisan test` no CI.

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

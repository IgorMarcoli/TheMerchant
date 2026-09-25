# Executar o TheMerchant localmente

[Voltar ao README](../README.md) · [Testes e validações](TESTES.md)

Guia da base inspecionada em 18/09/2026. Os comandos partem da raiz do repositório. Os exemplos principais usam PowerShell no Windows; comandos PHP/Composer são equivalentes em Linux/macOS.

## 1. Dependências

| Dependência | Uso |
| :--- | :--- |
| Git | Clonar e versionar |
| PHP 8.2+ | Executar a versão compatível com `composer.lock`; ambiente verificado: 8.2.33 |
| Composer 2 | Instalar dependências PHP |
| PostgreSQL / Supabase | Banco principal; conexão pelo Session pooler e schema `laravel` |
| Node.js/npm | Somente para trabalhar na futura integração de assets; o layout atual usa CDN |

O Supabase é configurado como banco PostgreSQL do Laravel. O diagnóstico `php artisan test:db --write` verifica leitura e gravação com rollback. SQLite em memória continua sendo usado nos testes isolados. Veja [o guia de Supabase](SUPABASE.md).

Verifique o terminal:

```powershell
git --version
php --version
php --ini
php -m
composer --version
```

PHP precisa das extensões exigidas pelo Composer e de `pdo_pgsql` para Supabase; `pdo_sqlite` é necessária para os testes isolados. Confira também `fileinfo`, `mbstring`, `openssl`, `dom`, `xml` e `xmlwriter`. Se houver mais de um PHP instalado, use `Get-Command php` no PowerShell para identificar o executável.

## 2. Obter código e dependências

```powershell
git clone https://github.com/IgorMarcoli/TheMerchant.git
cd TheMerchant
composer install
composer check-platform-reqs
```

Em clone já existente, entre na pasta e confira branch/alterações com `git status`. Use `composer install` para respeitar o lockfile; `composer update` altera a resolução de dependências e não faz parte da instalação normal.

O projeto não versiona um arquivo Compose. A dependência Laravel Sail, sozinha, não oferece um ambiente Docker pronto.

## 3. Banco local e arquivo de ambiente

No Supabase, obtenha os dados em **Connect → Session pooler**. Para uma instalação nova, crie o schema no SQL Editor:

```sql
CREATE SCHEMA IF NOT EXISTS laravel;
```

Use as credenciais do banco do seu projeto Supabase. Crie o `.env` sem sobrescrever uma configuração existente:

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

Em Linux/macOS, a alternativa é:

```bash
test -f .env || cp .env.example .env
```

Edite estes valores, substituindo usuário/senha pelas suas credenciais:

```dotenv
APP_NAME="TheMerchant"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=host_do_session_pooler
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.referencia_do_projeto
DB_PASSWORD="senha_do_banco"
DB_SCHEMA=laravel
DB_SSLMODE=require

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
MAIL_MAILER=log
BROADCAST_CONNECTION=log
```

O `.env.example` usa sessão, cache e fila em banco; as migrations auxiliares de `sessions`, `cache` e `jobs` estão versionadas. O perfil acima permite iniciar com arquivos locais e jobs síncronos. A conexão está definida em `config/database.php`.

Não coloque credenciais reais de pagamento neste primeiro fluxo: `PaymentGatewayService` ainda simula a preferência. Preencher variáveis Mercado Pago/Stripe não implementa a integração.

```powershell
php artisan config:clear
php artisan key:generate
php artisan migrate
php artisan migrate:status
```

Gere `APP_KEY` apenas no primeiro preparo de um ambiente novo. Não troque uma chave de ambiente com dados criptografados/sessões a preservar. Se `migrate` falhar, resolva a conexão antes de continuar.

## 4. Dados de demonstração e imagens

Em banco recém-migrado e vazio:

```powershell
php artisan db:seed
php artisan storage:link
```

O seeder usa `create()` e e-mails/slugs fixos; rodá-lo novamente pode gerar erro de unicidade. Ele cria estas contas fictícias:

| Papel | E-mail | Senha local |
| :--- | :--- | :--- |
| Administrador | admin@themerchant.local | admin123456 |
| Vendedor | vendedor@themerchant.local | vendedor123456 |
| Comprador | comprador@themerchant.local | comprador123456 |

Essas credenciais são exclusivas de demonstração. A reputação e os contadores do vendedor também são dados fictícios, não avaliações produzidas por compras reais.

O seeder mantém jogos, categorias e contas de demonstração, mas não cria anúncios nem imagens de produtos. O catálogo inicia vazio para que os itens sejam cadastrados manualmente, inclusive após a migração para outro banco.

As capas de jogos referenciadas no seeder podem ser disponibilizadas em `storage/app/public/games/` (`cs2.jpg`, `valorant.jpg`, `lol.jpg`, `dota2.jpg`). A ligação `public/storage` precisa existir para imagens enviadas pelo cadastro de anúncios.

Para reconstruir **somente um banco local descartável**, confira primeiro `DB_DATABASE`. O comando abaixo apaga todas as tabelas desse banco:

```powershell
php artisan migrate:fresh --seed
```

Não execute em produção ou em base compartilhada com dados a preservar.

## 5. Iniciar a aplicação

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Mantenha esse terminal aberto e use outro para comandos. Encerre com `Ctrl+C`.

| Endereço | Finalidade |
| :--- | :--- |
| http://127.0.0.1:8000/up | Verificação de inicialização; não homologa banco/pagamento |
| http://127.0.0.1:8000/ | Retorna `Hello World` no estado atual |
| http://127.0.0.1:8000/anuncios | Catálogo |
| http://127.0.0.1:8000/login | Login |
| http://127.0.0.1:8000/cadastro | Cadastro |
| http://127.0.0.1:8000/perfil | Perfil autenticado |
| http://127.0.0.1:8000/carrinho | Carrinho |
| http://127.0.0.1:8000/pedidos | Pedidos |
| http://127.0.0.1:8000/vendedor/anuncios | Anúncios do vendedor |
| http://127.0.0.1:8000/vendedor/vendas | Vendas |
| http://127.0.0.1:8000/admin/dashboard | Painel administrativo |

A existência de uma rota não comprova que todos os seus cenários funcionam. Confira permissões e fluxos com [TESTES.md](TESTES.md).

Mantenha o servidor vinculado a `127.0.0.1`: há uma pendência conhecida de exposição de diagnósticos na [#28](https://github.com/IgorMarcoli/TheMerchant/issues/28).

## 6. Frontend hoje e evolução para Vite

O layout `resources/views/layouts/app.blade.php` carrega Tailwind e Alpine por CDN e fontes externas. Uma página sem estilo pode indicar ausência de internet ou bloqueio do CDN.

`package.json` contém `npm run dev` e `npm run build`, mas não existe `vite.config.*` versionado, e o layout não utiliza `@vite`. Por isso, esses comandos não são pré-requisito para executar a interface atual nem são apresentados como build homologado.

Ao implementar assets locais: adicionar configuração Vite/Laravel, entradas CSS/JS, Tailwind/PostCSS e `@vite`; substituir os CDNs evitando carregar Alpine duas vezes. Então instalar com `npm install`, versionar o lockfile e verificar `npm run build`. Depois de existir lockfile, usar `npm ci` em instalações reproduzíveis.

## 7. Filas, e-mails e chat

**Autenticação RF03:** verificação de e-mail e recuperação de senha usam o Laravel, com envio em fila. Consulte [AUTENTICACAO.md](AUTENTICACAO.md) para configurar SMTP, worker e validar os fluxos. `MAIL_MAILER=log` não entrega e-mail real; `QUEUE_CONNECTION=sync` executa imediatamente e deve ficar restrito a testes/desenvolvimento.

**Validar fila real:** as migrations de `jobs` e `failed_jobs` estão versionadas. Execute:

```powershell
php artisan migrate
```

Definir `QUEUE_CONNECTION=database` no `.env` e executar:

```powershell
php artisan config:clear
php artisan queue:work --tries=3 --timeout=60
```

Mantenha o worker em outro terminal e reinicie após alterar código/configuração. Para diagnóstico:

```powershell
php artisan queue:failed
```

Antes de repetir um job, confirme que a operação é idempotente e que a causa foi corrigida. Sessão/cache em banco também exigem migrations próprias; não são necessárias com os drivers `file`.

**Chat:** Reverb/Echo, canais privados e tabelas ainda serão implementados nas #30–#32. Não há comando de chat pronto para executar nesta revisão; o guia deverá incorporar inicialização do Reverb após essa entrega.

## 8. Problemas frequentes

| Sintoma | Verificação/ação |
| :--- | :--- |
| `php` ou `composer` não reconhecido | Corrigir PATH e abrir novo terminal; identificar PHP com `Get-Command php` |
| `vendor/autoload.php` ausente | Executar `composer install` |
| `No application encryption key` | Preparar `.env` e gerar chave do ambiente novo |
| `could not find driver` | Habilitar `pdo_pgsql` (Supabase) ou `pdo_sqlite` (testes) no PHP CLI mostrado por `php --ini` |
| `Connection refused` / `Access denied` | Conferir MySQL, porta, credenciais e permissão no banco |
| Tabela `sessions`, `cache` ou `jobs` ausente | Executar as migrations auxiliares existentes |
| E-mail/slug duplicado no seed | Seeder já foi executado; não repetir sem necessidade |
| Erro 419 em formulário | Conferir sessão/cookies, `@csrf`, URL e consistência entre localhost/127.0.0.1 |
| Configuração antiga | `php artisan config:clear`; reiniciar servidor/worker |
| Imagem quebrada | Conferir arquivo físico e `public/storage`; no Windows, checar permissão para links simbólicos |
| Página inicial mostra Hello World | Comportamento atual; acessar `/anuncios` |
| Pagamento volta diretamente ao site | Preferência local simulada; não houve cobrança real |
| Teste acusa UserFactory ausente | Bloqueio conhecido; ver guia de testes |

Logs locais:

```powershell
Get-Content storage/logs/laravel.log -Tail 80
```

Revise dados sensíveis antes de compartilhar logs. Use `php artisan test:db --write`: o diagnóstico suporta PostgreSQL e SQLite, sanitiza falhas e retorna código diferente de zero em caso de erro. Diagnósticos e consultas genéricas de tabelas por HTTP foram removidos; não há opção de reativá-los por parâmetros da requisição. Em publicação, configure `APP_DEBUG=false` e `APP_ENV=production`.

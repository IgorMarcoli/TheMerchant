# 🚀 Guia de Deploy no Render — TheMerchant

Este guia orienta a publicação do **TheMerchant** como **Web Service** no [Render.com](https://render.com).

---

## 1. Como Criar o Web Service no Render

1. Acesse o painel do **Render** ([dashboard.render.com](https://dashboard.render.com));
2. Clique no botão **New +** no topo e selecione **Web Service**;
3. Conecte sua conta do GitHub e selecione o repositório `JoaoPMA23/TheMerchant`;
4. Configure os parâmetros básicos:
   - **Name:** `themerchant` (ou o nome desejado);
   - **Region:** `Ohio (US East)` ou `Oregon (US West)`;
   - **Branch:** `main` (ou `João`);
   - **Runtime:** Escolha **Docker**;
   - O Render detectará automaticamente o `Dockerfile` na raiz do projeto.

---

## 2. Variáveis de Ambiente Obrigatórias no Render

Na aba **Environment** do serviço no Render, adicione as seguintes variáveis:

| Variável | Valor Recomendado | Observação |
| :--- | :--- | :--- |
| `APP_NAME` | `TheMerchant` | Nome da aplicação |
| `APP_ENV` | `production` | Ambiente de produção |
| `APP_DEBUG` | `false` | Desabilita mensagens de debug em produção |
| `APP_KEY` | *(Gerar uma chave)* | Execute localmente `php artisan key:generate --show` e cole aqui |
| `APP_URL` | `https://themerchant.onrender.com` | Substitua pelo domínio real fornecido pelo Render |
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso horário |
| `LOG_CHANNEL` | `stderr` | Envia logs diretamente para o console do Render |
| `SESSION_DRIVER` | `file` | Sessões locais em arquivo (ou `database`) |
| `CACHE_STORE` | `file` | Cache local em arquivo (ou `database`) |
| `QUEUE_CONNECTION` | `sync` | Processamento síncrono inicial |
| `FILESYSTEM_DISK` | `public` | Armazenamento de uploads |
| `RUN_MIGRATIONS` | `true` | Executa `php artisan migrate --force` automaticamente ao iniciar |

---

## 3. Configuração do Banco de Dados

Caso vá utilizar um banco de dados externo (como Aiven, PlanetScale, Supabase ou instância remota do MySQL):

| Variável | Descrição |
| :--- | :--- |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Host fornecido pelo provedor do banco |
| `DB_PORT` | Porta (geralmente `3306`) |
| `DB_DATABASE` | Nome do banco |
| `DB_USERNAME` | Usuário |
| `DB_PASSWORD` | Senha |

> 💡 **Dica:** Se desejar testar a aplicação em homologação temporária sem configurar MySQL externo imediatamente, você pode definir:
> - `DB_CONNECTION=sqlite`
> - `DB_DATABASE=/var/www/html/database/database.sqlite`

---

## 4. Health Check

- Na seção **Advanced** do Web Service no Render:
  - **Health Check Path:** `/up` (Endpoint nativo do Laravel 11 para verificação de status).

---

## 5. Como Funciona a Inicialização (Entrypoint)

O arquivo `docker/entrypoint.sh`:
1. Vincula o Apache dinamicamente à porta atribuída pelo Render via variável `$PORT`;
2. Cria o link simbólico `storage/` para disponibilizar imagens estáticas;
3. Otimiza a aplicação gerando cache de rotas e configurações para máxima performance;
4. Executa as migrations (`php artisan migrate --force`) de forma transparente se `RUN_MIGRATIONS=true`.

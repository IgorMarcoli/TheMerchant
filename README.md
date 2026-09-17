# 🎮 TheMerchant — Marketplace de Cosméticos e Serviços para Jogos Digitais

> **Documento de Especificação e Arquitetura** da disciplina de **Laboratório de Engenharia de Software III (LES III 2026)** — FATEC PG.  
> Plataforma web de marketplace para intermediação segura de cosméticos (skins, temas, avatares) e serviços digitais (coaching) para jogos, construída em arquitetura monolítica modular com **PHP/Laravel**, **Blade**, **Tailwind CSS** e **Alpine.js**.

[![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)]()
[![Laravel](https://img.shields.io/badge/Laravel-11%20%2F%2012-FF2D20?logo=laravel&logoColor=white)]()
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)]()
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?logo=tailwind-css&logoColor=white)]()
[![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?logo=alpinedotjs&logoColor=white)]()
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?logo=postgresql&logoColor=white)]()
[![MySQL](https://img.shields.io/badge/MySQL-00758F?logo=mysql&logoColor=white)]()
[![License](https://img.shields.io/badge/license-MIT-blue)]()

---

## 👥 Membros da Equipe

| Nome Completo | Matrícula / Função | GitHub |
| :--- | :--- | :--- |
| **Igor Marcoli Bastos** | Desenvolvedor Full-Stack | [@IgorMarcoli](https://github.com/IgorMarcoli) |
| **João Pedro Martins de Andrade** | Desenvolvedor Full-Stack | [@JoaoPMA23](https://github.com/JoaoPMA23) |

> 📌 *Este repositório e a documentação na raiz atendem às especificações e entregas do Laboratório de Engenharia de Software III (2026), documentando o escopo, arquitetura, requisitos e modelo de dados.*

---

## 📋 Sobre o Projeto e Justificativa

O mercado de jogos digitais expandiu de forma vertiginosa a demanda por **itens cosméticos** (como *skins*, temas visuais, pacotes gráficos e avatares) e **serviços associados** (como *coaching* e personalizações autorizadas). 

Atualmente, grande parte dessas negociações acontece na informalidade — por meio de grupos de redes sociais, fóruns e mensageiros instantâneos. Nesses ambientes, os usuários enfrentam:
- **Ausência de padronização** nas ofertas e nos pagamentos;
- **Baixa rastreabilidade** das transações;
- **Alta vulnerabilidade** a golpes, fraudes, falsidade ideológica e chargebacks;
- **Inexistência de mecanismos de reputação e moderação** auditáveis.

O **TheMerchant** surge como uma plataforma web centralizada para intermediar transações entre compradores e vendedores com transparência, reputação verificada, integração segura com gateways de pagamento externos e moderação administrativa.

> ⚠️ **Observação de Escopo e Conformidade:** Itens e serviços somente poderão ser anunciados quando forem estritamente compatíveis com as políticas, termos de uso e restrições da publicadora do jogo ou plataforma correspondente.

---

## 🎯 Objetivos

### 4.1 Objetivo Geral
Desenvolver uma plataforma web de marketplace que permita anunciar, pesquisar, comprar e acompanhar produtos e serviços digitais relacionados a jogos, intermediando as negociações com segurança, rastreabilidade e controle administrativo.

### 4.2 Objetivos Específicos
- 🔐 Permitir cadastro, autenticação, recuperação de acesso e controle de perfis de usuário (*RBAC*).
- 📦 Possibilitar que vendedores cadastrem, editem, pausem e removam anúncios de produtos e serviços digitais com galeria de imagens.
- 🔍 Permitir pesquisa e filtragem estruturada por jogo, categoria, faixa de preço e avaliação do vendedor.
- 🛒 Disponibilizar carrinho de compras e fluxo seguro de checkout.
- 💳 Integrar a aplicação a um gateway de pagamento externo sem armazenar dados sensíveis de cartão na plataforma.
- 📈 Permitir o acompanhamento completo do status de pedidos (comprador) e vendas (vendedor).
- ⭐ Implementar avaliação e reputação de vendedores somente após uma compra efetivamente concluída.
- 🛡️ Disponibilizar painel administrativo para moderação de denúncias, anúncios, categorias e usuários.
- 🏛️ Construir a solução sobre uma arquitetura organizada, modular e de fácil manutenção utilizando PHP 8.2+ e o ecossistema Laravel.

---

## 👤 Perfis de Usuário

| Perfil | Responsabilidades Principais |
| :--- | :--- |
| **Comprador** | Pesquisa produtos e serviços, filtra por jogo/categoria, adiciona itens ao carrinho, realiza checkout via gateway externo, acompanha o status de seus pedidos e avalia o vendedor após a entrega. |
| **Vendedor** | Mantém perfil comercial, publica e faz a gestão dos seus anúncios (ativar, pausar, editar fotos/preço), acompanha pedidos das suas vendas e acumula reputação verificada. |
| **Administrador** | Gerencia catálogo de jogos e categorias, modera denúncias, suspende anúncios irregulares, audita transações e gerencia contas de usuários. |

---

## 📊 Requisitos do Sistema

### Requisitos Funcionais (RF)

| ID | Prioridade | Descrição |
| :--- | :---: | :--- |
| **RF01** | Alta | O sistema deve permitir o cadastro de usuários com nome, e-mail e senha. |
| **RF02** | Alta | O sistema deve permitir login e logout de usuários cadastrados. |
| **RF03** | Alta | O sistema deve permitir verificação de e-mail e recuperação de senha. |
| **RF04** | Alta | O sistema deve aplicar permissões conforme o perfil do usuário: comprador, vendedor ou administrador. |
| **RF05** | Alta | O vendedor deve poder cadastrar, editar, pausar e remover seus próprios anúncios. |
| **RF06** | Alta | Cada anúncio deve permitir informar jogo, categoria, título, descrição, preço, imagens e status. |
| **RF07** | Alta | O comprador deve poder pesquisar, filtrar e ordenar anúncios por jogo, categoria e faixa de preço. |
| **RF08** | Alta | O sistema deve exibir uma página de detalhes do anúncio com informações do vendedor e avaliação. |
| **RF09** | Alta | O sistema deve disponibilizar carrinho de compras para o comprador. |
| **RF10** | Alta | O sistema deve permitir finalizar a compra por meio de checkout integrado a um gateway de pagamento. |
| **RF11** | Alta | O sistema deve registrar e atualizar o status do pagamento e do pedido, inclusive por notificações/webhooks do gateway. |
| **RF12** | Alta | O comprador deve poder acompanhar o histórico e o status de seus pedidos. |
| **RF13** | Média | O vendedor deve poder acompanhar as vendas relacionadas aos seus anúncios. |
| **RF14** | Média | O comprador deve poder avaliar o vendedor após a conclusão da compra. |
| **RF15** | Alta | O administrador deve poder gerenciar usuários, categorias, anúncios e denúncias. |

### Requisitos Não Funcionais (RNF)

| ID | Categoria | Descrição |
| :--- | :--- | :--- |
| **RNF01** | Usabilidade | A aplicação deve ser acessível por navegador web e possuir layout responsivo para desktop e dispositivos móveis. |
| **RNF02** | Segurança | As senhas devem ser armazenadas por hash seguro e irreversível (Bcrypt/Argon2id), nunca em texto puro ou criptografia reversível. |
| **RNF03** | Segurança | A aplicação deve aplicar autenticação, autorização por perfil (*Policies/Gates*), proteção CSRF e validação estrita em todas as operações sensíveis. |
| **RNF04** | Segurança | Dados sensíveis de cartão não devem ser armazenados pela plataforma; o processamento é 100% delegado ao gateway externo. |
| **RNF05** | Desempenho | Listagens e buscas devem responder em tempo adequado, com consultas paginadas e índices no banco de dados para campos de filtro frequentes. |
| **RNF06** | Integridade | Operações críticas de pedido e pagamento devem utilizar transações ACID (`DB::transaction`) no banco ao alterar múltiplos registros relacionados. |
| **RNF07** | Privacidade | Coletar apenas os dados necessários para a operação comercial, em conformidade com as boas práticas e legislação de privacidade (LGPD). |
| **RNF08** | Manutenibilidade | O código deve seguir organização em camadas (MVC + Services + Form Requests), padrões PSR-12, migrations versionadas e testes automatizados. |
| **RNF09** | Observabilidade | Falhas de integração, webhooks e erros críticos de sistema devem ser registrados em logs estruturados sem exposição de dados sensíveis. |

---

## 🚫 Limites de Escopo (Primeira Versão)

| Dentro do Escopo (v1) | Fora do Escopo (v1) |
| :--- | :--- |
| ✅ Aplicação web 100% responsiva (Desktop / Mobile) | ❌ Aplicativo mobile nativo (iOS / Android) |
| ✅ Catálogo, carrinho, checkout e webhook assíncrono | ❌ Integração direta com APIs oficiais de jogos (Steam, Riot) |
| ✅ Sistema de reputação e avaliações pós-compra | ❌ Chat em tempo real entre comprador e vendedor |
| ✅ Painel administrativo de moderação e denúncias | ❌ Múltiplos idiomas e múltiplas moedas (foco BRL) |
| ✅ Gateway externo (Mercado Pago / Stripe / PagSeguro) | ❌ Carteira financeira própria ou custódia interna de valores |

---

## 🛠️ Stack Tecnológica

| Camada / Função | Tecnologia | Descrição |
| :--- | :--- | :--- |
| **Linguagem & Back-end** | **PHP 8.2+ / Laravel 11/12** | Monólito modular baseado no padrão MVC do Laravel. |
| **Camada de Apresentação** | **Blade + Tailwind CSS + Alpine.js** | Renderização server-side rápida, estilização moderna e componentes interativos leves. |
| **Banco de Dados** | **PostgreSQL / MySQL** | Modelagem relacional estrita com migrations versionadas e Eloquent ORM. |
| **Autenticação & Autorização** | **Laravel Sessions & Policies** | Sessões seguras com cookies HTTP-only, CSRF e Laravel Policies/Gates. |
| **Integração de Pagamentos** | **Mercado Pago / Stripe / PagSeguro** | Camada de serviço desacoplada (`PaymentGatewayService`) com suporte a webhooks assíncronos e idempotência. |
| **Armazenamento de Arquivos** | **Laravel Storage (Disk Local / S3)** | Armazenamento de imagens de anúncios e perfis com links simbólicos públicos. |
| **Filas & Tarefas Assíncronas** | **Laravel Queues / Jobs** | Processamento em segundo plano para envio de e-mails, confirmação de webhooks e logs de auditoria. |
| **Testes Automatizados** | **Pest / PHPUnit** | Cobertura de testes unitários para Services e testes de feature para fluxos HTTP críticos. |
| **Versionamento & Ambiente** | **Git, GitHub & Docker (Sail)** | Padronização do ambiente de desenvolvimento e controle de versões via branches. |

---

## 🏛️ Arquitetura da Aplicação

A solução adota uma **arquitetura monolítica modular baseada em camadas no Laravel**:

```mermaid
flowchart TD
    subgraph Client["Cliente (Navegador)"]
        Browser["Desktop / Mobile (Navegador)"]
    end

    subgraph Presentation["Camada de Apresentação"]
        Blade["Blade Views"]
        Tailwind["Tailwind CSS + Alpine.js"]
    end

    subgraph HttpLayer["Camada HTTP / Aplicação"]
        Routes["Rotas (web.php)"]
        Middleware["Middlewares (Auth, Role, CSRF)"]
        Controllers["Controllers (Admin, Buyer, Seller)"]
        Requests["Form Requests (Validação)"]
    end

    subgraph BusinessLayer["Camada de Regras de Negócio"]
        Services["Services (CheckoutService, PaymentGatewayService)"]
        Actions["Actions & Jobs Assíncronos"]
        Policies["Policies & Gates (Autorização RBAC)"]
    end

    subgraph PersistenceLayer["Camada de Persistência"]
        Models["Models Eloquent"]
        DB[(Banco de Dados PostgreSQL / MySQL)]
    end

    subgraph ExternalInfra["Infraestrutura & Integrações Externas"]
        Gateway["Gateway de Pagamento (Mercado Pago / Stripe)"]
        Storage["Storage de Imagens"]
        MailService["Servidor de E-mail / Notificações"]
    end

    Browser -->|HTTPS| Routes
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Requests
    Controllers --> Services
    Services --> Policies
    Services --> Actions
    Services --> Models
    Models --> DB
    Services -->|API / Webhook| Gateway
    Services --> Storage
    Actions --> MailService
    Controllers --> Blade
    Blade --> Tailwind
    Tailwind --> Browser
```

### Diagrama de Casos de Uso

```mermaid
flowchart LR
    Buyer((Comprador))
    Seller((Vendedor))
    Admin((Administrador))
    Gateway[Gateway Externo]

    subgraph TheMerchant["Plataforma TheMerchant"]
        UC1[Cadastrar-se / Autenticar]
        UC2[Pesquisar e Filtrar Anúncios]
        UC3[Visualizar Detalhes do Anúncio]
        UC4[Gerenciar Carrinho]
        UC5[Finalizar Compra / Checkout]
        UC6[Processar Pagamento]
        UC7[Acompanhar Pedidos]
        UC8[Avaliar Vendedor]
        
        UC9[Gerenciar Anúncios CRUD]
        UC10[Acompanhar Vendas]
        
        UC11[Gerenciar Usuários e Categorias]
        UC12[Moderar Anúncios e Denúncias]
    end

    Buyer --> UC1
    Buyer --> UC2
    Buyer --> UC3
    Buyer --> UC4
    Buyer --> UC5
    UC5 -.->|inclui| UC6
    UC6 <-->|API / Webhook| Gateway
    Buyer --> UC7
    Buyer --> UC8

    Seller --> UC1
    Seller --> UC9
    Seller --> UC10

    Admin --> UC11
    Admin --> UC12
```

---

## 🗄️ Modelo de Dados Preliminar

A modelagem de dados foi projetada para garantir integridade referencial, rastreabilidade contábil e histórico imutável de transações.

| Entidade | Tabela | Finalidade |
| :--- | :--- | :--- |
| **User** | `users` | Dados de autenticação, status (ativo/suspenso) e papel do usuário (`buyer`, `seller`, `admin`). |
| **SellerProfile** | `seller_profiles` | Dados específicos de vendedor, bio, pontuação agregada de reputação e total de vendas. |
| **Game** | `games` | Catálogo de jogos suportados (ex: CS2, Valorant, League of Legends, Dota 2). |
| **Category** | `categories` | Categorias vinculadas a itens (ex: Skins de Arma, Facas, Coaching, Luvas). |
| **Listing** | `listings` | Anúncios de produtos ou serviços com título, descrição, preço e status. |
| **ListingImage** | `listing_images` | Galeria de imagens de cada anúncio, com flag de imagem principal. |
| **Cart** | `carts` | Carrinho de compras ativo do comprador (persistido por usuário ou sessão). |
| **CartItem** | `cart_items` | Itens e quantidades adicionados ao carrinho antes do checkout. |
| **Order** | `orders` | Pedido consolidado contendo valor total, comprador e status do fluxo de entrega. |
| **OrderItem** | `order_items` | Itens adquiridos no pedido, **preservando o preço histórico praticado** no momento da compra. |
| **Payment** | `payments` | Registro das tentativas de pagamento, transação externa do gateway, status e payload. |
| **Review** | `reviews` | Avaliações com nota (1 a 5) e comentário, vinculadas estritamente a uma compra concluída. |
| **Report** | `reports` | Denúncias de anúncios ou comportamentos irregulares enviadas para moderação administrativa. |

### Diagrama de Relacionamentos (ERD)

```mermaid
erDiagram
    USERS ||--o| SELLER_PROFILES : "possui perfil"
    USERS ||--o{ LISTINGS : "anuncia (como vendedor)"
    USERS ||--o{ ORDERS : "realiza (como comprador)"
    USERS ||--o{ CARTS : "mantém"
    USERS ||--o{ REVIEWS : "escreve"
    USERS ||--o{ REPORTS : "registra denúncia"

    GAMES ||--o{ CATEGORIES : "possui"
    GAMES ||--o{ LISTINGS : "classifica"
    CATEGORIES ||--o{ LISTINGS : "categoriza"

    LISTINGS ||--o{ LISTING_IMAGES : "possui fotos"
    LISTINGS ||--o{ CART_ITEMS : "está presente em"
    LISTINGS ||--o{ ORDER_ITEMS : "referencia item"
    LISTINGS ||--o{ REPORTS : "pode receber denúncias"

    CARTS ||--o{ CART_ITEMS : "contém"

    ORDERS ||--o{ ORDER_ITEMS : "possui itens"
    ORDERS ||--o{ PAYMENTS : "gera registros de pagamento"
    ORDERS ||--o| REVIEWS : "permite avaliação pós-conclusão"
```

---

## 🔄 Ciclos de Vida e Regras de Negócio

### 1. Estados do Anúncio (`listings.status`)
- `rascunho`: Anúncio salvo pelo vendedor sem visibilidade pública no catálogo.
- `publicado`: Ativo e visível nas buscas e filtros.
- `pausado`: Temporariamente ocultado pelo vendedor.
- `vendido`: Item único já comercializado ou sem estoque disponível.
- `bloqueado`: Suspenso pela equipe de moderação administrativa por inconformidade com as regras.

### 2. Estados do Pedido (`orders.status`)
- `pendente`: Pedido gerado aguardando confirmação do gateway de pagamento.
- `pago`: Pagamento confirmado via webhook assíncrono.
- `em_andamento`: Vendedor notificado para liberação ou entrega do item/serviço digital.
- `concluido`: Entrega confirmada; habilita a avaliação do vendedor pelo comprador.
- `cancelado`: Pedido cancelado por expiração ou estorno.

---

## 🌐 Rotas Principais da Aplicação

| Método | Rota | Descrição | Acesso |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | Página inicial com destaques, jogos populares e busca rápida | Público |
| `GET` | `/anuncios` | Catálogo geral com paginação, filtros e ordenação | Público |
| `GET` | `/anuncios/{slug}` | Detalhes do anúncio, galeria de fotos e dados do vendedor | Público |
| `GET/POST` | `/carrinho` | Visualização e manipulação do carrinho de compras | Comprador |
| `GET/POST` | `/checkout` | Resumo da compra e redirecionamento para o gateway | Comprador |
| `POST` | `/api/webhooks/payment` | Recebimento assíncrono de notificações de pagamento do gateway | Externo / Assíncrono |
| `GET` | `/pedidos` | Histórico e rastreamento dos pedidos realizados | Comprador |
| `POST` | `/pedidos/{id}/avaliar` | Envio de avaliação e nota para o vendedor | Comprador |
| `GET/POST` | `/vendedor/anuncios` | Painel do vendedor para CRUD e gestão de anúncios | Vendedor |
| `GET` | `/vendedor/vendas` | Acompanhamento de vendas recebidas e status de entrega | Vendedor |
| `GET` | `/admin/dashboard` | Métricas gerais de faturamento, usuários e volume de anúncios | Administrador |
| `GET/POST` | `/admin/denuncias` | Moderação de denúncias e suspensão de anúncios/usuários | Administrador |

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
- **PHP 8.2 ou superior** com extensões ativas (`pdo`, `pdo_pgsql` ou `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd` ou `imagick`, `fileinfo`);
- **Composer 2.x**;
- **Node.js 18+** e **npm**;
- Banco de dados **PostgreSQL** ou **MySQL** (ou Docker).

---

### Opção 1: Execução Local com PHP & Node

```bash
# 1. Clone o repositório
git clone https://github.com/IgorMarcoli/TheMerchant.git
cd TheMerchant

# 2. Instale as dependências do backend PHP
composer install

# 3. Configure as variáveis de ambiente
cp .env.example .env
php artisan key:generate

# 4. Ajuste as credenciais do banco em .env e execute as migrações com dados de teste
php artisan migrate --seed

# 5. Crie o link simbólico para o armazenamento público de imagens
php artisan storage:link

# 6. Instale e compile os assets do frontend (Tailwind CSS / Vite)
npm install
npm run build   # ou 'npm run dev' para hot-reload em desenvolvimento

# 7. Inicie o servidor local da aplicação
php artisan serve
```

Acesse no navegador: **`http://localhost:8000`**

---

### Opção 2: Execução com Docker / Laravel Sail

```bash
# 1. Suba os containers do projeto (App, Banco de Dados, Redis/Mailpit se configurados)
./vendor/bin/sail up -d

# 2. Execute as migrações e seeds
./vendor/bin/sail artisan migrate --seed

# 3. Compile os assets do frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

---

## 📁 Estrutura de Pastas do Projeto

```
TheMerchant/
├── .env.example                     # Exemplo de configurações de ambiente
├── .gitignore                       # Regras de exclusão do Git
├── CONTRIBUTING.md                  # Guia de contribuição, Git Flow e padrões de código
├── README.md                        # Documento principal do projeto
├── agents.md                        # Diretrizes para automação e agentes de IA
├── requirements.md                  # Especificação formal de requisitos (RF / RNF)
├── specs.md                         # Especificações técnicas, ERD detalhado e rotas
├── composer.json                    # Dependências do ecossistema PHP/Laravel
├── package.json                     # Dependências do ecossistema Node/Tailwind/Vite
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/               # Controladores do painel administrativo
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   └── UserController.php
│   │   │   ├── Buyer/               # Controladores da jornada do comprador
│   │   │   │   ├── CartController.php
│   │   │   │   ├── CheckoutController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── ReviewController.php
│   │   │   ├── Seller/              # Controladores do painel do vendedor
│   │   │   │   ├── ListingController.php
│   │   │   │   └── SaleController.php
│   │   │   ├── Auth/                # Autenticação e controle de sessões
│   │   │   ├── ListingPublicController.php
│   │   │   ├── WebhookController.php
│   │   │   └── Controller.php
│   │   └── Requests/                # Form Requests com validações de entrada
│   │       ├── CheckoutRequest.php
│   │       └── ListingStoreRequest.php
│   ├── Models/                      # Entidades Eloquent ORM
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Category.php
│   │   ├── Game.php
│   │   ├── Listing.php
│   │   ├── ListingImage.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Payment.php
│   │   ├── Report.php
│   │   ├── Review.php
│   │   ├── SellerProfile.php
│   │   └── User.php
│   ├── Policies/                    # Regras de autorização por perfil (RBAC)
│   │   ├── ListingPolicy.php
│   │   └── OrderPolicy.php
│   ├── Services/                    # Camada de serviços e regras de negócio
│   │   ├── CheckoutService.php
│   │   └── PaymentGatewayService.php
│   ├── Jobs/                        # Tarefas assíncronas (webhooks, e-mails)
│   │   └── ProcessPaymentWebhookJob.php
│   └── Notifications/               # Notificações do sistema
│       └── OrderPaidNotification.php
├── bootstrap/
│   └── app.php                      # Inicializador do kernel Laravel
├── database/
│   ├── migrations/                  # Versionamento de tabelas do banco de dados
│   │   ├── 2026_01_01_000001_create_users_table.php
│   │   ├── 2026_01_01_000002_create_seller_profiles_table.php
│   │   ├── 2026_01_01_000003_create_games_table.php
│   │   ├── 2026_01_01_000004_create_categories_table.php
│   │   ├── 2026_01_01_000005_create_listings_and_images_tables.php
│   │   ├── 2026_01_01_000006_create_carts_and_items_tables.php
│   │   ├── 2026_01_01_000007_create_orders_and_items_tables.php
│   │   ├── 2026_01_01_000008_create_payments_table.php
│   │   ├── 2026_01_01_000009_create_reviews_table.php
│   │   └── 2026_01_01_000010_create_reports_table.php
│   └── seeders/                     # Povoamento inicial para testes e demonstração
│       └── DatabaseSeeder.php
├── resources/
│   ├── views/                       # Templates Blade da interface
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   └── navigation.blade.php
│   │   ├── listings/
│   │   │   ├── index.blade.php
│   │   │   └── show.blade.php
│   │   ├── cart/
│   │   ├── checkout/
│   │   ├── orders/
│   │   ├── seller/
│   │   └── admin/
│   ├── css/
│   │   └── app.css                  # Folhas de estilo (Tailwind CSS)
│   └── js/
│       └── app.js                   # Scripts Alpine.js / Vite
├── routes/
│   ├── web.php                      # Rotas da interface web
│   ├── api.php                      # Endpoints de webhooks e integrações
│   └── console.php                  # Comandos Artisan customizados
└── tests/
    ├── Feature/                     # Testes de integração de fluxos HTTP
    │   ├── CheckoutTest.php
    │   ├── ListingTest.php
    │   └── WebhookPaymentTest.php
    └── Unit/                        # Testes unitários de serviços e regras
        └── CheckoutServiceTest.php
```

---

## 📑 Documentação Complementar

- 📋 [Requisitos Detalhados (`requirements.md`)](./requirements.md) — Matriz completa de Requisitos Funcionais, Não Funcionais e Regras de Negócio.
- 📐 [Especificações Técnicas & ERD (`specs.md`)](./specs.md) — Dicionário de dados, máquina de estados e contratos dos Services.
- 📌 [Planejamento de Sprints & Issues (`ISSUES.md`)](./ISSUES.md) — Backlog dividido em 5 Milestones com distribuição equilibrada de tarefas da equipe.
- 🤝 [Guia de Contribuição (`CONTRIBUTING.md`)](./CONTRIBUTING.md) — Diretrizes de Git Flow, branches e commits semânticos.
- 🤖 [Diretrizes para Agentes (`agents.md`)](./agents.md) — Contexto de desenvolvimento e padrões arquiteturais para automações e LLMs.

---

## 📄 Licença

Este projeto é desenvolvido para fins exclusivamente acadêmicos na FATEC Praia Grande sob a licença [MIT](./LICENSE).

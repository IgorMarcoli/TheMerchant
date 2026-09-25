# 📋 Requisitos e Regras de Negócio — TheMerchant

> **Documento de Engenharia de Requisitos**  
> **Disciplina:** Laboratório de Engenharia de Software III (LES III 2026) — FATEC PG  
> **Projeto:** TheMerchant (Marketplace de Cosméticos e Serviços para Jogos Digitais)  
> **Integrantes:** Igor Marcoli Bastos e João Pedro Martins de Andrade  

---

## 1. Visão Geral do Produto

O **TheMerchant** é uma plataforma web intermediadora para a compra e venda de itens cosméticos (skins, pacotes visuais, temas, avatares) e serviços digitais (como coaching de partidas) associados a jogos eletrônicos. Seu foco principal é sanar os problemas de informalidade enfrentados pela comunidade gamer em redes sociais e fóruns, fornecendo um ambiente estruturado com:
- Autenticação e perfis diferenciados (*Comprador*, *Vendedor*, *Administrador*);
- Anúncios categorizados e vinculados a jogos específicos;
- Carrinho de compras unificado e checkout transparente integrado a gateway externo;
- Atualização assíncrona de pagamentos via webhooks com proteção contra duplicidade;
- Reputação e avaliação condicionadas à compra efetivamente concluída;
- Painel de moderação para tratamento de denúncias e suspensão de itens irregulares.

---

## 2. Limites e Premissas de Escopo

### 2.1 Em Escopo (Primeira Versão)
1. Interface Web responsiva otimizada para desktops, tablets e smartphones.
2. Catálogo público com busca textual e filtros combinados (jogo, categoria, faixa de preço, ordenação por preço/avaliação).
3. Gestão completa de anúncios por vendedores credenciados (criação, edição, upload de fotos, pausa e remoção).
4. Carrinho de compras com retenção de sessão para compradores.
5. Checkout com delegação do processamento de pagamento a gateway externo (Mercado Pago, Stripe ou PagSeguro).
6. Confirmação assíncrona de pagamento via webhook e garantia de idempotência.
7. Rastreamento e acompanhamento de histórico de pedidos para o comprador e vendas para o vendedor.
8. Módulo de avaliação do vendedor (nota de 1 a 5 e comentário textual), restrito a pedidos com entrega concluída.
9. Mecanismo de denúncia de anúncios suspeitos e painel administrativo para moderação.
10. Painel administrativo para gerenciamento de usuários, categorias e jogos.

### 2.2 Fora do Escopo (Primeira Versão)
1. Aplicativo mobile nativo para iOS ou Android.
2. Integração direta via API interna com distribuidoras de jogos (Steam Web API, Riot Games API, Blizzard API, etc.).
3. Chat em tempo real (*WebSocket / Socket.io*) entre comprador e vendedor.
4. Múltiplos idiomas (i18n) e suporte a moedas estrangeiras (todas as operações em Real Brasileiro - BRL).
5. Carteira digital própria (*digital wallet*) ou custódia interna de saldo financeiro (todos os valores transitam pelo gateway externo).

---

## 3. Matriz de Requisitos Funcionais (RF)

| ID | Prioridade | Nome do Requisito | Descrição Detalhada | Critério de Aceitação |
| :--- | :---: | :--- | :--- | :--- |
| **RF01** | **Alta** | Cadastro de Conta por Visitante | O sistema deve permitir que um visitante cadastre uma conta informando, no mínimo, nome, e-mail e senha, com uma conta única apta a comprar e solicitar aprovação para vender. | E-mail validado quanto ao formato e unicidade; senha com no mínimo 8 caracteres; conta sem privilégios administrativos; solicitação de venda em `seller_profiles.status=pending`. |
| **RF02** | **Alta** | Autenticação e Sessão | O sistema deve permitir autenticação, logout e controle de sessão de usuários cadastrados. | Sessão com cookie seguro (`HttpOnly`, `SameSite=Lax`); logout invalida e regenera o token de sessão. |
| **RF03** | **Alta** | Verificação de E-mail e Recuperação | O sistema deve permitir verificação de e-mail e recuperação de acesso por meio de fluxo seguro. | Envio de token temporário criptografado com expiração de 60 minutos para o e-mail cadastrado. |
| **RF04** | **Alta** | Controle de Acesso e Permissões (RBAC) | Toda conta ativa pode comprar; vender exige perfil aprovado; administração depende de `users.is_admin`. | Bloqueio imediato via Middleware e Policies; contas sem perfil aprovado não criam anúncios; apenas administradores ativos acessam `/admin`. |
| **RF05** | **Alta** | Gestão de Anúncios pelo Vendedor | O vendedor deve poder cadastrar, editar, pausar e remover seus próprios anúncios. | Somente o criador do anúncio ou administrador pode alterá-lo/excluí-lo (assegurado por `ListingPolicy`). |
| **RF06** | **Alta** | Estrutura de Dados do Anúncio | Cada anúncio deve permitir informar jogo, categoria, título, descrição, preço, imagens e status. | Preço maior que zero (min: R$ 1,00); pelo menos 1 imagem principal obrigatória; status inicial `publicado` ou `rascunho`. |
| **RF07** | **Alta** | Pesquisa e Filtros de Catálogo | O usuário deve poder pesquisar, filtrar e ordenar anúncios por jogo, categoria e faixa de preço. | Filtros combinados preservados na query string com paginação e tempo de resposta < 500ms. |
| **RF08** | **Alta** | Página de Detalhes do Anúncio | O sistema deve exibir uma página de detalhes do anúncio com informações do produto ou serviço, vendedor e reputação disponível. | Carrossel de fotos, especificações, nota de reputação média e botão de adicionar ao carrinho contextual. |
| **RF09** | **Alta** | Gestão do Carrinho de Compras | O comprador deve poder gerenciar o carrinho, adicionando, removendo e ajustando itens antes da compra. | Persistência do carrinho por usuário; bloqueio de compra de anúncios próprios ou de anúncios pausados. |
| **RF10** | **Alta** | Finalização de Compra (Checkout) | O comprador deve poder finalizar a compra por meio do checkout, gerando o pedido correspondente. | Geração de registro imutável em `orders` e congelamento dos preços unitários praticados em `order_items`. |
| **RF11** | **Alta** | Processamento com Gateway Externo | A finalização da compra deve incluir o processamento do pagamento por meio de um gateway externo. | Redirecionamento ou checkout transparente com Mercado Pago, Stripe ou PagSeguro; nenhum dado de cartão salvo no banco. |
| **RF12** | **Alta** | Confirmação por Webhooks e Idempotência | O sistema deve receber notificações/webhooks do gateway e atualizar de forma idempotente os status do pagamento e do pedido. | Retorno imediato `200 OK`; processamento assíncrono via fila; trava contra notificações duplicadas por `idempotency_key`. |
| **RF13** | **Alta** | Histórico e Acompanhamento de Pedidos | O comprador deve poder acompanhar o histórico e o status de seus pedidos. | Listagem cronológica com status detalhado (Pendente, Pago, Em Entrega, Concluído, Cancelado). |
| **RF14** | **Média** | Acompanhamento de Vendas pelo Vendedor | O vendedor deve poder acompanhar as vendas relacionadas aos seus anúncios. | Painel de vendas com visualização dos itens a entregar e confirmação manual de envio do item/serviço. |
| **RF15** | **Média** | Avaliação do Vendedor Pós-Compra | O comprador deve poder avaliar o vendedor somente após a conclusão da compra. | Avaliação liberada por item pago e entregue; 1 avaliação por order_item_id, sem aguardar outros vendedores; recálculo dinâmico da reputação. |
| **RF16** | **Média** | Denúncia de Anúncios por Usuários | O usuário autenticado deve poder denunciar um anúncio, informando o motivo da denúncia. | Modal de envio rápido; registro em `reports` com status inicial `aberta`; restrito a usuários logados. |
| **RF17** | **Alta** | Gestão Administrativa de Usuários | O administrador deve poder gerenciar usuários, incluindo consulta, alteração de status e demais ações administrativas previstas pela plataforma. | Visualização de lista de usuários, filtros por papel e opção de suspensão de contas irregulares. |
| **RF18** | **Alta** | Gestão Administrativa de Categorias | O administrador deve poder gerenciar as categorias utilizadas para classificar anúncios. | Criação, edição e inativação de categorias vinculadas aos jogos suportados. |
| **RF19** | **Alta** | Moderação Administrativa de Anúncios | O administrador deve poder moderar anúncios, podendo ocultar, bloquear ou restaurar anúncios conforme as regras da plataforma. | Bloqueio de anúncios irregulares procedentes, removendo-os imediatamente do catálogo público. |
| **RF20** | **Alta** | Tratamento Administrativo de Denúncias | O administrador deve poder consultar e tratar denúncias registradas pelos usuários. | Fila de moderação com histórico, campo de parecer motivado e julgamento (procedente / improcedente). |

---

## 4. Matriz de Requisitos Não Funcionais (RNF)

| ID | Categoria | Descrição Técnica | Métrica / Validação |
| :--- | :--- | :--- | :--- |
| **RNF01** | **Usabilidade & Responsividade** | A aplicação deve ser desenvolvida em padrão mobile-first com Tailwind CSS, adaptando-se a resoluções de 320px a 4K. | Testes em viewports mobile (iPhone, Android) e navegadores modernos (Chrome, Firefox, Safari, Edge). |
| **RNF02** | **Criptografia de Senhas** | Nenhuma credencial deve ser persistida em texto claro; uso de algoritmo de hashing irreversível padrão do Laravel (Bcrypt com custo >= 12 ou Argon2id). | Auditoria no banco demonstrando hashes com salt automático. |
| **RNF03** | **Segurança da Aplicação** | Todas as requisições de alteração de estado (POST/PUT/DELETE) devem validar tokens CSRF; autorizações checadas por Laravel Policies. | Prevenção contra CSRF, SQL Injection (Eloquent preparado) e XSS (escape nativo do Blade `{{ }}`). |
| **RNF04** | **Conformidade PCI-DSS** | O sistema não deve transitar nem salvar números de cartão de crédito, CVV ou datas de expiração em seu banco de dados. | Pagamento mediado por Checkout transparente ou redirect do provedor (Mercado Pago / Stripe). |
| **RNF05** | **Desempenho e Paginação** | Consultas ao catálogo e painéis devem responder em até 500ms sob carga acadêmica típica, utilizando paginação e índices em chaves estrangeiras e status. | Índices compostos em `(game_id, category_id, status)` e `price`. |
| **RNF06** | **Integridade Transacional** | Fluxos de criação de pedido, baixa de estoque e registro de pagamento devem executar sob transações atômicas de banco (`DB::transaction`). | Garantia de que falhas no meio do processo realizem Rollback sem registros órfãos. |
| **RNF07** | **Privacidade de Dados** | Coleta restrita aos campos estritamente necessários para viabilizar a negociação; mascaramento de e-mails em avaliações públicas. | Nomes completos e contatos diretos não são expostos em áreas públicas. |
| **RNF08** | **Manutenibilidade e Padrões** | Código modular seguindo MVC estrito, camada de Services para regras de negócio, injeção de dependência e convenção PSR-12 / Laravel Pint. | Testes automatizados com Pest/PHPUnit cobrindo os serviços de checkout e webhook. |
| **RNF09** | **Observabilidade e Logs** | Registro de falhas críticas, retornos de webhook e erros 500 em arquivos de log diários (`storage/logs/laravel.log`) sem vazar dados confidenciais. | Log estruturado com identificador da requisição e motivo da falha. |

---

## 5. Regras de Negócio Críticas (RN)

1. **RN01 — Vinculação Obrigatória:** Todo anúncio cadastrado deve obrigatoriamente estar associado a um jogo ativo e a uma categoria válida cadastrada pelo administrador.
2. **RN02 — Preço Mínimo:** Anúncios não podem ter valor igual ou inferior a R$ 0,00. O valor mínimo é R$ 1,00.
3. **RN03 — Máquina de Estados do Anúncio:**
   - Transições válidas: `rascunho` ➔ `publicado` ➔ (`pausado`, `vendido`, `bloqueado`).
   - Anúncio com status `bloqueado` só pode ser desbloqueado por um administrador.
4. **RN04 — Congelamento de Preço no Pedido:** O preço praticado no momento da finalização do pedido é imutável e gravado na tabela `order_items`. Alterações posteriores no anúncio não impactam compras já efetuadas.
5. **RN05 — Condição de Avaliação:** O comprador pode avaliar cada item pago e entregue uma única vez por `order_item_id`, sem aguardar a entrega dos outros itens/vendedores. Serviço exige todas as sessões do item concluídas. Ver [contrato de domínio #29](docs/CONTRATO_DOMINIO.md).
6. **RN06 — Idempotência do Webhook:** Notificações de pagamento enviadas repetidas vezes pelo gateway devem ser verificadas pela coluna `idempotency_key` ou `transaction_id`. Se a transação já foi processada, retorna `200 OK` imediatamente sem reprocessar saldo ou status.
7. **RN07 — Denúncias por Usuários Autenticados:** Somente usuários autenticados no sistema poderão registrar denúncias de anúncios.
8. **RN08 — Acionamento Obrigatório de Gateway:** Toda finalização de compra deverá obrigatoriamente acionar o processamento do pagamento por meio do gateway externo.

---

## 6. Critérios de Modelagem UML Aplicados

Conforme a Seção 6.1 da documentação oficial:
- **Associações entre atores e casos de uso:** Representadas por linhas contínuas, sem pontas de seta.
- **Generalização de Atores:** Comprador, Vendedor e Administrador especializam o ator geral `Usuário` por meio de generalização (`herança`), compartilhando funcionalidades comuns (Autenticar-se, Pesquisar e filtrar anúncios, Visualizar anúncio e Denunciar anúncio).
- **Visitante:** Representa o usuário não autenticado que pode se cadastrar, autenticar-se e navegar pelo catálogo.
- **Relacionamento `<<include>>`:** O único relacionamento obrigatório entre casos de uso é `Finalizar compra <<include>> Processar pagamento`, pois toda finalização de compra exige a execução do processamento de pagamento.
- **Independência de Fluxos:** `Gerenciar carrinho` e `Finalizar compra` não possuem relacionamento direto no diagrama, pois representam uma sequência de fluxo e não uma relação estrutural de caso de uso.
- **Ações Administrativas Atômicas:** `Gerenciar usuários`, `Gerenciar categorias`, `Moderar anúncios` e `Tratar denúncias` são casos de uso separados para representar ações de gestão independentes.
- **Gateway de Pagamento como Ator Externo:** Participa ativamente dos casos `Processar pagamento` e `Atualizar status de pagamento/pedido`.

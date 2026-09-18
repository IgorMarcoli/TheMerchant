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
11. Chat privado de texto em tempo real com vendedor, antes e depois da compra, com histórico e mensagens não lidas (RF21–RF23).

### 2.2 Fora do Escopo (Primeira Versão)
1. Aplicativo mobile nativo para iOS ou Android.
2. Integração direta via API interna com distribuidoras de jogos (Steam Web API, Riot Games API, Blizzard API, etc.).
3. Anexos, áudio/vídeo, grupos e agenda automática de coaching; chat de texto em tempo real está incluído na v1 pela revisão de 18/09/2026.
4. Múltiplos idiomas (i18n) e suporte a moedas estrangeiras (todas as operações em Real Brasileiro - BRL).
5. Carteira digital própria (*digital wallet*) ou custódia interna de saldo financeiro (todos os valores transitam pelo gateway externo).

---

## 3. Matriz de Requisitos Funcionais (RF)

| ID | Prioridade | Nome do Requisito | Descrição Detalhada | Critério de Aceitação |
| :--- | :---: | :--- | :--- | :--- |
<<<<<<< Updated upstream
| **RF01** | **Alta** | Cadastro de Usuários | O sistema deve permitir o autocadastro de usuários fornecendo nome completo, endereço de e-mail único e senha segura. | E-mail deve ser validado quanto ao formato e unicidade; senha deve possuir no mínimo 8 caracteres com letras e números. |
| **RF02** | **Alta** | Autenticação e Sessão | O sistema deve permitir que usuários realizem login e logout utilizando e-mail e senha. | Criação de cookie seguro de sessão (`HttpOnly`, `SameSite=Lax`); logout deve invalidar e regenerar a sessão. |
| **RF03** | **Alta** | Recuperação de Senha e Verificação | O sistema deve permitir envio de token de recuperação de acesso por e-mail e verificação do e-mail cadastrado. | Token temporário com validade de 60 minutos enviado para o e-mail cadastrado. |
| **RF04** | **Alta** | Controle de Acesso e Perfis (RBAC) | O sistema deve diferenciar as permissões e telas conforme o papel do usuário: Comprador, Vendedor ou Administrador. | Usuário sem perfil de vendedor não pode publicar anúncios; rotas administrativas acessíveis estritamente por administradores via middleware. |
| **RF05** | **Alta** | Gestão de Anúncios pelo Vendedor | O vendedor deve poder cadastrar, editar dados, pausar, reativar e excluir seus anúncios. | Somente o criador do anúncio ou um administrador pode editar/excluir o anúncio (assegurado por Policy). |
| **RF06** | **Alta** | Estrutura de Dados do Anúncio | Cada anúncio deve conter: jogo associado, categoria, título, descrição, preço unitário, status e galeria de fotos. | O preço deve ser maior que zero; ao menos uma foto principal deve ser informada no cadastro. |
| **RF07** | **Alta** | Busca e Filtros de Catálogo | O comprador deve poder consultar anúncios com paginação e filtrar por jogo, categoria, faixa de preço mínimo/máximo e ordenação. | Filtros cumulativos mantidos na query string da URL com resposta rápida e paginação de 15 itens por página. |
| **RF08** | **Alta** | Página de Detalhes do Anúncio | O sistema deve apresentar a página detalhada do anúncio com carrossel de fotos, dados do vendedor, reputação média e botão de compra. | Se o anúncio estiver com status diferente de `publicado`, não deve permitir adição ao carrinho. |
| **RF09** | **Alta** | Carrinho de Compras | O sistema deve permitir adicionar anúncios ao carrinho, alterar quantidade (se aplicável), visualizar subtotal e remover itens. | Validação de disponibilidade de cada anúncio antes de prosseguir ao checkout. |
| **RF10** | **Alta** | Fluxo de Checkout | O sistema deve permitir fechar o pedido criando um registro imutável com os preços vigentes e gerar link/sessão de pagamento. | Valores dos itens são salvos na tabela de itens do pedido para proteção contra alterações posteriores de preço no anúncio. |
| **RF11** | **Alta** | Integração e Webhooks de Pagamento | O sistema deve receber confirmações do gateway externo via webhook e atualizar o status do pedido de forma idempotente. | O mesmo payload de webhook processado mais de uma vez não pode duplicar confirmações ou créditos. |
| **RF12** | **Alta** | Acompanhamento de Pedidos pelo Comprador | O comprador deve poder listar seus pedidos anteriores e acompanhar o status atual (Pendente, Pago, Concluído, Cancelado). | Visualização cronológica detalhada com número do pedido, data e itens. |
| **RF13** | **Média** | Acompanhamento de Vendas pelo Vendedor | O vendedor deve poder acompanhar as vendas originadas de seus anúncios e o status de entrega do item digital. | Painel dedicado com filtro por status e notificação de nova venda paga. |
| **RF14** | **Média** | Avaliação do Vendedor pós-compra | O comprador deve poder avaliar o vendedor com nota de 1 a 5 estrelas e comentário após a conclusão do pedido. | Cada pedido concluído permite apenas uma avaliação por item/vendedor; nota reflete na média pública do vendedor. |
| **RF15** | **Alta** | Painel Administrativo e Moderação | Administradores devem gerenciar categorias, jogos, usuários e moderar denúncias de anúncios irregulares. | Denúncias podem ser aceitas (ocultando o anúncio e advertindo o vendedor) ou rejeitadas com justificativa. |
=======
| **RF01** | **Alta** | Cadastro de Conta por Visitante | O sistema deve permitir que um visitante cadastre uma conta informando, no mínimo, nome, e-mail e senha, escolhendo o perfil de comprador ou vendedor quando aplicável. | E-mail validado quanto ao formato e unicidade; senha com no mínimo 8 caracteres; perfil gravado em `users.role`. |
| **RF02** | **Alta** | Autenticação e Sessão | O sistema deve permitir autenticação, logout e controle de sessão de usuários cadastrados. | Sessão com cookie seguro (`HttpOnly`, `SameSite=Lax`); logout invalida e regenera o token de sessão. |
| **RF03** | **Alta** | Verificação de E-mail e Recuperação | O sistema deve permitir verificação de e-mail e recuperação de acesso por meio de fluxo seguro. | Verificação por link assinado com expiração; recuperação por token de uso único armazenado como hash e com expiração de 60 minutos; reenvio limitado e notificações em fila. Troca de senha autenticada não substitui recuperação. |
| **RF04** | **Alta** | Controle de Acesso e Permissões (RBAC) | O sistema deve aplicar permissões conforme o perfil do usuário: comprador, vendedor ou administrador. | Bloqueio imediato via Middleware e Policies; compradores não acessam rotas de criação de anúncios nem o painel `/admin`. |
| **RF05** | **Alta** | Gestão de Anúncios pelo Vendedor | O vendedor deve poder cadastrar, editar, pausar e remover seus próprios anúncios. | Somente o criador do anúncio ou administrador pode alterá-lo/excluí-lo (assegurado por `ListingPolicy`). |
| **RF06** | **Alta** | Estrutura de Dados do Anúncio | Cada anúncio deve permitir informar jogo, categoria, título, descrição, preço, imagens e status. | Preço maior que zero (min: R$ 1,00); pelo menos 1 imagem principal obrigatória; status inicial `publicado` ou `rascunho`. |
| **RF07** | **Alta** | Pesquisa e Filtros de Catálogo | Pesquisar, filtrar e ordenar por jogo, categoria, faixa de preço e reputação do vendedor; identificar vendedores sem avaliações e aplicar desempate estável. | Filtros combinados preservados na query string com paginação e tempo de resposta < 500ms. |
| **RF08** | **Alta** | Página de Detalhes do Anúncio | O sistema deve exibir uma página de detalhes do anúncio com informações do produto ou serviço, vendedor e reputação disponível. | Carrossel de fotos, especificações, nota de reputação média e botão de adicionar ao carrinho contextual. |
| **RF09** | **Alta** | Gestão do Carrinho de Compras | O comprador deve poder gerenciar o carrinho, adicionando, removendo e ajustando itens antes da compra. | Persistência do carrinho por usuário; bloqueio de compra de anúncios próprios ou de anúncios pausados. |
| **RF10** | **Alta** | Finalização de Compra (Checkout) | O comprador deve poder finalizar a compra por meio do checkout, gerando o pedido correspondente. | Geração de registro imutável em `orders` e congelamento dos preços unitários praticados em `order_items`. |
| **RF11** | **Alta** | Processamento com Gateway Externo | A finalização da compra deve incluir o processamento do pagamento por meio de um gateway externo. | Redirecionamento ou checkout transparente com Mercado Pago, Stripe ou PagSeguro; nenhum dado de cartão salvo no banco. |
| **RF12** | **Alta** | Confirmação por Webhooks e Idempotência | O sistema deve receber notificações/webhooks do gateway e atualizar de forma idempotente os status do pagamento e do pedido. | Retorno imediato `200 OK`; processamento assíncrono via fila; trava contra notificações duplicadas por `idempotency_key`. |
| **RF13** | **Alta** | Histórico e Acompanhamento de Pedidos | O comprador deve poder acompanhar o histórico e o status de seus pedidos. | Listagem cronológica com status detalhado (Pendente, Pago, Em Entrega, Concluído, Cancelado). |
| **RF14** | **Média** | Acompanhamento de Vendas pelo Vendedor | O vendedor deve poder acompanhar as vendas relacionadas aos seus anúncios. | Painel de vendas com visualização dos itens a entregar e confirmação manual de envio do item/serviço. |
| **RF15** | **Média** | Avaliação do Vendedor Pós-Compra | O comprador deve poder avaliar o vendedor somente após a conclusão da compra. | Pagamento confirmado e entrega do item concluída; uma avaliação por order_item_id, independentemente da quantidade ou de itens de outros vendedores; recálculo transacional da reputação. |
| **RF16** | **Média** | Denúncia de Anúncios por Usuários | O usuário autenticado deve poder denunciar um anúncio, informando o motivo da denúncia. | Modal de envio rápido; registro em `reports` com status inicial `aberta`; restrito a usuários logados. |
| **RF17** | **Alta** | Gestão Administrativa de Usuários | O administrador deve poder gerenciar usuários, incluindo consulta, alteração de status e demais ações administrativas previstas pela plataforma. | Visualização de lista de usuários, filtros por papel e opção de suspensão de contas irregulares. |
| **RF18** | **Alta** | Gestão Administrativa de Categorias | O administrador deve poder gerenciar as categorias utilizadas para classificar anúncios. | Criação, edição e inativação de categorias vinculadas aos jogos suportados. |
| **RF19** | **Alta** | Moderação Administrativa de Anúncios | O administrador deve poder moderar anúncios, podendo ocultar, bloquear ou restaurar anúncios conforme as regras da plataforma. | Bloqueio de anúncios irregulares procedentes, removendo-os imediatamente do catálogo público. |
| **RF20** | **Alta** | Tratamento Administrativo de Denúncias | O administrador deve poder consultar e tratar denúncias registradas pelos usuários. | Fila de moderação com histórico, campo de parecer motivado e julgamento (procedente / improcedente). |
>>>>>>> Stashed changes

---

### Ampliação de escopo de 18/09/2026 — chat com vendedor

Solicitada pelo responsável pelo projeto. RF01–RF20 mantêm a numeração do PDF original; RF21–RF23 ampliam a v1. Trata-se de escopo planejado, acompanhado pelas issues #30–#32.

| ID | Prioridade | Requisito | Critério de aceitação |
| :--- | :--- | :--- | :--- |
| RF21 | Alta | Iniciar/retomar conversa privada com vendedor | Usuário ativo contata outro vendedor por anúncio publicado; compra existente permite retomar após venda. Uma conversa por interessado, vendedor e anúncio; sem conversa consigo mesmo. |
| RF22 | Alta | Enviar e receber texto em tempo real | Texto de 1–2000 caracteres persistido antes de transmitir em canal privado; acesso somente por participantes ativos; retries sem duplicação. |
| RF23 | Média | Histórico e mensagens não lidas | Histórico paginado persistente, cursor de leitura independente, contador de não lidas e recuperação após reconexão. |

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
   - Transições válidas:
     - `rascunho` ➔ `publicado`
     - `publicado` ➔ `pausado` ou `vendido` ou `bloqueado`
     - `pausado` ➔ `publicado` ou `vendido`
     - `bloqueado` (apenas administrador pode desbloquear)
4. **RN04 — Congelamento de Preço no Pedido:** O preço praticado no momento da finalização do pedido é imutável e gravado na tabela `order_items`. Alterações posteriores no anúncio não impactam compras já efetuadas.
<<<<<<< Updated upstream
5. **RN05 — Condição de Avaliação:** A avaliação de um vendedor por um comprador só é permitida se o pedido estiver com o status `concluido`. Cada compra confere direito a 1 única avaliação.
6. **RN06 — Idempotência do Webhook:** Notificações de pagamento enviadas repetidas vezes pelo gateway (retry de entrega) devem ser verificadas pela coluna `idempotency_key` ou `transaction_id`. Se a transação já foi processada, retorna `200 OK` imediatamente sem alterar o saldo ou status novamente.
=======
5. **RN05 — Condição de Avaliação:** Pagamento confirmado e `order_items.delivery_status=entregue`; uma avaliação por item adquirido, garantida por constraint única. Itens de outros vendedores não bloqueiam essa avaliação.
6. **RN06 — Idempotência do Webhook:** Distinguir evento repetido de mudança de estado da mesma transação. Deduplicar eventos sem impedir `pending → approved`; validar assinatura, valor/moeda/pedido e estado no provedor, impedindo regressão por eventos fora de ordem.
7. **RN07 — Denúncias por Usuários Autenticados:** Somente usuários autenticados no sistema poderão registrar denúncias de anúncios.
8. **RN08 — Acionamento Obrigatório de Gateway:** Toda finalização de compra deverá obrigatoriamente acionar o processamento do pagamento por meio do gateway externo.

---

### Regras complementares da revisão

- **RN09 — Reserva:** Checkout cria pedido pendente e reserva capacidade com expiração; aprovação confirma consumo. Recusa, cancelamento ou expiração liberam reserva uma única vez. Pagamento tardio exige reconciliação sem dupla venda; eventual estorno ocorre no provedor.
- **RN10 — Quantidades e coaching:** Cosmético único tem quantidade 1. Serviço tem quantidade inteira de sessões, duração e capacidade configuradas pelo vendedor. Congelar essas condições no pedido; manter anúncio disponível enquanto houver capacidade. Horários/fuso são combinados por item e chat; concluir após todas as sessões, sem agenda automática na v1.
- **RN11 — Elegibilidade:** Venda de contas não entra nos seeds sem decisão documentada e validação das regras da plataforma. Jogos/categorias ativos precisam admitir o tipo de oferta.
- **RN12 — Privacidade do chat:** Leitura, envio e assinatura de canal exigem participação e conta ativa. Papel de administrador não concede acesso ao conteúdo. Sem e-mail, credenciais ou dados de pagamento nos eventos; escape de texto, limitação de envio e logs sem conteúdo privado.
- **RN13 — Moderação e histórico:** Somente administrador restaura anúncio bloqueado, se ainda elegível/disponível. Remover da vitrine preserva referências de pedidos e conversas.
- **RN14 — Critério de entrega:** M1 permanece aberta até #27 (RF03) e #28 (diagnósticos); testes críticos acompanham cada milestone. PDF revisado/UML é entrega da #33.

## 6. Critérios de Modelagem UML Aplicados

Conforme a Seção 6.1 da documentação oficial:
- **Associações entre atores e casos de uso:** Representadas por linhas contínuas, sem pontas de seta.
- **Generalização de Atores:** Comprador, Vendedor e Administrador especializam o ator geral `Usuário` por meio de generalização (`herança`), compartilhando funcionalidades comuns (Autenticar-se, Pesquisar e filtrar anúncios, Visualizar anúncio e Denunciar anúncio).
- **Visitante:** Representa o usuário não autenticado que pode se cadastrar, autenticar-se e navegar pelo catálogo.
- **Relacionamento `<<include>>`:** O único relacionamento obrigatório entre casos de uso é `Finalizar compra <<include>> Processar pagamento`, pois toda finalização de compra exige a execução do processamento de pagamento.
- **Independência de Fluxos:** `Gerenciar carrinho` e `Finalizar compra` não possuem relacionamento direto no diagrama, pois representam uma sequência de fluxo e não uma relação estrutural de caso de uso.
- **Ações Administrativas Atômicas:** `Gerenciar usuários`, `Gerenciar categorias`, `Moderar anúncios` e `Tratar denúncias` são casos de uso separados para representar ações de gestão independentes.
- **Gateway de Pagamento como Ator Externo:** Participa ativamente dos casos `Processar pagamento` e `Atualizar status de pagamento/pedido`.

### Casos de uso acrescentados

Comprador/interessado e Vendedor associam-se a Iniciar/retomar conversa, Enviar/receber mensagem e Consultar histórico/não lidas. Visitante deve estar associado a cadastro, autenticação, pesquisa e visualização. A figura do PDF será reexportada na #33 com todas as associações visíveis.
>>>>>>> Stashed changes

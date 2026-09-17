# Script PowerShell para criar automaticamente as Milestones e Issues no GitHub via gh cli
# Requisito: ter o GitHub CLI instalado (winget install GitHub.cli) e autenticado (gh auth login)

Write-Host "Criando Milestones no GitHub..." -ForegroundColor Cyan

gh api repos/:owner/:repo/milestones -f title="M1: Fundação, Autenticação e Perfis" -f description="Setup base, banco, autenticação, controle de papéis (RBAC) e layout inicial"
gh api repos/:owner/:repo/milestones -f title="M2: Catálogo, Anúncios e Mecanismo de Busca" -f description="CRUD de jogos/categorias/anúncios, filtros avançados e páginas de detalhes"
gh api repos/:owner/:repo/milestones -f title="M3: Carrinho, Checkout Transacional e Pagamentos" -f description="Carrinho de compras, serviço atômico de checkout e integração de webhook com gateway"
gh api repos/:owner/:repo/milestones -f title="M4: Pós-Venda, Entrega Digital e Reputação" -f description="Acompanhamento de pedidos/vendas, entrega de itens e avaliações pós-compra"
gh api repos/:owner/:repo/milestones -f title="M5: Painel Administrativo, Moderação e Qualidade" -f description="Dashboard administrativo, gestão de denúncias, auditoria e testes automatizados"

Write-Host "Criando Issues no GitHub..." -ForegroundColor Cyan

# M1
gh issue create --title "[Backend/Auth] Modelagem de Usuários, Autenticação Segura e Controle de Perfis (RBAC)" `
  --body "Configurar a base de dados de usuários e perfis com suporte a autenticação por sessão, hashing irreversível de senhas e políticas de autorização (RBAC) separando Comprador, Vendedor e Administrador.`n`nRequisitos: RF01, RF02, RF03, RF04, RNF02, RNF03" `
  --assignee "JoaoPMA23" --label "backend,security,database" --milestone "M1: Fundação, Autenticação e Perfis"

gh issue create --title "[UI/Auth] Layout Mestre Responsivo, Componentes Base e Telas de Autenticação" `
  --body "Desenvolver o layout principal da aplicação utilizando Tailwind CSS e Blade, incluindo barra de navegação responsiva, menu condicional por tipo de usuário, rodapé e as páginas de formulário de login e registro.`n`nRequisitos: RF01, RF02, RNF01" `
  --assignee "IgorMarcoli" --label "frontend,ui/ux,blade" --milestone "M1: Fundação, Autenticação e Perfis"

# M2
gh issue create --title "[Backend/Search] Mecanismo de Busca, Filtros Combinados e Otimização de Consultas" `
  --body "Construir a lógica de consulta do catálogo com suporte a múltiplos filtros simultâneos (busca textual por título, jogo, categoria, faixa de preço mínimo/máximo e ordenação), garantindo performance com índices no banco e paginação.`n`nRequisitos: RF07, RNF05" `
  --assignee "JoaoPMA23" --label "backend,database,performance" --milestone "M2: Catálogo, Anúncios e Mecanismo de Busca"

gh issue create --title "[Feature/Listings] Gestão de Jogos, Categorias e CRUD de Anúncios do Vendedor com Galeria" `
  --body "Desenvolver o painel de gerenciamento de anúncios para os vendedores, permitindo cadastrar novos itens com upload de múltiplas imagens, editar dados, alternar status (publicar/pausar) e remover anúncios com validação de política.`n`nRequisitos: RF05, RF06, RNF03" `
  --assignee "IgorMarcoli" --label "backend,frontend,feature" --milestone "M2: Catálogo, Anúncios e Mecanismo de Busca"

gh issue create --title "[UI/Catalog] Vitrine da Página Inicial e Página Detalhada do Anúncio com Dados do Vendedor" `
  --body "Desenvolver a interface da Home (vitrine de jogos populares e lançamentos) e a página individual de detalhes do anúncio, exibindo carrossel de fotos, dados completos, termos de entrega, reputação do vendedor e modal de denúncia.`n`nRequisitos: RF08, RNF01" `
  --assignee "IgorMarcoli" --label "frontend,ui/ux,blade" --milestone "M2: Catálogo, Anúncios e Mecanismo de Busca"

# M3
gh issue create --title "[Backend/Checkout] Camada de Serviço de Checkout Transacional com Congelamento Histórico de Preços" `
  --body "Implementar a regra de negócio central de fechamento de pedido via CheckoutService. A operação deve executar sob transação atômica (DB::transaction), validar a disponibilidade concorrente de cada anúncio, gerar o pedido e congelar os preços vigentes na tabela order_items.`n`nRequisitos: RF10, RNF06" `
  --assignee "JoaoPMA23" --label "backend,architecture,database" --milestone "M3: Carrinho, Checkout Transacional e Pagamentos"

gh issue create --title "[Backend/Payments] Integração com Gateway, Endpoint de Webhook Assíncrono e Idempotência" `
  --body "Criar o serviço de integração de pagamento (PaymentGatewayService) e o endpoint seguro de Webhook com processamento em fila, verificação de assinatura e mecanismo estrito de idempotência para evitar confirmação duplicada de pedidos.`n`nRequisitos: RF11, RNF04, RNF06, RNF09" `
  --assignee "JoaoPMA23" --label "backend,payment,security" --milestone "M3: Carrinho, Checkout Transacional e Pagamentos"

gh issue create --title "[Feature/Cart] Carrinho de Compras com Persistência em Banco e Controle de Subtotal" `
  --body "Desenvolver o módulo de carrinho de compras, permitindo ao comprador autenticado adicionar anúncios, visualizar a listagem com fotos e subtotais, remover itens indesejados e esvaziar o carrinho.`n`nRequisitos: RF09" `
  --assignee "IgorMarcoli" --label "backend,frontend,feature" --milestone "M3: Carrinho, Checkout Transacional e Pagamentos"

gh issue create --title "[UI/Checkout] Interface de Checkout, Instruções de Entrega e Telas de Confirmação" `
  --body "Desenvolver as páginas do fluxo de checkout: tela de resumo e confirmação com campo para instruções de entrega, checkbox de concordância com os termos e telas de retorno (sucesso e cancelamento).`n`nRequisitos: RF10, RNF01" `
  --assignee "IgorMarcoli" --label "frontend,ui/ux,blade" --milestone "M3: Carrinho, Checkout Transacional e Pagamentos"

# M4
gh issue create --title "[Backend/Reviews] Motor de Avaliação Pós-Compra e Cálculo Dinâmico de Reputação" `
  --body "Construir a camada de avaliação do vendedor pelo comprador (ReviewController). O sistema deve garantir que avaliações só ocorram após a entrega confirmada do item/serviço, limitando a 1 avaliação por compra e recalculando atomicamente a reputação média do vendedor.`n`nRequisitos: RF14, RNF07" `
  --assignee "JoaoPMA23" --label "backend,database,business-rules" --milestone "M4: Pós-Venda, Entrega Digital e Reputação"

gh issue create --title "[Feature/Orders] Painel de Acompanhamento de Pedidos e Histórico do Comprador" `
  --body "Construir a área do comprador para consulta de histórico cronológico de pedidos e tela detalhada de acompanhamento de status de entrega, incluindo o formulário de avaliação do vendedor.`n`nRequisitos: RF12, RNF03" `
  --assignee "IgorMarcoli" --label "frontend,backend,feature" --milestone "M4: Pós-Venda, Entrega Digital e Reputação"

gh issue create --title "[Feature/Sales] Painel de Vendas do Vendedor e Ação de Confirmação de Entrega" `
  --body "Desenvolver o painel de vendas recebidas para vendedores credenciados, permitindo visualizar os itens comprados por outros usuários, detalhes de contato/entrega e acionar o botão de marcação de item como entregue.`n`nRequisitos: RF13" `
  --assignee "IgorMarcoli" --label "backend,frontend,feature" --milestone "M4: Pós-Venda, Entrega Digital e Reputação"

# M5
gh issue create --title "[Backend/Security] Testes Automatizados de Regras Críticas e Revisão de Segurança" `
  --body "Desenvolver a suíte de testes automatizados com Pest/PHPUnit cobrindo os fluxos mais sensíveis do sistema: fechamento transacional de checkout, recepção de webhooks de pagamento com garantia de idempotência e regras de autorização de policies.`n`nRequisitos: RNF02, RNF03, RNF06, RNF08" `
  --assignee "JoaoPMA23" --label "testing,security,qa" --milestone "M5: Painel Administrativo, Moderação e Qualidade"

gh issue create --title "[Feature/Admin] Painel de Controle Administrativo e Gestão de Usuários/Categorias" `
  --body "Construir o painel administrativo exclusivo para usuários com papel admin, exibindo indicadores gerais da plataforma (total de usuários, volume de pedidos, anúncios ativos) e gerenciamento de categorias e usuários.`n`nRequisitos: RF15, RNF03" `
  --assignee "IgorMarcoli" --label "admin,backend,frontend" --milestone "M5: Painel Administrativo, Moderação e Qualidade"

gh issue create --title "[Feature/Moderation] Fila de Moderação de Denúncias e Bloqueio de Anúncios Suspeitos" `
  --body "Desenvolver a fila de moderação de denúncias para os administradores, permitindo visualizar detalhes reportados pelos compradores, registrar parecer motivado e suspender/bloquear anúncios irregulares.`n`nRequisitos: RF15, RNF09" `
  --assignee "IgorMarcoli" --label "admin,moderation,feature" --milestone "M5: Painel Administrativo, Moderação e Qualidade"

Write-Host "Todas as Milestones e Issues foram criadas com sucesso!" -ForegroundColor Green

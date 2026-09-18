# 🤖 Diretrizes para Agentes de IA — TheMerchant

> **Arquivo de Contexto e Instruções Operacionais para Agentes de Inteligência Artificial**  
> **Repositório:** `IgorMarcoli/TheMerchant`  
> **Contexto:** Marketplace Web de Cosméticos e Serviços para Jogos Digitais (FATEC PG - LES III 2026)  

---

## 🎯 Objetivo do Repositório
O **TheMerchant** é um sistema web desenvolvido em **PHP 8.2+ e Laravel 11/12**, projetado para intermediar a comercialização segura de cosméticos digitais (*skins*, avatares, temas visuais) e serviços relacionados a jogos (*coaching*).

---

## 🏛️ Padrões Arquiteturais Obrigatórios

Ao gerar ou modificar código neste projeto, respeite os seguintes princípios:

1. **Arquitetura em Camadas (Layered Architecture):**
   - **Controllers:** Devem ser extremamente enxutos (*skinny controllers*), responsáveis exclusivamente por receber a requisição, chamar o Form Request para validação, invocar o Service competente e retornar a View ou Redirecionamento com mensagem flash.
   - **Form Requests:** Toda validação de formulário com regras complexas (ex: regras de cadastro de anúncio, checkout) deve estar em `app/Http/Requests/`.
   - **Camada de Serviços (Services):** Fluxos de negócio transacionais (`CheckoutService`, `PaymentGatewayService`, `ReviewService`) devem residir em `app/Services/`. Use `DB::transaction()` em qualquer operação que envolva mais de uma tabela.
   - **Policies & Gates:** Nenhuma checagem manual com `if ($user->id !== $listing->user_id)` dentro de Controllers. Use `$this->authorize('update', $listing)` ou `@can` nas Blade views via `app/Policies/`.
   - **Jobs & Queues:** Processamento de webhooks e envio de e-mails/notificações devem ser despachados para a fila via `dispatch()`.

2. **Convenções de Código (PHP / Laravel):**
   - Tipagem estrita em métodos: use declarações de tipos para argumentos e retornos (ex: `public function process(Order $order): PaymentResult`).
   - Siga rigorosamente o padrão **PSR-12**.
   - No Eloquent, utilize relacionamentos explícitos (`hasMany`, `belongsTo`, `hasOne`, `belongsToMany`) com chaves estrangeiras tipadas.

3. **Front-end com Blade & Tailwind:**
   - As views residem em `resources/views/`.
   - Use o layout mestre `resources/views/layouts/app.blade.php`.
   - Mantenha componentes reutilizáveis em `resources/views/components/` (ex: `badge.blade.php`, `listing-card.blade.php`).
   - Evite bibliotecas pesadas de terceiros; priorize **Alpine.js** para reatividade declarativa local (ex: modais, tabs, carrosséis de fotos).

4. **Tratamento de Dados e Segurança:**
   - **NUNCA** colete ou armazene dados brutos de cartão de crédito no banco de dados.
   - Sempre utilize CSRF em formulários Blade (`@csrf`).
   - Garanta idempotência em qualquer endpoint que receba Webhooks de pagamento.

---

## 📚 Documentos de Referência no Repositório
- `README.md`: Visão executiva, membros da equipe, status e resumo do projeto.
<<<<<<< Updated upstream
- `requirements.md`: Tabela completa de Requisitos Funcionais (RF01 - RF15) e Não Funcionais (RNF01 - RNF09).
=======
- `requirements.md`: Tabela completa de Requisitos Funcionais (RF01 - RF23, incluindo chat privado em tempo real) e Não Funcionais (RNF01 - RNF09).
>>>>>>> Stashed changes
- `specs.md`: Especificações arquiteturais detalhadas, diagramas ERD, definições de schema e tabelas.
- `CONTRIBUTING.md`: Guia de branches, Conventional Commits e fluxos de PR.

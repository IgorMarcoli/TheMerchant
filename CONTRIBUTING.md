# 🤝 Guia de Contribuição e Boas Práticas — TheMerchant

> **Guia Operacional e Tutorial Prático de Desenvolvimento Colaborativo**  
> **Equipe:** Igor Marcoli Bastos & João Pedro Martins de Andrade  
> **Disciplina:** Laboratório de Engenharia de Software III (LES III 2026) — FATEC PG  

---

## 🎯 Por que este guia existe?

Para que o desenvolvimento em dupla ocorra sem atritos, sem commits perdidos e sem conflitos de merge desagradáveis (*merge conflicts*), estabelecemos este fluxo simples e profissional. 

Seguir essas práticas deixará o histórico do Git limpo, facilitará a avaliação pelo professor e servirá como um excelente exemplo de engenharia de software para o portfólio de ambos.

---

## 🌿 1. Fluxo de Trabalho com Git (Passo a Passo)

### Regra de Ouro:
> **NUNCA trabalhem ou façam commit direto na branch `main`.**  
> A branch `main` deve sempre conter código estável, testado e pronto para entrega/apresentação.

---

### Passo 1: Antes de começar uma tarefa (Sincronizar)
Sempre que for iniciar uma nova funcionalidade ou correção, garanta que a sua máquina possui a versão mais recente do repositório:

```bash
# 1. Vá para a branch principal
git checkout main

# 2. Puxe as atualizações mais recentes feitas pelo seu parceiro
git pull origin main
```

---

### Passo 2: Criar uma branch temática
Nunca misture funcionalidades na mesma branch. Crie uma branch com nome claro indicando o que você está fazendo:

| Prefixo | Quando Usar | Exemplo de Nome de Branch |
| :--- | :--- | :--- |
| `feat/` | Nova funcionalidade ou tela | `feat/carrinho-compras`, `feat/crud-anuncios` |
| `fix/` | Correção de bug ou validação incorreta | `fix/calculo-total-pedido`, `fix/upload-foto` |
| `docs/` | Documentações e especificações | `docs/atualizar-requisitos`, `docs/ajuste-readme` |
| `refactor/` | Melhoria de código sem alterar regra | `refactor/checkout-service`, `refactor/views-blade` |
| `chore/` | Ajustes em configs, pacotes ou build | `chore/config-tailwind`, `chore/setup-sail` |

**Comando no terminal:**
```bash
# Cria e entra na nova branch
git checkout -b feat/cadastro-anuncios
```

---

### Passo 3: Trabalhando e inspecionando alterações
Ao terminar ou pausar uma parte do código, confira exatamente o que foi modificado antes de salvar:

```bash
# Veja quais arquivos foram alterados ou criados
git status

# Veja a diferença exata linha por linha
git diff
```

> ⚠️ **Atenção:** Evite rodar `git add .` cegamente sem verificar o `git status`. Tenha certeza de que **não está adicionando arquivos temporários ou confidenciais** como `.env` com senhas reais!

---

### Passo 4: Salvando com Commits Semânticos (*Conventional Commits*)
Adicione apenas os arquivos pertinentes à tarefa:

```bash
# Adiciona arquivos específicos
git add app/Http/Controllers/Seller/ListingController.php resources/views/seller/listings/create.blade.php

# Salva o commit com mensagem padronizada
git commit -m "feat(listings): implementar tela de cadastro de anuncios com upload"
```

---

### Passo 5: Enviando para o GitHub
Envie a sua branch para o repositório remoto:

```bash
# Envia a branch criada para o GitHub
git push -u origin feat/cadastro-anuncios
```

---

### Passo 6: Abrindo a Pull Request (Code Review em Dupla)
1. Acesse o repositório no GitHub: [github.com/IgorMarcoli/TheMerchant](https://github.com/IgorMarcoli/TheMerchant);
2. Você verá o aviso amarelo convidando a criar uma **Compare & Pull Request**;
3. Preencha a descrição rápida da PR:
   - **O que foi feito:** Breve resumo das telas/regras criadas;
   - **Requisito atendido:** Ex: `Atende RF05 e RF06`;
   - **Como testar:** Passos manuais que o colega pode seguir para ver funcionando.
4. **Marque o outro membro como Reviewer**;
5. O colega dá uma olhada, valida se tudo faz sentido e clica em **Merge pull request**!

---

## 💬 2. Como Commitar: O Guia do Conventional Commits

Um bom histórico de commits conta a história do projeto. O padrão que usamos é:

```
<tipo>(<escopo>): <descrição no imperativo e em minúsculas>
```

### 📋 Tabela de Tipos de Commit

| Tipo | Finalidade | Exemplo Real no TheMerchant |
| :---: | :--- | :--- |
| `feat` | Uma funcionalidade nova no sistema | `feat(cart): adicionar subtotal e remocao de itens` |
| `fix` | Correção de um bug, erro ou validação | `fix(checkout): impedir criacao de pedido com carrinho vazio` |
| `docs` | Alterações em arquivos de documentação | `docs(specs): atualizar diagrama erd com novas tabelas` |
| `style` | Formatação de código ou ajuste visual sem mudar lógica | `style(views): ajustar espacamento no card de anuncio` |
| `refactor` | Refatoração de código sem alterar regra externa | `refactor(services): desacoplar chamada do gateway de pagamento` |
| `test` | Criação ou ajuste de testes automatizados | `test(feature): adicionar teste de integracao do checkout` |
| `chore` | Configurações gerais, dependências ou scripts | `chore(deps): adicionar alpinejs e dependencias do vite` |

### ❌ Como NÃO commitar (Anti-exemplos):
- `git commit -m "alterações"` ❌ *(Genérico, ninguém sabe o que mudou)*
- `git commit -m "arrumando bug"` ❌ *(Qual bug? Onde?)*
- `git commit -m "wip"` ou `git commit -m "commitei"` ❌
- `git commit -m "tudo pronto"` ❌

### ✅ Como commitar Corretamente (Exemplos recomendados):
- `git commit -m "feat(auth): implementar registro com selecao de comprador ou vendedor"`
- `git commit -m "fix(payment): garantir idempotencia no webhook com transaction_id"`
- `git commit -m "docs(readme): adicionar tabela de requisitos funcionais"`
- `git commit -m "feat(seller): adicionar botao de marcar pedido como entregue"`

---

## 🏛️ 3. Boas Práticas de Código no TheMerchant

Para mantermos a nota alta em Engenharia de Software e o código limpo, sigam estas regras no dia a dia:

### 3.1 Nomenclatura no Laravel & PHP (PSR-12)
- **Controllers:** PascalCase com sufixo `Controller` (ex: `ListingController.php`, `CheckoutController.php`);
- **Models:** PascalCase no singular (ex: `Listing.php`, `User.php`, `OrderItem.php`);
- **Tabelas do Banco:** snake_case no plural (ex: `listings`, `order_items`, `seller_profiles`);
- **Colunas do Banco:** snake_case (ex: `unit_price`, `seller_id`, `delivery_status`);
- **Views Blade:** kebab-case ou snake_case em pastas modulares (ex: `resources/views/listings/show.blade.php`).

---

### 3.2 "Skinny Controllers, Fat Services" (Controllers Enxutos)
**Nunca** coloquem regras de negócio complexas, cálculos financeiros ou queries gigantes dentro dos Controllers.

```php
// ❌ EVITE: Lógica complexa e transacional direto no Controller
public function process(Request $request) {
    // 50 linhas criando pedido, calculando taxas, salvando itens...
}

// ✅ PREFIRA: Delegar para Form Requests e Services
public function process(CheckoutRequest $request, CheckoutService $checkoutService) {
    $result = $checkoutService->checkout($request->user(), $request->notes);
    return redirect($result['checkout_url']);
}
```

---

### 3.3 Validações Sempre em Form Requests
Não façam validações manuais com `if` ou blocos gigantes no controller. Usem as classes em `app/Http/Requests/`:
- `ListingStoreRequest.php`
- `CheckoutRequest.php`

---

### 3.4 Transações de Banco de Dados (`DB::transaction`)
Sempre que uma operação envolver **duas ou mais tabelas** (por exemplo: criar o pedido em `orders`, criar os itens em `order_items` e marcar os anúncios em `listings` como vendidos), envolvam o bloco em `DB::transaction()`:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($user, $cart) {
    // Se qualquer query falhar aqui dentro, o Laravel desfaz tudo (Rollback)
    // evitando dados corrompidos ou inconsistentes no banco.
});
```

---

### 3.5 Autorização com Policies (Sem `if` manual de ID)
Nunca façam checagens manuais de permissão no Controller:
```php
// ❌ EVITE:
if ($listing->seller_id !== auth()->id()) {
    abort(403);
}

// ✅ PREFIRA:
$this->authorize('update', $listing); // Usando ListingPolicy.php
```

---

### 3.6 Front-end com Tailwind CSS & Blade Components
- Não criem arquivos `.css` avulsos com regras manuais desnecessárias. Usem as classes utilitárias do Tailwind;
- Para elementos visuais repetidos (como badges de status ou cards de anúncio), usem os componentes em `resources/views/components/`:
  ```blade
  <x-badge type="success">Entregue</x-badge>
  <x-listing-card :listing="$listing" />
  ```

---

## ⚡ 4. O que Fazer se Der Conflito de Merge? (*Merge Conflict*)

Se vocês dois mexerem no mesmo arquivo e o Git avisar sobre conflito ao puxar a branch:

1. **Fique calmo**: Conflito é normal em desenvolvimento de software;
2. Abra o arquivo marcado com conflito no VS Code / editor;
3. O editor vai mostrar as opções:
   - *Accept Current Change* (manter o que você fez);
   - *Accept Incoming Change* (manter o que o seu parceiro fez);
   - *Accept Both Changes* (combinar ambos);
4. Ajuste o código para que faça sentido, salve o arquivo;
5. Finalize no terminal:
   ```bash
   git add nome-do-arquivo.php
   git commit -m "fix(merge): resolver conflito no controller de anuncios"
   git push origin sua-branch
   ```

---

## ✅ 5. Checklist Pré-PR (Faça antes de abrir o Pull Request)

Antes de avisar o parceiro que a PR está pronta, faça este check rápido:

- [ ] A aplicação roda localmente sem erros 500 no navegador?
- [ ] O comando de migração funciona limpo? (`php artisan migrate`)
- [ ] As mensagens de commit seguem o formato `tipo(escopo): mensagem`?
- [ ] Nenhum arquivo desnecessário foi commitado (ex: `.env`, arquivos temporários)?
- [ ] A tela/fluxo foi testada manualmente simulando a experiência do usuário?

---

Com esse fluxo, o desenvolvimento do **TheMerchant** será ágil, organizado e com padrão profissional de mercado! 🚀

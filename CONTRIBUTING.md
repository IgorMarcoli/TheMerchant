# 🤝 Guia de Contribuição — TheMerchant

Agradecemos o interesse em contribuir com o **TheMerchant**! Este guia define os padrões e fluxos adotados pela equipe para manter o código organizado, testável e alinhado aos requisitos acadêmicos da disciplina **Laboratório de Engenharia de Software III (FATEC PG)**.

---

## 🌿 1. Estratégia de Branches (Git Flow)

Trabalhamos com o modelo baseado em branches funcionais derivadas de `develop`:

- `main`: Branch estável com versões validadas e prontas para entrega ao professor.
- `develop`: Branch de integração principal do desenvolvimento contínuo.
- `feat/<nome-da-feature>`: Para novas funcionalidades (ex: `feat/checkout-flow`, `feat/listing-filters`).
- `fix/<nome-do-bug>`: Para correções de bugs identificados (ex: `fix/payment-webhook-idempotency`).
- `docs/<nome-da-doc>`: Para atualizações de documentação (ex: `docs/erd-update`).
- `refactor/<escopo>`: Para refatorações sem alteração de comportamento externo.

---

## 💬 2. Padrão de Commits (Conventional Commits)

Os commits devem ser atômicos e seguir o padrão semântico:

```
<tipo>(<escopo>): <descrição no imperativo e concisa>
```

### Tipos Permitidos:
- `feat`: Uma nova funcionalidade adicionada ao sistema.
- `fix`: Correção de um bug ou comportamento incorreto.
- `docs`: Alterações exclusivamente em arquivos de documentação (README, specs, etc.).
- `style`: Formatação, identação ou remoção de espaços (sem alteração de lógica).
- `refactor`: Mudança no código que não altera o comportamento funcional nem corrige bugs.
- `test`: Adição ou correção de testes automatizados (Pest / PHPUnit).
- `chore`: Atualização de tarefas de build, pacotes ou configurações auxiliares.

### Exemplos:
```bash
feat(listings): implementar upload de imagens na criação de anúncios
fix(checkout): garantir idempotência no processamento de webhooks
test(services): adicionar cobertura de testes para CheckoutService
docs(readme): atualizar tabela de requisitos funcionais
```

---

## 📐 3. Padrões de Código e Qualidade

1. **PHP & Laravel:**
   - Siga rigorosamente os padrões **PSR-12** e as convenções oficiais do Laravel.
   - Mantenha **Controllers enxutos**: lógica de negócio complexa deve residir em `app/Services/` ou `Actions`.
   - Utilize **Form Requests** para validações de requisições HTTP (`app/Http/Requests`).
   - Use **Laravel Policies** para controle de autorização baseado em perfil (*RBAC*).
   - Não armazene senhas ou tokens em texto claro no código ou no banco.
   - Execute o formatador oficial antes de commitar:
     ```bash
     vendor/bin/pint
     ```

2. **Front-end:**
   - Utilize **Tailwind CSS** para estilização utilitária e componentes Blade reutilizáveis (`resources/views/components/`).
   - Mantenha scripts JavaScript organizados com **Alpine.js** para comportamentos reativos leves (ex: modais, abas, carrosséis).

3. **Testes Automatizados:**
   - Execute os testes antes de abrir uma Pull Request:
     ```bash
     php artisan test
     ```

---

## 🚀 4. Fluxo de Submissão (Pull Request)

1. Crie uma branch a partir de `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feat/minha-feature
   ```
2. Realize suas alterações respeitando os padrões de commit;
3. Execute as migrações, testes e o Laravel Pint localmente;
4. Envie a branch para o repositório remoto:
   ```bash
   git push origin feat/minha-feature
   ```
5. Abra uma Pull Request (PR) detalhando:
   - Resumo das alterações implementadas;
   - IDs dos Requisitos Funcionais atendidos (ex: `RF06`, `RF07`);
   - Procedimento de teste manual realizado.
6. Solicite a revisão dos membros da equipe antes de efetuar o merge.

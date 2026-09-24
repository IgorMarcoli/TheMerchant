# Supabase no ambiente local

Se o banco contém tabelas duplicadas em português e inglês, siga [a consolidação do schema](CONSOLIDACAO_SCHEMA.md) antes de criar novas tabelas. O procedimento preserva o protótipo em um schema de arquivo e mantém `laravel` como modelo ativo.

O Laravel usa o PostgreSQL do Supabase via Eloquent. Cadastro, login, permissões e sessões continuam no Laravel; não dependem de uma chave `sb_secret_...` nem do Supabase Auth.

## PHP correto

O projeto requer PHP 8.2+ e `pdo_pgsql`. O PHP do terminal pode ser diferente do PHP carregado pelo Apache do XAMPP. Habilitar uma extensão no terminal não habilita automaticamente a extensão no Apache.

```powershell
Get-Command php
php --version
php --ini
php -m
```

No `php.ini` indicado, habilite `extension=pdo_pgsql`. Para Apache, confira a versão e o `PHPIniDir` da instalação XAMPP e reinicie o Apache após alterar a configuração. PHP 8.0 não é compatível com esta aplicação; use PHP 8.2+.

Com PHP compatível no terminal, o projeto pode funcionar localmente sem o MySQL ou o Apache do XAMPP:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Abra `http://localhost:8000`. `http://localhost` na porta 80 é outro servidor e pode mostrar apenas o painel padrão do XAMPP. Para configurar Apache diretamente, a raiz pública deve ser `TheMerchant/public`, nunca a pasta que contém `.env`.

## Conexão

Copie host e usuário de **Supabase → Connect → Session pooler** para o `.env` local:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=host_do_session_pooler
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.referencia_do_projeto
DB_PASSWORD="senha_atual_do_banco"
DB_SCHEMA=laravel
DB_SSLMODE=require
```

Use a senha do banco, não a senha da conta Supabase nem uma chave de API. O caractere `@` é permitido diretamente em `DB_PASSWORD`; não acrescente uma barra antes dele. Se usar `DB_URL`, seus valores prevalecem sobre os campos separados; remova uma URL antiga ao adotar esta configuração. Variáveis do sistema também podem prevalecer sobre `.env`.

Em um projeto Supabase novo, crie `laravel` no SQL Editor:

```sql
CREATE SCHEMA IF NOT EXISTS laravel;
```

Depois:

```powershell
php artisan config:clear
php artisan migrate
php artisan test:db --write
php artisan serve
```

`test:db --write` verifica a conexão, o schema PostgreSQL, as tabelas essenciais e uma gravação/leitura na tabela de cache dentro de uma transação desfeita ao terminar. O comando retorna código diferente de zero se houver falha. Ele não imprime credenciais.

## Erros comuns

| Erro | Correção |
| --- | --- |
| `password authentication failed` | Confirme usuário e senha do banco desse projeto; salve `.env` e limpe o cache de configuração. |
| `could not find driver` | Habilite `pdo_pgsql` no PHP efetivamente usado pelo servidor. |
| Composer exige PHP 8.2 | Execute com PHP 8.2+; PHP 8.0 do XAMPP não serve para esta versão do Laravel. |
| Tabela ausente | Confira `DB_SCHEMA=laravel` e execute `php artisan migrate`. |
| Aparece o painel XAMPP | Abra a porta do servidor Laravel (`localhost:8000`). |

O banco fica no Supabase: registros de um MySQL anterior não são copiados automaticamente. Os dados podem ser vistos no **Table Editor → schema laravel**. Para uma instalação ou atualização via SQL, veja [os arquivos e instruções de migração](CONTAS_E_VENDEDORES.md).

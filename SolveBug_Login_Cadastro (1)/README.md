# SolveBug - Login + Cadastro + PHP + MySQL

Primeira versão funcional do sistema de autenticação do SolveBug, preparada para o banco `solvebugs`.

## 1. Requisitos

- XAMPP (Apache + MySQL/MariaDB)
- PHP 8.x
- phpMyAdmin
- VS Code

## 2. Coloque o projeto no XAMPP

Copie a pasta `SolveBug_Login_Cadastro` para:

`C:\xampp\htdocs\`

Depois ela ficará, por exemplo:

`C:\xampp\htdocs\SolveBug_Login_Cadastro`

## 3. Banco de dados

O projeto usa:

- Banco: `solvebugs`
- Tabela: `usuarios`
- Campos utilizados: `id`, `nome`, `email`, `senha`, `tipo`, `data_cadastro`

O banco original já possui a tabela `usuarios` com essa estrutura.

Se ainda não importou o banco, use o seu arquivo SQL do SolveBug no phpMyAdmin.

## 4. Conexão

Abra:

`config/conexao.php`

Por padrão está configurado para o XAMPP:

- host: localhost
- banco: solvebugs
- usuário: root
- senha: vazia

Se seu MySQL tiver outra senha, altere `$pass`.

## 5. Rodar

1. Abra o XAMPP.
2. Inicie Apache.
3. Inicie MySQL.
4. Acesse no navegador:

`http://localhost/SolveBug_Login_Cadastro/`

Não abra os arquivos `.php` pelo duplo clique no Windows. PHP precisa ser executado pelo Apache.

## 6. Login

A tela de login procura o usuário pelo e-mail.

As senhas novas são salvas com `password_hash()` e verificadas com `password_verify()`.

O código também possui compatibilidade temporária com registros antigos que estejam em texto puro e, quando o usuário entra corretamente, transforma essa senha em hash.

## 7. Cadastro

O cadastro:

- valida nome;
- valida e-mail;
- exige senha de pelo menos 6 caracteres;
- confirma a senha;
- impede e-mail duplicado;
- salva a senha com hash;
- grava `tipo = usuario`.

## 8. Arquivos principais

- `index.php` - página inicial
- `login.php` - login
- `cadastro.php` - cadastro
- `logout.php` - encerra a sessão
- `usuario.php` - área protegida do usuário
- `config/conexao.php` - conexão PDO com MySQL
- `config/auth.php` - controle de sessão
- `css/auth.css` - estilo do login/cadastro
- `css/Menu.css` - estilo inicial
- `css/Usuario.css` - perfil

## Próxima etapa

Depois que esta versão estiver funcionando, o próximo passo é ligar os comentários, favoritos e soluções ao `usuario_id` real do banco, substituindo o `localStorage` do JavaScript.

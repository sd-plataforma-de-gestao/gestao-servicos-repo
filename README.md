## Sistema de Gestão de Serviços

# Sobre o Projeto
O Sistema de Gestão de Serviços é uma plataforma desenvolvida para otimizar e centralizar o gerenciamento de tarefas, ordens de serviço e recursos em empresas. O projeto, que atualmente conta com as telas de Home e Login, está em fase de implementação para se tornar uma solução robusta e completa.

# 🚀 Tecnologias Utilizadas
Este projeto está sendo construído com as seguintes tecnologias:

Front-end: HTML5 e CSS3

Back-end: PHP

Banco de Dados: MySQL

Controle de Versão: Git e GitHub

# ⚙️ Como Executar o Projeto
Para rodar o projeto localmente, siga os passos abaixo:

Clone o repositório:
git clone https://github.com/sd-plataforma-de-gestao/gestao-servicos-repo.git

Navegue até o diretório do projeto:
cd gestao-servicos-repo

Configurações do PHP e MySQL:

Certifique-se de ter um ambiente de desenvolvimento com PHP e MySQL instalados e configurados (como XAMPP, WAMP ou MAMP).

Mova os arquivos do projeto para o diretório do seu servidor web (ex: htdocs no XAMPP).

O projeto ainda está em desenvolvimento, e a conexão com o banco de dados será configurada em breve.

Acesse a aplicação:

Rode pelo live server (extensão do VSCode)

# 🤝 Contribuições
Contribuições são sempre bem-vindas! Se você deseja colaborar com o projeto, sinta-se à vontade para abrir uma issue ou um pull request.

# 📧 Contato
Para mais informações, entre em contato através do repositório ou por meio dos perfis dos membros da equipe.

---

**Como rodar o projeto localmente (com XAMPP no Windows)**

Siga estes passos para executar a aplicação PHP/MySQL na sua máquina usando o XAMPP (Windows). As instruções assumem que você está usando o terminal Bash (`bash.exe`) e que o XAMPP está instalado em `C:\xampp` — ajuste os caminhos se necessário.

1) Pré-requisitos
- Instale o XAMPP: https://www.apachefriends.org/
- Tenha o repositório clonado localmente (ex.: `C:\Users\SeuUsuario\Documents\GitHub\gestao-servicos-repo`).

2) Iniciar o XAMPP
- Abra o `XAMPP Control Panel` e inicie os serviços **Apache** e **MySQL**.

3) Copiar os arquivos para o `htdocs`
Abra um terminal Bash e, a partir da raiz do repositório, copie os arquivos para a pasta `htdocs` do XAMPP:

```bash
# estando em C:/Users/Admin/Documents/GitHub/gestao-servicos-repo
cp -r . /c/xampp/htdocs/gestao-servicos-repo
```

Isso cria a pasta `C:\xampp\htdocs\gestao-servicos-repo` com o conteúdo do projeto. Se preferir, você pode mover os arquivos manualmente pelo Explorer.

4) Criar o banco de dados e importar o esquema
Você pode usar o phpMyAdmin (recomendado) ou a linha de comando.

- Via phpMyAdmin:
	- Acesse `http://localhost/phpmyadmin` no navegador.
	- Clique em "Novo" e crie um banco de dados chamado `farmacia` (ou outro nome, mas mantenha-o consistente).
	- Selecione o banco criado, clique em "Importar" e envie o arquivo `back-end/farmacia.sql` presente no projeto.

- Via linha de comando (ajuste o binário do mysql se necessário):
```bash
/c/xampp/mysql/bin/mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS farmacia CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
/c/xampp/mysql/bin/mysql -u root farmacia < back-end/farmacia.sql
```

5) Configurar conexão com o banco
- Abra o arquivo `config/database.php` e atualize as credenciais do banco (servidor, usuário, senha, nome do banco) conforme seu ambiente XAMPP. Exemplo mínimo:

```php
<?php
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP tipicamente usa senha vazia para root
$dbname = "farmacia";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
		die("Conexão falhou: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
```

6) Acessar a aplicação
- No navegador, acesse: `http://localhost/gestao-servicos-repo` (ou `http://localhost/gestao-servicos-repo/index.php`).

7) Dicas de configuração e solução de problemas
- Certifique-se de que o Apache está rodando e que a porta 80 não está ocupada (outros servidores, Skype, IIS, etc.).
- Se o MySQL não iniciar, verifique o XAMPP Control Panel e os logs em `C:\xampp\mysql\data\mysql_error.log`.
- Se ocorrerem erros de permissão na leitura de arquivos, verifique o usuário do Apache (no Windows costuma não ser problema) e se os arquivos foram copiados corretamente.
- Caso use um nome de banco diferente de `farmacia`, atualize `config/database.php` e quaisquer scripts que assumam esse nome.

8) Configurações PHP úteis (opcional)
- Se precisar habilitar exibição de erros em desenvolvimento, edite `php.ini` e ajuste `display_errors = On` e `error_reporting = E_ALL`. Reinicie o Apache após alterar `php.ini`.
- Verifique também a configuração de `date.timezone` no `php.ini`, por exemplo:
```
date.timezone = "America/Sao_Paulo"
```

9) Importante — segurança
- Essas instruções são para ambiente de desenvolvimento local. Não use credenciais ou configurações inseguras em produção.
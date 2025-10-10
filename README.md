# 🧪 Plataforma de Gestão Clínica e Farmacêutica

## 📘 Visão Geral do Projeto

A **Plataforma de Gestão Clínica e Farmacêutica** (versão do *Projeto Integrador*) é uma aplicação web destinada a **gestionar serviços clínicos, farmacêuticos e farmacovigilância** em nível municipal/por unidade. O objetivo principal é organizar e automatizar o fluxo de atendimento farmacêutico (crônico e agudo), cadastro de pacientes e prontuários, emissão de receituários, gerenciamento de unidades (até 8 CNPJs) e geração de relatórios gerenciais e operacionais.

O projeto está sendo desenvolvido em sprints e, atualmente, conta com telas iniciais de **Home** e **Login** e protótipos dos fluxos de atendimento farmacêutico.

---

## 🎯 Escopo funcional (resumido - itens essenciais para a Sprint)

- **Cadastro e gestão de pacientes:** dados demográficos, contatos, histórico e prontuário eletrônico.
- **Módulo de atendimento farmacêutico:** registro de atendimentos agudos e acompanhamento de pacientes crônicos.
- **Fluxo guiado de perguntas e respostas:** baseado em protocolos oficiais (para triagem e encaminhamento).
- **Geração automática de relatórios e receituários:** templates editáveis, possibilidade de impressão/exportação (PDF).
- **Gestão por unidade:** suporte para até 8 unidades distintas (controle por CNPJ, permissões e relatórios por unidade).
- **Portal do paciente:** visualização de prontuário, receituários, histórico e encaminhamentos (autenticação necessária).
- **Relatórios e insights:** métricas de atendimento, consumo de medicamentos, indicadores por unidade.
- **Segurança e conformidade:** tratamento de dados conforme a LGPD (minimização, consentimento, logs de acesso).

---

## 🧠 Stack Tecnológico (versão recomendada / alternativa)

**Versão do grupo (sugerida / atual):**
- Frontend: HTML5, CSS3, JavaScript (+ Bootstrap para UI)
- Backend: PHP
- Banco de Dados: MySQL (desenvolvimento em XAMPP)

**Versão alternativa (MVP / escalável):**
- Frontend: React ou Vue.js
- Backend: Python (Django / Flask) ou Node.js
- Banco de Dados: PostgreSQL (ou MySQL)
- Hospedagem: nuvem (ex.: DigitalOcean, AWS, Render)

**Integração de IA (opcional):**
- API Gemini (uso pontual para sumarização de prontuário, sugestões clínicas e geração de texto de receituário).

---

## ⚙️ Requisitos de ambiente (desenvolvimento)

- XAMPP (Apache + MySQL)
- PHP (versão compatível com o XAMPP instalado)
- Git
- VSCode (ou outro editor)
- (Opcional) Node.js + npm

---

## 📁 Estrutura sugerida do repositório

```
gestao-servicos-repo/
├── templates/           # includes: header.php, footer.php, modais e componentes HTML/PHP
├── styles/              # CSS: global.css, forms.css, responsive.css
├── js/                  # JS: validações, scripts UI, chamadas AJAX
├── config/              # config: db_connect.php, .env.example, gestao_servicos_schema.sql
├── back-end/            # controllers/, models/, services/ (lógica do servidor)
├── assets/              # imagens, logos, PDFs, favicon
├── portal/               # código do portal do paciente (separado para deploy)
├── index.php
├── README.md
├── API.md               # documentação de endpoints (recomendado)
├── .gitignore
└── .editorconfig
```

---

## 🔧 Instalação e configuração (passo a passo)

1. **Clonar o repositório**

```bash
git clone https://github.com/sd-plataforma-de-gestao/gestao-servicos-repo.git
cd gestao-servicos-repo
```

2. **Copiar para o diretório do servidor local**
- No Windows com XAMPP: copie a pasta para `C:/xampp/htdocs/gestao-servicos-repo` ou configure um Virtual Host.

3. **Iniciar Apache e MySQL**
- Abra o XAMPP e inicie Apache e MySQL.

4. **Criar banco de dados**
- Acesse `http://localhost/phpmyadmin` e crie o banco `gestao_servicos` (ou outro nome e ajuste o `.env`).

5. **Importar o schema**
- Importe `config/gestao_servicos_schema.sql` via phpMyAdmin (Import → Escolher arquivo → Executar).

6. **Configurar variáveis de ambiente**
- Copie `config/.env.example` para `config/.env` e preencha as credenciais. Nunca versionar `config/.env`.

7. **Verificar a conexão**
- Confirme `config/db_connect.php` aponta para as variáveis corretas.

8. **Acessar a aplicação**
- Abra: `http://localhost/gestao-servicos-repo` no navegador.

---

## 🔐 Arquivos importantes de configuração

### `config/.env.example`

```env
DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=
DB_NAME=gestao_servicos
GEMINI_API_KEY=your_gemini_key_here
APP_ENV=development
```

> **Atenção:** Não commitar o `config/.env` real. Adicione `config/.env` ao `.gitignore`.


### `config/db_connect.php` (exemplo)

```php
<?php
// config/db_connect.php
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'gestao_servicos';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
return $conn;
```

> Recomendação: considerar PDO com prepared statements em produção.

---

## 🗄️ Schema SQL sugerido (`config/gestao_servicos_schema.sql`)

Exemplo de estruturas iniciais focadas no domínio farmacêutico/clínico:

```sql
CREATE DATABASE IF NOT EXISTS gestao_servicos;
USE gestao_servicos;

-- Usuários (profissionais e admins)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  cargo VARCHAR(80),
  role ENUM('admin','farmaceutico','atendente') DEFAULT 'atendente',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Unidades (CNPJ)
CREATE TABLE unidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  cnpj VARCHAR(20) NOT NULL UNIQUE,
  endereco VARCHAR(255),
  telefone VARCHAR(50),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pacientes
CREATE TABLE pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  dt_nascimento DATE,
  cpf VARCHAR(20),
  telefone VARCHAR(50),
  email VARCHAR(150),
  endereco VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Atendimentos farmacêuticos
CREATE TABLE atendimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  unidade_id INT,
  profissional_id INT,
  tipo ENUM('agudo','cronico') DEFAULT 'agudo',
  motivo TEXT,
  diagnostico TEXT,
  encaminhamentos TEXT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
  FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE SET NULL,
  FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Prescrições / Receituários
CREATE TABLE prescricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  atendimento_id INT NOT NULL,
  medicamento VARCHAR(255),
  dosagem VARCHAR(100),
  via_administracao VARCHAR(50),
  duracao VARCHAR(100),
  observacoes TEXT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (atendimento_id) REFERENCES atendimentos(id) ON DELETE CASCADE
);

-- Log de acessos (para auditoria e conformidade LGPD)
CREATE TABLE auditoria_acessos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT,
  paciente_id INT,
  acao VARCHAR(255),
  detalhe TEXT,
  ip VARCHAR(50),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

> Ajuste conforme a necessidade (ex.: adicionar tabelas de medicamentos, estoque, dispensação, lote e validade se o sistema for integrar gerenciamento de estoque farmacêutico).

---

## 🧪 Padrões de segurança e LGPD

- **Minimização de dados:** armazenar apenas o necessário para atendimento.
- **Consentimento:** registrar consentimento para tratamento quando aplicável.
- **Logs e auditoria:** rastrear acesso a prontuários (tabela `auditoria_acessos`).
- **Criptografia de senhas:** usar `password_hash()` no PHP.
- **Ambiente protegido:** variáveis sensíveis em `.env`, TLS/HTTPS em produção.

---

## 🧭 Roadmap / Sprints (planejamento para entrega ao município)

- **Sprint 1:** levantamento de requisitos, estudo de protocolos e prototipação (wireframes).
- **Sprint 2:** configuração do repositório, README, estrutura inicial e autenticação básica.
- **Sprint 3:** implementação dos módulos de cadastro de pacientes e atendimentos (MVP funcional).
- **Sprint 4:** integração de geração de receituários e relatórios; melhorias de UX.
- **Sprint 5:** testes, documentação (API.md), validação de conformidade LGPD e segurança.
- **Sprint 6:** refinamento final, deploy/entrega ao município e treinamento.

---

## 📌 Boas práticas para o time

- Branch por feature: `feature/<nome>`.
- PRs revisados e descrição clara com screenshots se necessário.
- Testes manuais nas principais jornadas (criar paciente, atender, gerar receituário).
- Documentar endpoints no `API.md`.

---

## 📧 Contato

Para dúvidas sobre o repositório, abra uma **issue** ou marque os responsáveis pelo projeto no GitHub. Use o histórico de conversas do time para alinhar detalhes de protocolo e requisitos clínicos.

---

**Status do Projeto:** Em desenvolvimento 🚧

**Última atualização:** 9 de outubro de 2025


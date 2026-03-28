# InventAI - Controle de Finanças com Sugestões de IA

Um sistema web de controle de despesas e ganhos pessoais com recomendações inteligentes geradas por IA (Google Gemini API).

## 🎯 Funcionalidades

- **Controle de Despesas**: Registrar, visualizar, atualizar e deletar despesas
- **Controle de Ganhos**: Registrar, visualizar, atualizar e deletar ganhos
- **Autenticação**: Login seguro com sessões PHP
- **Sugestões de IA**: Análise inteligente de despesas e recomendações de economia usando Google Gemini API
- **Interface Web**: Frontend responsivo em JavaScript/HTML/CSS

## 🏗️ Arquitetura

### Backend
```
backend/
├── api/
│   └── ai_suggestion.php    # Endpoint de sugestões de IA
└── (Controllers e Services em estrutura MVC)
```

O backend utiliza:
- **PHP 7.4+** com orientação a objetos
- **MySQL** para persistência de dados
- **Google Gemini API** para análise inteligente
- **Padrão MVC** com Controllers e Services

### Frontend
```
Front/
├── index.php               # Página principal
├── api/                    # Chamadas AJAX
│   ├── auth/              # Autenticação
│   ├── despesas/          # Operações CRUD de despesas
│   └── ganhos/            # Operações CRUD de ganhos
└── assets/
    ├── css/               # Estilos
    └── js/                # Scripts JavaScript
```

### Banco de Dados
```
db/
├── conexao.php            # Configuração e conexão
├── schema.sql             # Estrutura das tabelas
└── seed.sql               # Dados iniciais
```

## 🚀 Instalação e Configuração

### Pré-requisitos
- XAMPP (Apache + PHP + MySQL)
- Google Gemini API Key

### Passos

1. **Clone ou copie para XAMPP**
   ```bash
   cd c:\xampp\htdocs
   # Copie a pasta inventai para aqui
   ```

2. **Configure o Banco de Dados**
   ```bash
   # Via phpMyAdmin ou MySQL CLI
   mysql -u root -p < db/schema.sql
   mysql -u root -p < db/seed.sql
   ```

3. **Configure a Conexão do Banco**
   - Edite `db/conexao.php` com suas credenciais MySQL:
   ```php
   $host = 'localhost';
   $db = 'seu_database';
   $usuario = 'seu_usuario';
   $senha = 'sua_senha';
   ```

4. **Configure a Google Gemini API**
   - Obtenha sua chave em: https://ai.google.dev/
   - Adicione a chave no arquivo de configuração apropriado

5. **Inicie o servidor**
   ```bash
   # Inicie Apache e MySQL no XAMPP
   # Acesse: http://localhost/inventai
   ```

## 📁 Estrutura de Pastas

```
inventai/
├── backend/
│   └── api/
│       └── ai_suggestion.php      # Sugestões de IA
├── db/
│   ├── conexao.php                # Conexão MySQL
│   ├── schema.sql                 # Estrutura DB
│   └── seed.sql                   # Dados iniciais
├── Front/
│   ├── index.php                  # Página principal
│   ├── api/
│   │   ├── auth/                  # Login/autenticação
│   │   ├── despesas/              # CRUD despesas
│   │   └── ganhos/                # CRUD ganhos
│   └── assets/
│       ├── css/style.css          # Estilos
│       └── js/main.js             # Scripts
└── README.md                       # Este arquivo
```

## 💰 Como Usar

### 1. Autenticação
- Acesse `http://localhost/inventai`
- Faça login com suas credenciais

### 2. Registrar Despesa
- No painel, clique em "Nova Despesa"
- Preencha descrição, valor e categoria
- Sistema registra a data automaticamente

### 3. Registrar Ganho
- Clique em "Novo Ganho"
- Preencha descrição, valor e fonte
- Dados são salvos no banco

### 4. Obter Sugestões de IA
- Clique em "Sugestões de IA"
- Sistema analisa suas despesas e ganhos
- Receba recomendações inteligentes de economia

## 🤖 Sugestões de IA

O sistema utiliza Google Gemini API para:
- Analisar padrões de gastos
- Identificar áreas de economia
- Propor categorização automática
- Fornecer dicas personalizadas

### Requisição de Exemplo
```javascript
fetch('backend/api/ai_suggestion.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        despesas: [...],
        ganhos: [...]
    })
})
.then(res => res.json())
.then(data => console.log(data.sugestoes));
```

## 🔒 Segurança

- Senhas com hash bcrypt
- Sessões PHP com cookies HttpOnly
- Validação de entrada em backend
- Proteção CSRF com tokens
- Queries preparadas contra SQL injection

## 🛠️ Tecnologias

**Backend:**
- PHP 7.4+
- MySQL 5.7+
- Google Gemini API

**Frontend:**
- HTML5
- CSS3
- JavaScript (vanilla)

**DevOps:**
- XAMPP
- Apache 2.4
- MySQL

## 📝 APIs Principais

### Autenticação
- `POST /Front/api/auth/login.js` - Login
- `POST /Front/api/auth/auth.js` - Validação de sessão

### Despesas
- `GET /Front/api/despesas/read.js` - Listar despesas
- `POST /Front/api/despesas/create.js` - Criar despesa
- `PUT /Front/api/despesas/update.js` - Atualizar despesa
- `DELETE /Front/api/despesas/delete.js` - Deletar despesa

### Ganhos
- `GET /Front/api/ganhos/read.js` - Listar ganhos
- `POST /Front/api/ganhos/create.js` - Criar ganho
- `PUT /Front/api/ganhos/update.js` - Atualizar ganho
- `DELETE /Front/api/ganhos/delete.js` - Deletar ganho

### IA
- `POST /backend/api/ai_suggestion.php` - Obter sugestões

## 🤝 Contribuindo

Para melhorias no projeto:
1. Faça suas alterações
2. Teste antes de enviar
3. Mantenha a estrutura MVC
4. Documente mudanças significativas

## 📄 Licença

Este projeto é de uso pessoal/educacional.

## 📞 Suporte

Para dúvidas ou problemas:
- Verifique se XAMPP está rodando
- Confirme as credenciais do banco de dados
- Teste a conexão com Google Gemini API
- Verifique os logs de erro do Apache

---

**Versão:** 1.0  
**Última atualização:** 2026-03-28  
**Status:** Em desenvolvimento

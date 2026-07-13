# Arquitetura

## Visão Geral

O RWDEV é um projeto PHP com MySQL, hospedado em ambiente Hostinger, composto por:

- Site institucional público.
- APIs auxiliares.
- Portal do cliente.
- Painel administrativo.
- Módulos internos administrativos.
- Scripts SQL de estrutura e evolução.

O sistema utiliza PHP procedural organizado por páginas e includes compartilhados.

## Estrutura das Pastas

```text
api/                         # Endpoints auxiliares
css/                         # CSS do site institucional
js/                          # JS do site institucional
images/                      # Imagens, logos e ícones
portal/admin/                # Painel administrativo
portal/admin/ponto/          # SONI PONTO
portal/admin/documentos-trabalho/ # Documentos do Trabalho
portal/cliente/              # Área do cliente
portal/config/               # Conexão PDO
portal/includes/             # Funções globais e autenticação
portal/assets/               # Assets do portal
portal/uploads/              # Uploads do portal
sql/                         # SQL incremental
```

## Fluxo das Requisições

1. O usuário acessa uma página pública, cliente ou administrativa.
2. A página inclui arquivos de configuração e helpers.
3. Páginas protegidas validam sessão.
4. A página consulta ou altera o banco via PDO.
5. A resposta é renderizada em HTML, JSON ou redirecionamento.

## Fluxo de Login

### Admin

1. Acesso em `portal/admin/login.php`.
2. Busca por e-mail na tabela `admins`.
3. Validação de senha com `password_verify`.
4. Criação de sessão `admin_id` e `admin_nome`.
5. Redirecionamento para `dashboard.php`.

### Cliente

1. Acesso em `portal/cliente/login.php`.
2. Busca por e-mail na tabela `clientes`.
3. Validação de senha.
4. Criação de sessão `cliente_id` e `cliente_nome`.
5. Redirecionamento para dashboard do cliente.

## Autorização

As funções de autorização ficam em `portal/includes/auth.php`.

- `exigir_admin()` bloqueia acesso sem sessão admin.
- `exigir_cliente()` bloqueia acesso sem sessão cliente.
- O sistema evita sessão simultânea admin/cliente no mesmo contexto.

## Banco de Dados

O banco principal é MySQL/MariaDB.

Conexão:

- Arquivo: `portal/config/conexao.php`
- Interface: PDO
- Charset: `utf8mb4`
- Erros: exceções PDO

Scripts:

- `u724577237_rwdev_portal.sql`
- `sql/soni_ponto.sql`
- `sql/documentos_trabalho.sql`

## Módulos

### Site Institucional

Páginas HTML públicas para apresentação da RWDEV, serviços, contato, parceiros, depoimentos e política de privacidade.

### Depoimentos

Permite envio público, moderação administrativa, aprovação, recusa, exclusão e reações.

### Diagnóstico

Ferramenta pública de diagnóstico comercial com rastreamento de eventos e painel administrativo de métricas.

### Portal do Cliente

Permite acesso do cliente, visualização de projetos e criação/acompanhamento de solicitações.

### Painel Administrativo

Centraliza clientes, convites, projetos, solicitações, depoimentos, diagnóstico, SONI PONTO e Documentos do Trabalho.

### SONI PONTO

Registra lojas, trajetos, pontos de trabalho, horários, observações, transporte e resumo mensal.

### Documentos do Trabalho

Organiza documentos profissionais por categoria, empresa, cargo, data, validade, arquivo e vínculo opcional com SONI PONTO.

## Uploads

- Solicitações de clientes: `portal/uploads/solicitacoes/`.
- Documentos do trabalho: `portal/uploads/documentos-trabalho/`.

Uploads reais não devem ser versionados.

## Segurança

- Uso de prepared statements.
- Escapamento HTML com `e()`.
- CSRF em formulários administrativos.
- Uploads com validação de extensão e MIME nos módulos novos.
- Credenciais em `.env`, fora do Git.

## Evolução Recomendada

- Criar include único para header admin.
- Centralizar helpers compartilhados.
- Melhorar segurança de sessão.
- Criar download autenticado para todos os uploads.
- Padronizar encoding UTF-8.

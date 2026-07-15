# RWDEV

RWDEV é a plataforma institucional e administrativa da RWDEV, criada para apresentar serviços digitais, captar oportunidades comerciais, gerenciar clientes, organizar projetos e centralizar rotinas internas de trabalho.

O projeto reúne site institucional, portal do cliente, painel administrativo, diagnóstico comercial, depoimentos, solicitações de alteração, SONI PONTO e Documentos do Trabalho.

Projeto online: <https://rwdev.com.br>

## Objetivos da Plataforma

- Apresentar a RWDEV, seus serviços, portfólio, parceiros e canais de contato.
- Receber depoimentos públicos com moderação administrativa.
- Captar leads por meio da ferramenta de diagnóstico.
- Organizar clientes, projetos e solicitações de alteração.
- Registrar pontos de trabalho, lojas, trajetos e resumo mensal.
- Armazenar documentos profissionais com upload protegido.
- Manter uma base simples, compatível com hospedagem compartilhada Hostinger.

## Tecnologias Utilizadas

- PHP
- MySQL
- JavaScript
- HTML5
- CSS3
- Git
- GitHub
- Hostinger

## Estrutura das Pastas

```text
rwdev/
├── api/                         # Endpoints auxiliares e notificações
├── audio/                       # Arquivos de áudio do site
├── css/                         # CSS do site institucional
├── images/                      # Logos, ícones e imagens do portfólio
├── js/                          # JavaScript do site institucional
├── portal/
│   ├── admin/                   # Painel administrativo
│   │   ├── documentos-trabalho/ # Módulo Documentos do Trabalho
│   │   └── ponto/               # Módulo SONI PONTO
│   ├── assets/                  # CSS e JS do portal
│   ├── cliente/                 # Área do cliente
│   ├── config/                  # Conexão PDO e configuração de banco
│   ├── includes/                # Funções globais, autenticação e notificações
│   └── uploads/                 # Uploads protegidos ou controlados do portal
├── sql/                         # Scripts SQL incrementais de módulos
├── uploads/                     # Uploads públicos/legados
├── *.html                       # Páginas institucionais
├── config.php                   # Configuração legada/global do site
├── u724577237_rwdev_portal.sql  # Dump principal do banco do portal
├── README.md                    # Documentação principal
└── .env.example                 # Modelo de variáveis de ambiente
```

## Como Instalar

1. Clone o repositório:

```bash
git clone <url-do-repositorio>
cd rwdev
```

2. Crie o arquivo `.env` com base em `.env.example`.

3. Configure as credenciais do banco:

```env
DB_HOST=localhost
DB_NAME=seu_banco
DB_USER=seu_usuario
DB_PASS=sua_senha
```

4. Configure um servidor local com PHP e MySQL.

5. Aponte o servidor para a raiz do projeto.

## Como Configurar

- O portal usa `portal/config/conexao.php` para carregar variáveis do `.env` e criar a conexão PDO.
- O timezone padrão é `America/Sao_Paulo`.
- O arquivo `.env` não deve ser versionado.
- Uploads reais e documentos pessoais não devem ser commitados.
- O domínio de produção é definido em constantes como `BASE_URL`.

## Como Importar o Banco

1. Crie o banco MySQL.
2. Importe o dump principal:

```text
u724577237_rwdev_portal.sql
```

3. Importe os scripts incrementais necessários:

```text
sql/soni_ponto.sql
sql/documentos_trabalho.sql
```

4. Confirme se as tabelas foram criadas corretamente no phpMyAdmin.

## Como Publicar na Hostinger

1. Faça backup dos arquivos e do banco atual.
2. Envie os arquivos para `public_html` ou diretório equivalente.
3. Configure o `.env` com as credenciais reais do banco.
4. Importe os SQLs pelo phpMyAdmin.
5. Verifique permissões de pastas de upload.
6. Teste login admin, portal do cliente, diagnóstico, depoimentos, SONI PONTO e Documentos do Trabalho.
7. Confirme que arquivos sensíveis não estão acessíveis diretamente.

Mais detalhes estão em `DEPLOY.md`.

## Como Utilizar Git

## Sprint 1.6 - Administradores e Convites Administrativos

A Sprint 1.6 adiciona gestao de administradores em `portal/admin/administradores.php`.

- Perfis: Superadministrador, Administrador de modulo e Visualizador.
- Permissoes por modulo/acao, como `ponto.criar`, `lojas.editar`, `admins.convidar`.
- Convite administrativo separado de `convites_cliente`.
- Token de convite salvo somente como hash SHA-256.
- Ativacao em `portal/admin/ativar-admin.php`, com senha definida pelo convidado.
- Menu administrativo dinamico e identificacao do usuario logado no topo.

SQL incremental:

```text
sql/admin_convites_permissoes_v1.sql
```

Para convidar Marquinhos, use Administradores > Convidar administrador, informe o e-mail real, mantenha o perfil Administrador de modulo e deixe marcadas apenas as permissoes de Dashboard, Soni Ponto, Resumo mensal, Lojas e Trajetos. Nao marque Documentos, Clientes, Convites, Projetos, Solicitacoes, Depoimentos, Diagnostico, Administradores nem configuracoes gerais.

Fluxo básico:

```bash
git status
git add .
git commit -m "Descreve a alteração"
git push
```

Antes de commitar:

- Revise `git status`.
- Evite versionar `.env`, PDFs reais, backups, dumps temporários e uploads reais.
- Rode validações de sintaxe PHP quando alterar arquivos `.php`.
- Separe commits por objetivo.

## Fluxo Recomendado de Desenvolvimento

1. Criar ou atualizar uma branch de trabalho.
2. Implementar a alteração localmente.
3. Testar fluxo afetado.
4. Rodar `php -l` nos arquivos PHP alterados.
5. Rodar `git diff --check`.
6. Revisar `git diff`.
7. Commitar com mensagem clara.
8. Publicar e validar na Hostinger.

## Como Criar Novos Módulos

Para módulos administrativos:

1. Criar pasta em `portal/admin/nome-do-modulo/`.
2. Incluir `portal/config/conexao.php` e `portal/includes/auth.php`.
3. Chamar `exigir_admin()`.
4. Usar prepared statements.
5. Reutilizar `portal/assets/css/style.css`.
6. Criar SQL incremental em `sql/`.
7. Atualizar menu admin.
8. Documentar em `CHANGELOG.md`, `DATABASE.md` e `ARCHITECTURE.md`.

## Autenticação

A autenticação fica em `portal/includes/auth.php`.

- `exigir_admin()` protege páginas administrativas.
- `exigir_cliente()` protege páginas do cliente.
- Sessões usam `$_SESSION['admin_id']` e `$_SESSION['cliente_id']`.
- Quando um tipo de usuário acessa uma área incompatível, o sistema redireciona para a área correta.

## Painel Administrativo

O painel admin fica em `portal/admin/`.

Principais áreas:

- Dashboard
- Clientes
- Convites
- Projetos
- Solicitações
- Depoimentos
- Diagnóstico
- SONI PONTO
- Documentos do Trabalho

Cada página administrativa deve validar sessão admin e usar PDO.

## Boas Práticas do Projeto

- Usar PHP com PDO e prepared statements.
- Validar dados no backend.
- Não exibir erros sensíveis em produção.
- Proteger uploads reais.
- Não versionar documentos pessoais, PDFs reais ou credenciais.
- Manter HTML semântico e CSS reaproveitável.
- Criar documentação para novos módulos.
- Manter scripts SQL incrementais em `sql/`.
- Testar compatibilidade com PHP 8.x e ambiente Hostinger.

## Documentação Complementar

- `ARCHITECTURE.md`
- `DATABASE.md`
- `DEPLOY.md`
- `CHANGELOG.md`
- `ROADMAP.md`
- `TODO.md`
- `VERSION.md`
- `CONTRIBUTING.md`
- `melhorias-futuras-portal-rwdev.md`

## Responsável

Ricardo Sousa  
RWDEV

## Sprint 1.7 - Central de Auditoria

A Central de Auditoria fica em `portal/admin/auditoria.php` e aparece no menu apenas para superadministrador. Ela registra acoes administrativas internas, com filtros, detalhes, impressao e exportacao CSV. O registro e feito pelo helper `portal/includes/auditoria.php`, que remove senhas, tokens, CSRF, cookies, dados de sessao e outros segredos antes de gravar.

SQL incremental:

```text
sql/auditoria_admin_v1.sql
```

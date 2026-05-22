# Melhorias futuras do Portal RWDEV

Este arquivo registra os pontos que devem ser ajustados depois, antes de divulgar o Portal do Cliente RWDEV para mais clientes.

## Prioridade alta

### 1. Remover arquivo de teste da hospedagem

Arquivo:

- `portal/teste-conexao.php`

Esse arquivo foi útil para testar o banco, mas em produção não precisa ficar público.

### 2. Proteger melhor a sessão

Arquivo principal:

- `portal/includes/funcoes.php`

Melhorar a configuração da sessão usando cookies mais seguros:

- `secure`
- `httponly`
- `samesite`

Isso reduz riscos caso alguém tente capturar ou manipular sessão.

### 3. Corrigir encoding dos arquivos

Alguns textos aparecem como:

- `SolicitaÃ§Ã£o`
- `InÃ­cio`
- `PolÃ­tica`

O ideal é padronizar todos os arquivos como UTF-8 para evitar texto quebrado no navegador.

### 4. Atualizar LGPD / Política de Privacidade

Arquivo:

- `politica-de-privacidade.html`

Adicionar uma seção específica sobre o Portal do Cliente RWDEV, explicando que o sistema pode tratar:

- nome
- e-mail
- WhatsApp
- empresa
- projetos/sites
- solicitações enviadas
- arquivos anexados pelo cliente
- histórico de atendimento

Também é importante informar finalidade, retenção e canal de solicitação de exclusão/alteração dos dados.

## Prioridade média

### 5. Melhorar segurança dos uploads

Arquivos principais:

- `portal/includes/funcoes.php`
- `portal/admin/solicitacoes.php`

Hoje os arquivos enviados ficam em `portal/uploads/solicitacoes/`.

O ideal no futuro é não acessar os arquivos por link direto. Melhor seria criar um arquivo PHP autenticado para download, verificando se quem está acessando é admin ou o cliente dono da solicitação.

### 6. Trocar `mail()` por SMTP

Arquivo:

- `portal/includes/notificacoes.php`

Hoje o aviso para admin usa `mail()`. Pode funcionar, mas em hospedagem pode falhar ou cair em spam.

Melhor usar SMTP autenticado, por exemplo:

- e-mail profissional da Hostinger
- PHPMailer
- autenticação SMTP com usuário e senha próprios

### 7. Limitar tentativas de login

Arquivos:

- `portal/cliente/login.php`
- `portal/admin/login.php`

Adicionar proteção contra muitas tentativas de senha errada.

Ideias:

- bloquear por alguns minutos após várias tentativas
- registrar IP/data
- captcha no admin, se necessário

## Prioridade baixa

### 8. Ocultar melhor o admin

O admin está em:

- `/portal/admin/login.php`

Não aparece no site, mas a URL é previsível. No futuro pode ser interessante trocar para um caminho menos óbvio ou adicionar uma camada simples de proteção.

### 9. Melhorar gerenciamento dos convites

Tela:

- `portal/admin/convites.php`

Ideias futuras:

- botão para expirar convite manualmente
- botão para gerar novo convite para o mesmo cliente
- filtro por status: pendente, usado, expirado
- busca por nome, empresa ou WhatsApp

### 10. Melhorar dashboard administrativo

Tela:

- `portal/admin/dashboard.php`

Ideias:

- últimas solicitações com destaque visual
- solicitações atrasadas
- quantidade por status
- cliente com mais solicitações

## Observação

O sistema atual está bom para teste e MVP controlado. Antes de usar com muitos clientes e arquivos reais, o ideal é aplicar pelo menos os itens de prioridade alta.

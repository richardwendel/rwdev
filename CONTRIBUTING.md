# Guia de Contribuição

Este documento define o padrão de contribuição para o projeto RWDEV.

## Fluxo Git

1. Atualize a branch principal.
2. Crie uma branch para a alteração.
3. Desenvolva e teste localmente.
4. Revise o diff.
5. Faça commit com mensagem clara.
6. Envie para o GitHub.
7. Publique na Hostinger somente após validação.

## Como Criar Branch

Use nomes curtos, em minúsculas e separados por hífen.

Exemplos:

```bash
git checkout -b feature/soni-ponto
git checkout -b fix/upload-documentos
git checkout -b docs/atualiza-readme
```

## Como Fazer Commit

Use mensagens objetivas, em português.

Exemplos:

```bash
git commit -m "Cria módulo Soni Ponto"
git commit -m "Corrige validação de upload"
git commit -m "Atualiza documentação do deploy"
```

## Padrão de Nomes

- Pastas: minúsculas e com hífen quando necessário.
- Arquivos PHP: minúsculos, descritivos e com hífen quando necessário.
- Tabelas: minúsculas, no plural ou nome composto claro.
- Campos: `snake_case`.
- CSS: classes descritivas e reutilizáveis.

## Padrão de Código PHP

- Usar `declare(strict_types=1)` em novos arquivos PHP.
- Usar PDO com prepared statements.
- Validar dados no backend.
- Proteger páginas administrativas com `exigir_admin()`.
- Proteger páginas do cliente com `exigir_cliente()`.
- Evitar exibir erros sensíveis em produção.
- Usar `e()` para escapar saída HTML.

## Padrão de CSS

- Reutilizar `portal/assets/css/style.css` no portal.
- Reutilizar `css/style.css` no site institucional.
- Manter classes simples e sem excesso de especificidade.
- Garantir responsividade em mobile e desktop.
- Evitar estilos inline, exceto quando houver necessidade pontual.

## Padrão de JavaScript

- Usar JavaScript simples.
- Evitar dependências externas desnecessárias.
- Manter scripts em arquivos próprios quando forem reutilizáveis.
- Garantir que formulários funcionem mesmo com validação backend.

## Estrutura dos Módulos

Módulos administrativos devem seguir o padrão:

```text
portal/admin/nome-do-modulo/
├── index.php
├── novo.php
├── editar.php
├── excluir.php
└── _funcoes.php
```

Quando necessário, adicionar:

- `visualizar.php`
- `categorias.php`
- `_form-*.php`
- SQL incremental em `sql/`

## Checklist Antes do Commit

- [ ] `git status` revisado.
- [ ] Nenhum `.env` versionado.
- [ ] Nenhum PDF real versionado.
- [ ] Nenhum upload real versionado.
- [ ] `php -l` executado nos PHPs alterados.
- [ ] `git diff --check` executado.
- [ ] Documentação atualizada quando necessário.

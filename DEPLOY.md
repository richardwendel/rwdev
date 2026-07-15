# Deploy

## Deploy SONI PONTO - Sprint 1 Lojas e Trajetos

Ordem recomendada na Hostinger:

1. Fazer backup completo dos arquivos.
2. Fazer backup completo do banco via phpMyAdmin.
3. Executar `sql/soni_ponto_lojas_trajetos_v2.sql` no phpMyAdmin.
4. Conferir se as colunas novas e as foreign keys de ida/volta foram criadas.
5. Enviar os arquivos PHP, JS, CSS e docs alterados.
6. Testar login admin.
7. Testar `portal/admin/ponto/lojas.php`.
8. Testar `portal/admin/ponto/trajetos.php`.
9. Testar novo ponto com loja que possui trajetos.
10. Testar ida e volta diferentes.
11. Testar troca de loja limpando os selects.
12. Testar loja sem trajeto sem bloquear o cadastro.
13. Testar visual mobile do formulário de ponto.

Este documento descreve o processo recomendado para publicar o projeto RWDEV na Hostinger.

## Ambiente

- Hospedagem: Hostinger
- Linguagem: PHP
- Banco: MySQL/MariaDB
- Gerenciador de banco: phpMyAdmin
- Domínio: `rwdev.com.br`

## Antes do Deploy

- [ ] Revisar `git status`.
- [ ] Confirmar que `.env` não será enviado ao Git.
- [ ] Confirmar que PDFs reais e uploads reais não serão versionados.
- [ ] Rodar `php -l` nos arquivos PHP alterados.
- [ ] Rodar `git diff --check`.
- [ ] Fazer backup dos arquivos atuais.
- [ ] Fazer backup do banco atual.
- [ ] Revisar scripts SQL novos.

## Backup

### Arquivos

Baixe uma cópia dos arquivos atuais da hospedagem pelo Gerenciador de Arquivos ou FTP.

### Banco

No phpMyAdmin:

1. Selecione o banco.
2. Clique em Exportar.
3. Use formato SQL.
4. Salve o arquivo com data no nome.

Exemplo:

```text
backup-rwdev-2026-07-06.sql
```

## Publicação de Arquivos

1. Gere ou selecione os arquivos prontos para produção.
2. Envie para `public_html` ou diretório configurado na Hostinger.
3. Não envie arquivos locais sensíveis:
   - `.env` local com credenciais erradas.
   - PDFs reais não necessários.
   - backups `.sql`.
   - arquivos temporários.
4. Confirme que `.htaccess` foi enviado.

## Configuração do `.env`

Na hospedagem, configure:

```env
DB_HOST=localhost
DB_NAME=nome_do_banco
DB_USER=usuario_do_banco
DB_PASS=senha_do_banco
```

O `.env` de produção deve ficar protegido e nunca deve ser commitado.

## Importação SQL

No phpMyAdmin:

1. Selecione o banco.
2. Clique em Importar.
3. Envie o dump principal, se for instalação nova:

```text
u724577237_rwdev_portal.sql
```

4. Importe scripts incrementais conforme necessário:

```text
sql/soni_ponto.sql
sql/documentos_trabalho.sql
```

5. Verifique se as tabelas foram criadas.

## Atualização

Para atualizar uma instalação existente:

1. Faça backup.
2. Envie somente arquivos alterados.
3. Importe apenas SQLs incrementais necessários.
4. Teste rotas principais.
5. Verifique logs de erro da hospedagem.

## Rollback

Se algo falhar:

1. Reenvie os arquivos do backup anterior.
2. Restaure o banco pelo phpMyAdmin.
3. Limpe cache do navegador, se necessário.
4. Teste login admin e páginas públicas.

## Checklist Pós-Deploy

- [ ] Site inicial abre corretamente.
- [ ] Formulário de contato funciona.
- [ ] Depoimentos carregam.
- [ ] Diagnóstico registra eventos.
- [ ] Login admin funciona.
- [ ] Login cliente funciona.
- [ ] Solicitações funcionam.
- [ ] SONI PONTO abre e salva registro.
- [ ] Documentos do Trabalho abre e protege uploads.
- [ ] Arquivos sensíveis não ficam públicos.
- [ ] `robots.txt` e `sitemap.xml` continuam acessíveis.

## Cuidados com Uploads

- `portal/uploads/solicitacoes/` armazena anexos de solicitações.
- `portal/uploads/documentos-trabalho/` armazena documentos profissionais protegidos.
- Não versionar arquivos reais dessas pastas.
- Testar se `portal/uploads/documentos-trabalho/arquivo.pdf` não abre diretamente pelo navegador.

## Comandos Úteis

```bash
git status
git diff --check
git add .
git commit -m "Atualiza projeto"
git push
```

## Deploy Sprint 1.6 - Administradores

Ordem exata na Hostinger:

1. Fazer backup completo dos arquivos.
2. Fazer backup completo do banco no phpMyAdmin.
3. Executar `sql/admin_convites_permissoes_v1.sql`.
4. Conferir se `admins` recebeu `perfil`, `ativo`, `ultimo_acesso`, `atualizado_em` e `criado_por`.
5. Conferir se `admin_permissoes` e `convites_admin` existem.
6. Enviar os arquivos PHP/CSS/docs alterados.
7. Testar login do superadministrador atual.
8. Acessar `portal/admin/administradores.php`.
9. Gerar convite administrativo para Marquinhos com e-mail real.
10. Copiar o link, ativar a conta e definir senha.
11. Testar login do administrador de modulo.
12. Confirmar menu reduzido: Dashboard, Soni Ponto e Sair.
13. Testar CRUD de ponto, lojas e trajetos.
14. Tentar acessar Documentos, Clientes e Administradores por URL direta e confirmar HTTP 403.
15. Desativar a conta apos o teste em Administradores.

Rollback:

1. Reenviar os arquivos do backup anterior.
2. Restaurar o banco salvo antes da migration.
3. Testar login admin.
4. Se a restauracao completa nao for possivel, desative contas de teste em `admins.ativo = 0` e revogue convites pendentes em `convites_admin.status = 'revogado'`.

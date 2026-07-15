# TODO

## SONI PONTO

- Validar a migration `sql/soni_ponto_status_dia_escala_v15.sql` em cópia do banco de produção antes do deploy.
- Evoluir a escala de domingo para calendário visual na próxima sprint, sem automatizar alteração de registros.

- Validar a migration `sql/soni_ponto_lojas_trajetos_v2.sql` em cópia do banco de produção antes do deploy.
- Planejar Sprint 2 para Transportes Inteligentes sem misturar com a base de lojas/trajetos.

## Alta

- Validar melhor a ordem cronológica dos horários no SONI PONTO.
- Melhorar armazenamento de documentos fora do diretório público quando possível.
- Revisar segurança das sessões com cookies `secure`, `httponly` e `samesite`.
- Padronizar encoding UTF-8 em todos os arquivos.
- Revisar LGPD e política de privacidade para cobrir portal, uploads e documentos.

## Média

- Melhorar filtros do SONI PONTO.
- Criar dashboard geral consolidado.
- Adicionar relatórios de banco de horas.
- Melhorar upload de anexos de solicitações com download autenticado.
- Trocar envio de e-mail via `mail()` por SMTP.
- Adicionar limite de tentativas de login.

## Baixa

- Centralizar futuramente o header/menu administrativo em um include único para evitar duplicidade entre páginas.
- Melhorar tooltips.
- Criar classe visual para alertas informativos.
- Melhorar microcopy dos formulários.
- Adicionar mais estados vazios nas tabelas.
- Criar documentação visual do fluxo do painel.
- Melhorar destaque do menu ativo.

## TODO - Sprint 1.6

- [ ] Aplicar `sql/admin_convites_permissoes_v1.sql` em producao antes dos arquivos.
- [ ] Convidar Marquinhos pelo fluxo administrativo usando e-mail real.
- [ ] Desativar a conta de teste apos validacao do SONI PONTO.
- [ ] Validar manualmente todos os cenarios de convite expirado, revogado e usado.
- [ ] Criar futuramente uma tela de consulta de auditoria.

## TODO - Sprint 1.7

- [ ] Aplicar `sql/auditoria_admin_v1.sql` em producao antes dos arquivos.
- [ ] Definir politica de retencao dos registros de auditoria.
- [ ] Testar volume crescente de registros em producao.
- [ ] Revisar periodicamente os eventos auditados conforme novos modulos surgirem.

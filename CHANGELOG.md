# Changelog

## 2.1.5

Data: 2026-07-14

### Adicionado

- Migration incremental `sql/soni_ponto_status_dia_escala_v15.sql`.
- Campo `status_dia` para classificar dias trabalhados, folgas, integração/treinamento, feriado, falta, atestado, férias e outro.
- Ocultação automática de horários, transporte e trajetos quando o status não é Trabalhado.
- Cartões de resumo mensal por status do dia.
- Escala informativa de domingo no ciclo 2 domingos trabalhados para 1 domingo de folga.

### Melhorias

- Registros sem jornada podem ser salvos sem loja, trajetos ou transporte.
- Resumo e listagem preservam dias sem loja usando relacionamento opcional.

## 2.1.0

Data: 2026-07-13

### Adicionado

- Migration incremental `sql/soni_ponto_lojas_trajetos_v2.sql`.
- Padrão de código técnico de lojas `LJ01`, `LJ02`, `LJ03`...
- Campos opcionais de loja: número interno, responsável, telefone, horário padrão e cor.
- Selects de trajeto de ida e volta filtrados pela loja.
- Vínculo opcional de ponto com trajeto de ida e trajeto de volta.

### Melhorias

- Cadastro de trajetos como opção de caminho por loja.
- Tratamento de lojas sem trajeto sem bloquear o cadastro do ponto.

Todas as mudanças relevantes do projeto RWDEV devem ser registradas neste arquivo.

O projeto utiliza versionamento semântico no formato `MAJOR.MINOR.PATCH`.

## 2.0.0

Data: 2026-07-06

### Adicionado

- SONI PONTO.
- Cadastro de lojas de trabalho.
- Cadastro de trajetos de trabalho.
- Cadastro de registros de ponto.
- Resumo mensal de horas, lojas, transporte e bilhetes perdidos.
- Botão "Agora" para preenchimento rápido de horários.
- Data automática no novo ponto.
- Dia da semana automático.
- Documentos do Trabalho.
- Cadastro de categorias de documentos.
- Upload seguro de PDF, JPG, JPEG e PNG.
- Pasta protegida para documentos de trabalho.
- Integração entre Documentos do Trabalho e SONI PONTO.
- Scripts SQL incrementais em `sql/`.
- Documentação oficial do projeto.

### Melhorias

- Organização do painel administrativo.
- Reforço de segurança em uploads.
- Padronização de documentação.
- Melhoria de responsividade em formulários e tabelas.
- Melhoria no fluxo de desenvolvimento com Git.

### Correções

- Proteção contra versionamento acidental de documentos pessoais.
- Ajustes de validação de horários no backend.
- Exibição de horários com segundos quando necessário.

## 1.0.0

### Adicionado

- Site institucional RWDEV.
- Portal do cliente.
- Painel administrativo.
- Cadastro de clientes por convite.
- Cadastro de projetos.
- Solicitações de alteração.
- Upload de anexos em solicitações.
- Sistema de depoimentos.
- Moderação administrativa de depoimentos.
- Diagnóstico comercial.
- Métricas administrativas do diagnóstico.

# Banco de Dados

Este documento descreve as tabelas utilizadas pelo projeto RWDEV.

## Arquivos SQL

- `u724577237_rwdev_portal.sql`: dump principal do portal.
- `sql/soni_ponto.sql`: tabelas do SONI PONTO.
- `sql/documentos_trabalho.sql`: tabelas de Documentos do Trabalho.

## Tabelas Principais

### admins

Usuários administrativos.

Campos principais:

- `id`: chave primária.
- `nome`: nome do administrador.
- `email`: e-mail único.
- `senha`: hash da senha.
- `criado_em`: data de criação.

Índices:

- Primária em `id`.
- Única em `email`.

### clientes

Clientes com acesso ao portal.

Campos principais:

- `id`
- `nome`
- `email`
- `senha`
- `empresa`
- `telefone`
- `status`
- `criado_em`

Índices:

- Primária em `id`.
- Única em `email`.

### convites_cliente

Convites privados para cadastro de clientes.

Campos principais:

- `token`
- `nome`
- `empresa`
- `email`
- `telefone`
- `projeto_nome`
- `projeto_dominio`
- `paginas_json`
- `status`
- `expira_em`
- `usado_em`

### projetos

Projetos/sites vinculados a clientes.

Campos principais:

- `id`
- `cliente_id`
- `nome`
- `dominio`
- `descricao`
- `status`
- `criado_em`

Relacionamentos:

- `cliente_id` referencia `clientes.id`.

### paginas_projeto

Páginas pertencentes a um projeto.

Campos principais:

- `id`
- `projeto_id`
- `nome_pagina`

Relacionamentos:

- `projeto_id` referencia `projetos.id`.

### solicitacoes

Solicitações de alteração feitas pelos clientes.

Campos principais:

- `id`
- `cliente_id`
- `projeto_id`
- `pagina`
- `tipo_alteracao`
- `descricao`
- `status`
- `resposta_admin`
- `criado_em`
- `atualizado_em`

Relacionamentos:

- `cliente_id` referencia `clientes.id`.
- `projeto_id` referencia `projetos.id`.

### arquivos_solicitacao

Anexos enviados em solicitações.

Campos principais:

- `id`
- `solicitacao_id`
- `nome_original`
- `nome_arquivo`
- `caminho`
- `tipo`
- `tamanho`
- `criado_em`

Relacionamentos:

- `solicitacao_id` referencia `solicitacoes.id`.

### depoimentos

Depoimentos públicos enviados por visitantes.

Campos principais:

- `id`
- `nome`
- `cidade`
- `rede_social`
- `foto`
- `depoimento`
- `resposta_admin`
- `respondido_em`
- `tempo_conhece`
- `autorizacao`
- `status`
- `criado_em`

### depoimento_reacoes

Reações de visitantes aos depoimentos.

Campos principais:

- `id`
- `depoimento_id`
- `tipo`
- `identificador_usuario`
- `criado_em`
- `atualizado_em`

Relacionamentos:

- `depoimento_id` referencia `depoimentos.id`.

### diagnostico_eventos

Eventos da ferramenta de diagnóstico.

Campos principais:

- `event_type`
- `page`
- `referer`
- `ip_hash`
- `user_agent_hash`
- `created_at`

### diagnostico_leads

Leads gerados pelo diagnóstico.

Campos principais:

- `empresa`
- `cidade`
- `responsavel`
- `whatsapp`
- `email`
- `pontuacao`
- `classificacao`
- `origem`
- `status`
- `respostas_json`
- `clicou_whatsapp`
- `created_at`
- `updated_at`

### logs_seguranca

Registros de eventos de segurança.

Campos principais:

- `tipo_evento`
- `email`
- `ip`
- `tipo_usuario`
- `mensagem`
- `criado_em`

### tentativas_login

Tentativas de login para controle futuro de bloqueio.

Campos principais:

- `email`
- `ip`
- `tipo_usuario`
- `sucesso`
- `criado_em`

## SONI PONTO

### lojas_trabalho

Lojas onde o trabalho é realizado.

Campos:

- `id`
- `codigo_loja`
- `nome`
- `endereco`
- `cidade`
- `observacoes`
- `ativo`
- `criado_em`
- `atualizado_em`

Índices:

- Único em `codigo_loja`.
- Índice em `ativo`.

### trajetos_trabalho

Trajetos usados para deslocamento até lojas.

Campos:

- `id`
- `loja_id`
- `nome_trajeto`
- `tipo_transporte`
- `valor_ida`
- `valor_volta`
- `valor_total`
- `tempo_medio`
- `observacoes`
- `ativo`
- `criado_em`
- `atualizado_em`

Relacionamentos:

- `loja_id` referencia `lojas_trabalho.id`.

### pontos_trabalho

Registros de ponto.

Campos:

- `id`
- `data`
- `dia_semana`
- `loja_id`
- `entrada`
- `cafe_saida`
- `cafe_retorno`
- `almoco_saida`
- `almoco_retorno`
- `saida`
- `transporte_observacao`
- `gasto_transporte`
- `bilhetes_perdidos`
- `valor_bilhetes_perdidos`
- `observacoes`
- `criado_em`
- `atualizado_em`

Relacionamentos:

- `loja_id` referencia `lojas_trabalho.id`.

## Documentos do Trabalho

### documentos_trabalho_categorias

Categorias para documentos profissionais.

Campos:

- `id`
- `nome`
- `ativo`
- `criado_em`
- `atualizado_em`

Índices:

- Único em `nome`.

### documentos_trabalho

Documentos profissionais armazenados no painel.

Campos:

- `id`
- `titulo`
- `categoria`
- `empresa`
- `cargo`
- `data_documento`
- `data_validade`
- `arquivo`
- `observacoes`
- `ativo`
- `ponto_id`
- `criado_em`
- `atualizado_em`

Relacionamentos:

- `ponto_id` é vínculo opcional com SONI PONTO.

Índices:

- `categoria`
- `empresa`
- `data_documento`
- `ponto_id`

## Observações

- O banco usa `utf8mb4`.
- As tabelas principais usam `InnoDB`.
- Uploads reais não devem ser armazenados no Git.
- Scripts incrementais devem ser documentados ao criar novos módulos.

# SONI PONTO

## Implantação

1. Faça backup externo e valide em homologação.
2. Execute `sql/soni_ponto_migration_20260726_acompanhamento.sql`.
3. Para a etapa de transportes, execute `sql/soni_ponto_migration_20260727_transportes_reembolsos.sql`
   e depois o bloco configurável de `SQL_MANUAL_TRANSPORTES.md`.
3. Cadastre uma configuração de jornada com vigência. Até isso ocorrer, a interface deve informar “jornada não configurada”.
4. Não importe nem versione o dump de produção. O arquivo em `sql/` é apenas referência local.

## Regras

- `feriado` é legado e permanece sem conclusão automática; a revisão para `feriado_folgado` ou `feriado_trabalhado` é manual.
- Café e almoço são períodos independentes e podem ocorrer em qualquer ordem. Cada par deve estar completo e dentro da permanência.
- Dias remunerados seguem a configuração vigente e são estimativa administrativa, não parecer jurídico.
- RHID e SONI PONTO são armazenados separadamente.
- Valores previsto/recebido/gasto são snapshots do dia e não mudam com futura alteração de tarifa.
- Competência fechada bloqueia edição e exclusão comum; reabertura exige justificativa e histórico.

## Testes

Execute `php portal/admin/ponto/tests/calculos_test.php`. O teste cobre intervalos em ordens distintas,
marcações incompletas, feriados, domingos, transporte e os 26 registros esperados em julho.
Execute também `php portal/admin/ponto/tests/gestao_test.php` para validar sobreposição de vigências,
comparação de marcações, ciclo de direitos, ocorrências, fechamento, reabertura e histórico.

## Interfaces de gestão

- `configuracoes.php`: jornadas por vigência, sem valor presumido.
- `rhid.php?ponto_id=ID`: marcações RHID separadas e comparação lado a lado.
- `direitos.php`: aquisição, agenda, uso e cancelamento de direitos.
- `ocorrencias.php`: registro e resolução de ocorrências estruturadas.
- `competencias.php`: revisão, fechamento e reabertura justificada.
- `historico.php`: consulta filtrável do histórico do módulo.
- `trechos.php`: composição e vigência das tarifas por direção.
- `reembolsos.php`: diferenças pendentes de conferência/reembolso, solicitações, aprovações e pagamentos.

## Limitações

Não há integração automática com RHID nem conclusão jurídica automática. As marcações do RHID são
informadas manualmente e nunca substituem o ponto. PDF/Excel continuam preparados para evolução.
O saldo de vale-transporte não é apresentado como salário, lucro ou dinheiro livre. Uma diferença
calculada começa como pendente de conferência e não constitui reconhecimento automático de dívida.

# SONI PONTO

## Implantação

1. Faça backup externo e valide em homologação.
2. Execute `sql/soni_ponto_migration_20260726_acompanhamento.sql`.
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

## Limitações

Não há integração automática com RHID nem conclusão jurídica automática. PDF/Excel ficam preparados
para evolução; a primeira saída deve ser HTML imprimível/CSV sem dependências pesadas.

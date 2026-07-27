<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_calculos.php';
function ok(bool $condicao, string $mensagem): void {
    if (!$condicao) { fwrite(STDERR, "FALHOU: {$mensagem}\n"); exit(1); }
}
$base=['status_dia'=>'trabalhado','entrada'=>'06:00','cafe_saida'=>'08:00','cafe_retorno'=>'08:15',
 'almoco_saida'=>'12:00','almoco_retorno'=>'13:00','saida'=>'15:15'];
$c=ponto_calcular_jornada($base,480);
ok($c['liquido']===480 && $c['saldo']===0,'desconta intervalos da jornada');
$depois=$base;$depois['cafe_saida']='14:00';$depois['cafe_retorno']='14:15';
ok(ponto_calcular_jornada($depois,480)['liquido']===480,'café depois do almoço');
$antes=$base;$antes['almoco_saida']='08:30';$antes['almoco_retorno']='09:30';
ok(ponto_calcular_jornada($antes,480)['liquido']===480,'almoço depois do café sem ordem fixa global');
$incompleto=$base;$incompleto['cafe_retorno']=null;
ok(count(ponto_calcular_jornada($incompleto,480)['alertas'])===1,'marcação incompleta gera alerta');
ok(ponto_status_meta('feriado_folgado')['trabalha']===false,'feriado folgado');
ok(ponto_status_meta('feriado_trabalhado')['trabalha']===true,'feriado trabalhado');
ok(ponto_status_meta('feriado')['remunerado']===null,'feriado legado não é convertido');
$t=ponto_calcular_transporte(30,25,34);
ok(abs($t['proprio_bolso']-9.0)<0.001 && abs($t['economia'])<0.001,'transporte e próprio bolso');
$domingos=[
 ['data'=>'2026-07-05','status_dia'=>'trabalhado'],['data'=>'2026-07-12','status_dia'=>'trabalhado'],
 ['data'=>'2026-07-19','status_dia'=>'folga_domingo'],['data'=>'2026-07-26','status_dia'=>'trabalhado']];
$r=ponto_resumo_registros($domingos,null,31);
ok($r['dias_trabalhados']===3 && $r['folgas_domingo']===1,'domingos e reinício preservados');
$julho=[];
for($i=1;$i<=26;$i++)$julho[]=['data'=>sprintf('2026-07-%02d',$i),'status_dia'=>'trabalhado'];
ok(ponto_resumo_registros($julho,null,31)['dias_sem_registro']===5,'compatibilidade com 26 registros de julho');
echo "OK - cálculos SONI PONTO\n";

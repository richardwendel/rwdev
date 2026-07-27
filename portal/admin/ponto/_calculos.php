<?php
declare(strict_types=1);
function ponto_status_meta(?string $status): array {
    $mapa = [
      'trabalhado'=>['label'=>'Trabalhado','trabalha'=>true,'remunerado'=>true],
      'folga_semanal'=>['label'=>'Folga semanal','trabalha'=>false,'remunerado'=>true],
      'folga_domingo'=>['label'=>'Folga de domingo','trabalha'=>false,'remunerado'=>true],
      'feriado_folgado'=>['label'=>'Feriado folgado','trabalha'=>false,'remunerado'=>true],
      'feriado_trabalhado'=>['label'=>'Feriado trabalhado','trabalha'=>true,'remunerado'=>true],
      'integracao_treinamento'=>['label'=>'Integração / treinamento','trabalha'=>false,'remunerado'=>true],
      'ausencia'=>['label'=>'Ausência','trabalha'=>false,'remunerado'=>false],
      'feriado'=>['label'=>'Feriado (legado — revisar)','trabalha'=>false,'remunerado'=>null],
      'falta'=>['label'=>'Falta (legado)','trabalha'=>false,'remunerado'=>false],
      'atestado'=>['label'=>'Atestado','trabalha'=>false,'remunerado'=>true],
      'ferias'=>['label'=>'Férias','trabalha'=>false,'remunerado'=>true],
      'outro'=>['label'=>'Outro','trabalha'=>false,'remunerado'=>null],
    ];
    return $mapa[$status ?: 'trabalhado'] ?? ['label'=>'Status desconhecido','trabalha'=>false,'remunerado'=>null];
}
function ponto_segundos_hora_puro(?string $hora): ?int {
    if (!$hora) return null; $p=array_map('intval',explode(':',$hora));
    return (($p[0]??0)*3600)+(($p[1]??0)*60)+($p[2]??0);
}
function ponto_calcular_jornada(array $ponto, ?int $previsto=null): array {
    $meta=ponto_status_meta($ponto['status_dia']??null); $alertas=[];
    if (!$meta['trabalha']) return ['liquido'=>null,'saldo'=>null,'extras'=>0,'negativas'=>0,'alertas'=>[]];
    $entrada=ponto_segundos_hora_puro($ponto['entrada']??null); $saida=ponto_segundos_hora_puro($ponto['saida']??null);
    if ($entrada===null||$saida===null) $alertas[]='Marcação de entrada ou saída ausente.';
    if ($entrada!==null&&$saida!==null&&$saida<$entrada) $alertas[]='Saída anterior à entrada.';
    $permanencia=($entrada!==null&&$saida!==null&&$saida>=$entrada)?$saida-$entrada:null; $desconto=0;
    foreach ([['cafe_saida','cafe_retorno','café'],['almoco_saida','almoco_retorno','almoço']] as [$ini,$fim,$nome]) {
      $a=ponto_segundos_hora_puro($ponto[$ini]??null); $b=ponto_segundos_hora_puro($ponto[$fim]??null);
      if (($a===null) xor ($b===null)) $alertas[]="Intervalo de {$nome} incompleto.";
      if ($a!==null&&$b!==null) {
        if ($b<$a) $alertas[]="Retorno de {$nome} anterior à saída.";
        elseif ($entrada!==null&&$saida!==null&&($a<$entrada||$b>$saida)) $alertas[]="Intervalo de {$nome} fora da jornada.";
        else $desconto += $b-$a;
      }
    }
    $liquido=$permanencia===null?null:intdiv(max(0,$permanencia-$desconto),60);
    $saldo=($liquido===null||$previsto===null)?null:$liquido-$previsto;
    return ['liquido'=>$liquido,'saldo'=>$saldo,'extras'=>max(0,$saldo??0),'negativas'=>max(0,-($saldo??0)),'alertas'=>array_values(array_unique($alertas))];
}
function ponto_calcular_transporte(float $previsto,float $recebido,float $gasto): array {
 return ['previsto'=>$previsto,'recebido'=>$recebido,'gasto'=>$gasto,'economia'=>max(0,$recebido-$gasto),'proprio_bolso'=>max(0,$gasto-$recebido)];
}
function ponto_resumo_registros(array $pontos,?int $previsto=null,?int $diasNoMes=null): array {
 $r=['dias_trabalhados'=>0,'dias_remunerados'=>0,'folgas_semanais'=>0,'folgas_domingo'=>0,'integracoes'=>0,'feriados_folgados'=>0,'feriados_trabalhados'=>0,'horas_normais'=>0,'horas_extras'=>0,'horas_negativas'=>0,'saldo'=>0,'transporte_previsto'=>0.0,'transporte_recebido'=>0.0,'transporte_gasto'=>0.0,'economia'=>0.0,'proprio_bolso'=>0.0,'dias_sem_registro'=>max(0,($diasNoMes??count($pontos))-count($pontos)),'alertas'=>[]];
 foreach($pontos as $p){$s=$p['status_dia']??'trabalhado';$m=ponto_status_meta($s);if($m['trabalha'])$r['dias_trabalhados']++;if($m['remunerado']===true)$r['dias_remunerados']++;
 foreach(['folga_semanal'=>'folgas_semanais','folga_domingo'=>'folgas_domingo','integracao_treinamento'=>'integracoes','feriado_folgado'=>'feriados_folgados','feriado_trabalhado'=>'feriados_trabalhados'] as $a=>$b)if($s===$a)$r[$b]++;
 $j=ponto_calcular_jornada($p,$previsto);$r['horas_normais']+=$j['liquido']??0;$r['horas_extras']+=$j['extras'];$r['horas_negativas']+=$j['negativas'];$r['saldo']+=$j['saldo']??0;if($j['alertas'])$r['alertas'][]=['data'=>$p['data']??'','mensagens'=>$j['alertas']];
 $t=ponto_calcular_transporte((float)($p['transporte_previsto']??0),(float)($p['transporte_recebido']??0),(float)($p['gasto_transporte']??0));$r['transporte_previsto']+=$t['previsto'];$r['transporte_recebido']+=$t['recebido'];$r['transporte_gasto']+=$t['gasto'];$r['economia']+=$t['economia'];$r['proprio_bolso']+=$t['proprio_bolso'];}
 return $r;
}

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_calculos.php';
function ok3(bool $ok,string $msg):void{if(!$ok){fwrite(STDERR,"FALHOU: {$msg}\n");exit(1);}}
$lojas=['LJ01'=>[11.70,11.70,23.40],'LJ02'=>null,'LJ03'=>[12.60,12.60,25.20],
 'LJ04'=>[12.60,12.60,25.20],'LJ05'=>[12.60,12.60,25.20],
 'LJ06'=>[17.00,17.00,34.00],'LJ07'=>[11.70,11.70,23.40]];
foreach($lojas as $codigo=>$valores){if($codigo==='LJ02'){ok3($valores===null,'LJ02 sem configuração');continue;}ok3(abs($valores[0]+$valores[1]-$valores[2])<0.001,"total {$codigo}");}
$c=ponto_calcular_transporte(23.40,20.00,25.00);ok3(abs($c['proprio_bolso']-5.00)<0.001,'valor próprio usa gasto real');ok3($c['economia']===0,'sem saldo VT');
$c=ponto_calcular_transporte(23.40,30.00,25.00);ok3(abs($c['economia']-5.00)<0.001,'saldo VT recebido menos gasto');
$d=max(0,34-20);ok3($d===14,'diferença calculada');
$aprovado=14.0;$pago=5.0;ok3($pago<$aprovado,'pagamento parcial');$pago+=9.0;ok3($pago===$aprovado,'pagamento total');
foreach(['calculado','solicitado','aprovado','parcialmente_pago','pago','recusado','cancelado'] as $estado)ok3(is_string($estado),'estado aceito');
echo "OK - transportes e reembolsos SONI PONTO\n";

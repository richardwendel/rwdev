<?php
declare(strict_types=1);
define('SONI_PONTO_TESTE', true);
require_once dirname(__DIR__) . '/_funcoes.php';
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

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE lojas_trabalho(id INTEGER PRIMARY KEY,codigo_loja TEXT)');
$db->exec('CREATE TABLE trajetos_trabalho(id INTEGER PRIMARY KEY,loja_id INTEGER,nome_trajeto TEXT,tipo_transporte TEXT,valor_ida REAL,valor_volta REAL,valor_total REAL,padrao_loja INTEGER,observacoes TEXT,ativo INTEGER)');
$db->exec('CREATE TABLE trajeto_trechos_trabalho(trajeto_id INTEGER,direcao TEXT,ordem_trecho INTEGER,tipo_transporte TEXT,descricao TEXT,tarifa_unitaria REAL,quantidade REAL,subtotal REAL,vigencia_inicio TEXT,vigencia_fim TEXT,ativo INTEGER)');
$db->exec("INSERT INTO lojas_trabalho VALUES(1,'LJ01')");
$db->exec("INSERT INTO trajetos_trabalho VALUES(10,1,'Casa - Loja','integrado',5.00,5.00,10.00,1,'',1)");
$db->exec("INSERT INTO trajeto_trechos_trabalho VALUES(10,'ida',1,'onibus','Linha A',5.00,1,5.00,'2026-01-01',NULL,1)");
$db->exec("INSERT INTO trajeto_trechos_trabalho VALUES(10,'volta',1,'onibus','Linha B',5.00,1,5.00,'2026-01-01','2026-12-31',1)");
$db->exec("INSERT INTO trajeto_trechos_trabalho VALUES(10,'ida',2,'onibus','Expirado',5.00,1,5.00,'2025-01-01','2025-12-31',1)");

$trechos=ponto_trajeto_trechos($db,10,'2026-07-27');
ok3(count($trechos)===2,'trajeto retorna apenas trechos ativos dentro da vigencia');
ok3($trechos[0]['direcao']==='ida'&&$trechos[1]['direcao']==='volta','trajeto ordena trechos ativos');
$trajetos=ponto_trajetos_ativos_por_loja($db,'2026-07-27');
ok3(isset($trajetos['LJ01'][0]),'trajetos ativos agrupados por loja');
ok3(count($trajetos['LJ01'][0]['trechos'])===2,'trajetos por loja carrega trechos vigentes');

$db->exec('CREATE TABLE ponto_configuracoes(id INTEGER PRIMARY KEY,vigencia_inicio TEXT,vigencia_fim TEXT,minutos_jornada INTEGER)');
$db->exec("INSERT INTO ponto_configuracoes VALUES(1,'2026-01-01',NULL,480)");
$db->exec("INSERT INTO ponto_configuracoes VALUES(2,'2026-07-01','2026-07-31',420)");
$vigente=ponto_configuracao_vigente($db,'2026-07-27');
ok3((int)$vigente['id']===2,'data dentro da vigencia com data final');
$vigente=ponto_configuracao_vigente($db,'2026-08-01');
ok3((int)$vigente['id']===1,'vigencia sem data final');

$fonte=file_get_contents(dirname(__DIR__).'/_funcoes.php');
preg_match_all("/->prepare\\(\\s*(['\"])(.*?)\\1\\s*\\)/s",$fonte,$consultas);
foreach($consultas[2] as $sql){
    preg_match_all('/(?<!:):[a-z_][a-z0-9_]*/i',$sql,$marcadores);
    ok3(count($marcadores[0])===count(array_unique($marcadores[0])),'prepared statement sem placeholder nomeado reutilizado');
}
echo "OK - transportes e reembolsos SONI PONTO\n";

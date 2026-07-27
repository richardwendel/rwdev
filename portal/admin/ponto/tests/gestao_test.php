<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_calculos.php';
function teste(bool $ok,string $msg):void{if(!$ok){fwrite(STDERR,"FALHOU: {$msg}\n");exit(1);}}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE cfg(id INTEGER PRIMARY KEY,vigencia_inicio TEXT,vigencia_fim TEXT)');
$db->exec("INSERT INTO cfg VALUES(1,'2026-07-01','2026-07-31')");
$q=$db->prepare("SELECT 1 FROM cfg WHERE id<>:id AND vigencia_inicio<=:fim AND COALESCE(vigencia_fim,'9999-12-31')>=:inicio");
$q->execute([':id'=>0,':inicio'=>'2026-07-15',':fim'=>'2026-08-15']);teste((bool)$q->fetchColumn(),'rejeita vigência sobreposta');
$q->execute([':id'=>0,':inicio'=>'2026-08-01',':fim'=>'2026-08-31']);teste(!$q->fetchColumn(),'aceita vigência não sobreposta');
teste(ponto_segundos_hora_puro('08:15')-ponto_segundos_hora_puro('08:00')===900,'compara Soni e RHID');
$db->exec('CREATE TABLE direitos(id INTEGER PRIMARY KEY,situacao TEXT,data_prevista TEXT,data_utilizacao TEXT)');
$db->exec("INSERT INTO direitos VALUES(1,'pendente',NULL,NULL)");
$db->exec("UPDATE direitos SET situacao='agendada',data_prevista='2026-08-10' WHERE id=1");
$db->exec("UPDATE direitos SET situacao='utilizada',data_utilizacao='2026-08-10' WHERE id=1");
teste($db->query("SELECT situacao FROM direitos WHERE id=1")->fetchColumn()==='utilizada','direito agendado e utilizado');
$db->exec('CREATE TABLE ocorrencias(id INTEGER PRIMARY KEY,situacao TEXT,resolucao TEXT)');
$db->exec("INSERT INTO ocorrencias VALUES(1,'aberta',NULL)");
$db->exec("UPDATE ocorrencias SET situacao='resolvida',resolucao='Conferida' WHERE id=1");
teste($db->query("SELECT situacao FROM ocorrencias")->fetchColumn()==='resolvida','ocorrência resolvida');
$db->exec('CREATE TABLE competencias(ano INTEGER,mes INTEGER,situacao TEXT,justificativa TEXT,UNIQUE(ano,mes))');
$db->exec("INSERT INTO competencias VALUES(2026,7,'fechada',NULL)");
teste($db->query("SELECT situacao FROM competencias")->fetchColumn()==='fechada','competência fechada bloqueável');
$db->exec("UPDATE competencias SET situacao='aberta',justificativa='Correção autorizada' WHERE ano=2026 AND mes=7");
teste($db->query("SELECT justificativa FROM competencias")->fetchColumn()!=='','reabertura guarda justificativa');
$db->exec('CREATE TABLE historico(id INTEGER PRIMARY KEY,acao TEXT,anterior TEXT,novo TEXT)');
$db->exec("INSERT INTO historico VALUES(1,'reabertura','{\"situacao\":\"fechada\"}','{\"situacao\":\"aberta\"}')");
teste((int)$db->query('SELECT COUNT(*) FROM historico')->fetchColumn()===1,'histórico gravado');
echo "OK - gestão e auditoria SONI PONTO\n";

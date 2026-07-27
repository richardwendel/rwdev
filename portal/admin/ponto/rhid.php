<?php
declare(strict_types=1);
require_once __DIR__ . '/_funcoes.php';
exigir_permissao('ponto.visualizar');
$id=(int)($_GET['ponto_id']??$_POST['ponto_id']??0); $ponto=ponto_buscar_ponto($pdo,$id);
if(!$ponto){http_response_code(404);exit('Registro não encontrado.');}
$stmt=$pdo->prepare('SELECT * FROM ponto_rhid_conferencias WHERE ponto_id=:id');$stmt->execute([':id'=>$id]);$rhid=$stmt->fetch()?:[];
$erro='';$campos=['entrada'=>'Entrada','cafe_saida'=>'Café saída','cafe_retorno'=>'Café retorno','almoco_saida'=>'Almoço saída','almoco_retorno'=>'Almoço retorno','saida'=>'Saída'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 exigir_permissao('ponto.editar');validar_csrf();
 try{
  $situacao=trim($_POST['situacao']??'');
  if(!in_array($situacao,['nao_conferido','conferido','divergente','corrigido'],true))throw new RuntimeException('Situação inválida.');
  $conferido=trim($_POST['conferido_em']??'');
  $dados=['ponto_id'=>$id,'situacao'=>$situacao,'diferencas'=>trim($_POST['diferencas']??''),'observacao'=>trim($_POST['observacao']??''),'responsavel'=>trim($_POST['responsavel']??''),'conferido_em'=>$conferido===''?null:str_replace('T',' ',$conferido).':00'];
  foreach($campos as $campo=>$label)$dados[$campo]=ponto_normalizar_hora($_POST[$campo]??'');
  $sql='INSERT INTO ponto_rhid_conferencias (ponto_id,entrada,cafe_saida,cafe_retorno,almoco_saida,almoco_retorno,saida,conferido_em,situacao,diferencas,observacao,responsavel) VALUES (:ponto_id,:entrada,:cafe_saida,:cafe_retorno,:almoco_saida,:almoco_retorno,:saida,:conferido_em,:situacao,:diferencas,:observacao,:responsavel) ON DUPLICATE KEY UPDATE entrada=VALUES(entrada),cafe_saida=VALUES(cafe_saida),cafe_retorno=VALUES(cafe_retorno),almoco_saida=VALUES(almoco_saida),almoco_retorno=VALUES(almoco_retorno),saida=VALUES(saida),conferido_em=VALUES(conferido_em),situacao=VALUES(situacao),diferencas=VALUES(diferencas),observacao=VALUES(observacao),responsavel=VALUES(responsavel)';
  $pdo->prepare($sql)->execute($dados);$rid=(int)($rhid['id']??$pdo->lastInsertId());
  ponto_historico($pdo,'ponto_rhid_conferencias',$rid,$rhid?'edicao':'criacao',$rhid,$dados);
  registrar_auditoria('ponto','rhid_conferido','ponto_rhid_conferencias',$rid,$rhid,$dados);redirect('rhid.php?ponto_id='.$id.'&salvo=1');
 }catch(Throwable $e){$erro=ponto_mensagem_erro($e);$rhid=array_merge($rhid,$_POST);}
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>RHID | SONI PONTO</title><link rel="stylesheet" href="<?=e(asset_url('assets/css/style.css'))?>"></head><body><?php ponto_render_header('SONI PONTO');?><main class="app-container"><section class="page-title"><span>SONI PONTO × RHID</span><h1>Conferência de <?=e(date('d/m/Y',strtotime($ponto['data'])))?></h1></section><?php ponto_render_nav('index.php');?><?php if($erro):?><div class="alerta erro"><?=e($erro)?></div><?php endif;?><?php if(isset($_GET['salvo'])):?><div class="alerta sucesso">Conferência salva sem alterar o Soni Ponto.</div><?php endif;?>
<section class="panel"><h2>Comparação das marcações</h2><div class="table-wrap"><table><thead><tr><th>Marcação</th><th>Soni Ponto</th><th>RHID</th><th>Diferença</th></tr></thead><tbody><?php foreach($campos as $campo=>$label):$dif=ponto_diferenca_horarios($ponto[$campo]??null,$rhid[$campo]??null);?><tr><td><?=e($label)?></td><td><?=e(ponto_formatar_hora($ponto[$campo]??null))?></td><td><?=e(ponto_formatar_hora($rhid[$campo]??null))?></td><td><?=$dif===null?'-':e(($dif>0?'+':'').$dif.' min')?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php if(usuario_pode('ponto.editar')):?><form class="panel form-grid two-cols" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="ponto_id" value="<?=$id?>"><?php foreach($campos as $campo=>$label):?><label><?=e($label)?><input type="time" step="1" name="<?=e($campo)?>" value="<?=e((string)($rhid[$campo]??''))?>"></label><?php endforeach;?>
<label>Situação<select name="situacao"><?php foreach(['nao_conferido'=>'Não conferido','conferido'=>'Conferido','divergente'=>'Divergente','corrigido'=>'Corrigido'] as $v=>$l):?><option value="<?=$v?>" <?=($rhid['situacao']??'nao_conferido')===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></label><label>Conferido em<input type="datetime-local" name="conferido_em" value="<?=e(isset($rhid['conferido_em'])?str_replace(' ','T',substr($rhid['conferido_em'],0,16)):'')?>"></label>
<label>Responsável<input name="responsavel" value="<?=e((string)($rhid['responsavel']??ponto_admin_nome()))?>"></label><label class="full">Diferenças encontradas<textarea name="diferencas"><?=e((string)($rhid['diferencas']??''))?></textarea></label><label class="full">Observação<textarea name="observacao"><?=e((string)($rhid['observacao']??''))?></textarea></label><button type="submit">Salvar conferência</button></form><?php endif;?></main></body></html>

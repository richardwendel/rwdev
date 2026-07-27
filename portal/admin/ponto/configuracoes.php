<?php
declare(strict_types=1);
require_once __DIR__ . '/_funcoes.php';
exigir_permissao('ponto.visualizar');
$erro = $sucesso = '';
$editarId = (int) ($_GET['editar'] ?? 0);
$stmtEditar = $pdo->prepare('SELECT * FROM ponto_configuracoes WHERE id = :id');
$stmtEditar->execute([':id' => $editarId]);
$config = $stmtEditar->fetch() ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_permissao('ponto.editar'); validar_csrf();
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $inicio = ponto_validar_data(trim($_POST['vigencia_inicio'] ?? ''));
        $fim = trim($_POST['vigencia_fim'] ?? '');
        $fim = $fim === '' ? null : ponto_validar_data($fim);
        if ($fim !== null && $fim < $inicio) throw new RuntimeException('A vigência final não pode ser anterior à inicial.');
        $minutos = ponto_int($_POST['minutos_jornada'] ?? '');
        if ($minutos < 1 || $minutos > 1440) throw new RuntimeException('Informe uma jornada entre 1 e 1440 minutos.');
        $domingosTrabalho = ponto_int($_POST['domingos_trabalho_seguidos'] ?? '');
        $domingosFolga = ponto_int($_POST['domingos_folga_seguidos'] ?? '');
        if ($domingosTrabalho < 1 || $domingosFolga < 1) throw new RuntimeException('O ciclo de domingos deve ter valores maiores que zero.');
        if (ponto_configuracao_sobrepoe($pdo, $inicio, $fim, $id)) throw new RuntimeException('A vigência informada se sobrepõe a outra configuração.');
        $dados = [
          'vigencia_inicio'=>$inicio,'vigencia_fim'=>$fim,'minutos_jornada'=>$minutos,
          'hora_entrada_prevista'=>ponto_normalizar_hora($_POST['hora_entrada_prevista'] ?? ''),
          'domingos_trabalho_seguidos'=>$domingosTrabalho,'domingos_folga_seguidos'=>$domingosFolga,
          'integracao_remunerada'=>isset($_POST['integracao_remunerada'])?1:0,
          'feriado_folgado_remunerado'=>isset($_POST['feriado_folgado_remunerado'])?1:0,
          'feriado_trabalhado_adicional_percentual'=>ponto_decimal($_POST['feriado_trabalhado_adicional_percentual'] ?? ''),
          'feriado_trabalhado_gera_folga'=>isset($_POST['feriado_trabalhado_gera_folga'])?1:0,
          'observacoes'=>trim($_POST['observacoes'] ?? ''),
        ];
        if ($id) {
            $antes = $config;
            $sql='UPDATE ponto_configuracoes SET vigencia_inicio=:vigencia_inicio,vigencia_fim=:vigencia_fim,minutos_jornada=:minutos_jornada,hora_entrada_prevista=:hora_entrada_prevista,domingos_trabalho_seguidos=:domingos_trabalho_seguidos,domingos_folga_seguidos=:domingos_folga_seguidos,integracao_remunerada=:integracao_remunerada,feriado_folgado_remunerado=:feriado_folgado_remunerado,feriado_trabalhado_adicional_percentual=:feriado_trabalhado_adicional_percentual,feriado_trabalhado_gera_folga=:feriado_trabalhado_gera_folga,observacoes=:observacoes WHERE id=:id';
            $dados['id']=$id; $pdo->prepare($sql)->execute($dados);
            ponto_historico($pdo,'ponto_configuracoes',$id,'edicao',$antes,$dados);
        } else {
            $sql='INSERT INTO ponto_configuracoes (vigencia_inicio,vigencia_fim,minutos_jornada,hora_entrada_prevista,domingos_trabalho_seguidos,domingos_folga_seguidos,integracao_remunerada,feriado_folgado_remunerado,feriado_trabalhado_adicional_percentual,feriado_trabalhado_gera_folga,observacoes) VALUES (:vigencia_inicio,:vigencia_fim,:minutos_jornada,:hora_entrada_prevista,:domingos_trabalho_seguidos,:domingos_folga_seguidos,:integracao_remunerada,:feriado_folgado_remunerado,:feriado_trabalhado_adicional_percentual,:feriado_trabalhado_gera_folga,:observacoes)';
            $pdo->prepare($sql)->execute($dados); $id=(int)$pdo->lastInsertId();
            ponto_historico($pdo,'ponto_configuracoes',$id,'criacao',[],$dados);
        }
        registrar_auditoria('ponto','configuracao_salva','ponto_configuracoes',$id,$config,$dados);
        redirect('configuracoes.php?salvo=1');
    } catch(Throwable $e) { $erro=ponto_mensagem_erro($e); $config=array_merge($config,$_POST); }
}
$configs=$pdo->query('SELECT * FROM ponto_configuracoes ORDER BY vigencia_inicio DESC,id DESC')->fetchAll();
$sucesso=isset($_GET['salvo'])?'Configuração salva.':'';
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Jornadas | SONI PONTO</title><link rel="stylesheet" href="<?=e(asset_url('assets/css/style.css'))?>"></head><body>
<?php ponto_render_header('SONI PONTO'); ?><main class="app-container"><section class="page-title"><span>SONI PONTO</span><h1>Configurações de jornada</h1></section><?php ponto_render_nav('configuracoes.php'); ?>
<?php if($erro):?><div class="alerta erro"><?=e($erro)?></div><?php endif;?><?php if($sucesso):?><div class="alerta sucesso"><?=e($sucesso)?></div><?php endif;?>
<?php if(usuario_pode('ponto.editar')):?><form method="post" class="panel form-grid two-cols"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)($config['id']??0)?>">
<label>Vigência inicial<input type="date" name="vigencia_inicio" required value="<?=e((string)($config['vigencia_inicio']??''))?>"></label><label>Vigência final<input type="date" name="vigencia_fim" value="<?=e((string)($config['vigencia_fim']??''))?>"></label>
<label>Minutos da jornada<input type="number" min="1" max="1440" name="minutos_jornada" required value="<?=e((string)($config['minutos_jornada']??''))?>"></label><label>Entrada prevista<input type="time" step="1" name="hora_entrada_prevista" value="<?=e((string)($config['hora_entrada_prevista']??''))?>"></label>
<label>Domingos trabalhados<input type="number" min="1" name="domingos_trabalho_seguidos" value="<?=e((string)($config['domingos_trabalho_seguidos']??2))?>"></label><label>Domingos de folga<input type="number" min="1" name="domingos_folga_seguidos" value="<?=e((string)($config['domingos_folga_seguidos']??1))?>"></label>
<label>Adicional de feriado (%)<input name="feriado_trabalhado_adicional_percentual" inputmode="decimal" value="<?=e((string)($config['feriado_trabalhado_adicional_percentual']??'100.00'))?>"></label>
<label class="ponto-checkbox"><input type="checkbox" name="integracao_remunerada" <?=((int)($config['integracao_remunerada']??1))?'checked':''?>> Integração remunerada</label><label class="ponto-checkbox"><input type="checkbox" name="feriado_folgado_remunerado" <?=((int)($config['feriado_folgado_remunerado']??1))?'checked':''?>> Feriado folgado remunerado</label><label class="ponto-checkbox"><input type="checkbox" name="feriado_trabalhado_gera_folga" <?=((int)($config['feriado_trabalhado_gera_folga']??1))?'checked':''?>> Feriado trabalhado gera folga</label>
<label class="full">Observações<textarea name="observacoes"><?=e((string)($config['observacoes']??''))?></textarea></label><button type="submit">Salvar configuração</button></form><?php endif;?>
<section class="panel"><h2>Vigências cadastradas</h2><div class="table-wrap"><table><thead><tr><th>Vigência</th><th>Jornada</th><th>Entrada</th><th>Domingos</th><th>Regras</th><th></th></tr></thead><tbody><?php foreach($configs as $c):?><tr><td><?=e(date('d/m/Y',strtotime($c['vigencia_inicio'])))?> a <?=e($c['vigencia_fim']?date('d/m/Y',strtotime($c['vigencia_fim'])):'sem término')?></td><td><?=e(ponto_formatar_minutos((int)$c['minutos_jornada']))?></td><td><?=e(ponto_formatar_hora($c['hora_entrada_prevista']))?></td><td><?=(int)$c['domingos_trabalho_seguidos']?> trabalho / <?=(int)$c['domingos_folga_seguidos']?> folga</td><td>Adicional <?=e((string)$c['feriado_trabalhado_adicional_percentual'])?>%</td><td><a href="?editar=<?=(int)$c['id']?>">Editar</a></td></tr><?php endforeach;?></tbody></table></div></section>
</main></body></html>

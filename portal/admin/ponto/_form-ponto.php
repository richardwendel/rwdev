<?php
/** @var array $ponto */
/** @var array $lojas */
/** @var array $trajetosPorLoja */
/** @var string $acao */
$dataPonto = (string) ($ponto['data'] ?? date('Y-m-d'));
$statusDia = (string) ($ponto['status_dia'] ?? 'trabalhado');
$statusOpcoes = ponto_status_dia_opcoes();
$escalaDomingo = ponto_escala_domingo($pdo, $dataPonto);
$trajetosPorLoja = $trajetosPorLoja ?? [];
$trajetosJson = json_encode($trajetosPorLoja, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<form class="panel form-grid two-cols" method="post" data-ponto-form>
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <script type="application/json" data-ponto-trajetos-json><?= $trajetosJson ?></script>

  <h2 class="form-section-title">Dados do dia</h2>
  <label>Data
    <input name="data" type="date" value="<?= e($dataPonto) ?>" required data-ponto-data>
    <small class="ponto-dia-semana" data-ponto-dia-semana><?= e(ponto_dia_semana($dataPonto)) ?></small>
  </label>
  <label>Status do dia
    <select name="status_dia" required data-ponto-status>
      <?php foreach ($statusOpcoes as $valor => $rotulo): ?>
        <option value="<?= e($valor) ?>" <?= $valor === $statusDia ? 'selected' : '' ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="ponto-escala-card full" data-ponto-escala>
    <strong>Escala de domingo</strong>
    <span>Domingos trabalhados no ciclo: <?= (int) $escalaDomingo['trabalhados_no_ciclo'] ?>.</span>
    <span>Próximo domingo: <?= date('d/m/Y', strtotime((string) $escalaDomingo['proximo_domingo'])) ?> - <?= $escalaDomingo['folga_prevista'] ? 'Folga prevista.' : 'Trabalho permitido.' ?></span>
  </div>
  <label data-ponto-trabalhado>Loja
    <select name="loja_id" data-ponto-loja>
      <option value="">Selecione</option>
      <?php foreach ($lojas as $loja): ?>
        <option value="<?= (int) $loja['id'] ?>" data-loja-codigo="<?= e((string) $loja['codigo_loja']) ?>" <?= (int) ($ponto['loja_id'] ?? 0) === (int) $loja['id'] ? 'selected' : '' ?>>
          <?= e((string) $loja['codigo_loja']) ?> - <?= e((string) $loja['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <h2 class="form-section-title" data-ponto-trabalhado>Horários</h2>
  <label data-ponto-trabalhado>Entrada
    <span class="ponto-hora-input"><input name="entrada" placeholder="06:41:00" value="<?= e((string) ($ponto['entrada'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="entrada">Agora</button></span>
  </label>
  <label data-ponto-trabalhado>Saída
    <span class="ponto-hora-input"><input name="saida" placeholder="15:41:57" value="<?= e((string) ($ponto['saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="saida">Agora</button></span>
  </label>
  <label data-ponto-trabalhado>Café saída
    <span class="ponto-hora-input"><input name="cafe_saida" placeholder="08:21:00" value="<?= e((string) ($ponto['cafe_saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="cafe_saida">Agora</button></span>
  </label>
  <label data-ponto-trabalhado>Café retorno
    <span class="ponto-hora-input"><input name="cafe_retorno" placeholder="08:30:00" value="<?= e((string) ($ponto['cafe_retorno'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="cafe_retorno">Agora</button></span>
  </label>
  <label data-ponto-trabalhado>Almoço saída
    <span class="ponto-hora-input"><input name="almoco_saida" placeholder="13:19:00" value="<?= e((string) ($ponto['almoco_saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="almoco_saida">Agora</button></span>
  </label>
  <label data-ponto-trabalhado>Almoço retorno
    <span class="ponto-hora-input"><input name="almoco_retorno" placeholder="14:18:00" value="<?= e((string) ($ponto['almoco_retorno'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="almoco_retorno">Agora</button></span>
  </label>

  <h2 class="form-section-title" data-ponto-trabalhado>Transporte e trajetos</h2>
  <label data-ponto-trabalhado>Trajeto de ida
    <select name="trajeto_ida_id" data-ponto-trajeto="ida" data-selected="<?= (int) ($ponto['trajeto_ida_id'] ?? 0) ?>">
      <option value="">Selecione a loja primeiro</option>
    </select>
  </label>
  <label data-ponto-trabalhado>Trajeto de volta
    <select name="trajeto_volta_id" data-ponto-trajeto="volta" data-selected="<?= (int) ($ponto['trajeto_volta_id'] ?? 0) ?>">
      <option value="">Selecione a loja primeiro</option>
    </select>
  </label>
  <div class="ponto-sem-trajeto full" data-ponto-sem-trajeto data-ponto-trabalhado hidden>
    Nenhum trajeto cadastrado para esta loja.
    <a href="trajetos.php">➕ Cadastrar trajeto</a>
  </div>
  <p class="ponto-link-discreto full" data-ponto-trabalhado><a href="trajetos.php">🛣️ Gerenciar Trajetos</a></p>
  <label data-ponto-trabalhado>Gasto com transporte
    <input name="gasto_transporte" inputmode="decimal" placeholder="0,00" value="<?= e(number_format((float) ($ponto['gasto_transporte'] ?? 0), 2, ',', '.')) ?>">
  </label>
  <label data-ponto-trabalhado>Bilhetes perdidos<input name="bilhetes_perdidos" inputmode="numeric" value="<?= e((string) ($ponto['bilhetes_perdidos'] ?? 0)) ?>"></label>
  <label data-ponto-trabalhado>Valor dos bilhetes perdidos<input name="valor_bilhetes_perdidos" inputmode="decimal" placeholder="0,00" value="<?= e(number_format((float) ($ponto['valor_bilhetes_perdidos'] ?? 0), 2, ',', '.')) ?>"></label>
  <label class="full" data-ponto-trabalhado>Observação de transporte
    <textarea name="transporte_observacao" rows="3" placeholder="Atraso de van, vale-transporte, troca de trajeto..."><?= e((string) ($ponto['transporte_observacao'] ?? '')) ?></textarea>
  </label>
  <h2 class="form-section-title">Observação</h2>
  <label class="full">Observações do dia
    <textarea name="observacoes" rows="4" placeholder="Mudança de escala, loja alterada, atraso, bilhete perdido..."><?= e((string) ($ponto['observacoes'] ?? '')) ?></textarea>
  </label>

  <button type="submit"><?= e($acao) ?></button>
  <a class="btn outline" href="index.php">Cancelar</a>
</form>

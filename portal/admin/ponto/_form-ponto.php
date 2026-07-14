<?php
/** @var array $ponto */
/** @var array $lojas */
/** @var array $trajetosPorLoja */
/** @var string $acao */
$dataPonto = (string) ($ponto['data'] ?? date('Y-m-d'));
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
  <label>Loja
    <select name="loja_id" required data-ponto-loja>
      <option value="">Selecione</option>
      <?php foreach ($lojas as $loja): ?>
        <option value="<?= (int) $loja['id'] ?>" data-loja-codigo="<?= e((string) $loja['codigo_loja']) ?>" <?= (int) ($ponto['loja_id'] ?? 0) === (int) $loja['id'] ? 'selected' : '' ?>>
          <?= e((string) $loja['codigo_loja']) ?> - <?= e((string) $loja['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <h2 class="form-section-title">Horários</h2>
  <label>Entrada
    <span class="ponto-hora-input"><input name="entrada" placeholder="06:41:00" value="<?= e((string) ($ponto['entrada'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="entrada">Agora</button></span>
  </label>
  <label>Saída
    <span class="ponto-hora-input"><input name="saida" placeholder="15:41:57" value="<?= e((string) ($ponto['saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="saida">Agora</button></span>
  </label>
  <label>Café saída
    <span class="ponto-hora-input"><input name="cafe_saida" placeholder="08:21:00" value="<?= e((string) ($ponto['cafe_saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="cafe_saida">Agora</button></span>
  </label>
  <label>Café retorno
    <span class="ponto-hora-input"><input name="cafe_retorno" placeholder="08:30:00" value="<?= e((string) ($ponto['cafe_retorno'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="cafe_retorno">Agora</button></span>
  </label>
  <label>Almoço saída
    <span class="ponto-hora-input"><input name="almoco_saida" placeholder="13:19:00" value="<?= e((string) ($ponto['almoco_saida'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="almoco_saida">Agora</button></span>
  </label>
  <label>Almoço retorno
    <span class="ponto-hora-input"><input name="almoco_retorno" placeholder="14:18:00" value="<?= e((string) ($ponto['almoco_retorno'] ?? '')) ?>"><button type="button" class="btn small outline" data-ponto-agora="almoco_retorno">Agora</button></span>
  </label>

  <h2 class="form-section-title">Transporte e observações</h2>
  <label>Trajeto de ida
    <select name="trajeto_ida_id" data-ponto-trajeto="ida" data-selected="<?= (int) ($ponto['trajeto_ida_id'] ?? 0) ?>">
      <option value="">Selecione a loja primeiro</option>
    </select>
  </label>
  <label>Trajeto de volta
    <select name="trajeto_volta_id" data-ponto-trajeto="volta" data-selected="<?= (int) ($ponto['trajeto_volta_id'] ?? 0) ?>">
      <option value="">Selecione a loja primeiro</option>
    </select>
  </label>
  <div class="ponto-sem-trajeto full" data-ponto-sem-trajeto hidden>
    Nenhum trajeto cadastrado para esta loja.
    <a href="trajetos.php">➕ Cadastrar trajeto</a>
  </div>
  <p class="ponto-link-discreto full"><a href="trajetos.php">🛣️ Gerenciar Trajetos</a></p>
  <label>Gasto com transporte
    <input name="gasto_transporte" inputmode="decimal" placeholder="0,00" value="<?= e(number_format((float) ($ponto['gasto_transporte'] ?? 0), 2, ',', '.')) ?>">
  </label>
  <label>Bilhetes perdidos<input name="bilhetes_perdidos" inputmode="numeric" value="<?= e((string) ($ponto['bilhetes_perdidos'] ?? 0)) ?>"></label>
  <label>Valor dos bilhetes perdidos<input name="valor_bilhetes_perdidos" inputmode="decimal" placeholder="0,00" value="<?= e(number_format((float) ($ponto['valor_bilhetes_perdidos'] ?? 0), 2, ',', '.')) ?>"></label>
  <label class="full">Observação de transporte
    <textarea name="transporte_observacao" rows="3" placeholder="Atraso de van, vale-transporte, troca de trajeto..."><?= e((string) ($ponto['transporte_observacao'] ?? '')) ?></textarea>
  </label>
  <label class="full">Observações do dia
    <textarea name="observacoes" rows="4" placeholder="Mudança de escala, loja alterada, atraso, bilhete perdido..."><?= e((string) ($ponto['observacoes'] ?? '')) ?></textarea>
  </label>

  <button type="submit"><?= e($acao) ?></button>
  <a class="btn outline" href="index.php">Cancelar</a>
</form>

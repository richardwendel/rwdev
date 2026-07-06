<?php
/** @var array $documento */
/** @var array $categorias */
/** @var array $pontos */
/** @var string $acao */
?>
<form class="panel form-grid two-cols" method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <h2 class="form-section-title">Dados do documento</h2>
  <label>Título<input name="titulo" value="<?= e((string) ($documento['titulo'] ?? '')) ?>" required></label>
  <label>Categoria
    <select name="categoria" required>
      <option value="">Selecione</option>
      <?php foreach ($categorias as $categoria): ?>
        <option value="<?= e((string) $categoria) ?>" <?= ($documento['categoria'] ?? '') === $categoria ? 'selected' : '' ?>><?= e((string) $categoria) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Empresa<input name="empresa" value="<?= e((string) ($documento['empresa'] ?? '')) ?>"></label>
  <label>Cargo<input name="cargo" value="<?= e((string) ($documento['cargo'] ?? '')) ?>"></label>
  <label>Data do documento<input type="date" name="data_documento" value="<?= e((string) ($documento['data_documento'] ?? '')) ?>"></label>
  <label>Data de validade<input type="date" name="data_validade" value="<?= e((string) ($documento['data_validade'] ?? '')) ?>"></label>

  <h2 class="form-section-title">Arquivo e vínculo</h2>
  <label>Arquivo
    <input type="file" name="arquivo" accept=".pdf,.jpg,.jpeg,.png" <?= empty($documento['id']) ? 'required' : '' ?>>
  </label>
  <label>Vincular ao SONI PONTO
    <select name="ponto_id">
      <option value="">Sem vínculo</option>
      <?php foreach ($pontos as $ponto): ?>
        <option value="<?= (int) $ponto['id'] ?>" <?= (int) ($documento['ponto_id'] ?? 0) === (int) $ponto['id'] ? 'selected' : '' ?>>
          <?= date('d/m/Y', strtotime($ponto['data'])) ?> - <?= e((string) $ponto['dia_semana']) ?> - Loja <?= e((string) $ponto['codigo_loja']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php if (!empty($documento['arquivo'])): ?>
    <p class="full empty">Arquivo atual: <?= e((string) $documento['arquivo']) ?></p>
  <?php endif; ?>
  <label class="full">Observações<textarea name="observacoes" rows="4"><?= e((string) ($documento['observacoes'] ?? '')) ?></textarea></label>
  <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($documento['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativo</label>

  <button type="submit"><?= e($acao) ?></button>
  <a class="btn outline" href="index.php">Cancelar</a>
</form>

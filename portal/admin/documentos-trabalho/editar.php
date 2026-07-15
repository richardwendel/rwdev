<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('documentos.editar');

$erro = '';
$id = (int) ($_GET['id'] ?? 0);
$documento = docs_buscar($pdo, $id);

if (!$documento) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

$categorias = docs_categorias($pdo, false);
$pontos = docs_pontos($pdo);
$acao = 'Salvar documento';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $dados = docs_dados_post((string) $documento['arquivo']);
        $dados['id'] = $id;

        $stmt = $pdo->prepare(
            'UPDATE documentos_trabalho
             SET titulo = :titulo, categoria = :categoria, empresa = :empresa, cargo = :cargo,
                 data_documento = :data_documento, data_validade = :data_validade, arquivo = :arquivo,
                 observacoes = :observacoes, ponto_id = :ponto_id, ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($dados);
        registrar_auditoria('documentos', 'metadados_editados', 'documentos_trabalho', $id, array_diff_key($documento, ['arquivo' => true]), array_diff_key($dados, ['arquivo' => true, 'id' => true]), 'sucesso', null, 'Metadados de documento editados');

        redirect('visualizar.php?id=' . $id);
    } catch (Throwable $e) {
        registrar_auditoria('documentos', 'erro_editar', 'documentos_trabalho', $id, array_diff_key($documento, ['arquivo' => true]), array_diff_key($_POST, ['arquivo' => true]), 'erro', $e->getMessage(), 'Falha ao editar documento');
        $erro = docs_mensagem_erro($e);
        $documento = array_merge($documento, $_POST);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar documento | RWDEV Admin</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Documentos do Trabalho</span>
      <h1>Editar documento</h1>
    </section>

    <?php docs_render_nav('index.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php require __DIR__ . '/_form-documento.php'; ?>
  </main>
</body>
</html>

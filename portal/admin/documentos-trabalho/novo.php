<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('documentos.criar');

$erro = '';
$documento = ['ativo' => 1];
$categorias = docs_categorias($pdo);
$pontos = docs_pontos($pdo);
$acao = 'Cadastrar documento';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $dados = docs_dados_post();

        if (!$dados['arquivo']) {
            throw new RuntimeException('Envie o arquivo do documento.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO documentos_trabalho
             (titulo, categoria, empresa, cargo, data_documento, data_validade, arquivo, observacoes, ponto_id, ativo)
             VALUES
             (:titulo, :categoria, :empresa, :cargo, :data_documento, :data_validade, :arquivo, :observacoes, :ponto_id, :ativo)'
        );
        $stmt->execute($dados);
        registrar_auditoria('documentos', 'documento_cadastrado', 'documentos_trabalho', (int) $pdo->lastInsertId(), [], array_diff_key($dados, ['arquivo' => true]), 'sucesso', null, 'Documento cadastrado');

        redirect('index.php');
    } catch (Throwable $e) {
        registrar_auditoria('documentos', 'erro_cadastrar', 'documentos_trabalho', null, [], array_diff_key($_POST, ['arquivo' => true]), 'erro', $e->getMessage(), 'Falha ao cadastrar documento');
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
  <title>Novo documento | RWDEV Admin</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Documentos do Trabalho</span>
      <h1>Novo documento</h1>
    </section>

    <?php docs_render_nav('novo.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <div class="alerta erro">Não envie documentos pessoais para o Git. Use esta tela para armazenar arquivos na pasta protegida de uploads.</div>
    <?php require __DIR__ . '/_form-documento.php'; ?>
  </main>
</body>
</html>

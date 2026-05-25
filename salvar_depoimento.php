<?php
declare(strict_types=1);

// Endpoint público responsável por receber o formulário de depoimentos.
require_once __DIR__ . '/config.php';

// Aceita somente envio via POST para evitar gravações por acesso direto.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resposta_json(['sucesso' => false, 'mensagem' => 'Método não permitido.'], 405);
}

try {
    // Captura e normaliza os campos enviados pelo formulário público.
    $nome = campo_texto((string) ($_POST['nome'] ?? ''), 150);
    $cidade = campo_texto((string) ($_POST['cidade'] ?? ''), 150);
    $redeSocial = campo_texto((string) ($_POST['rede'] ?? ''), 255);
    $tempoConhece = campo_texto((string) ($_POST['tempo'] ?? ''), 100);
    $depoimento = trim((string) ($_POST['depoimento'] ?? ''));
    $autorizacao = isset($_POST['autorizacao']) ? 1 : 0;

    // Valida os campos obrigatórios antes de salvar no banco.
    if ($nome === '' || $cidade === '' || $tempoConhece === '' || $depoimento === '') {
        throw new RuntimeException('Preencha todos os campos obrigatórios.');
    }

    if (!$autorizacao) {
        throw new RuntimeException('A autorização é obrigatória para enviar o depoimento.');
    }

    if ($redeSocial !== '' && !filter_var($redeSocial, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Informe um link de rede social válido.');
    }

    // Salva a foto opcional e recebe o caminho público para gravar no banco.
    $foto = isset($_FILES['foto']) ? salvar_foto_depoimento($_FILES['foto']) : null;

    // Insere sempre como pendente para aprovação manual futura no painel administrativo.
    $stmt = $pdo->prepare(
        'INSERT INTO depoimentos
         (nome, cidade, rede_social, foto, depoimento, tempo_conhece, autorizacao, status)
         VALUES
         (:nome, :cidade, :rede_social, :foto, :depoimento, :tempo_conhece, :autorizacao, "pendente")'
    );
    $stmt->execute([
        ':nome' => $nome,
        ':cidade' => $cidade,
        ':rede_social' => $redeSocial,
        ':foto' => $foto,
        ':depoimento' => $depoimento,
        ':tempo_conhece' => $tempoConhece,
        ':autorizacao' => $autorizacao,
    ]);

    resposta_json([
        'sucesso' => true,
        'mensagem' => 'Seu depoimento foi enviado e aguarda aprovação.',
    ]);
} catch (Throwable $erro) {
    // Registra detalhes técnicos no log e retorna apenas mensagem segura ao visitante.
    error_log('Erro ao salvar depoimento: ' . $erro->getMessage());
    resposta_json([
        'sucesso' => false,
        'mensagem' => $erro instanceof RuntimeException ? $erro->getMessage() : 'Não foi possível enviar o depoimento agora.',
    ], 400);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function auditoria_campo_sensivel(string $campo): bool
{
    $campo = strtolower($campo);
    $bloqueados = [
        'senha',
        'senha_hash',
        'password',
        'token',
        'token_hash',
        'csrf',
        'csrf_token',
        'cookie',
        'session',
        'sessao',
        'secret',
        'api_key',
        'authorization',
    ];

    foreach ($bloqueados as $bloqueado) {
        if (str_contains($campo, $bloqueado)) {
            return true;
        }
    }

    return false;
}

function auditoria_mascarar_valor(string $campo, mixed $valor): mixed
{
    if ($valor === null || is_array($valor)) {
        return $valor;
    }

    $campo = strtolower($campo);
    $texto = (string) $valor;

    if (str_contains($campo, 'email') || str_contains($campo, 'e-mail')) {
        if (!str_contains($texto, '@')) {
            return $texto;
        }

        [$usuario, $dominio] = explode('@', $texto, 2);
        return substr($usuario, 0, 2) . '***@' . $dominio;
    }

    if (str_contains($campo, 'telefone') || str_contains($campo, 'whatsapp')) {
        $digitos = preg_replace('/\D+/', '', $texto) ?? '';
        if (strlen($digitos) < 4) {
            return '***';
        }

        return '***' . substr($digitos, -4);
    }

    if (str_contains($campo, 'cpf')) {
        $digitos = preg_replace('/\D+/', '', $texto) ?? '';
        return strlen($digitos) >= 4 ? '***.' . substr($digitos, -4) : '***';
    }

    if (str_contains($campo, 'banco') || str_contains($campo, 'agencia') || str_contains($campo, 'conta')) {
        return '***';
    }

    return $valor;
}

function auditoria_sanitizar_dados(array $dados): array
{
    $limpos = [];

    foreach ($dados as $campo => $valor) {
        $campoString = (string) $campo;

        if (auditoria_campo_sensivel($campoString)) {
            continue;
        }

        if (is_array($valor)) {
            $limpos[$campoString] = auditoria_sanitizar_dados($valor);
            continue;
        }

        $limpos[$campoString] = auditoria_mascarar_valor($campoString, $valor);
    }

    return $limpos;
}

function auditoria_diferenca(array $antes, array $depois): array
{
    $campos = array_values(array_unique(array_merge(array_keys($antes), array_keys($depois))));
    $antesAlterados = [];
    $depoisAlterados = [];
    $camposAlterados = [];

    foreach ($campos as $campo) {
        $valorAntes = $antes[$campo] ?? null;
        $valorDepois = $depois[$campo] ?? null;

        if ($valorAntes != $valorDepois) {
            $antesAlterados[$campo] = $valorAntes;
            $depoisAlterados[$campo] = $valorDepois;
            $camposAlterados[] = $campo;
        }
    }

    return [$antesAlterados, $depoisAlterados, $camposAlterados];
}

function auditoria_json(?array $dados): ?string
{
    if (!$dados) {
        return null;
    }

    return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function registrar_auditoria(
    string $modulo,
    string $acao,
    string $entidade,
    ?int $registroId = null,
    array $antes = [],
    array $depois = [],
    string $resultado = 'sucesso',
    ?string $mensagem = null,
    ?string $descricao = null
): void {
    global $pdo;

    try {
        if (!isset($pdo) || !admin_tabela_existe($pdo, 'auditoria_admin')) {
            return;
        }

        $admin = admin_atual() ?? [];
        $antes = auditoria_sanitizar_dados($antes);
        $depois = auditoria_sanitizar_dados($depois);
        $camposAlterados = [];

        if ($antes && $depois) {
            [$antes, $depois, $camposAlterados] = auditoria_diferenca($antes, $depois);
        } elseif ($depois) {
            $camposAlterados = array_keys($depois);
        } elseif ($antes) {
            $camposAlterados = array_keys($antes);
        }

        $resultado = in_array($resultado, ['sucesso', 'erro', 'negado'], true) ? $resultado : 'sucesso';
        $sessaoId = session_id();
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;

        $stmt = $pdo->prepare(
            'INSERT INTO auditoria_admin
             (admin_id, admin_nome_snapshot, admin_email_snapshot, admin_perfil_snapshot, empresa_id,
              modulo, acao, entidade, registro_id, descricao, dados_anteriores_json,
              dados_posteriores_json, campos_alterados_json, resultado, mensagem_resultado,
              rota, metodo_http, ip, user_agent, request_id, sessao_id_hash)
             VALUES
             (:admin_id, :admin_nome_snapshot, :admin_email_snapshot, :admin_perfil_snapshot, NULL,
              :modulo, :acao, :entidade, :registro_id, :descricao, :dados_anteriores_json,
              :dados_posteriores_json, :campos_alterados_json, :resultado, :mensagem_resultado,
              :rota, :metodo_http, :ip, :user_agent, :request_id, :sessao_id_hash)'
        );
        $stmt->execute([
            ':admin_id' => isset($admin['id']) ? (int) $admin['id'] : null,
            ':admin_nome_snapshot' => $admin['nome'] ?? ($_SESSION['admin_nome'] ?? null),
            ':admin_email_snapshot' => $admin['email'] ?? null,
            ':admin_perfil_snapshot' => $admin['perfil'] ?? ($_SESSION['admin_perfil'] ?? null),
            ':modulo' => $modulo,
            ':acao' => $acao,
            ':entidade' => $entidade,
            ':registro_id' => $registroId,
            ':descricao' => $descricao,
            ':dados_anteriores_json' => auditoria_json($antes),
            ':dados_posteriores_json' => auditoria_json($depois),
            ':campos_alterados_json' => auditoria_json($camposAlterados),
            ':resultado' => $resultado,
            ':mensagem_resultado' => $mensagem ? substr($mensagem, 0, 255) : null,
            ':rota' => substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 255),
            ':metodo_http' => substr((string) ($_SERVER['REQUEST_METHOD'] ?? ''), 0, 10),
            ':ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':request_id' => $requestId ? substr((string) $requestId, 0, 80) : null,
            ':sessao_id_hash' => $sessaoId !== '' ? hash('sha256', $sessaoId) : null,
        ]);
    } catch (Throwable $e) {
        error_log('Falha ao registrar auditoria administrativa: ' . $e->getMessage());
    }
}

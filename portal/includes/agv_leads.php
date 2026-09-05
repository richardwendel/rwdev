<?php
declare(strict_types=1);

const AGV_ORIGEM = 'RWDEV - Página AGV';
const AGV_WHATSAPP_CARLOS = '5511940195111';

function agv_status_disponiveis(): array
{
    return ['Novo', 'Encaminhado ao Carlos', 'Em atendimento', 'Fechado', 'Perdido', 'Acompanhamento'];
}

function agv_texto(mixed $valor, int $limite): string
{
    $texto = trim((string) $valor);
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

    return function_exists('mb_substr') ? mb_substr($texto, 0, $limite) : substr($texto, 0, $limite);
}

function agv_tamanho(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor) : strlen($valor);
}

function agv_normalizar_whatsapp(mixed $valor): string
{
    $digitos = preg_replace('/\D+/', '', (string) $valor) ?? '';

    if ((strlen($digitos) === 12 || strlen($digitos) === 13) && str_starts_with($digitos, '55')) {
        $digitos = substr($digitos, 2);
    }

    return $digitos;
}

function agv_whatsapp_valido(string $whatsapp): bool
{
    return preg_match('/^[1-9][0-9](?:[2-9][0-9]{7}|9[0-9]{8})$/', $whatsapp) === 1;
}

function agv_formatar_whatsapp(string $whatsapp): string
{
    if (strlen($whatsapp) === 11) {
        return sprintf('(%s) %s-%s', substr($whatsapp, 0, 2), substr($whatsapp, 2, 5), substr($whatsapp, 7));
    }

    if (strlen($whatsapp) === 10) {
        return sprintf('(%s) %s-%s', substr($whatsapp, 0, 2), substr($whatsapp, 2, 4), substr($whatsapp, 6));
    }

    return $whatsapp;
}

function agv_normalizar_placa(mixed $valor): string
{
    $placa = strtoupper((string) $valor);
    $placa = preg_replace('/[\s-]+/', '', $placa) ?? '';

    if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa) === 1) {
        return substr($placa, 0, 3) . '-' . substr($placa, 3);
    }

    return $placa;
}

function agv_placa_valida(string $placa): bool
{
    return preg_match('/^(?:[A-Z]{3}-[0-9]{4}|[A-Z]{3}[0-9][A-Z][0-9]{2})$/', $placa) === 1;
}

function agv_ano_valido(mixed $valor, ?int $anoAtual = null): bool
{
    $ano = (string) $valor;
    $limite = ($anoAtual ?? (int) date('Y')) + 1;

    return preg_match('/^[0-9]{4}$/', $ano) === 1 && (int) $ano >= 1900 && (int) $ano <= $limite;
}

function agv_validar_lead(array $entrada, ?int $anoAtual = null): array
{
    $dados = [
        'nome' => agv_texto($entrada['nome'] ?? '', 150),
        'whatsapp' => agv_normalizar_whatsapp($entrada['whatsapp'] ?? ''),
        'cidade' => agv_texto($entrada['cidade'] ?? '', 120),
        'veiculo' => agv_texto($entrada['veiculo'] ?? '', 160),
        'ano' => trim((string) ($entrada['ano'] ?? '')),
        'placa' => agv_normalizar_placa($entrada['placa'] ?? ''),
        'privacidade_aceita' => filter_var($entrada['privacidade_aceita'] ?? false, FILTER_VALIDATE_BOOL),
    ];
    $erros = [];

    if (agv_tamanho($dados['nome']) < 2) {
        $erros['nome'] = 'Informe seu nome.';
    }

    if (!agv_whatsapp_valido($dados['whatsapp'])) {
        $erros['whatsapp'] = 'Informe um WhatsApp brasileiro válido com DDD.';
    }

    if (agv_tamanho($dados['cidade']) < 2) {
        $erros['cidade'] = 'Informe sua cidade.';
    }

    if (agv_tamanho($dados['veiculo']) < 2) {
        $erros['veiculo'] = 'Informe o veículo e o modelo.';
    }

    if (!agv_ano_valido($dados['ano'], $anoAtual)) {
        $erros['ano'] = 'Informe um ano válido com quatro números.';
    }

    if (!agv_placa_valida($dados['placa'])) {
        $erros['placa'] = 'Informe uma placa válida, como ABC-1234 ou ABC1D23.';
    }

    if (!$dados['privacidade_aceita']) {
        $erros['privacidade_aceita'] = 'Confirme o uso dos dados para solicitar a cotação.';
    }

    return ['dados' => $dados, 'erros' => $erros];
}

function agv_codigo_por_id(int $id): string
{
    return 'AGV-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function agv_salvar_lead(PDO $pdo, array $dados): array
{
    $iniciouTransacao = !$pdo->inTransaction();

    if ($iniciouTransacao) {
        $pdo->beginTransaction();
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO agv_leads
             (codigo, nome, whatsapp, cidade, veiculo, ano, placa, origem, status, privacidade_aceita_em, created_at, updated_at)
             VALUES
             (NULL, :nome, :whatsapp, :cidade, :veiculo, :ano, :placa, :origem, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':whatsapp' => $dados['whatsapp'],
            ':cidade' => $dados['cidade'],
            ':veiculo' => $dados['veiculo'],
            ':ano' => (int) $dados['ano'],
            ':placa' => $dados['placa'],
            ':origem' => AGV_ORIGEM,
            ':status' => 'Novo',
        ]);

        $id = (int) $pdo->lastInsertId();
        $codigo = agv_codigo_por_id($id);
        $stmtCodigo = $pdo->prepare('UPDATE agv_leads SET codigo = :codigo WHERE id = :id');
        $stmtCodigo->execute([':codigo' => $codigo, ':id' => $id]);

        if ($iniciouTransacao) {
            $pdo->commit();
        }

        return ['id' => $id, 'codigo' => $codigo];
    } catch (Throwable $erro) {
        if ($iniciouTransacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }
}

function agv_mensagem_whatsapp(array $dados, string $codigo): string
{
    return "Olá Carlos! Vim através da página AGV no site RWDEV.\n\n"
        . "Nome: {$dados['nome']}\n"
        . 'WhatsApp: ' . agv_formatar_whatsapp($dados['whatsapp']) . "\n"
        . "Cidade: {$dados['cidade']}\n"
        . "Veículo: {$dados['veiculo']}\n"
        . "Ano: {$dados['ano']}\n"
        . "Placa: {$dados['placa']}\n\n"
        . "Código da solicitação: {$codigo}\n\n"
        . 'Gostaria de fazer uma cotação.';
}

function agv_url_whatsapp(array $dados, string $codigo): string
{
    return 'https://wa.me/' . AGV_WHATSAPP_CARLOS . '?text=' . rawurlencode(agv_mensagem_whatsapp($dados, $codigo));
}

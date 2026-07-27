<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/admin_ui.php';
require_once __DIR__ . '/../../includes/auditoria.php';
require_once __DIR__ . '/_calculos.php';

exigir_admin();

function ponto_admin_url(string $arquivo = 'index.php'): string
{
    return $arquivo;
}

function ponto_render_header(string $titulo): void
{
    admin_render_header('../');
}

function ponto_render_nav(string $ativo): void
{
    $itens = [
        'index.php' => ['Registros', 'ponto.visualizar'],
        'novo.php' => ['Novo ponto', 'ponto.criar'],
        'resumo.php' => ['Resumo mensal', 'resumo.visualizar'],
        'configuracoes.php' => ['Jornadas', 'ponto.visualizar'],
        'direitos.php' => ['Direitos', 'ponto.visualizar'],
        'reembolsos.php' => ['Reembolsos', 'ponto.visualizar'],
        'ocorrencias.php' => ['Ocorrências', 'ponto.visualizar'],
        'competencias.php' => ['Fechamento', 'resumo.visualizar'],
        'historico.php' => ['Histórico', 'ponto.visualizar'],
        'lojas.php' => ['Lojas', 'lojas.visualizar'],
        'trajetos.php' => ['Trajetos', 'trajetos.visualizar'],
    ];
    ?>
    <nav class="ponto-tabs" aria-label="Navegacao SONI PONTO">
      <?php foreach ($itens as $href => [$rotulo, $permissao]): ?>
        <?php if (usuario_pode($permissao)): ?>
          <a class="<?= $ativo === $href ? 'ativo' : '' ?>" href="<?= e($href) ?>"><?= e($rotulo) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php
}

function ponto_dias_semana(): array
{
    return [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];
}

function ponto_status_dia_opcoes(): array
{
    $opcoes = [];
    foreach (['trabalhado', 'folga_semanal', 'folga_domingo', 'feriado_folgado',
        'feriado_trabalhado', 'integracao_treinamento', 'ausencia', 'feriado',
        'falta', 'atestado', 'ferias', 'outro'] as $status) {
        $opcoes[$status] = ponto_status_meta($status)['label'];
    }
    return $opcoes;
}

function ponto_status_dia_valido(string $status): bool
{
    return array_key_exists($status, ponto_status_dia_opcoes());
}

function ponto_status_dia_label(?string $status): string
{
    $status = $status ?: 'trabalhado';
    $opcoes = ponto_status_dia_opcoes();

    return $opcoes[$status] ?? $opcoes['trabalhado'];
}

function ponto_dia_trabalhado(?string $status): bool
{
    return (bool) ponto_status_meta($status)['trabalha'];
}

function ponto_proximo_domingo(string $dataReferencia): string
{
    $data = new DateTime(ponto_validar_data($dataReferencia));

    if ((int) $data->format('w') !== 0) {
        $data->modify('next sunday');
    }

    return $data->format('Y-m-d');
}

function ponto_escala_domingo(PDO $pdo, string $dataReferencia): array
{
    $proximoDomingo = ponto_proximo_domingo($dataReferencia);
    $stmt = $pdo->prepare(
        "SELECT data, status_dia
         FROM pontos_trabalho
         WHERE DAYOFWEEK(data) = 1 AND data < :proximo_domingo
         ORDER BY data DESC, id DESC
         LIMIT 12"
    );
    $stmt->execute([':proximo_domingo' => $proximoDomingo]);
    $domingos = $stmt->fetchAll();

    $trabalhadosNoCiclo = 0;

    foreach ($domingos as $domingo) {
        $status = (string) ($domingo['status_dia'] ?? 'trabalhado');

        if ($status === 'folga_domingo') {
            break;
        }

        if ($status === 'trabalhado') {
            $trabalhadosNoCiclo++;
        }
    }

    return [
        'proximo_domingo' => $proximoDomingo,
        'trabalhados_no_ciclo' => $trabalhadosNoCiclo,
        'folga_prevista' => $trabalhadosNoCiclo >= 2,
    ];
}

function ponto_dia_semana(string $data): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $data);

    if (!$dt) {
        return '';
    }

    return ponto_dias_semana()[(int) $dt->format('w')];
}

function ponto_validar_data(string $data): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $data);

    if (!$dt || $dt->format('Y-m-d') !== $data) {
        throw new RuntimeException('Informe uma data válida.');
    }

    return $data;
}

function ponto_normalizar_hora(?string $hora): ?string
{
    $hora = trim((string) $hora);

    if ($hora === '') {
        return null;
    }

    if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $hora, $partes)) {
        throw new RuntimeException('Use horários no formato HH:MM ou HH:MM:SS.');
    }

    return sprintf('%02d:%02d:%02d', (int) $partes[1], (int) $partes[2], isset($partes[3]) ? (int) $partes[3] : 0);
}

function ponto_decimal(?string $valor): float
{
    $valor = str_replace(',', '.', trim((string) $valor));

    if ($valor === '') {
        return 0.0;
    }

    if (!is_numeric($valor)) {
        throw new RuntimeException('Informe valores numéricos válidos.');
    }

    return max(0.0, round((float) $valor, 2));
}

function ponto_int(?string $valor): int
{
    $valor = trim((string) $valor);

    if ($valor === '') {
        return 0;
    }

    if (!ctype_digit($valor)) {
        throw new RuntimeException('Informe números inteiros válidos.');
    }

    return max(0, (int) $valor);
}

function ponto_minutos_hora(?string $hora): ?int
{
    $segundos = ponto_segundos_hora($hora);

    if ($segundos === null) {
        return null;
    }

    return intdiv($segundos, 60);
}

function ponto_segundos_hora(?string $hora): ?int
{
    if (!$hora) {
        return null;
    }

    $partes = array_map('intval', explode(':', $hora));
    $h = $partes[0] ?? 0;
    $m = $partes[1] ?? 0;
    $s = $partes[2] ?? 0;

    return ($h * 3600) + ($m * 60) + $s;
}

function ponto_intervalo_segundos(?string $inicio, ?string $fim): ?int
{
    $inicioSegundos = ponto_segundos_hora($inicio);
    $fimSegundos = ponto_segundos_hora($fim);

    if ($inicioSegundos === null || $fimSegundos === null || $fimSegundos < $inicioSegundos) {
        return null;
    }

    return $fimSegundos - $inicioSegundos;
}

function ponto_intervalo(?string $inicio, ?string $fim): ?int
{
    $segundos = ponto_intervalo_segundos($inicio, $fim);

    if ($segundos === null) {
        return null;
    }

    return intdiv($segundos, 60);
}

function ponto_calcular(array $ponto): array
{
    if (!ponto_dia_trabalhado($ponto['status_dia'] ?? 'trabalhado')) {
        return [
            'cafe' => null,
            'almoco' => null,
            'permanencia' => null,
            'liquido' => null,
            'cafe_segundos' => null,
            'almoco_segundos' => null,
            'permanencia_segundos' => null,
            'liquido_segundos' => null,
        ];
    }

    $permanenciaSegundos = ponto_intervalo_segundos($ponto['entrada'] ?? null, $ponto['saida'] ?? null);
    $cafeSegundos = ponto_intervalo_segundos($ponto['cafe_saida'] ?? null, $ponto['cafe_retorno'] ?? null);
    $almocoSegundos = ponto_intervalo_segundos($ponto['almoco_saida'] ?? null, $ponto['almoco_retorno'] ?? null);
    $liquidoSegundos = $permanenciaSegundos;

    if ($liquidoSegundos !== null) {
        $liquidoSegundos -= $cafeSegundos ?? 0;
        $liquidoSegundos -= $almocoSegundos ?? 0;
        $liquidoSegundos = max(0, $liquidoSegundos);
    }

    return [
        'cafe' => $cafeSegundos === null ? null : intdiv($cafeSegundos, 60),
        'almoco' => $almocoSegundos === null ? null : intdiv($almocoSegundos, 60),
        'permanencia' => $permanenciaSegundos === null ? null : intdiv($permanenciaSegundos, 60),
        'liquido' => $liquidoSegundos === null ? null : intdiv($liquidoSegundos, 60),
        'cafe_segundos' => $cafeSegundos,
        'almoco_segundos' => $almocoSegundos,
        'permanencia_segundos' => $permanenciaSegundos,
        'liquido_segundos' => $liquidoSegundos,
    ];
}

function ponto_formatar_minutos(?int $minutos): string
{
    if ($minutos === null) {
        return '-';
    }

    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;

    return sprintf('%02dh%02d', $horas, $resto);
}

function ponto_formatar_hora(?string $hora): string
{
    if (!$hora) {
        return '-';
    }

    return substr($hora, 0, 5);
}

function ponto_formatar_hora_completa(?string $hora): string
{
    if (!$hora) {
        return '';
    }

    return substr($hora, 0, 8);
}

function ponto_hora_tem_segundos(?string $hora): bool
{
    return $hora !== null && strlen($hora) >= 8 && substr($hora, 6, 2) !== '00';
}

function ponto_moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function ponto_mensagem_erro(Throwable $erro): string
{
    if ($erro instanceof RuntimeException) {
        return $erro->getMessage();
    }

    error_log('Erro SONI PONTO: ' . $erro->getMessage());

    return 'Não foi possível concluir a operação agora.';
}

function ponto_lojas(PDO $pdo, bool $somenteAtivas = true): array
{
    $sql = 'SELECT id, codigo_loja, numero_interno, nome, endereco, cidade, responsavel, telefone, horario_padrao, cor_identificacao, observacoes, ativo FROM lojas_trabalho';

    if ($somenteAtivas) {
        $sql .= ' WHERE ativo = 1';
    }

    $sql .= ' ORDER BY codigo_loja, nome';

    return $pdo->query($sql)->fetchAll();
}

function ponto_trajetos_ativos_por_loja(PDO $pdo): array
{
    $trajetos = $pdo->query(
        'SELECT t.id, t.loja_id, l.codigo_loja, t.nome_trajeto, t.tipo_transporte,
                t.valor_ida, t.valor_volta, t.valor_total, t.padrao_loja, t.observacoes
         FROM trajetos_trabalho t
         INNER JOIN lojas_trabalho l ON l.id = t.loja_id
         WHERE t.ativo = 1
         ORDER BY l.codigo_loja, t.nome_trajeto'
    )->fetchAll();

    $porLoja = [];

    foreach ($trajetos as $trajeto) {
        $id = (int) $trajeto['id'];
        $codigoLoja = (string) $trajeto['codigo_loja'];
        $porLoja[$codigoLoja][] = [
            'id' => $id,
            'loja_id' => (int) $trajeto['loja_id'],
            'codigo_loja' => $codigoLoja,
            'nome' => (string) $trajeto['nome_trajeto'],
            'rotulo' => (string) $trajeto['nome_trajeto'],
            'observacoes' => (string) ($trajeto['observacoes'] ?? ''),
            'valor_ida' => (float) $trajeto['valor_ida'],
            'valor_volta' => (float) $trajeto['valor_volta'],
            'valor_total' => (float) $trajeto['valor_total'],
            'padrao' => (int) $trajeto['padrao_loja'] === 1,
            'trechos' => ponto_trajeto_trechos($pdo, $id),
        ];
    }

    return $porLoja;
}

function ponto_trajeto_trechos(PDO $pdo, int $trajetoId, ?string $data = null): array
{
    $data = $data ?: date('Y-m-d');
    $stmt = $pdo->prepare(
        'SELECT direcao, ordem_trecho, tipo_transporte, descricao, tarifa_unitaria, quantidade, subtotal
         FROM trajeto_trechos_trabalho
         WHERE trajeto_id = :trajeto_id AND ativo = 1
           AND vigencia_inicio <= :data AND (vigencia_fim IS NULL OR vigencia_fim >= :data)
         ORDER BY direcao, ordem_trecho'
    );
    $stmt->execute([':trajeto_id' => $trajetoId, ':data' => $data]);
    return $stmt->fetchAll();
}

function ponto_transporte_trajetos(PDO $pdo, ?int $idaId, ?int $voltaId, int $lojaId, string $data): array
{
    $resultado = ['configurado' => false, 'previsto' => 0.0, 'ida' => [], 'volta' => []];
    foreach ([['id'=>$idaId,'direcao'=>'ida'], ['id'=>$voltaId,'direcao'=>'volta']] as $item) {
        if (!$item['id']) continue;
        $stmt = $pdo->prepare(
            'SELECT id, valor_ida, valor_volta FROM trajetos_trabalho
             WHERE id = :id AND loja_id = :loja_id AND ativo = 1'
        );
        $stmt->execute([':id'=>$item['id'], ':loja_id'=>$lojaId]);
        $trajeto = $stmt->fetch();
        if (!$trajeto) throw new RuntimeException('Trajeto de transporte inválido para a loja.');
        $resultado['configurado'] = true;
        $resultado['previsto'] += (float) $trajeto['valor_' . $item['direcao']];
        $trechos = ponto_trajeto_trechos($pdo, (int)$trajeto['id'], $data);
        $resultado[$item['direcao']] = array_values(array_filter($trechos, static fn(array $t): bool => $t['direcao'] === $item['direcao']));
    }
    return $resultado;
}

function ponto_reembolso_calculos(float $recebido, float $gasto, float $aprovado = 0.0, float $reembolsado = 0.0): array
{
    $diferenca = max(0.0, round($gasto - $recebido, 2));
    return [
        'diferenca' => $diferenca,
        'saldo_vt' => max(0.0, round($recebido - $gasto, 2)),
        'saldo_reembolso' => max(0.0, round(($aprovado > 0 ? $aprovado : $diferenca) - $reembolsado, 2)),
    ];
}

function ponto_reembolso_situacao_pagamento(float $aprovado, float $jaPago, float $novoPagamento): array
{
    $total = round($jaPago + $novoPagamento, 2);
    if ($novoPagamento <= 0 || $aprovado <= 0 || $total > $aprovado) {
        throw new RuntimeException('Pagamento excede o valor aprovado.');
    }
    return ['total'=>$total, 'situacao'=>$total < $aprovado ? 'parcialmente_pago' : 'pago'];
}

function ponto_upload_comprovante(array $arquivo, ?string $atual = null): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $atual;
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no envio do comprovante.');
    if ((int)($arquivo['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('O comprovante deve ter no máximo 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$arquivo['tmp_name']) ?: '';
    $extensoes = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensoes[$mime])) throw new RuntimeException('Envie comprovante PDF, JPG, PNG ou WEBP.');
    $pasta = dirname(__DIR__, 2) . '/uploads/ponto-reembolsos';
    if (!is_dir($pasta) && !mkdir($pasta, 0750, true) && !is_dir($pasta)) throw new RuntimeException('Não foi possível preparar o armazenamento.');
    $nome = bin2hex(random_bytes(20)) . '.' . $extensoes[$mime];
    if (!move_uploaded_file((string)$arquivo['tmp_name'], $pasta . '/' . $nome)) throw new RuntimeException('Não foi possível armazenar o comprovante.');
    return $nome;
}

function ponto_reembolso_sincronizar(PDO $pdo, int $pontoId, array $dados): void
{
    $calculo = ponto_reembolso_calculos((float)$dados['transporte_recebido'], (float)$dados['gasto_transporte']);
    if ($calculo['diferenca'] <= 0 || empty($dados['loja_id'])) return;
    $stmt = $pdo->prepare('SELECT * FROM ponto_reembolsos_transporte WHERE ponto_id = :ponto_id');
    $stmt->execute([':ponto_id'=>$pontoId]); $antes = $stmt->fetch() ?: [];
    $trajetoId = $dados['trajeto_ida_id'] ?: $dados['trajeto_volta_id'];
    $params = [
        ':ponto_id'=>$pontoId, ':loja_id'=>$dados['loja_id'], ':trajeto_id'=>$trajetoId,
        ':previsto'=>$dados['transporte_previsto'], ':recebido'=>$dados['transporte_recebido'],
        ':gasto'=>$dados['gasto_transporte'], ':diferenca'=>$calculo['diferenca'],
    ];
    if (!$antes) {
        $pdo->prepare(
            "INSERT INTO ponto_reembolsos_transporte
             (ponto_id,loja_id,trajeto_id,valor_previsto,valor_recebido,valor_gasto,diferenca_calculada,situacao)
             VALUES (:ponto_id,:loja_id,:trajeto_id,:previsto,:recebido,:gasto,:diferenca,'calculado')"
        )->execute($params);
        $id=(int)$pdo->lastInsertId();
        ponto_historico($pdo,'ponto_reembolsos_transporte',$id,'geracao_diferenca',[],$params);
    } elseif ($antes['situacao'] === 'calculado') {
        $params[':id']=$antes['id'];
        $pdo->prepare(
            'UPDATE ponto_reembolsos_transporte SET loja_id=:loja_id,trajeto_id=:trajeto_id,
             valor_previsto=:previsto,valor_recebido=:recebido,valor_gasto=:gasto,diferenca_calculada=:diferenca
             WHERE id=:id'
        )->execute(array_diff_key($params,[':ponto_id'=>true]));
        ponto_historico($pdo,'ponto_reembolsos_transporte',(int)$antes['id'],'geracao_diferenca',$antes,$params);
    }
}

function ponto_validar_trajeto_loja(PDO $pdo, int $trajetoId, int $lojaId): ?int
{
    if ($trajetoId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT *
         FROM trajetos_trabalho
         WHERE id = :id AND loja_id = :loja_id AND ativo = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $trajetoId, ':loja_id' => $lojaId]);
    $trajeto = $stmt->fetch();

    if (!$trajeto) {
        throw new RuntimeException('O trajeto selecionado nÃ£o pertence Ã  loja informada ou estÃ¡ inativo.');
    }

    return $trajetoId;
}

function ponto_buscar_ponto(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, l.codigo_loja, l.nome AS loja_nome
         FROM pontos_trabalho p
         LEFT JOIN lojas_trabalho l ON l.id = p.loja_id
         WHERE p.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $ponto = $stmt->fetch();

    return $ponto ?: null;
}

function ponto_configuracao_vigente(PDO $pdo, string $data): ?array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM ponto_configuracoes
         WHERE vigencia_inicio <= :data AND (vigencia_fim IS NULL OR vigencia_fim >= :data)
         ORDER BY vigencia_inicio DESC, id DESC LIMIT 1'
    );
    $stmt->execute([':data' => $data]);
    return $stmt->fetch() ?: null;
}

function ponto_competencia_fechada(PDO $pdo, string $data): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM ponto_competencias
         WHERE ano = YEAR(:data) AND mes = MONTH(:data) AND situacao = 'fechada' LIMIT 1"
    );
    $stmt->execute([':data' => $data]);
    return (bool) $stmt->fetchColumn();
}

function ponto_admin_nome(): string
{
    $admin = admin_atual() ?? [];
    return (string) ($admin['nome'] ?? $_SESSION['admin_nome'] ?? 'Administrador');
}

function ponto_historico(PDO $pdo, string $entidade, ?int $entidadeId, string $acao, array $antes = [], array $depois = [], ?string $justificativa = null): void
{
    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $stmt = $pdo->prepare(
        'INSERT INTO ponto_historico
         (entidade, entidade_id, acao, valor_anterior, valor_novo, justificativa, usuario)
         VALUES (:entidade, :entidade_id, :acao, :anterior, :novo, :justificativa, :usuario)'
    );
    $stmt->execute([
        ':entidade' => $entidade, ':entidade_id' => $entidadeId, ':acao' => $acao,
        ':anterior' => $antes ? json_encode($antes, $jsonFlags) : null,
        ':novo' => $depois ? json_encode($depois, $jsonFlags) : null,
        ':justificativa' => $justificativa, ':usuario' => ponto_admin_nome(),
    ]);
}

function ponto_exigir_competencia_aberta(PDO $pdo, string $data): void
{
    if (ponto_competencia_fechada($pdo, $data)) {
        throw new RuntimeException('A competência está fechada. Reabra o mês com justificativa antes desta operação.');
    }
}

function ponto_configuracao_sobrepoe(PDO $pdo, string $inicio, ?string $fim, int $ignorarId = 0): bool
{
    $fimComparacao = $fim ?: '9999-12-31';
    $stmt = $pdo->prepare(
        "SELECT 1 FROM ponto_configuracoes
         WHERE id <> :id
           AND vigencia_inicio <= :fim
           AND COALESCE(vigencia_fim, '9999-12-31') >= :inicio
         LIMIT 1"
    );
    $stmt->execute([':id' => $ignorarId, ':fim' => $fimComparacao, ':inicio' => $inicio]);
    return (bool) $stmt->fetchColumn();
}

function ponto_diferenca_horarios(?string $soni, ?string $rhid): ?int
{
    $a = ponto_segundos_hora_puro($soni);
    $b = ponto_segundos_hora_puro($rhid);
    return ($a === null || $b === null) ? null : intdiv($b - $a, 60);
}

function ponto_dados_post(): array
{
    global $pdo;

    $data = ponto_validar_data(trim($_POST['data'] ?? ''));
    $statusDia = trim((string) ($_POST['status_dia'] ?? 'trabalhado'));

    if (!ponto_status_dia_valido($statusDia)) {
        throw new RuntimeException('Selecione um status do dia válido.');
    }

    if (!ponto_dia_trabalhado($statusDia)) {
        return [
            'data' => $data,
            'dia_semana' => ponto_dia_semana($data),
            'status_dia' => $statusDia,
            'loja_id' => null,
            'trajeto_ida_id' => null,
            'trajeto_volta_id' => null,
            'entrada' => null,
            'cafe_saida' => null,
            'cafe_retorno' => null,
            'almoco_saida' => null,
            'almoco_retorno' => null,
            'saida' => null,
            'transporte_observacao' => '',
            'transporte_previsto' => 0.0,
            'transporte_recebido' => 0.0,
            'gasto_transporte' => 0.0,
            'bilhetes_perdidos' => 0,
            'valor_bilhetes_perdidos' => 0.0,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
    }

    $lojaId = (int) ($_POST['loja_id'] ?? 0);

    if ($lojaId <= 0) {
        throw new RuntimeException('Selecione a loja.');
    }

    $trajetoIdaId = (int) ($_POST['trajeto_ida_id'] ?? 0);
    $trajetoVoltaId = (int) ($_POST['trajeto_volta_id'] ?? 0);
    $trajetoIdaId = ponto_validar_trajeto_loja($pdo, $trajetoIdaId, $lojaId);
    $trajetoVoltaId = ponto_validar_trajeto_loja($pdo, $trajetoVoltaId, $lojaId);
    $transporteConfig = ponto_transporte_trajetos($pdo, $trajetoIdaId, $trajetoVoltaId, $lojaId, $data);
    $previstoInformado = trim((string) ($_POST['transporte_previsto'] ?? ''));
    $recebidoInformado = trim((string) ($_POST['transporte_recebido'] ?? ''));
    $transportePrevisto = $previstoInformado === '' && $transporteConfig['configurado']
        ? (float) $transporteConfig['previsto']
        : ponto_decimal($previstoInformado);
    $transporteRecebido = $recebidoInformado === '' && $transporteConfig['configurado']
        ? (float) $transporteConfig['previsto']
        : ponto_decimal($recebidoInformado);

    return [
        'data' => $data,
        'dia_semana' => ponto_dia_semana($data),
        'status_dia' => $statusDia,
        'loja_id' => $lojaId,
        'trajeto_ida_id' => $trajetoIdaId,
        'trajeto_volta_id' => $trajetoVoltaId,
        'entrada' => ponto_normalizar_hora($_POST['entrada'] ?? ''),
        'cafe_saida' => ponto_normalizar_hora($_POST['cafe_saida'] ?? ''),
        'cafe_retorno' => ponto_normalizar_hora($_POST['cafe_retorno'] ?? ''),
        'almoco_saida' => ponto_normalizar_hora($_POST['almoco_saida'] ?? ''),
        'almoco_retorno' => ponto_normalizar_hora($_POST['almoco_retorno'] ?? ''),
        'saida' => ponto_normalizar_hora($_POST['saida'] ?? ''),
        'transporte_observacao' => trim($_POST['transporte_observacao'] ?? ''),
        'transporte_previsto' => $transportePrevisto,
        'transporte_recebido' => $transporteRecebido,
        'gasto_transporte' => ponto_decimal($_POST['gasto_transporte'] ?? ''),
        'bilhetes_perdidos' => ponto_int($_POST['bilhetes_perdidos'] ?? ''),
        'valor_bilhetes_perdidos' => ponto_decimal($_POST['valor_bilhetes_perdidos'] ?? ''),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ];
}

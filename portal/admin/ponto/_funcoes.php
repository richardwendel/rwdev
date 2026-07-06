<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/auth.php';

exigir_admin();

function ponto_admin_url(string $arquivo = 'index.php'): string
{
    return $arquivo;
}

function ponto_render_header(string $titulo): void
{
    ?>
    <header class="app-header admin">
      <a href="../dashboard.php" class="marca">RWDEV Admin</a>
      <nav>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../clientes.php">Clientes</a>
        <a href="../convites.php">Convites</a>
        <a href="../projetos.php">Projetos</a>
        <a href="../solicitacoes.php">Solicitações</a>
        <a href="../depoimentos.php">Depoimentos</a>
        <a href="../diagnostico-metricas.php">&#128202; Diagnóstico</a>
        <a href="index.php">SONI PONTO</a>
        <a href="../documentos-trabalho/index.php">DOCUMENTOS</a>
        <a href="../../logout.php">Sair</a>
      </nav>
    </header>
    <?php
}

function ponto_render_nav(string $ativo): void
{
    $itens = [
        'index.php' => 'Registros',
        'novo.php' => 'Novo ponto',
        'resumo.php' => 'Resumo mensal',
        'lojas.php' => 'Lojas',
        'trajetos.php' => 'Trajetos',
    ];
    ?>
    <nav class="ponto-tabs" aria-label="Navegação SONI PONTO">
      <?php foreach ($itens as $href => $rotulo): ?>
        <a class="<?= $ativo === $href ? 'ativo' : '' ?>" href="<?= e($href) ?>"><?= e($rotulo) ?></a>
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
    $sql = 'SELECT id, codigo_loja, nome FROM lojas_trabalho';

    if ($somenteAtivas) {
        $sql .= ' WHERE ativo = 1';
    }

    $sql .= ' ORDER BY codigo_loja, nome';

    return $pdo->query($sql)->fetchAll();
}

function ponto_buscar_ponto(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, l.codigo_loja, l.nome AS loja_nome
         FROM pontos_trabalho p
         INNER JOIN lojas_trabalho l ON l.id = p.loja_id
         WHERE p.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $ponto = $stmt->fetch();

    return $ponto ?: null;
}

function ponto_dados_post(): array
{
    $data = ponto_validar_data(trim($_POST['data'] ?? ''));
    $lojaId = (int) ($_POST['loja_id'] ?? 0);

    if ($lojaId <= 0) {
        throw new RuntimeException('Selecione a loja.');
    }

    return [
        'data' => $data,
        'dia_semana' => ponto_dia_semana($data),
        'loja_id' => $lojaId,
        'entrada' => ponto_normalizar_hora($_POST['entrada'] ?? ''),
        'cafe_saida' => ponto_normalizar_hora($_POST['cafe_saida'] ?? ''),
        'cafe_retorno' => ponto_normalizar_hora($_POST['cafe_retorno'] ?? ''),
        'almoco_saida' => ponto_normalizar_hora($_POST['almoco_saida'] ?? ''),
        'almoco_retorno' => ponto_normalizar_hora($_POST['almoco_retorno'] ?? ''),
        'saida' => ponto_normalizar_hora($_POST['saida'] ?? ''),
        'transporte_observacao' => trim($_POST['transporte_observacao'] ?? ''),
        'gasto_transporte' => ponto_decimal($_POST['gasto_transporte'] ?? ''),
        'bilhetes_perdidos' => ponto_int($_POST['bilhetes_perdidos'] ?? ''),
        'valor_bilhetes_perdidos' => ponto_decimal($_POST['valor_bilhetes_perdidos'] ?? ''),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ];
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../portal/includes/agv_leads.php';

function teste_agv(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

teste_agv(agv_normalizar_placa(' abc 1234 ') === 'ABC-1234', 'Placa antiga sem hífen não foi normalizada.');
teste_agv(agv_normalizar_placa('abc-1234') === 'ABC-1234', 'Placa antiga com hífen não foi normalizada.');
teste_agv(agv_placa_valida(agv_normalizar_placa('ABC-1234')), 'Placa antiga válida foi rejeitada.');
teste_agv(agv_normalizar_placa(' abc1d23 ') === 'ABC1D23', 'Placa Mercosul não foi normalizada.');
teste_agv(agv_placa_valida(agv_normalizar_placa('ABC1D23')), 'Placa Mercosul válida foi rejeitada.');
teste_agv(!agv_placa_valida(agv_normalizar_placa('ABC12D3')), 'Placa inválida foi aceita.');

teste_agv(agv_normalizar_whatsapp('+55 (11) 98765-4321') === '11987654321', 'WhatsApp com código do país não foi normalizado.');
teste_agv(agv_whatsapp_valido('11987654321'), 'WhatsApp celular válido foi rejeitado.');
teste_agv(agv_whatsapp_valido('1132654321'), 'WhatsApp fixo válido foi rejeitado.');
teste_agv(!agv_whatsapp_valido('119876543'), 'WhatsApp curto foi aceito.');

$entrada = [
    'nome' => 'Maria da Silva',
    'whatsapp' => '(11) 98765-4321',
    'cidade' => 'Suzano',
    'veiculo' => 'Honda Civic',
    'ano' => '2022',
    'placa' => 'abc1d23',
    'privacidade_aceita' => true,
];
$validacao = agv_validar_lead($entrada, 2026);
teste_agv($validacao['erros'] === [], 'Lead válido apresentou erros.');

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(
    'CREATE TABLE agv_leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo TEXT UNIQUE,
        nome TEXT NOT NULL,
        whatsapp TEXT NOT NULL,
        cidade TEXT NOT NULL,
        veiculo TEXT NOT NULL,
        ano INTEGER NOT NULL,
        placa TEXT NOT NULL,
        origem TEXT NOT NULL,
        status TEXT NOT NULL,
        privacidade_aceita_em TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )'
);

$registro = agv_salvar_lead($pdo, $validacao['dados']);
teste_agv($registro['codigo'] === 'AGV-000001', 'Código sequencial incorreto.');

$segundoRegistro = agv_salvar_lead($pdo, $validacao['dados']);
teste_agv($segundoRegistro['codigo'] === 'AGV-000002', 'Código único do segundo lead incorreto.');

$salvo = $pdo->query('SELECT * FROM agv_leads WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
teste_agv(is_array($salvo), 'Lead não foi salvo antes da criação do link.');
teste_agv($salvo['origem'] === AGV_ORIGEM, 'Origem do lead incorreta.');
teste_agv($salvo['status'] === 'Novo', 'Status inicial incorreto.');
teste_agv($salvo['placa'] === 'ABC1D23', 'Placa não foi armazenada normalizada.');

$url = agv_url_whatsapp($validacao['dados'], $registro['codigo']);
teste_agv(str_starts_with($url, 'https://wa.me/5511940195111?text='), 'Destino do WhatsApp incorreto.');
teste_agv(!str_contains($url, ' '), 'URL do WhatsApp contém espaços sem codificação.');
teste_agv(str_contains($url, '%C3%A1'), 'Texto UTF-8 não foi codificado corretamente na URL.');
$mensagem = rawurldecode((string) parse_url($url, PHP_URL_QUERY));
teste_agv(str_contains($mensagem, 'Nome: Maria da Silva'), 'Nome ausente na mensagem.');
teste_agv(str_contains($mensagem, 'WhatsApp: (11) 98765-4321'), 'WhatsApp ausente na mensagem.');
teste_agv(str_contains($mensagem, 'Cidade: Suzano'), 'Cidade ausente na mensagem.');
teste_agv(str_contains($mensagem, 'Veículo: Honda Civic'), 'Veículo ausente na mensagem.');
teste_agv(str_contains($mensagem, 'Ano: 2022'), 'Ano ausente na mensagem.');
teste_agv(str_contains($mensagem, 'Placa: ABC1D23'), 'Placa ausente na mensagem.');
teste_agv(str_contains($mensagem, 'Código da solicitação: AGV-000001'), 'Código ausente na mensagem.');
teste_agv(agv_status_disponiveis() === ['Novo', 'Encaminhado ao Carlos', 'Em atendimento', 'Fechado', 'Perdido', 'Acompanhamento'], 'Lista de status incorreta.');

echo "AGV leads: testes concluídos com sucesso.\n";

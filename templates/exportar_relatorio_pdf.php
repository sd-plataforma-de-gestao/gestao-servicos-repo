<?php
require_once '../../vendor/autoload.php';
include("../../config/database.php");

session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    http_response_code(403);
    echo "Acesso negado. Faça login.";
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

function construirWhereData($params) {
    $where = " WHERE 1=1 ";

    $data_inicio = isset($params['data_inicio']) ? $params['data_inicio'] : null;
    $data_fim = isset($params['data_fim']) ? $params['data_fim'] : null;
    $periodo = isset($params['periodo']) ? $params['periodo'] : null;

    global $conn;

    if ($data_inicio && $data_fim) {
        $where .= " AND a.criado_em BETWEEN '" . $conn->real_escape_string($data_inicio) . " 00:00:00' AND '" . $conn->real_escape_string($data_fim) . " 23:59:59' ";
    } elseif ($periodo && $periodo !== 'personalizado') {
        $data_inicio_calc = date('Y-m-d 00:00:00', strtotime("-{$periodo} days"));
        $data_fim_calc = date('Y-m-d 23:59:59');
        $where .= " AND a.criado_em BETWEEN '" . $conn->real_escape_string($data_inicio_calc) . "' AND '" . $conn->real_escape_string($data_fim_calc) . "' ";
    }
    return $where;
}

function buscarDadosRelatorio($acao, $params = []) {
    global $conn;
    try {
        switch ($acao) {
            case 'dispensacoes':
                $where_atendimentos = construirWhereData($params);
                $sql = "
                    SELECT 
                        a.id as atendimento_id,
                        a.criado_em as data_atendimento,
                        p.nome as paciente_nome,
                        m.nome as medicamento_nome,
                        am.quantidade_dispensada,
                        a.tipo_atendimento
                    FROM atendimentos a
                    JOIN pacientes p ON a.paciente_id = p.id
                    JOIN atendimento_medicamentos am ON a.id = am.atendimento_id
                    JOIN medicamentos m ON am.medicamento_id = m.id
                    {$where_atendimentos}
                    AND a.status_atendimento = 'Concluído'
                    ORDER BY a.criado_em DESC
                    LIMIT 200
                ";
                $result = $conn->query($sql);
                if (!$result) throw new Exception("Erro na consulta de dispensações: " . $conn->error);
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'atendimento_id' => (int)$row['atendimento_id'],
                        'data_atendimento' => date('d/m/Y H:i', strtotime($row['data_atendimento'])),
                        'paciente_nome' => $row['paciente_nome'],
                        'medicamento_nome' => $row['medicamento_nome'],
                        'quantidade_dispensada' => (int)$row['quantidade_dispensada'],
                        'tipo_atendimento' => $row['tipo_atendimento']
                    ];
                }
                return $data;

                case 'estoque_critico':
                $threshold = isset($params['threshold']) ? (int)$params['threshold'] : 5;
                $sql = "SELECT id, nome, quantidade, data_validade FROM medicamentos WHERE quantidade <= {$threshold} ORDER BY quantidade ASC";
                $result = $conn->query($sql);
                if (!$result) throw new Exception("Erro na consulta estoque_critico: " . $conn->error);
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'medicamento_id' => (int)$row['id'],
                        'medicamento_nome' => $row['nome'],
                        'quantidade' => (int)$row['quantidade'],
                        'data_validade' => $row['data_validade']
                    ];
                }
                return $data;

            default:
                return [];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar dados para relatório PDF: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}


$type = $_GET['type'] ?? '';
$periodo = $_GET['periodo'] ?? '30';
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$limit = (int)($_GET['limit'] ?? 10);
$threshold = (int)($_GET['threshold'] ?? 5);

if (empty($type)) {
    http_response_code(400);
    echo "Tipo de relatório não especificado.";
    exit;
}

$params = [
    'periodo' => $periodo,
    'data_inicio' => $data_inicio,
    'data_fim' => $data_fim,
    'limit' => $limit,
    'threshold' => $threshold
];

$data = buscarDadosRelatorio($type, $params);

if (isset($data['error'])) {
    http_response_code(500);
    echo "Erro ao buscar dados: " . $data['error'];
    exit;
}

$titulo_relatorio = [
    'kpis_over_time' => 'KPIs (Evolução)',
    'top_medicamentos' => 'Top Medicamentos',
    'estoque_critico' => 'Estoque Crítico',
    'tratamentos_continuos' => 'Tratamentos Contínuos',
    'dispensacoes' => 'Histórico de Dispensações',
    'produtividade_farmaceuticos' => 'Produtividade Farmacêuticos',
    'unidades_desempenho' => 'Desempenho por Unidade',
    'receita_por_medicamento' => 'Receita por Medicamento',
    'predictive_atendimentos' => 'Previsão: Atendimentos (30 dias)',
    'predictive_adesao' => 'Previsão: Taxa de Adesão (30 dias)'
][$type] ?? 'Relatório';

$filtro_periodo_texto = '';
if ($periodo === 'personalizado' && $data_inicio && $data_fim) {
    $filtro_periodo_texto = "Período: De {$data_inicio} a {$data_fim}";
} elseif ($periodo !== 'personalizado') {
    $filtro_periodo_texto = "Período: Últimos {$periodo} dias";
}

$logo_principal_url = $_SERVER['DOCUMENT_ROOT'] . '/portal-repo-og/assets/logo-header.png';
$logo_rodape_url = $_SERVER['DOCUMENT_ROOT'] . '/portal-repo-og/assets/favicon.png';

if (!file_exists($logo_principal_url)) {
    $logo_principal_url = '';
}
if (!file_exists($logo_rodape_url)) {
    $logo_rodape_url = '';
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 2cm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1cm;
            padding-bottom: 0.2cm;
            border-bottom: 1px solid #1a6d40;
        }
        .header img {
            max-height: 30px;
            width: auto;
        }
        .header-info {
            text-align: right;
            font-size: 10px;
            color: #6c757d;
        }
        .title {
            color: #1a6d40;
            font-size: 18px;
            font-weight: bold;
            margin: 0.5cm 0 0.3cm 0;
            text-align: left;
        }
        .section-title {
            color: #1a6d40;
            font-size: 14px;
            font-weight: bold;
            margin: 0.8cm 0 0.3cm 0;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.8cm;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
            text-align: right;
            padding: 0 2cm 0 2cm;
            font-size: 9px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer img {
            max-height: 12px;
            width: auto;
            margin-right: 5px;
        }
        .footer-text {
            flex-grow: 1;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="' . $logo_principal_url . '" alt="Logo Principal">
        <div class="header-info">
            <p>' . $filtro_periodo_texto . '</p>
            <p>Data de Geração: ' . date('d/m/Y H:i:s') . '</p>
        </div>
    </div>

    <h1 class="title">Relatório: ' . $titulo_relatorio . '</h1>';

if (!empty($data)) {
    $html .= '<table>';

    switch ($type) {
        case 'estoque_critico':
            $html .= '<tr><th>Medicamento</th><th>Quantidade</th><th>Validade</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['medicamento_nome']) . '</td><td>' . $item['quantidade'] . '</td><td>' . $item['data_validade'] . '</td></tr>';
            }
            break;
        case 'top_medicamentos':
            $html .= '<tr><th>Medicamento</th><th>Total Dispensado</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['medicamento_nome']) . '</td><td>' . $item['total_dispensado'] . '</td></tr>';
            }
            break;
        case 'dispensacoes':
            $html .= '<tr><th>ID Atendimento</th><th>Data</th><th>Paciente</th><th>Medicamento</th><th>Quantidade</th><th>Tipo</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . $item['atendimento_id'] . '</td><td>' . $item['data_atendimento'] . '</td><td>' . htmlspecialchars($item['paciente_nome']) . '</td><td>' . htmlspecialchars($item['medicamento_nome']) . '</td><td>' . $item['quantidade_dispensada'] . '</td><td>' . htmlspecialchars($item['tipo_atendimento']) . '</td></tr>';
            }
            break;
        case 'tratamentos_continuos':
            $html .= '<tr><th>Paciente</th><th>Medicamento</th><th>Início Tratamento</th><th>Dispensações</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['paciente_nome']) . '</td><td>' . htmlspecialchars($item['medicamento_nome']) . '</td><td>' . $item['data_inicio_tratamento'] . '</td><td>' . $item['total_dispensacoes'] . '</td></tr>';
            }
            break;
        case 'produtividade_farmaceuticos':
            $html .= '<tr><th>Farmacêutico</th><th>Total Atendimentos</th><th>Cancelados</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['farmaceutico_nome']) . '</td><td>' . $item['total_atendimentos'] . '</td><td>' . $item['total_cancelados'] . '</td></tr>';
            }
            break;
        case 'unidades_desempenho':
            $html .= '<tr><th>Unidade</th><th>Status</th><th>Total Atendimentos</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['unidade_nome']) . '</td><td>' . $item['status'] . '</td><td>' . $item['total_atendimentos'] . '</td></tr>';
            }
            break;
        case 'receita_por_medicamento':
            $html .= '<tr><th>Medicamento</th><th>Receita (R$)</th></tr>';
            foreach ($data as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['medicamento_nome']) . '</td><td>' . number_format($item['receita'], 2, ',', '.') . '</td></tr>';
            }
            break;
        default:
            $html .= '<tr><td colspan="100%">Tipo de relatório não suportado para exportação em tabela.</td></tr>';
    }

    $html .= '</table>';
} else {
    $html .= '<p>Nenhum dado encontrado para os filtros aplicados.</p>';
}

$html .= '
    <div class="footer">
        <img src="' . $logo_rodape_url . '" alt="Mini Logo">
        <div class="footer-text">
            <span>Vitally - ' . date('Y') . '</span>
        </div>
    </div>
</body>
</html>';

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nome_arquivo = 'relatorio_' . $type . '_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($nome_arquivo, ['Attachment' => 1]);

?>
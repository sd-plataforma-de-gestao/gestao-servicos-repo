<?php
include("../config/database.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

function getDataInicio($periodo) {
    $hoje = new DateTime();
    switch ($periodo) {
        case '7':
            $inicio = $hoje->modify('-7 days');
            break;
        case '30':
            $inicio = $hoje->modify('-30 days');
            break;
        case '90':
            $inicio = $hoje->modify('-90 days');
            break;
        case '365':
            $inicio = $hoje->modify('-1 year');
            break;
        default:
            $inicio = $hoje->modify('-30 days');
            break;
    }
    return $inicio->format('Y-m-d 00:00:00');
}

function getDataInicioAnterior($periodo) {
    $hoje = new DateTime();
    $periodo_atual_inicio = clone $hoje;
    $periodo_atual_fim = clone $hoje;

    switch ($periodo) {
        case '7':
            $periodo_atual_inicio->modify('-7 days');
            $periodo_anterior_inicio = clone $periodo_atual_inicio;
            $periodo_anterior_fim = clone $periodo_atual_inicio;
            $periodo_anterior_inicio->modify('-7 days');
            break;
        case '30':
            $periodo_atual_inicio->modify('-30 days');
            $periodo_anterior_inicio = clone $periodo_atual_inicio;
            $periodo_anterior_fim = clone $periodo_atual_inicio;
            $periodo_anterior_inicio->modify('-30 days');
            break;
        case '90':
            $periodo_atual_inicio->modify('-90 days');
            $periodo_anterior_inicio = clone $periodo_atual_inicio;
            $periodo_anterior_fim = clone $periodo_atual_inicio;
            $periodo_anterior_inicio->modify('-90 days');
            break;
        case '365':
            $periodo_atual_inicio->modify('-1 year');
            $periodo_anterior_inicio = clone $periodo_atual_inicio;
            $periodo_anterior_fim = clone $periodo_atual_inicio;
            $periodo_anterior_inicio->modify('-1 year');
            break;
        default:
            $periodo_atual_inicio->modify('-30 days');
            $periodo_anterior_inicio = clone $periodo_atual_inicio;
            $periodo_anterior_fim = clone $periodo_atual_inicio;
            $periodo_anterior_inicio->modify('-30 days');
            break;
    }
    return [$periodo_anterior_inicio->format('Y-m-d H:i:s'), $periodo_anterior_fim->format('Y-m-d H:i:s')];
}

$periodo = $_GET['periodo'] ?? '30';
$data_inicio = getDataInicio($periodo);
list($data_inicio_anterior, $data_fim_anterior) = getDataInicioAnterior($periodo);

try {
    $sql_pacientes_ativos = "SELECT COUNT(*) AS total FROM pacientes WHERE status = 'ativo'";
    $result_pacientes_ativos = $conn->query($sql_pacientes_ativos);
    if (!$result_pacientes_ativos) {
        throw new Exception("Erro na consulta de pacientes: " . $conn->error);
    }
    $total_pacientes_ativos = $result_pacientes_ativos->fetch_assoc()['total'];

    $sql_atendimentos_atual = "
        SELECT COUNT(*) AS total_atendimentos
        FROM atendimentos
        WHERE status_atendimento = 'Concluído'
        AND criado_em BETWEEN ? AND NOW()
    ";
    $stmt_atendimentos_atual = $conn->prepare($sql_atendimentos_atual);
    if (!$stmt_atendimentos_atual) {
        throw new Exception("Erro na preparação da consulta de atendimentos (atual): " . $conn->error);
    }
    $stmt_atendimentos_atual->bind_param("s", $data_inicio);
    $stmt_atendimentos_atual->execute();
    $result_atendimentos_atual = $stmt_atendimentos_atual->get_result();
    $total_atendimentos_atual = $result_atendimentos_atual->fetch_assoc()['total_atendimentos'];
    $stmt_atendimentos_atual->close();

    $sql_farmaceuticos_ativos = "SELECT COUNT(*) AS total FROM farmaceuticos WHERE status = 'ativo'";
    $result_farmaceuticos_ativos = $conn->query($sql_farmaceuticos_ativos);
    if (!$result_farmaceuticos_ativos) {
        throw new Exception("Erro na consulta de farmacêuticos: " . $conn->error);
    }
    $total_farmaceuticos_ativos = $result_farmaceuticos_ativos->fetch_assoc()['total'];

    $sql_medicamentos_ativos = "SELECT COUNT(*) AS total FROM medicamentos WHERE quantidade > 0";
    $result_medicamentos_ativos = $conn->query($sql_medicamentos_ativos);
    if (!$result_medicamentos_ativos) {
        throw new Exception("Erro na consulta de medicamentos: " . $conn->error);
    }
    $total_medicamentos_ativos = $result_medicamentos_ativos->fetch_assoc()['total'];

    $sql_atendimentos_anterior = "
        SELECT COUNT(*) AS total_atendimentos
        FROM atendimentos
        WHERE status_atendimento = 'Concluído'
        AND criado_em BETWEEN ? AND ?
    ";
    $stmt_atendimentos_anterior = $conn->prepare($sql_atendimentos_anterior);
    if (!$stmt_atendimentos_anterior) {
        throw new Exception("Erro na preparação da consulta de atendimentos (anterior): " . $conn->error);
    }
    $stmt_atendimentos_anterior->bind_param("ss", $data_inicio_anterior, $data_fim_anterior);
    $stmt_atendimentos_anterior->execute();
    $result_atendimentos_anterior = $stmt_atendimentos_anterior->get_result();
    $total_atendimentos_anterior = $result_atendimentos_anterior->fetch_assoc()['total_atendimentos'];
    $stmt_atendimentos_anterior->close();

    $atendimentos_variacao = 0;
    if ($total_atendimentos_anterior != 0) {
        $atendimentos_variacao = (($total_atendimentos_atual - $total_atendimentos_anterior) / $total_atendimentos_anterior) * 100;
    } elseif ($total_atendimentos_atual > 0) {
        $atendimentos_variacao = 100;
    }

    $sql_tipo = "
        SELECT tipo_atendimento, COUNT(*) as quantidade
        FROM atendimentos
        WHERE status_atendimento = 'Concluído'
        AND criado_em BETWEEN ? AND NOW()
        GROUP BY tipo_atendimento
    ";
    $stmt_tipo = $conn->prepare($sql_tipo);
    if (!$stmt_tipo) {
        throw new Exception("Erro na preparação da consulta de tipos de atendimento: " . $conn->error);
    }
    $stmt_tipo->bind_param("s", $data_inicio);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $atendimentos_por_tipo = [];
    while ($row = $result_tipo->fetch_assoc()) {
        $atendimentos_por_tipo[] = [
            'tipo' => $row['tipo_atendimento'],
            'quantidade' => (int)$row['quantidade']
        ];
    }
    $stmt_tipo->close();

    $sql_top_pacientes = "
        SELECT p.nome, COUNT(a.id) as atendimentos
        FROM pacientes p
        JOIN atendimentos a ON p.id = a.paciente_id
        WHERE a.status_atendimento = 'Concluído'
        AND a.criado_em BETWEEN ? AND NOW()
        AND p.status = 'ativo'
        GROUP BY p.id, p.nome
        ORDER BY atendimentos DESC
        LIMIT 5
    ";
    $stmt_top_pacientes = $conn->prepare($sql_top_pacientes);
    if (!$stmt_top_pacientes) {
        throw new Exception("Erro na preparação da consulta de top pacientes: " . $conn->error);
    }
    $stmt_top_pacientes->bind_param("s", $data_inicio);
    $stmt_top_pacientes->execute();
    $result_top_pacientes = $stmt_top_pacientes->get_result();
    $top_pacientes = [];
    while ($row = $result_top_pacientes->fetch_assoc()) {
        $top_pacientes[] = [
            'nome' => $row['nome'],
            'atendimentos' => (int)$row['atendimentos']
        ];
    }
    $stmt_top_pacientes->close();

    $sql_recentes = "
        SELECT a.id, a.criado_em, a.tipo_atendimento, a.status_atendimento, p.nome as paciente_nome
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        ORDER BY a.criado_em DESC
        LIMIT 10
    ";
    $result_recentes = $conn->query($sql_recentes);
    if (!$result_recentes) {
        throw new Exception("Erro na consulta de atendimentos recentes: " . $conn->error);
    }
    $atendimentos_recentes = [];
    while ($row = $result_recentes->fetch_assoc()) {
        $atendimentos_recentes[] = $row;
    }

    $logo_principal_url = '/portal-repo-og/assets/logo-header.png';
    $logo_rodape_url = '/portal-repo-og/assets/favicon.png';

    $html = '
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @media print {
                body {
                    font-family: Arial, sans-serif;
                    margin: 1.5cm 1.5cm 2.5cm 1.5cm; /* Ajusta as margens, espaço extra embaixo para o rodapé */
                    background-color: #ffffff;
                    color: #000;
                    font-size: 12px; /* Ajusta tamanho da fonte geral */
                }
                .header {
                    display: flex;
                    justify-content: space-between; 
                    align-items: flex-start; 
                    margin-bottom: 0.5cm;
                    padding-bottom: 0.2cm;
                    border-bottom: 1px solid #1a6d40; /* Linha divisória abaixo do cabeçalho */
                }
                .header img {
                    max-height: 40px; /* Ajuste a altura da logo principal */
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
                    margin: 0.3cm 0 0.2cm 0;
                    text-align: left; /* Alinha título à esquerda */
                }
                .subtitle {
                    color: #6c757d;
                    font-size: 12px;
                    margin: 0.1cm 0;
                    text-align: left; /* Alinha subtítulo à esquerda */
                }
                .section-title {
                    color: #1a6d40;
                    font-size: 14px;
                    font-weight: bold;
                    margin-top: 0.8cm;
                    margin-bottom: 0.3cm;
                    text-align: left;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 0.8cm;
                }
                th, td {
                    border: 1px solid #000; /* Borda preta para as células */
                    padding: 6px 8px; /* Ajusta padding */
                    text-align: left;
                    font-size: 11px;
                }
                th {
                    background-color: #f2f2f2; /* Fundo cinza claro para cabeçalhos */
                    color: #000;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9; /* Fundo ligeiramente cinza para linhas pares */
                }
                /* Estilo específico para a tabela de KPIs */
                table.kpi-table th {
                    width: 70%; /* Ajuste a largura da coluna "Métrica" */
                }
                table.kpi-table td {
                    width: 30%; /* Ajuste a largura da coluna "Valor" */
                }
                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 20px; /* Altura do rodapé */
                    background-color: #f8f9fa;
                    border-top: 1px solid #ddd;
                    text-align: right; /* Alinha o conteúdo do rodapé à direita */
                    padding: 0 1.5cm 0 1.5cm; /* Espaçamento interno */
                    font-size: 9px;
                    color: #6c757d;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .footer img {
                    max-height: 12px; /* Ajuste a altura da logo do rodapé */
                    width: auto;
                    margin-right: 5px;
                }
                .footer-text {
                    flex-grow: 1; /* Faz o texto ocupar o espaço restante */
                    text-align: right; /* Alinha o número da página à direita */
                }
                .page-break {
                    page-break-after: always;
                }
            }
            @page {
                size: A4;
                margin: 1.5cm 1.5cm 2.5cm 1.5cm; /* Margens consistentes com o body */
            }
        </style>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 1000);
            };
        </script>
    </head>
    <body>
        <!-- Cabeçalho -->
        <div class="header">
            <img src="' . $logo_principal_url . '" alt="Logo Principal">
            <div class="header-info">
                <p>Período: Últimos ' . $periodo . ' dias</p>
                <p>Data de Geração: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </div>

        <h1 class="title">Relatório de Insights</h1>

        <h2 class="section-title">KPIs</h2>
        <table class="kpi-table">
            <tr><th>Métrica</th><th>Valor</th></tr>
            <tr><td>Pacientes Ativos</td><td>' . $total_pacientes_ativos . '</td></tr>
            <tr><td>Atendimentos Concluídos</td><td>' . $total_atendimentos_atual . '</td></tr>
            <tr><td>Farmacêuticos Ativos</td><td>' . $total_farmaceuticos_ativos . '</td></tr>
            <tr><td>Medicamentos Ativos</td><td>' . $total_medicamentos_ativos . '</td></tr>
            <tr><td>Variação Atendimentos</td><td>' . number_format($atendimentos_variacao, 2, ',', '.') . '%</td></tr>
        </table>

        <div class="page-break"></div>

        <!-- Seção Atendimentos por Tipo -->
        <h2 class="section-title">Atendimentos por Tipo</h2>
        <table>
            <tr><th>Tipo</th><th>Quantidade</th></tr>';
    foreach ($atendimentos_por_tipo as $item) {
        $html .= '<tr><td>' . htmlspecialchars($item['tipo']) . '</td><td>' . $item['quantidade'] . '</td></tr>';
    }
    $html .= '
        </table>

        <div class="page-break"></div>

        <!-- Seção Top 5 Pacientes -->
        <h2 class="section-title">Top 5 Pacientes</h2>
        <table>
            <tr><th>Paciente</th><th>Atendimentos</th></tr>';
    foreach ($top_pacientes as $item) {
        $html .= '<tr><td>' . htmlspecialchars($item['nome']) . '</td><td>' . $item['atendimentos'] . '</td></tr>';
    }
    $html .= '
        </table>

        <div class="page-break"></div>

        <!-- Seção Atendimentos Recentes -->
        <h2 class="section-title">Atendimentos Recentes</h2>
        <table>
            <tr><th>ID</th><th>Data</th><th>Paciente</th><th>Tipo</th><th>Status</th></tr>';
    foreach ($atendimentos_recentes as $item) {
        $html .= '<tr><td>' . $item['id'] . '</td><td>' . $item['criado_em'] . '</td><td>' . htmlspecialchars($item['paciente_nome']) . '</td><td>' . htmlspecialchars($item['tipo_atendimento']) . '</td><td>' . htmlspecialchars($item['status_atendimento']) . '</td></tr>';
    }
    $html .= '
        </table>

        <!-- Rodapé -->
        <div class="footer">
            <img src="' . $logo_rodape_url . '" alt="Mini Logo">
            <div class="footer-text">
                <span>Vitally - ' . date('Y') . '</span>
                <!-- Número de página: Pode ser complexo com CSS apenas para impressão, mas tentaremos uma abordagem -->
                <script>
                    document.addEventListener("DOMContentLoaded", function() {});
                </script>
            </div>
        </div>
    </body>
    </html>';

    header('Content-Type: text/html; charset=utf-8');
    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao gerar relatório: " . $e->getMessage();
    exit;
}
?>
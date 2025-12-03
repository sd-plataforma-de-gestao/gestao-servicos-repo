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

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_insights.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Relatório de Insights', 'Período: Últimos ' . $periodo . ' dias'], ';');
    fputcsv($output, ['']);

    fputcsv($output, ['KPIs'], ';');
    fputcsv($output, ['Métrica', 'Valor'], ';');
    fputcsv($output, ['Pacientes Ativos', $total_pacientes_ativos], ';');
    fputcsv($output, ['Atendimentos Concluídos', $total_atendimentos_atual], ';');
    fputcsv($output, ['Farmacêuticos Ativos', $total_farmaceuticos_ativos], ';');
    fputcsv($output, ['Medicamentos Ativos', $total_medicamentos_ativos], ';');
    fputcsv($output, ['Variação Atendimentos', number_format($atendimentos_variacao, 2, ',', '.') . '%'], ';');
    fputcsv($output, [''], ';');

    fputcsv($output, ['Atendimentos por Tipo'], ';');
    fputcsv($output, ['Tipo', 'Quantidade'], ';');
    foreach ($atendimentos_por_tipo as $item) {
        fputcsv($output, [$item['tipo'], $item['quantidade']], ';');
    }
    fputcsv($output, [''], ';');

    fputcsv($output, ['Top 5 Pacientes'], ';');
    fputcsv($output, ['Paciente', 'Atendimentos'], ';');
    foreach ($top_pacientes as $item) {
        fputcsv($output, [$item['nome'], $item['atendimentos']], ';');
    }
    fputcsv($output, [''], ';');

    fputcsv($output, ['Atendimentos Recentes'], ';');
    fputcsv($output, ['ID', 'Data', 'Paciente', 'Tipo', 'Status'], ';');
    foreach ($atendimentos_recentes as $item) {
        fputcsv($output, [
            $item['id'],
            $item['criado_em'],
            $item['paciente_nome'],
            $item['tipo_atendimento'],
            $item['status_atendimento']
        ], ';');
    }

    fclose($output);
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao gerar CSV: " . $e->getMessage();
    exit;
}
?>
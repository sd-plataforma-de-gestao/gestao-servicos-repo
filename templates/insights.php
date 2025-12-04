<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');
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

if (isset($_GET['action']) && $_GET['action'] === 'get_tratamentos_continuos') {
    try {
        $sql = "
            SELECT 
                p.nome as paciente_nome, 
                m.nome as medicamento_nome,
                MIN(a.criado_em) as data_inicio_tratamento,
                COUNT(am.id) as total_dispensacoes
            FROM atendimentos a
            JOIN pacientes p ON a.paciente_id = p.id
            JOIN atendimento_medicamentos am ON a.id = am.atendimento_id
            JOIN medicamentos m ON am.medicamento_id = m.id
            WHERE a.status_atendimento = 'Concluído'
            AND p.tipo_paciente = 'cronico' -- Focando em pacientes crônicos
            GROUP BY p.id, m.id
            HAVING total_dispensacoes >= 2 -- Pelo menos 2 dispensações
            ORDER BY total_dispensacoes DESC
            LIMIT 20
        ";
        $result = $conn->query($sql);
        if (!$result) throw new Exception("Erro na consulta de tratamentos contínuos: " . $conn->error);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'paciente_nome' => $row['paciente_nome'],
                'medicamento_nome' => $row['medicamento_nome'],
                'data_inicio_tratamento' => date('d/m/Y', strtotime($row['data_inicio_tratamento'])),
                'total_dispensacoes' => (int)$row['total_dispensacoes']
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_dispensacoes') {
    $filtro_data_inicio = $_GET['data_inicio'] ?? null;
    $filtro_data_fim = $_GET['data_fim'] ?? null;
    $filtro_tipo = $_GET['tipo'] ?? null;
    $filtro_paciente = $_GET['paciente'] ?? null;

    $where = "WHERE a.status_atendimento = 'Concluído'";
    $params = [];
    $types = '';

    if ($filtro_data_inicio) {
        $where .= " AND a.criado_em >= ?";
        $params[] = $filtro_data_inicio . ' 00:00:00';
        $types .= 's';
    }
    if ($filtro_data_fim) {
        $where .= " AND a.criado_em <= ?";
        $params[] = $filtro_data_fim . ' 23:59:59';
        $types .= 's';
    }
    if ($filtro_tipo) {
        $where .= " AND a.tipo_atendimento = ?";
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    if ($filtro_paciente) {
        $where .= " AND p.nome LIKE ?";
        $params[] = "%$filtro_paciente%";
        $types .= 's';
    }

    try {
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
            $where
            ORDER BY a.criado_em DESC
            LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
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
        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_interacoes_paciente') {
    $id_paciente = (int)($_GET['id'] ?? 0);
    if (!$id_paciente) {
        http_response_code(400);
        echo json_encode(['error' => 'ID do paciente inválido.']);
        exit;
    }

    try {
        $sql = "
            SELECT DISTINCT m1.nome as medicamento1, m2.nome as medicamento2
            FROM atendimento_medicamentos am1
            JOIN atendimento_medicamentos am2 ON am1.atendimento_id = am2.atendimento_id
            JOIN medicamentos m1 ON am1.medicamento_id = m1.id
            JOIN medicamentos m2 ON am2.medicamento_id = m2.id
            WHERE am1.atendimento_id IN (
                SELECT id FROM atendimentos WHERE paciente_id = ? AND status_atendimento = 'Concluído'
            )
            AND am1.medicamento_id != am2.medicamento_id
            AND m1.principio_ativo != m2.principio_ativo -- Exclui medicamentos com o mesmo princípio ativo
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_paciente);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) throw new Exception("Erro na consulta de interações: " . $conn->error);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'medicamento1' => $row['medicamento1'],
                'medicamento2' => $row['medicamento2'],
                'descricao' => 'Possível interação entre medicamentos'
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'load_insights') {
    $periodo = $_GET['periodo'] ?? '30';
    $filtro_data_inicio = $_GET['data_inicio'] ?? null;
    $filtro_data_fim = $_GET['data_fim'] ?? null;

    if ($filtro_data_inicio && $filtro_data_fim) {
        $data_inicio = $filtro_data_inicio . ' 00:00:00';
        $data_inicio_anterior = null;
        $data_fim_anterior = null;
    } else {
        $data_inicio = getDataInicio($periodo);
        list($data_inicio_anterior, $data_fim_anterior) = getDataInicioAnterior($periodo);
    }

    try {
        $sql_pacientes_ativos = "SELECT COUNT(*) AS total FROM pacientes WHERE status = 'ativo'";
        $result_pacientes_ativos = $conn->query($sql_pacientes_ativos);
        if (!$result_pacientes_ativos) {
            throw new Exception("Erro na consulta de pacientes: " . $conn->error);
        }
        $total_pacientes_ativos = $result_pacientes_ativos->fetch_assoc()['total'];

        $where_atendimentos = "WHERE status_atendimento = 'Concluído'";
        $params_atual = [];
        $types_atual = '';
        if ($filtro_data_inicio && $filtro_data_fim) {
            $where_atendimentos .= " AND criado_em BETWEEN ? AND ?";
            $params_atual[] = $filtro_data_inicio . ' 00:00:00';
            $params_atual[] = $filtro_data_fim . ' 23:59:59';
            $types_atual = 'ss';
        } else {
            $where_atendimentos .= " AND criado_em BETWEEN ? AND NOW()";
            $params_atual[] = $data_inicio;
            $types_atual = 's';
        }

        $sql_atendimentos_atual = "
            SELECT COUNT(*) AS total_atendimentos
            FROM atendimentos
            $where_atendimentos
        ";
        $stmt_atendimentos_atual = $conn->prepare($sql_atendimentos_atual);
        if (!$stmt_atendimentos_atual) {
            throw new Exception("Erro na preparação da consulta de atendimentos (atual): " . $conn->error);
        }
        if (!empty($params_atual)) {
            $stmt_atendimentos_atual->bind_param($types_atual, ...$params_atual);
        }
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

        $total_atendimentos_anterior = 0;
        if ($data_inicio_anterior && $data_fim_anterior) {
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
        }

        $atendimentos_variacao = 0;
        if ($total_atendimentos_anterior != 0) {
            $atendimentos_variacao = (($total_atendimentos_atual - $total_atendimentos_anterior) / $total_atendimentos_anterior) * 100;
        } elseif ($total_atendimentos_atual > 0) {
            $atendimentos_variacao = 100;
        }

        $data = [
            'total_pacientes_ativos' => (int)$total_pacientes_ativos,
            'total_atendimentos' => (int)$total_atendimentos_atual,
            'total_farmaceuticos_ativos' => (int)$total_farmaceuticos_ativos,
            'total_medicamentos_ativos' => (int)$total_medicamentos_ativos,
            'atendimentos_variacao' => ($atendimentos_variacao >= 0 ? '+' : '') . number_format($atendimentos_variacao, 2, ',', '.') . '%',
        ];

        $where_tipo = "WHERE status_atendimento = 'Concluído'";
        $params_tipo = [];
        $types_tipo = '';
        if ($filtro_data_inicio && $filtro_data_fim) {
            $where_tipo .= " AND criado_em BETWEEN ? AND ?";
            $params_tipo[] = $filtro_data_inicio . ' 00:00:00';
            $params_tipo[] = $filtro_data_fim . ' 23:59:59';
            $types_tipo = 'ss';
        } else {
            $where_tipo .= " AND criado_em BETWEEN ? AND NOW()";
            $params_tipo[] = $data_inicio;
            $types_tipo = 's';
        }

        $sql_tipo = "
            SELECT tipo_atendimento, COUNT(*) as quantidade
            FROM atendimentos
            $where_tipo
            GROUP BY tipo_atendimento
        ";
        $stmt_tipo = $conn->prepare($sql_tipo);
        if (!$stmt_tipo) {
            throw new Exception("Erro na preparação da consulta de tipos de atendimento: " . $conn->error);
        }
        if (!empty($params_tipo)) {
            $stmt_tipo->bind_param($types_tipo, ...$params_tipo);
        }
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
        $data['atendimentos_por_tipo'] = $atendimentos_por_tipo;

        $where_top = "WHERE a.status_atendimento = 'Concluído' AND p.status = 'ativo'";
        $params_top = [];
        $types_top = '';
        if ($filtro_data_inicio && $filtro_data_fim) {
            $where_top .= " AND a.criado_em BETWEEN ? AND ?";
            $params_top[] = $filtro_data_inicio . ' 00:00:00';
            $params_top[] = $filtro_data_fim . ' 23:59:59';
            $types_top = 'ss';
        } else {
            $where_top .= " AND a.criado_em BETWEEN ? AND NOW()";
            $params_top[] = $data_inicio;
            $types_top = 's';
        }

        $sql_top_pacientes = "
            SELECT p.nome, COUNT(a.id) as atendimentos
            FROM pacientes p
            JOIN atendimentos a ON p.id = a.paciente_id
            $where_top
            GROUP BY p.id, p.nome
            ORDER BY atendimentos DESC
            LIMIT 5
        ";
        $stmt_top_pacientes = $conn->prepare($sql_top_pacientes);
        if (!$stmt_top_pacientes) {
            throw new Exception("Erro na preparação da consulta de top pacientes: " . $conn->error);
        }
        if (!empty($params_top)) {
            $stmt_top_pacientes->bind_param($types_top, ...$params_top);
        }
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
        $data['top_pacientes'] = $top_pacientes;

        $sql_periodo = "
            SELECT 
                CONCAT('Sem ', WEEK(criado_em, 1) - WEEK(DATE_SUB(NOW(), INTERVAL 4 WEEK), 1) + 1) as periodo_label,
                COUNT(*) as quantidade
            FROM atendimentos
            WHERE status_atendimento = 'Concluído'
            AND criado_em >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
            GROUP BY YEARWEEK(criado_em, 1)
            ORDER BY criado_em ASC
            LIMIT 4
        ";
        $result_periodo = $conn->query($sql_periodo);
        if (!$result_periodo) {
            throw new Exception("Erro na consulta de atendimentos por período: " . $conn->error);
        }
        $labels_periodo = [];
        $dados_periodo = [];
        while ($row = $result_periodo->fetch_assoc()) {
            $labels_periodo[] = $row['periodo_label'];
            $dados_periodo[] = (int)$row['quantidade'];
        }
        $data['atendimentos_por_periodo'] = [
            'labels' => $labels_periodo,
            'quantidade' => $dados_periodo
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Erro interno no servidor: ' . $e->getMessage()]);
        exit;
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_recent_atendimentos') {
    try {
        $sql_recentes = "
            SELECT a.id, a.criado_em, a.tipo_atendimento, a.status_atendimento, p.nome as paciente_nome
            FROM atendimentos a
            JOIN pacientes p ON a.paciente_id = p.id
            ORDER BY a.criado_em DESC
            LIMIT 10
        ";
        $result_recentes = $conn->query($sql_recentes);
        if (!$result_recentes) throw new Exception("Erro na consulta de atendimentos recentes: " . $conn->error);

        $data = [];
        while ($row = $result_recentes->fetch_assoc()) {
            $data[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Insights - Portal Farmacêutico</title>
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/insights.css">
</head>
<body>
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 mb-0">Carregando dados...</p>
        </div>
    </div>

    <div id="header-container"></div>
    <div id="main-content-wrapper">
        <div id="sidebar-container"></div>
        <div id="main-container">
            <div class="page-header mb-4">
                <h1 class="page-title">Relatórios e Insights</h1>
                <p class="page-subtitle">Acompanhe as principais métricas e indicadores do seu negócio</p>
            </div>

            <div class="filters-section mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <label for="periodo-select" class="form-label">Período</label>
                        <select class="form-select" id="periodo-select">
                            <option value="7">Últimos 7 dias</option>
                            <option value="30" selected>Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                            <option value="365">Último ano</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="data-inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data-inicio">
                    </div>
                    <div class="col-md-3">
                        <label for="data-fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data-fim">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button class="btn btn-primary-custom me-2" id="aplicar-filtros">
                            <i class="fas fa-filter me-1"></i> Aplicar Filtros
                        </button>
                        <button class="btn btn-outline-secondary" id="limpar-filtros">
                            <i class="fas fa-times me-1"></i> Limpar
                        </button>
                        <button class="btn btn-outline-success ms-2" id="exportar-relatorio">
                            <i class="fas fa-download me-1"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <div class="metrics-section mb-4">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-primary text-white me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Pacientes Ativos</h6>
                                        <h4 class="card-title mb-0" id="total-pacientes">0</h4>
                                        <small class="text-muted">Total cadastrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-stethoscope"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Atendimentos Concluídos</h6>
                                        <h4 class="card-title mb-0" id="total-atendimentos">0</h4>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <span id="atendimentos-variacao">+0%</span> vs período anterior
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-info text-white me-3">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Farmacêuticos Ativos</h6>
                                        <h4 class="card-title mb-0" id="total-farmaceuticos">0</h4>
                                        <small class="text-muted">Total cadastrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-warning text-white me-3">
                                        <i class="fas fa-pills"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Medicamentos Ativos</h6>
                                        <h4 class="card-title mb-0" id="total-medicamentos">0</h4>
                                        <small class="text-muted">Com estoque disponível</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="charts-section mb-4">
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Atendimentos por Tipo</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categorias-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Atendimentos por Período</h5>
                                <div class="chart-controls">
                                    <button class="btn btn-sm btn-outline-primary active" data-chart-type="vendas" data-period="semanal">Semanal</button>
                                    <button class="btn btn-sm btn-outline-primary" data-chart-type="vendas" data-period="mensal">Mensal</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="vendas-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
 
            <div class="tables-section">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top 5 Pacientes (Últimos Atendimentos)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="top-pacientes-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Paciente</th>
                                                <th>Nº Atendimentos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Atendimentos Recentes</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary me-2" id="refresh-vendas">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="vendas-recentes-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Data</th>
                                                <th>Paciente</th>
                                                <th>Tipo</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Pacientes em Tratamentos Contínuos (Top 20)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tratamentos-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Paciente</th>
                                                <th>Medicamento</th>
                                                <th>Início do Tratamento</th>
                                                <th>Total de Dispensações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js  "></script>
    <script src="/portal-repo-og/js/insights.js"></script>
    <script src="/portal-repo-og/js/script.js"></script>

    <script>
         function loadTemplate(templatePath, containerId) {
        fetch(templatePath)
            .then(r => r.text())
            .then(html => {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = html;
                    setTimeout(ativarSidebarAtual, 100);
                } else {
                     console.error(`Elemento com ID '${containerId}' não encontrado no DOM.`);
                }
            })
            .catch(err => console.error('Erro ao carregar template:', err));
    }

    function ativarSidebarAtual() {
        const path = window.location.pathname;
        let paginaAtual = null;

        if (path.endsWith('/index.php') || path === '/' || path.includes('/portal-repo-og/') && !path.split('/').pop()) {
            paginaAtual = 'inicio';
        } else if (path.endsWith('/paciente.php')) {
            paginaAtual = 'pacientes';
        } else if (path.endsWith('/farmaceutico.php')) {
            paginaAtual = 'farmaceuticos';
        } else if (path.endsWith('/medicamento.php')) {
            paginaAtual = 'medicamentos';
        } else if (path.endsWith('/insights.php')) {
            paginaAtual = 'relatorios';
        }

        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });

        if (paginaAtual) {
            const linkAtivo = document.querySelector(`.sidebar-link[data-page="${paginaAtual}"]`);
            if (linkAtivo) {
                linkAtivo.classList.add('active');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
        loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');

        setTimeout(ativarSidebarAtual, 200);

        if (typeof loadInsights === 'function') {
            loadInsights();
        } else {
            console.error('Função loadInsights não encontrada no insights.js');
        }

        document.getElementById('aplicar-filtros')?.addEventListener('click', function() {
            if (typeof aplicarFiltros === 'function') {
                aplicarFiltros();
            } else {
                console.error('Função aplicarFiltros não encontrada no insights.js');
            }
        });

        document.getElementById('limpar-filtros')?.addEventListener('click', function() {
            if (typeof limparFiltros === 'function') {
                limparFiltros();
            } else {
                console.error('Função limparFiltros não encontrada no insights.js');
            }
        });

        document.getElementById('exportar-relatorio')?.addEventListener('click', function() {
            const periodo = document.getElementById('periodo-select').value;

            Swal.fire({
                title: 'Exportar Relatório',
                text: 'Selecione o formato para exportação:',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1a6d40',
                cancelButtonColor: '#d33',
                confirmButtonText: 'PDF',
                cancelButtonText: 'CSV',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(`/portal-repo-og/utils/gerar_pdf_insights.php?periodo=${periodo}`, '_blank');
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.open(`/portal-repo-og/utils/gerar_csv_insights.php?periodo=${periodo}`, '_blank');
                }
            });
        });

        document.getElementById('refresh-vendas')?.addEventListener('click', function() {
             if (typeof loadInsights === 'function') {
                loadInsights(document.getElementById('periodo-select').value);
            } else {
                console.error('Função loadInsights não encontrada no insights.js');
            }
        });

        document.getElementById('ver-relatorio-foda')?.addEventListener('click', function() {
            window.open('/portal-repo-og/utils/relatorio_completo.php', '_blank');
        });

        document.getElementById('carregar-tratamentos-btn')?.addEventListener('click', function() {
            carregarTratamentosContinuos();
        });

        document.getElementById('buscar-dispensacoes-btn')?.addEventListener('click', function() {
            const filtros = {
                data_inicio: document.getElementById('data-inicio').value,
                data_fim: document.getElementById('data-fim').value,
                tipo: document.getElementById('tipo-dispensacao').value,
                paciente: document.getElementById('nome-paciente').value
            };
            buscarDispensacoes(filtros);
        });

        document.getElementById('limpar-dispensacoes-btn')?.addEventListener('click', function() {
            document.getElementById('dispensacoes-table').querySelector('tbody').innerHTML = '';
        });

        document.getElementById('buscar-interacoes-btn')?.addEventListener('click', function() {
            const idPaciente = document.getElementById('paciente-interacao-id').value;
            if (idPaciente) {
                buscarInteracoesPorPaciente(parseInt(idPaciente));
            } else {
                mostrarNotificacao('Por favor, insira um ID de paciente válido.', 'error');
            }
        });
    });

    function mostrarNotificacao(mensagem, tipo = 'info') {
        console.log(`${tipo.toUpperCase()}: ${mensagem}`);
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }

    function attachMenuToggle() {
      const btn = document.getElementById('menu-toggle');
      const sidebar = document.getElementById('sidebar');

      if (btn && sidebar) {
        btn.onclick = null;
        btn.onclick = () => {
          sidebar.classList.toggle('collapsed');
        };
      } else {
        setTimeout(attachMenuToggle, 300);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      attachMenuToggle();
    });

    </script>
</body>
</html>

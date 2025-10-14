<?php
include("../config/database.php"); // Inclui database.php primeiro
// Configuração de erro para debug (opcional, pode remover depois)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo'); // Definindo fuso horário

// Função para calcular a data inicial com base no período
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
            $inicio = $hoje->modify('-30 days'); // Padrão
            break;
    }
    return $inicio->format('Y-m-d 00:00:00');
}

// Função para obter dados de período anterior (mesma duração antes do período atual)
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

if (isset($_GET['action']) && $_GET['action'] === 'load_insights') {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = getDataInicio($periodo);
    list($data_inicio_anterior, $data_fim_anterior) = getDataInicioAnterior($periodo);

    try {
        // --- Cálculo dos KPIs Atuais ---
        // Total de Pacientes Ativos
        $sql_pacientes_ativos = "SELECT COUNT(*) AS total FROM pacientes WHERE status = 'ativo'";
        $result_pacientes_ativos = $conn->query($sql_pacientes_ativos);
        if (!$result_pacientes_ativos) {
            throw new Exception("Erro na consulta de pacientes: " . $conn->error);
        }
        $total_pacientes_ativos = $result_pacientes_ativos->fetch_assoc()['total'];

        // Total de Atendimentos Concluídos no Período
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
        $stmt_atendimentos_atual->bind_param("s", $data_inicio); // 's' para string (data)
        $stmt_atendimentos_atual->execute();
        $result_atendimentos_atual = $stmt_atendimentos_atual->get_result();
        $total_atendimentos_atual = $result_atendimentos_atual->fetch_assoc()['total_atendimentos'];
        $stmt_atendimentos_atual->close();

        // Total de Farmacêuticos Ativos
        $sql_farmaceuticos_ativos = "SELECT COUNT(*) AS total FROM farmaceuticos WHERE status = 'ativo'";
        $result_farmaceuticos_ativos = $conn->query($sql_farmaceuticos_ativos);
        if (!$result_farmaceuticos_ativos) {
            throw new Exception("Erro na consulta de farmacêuticos: " . $conn->error);
        }
        $total_farmaceuticos_ativos = $result_farmaceuticos_ativos->fetch_assoc()['total'];

        // Total de Medicamentos Ativos (com estoque > 0)
        $sql_medicamentos_ativos = "SELECT COUNT(*) AS total FROM medicamentos WHERE quantidade > 0";
        $result_medicamentos_ativos = $conn->query($sql_medicamentos_ativos);
        if (!$result_medicamentos_ativos) {
            throw new Exception("Erro na consulta de medicamentos: " . $conn->error);
        }
        $total_medicamentos_ativos = $result_medicamentos_ativos->fetch_assoc()['total'];

        // --- Cálculo dos KPIs do Período Anterior ---
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

        // --- Cálculo das Variações ---
        $atendimentos_variacao = 0;
        if ($total_atendimentos_anterior != 0) {
            $atendimentos_variacao = (($total_atendimentos_atual - $total_atendimentos_anterior) / $total_atendimentos_anterior) * 100;
        } elseif ($total_atendimentos_atual > 0) {
            $atendimentos_variacao = 100; // Aumento de 100% se o anterior era 0 e o atual > 0
        }


        // --- Preparação dos Dados para Retorno ---
        $data = [
            'total_pacientes_ativos' => (int)$total_pacientes_ativos,
            'total_atendimentos' => (int)$total_atendimentos_atual,
            'total_farmaceuticos_ativos' => (int)$total_farmaceuticos_ativos,
            'total_medicamentos_ativos' => (int)$total_medicamentos_ativos,
            'atendimentos_variacao' => ($atendimentos_variacao >= 0 ? '+' : '') . number_format($atendimentos_variacao, 2, ',', '.') . '%',
            // Adicionando dados para gráficos e tabelas
        ];

        // Exemplo de como preencher 'atendimentos_por_tipo' com dados reais
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
        $data['atendimentos_por_tipo'] = $atendimentos_por_tipo;

        // Exemplo de como preencher 'top_pacientes' com dados reais
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
        $data['top_pacientes'] = $top_pacientes;

        // Exemplo de como preencher 'atendimentos_por_periodo' com dados reais (ex: semanal)
        // A lógica de agrupamento por período (diário, semanal, mensal) é mais complexa e depende do banco exato (MariaDB/MySQL)
        // Exemplo para dados semanais (últimas 4 semanas)
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
        $result_periodo = $conn->query($sql_periodo); // Não precisa de parâmetro aqui, data fixa
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
        // Em caso de erro no PHP/SQL, retorne um JSON de erro para o JS
        header('Content-Type: application/json');
        http_response_code(500); // Código de erro HTTP
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
        if (!$result_recentes) {
            throw new Exception("Erro na consulta de atendimentos recentes: " . $conn->error);
        }
        $atendimentos_recentes = [];
        while ($row = $result_recentes->fetch_assoc()) {
            $atendimentos_recentes[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($atendimentos_recentes);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao carregar atendimentos recentes: ' . $e->getMessage()]);
        exit;
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'exportar') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="relatorio_insights_simples.csv"');

    echo "Métrica,Valor\n";
    echo "Pacientes Ativos,0\n";
    echo "Atendimentos Concluídos,0\n";
    echo "Farmacêuticos Ativos,0\n";
    echo "Medicamentos Ativos,0\n";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Insights</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css    " rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css    ">
    <link rel="icon" href="/portal-repo-og/assets/favicon.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js    "></script>
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

            <!-- Seção de Métricas Simplificada -->
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

            <!-- Seção de Gráficos Simplificada -->
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

            <!-- Seção de Tabelas -->
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
                                    <button class="btn btn-sm btn-outline-primary" id="ver-todas-vendas">Ver Todos</button>
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
                                                <th>Ações</th>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js    "></script>
    <script src="/portal-repo-og/js/insights.js"></script> <!-- Seu JS corrigido -->
    <script src="/portal-repo-og/js/script.js"></script>
    <!-- <script src="/portal-repo-og/js/header.js"></script> REMOVIDO -->
    <!-- <script src="/portal-repo-og/js/sidebar.js"></script> REMOVIDO -->

    <script>
         function loadTemplate(templatePath, containerId) {
        fetch(templatePath)
            .then(r => r.text())
            .then(html => {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = html;
                    // Após carregar o sidebar, ative o item correto
                    setTimeout(ativarSidebarAtual, 100);
                } else {
                     console.error(`Elemento com ID '${containerId}' não encontrado no DOM.`);
                }
            })
            .catch(err => console.error('Erro ao carregar template:', err));
    }

    // Função para ativar o item do sidebar com base na página atual
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

        // Remove 'active' de todos
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });

        // Adiciona 'active' ao link correspondente
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

        // Chama a função para ativar o item correto após o sidebar ser carregado
        setTimeout(ativarSidebarAtual, 200);

        // Carrega os insights
        function loadInsights(periodo = '30') {
            document.getElementById('loading-overlay')?.classList.remove('d-none');

            fetch(`?action=load_insights&periodo=${periodo}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro na rede: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Erro retornado pelo PHP:', data.error);
                        mostrarNotificacao(`Erro: ${data.error}`, 'error');
                        return;
                    }

                    // Atualiza os KPIs
                    document.getElementById('total-pacientes').textContent = data.total_pacientes_ativos;
                    document.getElementById('total-atendimentos').textContent = data.total_atendimentos;
                    document.getElementById('total-farmaceuticos').textContent = data.total_farmaceuticos_ativos;
                    document.getElementById('total-medicamentos').textContent = data.total_medicamentos_ativos;
                    document.getElementById('atendimentos-variacao').textContent = data.atendimentos_variacao;

                    // Atualiza as classes de variação
                    const variacaoElement = document.getElementById('atendimentos-variacao');
                    const parent = variacaoElement.closest('small');
                    if (parent) {
                        parent.className = parent.className.replace(/text-(success|danger|secondary)/, '');
                        const valorVariacao = parseFloat(data.atendimentos_variacao);
                        if (valorVariacao > 0) {
                            parent.classList.add('text-success');
                            parent.querySelector('i').className = 'fas fa-arrow-up';
                        } else if (valorVariacao < 0) {
                            parent.classList.add('text-danger');
                            parent.querySelector('i').className = 'fas fa-arrow-down';
                        } else {
                            parent.classList.add('text-secondary');
                            parent.querySelector('i').className = 'fas fa-minus';
                        }
                    }

                    // Chama funções para atualizar gráficos e tabelas
                    if (typeof initCharts === 'function') initCharts(data); // Passa os dados para o JS
                    if (typeof loadTables === 'function') loadTables(data);
                })
                .catch(err => {
                    console.error('Erro ao carregar insights:', err);
                    mostrarNotificacao(`Erro ao carregar insights: ${err.message}`, 'error');
                })
                .finally(() => {
                    document.getElementById('loading-overlay')?.classList.add('d-none');
                });
        }

        loadInsights();

        document.getElementById('aplicar-filtros')?.addEventListener('click', function() {
            const periodo = document.getElementById('periodo-select').value;
            loadInsights(periodo);
        });

        document.getElementById('exportar-relatorio')?.addEventListener('click', function() {
            window.location.href = '?action=exportar';
        });

        document.getElementById('refresh-vendas')?.addEventListener('click', function() {
            loadInsights(document.getElementById('periodo-select').value);
        });
    });

    // Função para mostrar notificações (ex: Bootstrap Alert)
    function mostrarNotificacao(mensagem, tipo = 'info') {
        // Implementação da notificação (ex: Bootstrap Alert ou lib externa)
        console.log(`${tipo.toUpperCase()}: ${mensagem}`);
        // Exemplo com alerta Bootstrap (se estiver disponível)
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

    // Funções para visualizar/editar atendimentos (exemplo)
    function visualizarAtendimento(id) {
        mostrarNotificacao(`Visualizando atendimento #${id}`, 'info');
        // Implementar lógica de visualização
    }

    function editarAtendimento(id) {
        mostrarNotificacao(`Editando atendimento #${id}`, 'info');
        // Implementar lógica de edição
    }

    </script>
</body>
</html>
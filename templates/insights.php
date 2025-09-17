<?php include("../config/database.php"); ?>

<?php
// 👇 ENDPOINT PARA CARREGAR DADOS VIA AJAX (ex: métricas, gráficos, tabelas)
if (isset($_GET['action']) && $_GET['action'] === 'load_insights') {
    $periodo = $_GET['periodo'] ?? '30'; // padrão: últimos 30 dias

    // Simulação de dados — substitua pelas suas queries reais
    $receita_total = number_format(rand(15000, 80000), 2, ',', '.');
    $total_vendas = rand(80, 400);
    $novos_clientes = rand(15, 60);
    $taxa_conversao = rand(65, 95);

    $data = [
        'receita_total' => "R$ {$receita_total}",
        'total_vendas' => $total_vendas,
        'novos_clientes' => $novos_clientes,
        'taxa_conversao' => "{$taxa_conversao}%",
        'receita_variacao' => '+' . rand(2, 15) . '%',
        'vendas_variacao' => '+' . rand(1, 12) . '%',
        'clientes_variacao' => (rand(0,1) ? '+' : '-') . rand(1, 8) . '%',
        'conversao_variacao' => '+' . rand(1, 7) . '%',
    ];

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 👇 ENDPOINT PARA EXPORTAR RELATÓRIO (simulado)
if (isset($_GET['action']) && $_GET['action'] === 'exportar') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="relatorio_insights.csv"');

    echo "Métrica,Valor\n";
    echo "Receita Total,R$ 45.280,00\n";
    echo "Atendimentos,237\n";
    echo "Novos Pacientes,42\n";
    echo "Taxa de Adesão,83%\n";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Insights</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/assets/favicon.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/styles/global.css">
    <link rel="stylesheet" href="/styles/header.css">
    <link rel="stylesheet" href="/styles/sidebar.css">
    <link rel="stylesheet" href="/styles/insights.css">
</head>
<body>
    <!-- Overlay de carregamento -->
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 mb-0">Carregando dados...</p>
        </div>
    </div>

    <!-- Cabeçalho -->
    <div id="header-container"></div>

    <!-- Container principal -->
    <div id="main-content-wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <!-- Conteúdo -->
        <div id="main-container">
            <div class="page-header mb-4">
                <h1 class="page-title">Relatórios e Insights</h1>
                <p class="page-subtitle">Acompanhe as principais métricas e indicadores do seu negócio</p>
            </div>

            <!-- Filtros -->
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

            <!-- Métricas -->
            <div class="metrics-section mb-4">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-primary text-white me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Receita Total</h6>
                                        <h4 class="card-title mb-0" id="receita-total">R$ 0,00</h4>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <span id="receita-variacao">+0%</span> vs período anterior
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
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Atendimentos</h6>
                                        <h4 class="card-title mb-0" id="total-vendas">0</h4>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <span id="vendas-variacao">+0%</span> vs período anterior
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
                                    <div class="icon-circle bg-warning text-white me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Novos Pacientes</h6>
                                        <h4 class="card-title mb-0" id="novos-clientes">0</h4>
                                        <small class="text-warning">
                                            <i class="fas fa-arrow-down"></i>
                                            <span id="clientes-variacao">-0%</span> vs período anterior
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
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Taxa de Adesão</h6>
                                        <h4 class="card-title mb-0" id="taxa-conversao">0%</h4>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <span id="conversao-variacao">+0%</span> vs período anterior
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-section mb-4">
                <div class="row">
                    <!-- Gráfico de Vendas por Período -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Atendimentos por Período</h5>
                                <div class="chart-controls">
                                    <button class="btn btn-sm btn-outline-primary active" data-chart-type="vendas" data-period="diario">Diário</button>
                                    <button class="btn btn-sm btn-outline-primary" data-chart-type="vendas" data-period="semanal">Semanal</button>
                                    <button class="btn btn-sm btn-outline-primary" data-chart-type="vendas" data-period="mensal">Mensal</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="vendas-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Pizza -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Atendimentos por Tipo</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categorias-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Gráfico de Funil -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Funil de Pacientes</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="funil-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Receita vs Custos -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Receita vs Custos</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="receita-custos-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabelas -->
            <div class="tables-section">
                <div class="row">
                    <!-- Top Medicamentos -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Top 10 Medicamentos</h5>
                                <button class="btn btn-sm btn-outline-primary" id="ver-todos-produtos">Ver Todos</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="top-produtos-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Medicamento</th>
                                                <th>Dispensações</th>
                                                <th>Receita</th>
                                                <th>Variação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dados serão inseridos via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Pacientes -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Top 10 Pacientes</h5>
                                <button class="btn btn-sm btn-outline-primary" id="ver-todos-clientes">Ver Todos</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="top-clientes-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Paciente</th>
                                                <th>Atendimentos</th>
                                                <th>Total Gasto</th>
                                                <th>Último Atendimento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dados serão inseridos via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Atendimentos Recentes -->
                <div class="row">
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
                                                <th>Medicamento</th>
                                                <th>Tipo</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dados serão inseridos via JS -->
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/insights.js"></script>
    <script src="/js/script.js"></script>

    <!-- Carrega Header e Sidebar via JS -->
    <script>
        function loadTemplate(templatePath, containerId) {
            fetch(templatePath)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById(containerId);
                    if (container) container.innerHTML = html;
                })
                .catch(err => console.error('Erro ao carregar template:', err));
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Carrega header e sidebar
            loadTemplate('/templates/header.php', 'header-container');
            loadTemplate('/templates/sidebar.php', 'sidebar-container');

            // Inicializa funções globais
            if (typeof initializeSidebar === 'function') initializeSidebar();
            if (typeof initializeActionButtons === 'function') initializeActionButtons();
            if (typeof initializeTooltips === 'function') initializeTooltips();
            if (typeof initializeNavigation === 'function') initializeNavigation();
            if (typeof setActiveSidebarLink === 'function') setActiveSidebarLink();

            // Função para carregar métricas
            function loadInsights(periodo = '30') {
                document.getElementById('loading-overlay')?.classList.remove('d-none');

                fetch(`?action=load_insights&periodo=${periodo}`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('receita-total').textContent = data.receita_total;
                        document.getElementById('total-vendas').textContent = data.total_vendas;
                        document.getElementById('novos-clientes').textContent = data.novos_clientes;
                        document.getElementById('taxa-conversao').textContent = data.taxa_conversao;
                        document.getElementById('receita-variacao').textContent = data.receita_variacao;
                        document.getElementById('vendas-variacao').textContent = data.vendas_variacao;
                        document.getElementById('clientes-variacao').textContent = data.clientes_variacao;
                        document.getElementById('conversao-variacao').textContent = data.conversao_variacao;

                        // Aqui você pode carregar os gráficos também (usando Chart.js)
                        if (typeof initCharts === 'function') initCharts(periodo);
                        if (typeof loadTables === 'function') loadTables(periodo);
                    })
                    .catch(err => console.error('Erro ao carregar insights:', err))
                    .finally(() => {
                        document.getElementById('loading-overlay')?.classList.add('d-none');
                    });
            }

            // Carrega dados iniciais
            loadInsights();

            // Aplicar filtros
            document.getElementById('aplicar-filtros')?.addEventListener('click', function() {
                const periodo = document.getElementById('periodo-select').value;
                loadInsights(periodo);
            });

            // Exportar relatório
            document.getElementById('exportar-relatorio')?.addEventListener('click', function() {
                window.location.href = '?action=exportar';
            });

            // Refresh
            document.getElementById('refresh-vendas')?.addEventListener('click', function() {
                loadInsights(document.getElementById('periodo-select').value);
            });
        });
    </script>
</body>
</html>
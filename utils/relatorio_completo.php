<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');
date_default_timezone_set('America/Sao_Paulo');

// Tipos de relatório válidos (sem estoque!)
$tipos_relatorio = [
    'evolucao_atendimentos' => 'Evolução de Atendimentos',
    'perfil_pacientes'      => 'Perfil dos Pacientes',
    'produtividade_farma'   => 'Produtividade por Farmacêutico',
    'desempenho_unidades'   => 'Desempenho por Unidade',
    'status_atendimentos'   => 'Status dos Atendimentos'
];

// Filtros
$periodo = $_GET['periodo'] ?? '30';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$limite = (int)($_GET['limite'] ?? 10);
$tipo_relatorio = $_GET['tipo_relatorio'] ?? 'evolucao_atendimentos';

// Converter período em datas
if ($periodo !== 'custom') {
    $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));
    $data_fim = date('Y-m-d');
} else {
    // Formato brasileiro → SQL
    $data_inicio = !empty($_GET['data_inicio']) ? DateTime::createFromFormat('d/m/Y', $_GET['data_inicio'])?->format('Y-m-d') : '';
    $data_fim = !empty($_GET['data_fim']) ? DateTime::createFromFormat('d/m/Y', $_GET['data_fim'])?->format('Y-m-d') : date('Y-m-d');
}

// Funções de consulta — SEM medicamentos
function buscarEvolucaoAtendimentos($conn, $data_inicio, $data_fim) {
    $sql = "
        SELECT DATE(a.criado_em) as data, COUNT(*) as total
        FROM atendimentos a
        WHERE a.criado_em BETWEEN ? AND ?
        GROUP BY DATE(a.criado_em)
        ORDER BY data ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
    return $dados;
}

function buscarPerfilPacientes($conn, $data_inicio, $data_fim) {
    $sql = "
        SELECT 
            p.tipo_paciente,
            COUNT(*) as total,
            AVG(p.taxa_adesao) as media_adesao
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        WHERE a.criado_em BETWEEN ? AND ?
        GROUP BY p.tipo_paciente
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
    return $dados;
}

function buscarProdutividadeFarmaceuticos($conn, $data_inicio, $data_fim, $limite = 10) {
    $sql = "
        SELECT 
            f.nome,
            f.crf,
            COUNT(a.id) as total_atendimentos,
            SUM(CASE WHEN a.tipo_atendimento = 'Primeira Consulta' THEN 1 ELSE 0 END) as primeiras,
            SUM(CASE WHEN a.status_atendimento = 'Cancelado' THEN 1 ELSE 0 END) as cancelados
        FROM farmaceuticos f
        LEFT JOIN atendimentos a ON f.id = a.farmaceutico_id 
            AND a.criado_em BETWEEN ? AND ?
        WHERE f.status = 'ativo'
        GROUP BY f.id
        ORDER BY total_atendimentos DESC
        LIMIT ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $data_inicio, $data_fim, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
    return $dados;
}

function buscarDesempenhoUnidades($conn, $data_inicio, $data_fim, $limite = 10) {
    $sql = "
        SELECT 
            u.nome as unidade,
            u.status as status_unidade,
            f.nome as farmaceutico,
            COUNT(a.id) as total_atendimentos
        FROM unidades u
        LEFT JOIN farmaceuticos f ON u.crf_responsavel = f.crf
        LEFT JOIN atendimentos a ON f.id = a.farmaceutico_id 
            AND a.criado_em BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_atendimentos DESC
        LIMIT ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $data_inicio, $data_fim, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
    return $dados;
}

function buscarStatusAtendimentos($conn, $data_inicio, $data_fim) {
    $sql = "
        SELECT 
            status_atendimento,
            COUNT(*) as total
        FROM atendimentos
        WHERE criado_em BETWEEN ? AND ?
        GROUP BY status_atendimento
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
    return $dados;
}

// Gerar relatório selecionado
$dados_relatorio = [];
switch ($tipo_relatorio) {
    case 'evolucao_atendimentos':
        $dados_relatorio = buscarEvolucaoAtendimentos($conn, $data_inicio, $data_fim);
        break;
    case 'perfil_pacientes':
        $dados_relatorio = buscarPerfilPacientes($conn, $data_inicio, $data_fim);
        break;
    case 'produtividade_farma':
        $dados_relatorio = buscarProdutividadeFarmaceuticos($conn, $data_inicio, $data_fim, $limite);
        break;
    case 'desempenho_unidades':
        $dados_relatorio = buscarDesempenhoUnidades($conn, $data_inicio, $data_fim, $limite);
        break;
    case 'status_atendimentos':
        $dados_relatorio = buscarStatusAtendimentos($conn, $data_inicio, $data_fim);
        break;
}

$data_geracao = date('d/m/Y H:i:s');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Relatórios - Vitally</title>
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --vitally-green-dark: #285e47;
            --vitally-green-light: #3b9e73;
            --vitally-green-bg: #f5faf8;
            --vitally-white: #ffffff;
            --vitally-gray: #e9ecef;
            --vitally-text: #495057;
        }

        body {
            background-color: var(--vitally-green-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--vitally-text);
        }

        .header-bar {
            background: white;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-row {
            background: white;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .report-card, .events-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card-stat {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: bold;
            color: var(--vitally-green-dark);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .btn-vitally {
            background-color: var(--vitally-green-dark);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-vitally:hover {
            background-color: #214c3a;
        }

        .btn-outline-vitally {
            border: 1px solid var(--vitally-green-dark);
            color: var(--vitally-green-dark);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-outline-vitally:hover {
            background-color: var(--vitally-green-dark);
            color: white;
        }

        .badge-vitally {
            background-color: var(--vitally-green-light);
            color: white;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .no-data {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: var(--vitally-green-dark);
        }

        .table td, .table th {
            vertical-align: middle;
        }

        .icon-box {
            background: #f1f8f5;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--vitally-green-dark);
            font-size: 1.25rem;
            margin-left: 0.5rem;
        }

        .activity-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .activity-status-concluido {
            background-color: #d1f0e0;
            color: #1e7d52;
        }

        .activity-status-agendado {
            background-color: #fff3cd;
            color: #856404;
        }

        .quick-action {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-action:hover {
            background-color: #f1f8f5;
            border-color: var(--vitally-green-dark);
        }

        .quick-action i {
            font-size: 1.5rem;
            color: var(--vitally-green-dark);
            margin-bottom: 0.5rem;
        }

        .quick-action-title {
            font-weight: 500;
            color: var(--vitally-green-dark);
        }

        .quick-action-desc {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .form-select, .form-control {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--vitally-green-dark);
            box-shadow: 0 0 0 0.2rem rgba(40, 94, 71, 0.25);
        }

        .logo-header {
            height: 30px;
            margin-right: 0.5rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .user-menu i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bar">
        
        <div>
            <img src="/portal-repo-og/assets/logo-header.png" alt="Logo Vitally" class="logo-header">
            <strong>Painel de Relatórios</strong>
        </div>
        <div class="user-menu">
            <span>Dr(a). Vitor</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>

    <!-- Filtros -->
    <div class="container-fluid mt-3">
        <div class="filter-row">
            <form method="GET">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="form-label">Relatório:</label>
                        <select name="tipo_relatorio" class="form-select">
                            <?php foreach ($tipos_relatorio as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $key === $tipo_relatorio ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Período:</label>
                        <select name="periodo" class="form-select" onchange="this.form.submit()">
                            <option value="7" <?= $periodo == '7' ? 'selected' : '' ?>>7 dias</option>
                            <option value="30" <?= $periodo == '30' ? 'selected' : '' ?>>30 dias</option>
                            <option value="90" <?= $periodo == '90' ? 'selected' : '' ?>>90 dias</option>
                            <option value="custom" <?= $periodo == 'custom' ? 'selected' : '' ?>>Personalizado</option>
                        </select>
                    </div>
                    <?php if ($periodo === 'custom'): ?>
                        <div class="col-md-2">
                            <label class="form-label">Início:</label>
                            <input type="text" name="data_inicio" class="form-control" placeholder="dd/mm/aaaa"
                                   value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fim:</label>
                            <input type="text" name="data_fim" class="form-control" placeholder="dd/mm/aaaa"
                                   value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($tipo_relatorio, ['produtividade_farma', 'desempenho_unidades'])): ?>
                        <div class="col-md-2">
                            <label class="form-label">Limite:</label>
                            <input type="number" name="limite" class="form-control" value="<?= $limite ?>" min="1" max="50">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-vitally w-100">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data de geração -->
        <div class="text-muted text-end mb-3">Gerado em: <?= htmlspecialchars($data_geracao) ?></div>

        <!-- Relatório -->
        <div class="row">
            <div class="col-12">
                <div class="report-card">
                    <h5 class="mb-3" style="color: var(--vitally-green-dark);">
                        <?= htmlspecialchars($tipos_relatorio[$tipo_relatorio] ?? 'Relatório') ?>
                    </h5>

                    <?php if ($tipo_relatorio === 'evolucao_atendimentos'): ?>
                        <?php if (!empty($dados_relatorio)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Atendimentos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dados_relatorio as $row): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                                                <td><span class="stat-number"><?= (int)$row['total'] ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-line fa-2x mb-2"></i><br>
                                Nenhum atendimento no período.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($tipo_relatorio === 'perfil_pacientes'): ?>
                        <?php if (!empty($dados_relatorio)): ?>
                            <div class="row g-3">
                                <?php foreach ($dados_relatorio as $row): ?>
                                    <div class="col-md-6">
                                        <div class="card-stat">
                                            <div class="stat-number">
                                                <?= (int)$row['total'] ?>
                                            </div>
                                            <div class="stat-label">
                                                <?= $row['tipo_paciente'] === 'cronico' ? 'Pacientes Crônicos' : 'Pacientes Agudos' ?>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Adesão média: <?= number_format($row['media_adesao'], 2, ',', '.') ?>%
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                Nenhum paciente atendido no período.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($tipo_relatorio === 'produtividade_farma'): ?>
                        <?php if (!empty($dados_relatorio)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Farmacêutico (CRF)</th>
                                            <th>Atendimentos</th>
                                            <th>Primeiras</th>
                                            <th>Cancelados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dados_relatorio as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nome']) . " (" . htmlspecialchars($row['crf']) . ")" ?></td>
                                                <td><span class="stat-number"><?= (int)$row['total_atendimentos'] ?></span></td>
                                                <td><?= (int)$row['primeiras'] ?></td>
                                                <td><?= (int)$row['cancelados'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-stethoscope fa-2x mb-2"></i><br>
                                Nenhum farmacêutico com atendimentos no período.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($tipo_relatorio === 'desempenho_unidades'): ?>
                        <?php if (!empty($dados_relatorio)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Unidade</th>
                                            <th>Farmacêutico</th>
                                            <th>Atendimentos</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dados_relatorio as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['unidade']) ?></td>
                                                <td><?= htmlspecialchars($row['farmaceutico'] ?? '—') ?></td>
                                                <td><span class="stat-number"><?= (int)$row['total_atendimentos'] ?></span></td>
                                                <td>
                                                    <span class="badge-vitally">
                                                        <?= htmlspecialchars($row['status_unidade']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-hospital fa-2x mb-2"></i><br>
                                Nenhuma unidade com dados no período.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($tipo_relatorio === 'status_atendimentos'): ?>
                        <?php if (!empty($dados_relatorio)): ?>
                            <div class="row g-3">
                                <?php foreach ($dados_relatorio as $row): ?>
                                    <div class="col-md-3">
                                        <div class="card-stat">
                                            <div class="stat-number">
                                                <?= (int)$row['total'] ?>
                                            </div>
                                            <div class="stat-label">
                                                <?= htmlspecialchars($row['status_atendimento']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>
                                Nenhum atendimento no período.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-question-circle fa-2x mb-2"></i><br>
                            Relatório não disponível.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Para personalização futura (ex: máscara de data)
        document.addEventListener('DOMContentLoaded', () => {
            // Pode adicionar máscara de data aqui com um plugin leve, se quiser
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');





function limparCPF($cpf)
{
    return preg_replace('/[^0-9]/', '', $cpf);
}
function formatarCPF($cpf)
{
    $cpf = limparCPF($cpf);
    if (strlen($cpf) != 11) return $cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}



if (!isset($_GET['paciente_id']) || !is_numeric($_GET['paciente_id'])) {
    die("ID de paciente inválido.");
}

$paciente_id = (int)$_GET['paciente_id'];

$stmtPaciente = $conn->prepare("SELECT id, nome, cpf FROM pacientes WHERE id = ?");
$stmtPaciente->bind_param("i", $paciente_id);
$stmtPaciente->execute();
$resultPaciente = $stmtPaciente->get_result();

if ($resultPaciente->num_rows !== 1) {
    die("Paciente não encontrado.");
}

$paciente = $resultPaciente->fetch_assoc();

$stmtAtendimentos = $conn->prepare("
    SELECT 
        a.id,
        a.criado_em,
        a.tipo_atendimento,
        a.status_atendimento,
        f.nome AS farmaceutico_nome
    FROM atendimentos a
    JOIN farmaceuticos f ON a.farmaceutico_id = f.id
    WHERE a.paciente_id = ? AND a.status_atendimento = 'Concluído'
    ORDER BY a.criado_em DESC
");
$stmtAtendimentos->bind_param("i", $paciente_id);
$stmtAtendimentos->execute();
$resultAtendimentos = $stmtAtendimentos->get_result();

$atendimentos = [];
while ($row = $resultAtendimentos->fetch_assoc()) {
    $atendimentos[] = $row;
}

$contagemFarmaceuticos = [];
foreach ($atendimentos as $a) {
    $nome = $a['farmaceutico_nome'];
    $contagemFarmaceuticos[$nome] = ($contagemFarmaceuticos[$nome] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detalhes do Paciente - <?= htmlspecialchars($paciente['nome'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/global.css">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/responsive.css">
    <link rel="stylesheet" href="../styles/atendimento.css">
</head>

<body>
    <div id="header-container"></div>
    <div id="main-content-wrapper">
        <div id="sidebar-container"></div>
        <div id="main-container">
            <div class="page-header">
                <h2 class="page-title">Detalhes do Paciente</h2>
                <p class="page-subtitle">Paciente: <?= htmlspecialchars($paciente['nome'], ENT_QUOTES, 'UTF-8') ?> (CPF: <?= $paciente['cpf'] ? formatarCPF($paciente['cpf']) : '—' ?>)</p>
            </div>

            <?php if (!empty($contagemFarmaceuticos)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Resumo por Farmacêutico</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($contagemFarmaceuticos as $farmaceutico => $total): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($farmaceutico, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-primary"><?= $total ?> atendimento<?= $total === 1 ? '' : 's' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Histórico de Atendimentos</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Farmacêutico</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($atendimentos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum atendimento registrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($atendimentos as $a): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($a['tipo_atendimento']) ?></span></td>
                                        <td><?= htmlspecialchars($a['farmaceutico_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-success"><?= ucfirst($a['status_atendimento']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal-repo-og/js/script.js"></script>
    <script>
        async function loadTemplate(templatePath, containerId) {
            try {
                const response = await fetch(templatePath);
                if (!response.ok) throw new Error(`Erro ${response.status}`);
                const html = await response.text();
                document.getElementById(containerId).innerHTML = html;
            } catch (error) {
                console.error(`Erro ao carregar ${templatePath}:`, error);
            }
        }

        document.addEventListener('DOMContentLoaded', async function() {
            await loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
            await loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
            if (typeof initializeSidebar === 'function') initializeSidebar();
        });
    </script>
</body>

</html>
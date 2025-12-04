<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

if (isset($_GET['action']) && $_GET['action'] === 'listar_atendimentos') {
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'todos';

    $where = "a.status_atendimento = 'Concluído'";
    $params = [];

    if ($search !== '') {
        $like = '%' . trim($search) . '%';
        $where .= " AND (p.nome LIKE ? OR p.cpf LIKE ?)";
        $params[] = $like;
        $params[] = $like;
    }

    if ($filter === 'agudo') {
        $where .= " AND a.tipo_atendimento = 'Agudo'";
    } elseif ($filter === 'cronico') {
        $where .= " AND a.tipo_atendimento = 'Crônico'";
    }

    $sql = "
    SELECT 
        p.id AS paciente_id,
        p.nome AS paciente_nome,
        p.cpf,
        COUNT(a.id) AS total_atendimentos,
        MAX(a.criado_em) AS ultimo_atendimento_data,    
        (SELECT f2.nome 
         FROM atendimentos a2 
         JOIN farmaceuticos f2 ON a2.farmaceutico_id = f2.id 
         WHERE a2.paciente_id = p.id AND a2.status_atendimento = 'Concluído' 
         ORDER BY a2.criado_em DESC 
         LIMIT 1) AS farmaceutico_nome
    FROM atendimentos a
    JOIN pacientes p ON a.paciente_id = p.id
    WHERE a.status_atendimento = 'Concluído'
    GROUP BY p.id, p.nome, p.cpf
    ORDER BY ultimo_atendimento_data DESC
    LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $pacientes = [];
    while ($row = $result->fetch_assoc()) {
        $pacientes[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($pacientes);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Histórico de Atendimentos - Vitally</title>
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css  " rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css  " />
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
                <h2 class="page-title">Histórico de Atendimentos</h2>
                <p class="page-subtitle">Resumo por paciente com total de atendimentos.</p>
            </div>

            <div class="card mb-4 p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="busca-atendimento" placeholder="Buscar por nome ou CPF...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-filter"></i></span>
                            <select class="form-select" id="filtro-tipo">
                                <option value="todos">Todos os atendimentos</option>
                                <option value="agudo">Atendimentos Agudos</option>
                                <option value="cronico">Atendimentos Crônicos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100" onclick="window.location.href='atendimento.php'">
                            <i class="fa fa-plus"></i> Novo Atendimento
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Resumo por Paciente</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Último Atendimento</th>
                                <th>Nº Atendimentos</th>
                                <th>Farmacêutico</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-atendimentos">
                            <tr>
                                <td colspan="5" class="text-center">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js  "></script>
    <script src="/portal-repo-og/js/script.js"></script>
    <script>
        async function loadTemplate(templatePath, containerId) {
            try {
                const response = await fetch(templatePath);
                if (!response.ok) throw new Error(`Erro ${response.status}`);
                const html = await response.text();
                document.getElementById(containerId).innerHTML = html;
                
                if (containerId === 'sidebar-container' && typeof setActiveSidebarLink === 'function') {
                    setTimeout(() => setActiveSidebarLink(), 50);
                }
            } catch (error) {
                console.error(`Erro ao carregar ${templatePath}:`, error);
            }
        }

        document.addEventListener('DOMContentLoaded', async function() {
            await loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
            await loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
            if (typeof initializeSidebar === 'function') initializeSidebar();
            carregarAtendimentos();
        });

        async function carregarAtendimentos() {
            const busca = document.getElementById('busca-atendimento').value.trim();
            const filtro = document.getElementById('filtro-tipo').value;

            try {
                const url = `historico_atendimento.php?action=listar_atendimentos&search=${encodeURIComponent(busca)}&filter=${filtro}`;
                const res = await fetch(url);
                const pacientes = await res.json();

                const tbody = document.getElementById('lista-atendimentos');
                if (pacientes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum paciente encontrado.</td></tr>';
                    return;
                }

                tbody.innerHTML = pacientes.map(p => `
                    <tr>
                        <td>
                            <strong>${p.paciente_nome}</strong><br>
                            <small>CPF: ${p.cpf ? p.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '—'}</small>
                        </td>
                        <td>${new Date(p.ultimo_atendimento_data).toLocaleString('pt-BR')}</td>
                        <td>
                            <span class="badge bg-primary">
                                ${p.total_atendimentos} ${p.total_atendimentos === 1 ? 'atendimento' : 'atendimentos'}
                            </span>
                        </td>
                        <td>${p.farmaceutico_nome}</td>
                        <td>
                            <button class="badge" onclick="verDetalhes(${p.paciente_id})">
    <i class="fa fa-eye"></i> Ver
</button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                console.error('Erro ao carregar atendimentos:', err);
                document.getElementById('lista-atendimentos').innerHTML = '<tr><td colspan="5" class="text-danger text-center">Erro ao carregar dados.</td></tr>';
            }
        }

        function verDetalhes(pacienteId) {
            window.location.href = `detalhes_paciente.php?paciente_id=${pacienteId}`;
        }

        document.getElementById('busca-atendimento').addEventListener('input', carregarAtendimentos);
        document.getElementById('filtro-tipo').addEventListener('change', carregarAtendimentos);

        function attachMenuToggle() {
            const btn = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar-container');
            if (btn && sidebar) {
                btn.onclick = () => sidebar.classList.toggle('collapsed');
            } else {
                setTimeout(attachMenuToggle, 300);
            }
        }
        attachMenuToggle();
    </script>
</body>

</html>
<?php
include("../config/database.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
// Endpoint para listar atendimentos
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
            a.id,
            a.criado_em,
            a.tipo_atendimento,
            p.nome AS paciente_nome,
            p.cpf,
            f.nome AS farmaceutico_nome,
            (SELECT COUNT(*) FROM atendimentos a2 WHERE a2.paciente_id = a.paciente_id AND a2.status_atendimento = 'Concluído') AS total_atendimentos
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        JOIN farmaceuticos f ON a.farmaceutico_id = f.id
        WHERE $where
        ORDER BY a.criado_em DESC
        LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $atendimentos = [];
    while ($row = $result->fetch_assoc()) {
        $atendimentos[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($atendimentos);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/global.css">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/responsive.css">
    <link rel="stylesheet" href="../styles/atendimento.css"> <!-- opcional, se quiser estilos específicos -->
</head>

<body>
    <div id="header-container"></div>
    <div id="main-content-wrapper">
        <div id="sidebar-container"></div>
        <div id="main-container">
            <div class="page-header">
                <h2 class="page-title">Histórico de Atendimentos</h2>
                <p class="page-subtitle">Todos os atendimentos concluídos.</p>
            </div>

            <!-- Filtros e Busca -->
            <div class="card mb-4 p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="busca-atendimento" placeholder="Buscar por nome, CPF ou tipo...">
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

            <!-- Lista de Atendimentos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Atendimentos Concluídos</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Data</th>
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
            carregarAtendimentos();
        });

        async function carregarAtendimentos() {
            const busca = document.getElementById('busca-atendimento').value.trim();
            const filtro = document.getElementById('filtro-tipo').value;

            try {
                const url = `historico_atendimento.php?action=listar_atendimentos&search=${encodeURIComponent(busca)}&filter=${filtro}`;
                const res = await fetch(url);
                const atendimentos = await res.json();

                const tbody = document.getElementById('lista-atendimentos');
                if (atendimentos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum atendimento encontrado.</td></tr>';
                    return;
                }

                tbody.innerHTML = atendimentos.map(a => `
          <tr>
            <td>
              <strong>${a.paciente_nome}</strong><br>
              <small>CPF: ${a.cpf ? a.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '—'}</small>
            </td>
            <td>${new Date(a.criado_em).toLocaleString()}</td>
            <td><span class="badge bg-primary">${a.total_atendimentos} ${a.total_atendimentos == 1 ? 'atendimento' : 'atendimentos'}</span></td>
            <td>${a.farmaceutico_nome}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="verDetalhes(${a.id})">
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

        // Função para ver detalhes (opcional - pode abrir modal ou redirecionar)
        function verDetalhes(id) {
            alert(`Você clicou em ver detalhes do atendimento #${id}. Aqui você pode implementar a visualização completa.`);
            // Exemplo: window.location.href = 'detalhes_atendimento.php?id=' + id;
        }

        // Atualizar ao digitar ou mudar filtro
        document.getElementById('busca-atendimento').addEventListener('input', carregarAtendimentos);
        document.getElementById('filtro-tipo').addEventListener('change', carregarAtendimentos);
    </script>
</body>

</html>
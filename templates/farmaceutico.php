<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $sql = "SELECT id, nome, crf, email, telefone, status, criado_em FROM farmaceuticos ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>CRF</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Criado em</th></tr></thead><tbody>';
        while ($row = $result->fetch_assoc()):
            $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['crf']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
            echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($row['criado_em'])) . '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum farmacêutico cadastrado.</p>';
    endif;
    exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $crf = trim($_POST['crf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $status = $_POST['status'] ?? 'ativo';

    if (empty($nome) || empty($crf) || empty($email)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: Campos obrigatórios (nome, CRF, e-mail) não preenchidos.";
        } else {
            $_SESSION['error'] = "Campos obrigatórios não preenchidos.";
            header("Location: farmaceutico.php");
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: E-mail inválido.";
        } else {
            $_SESSION['error'] = "E-mail inválido.";
            header("Location: farmaceutico.php");
        }
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO farmaceuticos (nome, crf, email, telefone, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: falha ao preparar statement - " . $conn->error;
        } else {
            $_SESSION['error'] = "Erro interno ao preparar cadastro.";
            header("Location: farmaceutico.php");
        }
        exit;
    }
    $stmt->bind_param("sssss", $nome, $crf, $email, $telefone, $status);

    if ($stmt->execute()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "success";
        } else {
            $_SESSION['success'] = "Farmacêutico cadastrado com sucesso!";
            header("Location: farmaceutico.php");
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: " . $stmt->error;
        } else {
            $_SESSION['error'] = "Erro ao salvar: " . $stmt->error;
            header("Location: farmaceutico.php");
        }
    }

    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farmacêuticos</title>
  <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/paciente.css">
</head>
<body>
  <div id="header-container"></div>
  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>
    <div id="main-container">
      <div class="page-header">
        <h2 class="page-title">Farmacêuticos</h2>
        <p class="page-subtitle">Gestão e controle de acessos de Farmacêuticos</p>
      </div>
      <div class="farmaceutico-page">
        <div class="controls-bar card mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label"><i class="fa fa-search"></i> Buscar farmacêutico</label>
              <input type="text" class="form-control" id="buscaPaciente" placeholder="Nome, CRF ou telefone...">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label"><i class="fa fa-filter"></i> Filtro</label>
              <select class="form-select" id="filtroStatus">
                <option value="">Todos os farmacêuticos</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#farmaceuticoModal">
                <i class="fa fa-user-plus"></i> Novo Farmacêutico
              </button>
            </div>
          </div>
        </div>

        <div class="pacientes-list card">
          <h2 class="list-title">Lista de Farmacêuticos</h2>
          <div id="lista-pacientes">
            <?php
            $sql = "SELECT id, nome, crf, email, telefone, status, criado_em FROM farmaceuticos ORDER BY nome ASC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>CRF</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Criado em</th></tr></thead><tbody>';
                while ($row = $result->fetch_assoc()):
                    $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['crf']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
                    echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
                    echo '<td>' . date('d/m/Y H:i', strtotime($row['criado_em'])) . '</td>';
                    echo '</tr>';
                endwhile;
                echo '</tbody></table>';
            else:
                echo '<p class="text-muted mt-3">Nenhum farmacêutico cadastrado.</p>';
            endif;
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="farmaceuticoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formFarmaceutico" method="post">
          <div class="modal-header">
            <h5 class="modal-title">Cadastrar Novo Farmacêutico</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome Completo *</label>
                <input type="text" class="form-control" name="nome" required>
              </div>
              <div class="col-6">
                <label class="form-label">CRF (Registro Profissional) *</label>
                <input type="text" class="form-control" name="crf" placeholder="Ex: 123456-SP" required>
              </div>
              <div class="col-6">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" required>
              </div>
              <div class="col-6">
                <label class="form-label">Telefone</label>
                <input type="tel" class="form-control" name="telefone" placeholder="(00) 00000-0000">
              </div>
              <div class="col-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                  <option value="ativo" selected>Ativo</option>
                  <option value="inativo">Inativo</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom">Salvar Farmacêutico</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/farmaceutico.js"></script>

  <script>
    function loadTemplate(templatePath, containerId) {
        fetch(templatePath)
            .then(r => r.text())
            .then(html => {
                const container = document.getElementById(containerId);
                if (container) container.innerHTML = html;
            })
            .catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
        loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
    });
  </script>
</body>
</html>
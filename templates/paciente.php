<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

// ✅ 1. Endpoint para carregar só a lista — INDEPENDENTE de ser AJAX
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $sql = "SELECT id, nome, dtnascimento, email, cpf, telefone, tipo_paciente, status FROM pacientes ORDER BY nome ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>Data de Nascimento</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Tipo</th><th>Status</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $tipoBadge = $row['tipo_paciente'] === 'cronico' ? 'warning' : 'info';
            $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($row['dtnascimento'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['cpf'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
            echo '<td><span class="badge bg-' . $tipoBadge . '">' . ucfirst($row['tipo_paciente']) . '</span></td>';
            echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum paciente cadastrado.</p>';
    endif;
    exit; // ⚠️ SAIR IMEDIATAMENTE — NÃO RENDERIZAR O RESTANTE!
}

// ✅ 2. Bloco de cadastro — só processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se é AJAX
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $nome = trim($_POST['nome'] ?? '');
    $dtnascimento = $_POST['dtnascimento'] ?? '';
    $email = $_POST['email'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $tipo_paciente = $_POST['tipo_paciente'] ?? 'agudo';
    $status = $_POST['status'] ?? 'ativo';

    if (empty($nome) || empty($dtnascimento) || empty($email)) {
        if ($isAjax) {
            echo "error: Campos obrigatórios (nome, data de nascimento, e-mail) não preenchidos.";
        } else {
            $_SESSION['error'] = "Campos obrigatórios não preenchidos.";
            header("Location: paciente.php");
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            echo "error: E-mail inválido.";
        } else {
            $_SESSION['error'] = "E-mail inválido.";
            header("Location: paciente.php");
        }
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO pacientes (nome, dtnascimento, email, cpf, telefone, tipo_paciente, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            $_SESSION['error'] = "Erro interno.";
            header("Location: paciente.php");
        }
        exit;
    }

    $stmt->bind_param("sssssss", $nome, $dtnascimento, $email, $cpf, $telefone, $tipo_paciente, $status);

    if ($stmt->execute()) {
        if ($isAjax) {
            echo "success";
        } else {
            $_SESSION['success'] = "Paciente cadastrado com sucesso!";
            header("Location: paciente.php");
        }
    } else {
        if ($isAjax) {
            echo "error: " . $stmt->error;
        } else {
            $_SESSION['error'] = "Erro ao salvar.";
            header("Location: paciente.php");
        }
    }

    $stmt->close();
    exit; // ⚠️ SAIR IMEDIATAMENTE — NÃO RENDERIZAR O RESTANTE!
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pacientes</title>
  <link rel="icon" href="/assets/favicon.png" type="image/png">
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
        <h2 class="page-title">Pacientes</h2>
        <p class="page-subtitle">Gestão completa de pacientes e prontuários.</p>
      </div>

      <div class="pacientes-page">
        <div class="controls-bar card mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label"><i class="fa fa-search"></i> Buscar paciente</label>
              <input type="text" class="form-control" id="buscaPaciente" placeholder="Nome, CPF ou telefone...">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label"><i class="fa fa-filter"></i> Filtro</label>
              <select class="form-select" id="filtroStatus">
                <option value="">Todos os pacientes</option>
                <option value="cronico">Crônicos</option>
                <option value="agudo">Agudos</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#pacienteModal">
                <i class="fa fa-user-plus"></i> Novo Paciente
              </button>
            </div>
          </div>
        </div>

        <div class="pacientes-list card">
          <h2 class="list-title">Lista de Pacientes</h2>
          <div id="lista-pacientes">
            <?php
            $sql = "SELECT id, nome, dtnascimento, email, cpf, telefone, tipo_paciente, status FROM pacientes ORDER BY nome ASC";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0):
              echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>Data de Nascimento</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Tipo</th><th>Status</th></tr></thead><tbody>';
              while ($row = mysqli_fetch_assoc($result)):
                $tipoBadge = $row['tipo_paciente'] === 'cronico' ? 'warning' : 'info';
                $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($row['dtnascimento'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars($row['cpf'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
                echo '<td><span class="badge bg-' . $tipoBadge . '">' . ucfirst($row['tipo_paciente']) . '</span></td>';
                echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
                echo '</tr>';
              endwhile;
              echo '</tbody></table>';
            else:
              echo '<p class="text-muted mt-3">Nenhum paciente cadastrado.</p>';
            endif;
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formPaciente" method="post"> <!-- 👈 ADICIONEI O ID AQUI -->
          <div class="modal-header">
            <h5 class="modal-title">Cadastrar Novo Paciente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome Completo *</label>
                <input type="text" class="form-control" name="nome" required>
              </div>
              <div class="col-6">
                <label class="form-label">Data de Nascimento *</label>
                <input type="date" class="form-control" name="dtnascimento" required>
              </div>
              <div class="col-6">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" required>
              </div>
              <div class="col-6">
                <label class="form-label">CPF</label>
                <input type="text" class="form-control" name="cpf" placeholder="000.000.000-00">
              </div>
              <div class="col-6">
                <label class="form-label">Telefone</label>
                <input type="tel" class="form-control" name="telefone" placeholder="(00) 00000-0000">
              </div>
              <div class="col-6">
                <label class="form-label">Tipo de Paciente</label>
                <select class="form-select" name="tipo_paciente">
                  <option value="agudo" selected>Agudo</option>
                  <option value="cronico">Crônico</option>
                </select>
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
            <button type="submit" name="salvar" class="btn btn-primary-custom">Cadastrar Paciente</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/paciente.js"></script>

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
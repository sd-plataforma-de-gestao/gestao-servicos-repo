<?php include("../config/database.php"); ?>

<?php
// Endpoint para carregar apenas a lista via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $sql = "SELECT id, nome, crf, email FROM farmaceuticos ORDER BY nome ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>CRF</th><th>E-mail</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            echo '<tr><td>' . htmlspecialchars($row['nome']) . '</td><td>' . htmlspecialchars($row['crf']) . '</td><td>' . htmlspecialchars($row['email']) . '</td></tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum farmacêutico cadastrado.</p>';
    endif;
    exit;
}

// Bloco de cadastro — REDIRECIONA IMEDIATAMENTE após sucesso
if (isset($_POST['salvar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $crf  = trim($_POST['crf'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($nome) || empty($crf) || empty($email)) {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Todos os campos obrigatórios devem ser preenchidos.</div>";
        }
    } else {
        $nome = mysqli_real_escape_string($conn, $nome);
        $crf = mysqli_real_escape_string($conn, $crf);
        $email = mysqli_real_escape_string($conn, $email);

        $sql = "INSERT INTO farmaceuticos (nome, crf, email) VALUES ('$nome', '$crf', '$email')";

        if (mysqli_query($conn, $sql)) {
            // 👇👇👇 REDIRECIONA IMEDIATAMENTE — EVITA F5 DUPLICAR 👇👇👇
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Erro ao cadastrar: " . mysqli_error($conn) . "</div>";
            }
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farmacêutico</title>
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
        <h2 class="page-title">Farmacêutico</h2>
        <p class="page-subtitle">Gestão e controle de acessos de Farmacêuticos</p>
      </div>

      <div class="farmaceutico-page">
        <!-- Barra de busca + filtro -->
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
              <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#pacienteModal">
                <i class="fa fa-user-plus"></i> Novo Farmacêutico
              </button>
            </div>
          </div>
        </div>

        <!-- Lista de farmacêuticos -->
        <div class="pacientes-list card">
          <h2 class="list-title">Lista de Farmacêuticos</h2>
          <div id="lista-pacientes">
            <?php
            $sql = "SELECT id, nome, crf, email FROM farmaceuticos ORDER BY nome ASC";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0):
                echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>CRF</th><th>E-mail</th></tr></thead><tbody>';
                while ($row = mysqli_fetch_assoc($result)):
                    echo '<tr><td>' . htmlspecialchars($row['nome']) . '</td><td>' . htmlspecialchars($row['crf']) . '</td><td>' . htmlspecialchars($row['email']) . '</td></tr>';
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

  <!-- Modal -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form method="post" action="">
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
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="salvar" class="btn btn-primary-custom">Salvar Farmacêutico</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
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
<?php include("../config/database.php"); ?>

<?php
if (isset($_GET['action']) && $_GET['action'] === 'load_data') {
    $sql = "SELECT id, tipo, pergunta, resposta, data_atendimento FROM atendimentos ORDER BY data_atendimento DESC LIMIT 10";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0):
        echo '<div class="list-group">';
        while ($row = mysqli_fetch_assoc($result)):
            echo '<div class="list-group-item">';
            echo '<strong>Tipo:</strong> ' . htmlspecialchars($row['tipo']) . '<br>';
            echo '<strong>Pergunta:</strong> ' . htmlspecialchars($row['pergunta']) . '<br>';
            echo '<strong>Resposta:</strong> ' . htmlspecialchars($row['resposta']) . '<br>';
            echo '<small class="text-muted">Em: ' . htmlspecialchars($row['data_atendimento']) . '</small>';
            echo '</div>';
        endwhile;
        echo '</div>';
    else:
        echo '<p class="text-muted mt-3">Nenhum atendimento registrado.</p>';
    endif;
    exit;
}
if (isset($_POST['salvar_atendimento'])) {
    $tipo     = trim($_POST['tipo'] ?? '');
    $pergunta = trim($_POST['pergunta'] ?? '');
    $resposta = trim($_POST['resposta'] ?? '');

    if (empty($tipo) || empty($pergunta) || empty($resposta)) {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Todos os campos são obrigatórios.</div>";
        }
    } else {
        $tipo     = mysqli_real_escape_string($conn, $tipo);
        $pergunta = mysqli_real_escape_string($conn, $pergunta);
        $resposta = mysqli_real_escape_string($conn, $resposta);

        $sql = "INSERT INTO atendimentos (tipo, pergunta, resposta, data_atendimento) VALUES ('$tipo', '$pergunta', '$resposta', NOW())";

        if (mysqli_query($conn, $sql)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Erro ao registrar atendimento: " . mysqli_error($conn) . "</div>";
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
  <title>Atendimento Farmacêutico - Vitally</title>
  <link rel="icon" href="/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/styles/global.css">
  <link rel="stylesheet" href="/styles/header.css">
  <link rel="stylesheet" href="/styles/sidebar.css">
  <link rel="stylesheet" href="/styles/main.css">
  <link rel="stylesheet" href="/styles/responsive.css">
  <link rel="stylesheet" href="/styles/atendimento.css">
</head>
<body>
  <div id="header-container"></div>
  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>
    <div id="main-container">
      <div class="page-header">
        <h2 class="page-title">Atendimento Farmacêutico</h2>
        <p class="page-subtitle">Auxílio inteligente no atendimento ao paciente. Comece selecionando o tipo de atendimento.</p>
      </div>
      <div class="mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#atendimentoModal">
          <i class="fa fa-plus"></i> Novo Atendimento
        </button>
        <button class="btn btn-outline-secondary ms-2" id="btn-load-data">
          <i class="fa fa-sync"></i> Recarregar Dados
        </button>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">📋 Últimos Atendimentos Registrados</h5>
        </div>
        <div class="card-body" id="lista-atendimentos">
          <?php
          $sql = "SELECT id, tipo, pergunta, resposta, data_atendimento FROM atendimentos ORDER BY data_atendimento DESC LIMIT 5";
          $result = mysqli_query($conn, $sql);

          if ($result && mysqli_num_rows($result) > 0):
              echo '<div class="list-group">';
              while ($row = mysqli_fetch_assoc($result)):
                  echo '<div class="list-group-item">';
                  echo '<strong>Tipo:</strong> ' . htmlspecialchars($row['tipo']) . '<br>';
                  echo '<strong>Pergunta:</strong> ' . htmlspecialchars($row['pergunta']) . '<br>';
                  echo '<strong>Resposta:</strong> ' . htmlspecialchars($row['resposta']) . '<br>';
                  echo '<small class="text-muted">Em: ' . htmlspecialchars($row['data_atendimento']) . '</small>';
                  echo '</div>';
              endwhile;
              echo '</div>';
          else:
              echo '<p class="text-muted mt-3">Nenhum atendimento registrado.</p>';
          endif;
          ?>
        </div>
      </div>

      <div class="card chat-container d-none mt-4" id="chat-container">
        <div class="chat-messages p-3" id="chat-messages">
        </div>
        <div class="chat-input-group p-3 border-top">
          <div class="input-group">
            <input type="text" class="form-control" id="user-input" placeholder="Digite sua pergunta ou resposta ao paciente..." />
            <button class="btn btn-primary-custom" id="send-btn">
              <i class="fa fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="card suggestions-card d-none mt-4" id="suggestions">
        <div class="card-header">
          <h5 class="mb-0">💡 Sugestões para o atendimento</h5>
        </div>
        <ul class="list-group list-group-flush" id="suggestion-list">
        </ul>
      </div>
    </div>
  </div>

  <div class="modal fade" id="atendimentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form method="post" action="">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Novo Atendimento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Tipo de Atendimento *</label>
                <select class="form-select" name="tipo" required>
                  <option value="">Selecione...</option>
                  <option value="Agudo">Agudo</option>
                  <option value="Crônico">Crônico</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Pergunta do Paciente *</label>
                <textarea class="form-control" name="pergunta" rows="3" required></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Resposta do Farmacêutico *</label>
                <textarea class="form-control" name="resposta" rows="3" required></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="salvar_atendimento" class="btn btn-primary-custom">Salvar Atendimento</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/script.js"></script>
  <script src="/js/atendimento.js"></script>

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
        loadTemplate('/templates/header.php', 'header-container');
        loadTemplate('/templates/sidebar.php', 'sidebar-container');
        if (typeof initializeSidebar === 'function') initializeSidebar();
        if (typeof initializeActionButtons === 'function') initializeActionButtons();
        if (typeof initializeTooltips === 'function') initializeTooltips();
        if (typeof initializeNavigation === 'function') initializeNavigation();
        if (typeof setActiveSidebarLink === 'function') setActiveSidebarLink();
        if (typeof initAtendimento === 'function') initAtendimento();

        document.getElementById('btn-load-data')?.addEventListener('click', function() {
            fetch('?action=load_data')
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('lista-atendimentos');
                    if (container) container.innerHTML = html;
                })
                .catch(err => console.error('Erro ao recarregar dados:', err));
        });
    });
  </script>

</body>
</html>
<?php
include("../config/database.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo inserirAtendimento();
    exit;
}

function inserirAtendimento() {
    global $conn;

    $respostas_json = json_decode(file_get_contents("php://input"), true);
    if (!$respostas_json) {
        http_response_code(400);
        return "Dados inválidos.";
    }

    $ChatHistori = json_encode($respostas_json);

    $paciente_id = 1;
    $farmaceutico_id = 1;
    $criado_em = date(DATE_ATOM);

    $stmt = $conn->prepare("INSERT INTO atendimentos (paciente_id, farmaceutico_id, respostas_json, criado_em) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $paciente_id, $farmaceutico_id, $ChatHistori, $criado_em);

    if ($stmt->execute()) {
        $stmt->close();
        return "Atendimento finalizado com sucesso!";
    } else {
        $error = $stmt->error;
        $stmt->close();
        return "Erro ao salvar: $error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Atendimento Farmacêutico - Vitally</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/favicon.png" type="image/png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Custom Styles -->
  <link rel="stylesheet" href="../styles/global.css">
  <link rel="stylesheet" href="../styles/header.css">
  <link rel="stylesheet" href="../styles/sidebar.css">
  <link rel="stylesheet" href="../styles/main.css">
  <link rel="stylesheet" href="../styles/responsive.css">
  <link rel="stylesheet" href="../styles/atendimento.css">
</head>

<body>

  <!-- Cabeçalho -->
  <div id="header-container"></div>

  <!-- Container principal -->
  <div id="main-content-wrapper">
    <!-- Sidebar -->
    <div id="sidebar-container"></div>

    <!-- Conteúdo -->
    <div id="main-container">
      <div class="page-header">
        <h2 class="page-title">Atendimento Farmacêutico</h2>
        <p class="page-subtitle">Auxílio inteligente no atendimento ao paciente. Comece selecionando o tipo de atendimento.</p>
      </div>

      <!-- Tipo de Atendimento -->
      <div class="card mb-4 p-4" id="tipo-atendimento">
        <h4 class="mb-3">Selecione o tipo de atendimento</h4>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-secondary" data-tipo="agudo">
            <i class="fa fa-bolt"></i> Agudo
          </button>
          <button type="button" class="btn btn-outline-secondary" data-tipo="cronico">
            <i class="fa fa-heartbeat"></i> Crônico
          </button>
        </div>
      </div>

      <!-- Chat Container -->
      <div class="card chat-container d-none" id="chat-container">
        <div class="chat-messages p-3" id="chat-messages">
          <!-- Mensagens aparecerão aqui -->
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

      <!-- Sugestões -->
      <div class="card suggestions-cardd-none mt-4" id="suggestions">
        <div class="card-header">
          <h5 class="mb-0">💡 Sugestões para o atendimento</h5>
        </div>
        <ul class="list-group list-group-flush" id="suggestion-list">
          <!-- Sugestões serão inseridas aqui -->
        </ul>
      </div>
    </div>
  </div>



  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/script.js"></script>
  <script src="../js/atendimento.js"></script>

  <!-- Carrega Header e Sidebar -->
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
      // Carrega header e sidebar
      await loadTemplate('header.php', 'header-container');
      await loadTemplate('sidebar.php', 'sidebar-container');

      // Inicializa funções globais (do script.js)
      if (typeof initializeSidebar === 'function') initializeSidebar();
      if (typeof initializeActionButtons === 'function') initializeActionButtons();
      if (typeof initializeTooltips === 'function') initializeTooltips();
      if (typeof initializeNavigation === 'function') initializeNavigation();
      if (typeof setActiveSidebarLink === 'function') setActiveSidebarLink();

      // Inicializa o módulo de atendimento
      initAtendimento();
    });
  </script>
</body>
</html>
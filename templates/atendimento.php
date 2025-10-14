<?php
include("../config/database.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Endpoint para listar pacientes ativos com suporte a busca por nome ou CPF
if (isset($_GET['action']) && $_GET['action'] === 'listar_pacientes') {
  $search = $_GET['search'] ?? '';
  $where = "status = 'ativo'";
  $params = [];

  if ($search !== '') {
    $like = '%' . trim($search) . '%';
    $where .= " AND (nome LIKE ? OR cpf LIKE ?)";
    $params[] = $like;
    $params[] = $like;
  }

  $sql = "SELECT id, nome, cpf FROM pacientes WHERE $where ORDER BY nome ASC";
  $stmt = $conn->prepare($sql);
  if (!empty($params)) {
    $stmt->bind_param('ss', ...$params);
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

// Salvar atendimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents("php://input"), true);
  if (!$input || !isset($input['chatHistory']) || !isset($input['paciente_id'])) {
    http_response_code(400);
    echo "Dados incompletos.";
    exit;
  }

  $paciente_id = (int)$input['paciente_id'];
  $respostas_json = json_encode($input['chatHistory']);
  $farmaceutico_id = 1; // ← substitua por $_SESSION['farmaceutico_id'] depois
  $criado_em = date('Y-m-d H:i:s');

  // Novos campos: tipo_atendimento e status_atendimento
  // Contar atendimentos anteriores do paciente
  $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM atendimentos WHERE paciente_id = ? AND status_atendimento = 'Concluído'");
  $countStmt->bind_param("i", $paciente_id);
  $countStmt->execute();
  $count = $countStmt->get_result()->fetch_assoc()['total'];
  $countStmt->close();

  // Definir tipo com base na contagem
  if ($count === 0) {
    $tipo_atendimento = 'Primeira Consulta';
  } else {
    // Você pode escolher "Retorno" ou "Acompanhamento" — vamos usar "Retorno"
    $tipo_atendimento = 'Retorno';
  }

  $status_atendimento = 'Concluído';

  // Valida paciente
  $check = $conn->prepare("SELECT id FROM pacientes WHERE id = ? AND status = 'ativo'");
  $check->bind_param("i", $paciente_id);
  $check->execute();
  if (!$check->get_result()->fetch_assoc()) {
    echo "Paciente inválido.";
    exit;
  }
  $check->close();

  $stmt = $conn->prepare("INSERT INTO atendimentos (paciente_id, farmaceutico_id, tipo_atendimento, status_atendimento, respostas_json, criado_em) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("iissss", $paciente_id, $farmaceutico_id, $tipo_atendimento, $status_atendimento, $respostas_json, $criado_em);

  if ($stmt->execute()) {
    echo "Atendimento salvo com sucesso!";
  } else {
    echo "Erro ao salvar: " . $stmt->error;
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
  <title>Atendimento Farmacêutico - Vitally</title>
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
        <h2 class="page-title">Atendimento Farmacêutico</h2>
        <p class="page-subtitle">Selecione um paciente para iniciar o atendimento.</p>
      </div>

      <!-- Seleção de Paciente -->
      <div id="selecao-paciente" class="card mb-4 p-4">
        <h4 class="mb-3">Selecione o paciente</h4>
        <input type="text" class="form-control mb-2" id="busca-paciente" placeholder="Buscar por nome ou CPF...">
        <div class="list-group" id="lista-pacientes" style="max-height: 300px; overflow-y: auto;">
          <!-- Pacientes carregados via JS -->
        </div>
      </div>

      <!-- Tipo de Atendimento -->
      <div id="tipo-atendimento" class="card mb-4 p-4 d-none">
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

      <!-- Chat -->
      <div class="card chat-container d-none" id="chat-container">
        <div class="chat-messages p-3" id="chat-messages"></div>
        <div class="chat-input-group p-3 border-top">
          <div class="input-group">
            <input type="text" class="form-control" id="user-input" placeholder="Digite sua pergunta..." disabled />
            <button class="btn btn-primary-custom" id="send-btn" disabled>
              <i class="fa fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="card suggestions-card d-none mt-4" id="suggestions">
        <div class="card-header">
          <h5 class="mb-0">💡 Sugestões</h5>
        </div>
        <ul class="list-group list-group-flush" id="suggestion-list"></ul>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/atendimento.js"></script>

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
      initAtendimento();
    });
  </script>
</body>

</html>
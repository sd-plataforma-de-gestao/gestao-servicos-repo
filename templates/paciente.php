<?php include("../config/database.php"); ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pacientes</title>

  <!-- Favicons -->
  <link rel="icon" href="/assets/favicon.png" type="image/png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Custom Styles -->
  <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/paciente.css">
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
            <h2 class="page-title">Pacientes</h2>
            <p class="page-subtitle">Gestão completa de pacientes e prontuários.</p>
        </div>

        <div class="pacientes-page">
            <!-- Barra de busca + filtro -->
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

            <!-- Lista de Pacientes -->
            <div class="pacientes-list card">
                <h2 class="list-title">Lista de Pacientes</h2>
                <div id="lista-pacientes">
                    <?php
                    // Consulta para listar pacientes (nome, dtnascimento, email)
                    $sql = "SELECT nome, dtnascimento, email FROM pacientes ORDER BY nome ASC";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0):
                        echo '<table class="table table-striped table-hover mt-3">';
                        echo '<thead class="table-light">';
                        echo '<tr>';
                        echo '<th>Nome</th>';
                        echo '<th>Data de Nascimento</th>';
                        echo '<th>E-mail</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        while ($row = mysqli_fetch_assoc($result)):
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['dtnascimento']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '</tr>';
                        endwhile;

                        echo '</tbody>';
                        echo '</table>';
                    else:
                        echo '<p class="text-muted mt-3">Nenhum paciente cadastrado.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- =============== MODAL CADASTRAR =============== -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-labelledby="pacienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form method="post" action="">
          <div class="modal-header">
            <h5 class="modal-title" id="pacienteModalLabel">Cadastrar Novo Paciente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="pacienteId">

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

              <!-- Campos adicionais (visuais apenas, não usados no cadastro) -->
              <div class="col-6">
                <label class="form-label">Sexo *</label>
                <select class="form-select" name="sexoPaciente" >
                  <option value="">Selecione...</option>
                  <option value="Masculino">Masculino</option>
                  <option value="Feminino">Feminino</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">CPF *</label>
                <input type="text" class="form-control" name="cpfPaciente" placeholder="000.000.000-00" >
              </div>
              <div class="col-6">
                <label class="form-label">Contato *</label>
                <input type="text" class="form-control" name="telefonePaciente" placeholder="+(11) 99999-9999" >
              </div>
              <div class="col-12">
                <label class="form-label">Endereço *</label>
                <input type="text" class="form-control" name="enderecoPaciente" placeholder="Rua, número, bairro, cidade" >
              </div>
              <div class="col-6">
                <label class="form-label">Última Consulta</label>
                <input type="date" class="form-control" name="ultimaConsultaPaciente">
              </div>
              <div class="col-6">
                <label class="form-label">Tipo do Paciente</label>
                <select class="form-select" name="statusPaciente">
                  <option value="">Selecione...</option>
                  <option value="Crônico">Crônico</option>
                  <option value="Agudo">Agudo</option>
                </select>
              </div>
            </div>
            <small class="text-muted mt-2">* Campos obrigatórios: Nome, Data de Nascimento e Email</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="salvar" class="btn btn-primary-custom">Salvar Paciente</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- =============== MENSAGEM DE SUCESSO/ERRO =============== -->
  <?php
  if (isset($_POST['salvar'])) {
      $nome = trim($_POST['nome'] ?? '');
      $dtnascimento = $_POST['dtnascimento'] ?? '';
      $email = $_POST['email'] ?? '';

      if (empty($nome) || empty($dtnascimento) || empty($email)) {
          echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Todos os campos obrigatórios devem ser preenchidos.</div>";
      } else {
          // Sanitiza para segurança
          $nome = mysqli_real_escape_string($conn, $nome);
          $dtnascimento = mysqli_real_escape_string($conn, $dtnascimento);
          $email = mysqli_real_escape_string($conn, $email);

          $sql = "INSERT INTO pacientes (nome, dtnascimento, email) VALUES ('$nome', '$dtnascimento', '$email')";

          if (mysqli_query($conn, $sql)) {
              echo "<div class='alert alert-success mx-4 my-3' role='alert'>Paciente cadastrado com sucesso!</div>";
          } else {
              echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Erro ao cadastrar: " . mysqli_error($conn) . "</div>";
          }
      }
  }
  ?>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/paciente.js"></script>

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

    document.addEventListener('DOMContentLoaded', async function () {
      await loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
      await loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');

      initializeSidebar();
      initializeActionButtons();
      initializeTooltips();
      initializeNavigation();
      setActiveSidebarLink();
    });
  </script>

  <!-- MODAIS SECUNDÁRIOS (mantidos inalterados) -->
  <div class="modal fade" id="relatorioPacienteModal" tabindex="-1" aria-labelledby="relatorioPacienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="relatorioPacienteLabel">Relatório do Paciente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="relatorioCorpo"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary-custom" id="btnEditarPaciente">
            <i class="fa fa-edit"></i> Editar
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="detalhesPacienteModal" tabindex="-1" aria-labelledby="detalhesPacienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detalhesPacienteLabel">Detalhes do Paciente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalhesCorpo"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary-custom" id="btnEditarDoDetalhe">
            <i class="fa fa-edit"></i> Editar
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="prontuarioModal" tabindex="-1" aria-labelledby="prontuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="prontuarioModalLabel">Prontuário Farmacêutico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="prontuarioCorpo"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
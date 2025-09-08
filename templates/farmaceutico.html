<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farmacêutico</title>

  <!-- Favicons -->
  <link rel="icon" href="/assets/favicon.png" type="image/png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Custom Styles -->
  <link rel="stylesheet" href="/styles/global.css">
  <link rel="stylesheet" href="/styles/header.css">
  <link rel="stylesheet" href="/styles/sidebar.css">
  <link rel="stylesheet" href="/styles/main.css">
  <link rel="stylesheet" href="/styles/responsive.css">
  <link rel="stylesheet" href="/styles/paciente.css">
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
        <h2 class="page-title">Farmacêutico</h2>
        <p class="page-subtitle">Gestão e controle de acessos de Farmacêuticos</p>
      </div>
      <div class="farmaceutico-page">
        <!-- Barra de busca + filtro -->
        <div class="controls-bar card mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label"><i class="fa fa-search"></i> Buscar farmacêutico</label>
              <input
                type="text"
                class="form-control"
                id="buscaPaciente"
                placeholder="Nome, CRF ou telefone..."
              >
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
              <button
                class="btn btn-success w-100"
                data-bs-toggle="modal"
                data-bs-target="#pacienteModal"
              >
                <i class="fa fa-user-plus"></i> Novo Farmacêutico
              </button>
            </div>
          </div>
        </div>

        <!-- Lista de farmacêuticos -->
        <div class="pacientes-list card">
          <h2 class="list-title">Lista de Farmacêuticos</h2>
          <div id="lista-pacientes"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- =============== MODAL CADASTRAR/EDITAR =============== -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-labelledby="pacienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formPaciente">
          <div class="modal-header">
            <h5 class="modal-title" id="pacienteModalLabel">Cadastrar Novo Farmacêutico</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="pacienteId">

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome Completo *</label>
                <input type="text" class="form-control" id="nomePaciente" required>
              </div>
              <div class="col-6">
                <label class="form-label">CRF (Registro Profissional) *</label>
                <input type="text" class="form-control" id="idadePaciente" placeholder="Ex: 123456-SP" required>
              </div>
              <div class="col-6">
                <label class="form-label">Sexo *</label>
                <select class="form-select" id="sexoPaciente" required>
                  <option value="">Selecione...</option>
                  <option value="Masculino">Masculino</option>
                  <option value="Feminino">Feminino</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">CPF *</label>
                <input type="text" class="form-control" id="cpfPaciente" placeholder="000.000.000-00" required>
              </div>
              <div class="col-6">
                <label class="form-label">Contato *</label>
                <input type="text" class="form-control" id="telefonePaciente" placeholder="+(11) 99999-9999" required>
              </div>
              <div class="col-12">
                <label class="form-label">Endereço *</label>
                <input type="text" class="form-control" id="enderecoPaciente" placeholder="Rua, número, bairro, cidade" required>
              </div>
              <div class="col-6">
                <label class="form-label">Data de Contratação</label>
                <input type="date" class="form-control" id="ultimaConsultaPaciente">
              </div>
              <div class="col-6">
                <label class="form-label">Situação</label>
                <select class="form-select" id="statusPaciente">
                  <option value="">Selecione...</option>
                  <option value="Ativo">Ativo</option>
                  <option value="Inativo">Inativo</option>
                </select>
              </div>
            </div>
            <small class="text-muted mt-2">* Campos obrigatórios</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom">Salvar Farmacêutico</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- =============== MODAL RELATÓRIO =============== -->
  <div class="modal fade" id="relatorioPacienteModal" tabindex="-1" aria-labelledby="relatorioPacienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="relatorioPacienteLabel">Relatório do Farmacêutico</h5>
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/script.js"></script>
  <script src="/js/farmaceutico.js"></script>

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
      // Carrega header e sidebar
      await loadTemplate('/templates/header.html', 'header-container');
      await loadTemplate('/templates/sidebar.html', 'sidebar-container');

      // AGORA SIM, os elementos existem
      // Inicializa as funções do script.js
      initializeSidebar();
      initializeActionButtons();
      initializeTooltips();
      initializeNavigation();
      setActiveSidebarLink();
    });
  </script>

  <!-- =============== MODAL DE DETALHES (VISUALIZAR DADOS) =============== -->
  <div class="modal fade" id="detalhesPacienteModal" tabindex="-1" aria-labelledby="detalhesPacienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detalhesPacienteLabel">Detalhes do Farmacêutico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalhesCorpo">
          <!-- Dados serão preenchidos aqui -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary-custom" id="btnEditarDoDetalhe">
            <i class="fa fa-edit"></i> Editar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- =============== MODAL DE PRONTUÁRIO =============== -->
  <div class="modal fade" id="prontuarioModal" tabindex="-1" aria-labelledby="prontuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="prontuarioModalLabel">Prontuário Farmacêutico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="prontuarioCorpo">
          <!-- Dados carregados dinamicamente -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
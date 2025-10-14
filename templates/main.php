<div class="main-content">
  <div class="page-header">
    <h2 class="page-title">Início</h2>
    <p class="page-subtitle">Visão geral dos atendimentos farmacêuticos</p>
  </div>

  <!-- Indicadores -->
  <div class="stats-container">
    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-info">
          <p class="stat-label">Atendimentos Hoje</p>
          <p class="stat-value" id="atendimentos-hoje">...</p>
          <p class="stat-change positive" id="atendimentos-hoje-change">Carregando...</p>
        </div>
        <div class="stat-icon">
          <i class="fas fa-user"></i>
        </div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-info">
          <p class="stat-label">Consultas Crônicas</p>
          <p class="stat-value" id="consultas-cronicas">...</p>
          <p class="stat-change neutral" id="consultas-cronicas-change">Próximas 2h</p>
        </div>
        <div class="stat-icon">
          <i class="fas fa-calendar"></i>
        </div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-info">
          <p class="stat-label">Casos Agudos</p>
          <p class="stat-value" id="casos-agudos">...</p>
          <p class="stat-change positive" id="casos-agudos-change">+8% esta semana</p>
        </div>
        <div class="stat-icon">
          <i class="fas fa-file-medical"></i>
        </div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-info">
          <p class="stat-label">Taxa de Adesão</p>
          <p class="stat-value" id="taxa-adesao">...</p>
          <p class="stat-change positive" id="taxa-adesao-change">+2% este mês</p>
        </div>
        <div class="stat-icon">
          <i class="fas fa-arrow-up"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Atividades Recentes + Ações Rápidas -->
  <div class="content-grid">
    <div class="activities-section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">
            <i class="fas fa-clock me-2"></i>
            Atividades Recentes
          </h5>
        </div>
        <div class="card-body">
          <div class="activity-list" id="activity-list">
            <div class="text-center py-4">Carregando...</div>
          </div>
        </div>
      </div>
    </div>

    <div class="actions-section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">Ações Rápidas</h5>
        </div>
        <div class="card-body">
          <div class="actions-grid">
            <button class="action-btn" onclick="window.location.href='/portal-repo-og/templates/atendimento.php'">
              <i class="fas fa-plus"></i>
              <span class="action-title">Novo Atendimento</span>
              <small class="action-subtitle">Iniciar consulta farmacêutica</small>
            </button>

            <button class="action-btn" onclick="window.location.href='/portal-repo-og/templates/paciente.php'">
              <i class="fas fa-search"></i>
              <span class="action-title">Buscar Paciente</span>
              <small class="action-subtitle">Localizar prontuário existente</small>
            </button>

            <button class="action-btn" onclick="window.location.href='/portal-repo-og/templates/agendamento.php'">
              <i class="fas fa-calendar-check"></i>
              <span class="action-title">Agendar Retorno</span>
              <small class="action-subtitle">Programar próxima consulta</small>
            </button>

            <button class="action-btn" onclick="window.location.href='/portal-repo-og/templates/insights.php'">
              <i class="fas fa-file-alt"></i>
              <span class="action-title">Gerar Relatório</span>
              <small class="action-subtitle">Criar relatório de gestão</small>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



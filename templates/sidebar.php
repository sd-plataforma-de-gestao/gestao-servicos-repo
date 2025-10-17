<!-- sidebar.php -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h5 class="sidebar-title">Bom dia Ana!</h5>
    <h5 class="sidebar-subtitle">UNIDADE 1</h5>
  </div>

  <ul class="sidebar-menu">
    <li class="sidebar-item">
      <a href="#" class="sidebar-link" onclick="window.location.href='/portal-repo-og/index.php'; return false;">
        <i class="fas fa-home"></i>
        <span>Início</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/atendimento.php" class="sidebar-link">
        <i class="fas fa-hospital-alt"></i>
        <span>Atendimento</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/historico_atendimento.php" class="sidebar-link">
        <i class="fas fa-history"></i>
        <span>Histórico de Atendimento</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/paciente.php" class="sidebar-link">
        <i class="fa-solid fa-user-injured"></i>
        <span>Pacientes</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/farmaceutico.php" class="sidebar-link">
        <i class="fa-solid fa-stethoscope"></i>
        <span>Farmacêuticos</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/medicamento.php" class="sidebar-link">
        <i class="fa-solid fa-heart-pulse"></i>
        <span>Medicamentos</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/insights.php" class="sidebar-link">
        <i class="fas fa-chart-line"></i>
        <span>Relatórios e Insights</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/unidade.php" class="sidebar-link">
        <i class="fas fa-building"></i>
        <span>Gestão de Unidades</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="#" class="sidebar-link">
        <i class="fas fa-cog"></i>
        <span>Configurações</span>
      </a>
    </li>
  </ul>

  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
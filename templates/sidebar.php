<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <?php
    session_start();
    $nome_exibicao = $_SESSION['farmaceutico_nome'] ?? 'Usuário';
    $nome_formatado = 'Olá! Dr(a). ' . htmlspecialchars($nome_exibicao, ENT_QUOTES, 'UTF-8');
    ?>
    <span class="user-name"><?= $nome_formatado ?></span>
  </div>

  <ul class="sidebar-menu">
    <li class="sidebar-item">
      <a href="/portal-repo-og/index.php" class="sidebar-link" data-page="inicio">
          <i class="fas fa-home"></i>
          <span>Início</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/atendimento.php" class="sidebar-link" data-page="atendimento">
        <i class="fas fa-hospital-alt"></i>
        <span>Atendimento</span>
      </a>
    </li>

      <li class="sidebar-item">
        <a href="/portal-repo-og/templates/historico_atendimento.php" class="sidebar-link" data-page="historico_atendimento">
          <i class="fas fa-history"></i>
          <span>Histórico de Atendimento</span>
        </a>
      </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/paciente.php" class="sidebar-link" data-page="pacientes">
        <i class="fa-solid fa-user-injured"></i>
        <span>Pacientes</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/farmaceutico.php" class="sidebar-link" data-page="farmaceuticos">
        <i class="fa-solid fa-stethoscope"></i>
        <span>Farmacêuticos</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/medicamento.php" class="sidebar-link" data-page="medicamentos">
        <i class="fa-solid fa-heart-pulse"></i>
        <span>Medicamentos</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/insights.php" class="sidebar-link" data-page="relatorios">
        <i class="fas fa-chart-line"></i>
        <span>Relatórios e Insights</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/unidade.php" class="sidebar-link" data-page="unidades">
        <i class="fas fa-building"></i>
        <span>Gestão de Unidades</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="/portal-repo-og/templates/config.php" class="sidebar-link" data-page="configuracoes">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    
    sidebarLinks.forEach(link => link.classList.remove('active'));
    
    const activeLink = document.querySelector(`.sidebar-link[data-page="<?= $page_active ?? '' ?>"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            sidebarLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
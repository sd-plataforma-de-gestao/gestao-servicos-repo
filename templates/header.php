<!-- header.php -->
<header class="header">
  <button id="menu-toggle" class="menu-toggle-btn" aria-label="Abrir e fechar menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  
  <div class="header-logo">
    <img src="/portal-repo-og/assets/logo-header.png" alt="Vitally Logo" />
  </div>

  <div class="header-user-info">
    <div class="user-details">
      <span class="user-name">Dr. Ana Silva</span>
      <span class="user-role">Farmacêutica - CRF 12345</span>
    </div>
    <div class="user-actions">
      <div class="dropdown">
        <button class="header-btn" data-bs-toggle="dropdown" title="Menu do usuário">
          <i class="fas fa-chevron-down"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
          <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configurações</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/portal-repo-og/templates/logar.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
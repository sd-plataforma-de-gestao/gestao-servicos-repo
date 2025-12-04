<header class="header">
  <button id="menu-toggle" class="menu-toggle-btn" aria-label="Abrir e fechar menu">
    <i class="fa-solid fa-bars"></i>
  </button>

  <div class="header-logo">
    <a href="/portal-repo-og/index.php">
      <img src="/portal-repo-og/assets/logo-header.png" alt="Vitally Logo" />
    </a>
  </div>

  <div class="header-user-info">
    <div class="user-details">
      <?php
      session_start();
      $nome_exibicao = $_SESSION['farmaceutico_nome'] ?? 'Usuário';
      $nome_formatado = 'Dr(a). ' . htmlspecialchars($nome_exibicao, ENT_QUOTES, 'UTF-8');
      ?>
      <span class="user-name"><?= $nome_formatado ?></span>
    </div>
    <div class="user-actions">
      <div class="dropdown">
        <button class="header-btn" data-bs-toggle="dropdown" title="Menu do usuário">
          <i class="fas fa-chevron-down"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="/portal-repo-og/templates/config.php"><i class="fas fa-cog me-2"></i>Configurações</a></li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li><a class="dropdown-item" href="/portal-repo-og/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
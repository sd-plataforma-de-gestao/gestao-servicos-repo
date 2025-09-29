<?php include("../config/database.php"); ?>

<?php
include_once __DIR__ . '/../config/auth.php';

if (!Auth::isAuthenticated()) {
    header("Location: /portal-repo-og/templates/login.php");
    exit();
}
?>
<?php
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $sql = "SELECT * FROM unidades ORDER BY nome ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0):
        while ($row = mysqli_fetch_assoc($result)):
            $status_class = match($row['status']) {
                'Ativa' => 'success',
                'Inativa' => 'danger',
                'Manutenção' => 'warning',
                default => 'secondary'
            };
            ?>
            <div class="unidade-card">
                <div class="unidade-header">
                    <h3 class="unidade-nome"><?= htmlspecialchars($row['nome']) ?></h3>
                    <span class="badge bg-<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                </div>
                <div class="unidade-body">
                    <p><i class="fa fa-building me-2"></i> <strong>CNPJ:</strong> <?= htmlspecialchars($row['cnpj']) ?></p>
                    <p><i class="fa fa-phone me-2"></i> <strong>Telefone:</strong> <?= htmlspecialchars($row['telefone']) ?></p>
                    <p><i class="fa fa-map-marker-alt me-2"></i> <strong>Endereço:</strong> <?= htmlspecialchars($row['endereco']) ?></p>
                    <?php if (!empty($row['farmaceutico_responsavel'])): ?>
                        <p><i class="fa fa-user-md me-2"></i> <strong>Farm. Responsável:</strong> <?= htmlspecialchars($row['farmaceutico_responsavel']) ?> (<?= htmlspecialchars($row['crf_responsavel']) ?>)</p>
                    <?php endif; ?>
                    <?php if (!empty($row['horario_funcionamento'])): ?>
                        <p><i class="fa fa-clock me-2"></i> <strong>Horário:</strong> <?= htmlspecialchars($row['horario_funcionamento']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="unidade-footer">
                    <button class="btn btn-sm btn-outline-primary me-1 btn-ver" data-id="<?= $row['id'] ?>">
                        <i class="fa fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-outline-warning me-1 btn-editar" data-id="<?= $row['id'] ?>">
                        <i class="fa fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $row['id'] ?>">
                        <i class="fa fa-trash"></i> Excluir
                    </button>
                </div>
            </div>
            <?php
        endwhile;
    else:
        echo '<p class="text-muted text-center py-5">Nenhuma unidade cadastrada.</p>';
    endif;
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'get_unidade' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM unidades WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unidade = $result->fetch_assoc();

    if ($unidade) {
        header('Content-Type: application/json');
        echo json_encode($unidade);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Unidade não encontrada']);
    }
    exit;
}

if (isset($_POST['salvar'])) {
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $farmaceutico_responsavel = trim($_POST['farmaceutico_responsavel'] ?? '');
    $crf_responsavel = trim($_POST['crf_responsavel'] ?? '');
    $horario_funcionamento = trim($_POST['horario_funcionamento'] ?? '');
    $status = $_POST['status'] ?? 'Ativa';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (empty($nome) || empty($cnpj) || empty($telefone) || empty($endereco)) {
        echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Campos obrigatórios não preenchidos.</div>";
        exit;
    }
    $nome = mysqli_real_escape_string($conn, $nome);
    $cnpj = mysqli_real_escape_string($conn, $cnpj);
    $telefone = mysqli_real_escape_string($conn, $telefone);
    $endereco = mysqli_real_escape_string($conn, $endereco);
    $farmaceutico_responsavel = mysqli_real_escape_string($conn, $farmaceutico_responsavel);
    $crf_responsavel = mysqli_real_escape_string($conn, $crf_responsavel);
    $horario_funcionamento = mysqli_real_escape_string($conn, $horario_funcionamento);
    $status = mysqli_real_escape_string($conn, $status);
    $observacoes = mysqli_real_escape_string($conn, $observacoes);

    if ($id) {
        $sql = "UPDATE unidades SET 
                    nome = ?, 
                    cnpj = ?, 
                    telefone = ?, 
                    endereco = ?, 
                    farmaceutico_responsavel = ?, 
                    crf_responsavel = ?, 
                    horario_funcionamento = ?, 
                    status = ?, 
                    observacoes = ?, 
                    atualizado_em = NOW() 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssi",
            $nome,
            $cnpj,
            $telefone,
            $endereco,
            $farmaceutico_responsavel,
            $crf_responsavel,
            $horario_funcionamento,
            $status,
            $observacoes,
            $id
        );
    } else {
        $sql = "INSERT INTO unidades (
                    nome, 
                    cnpj, 
                    telefone, 
                    endereco, 
                    farmaceutico_responsavel, 
                    crf_responsavel, 
                    horario_funcionamento, 
                    status, 
                    observacoes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss",
            $nome,
            $cnpj,
            $telefone,
            $endereco,
            $farmaceutico_responsavel,
            $crf_responsavel,
            $horario_funcionamento,
            $status,
            $observacoes
        );
    }

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Erro ao salvar: " . $stmt->error . "</div>";
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'excluir' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM unidades WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'load_stats') {
    $sql_total = "SELECT COUNT(*) as total FROM unidades";
    $result_total = mysqli_query($conn, $sql_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $total_unidades = $row_total['total'];

    $sql_farma = "SELECT COUNT(*) as total FROM farmaceuticos WHERE status = 'ativo'";
    $result_farma = mysqli_query($conn, $sql_farma);
    $row_farma = mysqli_fetch_assoc($result_farma);
    $total_farmaceuticos = $row_farma['total'];

    $sql_atendimentos = "SELECT COUNT(*) as total FROM atendimentos WHERE DATE(criado_em) = CURDATE()";
    $result_atendimentos = mysqli_query($conn, $sql_atendimentos);
    $row_atendimentos = mysqli_fetch_assoc($result_atendimentos);
    $atendimentos_hoje = $row_atendimentos['total'];

    $stats = [
        'total_unidades' => $total_unidades,
        'total_farmaceuticos' => $total_farmaceuticos,
        'atendimentos_hoje' => $atendimentos_hoje
    ];

    header('Content-Type: application/json');
    echo json_encode($stats);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestão de Unidades</title>
  <link rel="icon" href="/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <link rel="stylesheet" href="/styles/global.css">
  <link rel="stylesheet" href="/styles/header.css">
  <link rel="stylesheet" href="/styles/sidebar.css">
  <link rel="stylesheet" href="/styles/responsive.css">
  <link rel="stylesheet" href="/styles/unidade.css">
</head>
<body>
  <div id="header-container"></div>
  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>
    <div id="main-container">
      <div class="page-header-with-button">
        <div>
          <h2 class="page-title">Gestão de Unidades</h2>
          <p class="page-subtitle">Administração das unidades da rede farmacêutica</p>
        </div>
        <button 
          class="btn btn-success btn-nova-unidade"
          data-bs-toggle="modal"
          data-bs-target="#unidadeModal"
          onclick="resetFormUnidade()"
        >
          <i class="fa fa-plus"></i> Nova Unidade
        </button>
      </div>

      <div class="unidades-page">
        <div class="stats-section">
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <div class="stat-card">
                <div class="stat-icon">
                  <i class="fa fa-building"></i>
                </div>
                <div class="stat-content">
                  <h3 class="stat-number" id="totalUnidades">0</h3>
                  <p class="stat-label">Total de Unidades</p>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="stat-card">
                <div class="stat-icon">
                  <i class="fa fa-user-md"></i>
                </div>
                <div class="stat-content">
                  <h3 class="stat-number" id="totalFarmaceuticos">0</h3>
                  <p class="stat-label">Farmacêuticos</p>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="stat-card">
                <div class="stat-icon">
                  <i class="fa fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                  <h3 class="stat-number" id="atendimentosHoje">0</h3>
                  <p class="stat-label">Atendimentos Hoje</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="unidades-grid" id="unidadesGrid">
          <?php
          $sql = "SELECT * FROM unidades ORDER BY nome ASC";
          $result = mysqli_query($conn, $sql);

          if ($result && mysqli_num_rows($result) > 0):
              while ($row = mysqli_fetch_assoc($result)):
                  $status_class = match($row['status']) {
                      'Ativa' => 'success',
                      'Inativa' => 'danger',
                      'Manutenção' => 'warning',
                      default => 'secondary'
                  };
                  ?>
                  <div class="unidade-card">
                      <div class="unidade-header">
                          <h3 class="unidade-nome"><?= htmlspecialchars($row['nome']) ?></h3>
                          <span class="badge bg-<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                      </div>
                      <div class="unidade-body">
                          <p><i class="fa fa-building me-2"></i> <strong>CNPJ:</strong> <?= htmlspecialchars($row['cnpj']) ?></p>
                          <p><i class="fa fa-phone me-2"></i> <strong>Telefone:</strong> <?= htmlspecialchars($row['telefone']) ?></p>
                          <p><i class="fa fa-map-marker-alt me-2"></i> <strong>Endereço:</strong> <?= htmlspecialchars($row['endereco']) ?></p>
                          <?php if (!empty($row['farmaceutico_responsavel'])): ?>
                              <p><i class="fa fa-user-md me-2"></i> <strong>Farm. Responsável:</strong> <?= htmlspecialchars($row['farmaceutico_responsavel']) ?> (<?= htmlspecialchars($row['crf_responsavel']) ?>)</p>
                          <?php endif; ?>
                          <?php if (!empty($row['horario_funcionamento'])): ?>
                              <p><i class="fa fa-clock me-2"></i> <strong>Horário:</strong> <?= htmlspecialchars($row['horario_funcionamento']) ?></p>
                          <?php endif; ?>
                      </div>
                      <div class="unidade-footer">
                          <button class="btn btn-sm btn-outline-primary me-1 btn-ver" data-id="<?= $row['id'] ?>">
                              <i class="fa fa-eye"></i> Ver
                          </button>
                          <button class="btn btn-sm btn-outline-warning me-1 btn-editar" data-id="<?= $row['id'] ?>">
                              <i class="fa fa-edit"></i> Editar
                          </button>
                          <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $row['id'] ?>">
                              <i class="fa fa-trash"></i> Excluir
                          </button>
                      </div>
                  </div>
                  <?php
              endwhile;
          else:
              echo '<p class="text-muted text-center py-5">Nenhuma unidade cadastrada.</p>';
          endif;
          ?>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="unidadeModal" tabindex="-1" aria-labelledby="unidadeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formUnidade" method="post" action="">
          <div class="modal-header">
            <h5 class="modal-title" id="unidadeModalLabel">Cadastrar Nova Unidade</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="unidadeId">

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome da Unidade *</label>
                <input type="text" class="form-control" name="nome" id="nomeUnidade" required>
              </div>
              <div class="col-6">
                <label class="form-label">CNPJ *</label>
                <input type="text" class="form-control" name="cnpj" id="cnpjUnidade" placeholder="00.000.000/0000-00" required>
              </div>
              <div class="col-6">
                <label class="form-label">Telefone *</label>
                <input type="text" class="form-control" name="telefone" id="telefoneUnidade" placeholder="(11) 0000-0000" required>
              </div>
              <div class="col-12">
                <label class="form-label">Endereço Completo *</label>
                <input type="text" class="form-control" name="endereco" id="enderecoUnidade" placeholder="Rua, número, bairro, cidade - UF" required>
              </div>
              <div class="col-6">
                <label class="form-label">Farmacêutico Responsável</label>
                <input type="text" class="form-control" name="farmaceutico_responsavel" id="farmaceuticoResponsavel" placeholder="Nome do farmacêutico">
              </div>
              <div class="col-6">
                <label class="form-label">CRF do Responsável</label>
                <input type="text" class="form-control" name="crf_responsavel" id="crfResponsavel" placeholder="CRF-SP 12345">
              </div>
              <div class="col-6">
                <label class="form-label">Horário de Funcionamento</label>
                <input type="text" class="form-control" name="horario_funcionamento" id="horarioFuncionamento" placeholder="08:00 - 18:00">
              </div>
              <div class="col-6">
                <label class="form-label">Status da Unidade</label>
                <select class="form-select" name="status" id="statusUnidade">
                  <option value="Ativa">Ativa</option>
                  <option value="Inativa">Inativa</option>
                  <option value="Manutenção">Em Manutenção</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" id="observacoesUnidade" rows="3" placeholder="Informações adicionais sobre a unidade"></textarea>
              </div>
            </div>
            <small class="text-muted mt-2">* Campos obrigatórios</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="salvar" class="btn btn-primary-custom">Salvar Unidade</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="detalhesUnidadeModal" tabindex="-1" aria-labelledby="detalhesUnidadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detalhesUnidadeLabel">Detalhes da Unidade</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalhesCorpoUnidade">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary-custom" id="btnEditarUnidade">
            <i class="fa fa-edit"></i> Editar
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/unidade.js"></script>
  <script src="/js/script.js"></script>
  <script src="/js/sidebar.js"></script>
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
    function loadStats() {
        fetch('?action=load_stats')
            .then(r => r.json())
            .then(data => {
                document.getElementById('totalUnidades').textContent = data.total_unidades;
                document.getElementById('totalFarmaceuticos').textContent = data.total_farmaceuticos;
                document.getElementById('atendimentosHoje').textContent = data.atendimentos_hoje;
            })
            .catch(err => console.error('Erro ao carregar estatísticas:', err));
    }
    function loadUnidades() {
        fetch('?action=load_list')
            .then(r => r.text())
            .then(html => {
                document.getElementById('unidadesGrid').innerHTML = html;
                attachEventListeners();
            })
            .catch(err => console.error('Erro ao carregar unidades:', err));
    }

    function attachEventListeners() {
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                fetch(`?action=get_unidade&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        let html = `
                            <h4>${data.nome}</h4>
                            <p><strong>CNPJ:</strong> ${data.cnpj}</p>
                            <p><strong>Telefone:</strong> ${data.telefone}</p>
                            <p><strong>Endereço:</strong> ${data.endereco}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${data.status === 'Ativa' ? 'success' : data.status === 'Inativa' ? 'danger' : 'warning'}">${data.status}</span></p>
                        `;
                        if (data.farmaceutico_responsavel) {
                            html += `<p><strong>Farmacêutico Responsável:</strong> ${data.farmaceutico_responsavel} (${data.crf_responsavel})</p>`;
                        }
                        if (data.horario_funcionamento) {
                            html += `<p><strong>Horário de Funcionamento:</strong> ${data.horario_funcionamento}</p>`;
                        }
                        if (data.observacoes) {
                            html += `<p><strong>Observações:</strong> ${data.observacoes}</p>`;
                        }
                        html += `<p class="text-muted mt-3"><small>Cadastrado em: ${new Date(data.criado_em).toLocaleString('pt-BR')}</small></p>`;

                        document.getElementById('detalhesCorpoUnidade').innerHTML = html;
                        document.getElementById('detalhesUnidadeLabel').textContent = 'Detalhes: ' + data.nome;
                        document.getElementById('btnEditarUnidade').setAttribute('data-id', data.id);
                        new bootstrap.Modal(document.getElementById('detalhesUnidadeModal')).show();
                    });
            });
        });

        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                fetch(`?action=get_unidade&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        document.getElementById('unidadeId').value = data.id;
                        document.getElementById('nomeUnidade').value = data.nome;
                        document.getElementById('cnpjUnidade').value = data.cnpj;
                        document.getElementById('telefoneUnidade').value = data.telefone;
                        document.getElementById('enderecoUnidade').value = data.endereco;
                        document.getElementById('farmaceuticoResponsavel').value = data.farmaceutico_responsavel || '';
                        document.getElementById('crfResponsavel').value = data.crf_responsavel || '';
                        document.getElementById('horarioFuncionamento').value = data.horario_funcionamento || '';
                        document.getElementById('statusUnidade').value = data.status;
                        document.getElementById('observacoesUnidade').value = data.observacoes || '';

                        document.getElementById('unidadeModalLabel').textContent = 'Editar Unidade';
                        new bootstrap.Modal(document.getElementById('unidadeModal')).show();
                    });
            });
        });
        document.querySelectorAll('.btn-excluir').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Tem certeza que deseja excluir esta unidade?')) return;
                const id = this.getAttribute('data-id');
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=excluir&id=${id}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadUnidades();
                        loadStats();
                    } else {
                        alert('Erro ao excluir: ' + data.error);
                    }
                });
            });
        });
    }

    function resetFormUnidade() {
        document.getElementById('formUnidade').reset();
        document.getElementById('unidadeId').value = '';
        document.getElementById('unidadeModalLabel').textContent = 'Cadastrar Nova Unidade';
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadTemplate("/templates/header.php", "header-container");
        loadTemplate("/templates/sidebar.php", "sidebar-container");

        if (typeof initializeSidebar === 'function') initializeSidebar();
        if (typeof initializeActionButtons === 'function') initializeActionButtons();
        if (typeof initializeTooltips === 'function') initializeTooltips();
        if (typeof initializeNavigation === 'function') initializeNavigation();
        if (typeof setActiveSidebarLink === 'function') setActiveSidebarLink();

        loadStats();
        attachEventListeners();

        document.getElementById('btnEditarUnidade').addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch(`?action=get_unidade&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('unidadeId').value = data.id;
                    document.getElementById('nomeUnidade').value = data.nome;
                    document.getElementById('cnpjUnidade').value = data.cnpj;
                    document.getElementById('telefoneUnidade').value = data.telefone;
                    document.getElementById('enderecoUnidade').value = data.endereco;
                    document.getElementById('farmaceuticoResponsavel').value = data.farmaceutico_responsavel || '';
                    document.getElementById('crfResponsavel').value = data.crf_responsavel || '';
                    document.getElementById('horarioFuncionamento').value = data.horario_funcionamento || '';
                    document.getElementById('statusUnidade').value = data.status;
                    document.getElementById('observacoesUnidade').value = data.observacoes || '';

                    document.getElementById('unidadeModalLabel').textContent = 'Editar Unidade';
                    new bootstrap.Modal(document.getElementById('unidadeModal')).show();
                    bootstrap.Modal.getInstance(document.getElementById('detalhesUnidadeModal')).hide();
                });
        });
    });
  </script>
</body>
</html>
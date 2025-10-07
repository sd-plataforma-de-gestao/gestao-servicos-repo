<?php
//include_once __DIR__ . '/../config/auth.php';

//if (!Auth::isAuthenticated()) {
    //header("Location: /portal-repo-og/templates/login.php");
    //exit();
//}
//?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

// Carrega lista via AJAX com suporte a filtros
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $where = [];
    $params = [];

    // Filtro de busca: nome ou princípio ativo
    if (!empty($_GET['search'])) {
        $search = '%' . trim($_GET['search']) . '%';
        $where[] = "(nome LIKE ? OR principio_ativo LIKE ?)";
        $params[] = $search;
        $params[] = $search;
    }

    // Filtro por status de estoque
    if (!empty($_GET['filtro'])) {
        switch ($_GET['filtro']) {
            case 'disponivel':
                $where[] = "quantidade > 10";
                break;
            case 'baixo':
                $where[] = "quantidade > 0 AND quantidade <= 10";
                break;
            case 'esgotado':
                $where[] = "quantidade = 0";
                break;
        }
    }

    $sql = "SELECT id, nome, principio_ativo, laboratorio, quantidade, data_validade, preco FROM medicamentos";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY nome ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo '<p class="text-danger">Erro ao preparar a consulta.</p>';
        exit;
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Fabricante</th><th>Estoque</th><th>Validade</th><th>Preço</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $estoque = (int)$row['quantidade'];
            $classe_estoque = $estoque > 10 ? 'success' : ($estoque > 0 ? 'warning' : 'danger');
            $status_estoque = $estoque > 10 ? 'Disponível' : ($estoque > 0 ? 'Baixo estoque' : 'Esgotado');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['principio_ativo']) . '</td>';
            echo '<td>' . htmlspecialchars($row['laboratorio']) . '</td>';
            echo '<td><span class="badge bg-' . $classe_estoque . '">' . $estoque . ' (' . $status_estoque . ')</span></td>';
            echo '<td>' . date('d/m/Y', strtotime($row['data_validade'])) . '</td>';
            echo '<td>R$ ' . number_format($row['preco'], 2, ',', '.') . '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum medicamento encontrado.</p>';
    endif;

    $stmt->close();
    exit;
}

// Bloco de cadastro — agora SEM depender de $isAjax para sair
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $principio_ativo = trim($_POST['principio_ativo'] ?? '');
    $dosagem = trim($_POST['dosagem'] ?? '');
    $laboratorio = trim($_POST['laboratorio'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $numero_lote = trim($_POST['numero_lote'] ?? '');
    $data_validade = trim($_POST['data_validade'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco = str_replace(',', '.', $_POST['preco'] ?? '0');
    $descricao = trim($_POST['descricao'] ?? '');
    $requer_receita = $_POST['requer_receita'] ?? 'Não';
    $condicao_armazenamento = $_POST['condicao_armazenamento'] ?? null;

    // Validação básica
    if (empty($nome) || empty($principio_ativo) || empty($dosagem) || empty($laboratorio) || empty($tipo) || empty($numero_lote) || empty($data_validade)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: Campos obrigatórios não preenchidos.";
        } else {
            $_SESSION['error'] = "Campos obrigatórios não preenchidos.";
            header("Location: medicamento.php");
        }
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO medicamentos (
        nome, principio_ativo, dosagem, laboratorio, tipo, numero_lote, data_validade, quantidade, preco, descricao, requer_receita, condicao_armazenamento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: falha ao preparar statement - " . $conn->error;
        } else {
            $_SESSION['error'] = "Erro interno ao preparar cadastro.";
            header("Location: medicamento.php");
        }
        exit;
    }

    $stmt->bind_param(
        "sssssssiidss",
        $nome,
        $principio_ativo,
        $dosagem,
        $laboratorio,
        $tipo,
        $numero_lote,
        $data_validade,
        $quantidade,
        $preco,
        $descricao,
        $requer_receita,
        $condicao_armazenamento
    );

    if ($stmt->execute()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "success";
        } else {
            $_SESSION['success'] = "Medicamento cadastrado com sucesso!";
            header("Location: medicamento.php");
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo "error: " . $stmt->error;
        } else {
            $_SESSION['error'] = "Erro ao salvar: " . $stmt->error;
            header("Location: medicamento.php");
        }
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
  <title>Medicamentos</title>
  <link rel="icon" href="/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/medicamento.css">
</head>
<body>
  <div id="header-container"></div>
  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>
    <div id="main-container">
      <div class="page-header">
        <h2 class="page-title">Medicamentos</h2>
        <p class="page-subtitle">Gestão completa de medicamentos e farmácia.</p>
      </div>

      <div class="medicamentos-page">
        <div class="controls-bar card mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label"><i class="fa fa-search"></i> Buscar medicamento</label>
              <input type="text" class="form-control" id="buscaMedicamento" placeholder="Nome, princípio ativo...">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label"><i class="fa fa-filter"></i> Filtro</label>
              <select class="form-select" id="filtroStatus">
                <option value="">Todos os medicamentos</option>
                <option value="disponivel">Disponível</option>
                <option value="baixo">Estoque Baixo</option>
                <option value="esgotado">Esgotado</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#medicamentoModal">
                <i class="fa fa-pills"></i> Novo Medicamento
              </button>
            </div>
          </div>
        </div>

        <div class="medicamentos-list card">
          <h2 class="list-title">Lista de Medicamentos</h2>
          <div id="lista-pacientes">
            <?php
            $sql = "SELECT id, nome, principio_ativo, laboratorio, quantidade, data_validade, preco FROM medicamentos ORDER BY nome ASC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Fabricante</th><th>Estoque</th><th>Validade</th><th>Preço</th></tr></thead><tbody>';
                while ($row = $result->fetch_assoc()):
                    $estoque = (int)$row['quantidade'];
                    $classe_estoque = $estoque > 10 ? 'success' : ($estoque > 0 ? 'warning' : 'danger');
                    $status_estoque = $estoque > 10 ? 'Disponível' : ($estoque > 0 ? 'Baixo estoque' : 'Esgotado');
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['principio_ativo']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['laboratorio']) . '</td>';
                    echo '<td><span class="badge bg-' . $classe_estoque . '">' . $estoque . ' (' . $status_estoque . ')</span></td>';
                    echo '<td>' . date('d/m/Y', strtotime($row['data_validade'])) . '</td>';
                    echo '<td>R$ ' . number_format($row['preco'], 2, ',', '.') . '</td>';
                    echo '</tr>';
                endwhile;
                echo '</tbody></table>';
            else:
                echo '<p class="text-muted mt-3">Nenhum medicamento cadastrado.</p>';
            endif;
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="medicamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formFarmaceutico" method="post">
          <div class="modal-header">
            <h5 class="modal-title">Cadastrar Novo Medicamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome do Medicamento *</label>
                <input type="text" class="form-control" name="nome" required>
              </div>
              <div class="col-6">
                <label class="form-label">Princípio Ativo *</label>
                <input type="text" class="form-control" name="principio_ativo" required>
              </div>
              <div class="col-6">
                <label class="form-label">Dosagem *</label>
                <input type="text" class="form-control" name="dosagem" placeholder="Ex: 500mg" required>
              </div>
              <div class="col-6">
                <label class="form-label">Fabricante *</label>
                <input type="text" class="form-control" name="laboratorio" required>
              </div>
              <div class="col-6">
                <label class="form-label">Tipo *</label>
                <select class="form-select" name="tipo" required>
                  <option value="Comprimido">Comprimido</option>
                  <option value="Cápsula">Cápsula</option>
                  <option value="Xarope">Xarope</option>
                  <option value="Injeção">Injeção</option>
                  <option value="Pomada">Pomada</option>
                  <option value="Gotas">Gotas</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Número do Lote *</label>
                <input type="text" class="form-control" name="numero_lote" required>
              </div>
              <div class="col-6">
                <label class="form-label">Data de Validade *</label>
                <input type="date" class="form-control" name="data_validade" required>
              </div>
              <div class="col-6">
                <label class="form-label">Quantidade em Estoque *</label>
                <input type="number" class="form-control" name="quantidade" min="0" required>
              </div>
              <div class="col-6">
                <label class="form-label">Preço Unitário *</label>
                <input type="number" class="form-control" name="preco" step="0.01" min="0" required>
              </div>
              <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="descricao" rows="3"></textarea>
              </div>
              <div class="col-6">
                <label class="form-label">Requer Receita</label>
                <select class="form-select" name="requer_receita">
                  <option value="Não">Não</option>
                  <option value="Sim">Sim</option>
                  <option value="Controlado">Controlado</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Condição de Armazenamento</label>
                <select class="form-select" name="condicao_armazenamento">
                  <option value="">Selecione...</option>
                  <option value="Temperatura Ambiente">Temperatura Ambiente</option>
                  <option value="Refrigerado">Refrigerado (2-8°C)</option>
                  <option value="Congelado">Congelado</option>
                  <option value="Local Seco">Local Seco</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom">Salvar Medicamento</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/medicamento.js"></script>

  <script>
    function loadTemplate(templatePath, containerId) {
        fetch(templatePath)
            .then(r => r.text())
            .then(html => {
                const container = document.getElementById(containerId);
                if (container) container.innerHTML = html;
            })
            .catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
        loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
    });

    // Função para carregar a lista de medicamentos com filtros
function carregarListaComFiltros() {
    const busca = document.getElementById('buscaMedicamento').value.trim();
    const filtro = document.getElementById('filtroStatus').value;

    let url = 'medicamento.php?action=load_list';
    if (busca) url += '&search=' + encodeURIComponent(busca);
    if (filtro) url += '&filtro=' + encodeURIComponent(filtro);

    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById('lista-pacientes').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('lista-pacientes').innerHTML = '<p class="text-danger">Erro ao carregar a lista.</p>';
        });
}

// Configura eventos de filtro e busca
document.addEventListener('DOMContentLoaded', function() {
    const campoBusca = document.getElementById('buscaMedicamento');
    const selectFiltro = document.getElementById('filtroStatus');

    if (campoBusca) {
        campoBusca.addEventListener('input', carregarListaComFiltros);
    }
    if (selectFiltro) {
        selectFiltro.addEventListener('change', carregarListaComFiltros);
    }

    // Carrega a lista inicial
    carregarListaComFiltros();
});
  </script>
</body>
</html>
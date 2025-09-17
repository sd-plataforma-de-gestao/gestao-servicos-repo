<?php include("../config/database.php"); ?>

<?php
// 👇 ENDPOINT PARA CARREGAR LISTA VIA AJAX
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';

    $sql = "SELECT * FROM medicamentos WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($busca)) {
        $sql .= " AND (nome LIKE ? OR principio_ativo LIKE ? OR laboratorio LIKE ?)";
        $busca_like = "%{$busca}%";
        $params = array_merge($params, [$busca_like, $busca_like, $busca_like]);
        $types .= 'sss';
    }

    if ($filtro === 'disponivel') {
        $sql .= " AND quantidade > 10";
    } elseif ($filtro === 'baixo') {
        $sql .= " AND quantidade BETWEEN 1 AND 10";
    } elseif ($filtro === 'esgotado') {
        $sql .= " AND quantidade = 0";
    }

    $sql .= " ORDER BY nome ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = mysqli_query($conn, $sql);
    }

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Estoque</th><th>Validade</th><th>Preço</th><th>Ações</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $estoque = (int)$row['quantidade'];
            $classe_estoque = $estoque > 10 ? 'text-success' : ($estoque > 0 ? 'text-warning' : 'text-danger');
            $status_estoque = $estoque > 10 ? 'Disponível' : ($estoque > 0 ? 'Baixo estoque' : 'Esgotado');

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['principio_ativo']) . '</td>';
            echo '<td class="' . $classe_estoque . '">' . $estoque . ' <small>(' . $status_estoque . ')</small></td>';
            echo '<td>' . date('d/m/Y', strtotime($row['data_validade'])) . '</td>';
            echo '<td>R$ ' . number_format($row['preco'], 2, ',', '.') . '</td>';
            echo '<td>';
            echo '<button class="btn btn-sm btn-outline-primary me-1 btn-ver" data-id="' . $row['id'] . '"><i class="fa fa-eye"></i></button>';
            echo '<button class="btn btn-sm btn-outline-warning me-1 btn-editar" data-id="' . $row['id'] . '"><i class="fa fa-edit"></i></button>';
            echo '<button class="btn btn-sm btn-outline-danger btn-excluir" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></button>';
            echo '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum medicamento encontrado.</p>';
    endif;
    exit;
}

// 👇 BLOCO DE CADASTRO/EDIÇÃO — REDIRECIONA APÓS SALVAR
if (isset($_POST['salvar'])) {
    $id = $_POST['id'] ?? null;
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
        echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Campos obrigatórios não preenchidos.</div>";
        exit;
    }

    // Sanitização
    $nome = mysqli_real_escape_string($conn, $nome);
    $principio_ativo = mysqli_real_escape_string($conn, $principio_ativo);
    $dosagem = mysqli_real_escape_string($conn, $dosagem);
    $laboratorio = mysqli_real_escape_string($conn, $laboratorio);
    $tipo = mysqli_real_escape_string($conn, $tipo);
    $numero_lote = mysqli_real_escape_string($conn, $numero_lote);
    $descricao = mysqli_real_escape_string($conn, $descricao);
    $requer_receita = mysqli_real_escape_string($conn, $requer_receita);
    $condicao_armazenamento = mysqli_real_escape_string($conn, $condicao_armazenamento);

    if ($id) {
        // Atualizar
        $sql = "UPDATE medicamentos SET 
                    nome = ?, 
                    principio_ativo = ?, 
                    dosagem = ?, 
                    laboratorio = ?, 
                    tipo = ?, 
                    numero_lote = ?, 
                    data_validade = ?, 
                    quantidade = ?, 
                    preco = ?, 
                    descricao = ?, 
                    requer_receita = ?, 
                    condicao_armazenamento = ?, 
                    atualizado_em = NOW() 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssiidsssi",
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
            $condicao_armazenamento,
            $id
        );
    } else {
        // Inserir
        $sql = "INSERT INTO medicamentos (
                    nome, 
                    principio_ativo, 
                    dosagem, 
                    laboratorio, 
                    tipo, 
                    numero_lote, 
                    data_validade, 
                    quantidade, 
                    preco, 
                    descricao, 
                    requer_receita, 
                    condicao_armazenamento
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
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
    }

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<div class='alert alert-danger mx-4 my-3' role='alert'>Erro ao salvar: " . $stmt->error . "</div>";
    }
    exit;
}

// 👇 ENDPOINT PARA CARREGAR DADOS DE UM MEDICAMENTO (modal de edição/visualização)
if (isset($_GET['action']) && $_GET['action'] === 'get_medicamento' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM medicamentos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicamento = $result->fetch_assoc();

    if ($medicamento) {
        header('Content-Type: application/json');
        echo json_encode($medicamento);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Medicamento não encontrado']);
    }
    exit;
}

// 👇 ENDPOINT PARA EXCLUIR MEDICAMENTO
if (isset($_POST['action']) && $_POST['action'] === 'excluir' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM medicamentos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
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
  <link rel="stylesheet" href="/styles/global.css">
  <link rel="stylesheet" href="/styles/sidebar.css">
  <link rel="stylesheet" href="/styles/medicamento.css">
  <link rel="stylesheet" href="/styles/header.css">
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
        <h2 class="page-title">Medicamentos</h2>
        <p class="page-subtitle">Gestão completa de medicamentos e farmácia.</p>
      </div>

      <div class="medicamentos-page">
        <!-- Barra de busca + filtro -->
        <div class="controls-bar card mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label"><i class="fa fa-search"></i> Buscar medicamento</label>
              <input
                type="text"
                class="form-control"
                id="buscaMedicamento"
                placeholder="Nome, princípio ativo ou fabricante..."
              >
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
              <button
                class="btn btn-success w-100"
                data-bs-toggle="modal"
                data-bs-target="#medicamentoModal"
                onclick="resetForm()"
              >
                <i class="fa fa-pills"></i> Novo Medicamento
              </button>
            </div>
          </div>
        </div>

        <!-- Lista de medicamentos -->
        <div class="medicamentos-list card">
          <h2 class="list-title">Lista de Medicamentos</h2>
          <div id="lista-medicamentos">
            <?php
            // Carrega lista inicial
            $sql = "SELECT * FROM medicamentos ORDER BY nome ASC LIMIT 10";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0):
                echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Estoque</th><th>Validade</th><th>Preço</th><th>Ações</th></tr></thead><tbody>';
                while ($row = mysqli_fetch_assoc($result)):
                    $estoque = (int)$row['quantidade'];
                    $classe_estoque = $estoque > 10 ? 'text-success' : ($estoque > 0 ? 'text-warning' : 'text-danger');
                    $status_estoque = $estoque > 10 ? 'Disponível' : ($estoque > 0 ? 'Baixo estoque' : 'Esgotado');

                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['principio_ativo']) . '</td>';
                    echo '<td class="' . $classe_estoque . '">' . $estoque . ' <small>(' . $status_estoque . ')</small></td>';
                    echo '<td>' . date('d/m/Y', strtotime($row['data_validade'])) . '</td>';
                    echo '<td>R$ ' . number_format($row['preco'], 2, ',', '.') . '</td>';
                    echo '<td>';
                    echo '<button class="btn btn-sm btn-outline-primary me-1 btn-ver" data-id="' . $row['id'] . '"><i class="fa fa-eye"></i></button>';
                    echo '<button class="btn btn-sm btn-outline-warning me-1 btn-editar" data-id="' . $row['id'] . '"><i class="fa fa-edit"></i></button>';
                    echo '<button class="btn btn-sm btn-outline-danger btn-excluir" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                endwhile;
                echo '</tbody></table>';
                echo '<div class="text-center mt-3"><small>Mostrando 10 primeiros registros. Use os filtros para refinar.</small></div>';
            else:
                echo '<p class="text-muted mt-3">Nenhum medicamento cadastrado.</p>';
            endif;
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Cadastrar/Editar -->
  <div class="modal fade" id="medicamentoModal" tabindex="-1" aria-labelledby="medicamentoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="formMedicamento" method="post" action="">
          <div class="modal-header">
            <h5 class="modal-title" id="medicamentoModalLabel">Cadastrar Novo Medicamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="medicamentoId">

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nome do Medicamento *</label>
                <input type="text" class="form-control" name="nome" id="nomeMedicamento" required>
              </div>
              <div class="col-6">
                <label class="form-label">Princípio Ativo *</label>
                <input type="text" class="form-control" name="principio_ativo" id="principioAtivo" required>
              </div>
              <div class="col-6">
                <label class="form-label">Dosagem *</label>
                <input type="text" class="form-control" name="dosagem" id="dosagem" placeholder="Ex: 500mg" required>
              </div>
              <div class="col-6">
                <label class="form-label">Fabricante *</label>
                <input type="text" class="form-control" name="laboratorio" id="fabricante" required>
              </div>
              <div class="col-6">
                <label class="form-label">Tipo *</label>
                <select class="form-select" name="tipo" id="tipoMedicamento" required>
                  <option value="">Selecione...</option>
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
                <input type="text" class="form-control" name="numero_lote" id="numeroLote" required>
              </div>
              <div class="col-6">
                <label class="form-label">Data de Validade *</label>
                <input type="date" class="form-control" name="data_validade" id="dataValidade" required>
              </div>
              <div class="col-6">
                <label class="form-label">Quantidade em Estoque *</label>
                <input type="number" class="form-control" name="quantidade" id="quantidadeEstoque" min="0" required>
              </div>
              <div class="col-6">
                <label class="form-label">Preço Unitário</label>
                <input type="number" class="form-control" name="preco" id="precoUnitario" step="0.01" min="0" placeholder="R$ 0,00">
              </div>
              <div class="col-12">
                <label class="form-label">Descrição/Observações</label>
                <textarea class="form-control" name="descricao" id="descricaoMedicamento" rows="3" placeholder="Informações adicionais sobre o medicamento"></textarea>
              </div>
              <div class="col-6">
                <label class="form-label">Requer Receita</label>
                <select class="form-select" name="requer_receita" id="requerReceita">
                  <option value="Não">Não</option>
                  <option value="Sim">Sim</option>
                  <option value="Controlado">Controlado</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Condição de Armazenamento</label>
                <select class="form-select" name="condicao_armazenamento" id="condicaoArmazenamento">
                  <option value="">Selecione...</option>
                  <option value="Temperatura Ambiente">Temperatura Ambiente</option>
                  <option value="Refrigerado">Refrigerado (2-8°C)</option>
                  <option value="Congelado">Congelado</option>
                  <option value="Local Seco">Local Seco</option>
                </select>
              </div>
            </div>
            <small class="text-muted mt-2">* Campos obrigatórios</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="salvar" class="btn btn-primary-custom">Salvar Medicamento</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Visualizar -->
  <div class="modal fade" id="detalhesMedicamentoModal" tabindex="-1" aria-labelledby="detalhesMedicamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detalhesMedicamentoLabel">Detalhes do Medicamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalhesCorpo">
          <!-- Conteúdo será carregado via JS -->
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/medicamento.js"></script>
  <script src="/js/sidebar.js"></script>
  <script src="/js/script.js"></script>

  <!-- Carrega Header e Sidebar via JS -->
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

    // Função para carregar lista de medicamentos via AJAX
    function loadMedicamentos() {
        const busca = document.getElementById('buscaMedicamento').value;
        const filtro = document.getElementById('filtroStatus').value;
        const url = `?action=load_list&busca=${encodeURIComponent(busca)}&filtro=${filtro}`;

        fetch(url)
            .then(r => r.text())
            .then(html => {
                document.getElementById('lista-medicamentos').innerHTML = html;
                // Re-attach event listeners
                attachEventListeners();
            })
            .catch(err => console.error('Erro ao carregar medicamentos:', err));
    }

    // Anexa eventos aos botões (ver, editar, excluir)
    function attachEventListeners() {
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                fetch(`?action=get_medicamento&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nome:</strong> ${data.nome}</p>
                                    <p><strong>Princípio Ativo:</strong> ${data.principio_ativo}</p>
                                    <p><strong>Dosagem:</strong> ${data.dosagem}</p>
                                    <p><strong>Fabricante:</strong> ${data.laboratorio}</p>
                                    <p><strong>Tipo:</strong> ${data.tipo}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Lote:</strong> ${data.numero_lote}</p>
                                    <p><strong>Validade:</strong> ${new Date(data.data_validade).toLocaleDateString('pt-BR')}</p>
                                    <p><strong>Estoque:</strong> ${data.quantidade}</p>
                                    <p><strong>Preço:</strong> R$ ${parseFloat(data.preco).toFixed(2).replace('.', ',')}</p>
                                    <p><strong>Requer Receita:</strong> ${data.requer_receita}</p>
                                </div>
                                <div class="col-12">
                                    <p><strong>Descrição:</strong> ${data.descricao || '—'}</p>
                                    <p><strong>Armazenamento:</strong> ${data.condicao_armazenamento || '—'}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('detalhesCorpo').innerHTML = html;
                        document.getElementById('detalhesMedicamentoModal').querySelector('.modal-title').textContent = 'Detalhes: ' + data.nome;
                        document.getElementById('btnEditarDoDetalhe').setAttribute('data-id', data.id);
                        new bootstrap.Modal(document.getElementById('detalhesMedicamentoModal')).show();
                    });
            });
        });

        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                fetch(`?action=get_medicamento&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        document.getElementById('medicamentoId').value = data.id;
                        document.getElementById('nomeMedicamento').value = data.nome;
                        document.getElementById('principioAtivo').value = data.principio_ativo;
                        document.getElementById('dosagem').value = data.dosagem;
                        document.getElementById('fabricante').value = data.laboratorio;
                        document.getElementById('tipoMedicamento').value = data.tipo;
                        document.getElementById('numeroLote').value = data.numero_lote;
                        document.getElementById('dataValidade').value = data.data_validade;
                        document.getElementById('quantidadeEstoque').value = data.quantidade;
                        document.getElementById('precoUnitario').value = data.preco;
                        document.getElementById('descricaoMedicamento').value = data.descricao || '';
                        document.getElementById('requerReceita').value = data.requer_receita;
                        document.getElementById('condicaoArmazenamento').value = data.condicao_armazenamento || '';

                        document.getElementById('medicamentoModalLabel').textContent = 'Editar Medicamento';
                        new bootstrap.Modal(document.getElementById('medicamentoModal')).show();
                    });
            });
        });

        document.querySelectorAll('.btn-excluir').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Tem certeza que deseja excluir este medicamento?')) return;
                const id = this.getAttribute('data-id');
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=excluir&id=${id}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadMedicamentos();
                    } else {
                        alert('Erro ao excluir: ' + data.error);
                    }
                });
            });
        });
    }

    // Reseta formulário ao abrir modal de cadastro
    function resetForm() {
        document.getElementById('formMedicamento').reset();
        document.getElementById('medicamentoId').value = '';
        document.getElementById('medicamentoModalLabel').textContent = 'Cadastrar Novo Medicamento';
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Carrega header e sidebar
        loadTemplate("/templates/header.php", "header-container");
        loadTemplate("/templates/sidebar.php", "sidebar-container");

        // Inicializa funções globais
        if (typeof initializeSidebar === 'function') initializeSidebar();
        if (typeof initializeActionButtons === 'function') initializeActionButtons();
        if (typeof initializeTooltips === 'function') initializeTooltips();
        if (typeof initializeNavigation === 'function') initializeNavigation();
        if (typeof setActiveSidebarLink === 'function') setActiveSidebarLink();

        // Eventos dos filtros
        document.getElementById('aplicar-filtros')?.addEventListener('click', loadMedicamentos);
        document.getElementById('buscaMedicamento').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') loadMedicamentos();
        });
        document.getElementById('filtroStatus').addEventListener('change', loadMedicamentos);

        // Botão editar do modal de detalhes
        document.getElementById('btnEditarDoDetalhe').addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch(`?action=get_medicamento&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('medicamentoId').value = data.id;
                    document.getElementById('nomeMedicamento').value = data.nome;
                    document.getElementById('principioAtivo').value = data.principio_ativo;
                    document.getElementById('dosagem').value = data.dosagem;
                    document.getElementById('fabricante').value = data.laboratorio;
                    document.getElementById('tipoMedicamento').value = data.tipo;
                    document.getElementById('numeroLote').value = data.numero_lote;
                    document.getElementById('dataValidade').value = data.data_validade;
                    document.getElementById('quantidadeEstoque').value = data.quantidade;
                    document.getElementById('precoUnitario').value = data.preco;
                    document.getElementById('descricaoMedicamento').value = data.descricao || '';
                    document.getElementById('requerReceita').value = data.requer_receita;
                    document.getElementById('condicaoArmazenamento').value = data.condicao_armazenamento || '';

                    document.getElementById('medicamentoModalLabel').textContent = 'Editar Medicamento';
                    new bootstrap.Modal(document.getElementById('medicamentoModal')).show();
                    bootstrap.Modal.getInstance(document.getElementById('detalhesMedicamentoModal')).hide();
                });
        });

        // Carrega lista inicial
        attachEventListeners();
    });
  </script>
</body>
</html>
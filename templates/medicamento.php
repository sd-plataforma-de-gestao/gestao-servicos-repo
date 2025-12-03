<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');
error_reporting(E_ALL);
require_once("../config/database.php");

if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, nome, principio_ativo, dosagem, laboratorio, tipo, numero_lote, data_validade, quantidade, preco, descricao, requer_receita, condicao_armazenamento FROM medicamentos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $medicamento = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($medicamento);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Medicamento não encontrado.']);
    }
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $principio_ativo = trim($_POST['principio_ativo'] ?? '');
    $dosagem = trim($_POST['dosagem'] ?? '');
    $laboratorio = trim($_POST['laboratorio'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $numero_lote = trim($_POST['numero_lote'] ?? '');
    $data_validade = trim($_POST['data_validade'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco_limpo = str_replace(['R$', ' ', '.'], '', $_POST['preco'] ?? '0');
    $preco = (double)str_replace(',', '.', $preco_limpo);
    $descricao = trim($_POST['descricao'] ?? '');
    $requer_receita = $_POST['requer_receita'] ?? 'Não';
    $condicao_armazenamento = $_POST['condicao_armazenamento'] ?? null;

    if ($id <= 0 || empty($nome) || empty($principio_ativo) || empty($dosagem) || empty($laboratorio) || empty($tipo) || empty($numero_lote) || empty($data_validade)) {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: ID inválido ou campos obrigatórios não preenchidos.";
        } else {
            header("Location: medicamento.php?error=ID inválido ou campos obrigatórios não preenchidos.");
        }
        exit;
    }

    $stmt = $conn->prepare("UPDATE medicamentos SET nome=?, principio_ativo=?, dosagem=?, laboratorio=?, tipo=?, numero_lote=?, data_validade=?, quantidade=?, preco=?, descricao=?, requer_receita=?, condicao_armazenamento=? WHERE id=?");
    if (!$stmt) {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: falha ao preparar statement - " . $conn->error;
        } else {
            header("Location: medicamento.php?error=Erro interno ao preparar atualização.");
        }
        exit;
    }

    $stmt->bind_param("sssssssiidssi", $nome, $principio_ativo, $dosagem, $laboratorio, $tipo, $numero_lote, $data_validade, $quantidade, $preco, $descricao, $requer_receita, $condicao_armazenamento, $id);

    if ($stmt->execute()) {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "success_edit";
        } else {
            header("Location: medicamento.php?success=Medicamento atualizado com sucesso!");
        }
    } else {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: " . $stmt->error;
        } else {
            header("Location: medicamento.php?error=Erro ao atualizar: " . $stmt->error);
        }
    }

    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    if ($id <= 0) {
        echo "error: ID inválido.";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM medicamentos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "success_delete";
        } else {
            echo "error: Nenhum medicamento foi excluído (ID pode não existir).";
        }
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $where = [];
    $params = [];
    if (!empty($_GET['search'])) {
        $search = '%' . trim($_GET['search']) . '%';
        $where[] = "(nome LIKE ? OR principio_ativo LIKE ?)";
        $params[] = $search;
        $params[] = $search;
    }
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
        header('Content-Type: text/plain');
        echo '<p class="text-danger">Erro ao preparar a consulta: ' . $conn->error . '</p>';
        exit;
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Fabricante</th><th>Estoque</th><th>Validade</th><th>Preço</th><th class="th-acoes">Ações</th></tr></thead><tbody>';
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
            echo '<td class="td-acoes">';
            echo '<div class="dropdown">';
            echo '<button class="btn btn-acoes dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
            echo '<i class="fas fa-ellipsis-v"></i>';
            echo '</button>';
            echo '<ul class="dropdown-menu">';
            echo '<li><button class="dropdown-item btn-editar" data-id="' . $row['id'] . '"><i class="fas fa-edit me-2"></i>Editar</button></li>';
            echo '<li><button class="dropdown-item btn-excluir" data-id="' . $row['id'] . '" data-nome="' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '"><i class="fas fa-trash me-2"></i>Excluir</button></li>';
            echo '</ul>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum medicamento encontrado.</p>';
    endif;
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $nome = trim($_POST['nome'] ?? '');
    $principio_ativo = trim($_POST['principio_ativo'] ?? '');
    $dosagem = trim($_POST['dosagem'] ?? '');
    $laboratorio = trim($_POST['laboratorio'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $numero_lote = trim($_POST['numero_lote'] ?? '');
    $data_validade = trim($_POST['data_validade'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco_limpo = str_replace(['R$', ' ', '.'], '', $_POST['preco'] ?? '0');
    $preco = (double)str_replace(',', '.', $preco_limpo);
    $descricao = trim($_POST['descricao'] ?? '');
    $requer_receita = $_POST['requer_receita'] ?? 'Não';
    $condicao_armazenamento = $_POST['condicao_armazenamento'] ?? null;

    if (empty($nome) || empty($principio_ativo) || empty($dosagem) || empty($laboratorio) || empty($tipo) || empty($numero_lote) || empty($data_validade)) {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: Campos obrigatórios não preenchidos.";
        } else {
            header("Location: medicamento.php?error=Campos obrigatórios não preenchidos.");
        }
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO medicamentos (
        nome, principio_ativo, dosagem, laboratorio, tipo, numero_lote, data_validade, quantidade, preco, descricao, requer_receita, condicao_armazenamento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: falha ao preparar statement - " . $conn->error;
        } else {
            header("Location: medicamento.php?error=Erro interno ao preparar cadastro.");
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
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "success";
        } else {
            header("Location: medicamento.php?success=Medicamento cadastrado com sucesso!");
        }
    } else {
        if ($isAjax) {
            header('Content-Type: text/plain');
            echo "error: " . $stmt->error;
        } else {
            header("Location: medicamento.php?error=Erro ao salvar: " . $stmt->error);
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
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css " rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css " />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css ">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11 "></script>
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
                            echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Medicamento</th><th>Princípio Ativo</th><th>Fabricante</th><th>Estoque</th><th>Validade</th><th>Preço</th><th class="th-acoes">Ações</th></tr></thead><tbody>';
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
                                echo '<td class="td-acoes">';
                                echo '<div class="dropdown">';
                                echo '<button class="btn btn-acoes dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
                                echo '<i class="fas fa-ellipsis-v"></i>';
                                echo '</button>';
                                echo '<ul class="dropdown-menu">';
                                echo '<li><button class="dropdown-item btn-editar" data-id="' . $row['id'] . '"><i class="fas fa-edit me-2"></i>Editar</button></li>';
                                echo '<li><button class="dropdown-item btn-excluir" data-id="' . $row['id'] . '" data-nome="' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '"><i class="fas fa-trash me-2"></i>Excluir</button></li>';
                                echo '</ul>';
                                echo '</div>';
                                echo '</td>';
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
                <form id="formMedicamento" method="post">
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
                                <input type="text" class="form-control" name="preco" required>
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

    <div class="modal fade" id="editarMedicamentoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="formEditarMedicamento" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id" required>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome do Medicamento *</label>
                                <input type="text" class="form-control" id="edit_nome" name="nome" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Princípio Ativo *</label>
                                <input type="text" class="form-control" id="edit_principio_ativo" name="principio_ativo" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Dosagem *</label>
                                <input type="text" class="form-control" id="edit_dosagem" name="dosagem" placeholder="Ex: 500mg" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fabricante *</label>
                                <input type="text" class="form-control" id="edit_laboratorio" name="laboratorio" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" id="edit_tipo" name="tipo" required>
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
                                <input type="text" class="form-control" id="edit_numero_lote" name="numero_lote" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Data de Validade *</label>
                                <input type="date" class="form-control" id="edit_data_validade" name="data_validade" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Quantidade em Estoque *</label>
                                <input type="number" class="form-control" id="edit_quantidade" name="quantidade" min="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Preço Unitário *</label>
                                <input type="text" class="form-control" id="edit_preco" name="preco" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Requer Receita</label>
                                <select class="form-select" id="edit_requer_receita" name="requer_receita">
                                    <option value="Não">Não</option>
                                    <option value="Sim">Sim</option>
                                    <option value="Controlado">Controlado</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Condição de Armazenamento</label>
                                <select class="form-select" id="edit_condicao_armazenamento" name="condicao_armazenamento">
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
                        <button type="submit" class="btn btn-primary-custom">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="excluirMedicamentoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir o medicamento <strong id="nomeMedicamentoExcluir"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarExclusaoBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js "></script>
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

        document.addEventListener('DOMContentLoaded', function() {
            loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
            loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');

            const campoBusca = document.getElementById('buscaMedicamento');
            const selectFiltro = document.getElementById('filtroStatus');
            const formMedicamento = document.getElementById("formMedicamento");
            const medicamentoModalElement = document.getElementById("medicamentoModal");

            if (campoBusca) {
                campoBusca.addEventListener('input', carregarListaComFiltros);
            }
            if (selectFiltro) {
                selectFiltro.addEventListener('change', carregarListaComFiltros);
            }

            carregarListaComFiltros();

            if (formMedicamento) {
                formMedicamento.addEventListener("submit", function(e) {
                    e.preventDefault();
                    const btn = formMedicamento.querySelector('[type="submit"]');
                    if (!btn || btn.disabled) return;
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
                    const formData = new FormData(formMedicamento);
                    fetch('medicamento.php', {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        const message = result.trim();
                        const modal = bootstrap.Modal.getInstance(medicamentoModalElement);
                        if (message === "success") {
                            if (modal) modal.hide();
                            formMedicamento.reset();
                            carregarListaComFiltros();
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Medicamento cadastrado com sucesso!',
                                confirmButtonColor: '#1C5B40'
                            });
                        } else {
                            let errorMessage = message.startsWith('error: ') ? message.replace('error: ', '') : 'Erro desconhecido ao cadastrar. Verifique o console.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro no Cadastro!',
                                text: errorMessage,
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro de requisição:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de Conexão!',
                            text: 'Erro de conexão ao cadastrar medicamento.',
                            confirmButtonColor: '#DC3545'
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                });
            }

            function atualizarListaMedicamentos() {
                carregarListaComFiltros();
            }

            const formEditarMedicamento = document.getElementById("formEditarMedicamento");
            const editarMedicamentoModalElement = document.getElementById("editarMedicamentoModal");

            if (formEditarMedicamento) {
                formEditarMedicamento.addEventListener("submit", function(e) {
                    e.preventDefault();
                    const btn = formEditarMedicamento.querySelector('[type="submit"]');
                    if (!btn || btn.disabled) return;
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
                    const formData = new FormData(formEditarMedicamento);
                    formData.append('action', 'edit');

                    fetch('medicamento.php', {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        const message = result.trim();
                        const modal = bootstrap.Modal.getInstance(editarMedicamentoModalElement);
                        if (message === "success_edit") {
                            if (modal) modal.hide();
                            atualizarListaMedicamentos();
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Medicamento atualizado com sucesso!',
                                confirmButtonColor: '#1C5B40'
                            });
                        } else {
                            let errorMessage = message.startsWith('error: ') ? message.replace('error: ', '') : 'Erro desconhecido ao atualizar. Verifique o console.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro na Atualização!',
                                text: errorMessage,
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro de requisição:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de Conexão!',
                            text: 'Erro de conexão ao atualizar medicamento.',
                            confirmButtonColor: '#DC3545'
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                });
            }

            function carregarDadosEdicao(id) {
                fetch(`medicamento.php?action=get&id=${encodeURIComponent(id)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: data.error,
                                confirmButtonColor: '#DC3545'
                            });
                            return;
                        }
                        document.getElementById('edit_id').value = data.id;
                        document.getElementById('edit_nome').value = data.nome;
                        document.getElementById('edit_principio_ativo').value = data.principio_ativo;
                        document.getElementById('edit_dosagem').value = data.dosagem;
                        document.getElementById('edit_laboratorio').value = data.laboratorio;
                        document.getElementById('edit_tipo').value = data.tipo;
                        document.getElementById('edit_numero_lote').value = data.numero_lote;
                        document.getElementById('edit_data_validade').value = data.data_validade;
                        document.getElementById('edit_quantidade').value = data.quantidade;
                        document.getElementById('edit_preco').value = data.preco.toFixed(2).replace('.', ',');
                        document.getElementById('edit_descricao').value = data.descricao;
                        document.getElementById('edit_requer_receita').value = data.requer_receita;
                        document.getElementById('edit_condicao_armazenamento').value = data.condicao_armazenamento;

                        const modal = new bootstrap.Modal(editarMedicamentoModalElement);
                        modal.show();
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao carregar dados do medicamento para edição.',
                            confirmButtonColor: '#DC3545'
                        });
                    });
            }

            let medicamentoParaExcluirId = null;
            const excluirMedicamentoModalElement = document.getElementById("excluirMedicamentoModal");

            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-editar')) {
                    const btn = e.target.closest('.btn-editar');
                    const id = btn.getAttribute('data-id');
                    if (id) {
                        carregarDadosEdicao(id);
                    }
                }
                if (e.target.closest('.btn-excluir')) {
                    const btn = e.target.closest('.btn-excluir');
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    if (id && nome) {
                        medicamentoParaExcluirId = id;
                        document.getElementById('nomeMedicamentoExcluir').textContent = nome;
                        const modal = new bootstrap.Modal(excluirMedicamentoModalElement);
                        modal.show();
                    }
                }
            });

            document.getElementById('confirmarExclusaoBtn').addEventListener('click', function() {
                if (medicamentoParaExcluirId) {
                    fetch('medicamento.php', {
                        method: 'POST',
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `action=delete&id=${encodeURIComponent(medicamentoParaExcluirId)}`
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.trim() === "success_delete") {
                            const modal = bootstrap.Modal.getInstance(excluirMedicamentoModalElement);
                            if (modal) modal.hide();
                            atualizarListaMedicamentos();
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Medicamento excluído com sucesso!',
                                confirmButtonColor: '#1C5B40'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro na Exclusão!',
                                text: "Erro ao excluir medicamento: " + result.replace("error: ", ""),
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de Conexão!',
                            text: 'Erro de conexão ao excluir medicamento.',
                            confirmButtonColor: '#DC3545'
                        });
                    });
                }
            });
        });

        function attachMenuToggle() {
          const btn = document.getElementById('menu-toggle');
          const sidebar = document.getElementById('sidebar');
          if (btn && sidebar) {
            btn.onclick = null;
            btn.onclick = () => {
              sidebar.classList.toggle('collapsed');
            };
          } else {
            setTimeout(attachMenuToggle, 300);
          }
        }

        document.addEventListener('DOMContentLoaded', () => {
          attachMenuToggle();
        });
    </script>
</body>
</html>
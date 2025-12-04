<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/../config/database.php');

function limparCRF($crf)
{
    return preg_replace('/[^A-Za-z0-9]/', '', $crf);
}

function validarCRF($crf)
{
    $crf = limparCRF($crf);
    if (strlen($crf) < 3 || strlen($crf) > 8) return false;
    if (!ctype_alpha(substr($crf, 0, 2))) return false;
    if (!ctype_digit(substr($crf, 2))) return false;
    return true;
}

function formatarCRF($crf)
{
    return strtoupper(limparCRF($crf));
}

function getStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'ativo':
            return 'status-ativo';
        case 'inativo':
            return 'status-inativo';
        default:
            return 'status-padrao';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, nome, email, crf, telefone, status FROM farmaceuticos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $farmaceutico = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($farmaceutico);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Farmacêutico não encontrado.']);
    }
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $email = $_POST['email'] ?? '';
    $crf = $_POST['crf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $status = $_POST['status'] ?? 'ativo';
    $senha = $_POST['senha'] ?? '';

    if ($id <= 0 || empty($nome) || empty($email) || empty($crf)) {
        if ($isAjax) {
            echo "error: ID inválido ou campos obrigatórios não preenchidos.";
        } else {
            header("Location: farmaceutico.php?error=ID inválido ou campos obrigatórios não preenchidos.");
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            echo "error: E-mail inválido.";
        } else {
            header("Location: farmaceutico.php?error=E-mail inválido.");
        }
        exit;
    }

    if (!validarCRF($crf)) {
        if ($isAjax) {
            echo "error: CRF inválido. Formato esperado: Ex: SP12345.";
        } else {
            header("Location: farmaceutico.php?error=CRF inválido. Formato esperado: Ex: SP12345.");
        }
        exit;
    }

    $crfParaSalvar = formatarCRF($crf);

    if (!empty($senha)) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE farmaceuticos SET nome=?, email=?, crf=?, telefone=?, senha=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nome, $email, $crfParaSalvar, $telefone, $senhaHash, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE farmaceuticos SET nome=?, email=?, crf=?, telefone=?, status=? WHERE id=?");
        $stmt->bind_param("sssssi", $nome, $email, $crfParaSalvar, $telefone, $status, $id);
    }

    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            header("Location: farmaceutico.php?error=Erro interno no banco de dados.");
        }
        exit;
    }

    if ($stmt->execute()) {
        if ($isAjax) {
            echo "success_edit";
        } else {
            header("Location: farmaceutico.php?success=Farmacêutico atualizado com sucesso!");
        }
    } else {
        if ($isAjax) {
            echo "error: " . $stmt->error;
        } else {
            header("Location: farmaceutico.php?error=Erro ao atualizar farmacêutico.");
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

    $stmt = $conn->prepare("DELETE FROM farmaceuticos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "success_delete";
        } else {
            echo "error: Nenhum farmacêutico foi excluído (ID pode não existir).";
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
        $where[] = "(nome LIKE ? OR crf LIKE ? OR telefone LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    if (!empty($_GET['status']) && in_array($_GET['status'], ['ativo', 'inativo'])) {
        $where[] = "status = ?";
        $params[] = $_GET['status'];
    }
    $sql = "SELECT id, nome, email, crf, telefone, status FROM farmaceuticos";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY nome ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo '<p class="text-danger">Erro na consulta.</p>';
        exit;
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th><th class="th-acoes">Ações</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $statusBadgeClass = getStatusBadgeClass($row['status']);
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><span class="badge ' . $statusBadgeClass . '">' . ucfirst($row['status']) . '</span></td>';
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
        echo '<p class="text-muted mt-3">Nenhum farmacêutico encontrado.</p>';
    endif;
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $nome = trim($_POST['nome'] ?? '');
    $email = $_POST['email'] ?? '';
    $crf = $_POST['crf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $status = $_POST['status'] ?? 'ativo';
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($email) || empty($crf) || empty($senha)) {
        if ($isAjax) {
            echo "error: Campos obrigatórios (nome, e-mail, CRF e senha) não preenchidos.";
        } else {
            header("Location: farmaceutico.php?error=Campos obrigatórios não preenchidos.");
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            echo "error: E-mail inválido.";
        } else {
            header("Location: farmaceutico.php?error=E-mail inválido.");
        }
        exit;
    }

    if (!validarCRF($crf)) {
        if ($isAjax) {
            echo "error: CRF inválido. Formato esperado: Ex: SP12345.";
        } else {
            header("Location: farmaceutico.php?error=CRF inválido. Formato esperado: Ex: SP12345.");
        }
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $crfParaSalvar = formatarCRF($crf);

    $stmt = $conn->prepare("INSERT INTO farmaceuticos (nome, email, crf, telefone, senha, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            header("Location: farmaceutico.php?error=Erro interno no banco de dados.");
        }
        exit;
    }

    $stmt->bind_param("ssssss", $nome, $email, $crfParaSalvar, $telefone, $senhaHash, $status);

    if ($stmt->execute()) {
        if ($isAjax) {
            echo "success";
        } else {
            header("Location: farmaceutico.php?success=Farmacêutico cadastrado com sucesso!");
        }
    } else {
        if ($isAjax) {
            echo "error: " . $stmt->error;
        } else {
            header("Location: farmaceutico.php?error=Erro ao salvar farmacêutico.");
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
    <title>Farmacêuticos</title>
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css  " rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css  " />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css  ">
    <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/farmaceutico.css">
</head>
<body>
    <div id="header-container"></div>
    <div id="main-content-wrapper">
        <div id="sidebar-container"></div>
        <div id="main-container">
            <div class="page-header">
                <h2 class="page-title">Farmacêuticos</h2>
                <p class="page-subtitle">Gestão de farmacêuticos e CRFs.</p>
            </div>
            <div class="farmaceuticos-page">
                <div class="controls-bar card mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fa fa-search"></i> Buscar farmacêutico</label>
                            <input type="text" class="form-control" id="buscaFarmaceutico" placeholder="Nome, CRF ou telefone...">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label"><i class="fa fa-filter"></i> Status</label>
                            <select class="form-select" id="filtroStatus">
                                <option value="">Todos</option>
                                <option value="ativo">Ativos</option>
                                <option value="inativo">Inativos</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#farmaceuticoModal">
                                <i class="fa fa-user-plus"></i> Novo Farmacêutico
                            </button>
                        </div>
                    </div>
                </div>
                <div class="farmaceuticos-list card">
                    <h2 class="list-title">Lista de Farmacêuticos</h2>
                    <div id="lista-farmaceuticos">
                        <?php
                        $sql = "SELECT id, nome, email, crf, telefone, status FROM farmaceuticos ORDER BY nome ASC";
                        $result = mysqli_query($conn, $sql);
                        if ($result && mysqli_num_rows($result) > 0):
                            echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th><th class="th-acoes">Ações</th></tr></thead><tbody>';
                            while ($row = mysqli_fetch_assoc($result)):
                                $statusBadgeClass = getStatusBadgeClass($row['status']);
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td><span class="badge ' . $statusBadgeClass . '">' . ucfirst($row['status']) . '</span></td>';
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
                            echo '<p class="text-muted mt-3">Nenhum farmacêutico cadastrado.</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="farmaceuticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="formFarmaceutico" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Novo Farmacêutico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">E-mail *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CRF *</label>
                                <input type="text" class="form-control" name="crf" id="crf" placeholder="SP12345" maxlength="8" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Senha *</label>
                                <input type="password" class="form-control" name="senha" required minlength="6">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="telefone" id="telefone" placeholder="(00) 00000-0000" maxlength="15">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="ativo" selected>Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Cadastrar Farmacêutico</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarFarmaceuticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="formEditarFarmaceutico" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Farmacêutico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id" required>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="edit_nome" name="nome" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">E-mail *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CRF *</label>
                                <input type="text" class="form-control" id="edit_crf" name="crf" placeholder="SP12345" maxlength="8" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="edit_senha" name="senha" placeholder="Deixe em branco para não alterar" minlength="6">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="edit_telefone" name="telefone" placeholder="(00) 00000-0000" maxlength="15">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
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

    <div class="modal fade" id="excluirFarmaceuticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir o farmacêutico <strong id="nomeFarmaceuticoExcluir"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarExclusaoBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js  "></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11  "></script>
    <script src="/portal-repo-og/js/script.js"></script>
    <script>
        function loadTemplate(templatePath, containerId) {
            const fullPath = '/portal-repo-og' + templatePath;
            fetch(fullPath)
                .then(r => {
                    if (!r.ok) throw new Error('Erro ao carregar template');
                    return r.text();
                })
                .then(html => {
                    const container = document.getElementById(containerId);
                    if (container) container.innerHTML = html;
                    
                    if (containerId === 'sidebar-container' && typeof setActiveSidebarLink === 'function') {
                        setTimeout(() => setActiveSidebarLink(), 50);
                    }
                })
                .catch(err => {
                    console.error('Erro ao carregar:', fullPath, err);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadTemplate('/templates/header.php', 'header-container');
            loadTemplate('/templates/sidebar.php', 'sidebar-container');

            const busca = document.getElementById('buscaFarmaceutico');
            const filtro = document.getElementById('filtroStatus');
            const lista = document.getElementById('lista-farmaceuticos');

            function atualizarLista() {
                const url = `farmaceutico.php?action=load_list&search=${encodeURIComponent(busca.value.trim())}&status=${encodeURIComponent(filtro.value)}`;
                fetch(url).then(r => r.text()).then(html => {
                    lista.innerHTML = html;
                });
            }

            if (busca) busca.addEventListener('input', atualizarLista);
            if (filtro) filtro.addEventListener('change', atualizarLista);
            if (lista) atualizarLista();

            const formFarmaceutico = document.getElementById("formFarmaceutico");
            const farmaceuticoModalElement = document.getElementById("farmaceuticoModal");

            if (formFarmaceutico) {
                formFarmaceutico.addEventListener("submit", function(e) {
                    e.preventDefault();
                    const btn = formFarmaceutico.querySelector('[type="submit"]');
                    if (!btn || btn.disabled) return;
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
                    const formData = new FormData(formFarmaceutico);
                    fetch('farmaceutico.php', {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then(response => response.text())
                        .then(result => {
                            if (result.trim() === "success") {
                                const modal = bootstrap.Modal.getInstance(farmaceuticoModalElement);
                                if (modal) modal.hide();
                                formFarmaceutico.reset();
                                atualizarLista();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Farmacêutico cadastrado com sucesso!',
                                    confirmButtonColor: '#1C5B40'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Ops...',
                                    text: "Erro: " + result.replace("error: ", ""),
                                    confirmButtonColor: '#DC3545'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro de Conexão!',
                                text: 'Erro de conexão ao cadastrar farmacêutico.',
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
                fetch(`farmaceutico.php?action=get&id=${encodeURIComponent(id)}`)
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
                        document.getElementById('edit_email').value = data.email;
                        document.getElementById('edit_crf').value = data.crf;
                        document.getElementById('edit_telefone').value = data.telefone;
                        document.getElementById('edit_status').value = data.status;
                        const modal = new bootstrap.Modal(document.getElementById("editarFarmaceuticoModal"));
                        modal.show();
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao carregar dados do farmacêutico para edição.',
                            confirmButtonColor: '#DC3545'
                        });
                    });
            }

            const formEditarFarmaceutico = document.getElementById("formEditarFarmaceutico");
            const editarFarmaceuticoModalElement = document.getElementById("editarFarmaceuticoModal");

            if (formEditarFarmaceutico) {
                formEditarFarmaceutico.addEventListener("submit", function(e) {
                    e.preventDefault();
                    const btn = formEditarFarmaceutico.querySelector('[type="submit"]');
                    if (!btn || btn.disabled) return;
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
                    const formData = new FormData(formEditarFarmaceutico);
                    formData.append('action', 'edit');

                    fetch('farmaceutico.php', {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then(response => response.text())
                        .then(result => {
                            const message = result.trim();
                            const modal = bootstrap.Modal.getInstance(editarFarmaceuticoModalElement);
                            if (message === "success_edit") {
                                if (modal) modal.hide();
                                atualizarLista();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Farmacêutico atualizado com sucesso!',
                                    confirmButtonColor: '#1C5B40'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Ops...',
                                    text: "Erro: " + result.replace("error: ", ""),
                                    confirmButtonColor: '#DC3545'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro de Conexão!',
                                text: 'Erro de conexão ao atualizar farmacêutico.',
                                confirmButtonColor: '#DC3545'
                            });
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                });
            }

            let farmaceuticoParaExcluirId = null;
            const excluirFarmaceuticoModalElement = document.getElementById("excluirFarmaceuticoModal");

            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-editar')) {
                    const id = e.target.closest('.btn-editar').dataset.id;
                    if (id) {
                        carregarDadosEdicao(id);
                    }
                }
                if (e.target.closest('.btn-excluir')) {
                    const btn = e.target.closest('.btn-excluir');
                    const id = btn.dataset.id;
                    const nome = btn.dataset.nome;
                    if (id && nome) {
                        farmaceuticoParaExcluirId = id;
                        document.getElementById('nomeFarmaceuticoExcluir').textContent = nome;
                        const modal = new bootstrap.Modal(excluirFarmaceuticoModalElement);
                        modal.show();
                    }
                }
            });

            document.getElementById('confirmarExclusaoBtn').addEventListener('click', function() {
                if (farmaceuticoParaExcluirId) {
                    fetch('farmaceutico.php', {
                            method: 'POST',
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: `action=delete&id=${encodeURIComponent(farmaceuticoParaExcluirId)}`
                        })
                        .then(response => response.text())
                        .then(result => {
                            if (result.trim() === "success_delete") {
                                const modal = bootstrap.Modal.getInstance(excluirFarmaceuticoModalElement);
                                if (modal) modal.hide();
                                atualizarLista();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Farmacêutico excluído com sucesso!',
                                    confirmButtonColor: '#1C5B40'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Ops...',
                                    text: "Erro: " + result.replace("error: ", ""),
                                    confirmButtonColor: '#DC3545'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro de Conexão!',
                                text: 'Erro de conexão ao excluir farmacêutico.',
                                confirmButtonColor: '#DC3545'
                            });
                        });
                }
            });

            function attachMenuToggle() {
                const btn = document.getElementById('menu-toggle');
                const sidebar = document.getElementById('sidebar-container');
                if (btn && sidebar) {
                    btn.onclick = () => sidebar.classList.toggle('collapsed');
                } else {
                    setTimeout(attachMenuToggle, 300);
                }
            }
            attachMenuToggle();
        });
    </script>
</body>
</html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

// Função para limpar CRF (mantém só letras e números)
function limparCRF($crf) {
    return preg_replace('/[^A-Za-z0-9]/', '', $crf);
}

// Função para validar CRF: 2 letras + até 6 dígitos (total: 3 a 8 caracteres)
function validarCRF($crf) {
    $crf = limparCRF($crf);
    if (strlen($crf) < 3 || strlen($crf) > 8) return false;
    if (!ctype_alpha(substr($crf, 0, 2))) return false;
    if (!ctype_digit(substr($crf, 2))) return false;
    return true;
}

// Função para formatar CRF: converte para maiúsculo
function formatarCRF($crf) {
    return strtoupper(limparCRF($crf));
}

if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $sql = "SELECT id, nome, email, crf, telefone, status FROM farmaceuticos ORDER BY nome ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf'])) : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
            echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum farmacêutico cadastrado.</p>';
    endif;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $nome = trim($_POST['nome'] ?? '');
    $email = $_POST['email'] ?? '';
    $crf = $_POST['crf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $status = $_POST['status'] ?? 'ativo';

    if (empty($nome) || empty($email)) {
        if ($isAjax) {
            echo "error: Campos obrigatórios (nome e e-mail) não preenchidos.";
        } else {
            $_SESSION['error'] = "Campos obrigatórios não preenchidos.";
            header("Location: farmaceutico.php");
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            echo "error: E-mail inválido.";
        } else {
            $_SESSION['error'] = "E-mail inválido.";
            header("Location: farmaceutico.php");
        }
        exit;
    }

    // Validação do CRF (se fornecido)
    $crfParaSalvar = null;
    if (!empty($crf)) {
        if (!validarCRF($crf)) {
            if ($isAjax) {
                echo "error: CRF inválido. Formato esperado: SP12345.";
            } else {
                $_SESSION['error'] = "CRF inválido. Formato esperado: SP12345.";
                header("Location: farmaceutico.php");
            }
            exit;
        }
        $crfParaSalvar = formatarCRF($crf);
    }

    $stmt = $conn->prepare("INSERT INTO farmaceuticos (nome, email, crf, telefone, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            $_SESSION['error'] = "Erro interno.";
            header("Location: farmaceutico.php");
        }
        exit;
    }

    $stmt->bind_param("sssss", $nome, $email, $crfParaSalvar, $telefone, $status);

    if ($stmt->execute()) {
        if ($isAjax) {
            echo "success";
        } else {
            $_SESSION['success'] = "Farmacêutico cadastrado com sucesso!";
            header("Location: farmaceutico.php");
        }
    } else {
        if ($isAjax) {
            echo "error: " . $stmt->error;
        } else {
            $_SESSION['error'] = "Erro ao salvar.";
            header("Location: farmaceutico.php");
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
    <link rel="icon" href="/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
                            echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th></tr></thead><tbody>';
                            while ($row = mysqli_fetch_assoc($result)):
                                $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf'])) : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-') . '</td>';
                                echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
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
                                <input type="text" class="form-control" name="crf" placeholder="SP12345" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="telefone" placeholder="(00) 00000-0000">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal-repo-og/js/script.js"></script>
    <script src="/portal-repo-og/js/farmaceutico.js"></script>

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
    </script>
</body>
</html>
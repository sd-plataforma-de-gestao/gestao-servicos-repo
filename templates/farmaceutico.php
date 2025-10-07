<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

/**
 * Remove todos os caracteres não alfanuméricos de uma string.
 */
function limparCRF($crf)
{
    return preg_replace('/[^A-Za-z0-9]/', '', $crf);
}

/**
 * Valida CRF: 2 letras no início + 1 a 6 dígitos (total: 3 a 8 caracteres).
 */
function validarCRF($crf)
{
    $crf = limparCRF($crf);
    if (strlen($crf) < 3 || strlen($crf) > 8) return false;
    if (!ctype_alpha(substr($crf, 0, 2))) return false;
    if (!ctype_digit(substr($crf, 2))) return false;
    return true;
}

/**
 * Formata CRF: converte para maiúsculo e remove caracteres inválidos.
 */
function formatarCRF($crf)
{
    return strtoupper(limparCRF($crf));
}


// Carrega lista via AJAX com suporte a filtros
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
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
    else:
        echo '<p class="text-muted mt-3">Nenhum farmacêutico encontrado.</p>';
    endif;

    $stmt->close();
    exit;
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $nome = trim($_POST['nome'] ?? '');
    $email = $_POST['email'] ?? '';
    $crf = $_POST['crf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $status = $_POST['status'] ?? 'ativo';

    if (empty($nome) || empty($email) || empty($crf)) {
        if ($isAjax) {
            echo "error: Campos obrigatórios (nome, e-mail e CRF) não preenchidos.";
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

    $crfParaSalvar = formatarCRF($crf);

    $stmt = $conn->prepare("INSERT INTO farmaceuticos (nome, email, crf, telefone, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            header("Location: farmaceutico.php?error=Erro interno no banco de dados.");
        }
        exit;
    }

    $stmt->bind_param("sssss", $nome, $email, $crfParaSalvar, $telefone, $status);

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
                                echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
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
                                <input type="text" class="form-control" name="crf" id="crf" placeholder="SP12345" maxlength="8" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal-repo-og/js/script.js"></script>
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

        // === FILTROS DINÂMICOS (não interfere nas máscaras) ===
        document.addEventListener('DOMContentLoaded', function() {
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
            if (lista) atualizarLista(); // carrega inicial
        });


        // Aplica formatação e limitação do CRF
        document.addEventListener('input', function(e) {
            if (e.target.matches('#crf')) {
                let valor = e.target.value;
                // Mantém apenas letras e números, converte para maiúsculo
                let limpo = valor.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                // Limita a 8 caracteres
                if (limpo.length > 8) limpo = limpo.substring(0, 8);
                // Atualiza o campo
                if (valor !== limpo) {
                    const start = e.target.selectionStart;
                    const diff = limpo.length - valor.length;
                    e.target.value = limpo;
                    e.target.setSelectionRange(start + diff, start + diff);
                }
            }
        });

        // Bloqueia teclas inválidas no CRF (permite letras, números e teclas de controle)
        document.addEventListener('keydown', function(e) {
            if (e.target.matches('#crf')) {
                const key = e.key;
                const isAlnum = /^[A-Za-z0-9]$/.test(key);
                const isControlKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key);
                if (!isAlnum && !isControlKey) {
                    e.preventDefault();
                }
            }
        });

        // === MÁSCARA DE TELEFONE (reutilizada do paciente) ===

        function mascaraTelefone(valor) {
            let digits = valor.replace(/\D/g, '').substring(0, 11);
            if (digits.length <= 10) {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{2})\s(\d{4})(\d)/, '$1 $2-$3');
            } else {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{2})\s(\d{5})(\d)/, '$1 $2-$3');
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target.matches('#telefone')) {
                let valorOriginal = e.target.value;
                let valorMascarado = mascaraTelefone(valorOriginal);
                if (valorOriginal !== valorMascarado) {
                    const start = e.target.selectionStart;
                    const diff = valorMascarado.length - valorOriginal.length;
                    e.target.value = valorMascarado;
                    e.target.setSelectionRange(start + diff, start + diff);
                }
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.target.matches('#telefone')) {
                const key = e.key;
                const isDigit = /^\d$/.test(key);
                const isControlKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key);
                if (!isDigit && !isControlKey) {
                    e.preventDefault();
                }
            }
        });

        // === AJAX PARA ENVIO DO FORMULÁRIO ===

        document.addEventListener("DOMContentLoaded", function() {
            const formFarmaceutico = document.getElementById("formFarmaceutico");
            const farmaceuticoModalElement = document.getElementById("farmaceuticoModal");
            const listaFarmaceuticos = document.getElementById("lista-farmaceuticos");

            if (!formFarmaceutico || !listaFarmaceuticos) return;

            function recarregarLista() {
                fetch('farmaceutico.php?action=load_list')
                    .then(r => r.text())
                    .then(html => {
                        listaFarmaceuticos.innerHTML = html;
                    })
                    .catch(err => {
                        listaFarmaceuticos.innerHTML = '<p class="text-danger">Erro ao carregar a lista.</p>';
                    });
            }

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
                            recarregarLista();
                            alert("Farmacêutico cadastrado com sucesso!");
                        } else {
                            alert("Erro: " + result.replace("error: ", ""));
                        }
                    })
                    .catch(() => {
                        alert("Erro de conexão ao cadastrar farmacêutico.");
                    })
                    .finally(() => {
                        setTimeout(() => {
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                            }
                        }, 500);
                    });
            });
        });

        // Máscara de telefone com suporte a fixo (10 dígitos) e celular (11 dígitos)
        document.addEventListener('input', function(e) {
            if (e.target.matches('#telefone')) {
                let valor = e.target.value;
                let digits = valor.replace(/\D/g, '');
                if (digits.length > 11) digits = digits.substring(0, 11);

                let masked = '';
                if (digits.length <= 2) {
                    masked = digits;
                } else if (digits.length <= 6) {
                    masked = digits.replace(/(\d{2})(\d{0,4})/, '($1) $2');
                } else if (digits.length <= 10) {
                    masked = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else {
                    masked = digits.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }

                if (valor !== masked) {
                    const start = e.target.selectionStart;
                    const diff = masked.length - valor.length;
                    e.target.value = masked;
                    e.target.setSelectionRange(start + diff, start + diff);
                }
            }
        });

        // Bloqueia teclas inválidas no telefone
        document.addEventListener('keydown', function(e) {
            if (e.target.matches('#telefone')) {
                const key = e.key;
                const isDigit = /^\d$/.test(key);
                const isControlKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key);
                if (!isDigit && !isControlKey) {
                    e.preventDefault();
                }
            }
        });

        // Trata colar (paste) no telefone
        document.addEventListener('paste', function(e) {
            if (e.target.matches('#telefone')) {
                setTimeout(() => {
                    let digits = e.target.value.replace(/\D/g, '').substring(0, 11);
                    let masked = '';
                    if (digits.length <= 2) masked = digits;
                    else if (digits.length <= 6) masked = digits.replace(/(\d{2})(\d{0,4})/, '($1) $2');
                    else if (digits.length <= 10) masked = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                    else masked = digits.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                    e.target.value = masked;
                }, 10);
            }
        });
        // Aplica regras de digitação e formatação em tempo real para o campo CRF
        document.addEventListener('input', function(e) {
            if (e.target.matches('#crf')) {
                let valor = e.target.value;
                let novoValor = '';
                let pos = e.target.selectionStart;

                // Processa caractere por caractere
                for (let i = 0; i < valor.length && i < 8; i++) {
                    let char = valor[i].toUpperCase();
                    if (i < 2) {
                        // Primeiros 2: só letras
                        if (/[A-Z]/.test(char)) {
                            novoValor += char;
                        }
                    } else {
                        // A partir do 3º: só números
                        if (/[0-9]/.test(char)) {
                            novoValor += char;
                        }
                    }
                }

                // Atualiza o campo se houver mudança
                if (valor !== novoValor) {
                    e.target.value = novoValor;
                    // Ajusta a posição do cursor
                    const novaPos = Math.min(pos, novoValor.length);
                    e.target.setSelectionRange(novaPos, novaPos);
                }
            }
        });

        // Bloqueia teclas inválidas para evitar caracteres indesejados
        document.addEventListener('keydown', function(e) {
            if (e.target.matches('#crf')) {
                const key = e.key;
                const pos = e.target.selectionStart || 0;
                const valor = e.target.value;

                // Permite teclas de controle sempre
                if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key)) {
                    return;
                }

                // Verifica o contexto da posição do cursor
                if (pos < 2) {
                    // Nas duas primeiras posições: só letras
                    if (!/^[A-Za-z]$/.test(key)) {
                        e.preventDefault();
                    }
                } else {
                    // Nas posições seguintes: só números
                    if (!/^[0-9]$/.test(key)) {
                        e.preventDefault();
                    }
                }

                // Impede digitar além do limite (8 caracteres)
                if (valor.length >= 8 && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight'].includes(key)) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>

</html>
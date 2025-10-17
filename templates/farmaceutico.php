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

/**
 * Função utilitária para mapear o status do BD para a classe CSS.
 * Usa as classes semânticas definidas no seu global.css.
 */
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
        // Note: O uso de '...' desempacota o array $params para a função bind_param
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0):
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)):
            // CORREÇÃO: Usando a classe semântica customizada
            $statusBadgeClass = getStatusBadgeClass($row['status']); 
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            // CORREÇÃO: Aplicando a classe semântica
            echo '<td><span class="badge ' . $statusBadgeClass . '">' . ucfirst($row['status']) . '</span></td>'; 
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
            // Em caso de erro de duplicidade (e-mail ou CRF), a mensagem do SQL aparecerá
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
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
                        // Carregamento inicial (sem filtros)
                        $sql = "SELECT id, nome, email, crf, telefone, status FROM farmaceuticos ORDER BY nome ASC";
                        $result = mysqli_query($conn, $sql);

                        if ($result && mysqli_num_rows($result) > 0):
                            echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>CRF</th><th>Telefone</th><th>Status</th></tr></thead><tbody>';
                            while ($row = mysqli_fetch_assoc($result)):
                                // CORREÇÃO: Usando a função para obter a classe correta
                                $statusBadgeClass = getStatusBadgeClass($row['status']);
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . (!empty($row['crf']) ? htmlspecialchars(formatarCRF($row['crf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
                                // CORREÇÃO: Aplicando a classe semântica
                                echo '<td><span class="badge ' . $statusBadgeClass . '">' . ucfirst($row['status']) . '</span></td>';
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
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="/portal-repo-og/js/script.js"></script>
    <script>
        // Funções utilitárias (carregar templates)
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

        // =====================================================================
        // === LÓGICA DE FILTROS, MÁSCARAS E ENVIO DO FORMULÁRIO ===
        // =====================================================================

        document.addEventListener('DOMContentLoaded', function() {
            const busca = document.getElementById('buscaFarmaceutico');
            const filtro = document.getElementById('filtroStatus');
            const lista = document.getElementById('lista-farmaceuticos');
            const formFarmaceutico = document.getElementById("formFarmaceutico");
            const farmaceuticoModalElement = document.getElementById("farmaceuticoModal"); // CORRETO: Referência ao modal Farmacêutico

            // Função para recarregar a lista (usada por filtros e após cadastro)
            function atualizarLista() {
                const url = `farmaceutico.php?action=load_list&search=${encodeURIComponent(busca.value.trim())}&status=${encodeURIComponent(filtro.value)}`;
                fetch(url).then(r => r.text()).then(html => {
                    lista.innerHTML = html;
                });
            }

            // Inicializa a lista e os listeners de filtro
            if (busca) busca.addEventListener('input', atualizarLista);
            if (filtro) filtro.addEventListener('change', atualizarLista);
            if (lista) atualizarLista();


            // === AJAX PARA ENVIO DO FORMULÁRIO (CORRIGIDO) ===
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
                    // 1. Recebe a resposta do PHP
                    .then(response => response.text())
                    // 2. Processa o resultado
                    .then(result => {
                        const message = result.trim();
                        const modal = bootstrap.Modal.getInstance(farmaceuticoModalElement); // Obtém a instância do modal

                        if (message === "success") {
                            // SUCESSO: FECHA MODAL, RESETA, ATUALIZA LISTA E MOSTRA POPUP
                            if (modal) modal.hide();
                            formFarmaceutico.reset();
                            atualizarLista(); // Atualiza a tabela imediatamente
                            
                            // Chama a função centralizada no script.js
                            showCustomAlert('success', 'Sucesso!', 'Farmacêutico cadastrado com sucesso!');

                        } else {
                            // ERRO DO PHP
                            let errorMessage = message.startsWith('error: ') ? message.replace('error: ', '') : 'Erro desconhecido ao cadastrar.';
                            showCustomAlert('error', 'Erro no Cadastro!', errorMessage);
                        }
                    })
                    // 3. Erro de Conexão/Rede
                    .catch(error => {
                        console.error('Erro de requisição:', error);
                        showCustomAlert('error', 'Erro de Conexão!', 'Erro de conexão ao cadastrar farmacêutico.');
                    })
                    // 4. Finaliza: restaura o botão
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                });
            }

            // === MÁSCARAS E VALIDAÇÕES (MANTIDAS DO SEU CÓDIGO ORIGINAL) ===

            // Aplica formatação e limitação do CRF (Apenas para garantir que não foi perdida)
            document.addEventListener('input', function(e) {
                if (e.target.matches('#crf')) {
                    let valor = e.target.value;
                    let novoValor = '';
                    let pos = e.target.selectionStart;

                    for (let i = 0; i < valor.length && i < 8; i++) {
                        let char = valor[i].toUpperCase();
                        if (i < 2) {
                            if (/[A-Z]/.test(char)) {
                                novoValor += char;
                            }
                        } else {
                            if (/[0-9]/.test(char)) {
                                novoValor += char;
                            }
                        }
                    }

                    if (valor !== novoValor) {
                        e.target.value = novoValor;
                        const novaPos = Math.min(pos, novoValor.length);
                        e.target.setSelectionRange(novaPos, novaPos);
                    }
                }
            });

            // Bloqueia teclas inválidas no CRF
            document.addEventListener('keydown', function(e) {
                if (e.target.matches('#crf')) {
                    const key = e.key;
                    const pos = e.target.selectionStart || 0;
                    const valor = e.target.value;

                    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key)) return;

                    if (pos < 2) {
                        if (!/^[A-Za-z]$/.test(key)) e.preventDefault();
                    } else {
                        if (!/^[0-9]$/.test(key)) e.preventDefault();
                    }

                    if (valor.length >= 8 && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight'].includes(key)) {
                        e.preventDefault();
                    }
                }
            });
            
            // Lógica e máscaras do telefone (mantidas do seu código original)
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

        });
    </script>
</body>

</html>
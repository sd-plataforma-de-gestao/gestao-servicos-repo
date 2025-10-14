<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../config/database.php");

/**
 * Remove todos os caracteres não numéricos de uma string.
 */
function limparCPF($cpf)
{
    return preg_replace('/[^0-9]/', '', $cpf);
}

/**
 * Valida um CPF com base nos dígitos verificadores.
 */
function validarCPF($cpf)
{
    $cpf = limparCPF($cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\1+$/', $cpf)) return false;

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += ((10 - $i) * (int)$cpf[$i]);
    }
    $digito1 = 11 - ($soma % 11);
    if ($digito1 > 9) $digito1 = 0;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += ((11 - $i) * (int)$cpf[$i]);
    }
    $digito2 = 11 - ($soma % 11);
    if ($digito2 > 9) $digito2 = 0;

    return ($digito1 == (int)$cpf[9] && $digito2 == (int)$cpf[10]);
}

/**
 * Formata um CPF limpo (11 dígitos) para o padrão XXX.XXX.XXX-XX.
 */
function formatarCPF($cpf)
{
    $cpf = limparCPF($cpf);
    if (strlen($cpf) != 11) return $cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Carrega a lista de pacientes com suporte a filtros de busca e tipo.
 * Aceita parâmetros via GET: 'search' e 'tipo_paciente'.
 */
if (isset($_GET['action']) && $_GET['action'] === 'load_list') {
    $whereConditions = [];
    $params = [];
    $types = '';

    // Filtro de busca: pesquisa em nome, CPF ou telefone
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        $whereConditions[] = "(nome LIKE ? OR cpf LIKE ? OR telefone LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }

    // Filtro por tipo de paciente (agudo ou cronico)
    if (!empty($_GET['tipo_paciente']) && in_array($_GET['tipo_paciente'], ['agudo', 'cronico'])) {
        $whereConditions[] = "tipo_paciente = ?";
        $params[] = $_GET['tipo_paciente'];
        $types .= 's';
    }

    // Monta a consulta SQL com condições dinâmicas
    $sql = "SELECT id, nome, dtnascimento, email, cpf, telefone, tipo_paciente, status FROM pacientes";
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    $sql .= " ORDER BY nome ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo '<p class="text-danger">Erro interno ao preparar a consulta.</p>';
        exit;
    }

    // Vincula os parâmetros dinamicamente, se houver
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>Data de Nascimento</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Tipo</th><th>Status</th></tr></thead><tbody>';
        while ($row = $result->fetch_assoc()) {
            // Tratamento seguro para data de nascimento (evita 0000-00-00)
            $dtnascimentoExibicao = (!empty($row['dtnascimento']) && $row['dtnascimento'] !== '0000-00-00')
                ? date('d/m/Y', strtotime($row['dtnascimento']))
                : '-';

            $tipoBadge = ($row['tipo_paciente'] === 'cronico') ? 'warning' : 'info';
            $statusBadge = ($row['status'] === 'ativo') ? 'success' : 'danger';

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($dtnascimentoExibicao, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . (!empty($row['cpf']) ? htmlspecialchars(formatarCPF($row['cpf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><span class="badge bg-' . $tipoBadge . '">' . ucfirst($row['tipo_paciente']) . '</span></td>';
            echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="text-muted mt-3">Nenhum paciente encontrado.</p>';
    }

    $stmt->close();
    exit;
}

/**
 * Processamento do formulário de cadastro via POST.
 * Suporta requisições AJAX e tradicionais.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $nome = trim($_POST['nome'] ?? '');
    $dtnascimento = $_POST['dtnascimento'] ?? '';
    $email = $_POST['email'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $tipo_paciente = $_POST['tipo_paciente'] ?? 'agudo';
    $status = $_POST['status'] ?? 'ativo';

    // Validação dos campos obrigatórios
    if (empty($nome) || empty($dtnascimento) || empty($email)) {
        if ($isAjax) {
            echo "error: Campos obrigatórios (nome, data de nascimento e e-mail) não preenchidos.";
        } else {
            header("Location: paciente.php?error=Campos obrigatórios não preenchidos.");
        }
        exit;
    }

    // Validação de e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            echo "error: E-mail inválido.";
        } else {
            header("Location: paciente.php?error=E-mail inválido.");
        }
        exit;
    }

    // Validação e limpeza do CPF (opcional)
    $cpfParaSalvar = null;
    if (!empty($cpf)) {
        $cpfLimpo = limparCPF($cpf);
        if (strlen($cpfLimpo) !== 11 || !validarCPF($cpfLimpo)) {
            if ($isAjax) {
                echo "error: CPF inválido.";
            } else {
                header("Location: paciente.php?error=CPF inválido.");
            }
            exit;
        }
        $cpfParaSalvar = $cpfLimpo;
    }

    // Inserção no banco de dados
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, dtnascimento, email, cpf, telefone, tipo_paciente, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($isAjax) {
            echo "error: falha na preparação da query - " . $conn->error;
        } else {
            header("Location: paciente.php?error=Erro interno no banco de dados.");
        }
        exit;
    }

    $stmt->bind_param("sssssss", $nome, $dtnascimento, $email, $cpfParaSalvar, $telefone, $tipo_paciente, $status);

    if ($stmt->execute()) {
        if ($isAjax) {
            echo "success";
        } else {
            header("Location: paciente.php?success=Paciente cadastrado com sucesso!");
        }
    } else {
        if ($isAjax) {
            echo "error: " . $stmt->error;
        } else {
            header("Location: paciente.php?error=Erro ao salvar paciente.");
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
    <title>Pacientes</title>
      <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/paciente.css">
</head>
<body>
    <div id="header-container"></div>
    <div id="main-content-wrapper">
        <div id="sidebar-container"></div>
        <div id="main-container">
            <div class="page-header">
                <h2 class="page-title">Pacientes</h2>
                <p class="page-subtitle">Gestão completa de pacientes e prontuários.</p>
            </div>

            <div class="pacientes-page">
                <div class="controls-bar card mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fa fa-search"></i> Buscar paciente</label>
                            <input type="text" class="form-control" id="buscaPaciente" placeholder="Nome, CPF ou telefone...">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label"><i class="fa fa-filter"></i> Filtro</label>
                            <select class="form-select" id="filtroStatus">
                                <option value="">Todos os pacientes</option>
                                <option value="cronico">Crônicos</option>
                                <option value="agudo">Agudos</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#pacienteModal">
                                <i class="fa fa-user-plus"></i> Novo Paciente
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pacientes-list card">
                    <h2 class="list-title">Lista de Pacientes</h2>
                    <div id="lista-pacientes">
                        <?php
                        // Carrega a lista inicial sem filtros
                        $sql = "SELECT id, nome, dtnascimento, email, cpf, telefone, tipo_paciente, status FROM pacientes ORDER BY nome ASC";
                        $result = mysqli_query($conn, $sql);

                        if ($result && mysqli_num_rows($result) > 0):
                            echo '<table class="table table-striped table-hover mt-3"><thead class="table-light"><tr><th>Nome</th><th>Data de Nascimento</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Tipo</th><th>Status</th></tr></thead><tbody>';
                            while ($row = mysqli_fetch_assoc($result)):
                                $dtnascimentoExibicao = (!empty($row['dtnascimento']) && $row['dtnascimento'] !== '0000-00-00')
                                    ? date('d/m/Y', strtotime($row['dtnascimento']))
                                    : '-';
                                $tipoBadge = $row['tipo_paciente'] === 'cronico' ? 'warning' : 'info';
                                $statusBadge = $row['status'] === 'ativo' ? 'success' : 'danger';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($dtnascimentoExibicao, ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . (!empty($row['cpf']) ? htmlspecialchars(formatarCPF($row['cpf']), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($row['telefone'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td><span class="badge bg-' . $tipoBadge . '">' . ucfirst($row['tipo_paciente']) . '</span></td>';
                                echo '<td><span class="badge bg-' . $statusBadge . '">' . ucfirst($row['status']) . '</span></td>';
                                echo '</tr>';
                            endwhile;
                            echo '</tbody></table>';
                        else:
                            echo '<p class="text-muted mt-3">Nenhum paciente cadastrado.</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pacienteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="formPaciente" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Novo Paciente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Data de Nascimento *</label>
                                <input type="date" class="form-control" name="dtnascimento" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CPF</label>
                                <input type="text" class="form-control" name="cpf" id="cpf" placeholder="000.000.000-00" maxlength="14">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="telefone" id="telefone" placeholder="(00) 00000-0000" maxlength="15">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tipo de Paciente</label>
                                <select class="form-select" name="tipo_paciente">
                                    <option value="agudo" selected>Agudo</option>
                                    <option value="cronico">Crônico</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="ativo" selected>Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Cadastrar Paciente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal-repo-og/js/script.js"></script>
    <script>
        /**
         * Carrega templates externos (header, sidebar) via fetch.
         */
        function loadTemplate(templatePath, containerId) {
            fetch(templatePath)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById(containerId);
                    if (container) container.innerHTML = html;
                })
                .catch(() => {});
        }

        /**
         * Função para carregar a lista de pacientes com filtros aplicados.
         * Utiliza os valores dos campos de busca e filtro para construir a URL.
         */
        function carregarListaComFiltros() {
            const termoBusca = document.getElementById('buscaPaciente').value.trim();
            const tipoFiltro = document.getElementById('filtroStatus').value;

            let url = 'paciente.php?action=load_list';
            if (termoBusca) {
                url += '&search=' + encodeURIComponent(termoBusca);
            }
            if (tipoFiltro) {
                url += '&tipo_paciente=' + encodeURIComponent(tipoFiltro);
            }

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('lista-pacientes').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('lista-pacientes').innerHTML = '<p class="text-danger">Erro ao carregar a lista.</p>';
                });
        }

        /**
         * Inicializa os eventos e carrega os templates quando a página estiver pronta.
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Carrega header e sidebar
            loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
            loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');

            // Configura eventos de filtro e busca
            const campoBusca = document.getElementById('buscaPaciente');
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

        /**
         * Gerencia o envio do formulário de pacientes via AJAX.
         */
        document.addEventListener("DOMContentLoaded", function() {
            const formPaciente = document.getElementById("formPaciente");
            const pacienteModalElement = document.getElementById("pacienteModal");
            const listaPacientes = document.getElementById("lista-pacientes");

            if (!formPaciente || !listaPacientes) {
                return;
            }

            function recarregarListaPacientes() {
                fetch('paciente.php?action=load_list')
                    .then(response => response.text())
                    .then(html => {
                        listaPacientes.innerHTML = html;
                    })
                    .catch(() => {
                        listaPacientes.innerHTML = '<p class="text-danger">Erro ao carregar a lista de pacientes.</p>';
                    });
            }

            formPaciente.addEventListener("submit", function(e) {
                e.preventDefault();

                const btn = formPaciente.querySelector('[type="submit"]');
                if (!btn || btn.disabled) return;

                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

                const formData = new FormData(formPaciente);

                fetch('paciente.php', {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.trim() === "success") {
                            const modal = bootstrap.Modal.getInstance(pacienteModalElement);
                            if (modal) modal.hide();
                            formPaciente.reset();
                            recarregarListaPacientes();
                            alert("Paciente cadastrado com sucesso!");
                        } else {
                            alert("Erro: " + result.replace("error: ", ""));
                        }
                    })
                    .catch(() => {
                        alert("Erro de conexão ao cadastrar paciente.");
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

        // === MÁSCARAS UNIFICADAS PARA CPF E TELEFONE ===

        /**
         * Aplica máscara de CPF no formato XXX.XXX.XXX-XX.
         */
        function mascaraCPF(valor) {
            let digits = valor.replace(/\D/g, '').substring(0, 11);
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
        }

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

        /**
         * Aplica máscaras automaticamente em tempo real.
         */
        document.addEventListener('input', function(e) {
            const el = e.target;
            let valorOriginal = el.value;

            if (el.matches('#cpf')) {
                let valorMascarado = mascaraCPF(valorOriginal);
                if (valorOriginal !== valorMascarado) {
                    const start = el.selectionStart;
                    const diff = valorMascarado.length - valorOriginal.length;
                    el.value = valorMascarado;
                    el.setSelectionRange(start + diff, start + diff);
                }
            } else if (el.matches('#telefone')) {
                let valorMascarado = mascaraTelefone(valorOriginal);
                if (valorOriginal !== valorMascarado) {
                    const start = el.selectionStart;
                    const diff = valorMascarado.length - valorOriginal.length;
                    el.value = valorMascarado;
                    el.setSelectionRange(start + diff, start + diff);
                }
            }
        });

        /**
         * Bloqueia teclas inválidas em campos de CPF e telefone.
         */
        document.addEventListener('keydown', function(e) {
            const el = e.target;
            if (el.matches('#cpf, #telefone')) {
                const key = e.key;
                const isControlKey = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(key);
                let isValid = false;

                if (el.matches('#cpf')) {
                    isValid = /^\d$/.test(key);
                } else if (el.matches('#telefone')) {
                    isValid = /^\d$/.test(key);
                }

                if (!isValid && !isControlKey) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
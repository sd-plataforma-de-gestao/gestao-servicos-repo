<?php
session_start();

// Inclui conexão (igual ao seu cadastro)
include("../config/database.php");

// Se já estiver logado, redireciona
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: /portal-repo-og/index.php");
    exit;
}

$mensagem = '';
$tipo_mensagem = ''; // 'erro' ou 'sucesso'

// Processa o login se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crf = trim($_POST['crf'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($crf) || empty($senha)) {
        $mensagem = "Preencha CRF e senha.";
        $tipo_mensagem = 'erro';
    } else {
        // Busca farmacêutico
        $stmt = $conn->prepare("SELECT id, nome, senha FROM farmaceuticos WHERE crf = ? AND status = 'ativo' LIMIT 1");
        if (!$stmt) {
            $mensagem = "Erro interno no servidor.";
            $tipo_mensagem = 'erro';
        } else {
            $stmt->bind_param("s", $crf);
            $stmt->execute();
            $result = $stmt->get_result();
            $farmaceutico = $result->fetch_assoc();
            $stmt->close();

            if (!$farmaceutico) {
                $mensagem = "CRF não encontrado ou conta inativa.";
                $tipo_mensagem = 'erro';
            } else {
                $senha_armazenada = $farmaceutico['senha'];

                // Verifica se a senha está em hash
                if (password_verify($senha, $senha_armazenada)) {
                    // ✅ Login OK
                    $_SESSION['farmaceutico_id'] = $farmaceutico['id'];
                    $_SESSION['farmaceutico_nome'] = $farmaceutico['nome'];
                    $_SESSION['logado'] = true;

                    header("Location: /portal-repo-og/index.php");
                    exit;

                } elseif ($senha === $senha_armazenada) {
                    // ⚠️ Senha em texto puro — converte
                    $nova_senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE farmaceuticos SET senha = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $nova_senha_hash, $farmaceutico['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    // ✅ Login após conversão
                    $_SESSION['farmaceutico_id'] = $farmaceutico['id'];
                    $_SESSION['farmaceutico_nome'] = $farmaceutico['nome'];
                    $_SESSION['logado'] = true;

                    header("Location: /portal-repo-og/index.php");
                    exit;

                } else {
                    $mensagem = "Senha incorreta.";
                    $tipo_mensagem = 'erro';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vitally</title>
    <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
    <link rel="stylesheet" href="/portal-repo-og/styles/login.css">
    <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="/portal-repo-og/assets/logo-login.png" alt="Logo Vitally" class="logo-full">
        </div>
        <div class="login-right">
            <h1 class="login-title">Seja bem-vindo(a) à Vitally!</h1>
            <p class="login-subtitle">Acesse sua conta para continuar</p>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $tipo_mensagem === 'erro' ? 'danger' : 'success' ?>" role="alert" style="margin: 15px 0; padding: 12px; border-radius: 6px;">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" style="width: 100%;">
                <div class="form-group">
                    <label for="CRF">CRF:</label>
                    <input type="text" id="CRF" name="crf" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                <div class="form-links">
                    <a href="#" class="text-primary">Cadastre-se</a>
                    <a href="#" class="text-primary">Esqueceu sua senha?</a>
                </div>
                <button type="submit" class="btn-primary-custom btn-login">Entrar</button>
            </form>

            <p class="copyright">© 2025 Vitally. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
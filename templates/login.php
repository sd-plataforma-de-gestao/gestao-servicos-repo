<?php
//include_once __DIR__ . '/../config/auth.php';

//if (!Auth::isAuthenticated()) {
    //header("Location: /portal-repo-og/templates/login.php");
    //exit();
//}
//?>

$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
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

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger" style="margin: 15px 0; padding: 12px; border-radius: 6px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/portal-repo-og/logar.php" class="login-form" style="width: 100%;">
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
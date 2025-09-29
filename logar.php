<?php
include_once __DIR__ . '/config/auth.php';

$crf = $_POST["crf"] ?? '';
$senha = $_POST["senha"] ?? '';

if (empty($crf) || empty($senha)) {
    header("Location: /portal-repo-og/templates/login.php?message=Campos obrigatórios não preenchidos.");
    exit();
}

$message = Auth::login($crf, $senha);

if (Auth::isAuthenticated()) {
    header("Location: /portal-repo-og/index.php");
    exit();
}

header("Location: /portal-repo-og/templates/login.php?message=" . urlencode($message));
exit();
?>
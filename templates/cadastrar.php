<?php include("../config/database.php"); ?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Paciente</title>
    <link rel="stylesheet" href="/portal-repo-og/styles/style.css">
</head>
<body>
    <h2>Cadastrar Paciente</h2>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="nome" required><br>

        <label>Data Nascimento:</label>
        <input type="number" name="dtnascimento" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <button type="submit" name="salvar">Salvar</button>
    </form>

    <?php
    if (isset($_POST['salvar'])) {
        $nome  = $_POST['nome'];
        $dtnascimento = $_POST['dtnascimento'];
        $email = $_POST['email'];

        $sql = "INSERT INTO pacientes (nome, dtnascimento, email) VALUES ('$nome', '$dtnascimento', '$email')";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green'>Paciente cadastrado com sucesso!</p>";
        } else {
            echo "<p style='color:red'>Erro: " . mysqli_error($conn) . "</p>";
        }
    }
    ?>
    <p><a href="../index.php">⬅ Voltar</a></p>
</body>
</html>

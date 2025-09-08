<?php include("config/database.php"); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Farmacêutico</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h2>Cadastrar Farmacêutico</h2>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="nome" required><br>

        <label>CRF:</label>
        <input type="text" name="crf" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <button type="submit" name="salvar">Salvar</button>
    </form>

    <?php
    if (isset($_POST['salvar'])) {
        $nome  = $_POST['nome'];
        $crf   = $_POST['crf'];
        $email = $_POST['email'];

        $sql = "INSERT INTO farmaceuticos (nome, crf, email) VALUES ('$nome', '$crf', '$email')";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green'>Farmacêutico cadastrado com sucesso!</p>";
        } else {
            echo "<p style='color:red'>Erro: " . mysqli_error($conn) . "</p>";
        }
    }
    ?>
    <p><a href="../index.php">⬅ Voltar</a></p>
</body>
</html>

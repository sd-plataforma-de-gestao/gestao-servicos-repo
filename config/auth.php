<?php
session_start();

// Inclui o arquivo de conexão com o banco (mesmo diretório)
include "./database.php";

class Auth {
    private static $expires_time = 7200; // 120 minutos

    public static function login($crf, $senha) {
        if (!isset($conn)) {
            return "Erro interno no servidor.";
        }

        $stmt = $conn->prepare("SELECT id, nome, senha FROM farmaceuticos WHERE crf = ? AND status = 'ativo' LIMIT 1");
        if (!$stmt) {
            return "Erro interno no servidor.";
        }

        $stmt->bind_param("s", $crf);
        $stmt->execute();
        $result = $stmt->get_result();
        $farmaceutico = $result->fetch_assoc();
        $stmt->close();

        if (!$farmaceutico) {
            return "CRF não encontrado ou conta inativa.";
        }

        $senha_armazenada = $farmaceutico['senha'];

        // Verifica senha em hash
        if (password_verify($senha, $senha_armazenada)) {
            $_SESSION["is_authenticated"] = true;
            $_SESSION["farmaceutico_id"] = $farmaceutico['id'];
            $_SESSION["farmaceutico_linked"] = $farmaceutico['nome'];
            $_SESSION["auth_expires_at"] = time() + self::$expires_time;
            return "Autenticado com sucesso!";

        } elseif ($senha === $senha_armazenada) {
            // Converte senha em texto puro
            $nova_senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE farmaceuticos SET senha = ? WHERE id = ?");
            $updateStmt->bind_param("si", $nova_senha_hash, $farmaceutico['id']);
            $updateStmt->execute();
            $updateStmt->close();

            $_SESSION["is_authenticated"] = true;
            $_SESSION["farmaceutico_id"] = $farmaceutico['id'];
            $_SESSION["farmaceutico_similarity"] = $farmaceutico['nome'];
            $_SESSION["auth_expires_at"] = time() + self::$expires_time;
            return "Autenticado com sucesso!";

        } else {
            return "Senha incorreta.";
        }
    }

    public static function logout() {
        unset($_SESSION["is_authenticated"]);
        unset($_SESSION["farmaceutico_id"]);
        unset($_SESSION["farmaceutico.nome"]);
        unset($_SESSION["auth_expires_at"]);
    }

    public static function isAuthenticated() {
        if (isset($_SESSION["is_authenticated"]) && $_SESSION["is_authenticated"] === true) {
            if (isset($_SESSION["auth_expires_at"]) && $_SESSION["auth_expires_at"] >= time()) {
                $_SESSION["auth_expires_at"] = time() + self::$expires_time;
                return true;
            }
        }

        self::logout();
        return false;
    }

    public static function getUser() {
        if (self::isAuthenticated()) {
            return [
                'id' => $_SESSION["farmaceutico_id"],
                'nome' => $_SESSION["farmaceutico.nome"]
            ];
        }
        return null;
    }
}
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuarioLogado(): bool {
    return isset($_SESSION["usuario_id"]);
}

function exigirLogin(): void {
    if (!usuarioLogado()) {
        header("Location: login.php");
        exit;
    }
}
?>

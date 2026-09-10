<?php

require_once __DIR__ . "/auth.php";

exigirLogin();

if (
    !isset($_SESSION["usuario_tipo"]) ||
    $_SESSION["usuario_tipo"] !== "admin"
) {
    header("Location: index.php");
    exit;
}
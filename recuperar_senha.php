<?php

session_start();

// ==========================================
// CONEXÃO
// ==========================================

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "solvebugs"
);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ==========================================
// PHPMailer
// ==========================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/config/email.php";

// ==========================================
// VARIÁVEIS
// ==========================================

$erro = "";
$sucesso = "";

$emailInformado = "";

// ==========================================
// PROCESSAMENTO
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $emailInformado = trim($_POST["email"] ?? "");

    if ($emailInformado === "") {

        $erro = "Informe seu e-mail.";
    } elseif (!filter_var($emailInformado, FILTER_VALIDATE_EMAIL)) {

        $erro = "Informe um e-mail válido.";
    } else {

        // ==========================================
        // PROCURA USUÁRIO
        // ==========================================

        $stmt = $conn->prepare("
            SELECT id, nome, email
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $emailInformado);
        $stmt->execute();

        $resultado = $stmt->get_result();

        /*
         * Mesmo que o e-mail não exista,
         * mostramos a mesma mensagem.
         *
         * Isso evita que alguém descubra
         * quais e-mails possuem conta.
         */

        if ($resultado->num_rows === 1) {

            $usuario = $resultado->fetch_assoc();

            // ==========================================
            // REMOVE TOKENS ANTIGOS
            // ==========================================

            $limpar = $conn->prepare("
                DELETE FROM recuperacao_senha
                WHERE usuario_id = ?
                   OR expiracao < NOW()
                   OR usado = 1
            ");

            $limpar->bind_param("i", $usuario["id"]);
            $limpar->execute();
            $limpar->close();

            // ==========================================
            // GERA TOKEN
            // ==========================================

            $token = bin2hex(random_bytes(32));

            // Token válido por 1 hora
            $expiracao = date(
                "Y-m-d H:i:s",
                time() + 3600
            );

            // ==========================================
            // SALVA TOKEN
            // ==========================================

            $insert = $conn->prepare("
                INSERT INTO recuperacao_senha
                (
                    usuario_id,
                    token,
                    expiracao,
                    usado
                )
                VALUES (?, ?, ?, 0)
            ");

            $insert->bind_param(
                "iss",
                $usuario["id"],
                $token,
                $expiracao
            );

            $insert->execute();
            $insert->close();

            // ==========================================
            // LINK
            // ==========================================

            $link = URL_SITE .
                "/redefinir_senha.php?token=" .
                urlencode($token);

            // ==========================================
            // ENVIA E-MAIL
            // ==========================================

            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = "html";
            try {

                $mail->isSMTP();

                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USUARIO;
                $mail->Password = SMTP_SENHA;

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;

                $mail->CharSet = "UTF-8";

                // Remetente
                $mail->setFrom(
                    EMAIL_REMETENTE,
                    NOME_REMETENTE
                );

                // Destinatário
                $mail->addAddress(
                    $usuario["email"],
                    $usuario["nome"]
                );

                $mail->isHTML(true);

                $mail->Subject = "Recuperação de senha - SolveBugs";

                $nome = htmlspecialchars(
                    $usuario["nome"],
                    ENT_QUOTES,
                    "UTF-8"
                );

                $linkSeguro = htmlspecialchars(
                    $link,
                    ENT_QUOTES,
                    "UTF-8"
                );

                $mail->Body =
                    "<!DOCTYPE html>
                <html lang='pt-BR'>
                <head>
                    <meta charset='UTF-8'>
                </head>

                <body style='
                    margin:0;
                    padding:0;
                    background:#141d29;
                    font-family:Arial,sans-serif;
                '>

                    <div style='
                        max-width:600px;
                        margin:40px auto;
                        background:#1b2838;
                        border:1px solid rgba(255,255,255,.15);
                        border-radius:12px;
                        padding:40px;
                        color:#c5c5c5;
                    '>

                        <h1 style='
                            color:#38bdf8;
                            text-align:center;
                        '>
                            SolveBugs
                        </h1>

                        <h2 style='
                            color:white;
                            text-align:center;
                        '>
                            Recuperação de senha
                        </h2>

                        <p>
                            Olá, <strong>{$nome}</strong>!
                        </p>

                        <p>
                            Recebemos uma solicitação para
                            redefinir a senha da sua conta
                            no SolveBugs.
                        </p>

                        <p>
                            Clique no botão abaixo para
                            criar uma nova senha:
                        </p>

                        <div style='text-align:center;margin:30px 0;'>

                            <a href='{$linkSeguro}'
                               style='
                                    display:inline-block;
                                    padding:14px 30px;
                                    background:#a2c9d4;
                                    color:#141d29;
                                    text-decoration:none;
                                    border-radius:30px;
                                    font-weight:bold;
                               '>
                                Redefinir senha
                            </a>

                        </div>

                        <p>
                            Este link ficará disponível
                            por <strong>1 hora</strong>.
                        </p>

                        <p style='
                            color:#999;
                            font-size:13px;
                        '>
                            Se você não solicitou a recuperação
                            da senha, ignore este e-mail.
                        </p>

                    </div>

                </body>
                </html>
                ";

                $mail->AltBody =
                    "Olá {$usuario["nome"]}!\n\n" .
                    "Para redefinir sua senha do SolveBugs, " .
                    "acesse o link:\n\n" .
                    $link . "\n\n" .
                    "Este link é válido por 1 hora.";

                $mail->send();
            } catch (Exception $e) {

                $remover = $conn->prepare("
        DELETE FROM recuperacao_senha
        WHERE token = ?
    ");

                $remover->bind_param("s", $token);
                $remover->execute();
                $remover->close();

                $erro = "Erro ao enviar e-mail: " . $mail->ErrorInfo;
            }
        }

        $stmt->close();

        // Mensagem genérica
        if ($erro === "") {
            $sucesso =
                "Se o e-mail estiver cadastrado, " .
                "você receberá um link para redefinir sua senha.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SolveBugs - Recuperar senha</title>

    <link
        rel="stylesheet"
        href="./css/recuperar_senha.css">

    <link
        href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
        rel="stylesheet">

</head>

<body>

    <div class="fundo"></div>

    <img
        class="imgLogo"
        src="img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
        alt="Logo SolveBugs">

    <main class="container">

        <form method="POST">

            <h1>Recuperar senha</h1>

            <p class="descricao">
                Informe o e-mail associado à sua conta.
                Enviaremos um link para você criar
                uma nova senha.
            </p>

            <?php if ($erro): ?>

                <div class="alerta erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <?php if ($sucesso): ?>

                <div class="alerta sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>

            <div class="input-box">

                <input
                    type="email"
                    name="email"
                    placeholder="E-mail"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars($emailInformado) ?>">

                <i class="bx bxs-envelope"></i>

            </div>

            <div class="container-botao">

                <button
                    class="recuperar"
                    type="submit">
                    Enviar link de recuperação
                </button>

            </div>

            <a
                href="login.php"
                class="voltar-login">
                Voltar para o login
            </a>

        </form>

    </main>

</body>

</html>
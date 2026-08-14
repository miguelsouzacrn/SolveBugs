<?php

session_start();

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}


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
// CADASTRO
// ==========================================

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];
    $confirmar = $_POST["confirmar_senha"];


    if (
        $nome === "" ||
        $email === "" ||
        $senha === "" ||
        $confirmar === ""
    ) {

        $erro = "Preencha todos os campos.";

    } elseif ($senha !== $confirmar) {

        $erro = "As senhas não são iguais.";

    } elseif (strlen($senha) < 6) {

        $erro = "A senha deve possuir pelo menos 6 caracteres.";

    } else {


        // ==================================
        // VERIFICAR E-MAIL
        // ==================================

        $stmt = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $resultado = $stmt->get_result();

        $stmt->close();


        if ($resultado->num_rows > 0) {

            $erro = "Este e-mail já está cadastrado.";

        } else {


            // ==============================
            // CRIPTOGRAFAR SENHA
            // ==============================

            $senhaHash =
                password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );


            // ==============================
            // CRIAR USUÁRIO
            // ==============================

            $stmt = $conn->prepare("
                INSERT INTO usuarios
                (nome, email, senha, tipo)
                VALUES (?, ?, ?, 'usuario')
            ");

            $stmt->bind_param(
                "sss",
                $nome,
                $email,
                $senhaHash
            );


            if ($stmt->execute()) {

                $sucesso =
                    "Cadastro realizado com sucesso!";

            } else {

                $erro =
                    "Erro ao realizar cadastro.";
            }

            $stmt->close();

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
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Cadastro - SolveBugs
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            font-family: Arial;

            background:
                #141d29;

            color:
                rgb(162,201,212);

        }


        .cadastro {

            width: 400px;

            padding: 35px;

            background:
                rgb(27,40,56);

            border:
                1px solid
                rgb(68,91,119);

            border-radius: 8px;

        }


        h1 {

            text-align: center;

            margin-bottom: 25px;

        }


        .campo {

            margin-bottom: 15px;

        }


        label {

            display: block;

            margin-bottom: 5px;

        }


        input {

            width: 100%;

            padding: 11px;

            background:
                transparent;

            border:
                1px solid
                rgba(255,255,255,.2);

            border-radius: 5px;

            color:
                rgb(162,201,212);

        }


        button {

            width: 100%;

            padding: 12px;

            border: none;

            border-radius: 5px;

            background:
                rgb(162,201,212);

            color:
                rgb(27,40,56);

            font-weight: bold;

            cursor: pointer;

        }


        .erro,
        .sucesso {

            padding: 10px;

            margin-bottom: 15px;

            border-radius: 5px;

            text-align: center;

        }


        .erro {

            background:
                rgba(184,44,70,.2);

            color:
                #ff7189;

        }


        .sucesso {

            background:
                rgba(50,150,100,.2);

            color:
                #72d5a0;

        }


        .links {

            text-align: center;

            margin-top: 20px;

        }


        a {

            color:
                rgb(162,201,212);

        }

    </style>

</head>


<body>


<div class="cadastro">

    <h1>
        Criar conta
    </h1>


    <?php if ($erro !== ""): ?>

        <div class="erro">

            <?= htmlspecialchars($erro) ?>

        </div>

    <?php endif; ?>


    <?php if ($sucesso !== ""): ?>

        <div class="sucesso">

            <?= htmlspecialchars($sucesso) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <div class="campo">

            <label>
                Nome
            </label>

            <input
                type="text"
                name="nome"
                required
            >

        </div>


        <div class="campo">

            <label>
                E-mail
            </label>

            <input
                type="email"
                name="email"
                required
            >

        </div>


        <div class="campo">

            <label>
                Senha
            </label>

            <input
                type="password"
                name="senha"
                minlength="6"
                required
            >

        </div>


        <div class="campo">

            <label>
                Confirmar senha
            </label>

            <input
                type="password"
                name="confirmar_senha"
                minlength="6"
                required
            >

        </div>


        <button type="submit">

            Cadastrar

        </button>


    </form>


    <div class="links">

        <a href="login.php">
            Já tenho uma conta
        </a>

        <br><br>

        <a href="index.php">
            Voltar para o início
        </a>

    </div>

</div>


</body>

</html>
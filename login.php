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
// LOGIN
// ==========================================

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];


    if ($email === "" || $senha === "") {

        $erro = "Preencha todos os campos.";

    } else {

        $stmt = $conn->prepare("
            SELECT id, nome, email, senha, tipo
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $resultado = $stmt->get_result();


        if ($resultado->num_rows === 1) {

            $usuario = $resultado->fetch_assoc();


            if (password_verify($senha, $usuario["senha"])) {

                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nome"] = $usuario["nome"];
                $_SESSION["usuario_email"] = $usuario["email"];
                $_SESSION["tipo"] = $usuario["tipo"];


                header("Location: index.php");
                exit;

            } else {

                $erro = "E-mail ou senha incorretos.";

            }

        } else {

            $erro = "E-mail ou senha incorretos.";

        }

        $stmt->close();

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

    <title>Login - SolveBugs</title>


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


        .fundo {

            position: fixed;

            inset: 0;

            background-image:
                url("https://image.api.playstation.com/vulcan/ap/rnd/202111/3013/bxSj4jO0KBqUgAbH3zuNjCje.jpg");

            background-size: cover;

            background-position: center;

            opacity: .35;

            z-index: -1;

        }


        .login {

            width: 380px;

            padding: 35px;

            background:
                rgba(27,40,56,.95);

            border:
                1px solid
                rgb(68,91,119);

            border-radius: 8px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.5);

        }


        .login h1 {

            text-align: center;

            margin-bottom: 25px;

        }


        .campo {

            margin-bottom: 18px;

        }


        .campo label {

            display: block;

            margin-bottom: 6px;

        }


        .campo input {

            width: 100%;

            padding: 12px;

            border-radius: 5px;

            border:
                1px solid
                rgba(255,255,255,.2);

            background:
                transparent;

            color:
                rgb(162,201,212);

            outline: none;

        }


        .botao {

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


        .botao:hover {

            background:
                rgb(27,40,56);

            color:
                rgb(162,201,212);

            border:
                1px solid
                rgb(162,201,212);

        }


        .erro {

            margin-bottom: 15px;

            padding: 10px;

            border-radius: 5px;

            background:
                rgba(184,44,70,.2);

            color:
                #ff7189;

            text-align: center;

        }


        .links {

            margin-top: 20px;

            text-align: center;

        }


        .links a {

            color:
                rgb(162,201,212);

        }

    </style>

</head>


<body>

<div class="fundo"></div>


<div class="login">

    <h1>
        Login
    </h1>


    <?php if ($erro !== ""): ?>

        <div class="erro">

            <?= htmlspecialchars($erro) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


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
                required
            >

        </div>


        <button
            class="botao"
            type="submit"
        >
            Entrar
        </button>


    </form>


    <div class="links">

        <p>
            Ainda não possui uma conta?
        </p>

        <a href="cadastro_usuario.php">
            Criar conta
        </a>

        <br>

        <a href="index.php">
            Voltar para o início
        </a>

    </div>

</div>

</body>

</html>
<?php

session_start();


// =====================================================
// CONEXÃO
// =====================================================

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


// =====================================================
// VERIFICAR ID DO JOGO
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;

}

$jogo_id = intval($_GET["id"]);


// =====================================================
// BUSCAR JOGO
// =====================================================

$stmt = $conn->prepare("
    SELECT
        id,
        nome,
        descricao,
        capa,
        logo,
        banner
    FROM jogos
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $jogo_id);

$stmt->execute();

$resultadoJogo = $stmt->get_result();


// =====================================================
// JOGO NÃO EXISTE
// =====================================================

if ($resultadoJogo->num_rows === 0) {

    header("Location: index.php");
    exit;

}

$jogo = $resultadoJogo->fetch_assoc();

$stmt->close();


// =====================================================
// PROCESSAR NOVO COMENTÁRIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (isset($_POST["acao"]) && $_POST["acao"] === "comentar") {

    if (!isset($_SESSION["usuario_id"])) {
        header("Location: login.php");
        exit;
    }

    $usuario_id = intval($_SESSION["usuario_id"]);

    $texto = trim($_POST["comentario"] ?? "");

    $comentario_pai_id = null;

    if (
        isset($_POST["comentario_pai_id"]) &&
        $_POST["comentario_pai_id"] !== ""
    ) {
        $comentario_pai_id =
            intval($_POST["comentario_pai_id"]);
    }


    if ($texto !== "") {

        $stmt = $conn->prepare("
            INSERT INTO comentarios
            (
                jogo_id,
                usuario_id,
                comentario_pai_id,
                texto
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiis",
            $jogo_id,
            $usuario_id,
            $comentario_pai_id,
            $texto
        );

        $stmt->execute();

        $stmt->close();
    }


    header(
        "Location: jogo.php?id=" . $jogo_id
    );

    exit;
}


    // =================================================
    // LIKE / DISLIKE
    // =================================================

    if (
        isset($_POST["acao"]) &&
        $_POST["acao"] === "interagir"
    ) {

        if (!isset($_SESSION["usuario_id"])) {

            header(
                "Location: login.php"
            );

            exit;
        }


        $usuario_id =
            intval($_SESSION["usuario_id"]);

        $comentario_id =
            intval($_POST["comentario_id"]);

        $tipo =
            $_POST["tipo"] ?? "";


        if (
            $tipo !== "like" &&
            $tipo !== "dislike"
        ) {

            header(
                "Location: jogo.php?id=" . $jogo_id
            );

            exit;
        }


        // Verificar se o usuário já interagiu

        $stmt = $conn->prepare("
            SELECT
                id,
                tipo
            FROM interacoes_comentario
            WHERE comentario_id = ?
            AND usuario_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $comentario_id,
            $usuario_id
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        $interacao =
            $resultado->fetch_assoc();

        $stmt->close();


        if ($interacao) {

            if ($interacao["tipo"] === $tipo) {

                // Clicou novamente:
                // remove a interação

                $stmt = $conn->prepare("
                    DELETE FROM interacoes_comentario
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "i",
                    $interacao["id"]
                );

                $stmt->execute();

                $stmt->close();

            } else {

                // Trocou like por dislike
                // ou dislike por like

                $stmt = $conn->prepare("
                    UPDATE interacoes_comentario
                    SET tipo = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "si",
                    $tipo,
                    $interacao["id"]
                );

                $stmt->execute();

                $stmt->close();

            }

        } else {

            // Primeira interação

            $stmt = $conn->prepare("
                INSERT INTO interacoes_comentario
                (
                    comentario_id,
                    usuario_id,
                    tipo
                )
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "iis",
                $comentario_id,
                $usuario_id,
                $tipo
            );

            $stmt->execute();

            $stmt->close();

        }


        header(
            "Location: jogo.php?id=" . $jogo_id
        );

        exit;
    }
}


// =====================================================
// BUSCAR COMENTÁRIOS
// =====================================================

$sqlComentarios = "
    SELECT

        c.id,
        c.texto,
        c.data_criacao,
        c.comentario_pai_id,

        u.id AS usuario_id,
        u.nome,

        (
            SELECT COUNT(*)
            FROM interacoes_comentario i
            WHERE i.comentario_id = c.id
            AND i.tipo = 'like'
        ) AS likes,

        (
            SELECT COUNT(*)
            FROM interacoes_comentario i
            WHERE i.comentario_id = c.id
            AND i.tipo = 'dislike'
        ) AS dislikes

    FROM comentarios c

    INNER JOIN usuarios u
        ON u.id = c.usuario_id

    WHERE c.jogo_id = ?

    ORDER BY c.data_criacao ASC
";

$stmt = $conn->prepare($sqlComentarios);

$stmt->bind_param(
    "i",
    $jogo_id
);

$stmt->execute();

$comentarios =
    $stmt->get_result();

$stmt->close();


// =====================================================
// USUÁRIO ATUAL
// =====================================================

$logado =
    isset($_SESSION["usuario_id"]);

$usuarioAtual =
    $logado
    ? intval($_SESSION["usuario_id"])
    : 0;

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
        <?= htmlspecialchars($jogo["nome"]) ?>
        - SolveBugs
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >


    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }


        body {

            min-height: 100vh;

            background:
                rgb(20, 29, 41);

            color:
                rgb(162, 201, 212);

            font-family:
                Arial,
                sans-serif;

        }


        /* =========================================
           FUNDO
        ========================================= */

        .fundoimg {

            position: fixed;

            inset: 0;

            width: 100%;

            height: 100vh;

            z-index: -2;

            overflow: hidden;

        }


        .fundoimg img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .fundoimg::after {

            content: "";

            position: absolute;

            inset: 0;

            background:
                rgba(20, 29, 41, .78);

        }


        /* =========================================
           BOTÃO VOLTAR
        ========================================= */

        .Btn {

            position: fixed;

            top: 10px;

            left: 10px;

            display: flex;

            align-items: center;

            width: 45px;

            height: 45px;

            border-radius: 50%;

            overflow: hidden;

            background:
                rgb(39, 57, 80);

            transition: .3s;

            text-decoration: none;

            z-index: 20;

        }


        .sign {

            width: 100%;

            display: flex;

            justify-content: center;

            align-items: center;

        }


        .sign svg {

            width: 17px;

        }


        .sign svg path {

            fill: white;

        }


        .text {

            position: absolute;

            right: 0;

            width: 0;

            opacity: 0;

            color: white;

            font-size: 14px;

            transition: .3s;

        }


        .Btn:hover {

            width: 90px;

            border-radius: 40px;

            background:
                #B82c46;

        }


        .Btn:hover .sign {

            width: 30%;

            padding-left: 10px;

        }


        .Btn:hover .text {

            opacity: 1;

            width: 60%;

            padding-right: 10px;

        }


        /* =========================================
           CONTAINER
        ========================================= */

        .containerjogo {

            min-height: 100vh;

            width: 73%;

            margin: auto;

            background:
                rgba(27, 40, 56, .85);

            border-left:
                2px solid
                rgba(68, 91, 119, .7);

            border-right:
                2px solid
                rgba(68, 91, 119, .7);

            padding-top: 20px;

            box-shadow:
                0 5px 10px
                rgba(0, 0, 0, .5);

        }


        /* =========================================
           LOGO
        ========================================= */

        .logo-container {

            margin:
                0 25%;

            height: 100px;

        }


        .logo-container img {

            width: 100%;

            height: 100%;

            object-fit: contain;

        }


        /* =========================================
           BARRA DE PESQUISA
        ========================================= */

        .linha-divisoria {

            margin-top: 3%;

            width: 100%;

            height: 70px;

            background:
                rgba(24, 36, 51, .75);

            display: flex;

            justify-content: center;

            align-items: center;

        }


        .input-box {

            width: 90%;

            height: 40px;

        }


        .input-box input {

            width: 100%;

            height: 100%;

            background:
                transparent;

            border:
                2px solid
                rgba(255, 255, 255, .2);

            border-radius: 40px;

            outline: none;

            font-size: 16px;

            color:
                rgb(162, 201, 212);

            padding:
                10px 20px;

        }


        .input-box input::placeholder {

            color:
                #c5c5c5;

        }


        /* =========================================
           COMENTÁRIOS
        ========================================= */

        #comentarios {

            margin:
                6% 3% 0;

            border-radius: 5px;

            border:
                1px solid
                rgb(36, 53, 75);

            min-height: 480px;

            background:
                rgba(20, 29, 41, 1);

            padding: 10px;

        }


        .comentario {

            background:
                rgb(39, 57, 80);

            padding: 12px;

            margin-bottom: 8px;

            border-radius: 5px;

        }


        .topo {

            display: flex;

            align-items: center;

            gap: 6px;

            margin-bottom: 7px;

        }


        .topo a {

            color:
                rgb(162, 201, 212);

            text-decoration: none;

        }


        .icone {

            font-size: 18px;

        }


        .nome {

            font-weight: bold;

        }


        .data {

            font-size: 11px;

            color:
                #9caab0;

            margin-left: 5px;

        }


        .texto-comentario {

            margin:
                5px 0 5px;

            word-wrap: break-word;

        }


        /* =========================================
           AÇÕES
        ========================================= */

        .acoes {

            display: flex;

            gap: 5px;

            margin-top: 5px;

        }


        .acoes form {

            display: inline;

        }


        .acoes button {

            padding:
                3px 8px;

            border:
                2px solid
                rgba(162, 201, 212, .4);

            border-radius: 4px;

            background:
                rgba(8, 82, 82, .2);

            color:
                rgb(135, 162, 167);

            cursor: pointer;

        }


        .acoes button:hover {

            background:
                rgba(2, 29, 29, .5);

            color:
                rgb(162, 201, 212);

        }


        /* =========================================
           BARRA DE COMENTÁRIO
        ========================================= */

        .barra-comentario {

            width: 100%;

            background:
                rgb(31, 46, 65);

            padding:
                15px 10px;

            position: sticky;

            bottom: 0;

            z-index: 10;

        }


        .input-area {

            display: flex;

            gap: 10px;

            margin:
                0 1rem;

        }


        .input-area input {

            width: 100%;

            padding: 10px;

            color:
                rgb(21, 32, 44);

            border-radius: 5px;

            border: none;

            outline: none;

        }


        .btnEnviar {

            padding:
                8px 19px;

            background:
                rgb(162, 201, 212);

            border: none;

            color:
                rgb(27, 40, 56);

            border-radius: 5px;

            cursor: pointer;

        }


        .btnEnviar:hover {

            border:
                2px solid
                rgb(162, 201, 212);

            background:
                rgb(27, 40, 56);

            color:
                rgb(162, 201, 212);

        }


        .aviso-login {

            text-align: center;

            padding: 12px;

        }


        .aviso-login a {

            color:
                rgb(162, 201, 212);

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media(max-width: 832px) {

            .containerjogo {

                width: 100%;

                border: none;

            }

            .logo-container {

                margin:
                    0 15%;

            }

        }


        @media(max-width: 480px) {

            .logo-container {

                height: 80px;

            }


            #comentarios {

                margin:
                    6% 3% 0;

                min-height: 630px;

            }


            .input-area {

                margin:
                    0;

            }


            .input-area input {

                font-size: 16px;

            }


            .btnEnviar {

                padding:
                    8px 14px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================
     FUNDO
========================================= -->

<div class="fundoimg">

    <?php if (!empty($jogo["banner"])): ?>

        <img
            src="<?= htmlspecialchars($jogo["banner"]) ?>"
            alt=""
        >

    <?php endif; ?>

</div>


<!-- =========================================
     VOLTAR
========================================= -->

<a
    href="index.php"
    class="Btn"
>

    <div class="sign">

        <svg viewBox="0 0 512 512">

            <path
                d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"
            />

        </svg>

    </div>


    <div class="text">
        Voltar
    </div>

</a>


<!-- =========================================
     PÁGINA
========================================= -->

<main class="containerjogo">


    <!-- LOGO DO JOGO -->

    <div class="logo-container">

        <?php if (!empty($jogo["logo"])): ?>

            <img
                src="<?= htmlspecialchars($jogo["logo"]) ?>"
                alt="<?= htmlspecialchars($jogo["nome"]) ?>"
            >

        <?php else: ?>

            <h1>
                <?= htmlspecialchars($jogo["nome"]) ?>
            </h1>

        <?php endif; ?>

    </div>


    <!-- PESQUISA -->

    <div class="linha-divisoria">

        <div class="input-box">

            <input
                placeholder="Pesquisar nos comentários..."
                type="text"
                id="pesquisaComentario"
            >

        </div>

    </div>


    <!-- =========================================
         COMENTÁRIOS
    ========================================== -->

    <div id="comentarios">


        <?php if ($comentarios->num_rows === 0): ?>

            <div
                style="
                    text-align:center;
                    padding:40px;
                "
            >

                <i
                    class="fa-solid fa-comments"
                    style="font-size:35px;"
                ></i>

                <p style="margin-top:15px;">

                    Ainda não existem comentários
                    neste jogo.

                </p>

            </div>


        <?php else: ?>


            <?php while ($comentario = $comentarios->fetch_assoc()): ?>


                <div
                    class="comentario"
                    data-texto="<?= htmlspecialchars(
                        strtolower(
                            $comentario["texto"]
                        )
                    ) ?>"
                >


                    <!-- USUÁRIO -->

                    <div class="topo">

                        <i
                            class="fa-solid fa-user icone"
                        ></i>


                        <a
                            href="perfil.php?id=<?= $comentario["usuario_id"] ?>"
                            class="nome"
                        >

                            <?= htmlspecialchars(
                                $comentario["nome"]
                            ) ?>

                        </a>


                        <span class="data">

                            <?= date(
                                "d/m/Y H:i",
                                strtotime(
                                    $comentario["data_criacao"]
                                )
                            ) ?>

                        </span>

                    </div>


                    <!-- TEXTO -->

                    <p class="texto-comentario">

                        <?= nl2br(
                            htmlspecialchars(
                                $comentario["texto"]
                            )
                        ) ?>

                    </p>


                    <!-- AÇÕES -->

                    <div class="acoes">


                        <!-- LIKE -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="acao"
                                value="interagir"
                            >

                            <input
                                type="hidden"
                                name="comentario_id"
                                value="<?= $comentario["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="tipo"
                                value="like"
                            >

                            <button type="submit">

                                👍
                                <?= $comentario["likes"] ?>

                            </button>

                        </form>


                        <!-- DISLIKE -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="acao"
                                value="interagir"
                            >

                            <input
                                type="hidden"
                                name="comentario_id"
                                value="<?= $comentario["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="tipo"
                                value="dislike"
                            >

                            <button type="submit">

                                👎
                                <?= $comentario["dislikes"] ?>

                            </button>

                        </form>


                    </div>


                </div>


            <?php endwhile; ?>


        <?php endif; ?>


    </div>


    <!-- =========================================
         BARRA DE COMENTÁRIO
    ========================================== -->


    <div class="barra-comentario">


        <?php if ($logado): ?>


            <form
                method="POST"
                class="input-area"
            >

                <input
                    type="hidden"
                    name="acao"
                    value="comentar"
                >


                <input
                    type="text"
                    name="comentario"
                    id="inputComentario"
                    maxlength="1000"
                    placeholder="Dê sua solução..."
                    autocomplete="off"
                    required
                >


                <button
                    class="btnEnviar"
                    type="submit"
                >

                    Enviar

                </button>


            </form>


        <?php else: ?>


            <div class="aviso-login">

                <a href="login.php">

                    Faça login

                </a>

                para comentar e interagir.

            </div>


        <?php endif; ?>


    </div>


</main>


<script>

// =============================================
// PESQUISAR COMENTÁRIOS
// =============================================

const pesquisa =
    document.getElementById(
        "pesquisaComentario"
    );


if (pesquisa) {

    pesquisa.addEventListener(
        "input",
        function () {

            const texto =
                this.value
                    .toLowerCase()
                    .trim();


            const comentarios =
                document.querySelectorAll(
                    ".comentario"
                );


            comentarios.forEach(
                function (comentario) {

                    const conteudo =
                        comentario.dataset.texto;


                    if (
                        conteudo.includes(texto)
                    ) {

                        comentario.style.display =
                            "block";

                    } else {

                        comentario.style.display =
                            "none";

                    }

                }
            );

        }
    );

}


// =============================================
// ENTER PARA ENVIAR
// =============================================

const input =
    document.getElementById(
        "inputComentario"
    );


if (input) {

    input.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Enter") {

                event.preventDefault();

                this.form.submit();

            }

        }
    );

}

</script>


</body>

</html>

<?php

$conn->close();

?>
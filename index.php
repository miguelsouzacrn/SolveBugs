<?php

// ==========================================
// CONEXÃO COM O BANCO
// ==========================================

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "solvebugs";

$conn = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


// ==========================================
// JOGOS MAIS COMENTADOS
// ==========================================

$sqlMaisComentados = "
    SELECT
        j.id,
        j.nome,
        j.banner,
        j.logo,
        COUNT(c.id) AS quantidade_comentarios

    FROM jogos j

    LEFT JOIN comentarios c
        ON c.jogo_id = j.id

    GROUP BY
        j.id,
        j.nome,
        j.banner,
        j.logo

    ORDER BY quantidade_comentarios DESC

    LIMIT 5
";

$maisComentados =
    $conn->query($sqlMaisComentados);


// ==========================================
// MAIS PESQUISADOS
// ==========================================
//
// Como ainda não temos uma tabela específica
// para pesquisas, vamos usar os jogos mais
// recentes como exemplo.
// Depois podemos criar um sistema de pesquisas.
//

$sqlMaisPesquisados = "
    SELECT
        id,
        nome,
        capa,
        logo

    FROM jogos

    ORDER BY id DESC

    LIMIT 5
";

$maisPesquisados =
    $conn->query($sqlMaisPesquisados);


// ==========================================
// RECÉM ADICIONADOS
// ==========================================

$sqlRecentes = "
    SELECT
        id,
        nome,
        capa,
        logo

    FROM jogos

    ORDER BY id DESC

    LIMIT 5
";

$recentes =
    $conn->query($sqlRecentes);


// ==========================================
// JOGOS NOVOS
// ==========================================

$sqlJogosNovos = "
    SELECT
        id,
        nome,
        capa,
        logo,
        data_lancamento

    FROM jogos

    WHERE data_lancamento IS NOT NULL

    ORDER BY data_lancamento DESC

    LIMIT 5
";

$jogosNovos =
    $conn->query($sqlJogosNovos);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        SolveBugs
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="icon"
        href="./img/favicon.ico">


    <style>
        /* =====================================
           RESET
        ===================================== */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

            color:
                rgb(162, 201, 212);

        }


        body {

            min-height: 100vh;

            background:
                rgb(20, 29, 41);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /* =====================================
           FUNDO
        ===================================== */

        .fundo {

            position: fixed;

            inset: 0;

            background-image:
                url('./img/background.png');

            background-size: cover;

            background-position: center;

            z-index: -2;

        }


        .fundo::after {

            content: "";

            position: absolute;

            inset: 0;

        }


        /* =====================================
           NAVBAR
        ===================================== */

        .navbar {

            background:
                rgba(20, 29, 41, .95);

            border-bottom:
                1px solid rgb(39, 57, 80);

        }


        .menu {

            min-height: 85px;

            padding:
                5px 3%;

        }


        .logo {

            width: 230px;

            height: 75px;

            object-fit: contain;

        }


        .acoes-direita {

            display: flex;

            align-items: center;

            gap: 15px;

        }


        /* =====================================
           PESQUISA
        ===================================== */

        .barraPesquisa {

            width: 300px;

        }


        .input-pesquisa {

            width: 100%;

            height: 40px;

            padding:
                0 18px;

            border:
                2px solid rgba(255, 255, 255, .15);

            border-radius: 30px;

            outline: none;

            background:
                transparent;

            color:
                rgb(162, 201, 212);

        }


        .input-pesquisa::placeholder {

            color:
                #c5c5c5;

        }


        .input-pesquisa:focus {

            border-color:
                rgb(162, 201, 212);

        }


        /* =====================================
           PERFIL
        ===================================== */

        .butPerfil {

            width: 45px;

            height: 45px;

            border: none;

            border-radius: 50%;

            background:
                rgb(39, 57, 80);

            cursor: pointer;

            overflow: hidden;

        }


        .imgBut {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        /* =====================================
           POPUP
        ===================================== */

        .overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0, 0, 0, .65);

            justify-content: center;

            align-items: center;

            z-index: 100;

        }


        .popupPerfil {

            width: 300px;

            padding: 25px;

            text-align: center;

            border-radius: 8px;

            background:
                rgb(27, 40, 56);

            border:
                1px solid rgb(68, 91, 119);

        }


        .imgPerfil {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            margin: 15px;

        }


        .popupPerfil ul {

            list-style: none;

            padding: 0;

        }


        .popupPerfil li {

            margin: 10px;

        }


        .popupPerfil a {

            text-decoration: none;

        }


        .close-btn {

            border: none;

            padding: 8px 20px;

            border-radius: 5px;

            background:
                rgb(162, 201, 212);

            color:
                rgb(27, 40, 56);

            cursor: pointer;

        }


        /* =====================================
           CONTEÚDO
        ===================================== */

        .conteudo {

            width: 90%;

            max-width: 1250px;

            margin: auto;

        }


        .titulo {

            margin-top: 40px;

            margin-bottom: 20px;

        }


        .titulo h1 {

            font-size: 30px;

        }


        /* =====================================
           CAROUSEL
        ===================================== */

        .carousel {

            border-radius: 8px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, .5);

        }


        .carousel-item {

            height: 450px;

            background:
                rgb(27, 40, 56);

        }


        .carousel-item img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .carousel-item::after {

            content: "";

            position: absolute;

            inset: 0;

            background:
                linear-gradient(transparent 35%,
                    rgba(0, 0, 0, .85));

        }


        .carousel-caption {

            z-index: 2;

            text-align: left;

            left: 5%;

        }


        .carousel-caption h2 {

            font-size: 32px;

        }


        /* =====================================
           LISTAS
        ===================================== */

        .container-lista {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-top: 35px;

        }


        .lista {

            background:
                rgba(27, 40, 56, .9);

            border:
                1px solid rgb(36, 53, 75);

            border-radius: 6px;

            padding: 20px;

        }


        .lista h3 {

            margin-bottom: 15px;

        }


        .lista ul {

            list-style: none;

            padding: 0;

        }


        .linha {

            margin-bottom: 8px;

        }


        .linha a {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 8px;

            text-decoration: none;

            border-radius: 5px;

            transition: .2s;

        }


        .linha a:hover {

            background:
                rgb(39, 57, 80);

        }


        .img {

            width: 45px;

            height: 45px;

            object-fit: cover;

            border-radius: 5px;

        }


        /* =====================================
           SOBRE
        ===================================== */

        .sobre {

            margin-top: 40px;

            padding: 30px;

            background:
                rgba(27, 40, 56, .9);

            border:
                1px solid rgb(36, 53, 75);

            border-radius: 6px;

            line-height: 1.7;

        }


        .sobre h2 {

            margin-bottom: 15px;

        }


        /* =====================================
           FOOTER
        ===================================== */

        .rodape {

            margin-top: 60px;

            background:
                rgb(20, 29, 41);

            border-top:
                1px solid rgb(39, 57, 80);

            padding:
                25px 5%;

        }


        .footer-content {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

        }


        .icones {

            display: flex;

            gap: 15px;

        }


        .icones a {

            text-decoration: none;

        }


        .icones svg {

            fill:
                rgb(162, 201, 212);

            transition: .2s;

        }


        .icones svg:hover {

            fill: white;

        }


        /* =====================================
           RESPONSIVO
        ===================================== */

        @media(max-width: 832px) {

            .menu {

                flex-direction: column;

                gap: 10px;

            }


            .acoes-direita {

                width: 100%;

                justify-content: center;

            }


            .barraPesquisa {

                width: 60%;

            }


            .container-lista {

                grid-template-columns:
                    1fr;

            }


            .carousel-item {

                height: 350px;

            }

        }


        @media(max-width: 480px) {

            .conteudo {

                width: 94%;

            }


            .logo {

                width: 190px;

            }


            .barraPesquisa {

                width: 100%;

            }


            .carousel-item {

                height: 250px;

            }


            .carousel-caption h2 {

                font-size: 22px;

            }


            .footer-content {

                flex-direction: column;

                gap: 20px;

                text-align: center;

            }

        }
    </style>

</head>


<body>


    <div class="fundo"></div>


    <!-- =====================================
     NAVBAR
===================================== -->

    <nav class="navbar navbar-dark">

        <div
            class="menu container-fluid
        d-flex justify-content-between
        align-items-center">


            <a href="index.php">

                <img
                    src="./img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
                    class="logo"
                    alt="SolveBugs">

            </a>


            <div class="acoes-direita">


                <form
                    action="jogos.php"
                    method="GET"
                    class="barraPesquisa">

                    <input
                        class="input-pesquisa"
                        type="text"
                        name="busca"
                        placeholder="Buscar jogo...">

                </form>


                <button
                    class="butPerfil"
                    onclick="abrirPopup()">

                    <img
                        class="imgBut"
                        src="./img/perfil.png"
                        alt="Perfil">

                </button>


            </div>

        </div>

    </nav>


    <!-- =====================================
     POPUP PERFIL
===================================== -->

    <div
        class="overlay"
        id="overlay">

        <div class="popupPerfil">

            <h2>
                Usuário
            </h2>


            <img
                class="imgPerfil"
                src="./img/perfil.png"
                alt="Perfil">


            <ul>

                <li>
                    <a href="./Login.php">
                        Login
                    </a>
                </li>

                <li>
                    <a href="./Cadastro.php">
                        Cadastrar-se
                    </a>
                </li>

            </ul>


            <button
                class="close-btn"
                onclick="fecharPopup()">
                Fechar
            </button>

        </div>

    </div>


    <!-- =====================================
     CONTEÚDO
===================================== -->

    <main class="conteudo">


        <div class="titulo">

            <h1>
                Mais Comentados!
            </h1>

        </div>


        <!-- =================================
         CAROUSEL
    ================================== -->

        <div
            id="carouselJogos"
            class="carousel slide"
            data-bs-ride="carousel">


            <div class="carousel-indicators">

                <?php

                $contador = 0;

                while (
                    $jogo =
                    $maisComentados->fetch_assoc()
                ) {

                ?>

                    <button
                        type="button"
                        data-bs-target="#carouselJogos"
                        data-bs-slide-to="<?= $contador ?>"
                        class="<?= $contador == 0 ? 'active' : '' ?>"></button>

                <?php

                    $contador++;
                }

                ?>

            </div>


            <div class="carousel-inner">


                <?php

                $maisComentados->data_seek(0);

                $contador = 0;

                while (
                    $jogo =
                    $maisComentados->fetch_assoc()
                ) {

                ?>


                    <div
                        class="carousel-item
                    <?= $contador == 0 ? 'active' : '' ?>">


                        <?php

                        if (!empty($jogo["banner"])) {

                        ?>

                            <img
                                src="<?= htmlspecialchars(
                                            $jogo["banner"]
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $jogo["nome"]
                                        ) ?>">

                        <?php

                        }

                        ?>


                        <div class="carousel-caption">

                            <h2>

                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>

                            </h2>

                            <p>

                                <?= $jogo["quantidade_comentarios"] ?>

                                comentários

                            </p>

                        </div>


                    </div>


                <?php

                    $contador++;
                }

                ?>


            </div>


            <button
                class="carousel-control-prev"
                type="button"
                data-bs-target="#carouselJogos"
                data-bs-slide="prev">

                <span
                    class="carousel-control-prev-icon"></span>

            </button>


            <button
                class="carousel-control-next"
                type="button"
                data-bs-target="#carouselJogos"
                data-bs-slide="next">

                <span
                    class="carousel-control-next-icon"></span>

            </button>


        </div>


        <!-- =================================
         LISTAS
    ================================== -->

        <div class="container-lista">


            <!-- MAIS PESQUISADOS -->

            <div class="lista">

                <h3>
                    Mais pesquisados
                </h3>

                <ul>

                    <?php

                    while (
                        $jogo =
                        $maisPesquisados->fetch_assoc()
                    ) {

                    ?>

                        <li class="linha">

                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">

                                <?php

                                if (
                                    !empty($jogo["capa"])
                                ) {

                                ?>

                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars(
                                                    $jogo["capa"]
                                                ) ?>">

                                <?php

                                }

                                ?>

                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>

                            </a>

                        </li>

                    <?php

                    }

                    ?>

                </ul>

            </div>


            <!-- RECÉM ADICIONADOS -->

            <div class="lista">

                <h3>
                    Recém adicionados
                </h3>

                <ul>

                    <?php

                    while (
                        $jogo =
                        $recentes->fetch_assoc()
                    ) {

                    ?>

                        <li class="linha">

                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">

                                <?php

                                if (
                                    !empty($jogo["capa"])
                                ) {

                                ?>

                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars(
                                                    $jogo["capa"]
                                                ) ?>">

                                <?php

                                }

                                ?>

                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>

                            </a>

                        </li>

                    <?php

                    }

                    ?>

                </ul>

            </div>


            <!-- JOGOS NOVOS -->

            <div class="lista">

                <h3>
                    Jogos novos
                </h3>

                <ul>

                    <?php

                    while (
                        $jogo =
                        $jogosNovos->fetch_assoc()
                    ) {

                    ?>

                        <li class="linha">

                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">

                                <?php

                                if (
                                    !empty($jogo["capa"])
                                ) {

                                ?>

                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars(
                                                    $jogo["capa"]
                                                ) ?>">

                                <?php

                                }

                                ?>

                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>

                            </a>

                        </li>

                    <?php

                    }

                    ?>

                </ul>

            </div>


        </div>


        <!-- =================================
         SOBRE
    ================================== -->

        <section class="sobre">

            <h2>
                Sobre o SolveBugs
            </h2>

            <p>

                O SolveBugs é uma plataforma destinada
                a auxiliar jogadores na resolução de
                problemas ou falhas em jogos.

            </p>

            <p>

                A plataforma permite que os usuários
                compartilhem problemas encontrados,
                discutam possíveis soluções e ajudem
                outros jogadores que estejam enfrentando
                as mesmas dificuldades.

            </p>

        </section>


    </main>


    <!-- =====================================
     FOOTER
===================================== -->

    <footer class="rodape">

        <div class="footer-content">


            <small>

                ©2026, SolveBugs LTDA.
                Todos os direitos reservados.

            </small>


            <div class="icones">

                <a
                    href="#"
                    title="Instagram">

                    Instagram

                </a>


                <a
                    href="#"
                    title="LinkedIn">

                    LinkedIn

                </a>


                <a
                    href="#"
                    title="X">

                    X

                </a>


                <a
                    href="#"
                    title="WhatsApp">

                    WhatsApp

                </a>

            </div>


        </div>

    </footer>


    <script>
        function abrirPopup() {

            document
                .getElementById("overlay")
                .style.display = "flex";

        }


        function fecharPopup() {

            document
                .getElementById("overlay")
                .style.display = "none";

        }
    </script>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>


<?php

$conn->close();

?>
<?php

// ==========================================
// AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . "/config/auth.php";


// Verifica se o usuário está logado
$estaLogado = isset($_SESSION["usuario_id"]);


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
// FOTO PADRÃO
// ==========================================

$fotoPerfil = "./img/perfilIcon.avif";


// ==========================================
// BUSCAR FOTO DO USUÁRIO LOGADO
// ==========================================

if ($estaLogado) {

    $idUsuario = $_SESSION["usuario_id"];


    $sqlUsuario = "
        SELECT foto_perfil
        FROM usuarios
        WHERE id = ?
    ";


    $stmtUsuario = $conn->prepare($sqlUsuario);


    if ($stmtUsuario) {

        $stmtUsuario->bind_param(
            "i",
            $idUsuario
        );


        $stmtUsuario->execute();


        $resultadoUsuario =
            $stmtUsuario->get_result();


        $dadosUsuario =
            $resultadoUsuario->fetch_assoc();


        // Se existir foto cadastrada
        if (
            $dadosUsuario &&
            !empty($dadosUsuario["foto_perfil"])
        ) {

            $fotoPerfil =
                $dadosUsuario["foto_perfil"];

        }


        $stmtUsuario->close();

    }

}


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

    <title>SolveBugs</title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="icon"
        href="./img/favicon.ico">

    <link rel="stylesheet" href="./css/index.css">

</head>


<body>


    <!-- FUNDO -->

    <div class="fundo"></div>


    <!-- =====================================
         NAVBAR
    ===================================== -->

    <nav class="navbar navbar-dark">


        <div
            class="
                menu
                container-fluid
                d-flex
                justify-content-between
                align-items-center
            ">


            <!-- LOGO -->

            <a href="index.php">

                <img
                    src="./img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
                    class="logo"
                    alt="SolveBugs">

            </a>


            <div class="acoes-direita">


                <!-- PESQUISA -->

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


                <!-- =====================================
                     PERFIL
                ====================================== -->

                <?php if ($estaLogado): ?>


                    <!-- USUÁRIO LOGADO -->

                    <a
                        href="Usuario.php"
                        class="butPerfil"
                        title="Minha conta">


                        <img
                            class="imgBut"
                            src="<?= htmlspecialchars($fotoPerfil) ?>"
                            alt="Meu perfil">


                    </a>


                <?php else: ?>


                    <!-- USUÁRIO NÃO LOGADO -->

                    <button
                        type="button"
                        class="butPerfil"
                        onclick="abrirPopup()"
                        title="Entrar">


                        <img
                            class="imgBut"
                            src="./img/perfilIcon.avif"
                            alt="Perfil">


                    </button>


                <?php endif; ?>


            </div>

        </div>

    </nav>


    <!-- =====================================
         POPUP PERFIL
    ===================================== -->

    <?php if (!$estaLogado): ?>


        <div
            class="overlay"
            id="overlay">


            <div class="popupPerfil">


                <h2>
                    Usuário
                </h2>


                <img
                    class="imgPerfil"
                    src="./img/perfilIcon.avif"
                    alt="Perfil">


                <ul>


                    <li>

                        <a href="./Login.php">

                            Login

                        </a>

                    </li>


                    <li>

                        <a href="./Cadastro_usuario.php">

                            Cadastrar-se

                        </a>

                    </li>


                </ul>


                <button
                    type="button"
                    class="close-btn"
                    onclick="fecharPopup()">

                    Fechar

                </button>


            </div>

        </div>


    <?php endif; ?>


    <!-- =====================================
         CONTEÚDO
    ===================================== -->

    <main class="conteudo">


        <div class="titulo">

            <h1>
                Mais Comentados!
            </h1>

        </div>


        <!-- =====================================
             CAROUSEL
        ===================================== -->

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
                        class="<?= $contador == 0 ? 'active' : '' ?>">
                    </button>


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
                        class="
                            carousel-item
                            <?= $contador == 0 ? 'active' : '' ?>
                        ">


                        <?php if (!empty($jogo["banner"])): ?>


                            <img
                                src="<?= htmlspecialchars($jogo["banner"]) ?>"
                                alt="<?= htmlspecialchars($jogo["nome"]) ?>">


                        <?php endif; ?>


                        <div class="carousel-caption">


                            <h2>

                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>

                            </h2>


                            <p>

                                <?= $jogo[
                                    "quantidade_comentarios"
                                ] ?>

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
                    class="carousel-control-prev-icon">
                </span>


            </button>


            <button
                class="carousel-control-next"
                type="button"
                data-bs-target="#carouselJogos"
                data-bs-slide="next">


                <span
                    class="carousel-control-next-icon">
                </span>


            </button>


        </div>


        <!-- =====================================
             LISTAS
        ===================================== -->

        <div class="container-lista">


            <!-- MAIS PESQUISADOS -->

            <div class="lista">


                <h3>
                    Mais pesquisados
                </h3>


                <ul>


                    <?php while (
                        $jogo =
                        $maisPesquisados->fetch_assoc()
                    ): ?>


                        <li class="linha">


                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">


                                <?php if (!empty($jogo["capa"])): ?>


                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars($jogo["capa"]) ?>"
                                        alt="<?= htmlspecialchars($jogo["nome"]) ?>">


                                <?php endif; ?>


                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>


                            </a>


                        </li>


                    <?php endwhile; ?>


                </ul>


            </div>


            <!-- RECÉM ADICIONADOS -->

            <div class="lista">


                <h3>
                    Recém adicionados
                </h3>


                <ul>


                    <?php while (
                        $jogo =
                        $recentes->fetch_assoc()
                    ): ?>


                        <li class="linha">


                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">


                                <?php if (!empty($jogo["capa"])): ?>


                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars($jogo["capa"]) ?>"
                                        alt="<?= htmlspecialchars($jogo["nome"]) ?>">


                                <?php endif; ?>


                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>


                            </a>


                        </li>


                    <?php endwhile; ?>


                </ul>


            </div>


            <!-- JOGOS NOVOS -->

            <div class="lista">


                <h3>
                    Jogos novos
                </h3>


                <ul>


                    <?php while (
                        $jogo =
                        $jogosNovos->fetch_assoc()
                    ): ?>


                        <li class="linha">


                            <a
                                href="jogo.php?id=<?= $jogo["id"] ?>">


                                <?php if (!empty($jogo["capa"])): ?>


                                    <img
                                        class="img"
                                        src="<?= htmlspecialchars($jogo["capa"]) ?>"
                                        alt="<?= htmlspecialchars($jogo["nome"]) ?>">


                                <?php endif; ?>


                                <?= htmlspecialchars(
                                    $jogo["nome"]
                                ) ?>


                            </a>


                        </li>


                    <?php endwhile; ?>


                </ul>


            </div>


        </div>


        <!-- =====================================
             SOBRE
        ===================================== -->

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


    <!-- =====================================
         JAVASCRIPT
    ===================================== -->

    <script>


        function abrirPopup() {

            const overlay =
                document.getElementById(
                    "overlay"
                );


            if (overlay) {

                overlay.style.display =
                    "flex";

            }

        }


        function fecharPopup() {

            const overlay =
                document.getElementById(
                    "overlay"
                );


            if (overlay) {

                overlay.style.display =
                    "none";

            }

        }


        const overlay =
            document.getElementById(
                "overlay"
            );


        if (overlay) {

            overlay.addEventListener(
                "click",
                function(event) {

                    if (
                        event.target ===
                        overlay
                    ) {

                        fecharPopup();

                    }

                }
            );

        }

    </script>


    <!-- BOOTSTRAP -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>


</body>

</html>


<?php

$conn->close();

?>
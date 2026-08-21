<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SolveBug</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Seu CSS -->
    <link rel="stylesheet" href="css/Menu.css">

    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico">
</head>

<body>

    <!-- =========================
         MENU
    ========================== -->
    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="menu container-fluid">

            <img
                src="img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
                class="logo"
                alt="SolveBug"
            >

            <div class="acoes-direita">

                <!-- Pesquisa -->
                <div class="barraPesquisa">
                    <input
                        class="input-pesquisa"
                        type="text"
                        placeholder="Buscar"
                    >
                </div>


                <!-- =========================
                     USUÁRIO LOGADO
                ========================== -->

                <?php if (isset($_SESSION["usuario_id"])): ?>

                    <a
                        class="perfil-link"
                        href="usuario.php"
                        title="Meu perfil"
                    >
                        <img
                            class="imgBut"
                            src="img/perfil.png"
                            alt="Perfil"
                        >
                    </a>


                <!-- =========================
                     USUÁRIO NÃO LOGADO
                ========================== -->

                <?php else: ?>

                    <button
                        class="perfil-link"
                        onclick="abrirPopup()"
                    >
                        <img
                            class="imgBut"
                            src="img/perfil.png"
                            alt="Perfil"
                        >
                    </button>

                <?php endif; ?>

            </div>

        </div>

    </nav>


    <!-- =========================
         POPUP DO USUÁRIO
    ========================== -->

    <?php if (!isset($_SESSION["usuario_id"])): ?>

        <div class="overlay" id="overlay">

            <div class="popupPerfil">

                <h2>Usuário</h2>

                <img
                    class="imgPerfil"
                    src="img/perfil.png"
                    alt="Perfil"
                >

                <ul>
                    <li>
                        <a href="login.php">Login</a>
                    </li>

                    <li>
                        <a href="cadastro.php">Cadastrar-se</a>
                    </li>
                </ul>

                <button
                    class="close-btn"
                    onclick="fecharPopup()"
                >
                    Fechar
                </button>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================
         TÍTULO
    ========================== -->

    <div class="titulo">
        <h1>Mais Comentados!</h1>
    </div>


    <!-- =========================
         CARROSSEL
    ========================== -->

    <div
        id="carouselExampleAutoplaying"
        class="carousel slide"
        data-bs-ride="carousel"
    >

        <!-- Indicadores -->
        <div class="carousel-indicators">

            <button
                type="button"
                data-bs-target="#carouselExampleAutoplaying"
                data-bs-slide-to="0"
                class="active"
                aria-current="true"
            ></button>

            <button
                type="button"
                data-bs-target="#carouselExampleAutoplaying"
                data-bs-slide-to="1"
            ></button>

            <button
                type="button"
                data-bs-target="#carouselExampleAutoplaying"
                data-bs-slide-to="2"
            ></button>

        </div>


        <!-- Slides -->
        <div class="carousel-inner">

            <div class="carousel-item active">

                <img
                    src="img/Cyberpunk 2077.img"
                    class="d-block w-100"
                    alt="Cyberpunk 2077"
                >

                <div class="carousel-caption">
                    <h5>Cyberpunk 2077</h5>
                </div>

            </div>


            <div class="carousel-item">

                <img
                    src="img/oblivionBanner.jpg"
                    class="d-block w-100"
                    alt="Oblivion"
                >

                <div class="carousel-caption">
                    <h5>Oblivion</h5>
                </div>

            </div>


            <div class="carousel-item">

                <img
                    src="img/FALLOUTnewBanner.avif"
                    class="d-block w-100"
                    alt="Fallout 76"
                >

                <div class="carousel-caption">
                    <h5>FALLOUT 76</h5>
                </div>

            </div>

        </div>


        <!-- Botão anterior -->
        <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#carouselExampleAutoplaying"
            data-bs-slide="prev"
        >
            <span class="carousel-control-prev-icon"></span>
        </button>


        <!-- Botão próximo -->
        <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#carouselExampleAutoplaying"
            data-bs-slide="next"
        >
            <span class="carousel-control-next-icon"></span>
        </button>

    </div>


    <!-- =========================
         LISTAS DE JOGOS
    ========================== -->

    <div class="container-lista">

        <!-- Mais pesquisados -->
        <div class="lista1">

            <h3>Mais pesquisados</h3>

            <ul>

                <li class="linha">
                    <a href="jogo.php">
                        <img class="img" src="img/Ark.avif">
                        ARK Survival Evolved
                    </a>
                </li>

                <li class="linha">
                    <a href="Jogo-Terraria.php">
                        <img class="img" src="img/terraria.png">
                        Terraria
                    </a>
                </li>

                <li class="linha">
                    <a href="Jogo-Brawlhalla.php">
                        <img class="img" src="img/brawlhalla.webp">
                        Brawlhalla
                    </a>
                </li>

            </ul>

        </div>


        <!-- Recém adicionados -->
        <div class="lista2">

            <h3>Recém adicionados</h3>

            <ul>

                <li class="linha">
                    <a href="jogo-Cyberpunk.php">
                        <img class="img" src="img/cyberpunk_logo.webp">
                        Cyberpunk 2077
                    </a>
                </li>

                <li class="linha">
                    <a href="jogo-DeadbyDaylight.php">
                        <img class="img" src="img/DbDLogo.jpg">
                        Dead by Daylight
                    </a>
                </li>

                <li class="linha">
                    <a href="jogo-Minecraft.php">
                        <img class="img" src="img/minecraft.avif">
                        Minecraft
                    </a>
                </li>

            </ul>

        </div>


        <!-- Jogos novos -->
        <div class="lista3">

            <h3>Jogos Novos</h3>

            <ul>

                <li class="linha">
                    <a href="jogo-Hytale.php">
                        <img class="img" src="img/Hytale_logo2.png">
                        Hytale
                    </a>
                </li>

                <li class="linha">
                    <a href="jogo-FC26.php">
                        <img class="img" src="img/fc26-logo.avif">
                        EA SPORTS FC™ 26
                    </a>
                </li>

                <li class="linha">
                    <a href="jogo-Resident.php">
                        <img class="img" src="img/resident-evil-requiem-log.jpg">
                        Resident Evil Requiem
                    </a>
                </li>

            </ul>

        </div>

    </div>


    <!-- =========================
         TEXTO
    ========================== -->

    <div class="text">

        Um site destinado a auxiliar usuários na resolução de
        problemas ou falhas em jogos. A plataforma contará com uma
        seção de relatos, na qual os próprios usuários poderão
        compartilhar as soluções adotadas, contribuindo para ajudar
        outros jogadores que enfrentam as mesmas dificuldades.

    </div>


    <!-- =========================
         FOOTER
    ========================== -->

    <footer class="rodape">

        <div class="menu_nav_footer">

            <nav class="d-flex justify-content-between align-items-center">

                <div class="logo-descriptions">

                    <small>
                        ©2026, SolveBugs LTDA. Todos os direitos reservados.
                    </small>

                </div>


                <ul class="icone-rodape d-flex list-unstyled mb-0 gap-3">

                    <li>
                        <a href="#" title="Instagram">
                            Instagram
                        </a>
                    </li>

                    <li>
                        <a href="#" title="LinkedIn">
                            LinkedIn
                        </a>
                    </li>

                    <li>
                        <a href="#" title="X">
                            X
                        </a>
                    </li>

                    <li>
                        <a href="#" title="WhatsApp">
                            WhatsApp
                        </a>
                    </li>

                </ul>

            </nav>

        </div>

    </footer>


    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous">
    </script>


    <!-- Popup -->
    <script>

        function abrirPopup() {
            document.getElementById("overlay").style.display = "flex";
        }

        function fecharPopup() {
            document.getElementById("overlay").style.display = "none";
        }

    </script>

</body>
</html>
<?php

// ==========================================
// AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . "/config/auth.php";

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
// DADOS DO USUÁRIO
// ==========================================

$fotoPerfil = "./img/perfilIcon.avif";
$tipoUsuario = "usuario";
$nomeUsuario = "";

if ($estaLogado) {

    $idUsuario = intval($_SESSION["usuario_id"]);

    $stmtUsuario = $conn->prepare("
        SELECT
            nome,
            foto_perfil,
            tipo
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");

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

        if ($dadosUsuario) {

            $nomeUsuario =
                $dadosUsuario["nome"] ?? "";

            $tipoUsuario =
                $dadosUsuario["tipo"] ?? "usuario";

            if (
                !empty($dadosUsuario["foto_perfil"])
            ) {

                $fotoPerfil =
                    $dadosUsuario["foto_perfil"];

            }
        }

        $stmtUsuario->close();
    }
}


// ==========================================
// MAIS COMENTADOS
// ==========================================

$sqlMaisComentados = "

    SELECT
        j.id,
        j.nome,
        j.banner,
        j.capa,
        j.logo,
        j.descricao,

        COUNT(c.id) AS quantidade_comentarios

    FROM jogos j

    LEFT JOIN comentarios c
        ON c.jogo_id = j.id

    GROUP BY
        j.id,
        j.nome,
        j.banner,
        j.capa,
        j.logo,
        j.descricao

    ORDER BY
        quantidade_comentarios DESC,
        j.id DESC

    LIMIT 5

";

$maisComentados =
    $conn->query($sqlMaisComentados);


// ==========================================
// MAIS FAVORITADOS
// ==========================================

$sqlMaisFavoritados = "

    SELECT
        j.id,
        j.nome,
        j.capa,
        j.logo,

        COUNT(f.jogo_id) AS quantidade_favoritos

    FROM jogos j

    LEFT JOIN favoritos f
        ON f.jogo_id = j.id

    GROUP BY
        j.id,
        j.nome,
        j.capa,
        j.logo

    ORDER BY
        quantidade_favoritos DESC,
        j.id DESC

    LIMIT 5

";

$maisFavoritados =
    $conn->query($sqlMaisFavoritados);


// ==========================================
// RECÉM ADICIONADOS
//
// Como a tabela jogos não possui
// data_criacao/data_adicao, usamos o ID.
// IDs maiores representam cadastros mais recentes.
// ==========================================

$sqlRecentes = "

    SELECT
        j.id,
        j.nome,
        j.capa,
        j.logo,
        j.descricao

    FROM jogos j

    ORDER BY
        j.id DESC

    LIMIT 5

";

$recentes =
    $conn->query($sqlRecentes);


// ==========================================
// JOGOS NOVOS
// ==========================================

$sqlJogosNovos = "

    SELECT
        j.id,
        j.nome,
        j.capa,
        j.logo,
        j.data_lancamento

    FROM jogos j

    WHERE j.data_lancamento IS NOT NULL

    ORDER BY
        j.data_lancamento DESC

    LIMIT 5

";

$jogosNovos =
    $conn->query($sqlJogosNovos);


// ==========================================
// JOGOS EM DESTAQUE
//
// Pegamos os mais comentados.
// ==========================================

$listaDestaques = [];

if ($maisComentados) {

    while (
        $jogo =
        $maisComentados->fetch_assoc()
    ) {

        $listaDestaques[] = $jogo;

    }

}


// ==========================================
// FUNÇÃO PARA ESCAPAR HTML
// ==========================================

function e($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==========================================
// FUNÇÃO PARA DATA
// ==========================================

function formatarData($data)
{

    if (
        empty($data) ||
        $data === "0000-00-00"
    ) {

        return "Data não informada";

    }

    $timestamp =
        strtotime($data);

    if (!$timestamp) {

        return "Data não informada";

    }

    return date(
        "d/m/Y",
        $timestamp
    );
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
        SolveBugs
    </title>


    <!-- FAVICON -->

    <link
        rel="icon"
        href="./img/favicon.ico"
    >


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >


    <!-- GOOGLE FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="./css/index.css"
    >

</head>


<body>


<!-- ==========================================
     FUNDO
========================================== -->

<div class="fundo"></div>


<!-- ==========================================
     NAVBAR
========================================== -->

<nav class="navbar navbar-dark">

    <div class="menu container-fluid">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo-link"
        >

            <img
                src="./img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
                class="logo"
                alt="SolveBugs"
            >

        </a>


        <!-- AÇÕES -->

        <div class="acoes-direita">


            <!-- BUSCA -->

            <form
                action="jogos.php"
                method="GET"
                class="barraPesquisa"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    class="input-pesquisa"
                    type="text"
                    name="busca"
                    placeholder="Buscar jogo..."
                    autocomplete="off"
                >

            </form>


            <!-- ==========================================
                 ADICIONAR JOGO
                 
                 FICA VISÍVEL PARA QUALQUER PESSOA
                 NÃO EXISTE MAIS VERIFICAÇÃO DE ADMIN
            ========================================== -->

            <a
                href="cadastro_jogos.php"
                class="btnAdicionarJogo"
                title="Adicionar novo jogo"
            >

                <i class="fa-solid fa-plus"></i>

                <span>
                    Adicionar jogo
                </span>

            </a>


            <!-- ==========================================
                 PERFIL
            ========================================== -->

            <?php if ($estaLogado): ?>


                <a
                    href="Usuario.php"
                    class="butPerfil"
                    title="Minha conta"
                >

                    <img
                        class="imgBut"
                        src="<?= e($fotoPerfil) ?>"
                        alt="Meu perfil"
                    >

                </a>


            <?php else: ?>


                <!-- PERFIL DESLOGADO -->

                <button
                    type="button"
                    class="butPerfil"
                    onclick="abrirPopup()"
                    title="Entrar"
                >

                    <img
                        class="imgBut"
                        src="./img/perfilIcon.avif"
                        alt="Perfil"
                    >

                </button>

            <?php endif; ?>


        </div>

    </div>

</nav>


<!-- ==========================================
     POPUP LOGIN
========================================== -->

<?php if (!$estaLogado): ?>

<div
    class="overlay"
    id="overlay"
>

    <div class="popupPerfil">


        <button
            type="button"
            class="popup-fechar"
            onclick="fecharPopup()"
            aria-label="Fechar"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="popup-icone">

            <i class="fa-solid fa-user"></i>

        </div>


        <h2>
            Bem-vindo!
        </h2>


        <p>
            Entre na sua conta para participar
            da comunidade SolveBugs.
        </p>


        <div class="popup-acoes">

            <a
                href="./Login.php"
                class="popup-login"
            >

                <i class="fa-solid fa-right-to-bracket"></i>

                Login

            </a>


            <a
                href="./Cadastro_usuario.php"
                class="popup-cadastro"
            >

                <i class="fa-solid fa-user-plus"></i>

                Cadastrar-se

            </a>

        </div>


    </div>

</div>

<?php endif; ?>


<!-- ==========================================
     CONTEÚDO
========================================== -->

<main class="conteudo">


    <!-- ======================================
         BEM-VINDO
    ======================================= -->

    <?php if ($estaLogado): ?>

        <section class="boas-vindas">

            <div>

                <span class="boas-vindas-label">
                    BEM-VINDO DE VOLTA
                </span>

                <h1>

                    Olá,
                    <strong>
                        <?= e($nomeUsuario) ?>
                    </strong>!

                </h1>

                <p>
                    Encontre problemas, compartilhe
                    soluções e ajude outros jogadores.
                </p>

            </div>

            <div class="boas-vindas-icone">

                <i class="fa-solid fa-gamepad"></i>

            </div>

        </section>

    <?php else: ?>

        <section class="boas-vindas">

            <div>

                <span class="boas-vindas-label">
                    COMUNIDADE GAMER
                </span>

                <h1>
                    Encontre a solução para seu bug.
                </h1>

                <p>
                    Pesquise jogos, encontre problemas
                    e descubra soluções compartilhadas
                    por outros jogadores.
                </p>

            </div>

            <div class="boas-vindas-icone">

                <i class="fa-solid fa-bug"></i>

            </div>

        </section>

    <?php endif; ?>


    <!-- ======================================
         TÍTULO
    ======================================= -->

    <div class="titulo">

        <span class="titulo-linha"></span>

        <h2>
            Jogos em destaque
        </h2>

        <span class="titulo-linha"></span>

    </div>


    <!-- ======================================
         CAROUSEL
    ======================================= -->

    <?php if (count($listaDestaques) > 0): ?>

        <div
            id="carouselJogos"
            class="carousel slide carousel-solvebugs"
            data-bs-ride="carousel"
        >


            <!-- INDICADORES -->

            <div class="carousel-indicators">

                <?php foreach (
                    $listaDestaques
                    as $indice => $jogo
                ): ?>

                    <button
                        type="button"
                        data-bs-target="#carouselJogos"
                        data-bs-slide-to="<?= $indice ?>"
                        class="<?= $indice === 0 ? 'active' : '' ?>"
                        aria-label="Slide <?= $indice + 1 ?>"
                    ></button>

                <?php endforeach; ?>

            </div>


            <!-- SLIDES -->

            <div class="carousel-inner">

                <?php foreach (
                    $listaDestaques
                    as $indice => $jogo
                ): ?>

                    <div
                        class="carousel-item
                        <?= $indice === 0 ? 'active' : '' ?>"
                    >


                        <?php if (
                            !empty($jogo["banner"])
                        ): ?>

                            <img
                                src="<?= e($jogo["banner"]) ?>"
                                class="carousel-banner"
                                alt="<?= e($jogo["nome"]) ?>"
                            >

                        <?php elseif (
                            !empty($jogo["capa"])
                        ): ?>

                            <img
                                src="<?= e($jogo["capa"]) ?>"
                                class="carousel-banner"
                                alt="<?= e($jogo["nome"]) ?>"
                            >

                        <?php else: ?>

                            <div class="carousel-sem-imagem">

                                <i class="fa-solid fa-gamepad"></i>

                            </div>

                        <?php endif; ?>


                        <div class="carousel-overlay"></div>


                        <div class="carousel-caption">

                            <span class="badge-destaque">

                                <i class="fa-solid fa-fire"></i>

                                Mais comentado

                            </span>


                            <h2>
                                <?= e($jogo["nome"]) ?>
                            </h2>


                            <p>

                                <i class="fa-solid fa-comments"></i>

                                <?= intval(
                                    $jogo["quantidade_comentarios"]
                                ) ?>

                                comentários

                            </p>


                            <a
                                href="jogo.php?id=<?= intval($jogo["id"]) ?>"
                                class="btnVerJogo"
                            >

                                Ver jogo

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- ANTERIOR -->

            <button
                class="carousel-control-prev"
                type="button"
                data-bs-target="#carouselJogos"
                data-bs-slide="prev"
            >

                <span
                    class="carousel-control-prev-icon"
                ></span>

            </button>


            <!-- PRÓXIMO -->

            <button
                class="carousel-control-next"
                type="button"
                data-bs-target="#carouselJogos"
                data-bs-slide="next"
            >

                <span
                    class="carousel-control-next-icon"
                ></span>

            </button>

        </div>

    <?php else: ?>

        <div class="sem-jogos">

            <i class="fa-solid fa-gamepad"></i>

            <h3>
                Ainda não existem jogos cadastrados.
            </h3>


            <!-- ==========================================
                 ADICIONAR PRIMEIRO JOGO
                 
                 TAMBÉM FICA DISPONÍVEL PARA TODOS
            ========================================== -->

            <a
                href="adicionar_jogo.php"
                class="btnAdicionarGrande"
            >

                <i class="fa-solid fa-plus"></i>

                Adicionar primeiro jogo

            </a>

        </div>

    <?php endif; ?>


    <!-- ======================================
         SEÇÕES DE JOGOS
    ====================================== -->


    <!-- ======================================
         MAIS COMENTADOS
    ====================================== -->

    <section class="secao-jogos">

        <div class="cabecalho-secao">

            <div>

                <span class="mini-titulo">
                    COMUNIDADE
                </span>

                <h2>

                    <i class="fa-solid fa-comments"></i>

                    Mais comentados

                </h2>

            </div>


            <a
                href="jogos.php"
                class="ver-todos"
            >

                Ver todos

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="grid-jogos">

            <?php

            // Reexecutamos a consulta porque
            // o resultado anterior foi utilizado
            // no carousel.

            $resultadoMaisComentados =
                $conn->query($sqlMaisComentados);

            ?>


            <?php if (
                $resultadoMaisComentados &&
                $resultadoMaisComentados->num_rows > 0
            ): ?>


                <?php while (
                    $jogo =
                    $resultadoMaisComentados->fetch_assoc()
                ): ?>

                    <a
                        href="jogo.php?id=<?= intval($jogo["id"]) ?>"
                        class="card-jogo"
                    >

                        <div class="card-imagem">

                            <?php if (
                                !empty($jogo["capa"])
                            ): ?>

                                <img
                                    src="<?= e($jogo["capa"]) ?>"
                                    alt="<?= e($jogo["nome"]) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="sem-capa">

                                    <i class="fa-solid fa-gamepad"></i>

                                </div>

                            <?php endif; ?>


                            <span class="numero-card">

                                #<?= intval(
                                    $jogo["quantidade_comentarios"]
                                ) ?>

                            </span>

                        </div>


                        <div class="card-info">

                            <h3>
                                <?= e($jogo["nome"]) ?>
                            </h3>


                            <span>

                                <i class="fa-solid fa-comments"></i>

                                <?= intval(
                                    $jogo["quantidade_comentarios"]
                                ) ?>

                                comentários

                            </span>

                        </div>

                    </a>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="sem-resultados">

                    Nenhum jogo possui comentários ainda.

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ======================================
         MAIS FAVORITADOS
    ====================================== -->

    <section class="secao-jogos">

        <div class="cabecalho-secao">

            <div>

                <span class="mini-titulo">
                    PREFERIDOS DA COMUNIDADE
                </span>

                <h2>

                    <i class="fa-solid fa-star"></i>

                    Mais favoritos

                </h2>

            </div>


            <a
                href="jogos.php"
                class="ver-todos"
            >

                Ver todos

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="grid-jogos">

            <?php if (
                $maisFavoritados &&
                $maisFavoritados->num_rows > 0
            ): ?>


                <?php while (
                    $jogo =
                    $maisFavoritados->fetch_assoc()
                ): ?>

                    <a
                        href="jogo.php?id=<?= intval($jogo["id"]) ?>"
                        class="card-jogo"
                    >

                        <div class="card-imagem">

                            <?php if (
                                !empty($jogo["capa"])
                            ): ?>

                                <img
                                    src="<?= e($jogo["capa"]) ?>"
                                    alt="<?= e($jogo["nome"]) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="sem-capa">

                                    <i class="fa-solid fa-gamepad"></i>

                                </div>

                            <?php endif; ?>


                            <span class="numero-card favorito">

                                <i class="fa-solid fa-star"></i>

                                <?= intval(
                                    $jogo["quantidade_favoritos"]
                                ) ?>

                            </span>

                        </div>


                        <div class="card-info">

                            <h3>
                                <?= e($jogo["nome"]) ?>
                            </h3>


                            <span>

                                <i class="fa-solid fa-star"></i>

                                <?= intval(
                                    $jogo["quantidade_favoritos"]
                                ) ?>

                                favoritos

                            </span>

                        </div>

                    </a>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="sem-resultados">

                    Ainda não existem jogos favoritados.

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ======================================
         RECÉM ADICIONADOS
    ====================================== -->

    <section class="secao-jogos">

        <div class="cabecalho-secao">

            <div>

                <span class="mini-titulo">
                    NOVOS NO SOLVEBUGS
                </span>

                <h2>

                    <i class="fa-solid fa-clock"></i>

                    Recém adicionados

                </h2>

            </div>


            <a
                href="jogos.php"
                class="ver-todos"
            >

                Ver todos

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="grid-jogos">

            <?php if (
                $recentes &&
                $recentes->num_rows > 0
            ): ?>


                <?php while (
                    $jogo =
                    $recentes->fetch_assoc()
                ): ?>

                    <a
                        href="jogo.php?id=<?= intval($jogo["id"]) ?>"
                        class="card-jogo"
                    >

                        <div class="card-imagem">

                            <?php if (
                                !empty($jogo["capa"])
                            ): ?>

                                <img
                                    src="<?= e($jogo["capa"]) ?>"
                                    alt="<?= e($jogo["nome"]) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="sem-capa">

                                    <i class="fa-solid fa-gamepad"></i>

                                </div>

                            <?php endif; ?>


                            <span class="novo-card">
                                NOVO
                            </span>

                        </div>


                        <div class="card-info">

                            <h3>
                                <?= e($jogo["nome"]) ?>
                            </h3>


                            <span>

                                <i class="fa-solid fa-gamepad"></i>

                                Ver detalhes

                            </span>

                        </div>

                    </a>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="sem-resultados">

                    Nenhum jogo cadastrado.

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ======================================
         JOGOS NOVOS
    ====================================== -->

    <section class="secao-jogos">

        <div class="cabecalho-secao">

            <div>

                <span class="mini-titulo">
                    LANÇAMENTOS
                </span>

                <h2>

                    <i class="fa-solid fa-calendar-days"></i>

                    Jogos novos

                </h2>

            </div>


            <a
                href="jogos.php"
                class="ver-todos"
            >

                Ver todos

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="grid-jogos">

            <?php if (
                $jogosNovos &&
                $jogosNovos->num_rows > 0
            ): ?>


                <?php while (
                    $jogo =
                    $jogosNovos->fetch_assoc()
                ): ?>

                    <a
                        href="jogo.php?id=<?= intval($jogo["id"]) ?>"
                        class="card-jogo"
                    >

                        <div class="card-imagem">

                            <?php if (
                                !empty($jogo["capa"])
                            ): ?>

                                <img
                                    src="<?= e($jogo["capa"]) ?>"
                                    alt="<?= e($jogo["nome"]) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="sem-capa">

                                    <i class="fa-solid fa-gamepad"></i>

                                </div>

                            <?php endif; ?>


                            <span class="data-card">

                                <?= formatarData(
                                    $jogo["data_lancamento"]
                                ) ?>

                            </span>

                        </div>


                        <div class="card-info">

                            <h3>
                                <?= e($jogo["nome"]) ?>
                            </h3>


                            <span>

                                <i class="fa-solid fa-calendar"></i>

                                Lançamento:
                                <?= formatarData(
                                    $jogo["data_lancamento"]
                                ) ?>

                            </span>

                        </div>

                    </a>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="sem-resultados">

                    Nenhum jogo possui data de lançamento cadastrada.

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ======================================
         SOBRE
    ====================================== -->

    <section class="sobre">


        <div class="sobre-icone">

            <i class="fa-solid fa-bug"></i>

        </div>


        <div class="sobre-texto">

            <span class="mini-titulo">
                SOBRE A PLATAFORMA
            </span>


            <h2>
                O que é o SolveBugs?
            </h2>


            <p>

                O SolveBugs é uma plataforma criada
                para ajudar jogadores a encontrar
                soluções para problemas e bugs
                encontrados em seus jogos.

            </p>


            <p>

                Aqui, a comunidade pode compartilhar
                experiências, discutir problemas,
                responder outros jogadores e encontrar
                soluções de forma colaborativa.

            </p>

        </div>


    </section>


</main>


<!-- ==========================================
     FOOTER
========================================== -->

<footer class="rodape">

    <div class="footer-content">


        <div class="footer-logo">

            <img
                src="./img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
                alt="SolveBugs"
            >

        </div>


        <div>

            <small>

                ©2026 SolveBugs LTDA.
                Todos os direitos reservados.

            </small>

        </div>


        <div class="icones">

            <a href="#" title="Instagram">

                <i class="fa-brands fa-instagram"></i>

            </a>


            <a href="#" title="LinkedIn">

                <i class="fa-brands fa-linkedin"></i>

            </a>


            <a href="#" title="X">

                <i class="fa-brands fa-x-twitter"></i>

            </a>


            <a href="#" title="WhatsApp">

                <i class="fa-brands fa-whatsapp"></i>

            </a>

        </div>


    </div>

</footer>


<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>


// ==========================================
// POPUP
// ==========================================

function abrirPopup()
{

    const overlay =
        document.getElementById("overlay");

    if (overlay) {

        overlay.classList.add("ativo");

        document.body.classList.add("popup-aberto");

    }

}


function fecharPopup()
{

    const overlay =
        document.getElementById("overlay");

    if (overlay) {

        overlay.classList.remove("ativo");

        document.body.classList.remove("popup-aberto");

    }

}


// ==========================================
// FECHAR CLICANDO FORA
// ==========================================

const overlay =
    document.getElementById("overlay");

if (overlay) {

    overlay.addEventListener(
        "click",
        function(event) {

            if (
                event.target === overlay
            ) {

                fecharPopup();

            }

        }
    );

}


// ==========================================
// ESC FECHA POPUP
// ==========================================

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Escape"
        ) {

            fecharPopup();

        }

    }
);


</script>


<!-- BOOTSTRAP -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>


<?php

$conn->close();

?>
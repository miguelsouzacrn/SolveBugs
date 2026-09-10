
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
// BUSCA
// ==========================================

$busca = "";

if (isset($_GET["busca"])) {
    $busca = trim($_GET["busca"]);
}


// ==========================================
// BUSCAR JOGOS
// ==========================================

if ($busca !== "") {

    $sql = "
        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.banner,
            j.capa,
            j.logo,
            j.data_lancamento,
            d.nome AS desenvolvedora

        FROM jogos j

        LEFT JOIN desenvolvedoras d
            ON j.desenvolvedora_id = d.id

        WHERE j.nome LIKE ?

        ORDER BY j.nome
    ";

    $stmt = $conn->prepare($sql);

    $termo = "%" . $busca . "%";

    $stmt->bind_param(
        "s",
        $termo
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

} else {

    $sql = "
        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.banner,
            j.capa,
            j.logo,
            j.data_lancamento,
            d.nome AS desenvolvedora

        FROM jogos j

        LEFT JOIN desenvolvedoras d
            ON j.desenvolvedora_id = d.id

        ORDER BY j.nome
    ";

    $resultado = $conn->query($sql);
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
        Jogos - SolveBugs
    </title>


    <style>

        /* ==================================
           RESET
        ================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

            color:
                rgb(162, 201, 212);
        }


        /* ==================================
           BODY
        ================================== */

        body {

            min-height: 100vh;

            background:
                rgb(20, 29, 41);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            padding-bottom: 50px;

        }


        /* ==================================
           FUNDO
        ================================== */

        .fundoimg {

            position: fixed;

            width: 100%;
            height: 100dvh;

            top: 0;
            left: 0;

            background-image:
                url("https://image.api.playstation.com/vulcan/ap/rnd/202111/3013/bxSj4jO0KBqUgAbH3zuNjCje.jpg");

            background-size: cover;

            background-position: center;

            z-index: -2;

        }


        .fundoimg::after {

            content: "";

            position: absolute;

            width: 100%;
            height: 100%;

            background:
                rgba(20, 29, 41, .72);

        }


        /* ==================================
           CONTAINER
        ================================== */

        .containerjogo {

            width: 73%;

            max-width: 1100px;

            min-height: 100vh;

            margin: auto;

            background:
                rgba(27, 40, 56, .88);

            border:
                2px solid
                rgba(68, 91, 119, .7);

            padding:
                30px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .5);

        }


        /* ==================================
           CABEÇALHO
        ================================== */

        .cabecalho {

            text-align: center;

            margin-bottom: 25px;

        }


        .cabecalho h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .cabecalho p {

            color:
                #c5c5c5;

        }


        /* ==================================
           LINHA
        ================================== */

        .linha-divisoria {

            width: 100%;

            height: 1px;

            background:
                rgba(162, 201, 212, .3);

            margin:
                20px 0;

        }


        /* ==================================
           BUSCA
        ================================== */

        .busca {

            display: flex;

            gap: 10px;

            margin-bottom: 30px;

        }


        .busca input {

            flex: 1;

            height: 45px;

            background:
                rgba(20, 29, 41, .9);

            border:
                2px solid
                rgba(255, 255, 255, .15);

            border-radius: 5px;

            outline: none;

            padding:
                0 15px;

            font-size: 16px;

            color:
                rgb(162, 201, 212);

        }


        .busca input:focus {

            border-color:
                rgb(162, 201, 212);

        }


        .btnBusca {

            padding:
                0 20px;

            border: none;

            border-radius: 5px;

            background:
                rgb(162, 201, 212);

            color:
                rgb(27, 40, 56);

            font-weight: bold;

            cursor: pointer;

            transition: .2s;

        }


        .btnBusca:hover {

            background:
                rgb(27, 40, 56);

            color:
                rgb(162, 201, 212);

            border:
                2px solid
                rgb(162, 201, 212);

        }


        /* ==================================
           GRID DOS JOGOS
        ================================== */

        .jogos {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

        }


        /* ==================================
           CARD
        ================================== */

        .card {

            background:
                rgb(31, 46, 65);

            border:
                1px solid
                rgb(36, 53, 75);

            border-radius: 6px;

            overflow: hidden;

            box-shadow:
                0 4px 10px
                rgba(0, 0, 0, .35);

            transition:
                transform .2s,
                box-shadow .2s;

        }


        .card:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, .55);

        }


        /* ==================================
           CAPA
        ================================== */

        .capa {

            width: 100%;

            height: 300px;

            object-fit: cover;

            display: block;

        }


        /* ==================================
           CONTEÚDO
        ================================== */

        .conteudo {

            padding:
                15px;

        }


        .nome {

            font-size: 21px;

            margin-bottom: 8px;

        }


        .descricao {

            color:
                #c5c5c5;

            font-size: 14px;

            line-height: 1.4;

            min-height: 58px;

            margin-bottom: 12px;

        }


        .desenvolvedora {

            font-size: 13px;

            margin-bottom: 12px;

        }


        .desenvolvedora span {

            color:
                #c5c5c5;

        }


        /* ==================================
           CATEGORIAS
        ================================== */

        .categorias {

            display: flex;

            flex-wrap: wrap;

            gap: 5px;

            margin-bottom: 12px;

        }


        .categoria {

            background:
                rgb(39, 57, 80);

            padding:
                5px 8px;

            border-radius: 4px;

            font-size: 11px;

        }


        /* ==================================
           BOTÃO
        ================================== */

        .btnJogo {

            display: block;

            width: 100%;

            padding:
                10px;

            text-align: center;

            text-decoration: none;

            background:
                rgb(162, 201, 212);

            color:
                rgb(27, 40, 56);

            border-radius: 5px;

            font-weight: bold;

            transition: .2s;

        }


        .btnJogo:hover {

            background:
                rgb(27, 40, 56);

            color:
                rgb(162, 201, 212);

            border:
                2px solid
                rgb(162, 201, 212);

        }


        /* ==================================
           SEM RESULTADOS
        ================================== */

        .sem-jogos {

            text-align: center;

            padding:
                60px 20px;

            color:
                #c5c5c5;

        }


        /* ==================================
           BOTÃO VOLTAR
        ================================== */

        .Btn {

            position: fixed;

            top: 15px;
            left: 15px;

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

            z-index: 10;

        }


        .sign {

            width: 45px;
            min-width: 45px;

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

            width: 0;

            opacity: 0;

            color: white;

            font-size: 14px;

            transition: .3s;

            white-space: nowrap;

        }


        .Btn:hover {

            width: 90px;

            border-radius: 40px;

            background:
                #B82c46;

        }


        .Btn:hover .text {

            opacity: 1;

            width: 60%;

        }


        /* ==================================
           832PX
        ================================== */

        @media (max-width: 832px) {

            .containerjogo {

                width: 90%;

                padding: 25px;

            }


            .jogos {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .capa {

                height: 280px;

            }

        }


        /* ==================================
           600PX
        ================================== */

        @media (max-width: 600px) {

            body {

                padding:
                    20px 10px;

            }


            .containerjogo {

                width: 100%;

                padding:
                    20px 15px;

            }


            .jogos {

                grid-template-columns:
                    1fr;

            }


            .capa {

                height: 350px;

            }


            .cabecalho h1 {

                font-size: 25px;

            }


            .busca {

                flex-direction: column;

            }


            .btnBusca {

                height: 42px;

            }

        }

    </style>

</head>


<body>


<div class="fundoimg"></div>


<!-- =====================================
     BOTÃO VOLTAR
===================================== -->

<a
    href="index.php"
    class="Btn"
>

    <div class="sign">

        <svg
            viewBox="0 0 24 24"
            fill="none"
        >

            <path
                d="M20 11H7.83l5.59-5.59L12 4l-8 8
                   8 8 1.41-1.41L7.83 13H20v-2z"
            />

        </svg>

    </div>

    <div class="text">
        Voltar
    </div>

</a>


<!-- =====================================
     CONTAINER
===================================== -->

<main class="containerjogo">


    <div class="cabecalho">

        <h1>
            Jogos
        </h1>

        <p>
            Encontre jogos e compartilhe
            seus problemas com a comunidade.
        </p>

    </div>


    <div class="linha-divisoria"></div>


    <!-- =================================
         BUSCA
    ================================== -->

    <form
        method="GET"
        class="busca"
    >

        <input
            type="text"
            name="busca"
            placeholder="Pesquisar jogo..."
            value="<?= htmlspecialchars($busca) ?>"
        >

        <button
            type="submit"
            class="btnBusca"
        >
            Pesquisar
        </button>

    </form>


    <!-- =================================
         JOGOS
    ================================== -->

    <div class="jogos">

        <?php

        if ($resultado->num_rows > 0) {

            while (
                $jogo =
                $resultado->fetch_assoc()
            ) {

                ?>

                <article class="card">


                    <!-- CAPA -->

                    <?php

                    if (
                        !empty($jogo["capa"])
                    ) {

                    ?>

                        <img
                            class="capa"
                            src="<?= htmlspecialchars(
                                $jogo["capa"]
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $jogo["nome"]
                            ) ?>"
                        >

                    <?php

                    } else {

                    ?>

                        <div
                            class="capa"
                            style="
                                display:flex;
                                justify-content:center;
                                align-items:center;
                                background:#182433;
                            "
                        >
                            Sem capa
                        </div>

                    <?php

                    }

                    ?>


                    <div class="conteudo">


                        <!-- NOME -->

                        <h2 class="nome">

                            <?= htmlspecialchars(
                                $jogo["nome"]
                            ) ?>

                        </h2>


                        <!-- DESCRIÇÃO -->

                        <p class="descricao">

                            <?= htmlspecialchars(
                                $jogo["descricao"]
                                ?: "Nenhuma descrição disponível."
                            ) ?>

                        </p>


                        <!-- DESENVOLVEDORA -->

                        <?php

                        if (
                            !empty(
                                $jogo["desenvolvedora"]
                            )
                        ) {

                        ?>

                            <p class="desenvolvedora">

                                Desenvolvedora:

                                <span>

                                    <?= htmlspecialchars(
                                        $jogo["desenvolvedora"]
                                    ) ?>

                                </span>

                            </p>

                        <?php

                        }


                        // =================================
                        // CATEGORIAS
                        // =================================

                        $stmtCat =
                            $conn->prepare(
                                "SELECT c.nome
                                 FROM categorias c
                                 INNER JOIN jogos_categorias jc
                                 ON c.id = jc.id_categoria
                                 WHERE jc.id_jogo = ?
                                 ORDER BY c.nome"
                            );

                        $stmtCat->bind_param(
                            "i",
                            $jogo["id"]
                        );

                        $stmtCat->execute();

                        $resultCat =
                            $stmtCat->get_result();

                        ?>


                        <div class="categorias">

                            <?php

                            while (
                                $cat =
                                $resultCat->fetch_assoc()
                            ) {

                            ?>

                                <span class="categoria">

                                    <?= htmlspecialchars(
                                        $cat["nome"]
                                    ) ?>

                                </span>

                            <?php

                            }

                            $stmtCat->close();

                            ?>

                        </div>


                        <!-- BOTÃO -->

                        <a
                            href="jogo.php?id=<?= $jogo["id"] ?>"
                            class="btnJogo"
                        >

                            Ver jogo

                        </a>


                    </div>

                </article>

                <?php

            }

        } else {

        ?>

            <div class="sem-jogos">

                <h2>
                    Nenhum jogo encontrado
                </h2>

                <p>
                    Tente pesquisar por outro nome.
                </p>

            </div>

        <?php

        }

        ?>

    </div>


</main>


</body>

</html>

<?php

$conn->close();

?>


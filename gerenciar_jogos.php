
<?php

// ==========================================
// PROTEÇÃO ADMIN
// ==========================================

require_once __DIR__ . "/config/auth.php";

exigirLogin();

if (
    !isset($_SESSION["usuario_tipo"]) ||
    $_SESSION["usuario_tipo"] !== "admin"
) {
    header("Location: index.php");
    exit;
}


// ==========================================
// CONEXÃO
// ==========================================

require_once __DIR__ . "/config/conn.php";


// ==========================================
// EXCLUIR JOGO
// ==========================================

if (
    isset($_POST["acao"]) &&
    $_POST["acao"] === "excluir"
) {

    $jogoId = intval($_POST["jogo_id"] ?? 0);

    if ($jogoId > 0) {

        try {

            $pdo->beginTransaction();


            // ==================================
            // BUSCAR IMAGENS DO JOGO
            // ==================================

            $stmt = $pdo->prepare("
                SELECT banner, capa, logo, background
                FROM jogos
                WHERE id = ?
            ");

            $stmt->execute([$jogoId]);

            $jogo = $stmt->fetch();


            // ==================================
            // BUSCAR IMAGENS ADICIONAIS
            // ==================================

            $stmtImagens = $pdo->prepare("
                SELECT imagem
                FROM imagens_jogo
                WHERE jogo_id = ?
            ");

            $stmtImagens->execute([$jogoId]);

            $imagens = $stmtImagens->fetchAll();


            // ==================================
            // EXCLUIR CATEGORIAS
            // ==================================

            $stmt = $pdo->prepare("
                DELETE FROM jogos_categorias
                WHERE id_jogo = ?
            ");

            $stmt->execute([$jogoId]);


            // ==================================
            // EXCLUIR PLATAFORMAS
            // ==================================

            $stmt = $pdo->prepare("
                DELETE FROM jogo_plataforma
                WHERE jogo_id = ?
            ");

            $stmt->execute([$jogoId]);


            // ==================================
            // EXCLUIR IMAGENS ADICIONAIS
            // ==================================

            $stmt = $pdo->prepare("
                DELETE FROM imagens_jogo
                WHERE jogo_id = ?
            ");

            $stmt->execute([$jogoId]);


            // ==================================
            // EXCLUIR JOGO
            // ==================================

            $stmt = $pdo->prepare("
                DELETE FROM jogos
                WHERE id = ?
            ");

            $stmt->execute([$jogoId]);


            $pdo->commit();


            // ==================================
            // APAGAR ARQUIVOS
            // ==================================

            if ($jogo) {

                $arquivos = [

                    $jogo["banner"],

                    $jogo["capa"],

                    $jogo["logo"],

                    $jogo["background"]
                    
                ];


                foreach ($arquivos as $arquivo) {

                    if (
                        !empty($arquivo)
                    ) {

                        $caminho =
                            __DIR__ .
                            "/" .
                            $arquivo;


                        if (
                            file_exists($caminho)
                        ) {

                            unlink($caminho);

                        }

                    }

                }

            }


            // ==================================
            // APAGAR IMAGENS ADICIONAIS
            // ==================================

            foreach ($imagens as $imagem) {

                if (
                    !empty($imagem["imagem"])
                ) {

                    $caminho =
                        __DIR__ .
                        "/" .
                        $imagem["imagem"];


                    if (
                        file_exists($caminho)
                    ) {

                        unlink($caminho);

                    }

                }

            }


            header(
                "Location: gerenciar_jogos.php?sucesso=excluido"
            );

            exit;

        }

        catch (Exception $e) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            header(
                "Location: gerenciar_jogos.php?erro=excluir"
            );

            exit;

        }

    }

}


// ==========================================
// BUSCA
// ==========================================

$busca =
    trim(
        $_GET["busca"] ?? ""
    );


// ==========================================
// BUSCAR JOGOS
// ==========================================

if ($busca !== "") {

    $stmt = $pdo->prepare("

        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.capa,
            j.data_lancamento,
            d.nome AS desenvolvedora

        FROM jogos j

        LEFT JOIN desenvolvedoras d
        ON j.desenvolvedora_id = d.id

        WHERE j.nome LIKE ?

        ORDER BY j.nome

    ");

    $stmt->execute([
        "%" . $busca . "%"
    ]);

}

else {

    $stmt = $pdo->query("

        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.capa,
            j.data_lancamento,
            d.nome AS desenvolvedora

        FROM jogos j

        LEFT JOIN desenvolvedoras d
        ON j.desenvolvedora_id = d.id

        ORDER BY j.nome

    ");

}


$jogos =
    $stmt->fetchAll();

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
        Gerenciar Jogos - SolveBugs
    </title>


    <style>


        /* =====================================
           RESET
        ====================================== */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }


        /* =====================================
           BODY
        ====================================== */

        body {

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                rgb(20, 29, 41);

            color:
                rgb(162, 201, 212);

        }


        /* =====================================
           FUNDO
        ====================================== */

        .fundoimg {

            position: fixed;

            width: 100%;

            height: 100vh;

            top: 0;

            left: 0;

            z-index: -2;

            background-image:

                url(
                    "https://image.api.playstation.com/vulcan/ap/rnd/202111/3013/bxSj4jO0KBqUgAbH3zuNjCje.jpg"
                );

            background-size: cover;

            background-position: center;

        }


        .fundoimg::after {

            content: "";

            position: absolute;

            width: 100%;

            height: 100%;

            background:
                rgba(
                    20,
                    29,
                    41,
                    .82
                );

        }


        /* =====================================
           CONTAINER
        ====================================== */

        .container {

            width: 85%;

            max-width: 1200px;

            min-height: 100vh;

            margin: auto;

            padding: 35px;

            background:
                rgba(
                    27,
                    40,
                    56,
                    .92
                );

            border-left:
                2px solid
                rgba(
                    68,
                    91,
                    119,
                    .7
                );

            border-right:
                2px solid
                rgba(
                    68,
                    91,
                    119,
                    .7
                );

        }


        /* =====================================
           VOLTAR
        ====================================== */

        .voltar {

            display: inline-block;

            margin-bottom: 25px;

            padding:
                10px
                18px;

            background:
                rgb(
                    39,
                    57,
                    80
                );

            color:
                white;

            text-decoration: none;

            border-radius: 6px;

            transition: .2s;

        }


        .voltar:hover {

            background:
                #B82c46;

        }


        /* =====================================
           CABEÇALHO
        ====================================== */

        .cabecalho {

            margin-bottom: 30px;

        }


        .cabecalho h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .cabecalho p {

            color:
                #c5c5c5;

        }


        /* =====================================
           LINHA
        ====================================== */

        .linha {

            width: 100%;

            height: 1px;

            background:
                rgba(
                    162,
                    201,
                    212,
                    .3
                );

            margin:
                25px 0;

        }


        /* =====================================
           MENSAGENS
        ====================================== */

        .mensagem {

            padding: 15px;

            margin-bottom: 20px;

            border-radius: 5px;

        }


        .sucesso {

            background:
                rgba(
                    30,
                    120,
                    70,
                    .4
                );

            border:
                1px solid
                #4caf50;

            color:
                #d7ffd9;

        }


        .erro {

            background:
                rgba(
                    150,
                    40,
                    40,
                    .4
                );

            border:
                1px solid
                #ff5555;

            color:
                #ffd5d5;

        }


        /* =====================================
           TOPO
        ====================================== */

        .acoes-topo {

            display: flex;

            justify-content:
                space-between;

            align-items:
                center;

            gap: 20px;

            margin-bottom: 25px;

        }


        /* =====================================
           BOTÃO CADASTRAR
        ====================================== */

        .btn-cadastrar {

            padding:
                12px
                18px;

            background:
                rgb(
                    162,
                    201,
                    212
                );

            color:
                rgb(
                    27,
                    40,
                    56
                );

            text-decoration: none;

            font-weight: bold;

            border-radius: 5px;

            white-space: nowrap;

        }


        .btn-cadastrar:hover {

            background:
                white;

        }


        /* =====================================
           BUSCA
        ====================================== */

        .busca {

            display: flex;

            gap: 10px;

            flex: 1;

        }


        .busca input {

            width: 100%;

            height: 45px;

            padding:
                0
                15px;

            background:
                rgb(
                    20,
                    29,
                    41
                );

            color:
                rgb(
                    162,
                    201,
                    212
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .2
                );

            border-radius: 5px;

            outline: none;

        }


        .busca button {

            padding:
                0
                20px;

            border: none;

            border-radius: 5px;

            cursor: pointer;

            background:
                rgb(
                    39,
                    57,
                    80
                );

            color: white;

        }


        /* =====================================
           TABELA
        ====================================== */

        .tabela-container {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            text-align: left;

            padding: 15px;

            background:
                rgb(
                    39,
                    57,
                    80
                );

        }


        td {

            padding: 15px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .08
                );

        }


        tr:hover {

            background:
                rgba(
                    255,
                    255,
                    255,
                    .03
                );

        }


        /* =====================================
           CAPA
        ====================================== */

        .capa {

            width: 70px;

            height: 90px;

            object-fit: cover;

            border-radius: 5px;

            background:
                rgb(
                    20,
                    29,
                    41
                );

        }


        /* =====================================
           DESCRIÇÃO
        ====================================== */

        .descricao {

            max-width: 300px;

            color:
                #c5c5c5;

            font-size: 14px;

        }


        /* =====================================
           BOTÕES
        ====================================== */

        .acoes {

            display: flex;

            gap: 8px;

        }


        .btn-editar {

            padding:
                8px
                12px;

            border-radius: 5px;

            background:
                rgb(
                    162,
                    201,
                    212
                );

            color:
                rgb(
                    27,
                    40,
                    56
                );

            text-decoration: none;

            font-weight: bold;

        }


        .btn-excluir {

            padding:
                8px
                12px;

            border: none;

            border-radius: 5px;

            cursor: pointer;

            background:
                #B82c46;

            color: white;

            font-weight: bold;

        }


        /* =====================================
           SEM JOGOS
        ====================================== */

        .sem-jogos {

            text-align: center;

            padding: 50px;

            color:
                #c5c5c5;

        }


        /* =====================================
           RESPONSIVO
        ====================================== */

        @media (
            max-width: 800px
        ) {

            .container {

                width: 95%;

                padding: 20px;

            }


            .acoes-topo {

                flex-direction:
                    column;

                align-items:
                    stretch;

            }


        }


    </style>

</head>


<body>


<div class="fundoimg"></div>


<main class="container">


    <!-- =====================================
         VOLTAR
    ====================================== -->

    <a
        href="usuario.php"
        class="voltar"
    >

        ← Voltar para minha conta

    </a>



    <!-- =====================================
         CABEÇALHO
    ====================================== -->

    <div class="cabecalho">

        <h1>
            ⚙️ Gerenciar jogos
        </h1>

        <p>
            Edite ou exclua os jogos cadastrados
            no SolveBugs.
        </p>

    </div>


    <div class="linha"></div>



    <!-- =====================================
         MENSAGENS
    ====================================== -->

    <?php if (
        isset($_GET["sucesso"]) &&
        $_GET["sucesso"] === "excluido"
    ): ?>

        <div class="mensagem sucesso">

            Jogo excluído com sucesso!

        </div>

    <?php endif; ?>


    <?php if (
        isset($_GET["erro"]) &&
        $_GET["erro"] === "excluir"
    ): ?>

        <div class="mensagem erro">

            Não foi possível excluir o jogo.

        </div>

    <?php endif; ?>



    <!-- =====================================
         AÇÕES
    ====================================== -->

    <div class="acoes-topo">


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

            <button type="submit">

                🔍 Pesquisar

            </button>

        </form>



        <a
            href="cadastro_jogos.php"
            class="btn-cadastrar"
        >

            + Cadastrar jogo

        </a>


    </div>



    <!-- =====================================
         TABELA
    ====================================== -->

    <div class="tabela-container">


        <?php if (
            count($jogos) > 0
        ): ?>


            <table>


                <thead>

                    <tr>

                        <th>
                            Capa
                        </th>

                        <th>
                            Nome
                        </th>

                        <th>
                            Desenvolvedora
                        </th>

                        <th>
                            Descrição
                        </th>

                        <th>
                            Ações
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach (
                        $jogos
                        as $jogo
                    ): ?>


                        <tr>


                            <!-- CAPA -->

                            <td>

                                <?php if (
                                    !empty(
                                        $jogo["capa"]
                                    )
                                ): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $jogo["capa"]
                                        ) ?>"
                                        class="capa"
                                        alt="<?= htmlspecialchars(
                                            $jogo["nome"]
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    Sem capa

                                <?php endif; ?>

                            </td>



                            <!-- NOME -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $jogo["nome"]
                                    ) ?>

                                </strong>

                            </td>



                            <!-- DESENVOLVEDORA -->

                            <td>

                                <?= htmlspecialchars(
                                    $jogo["desenvolvedora"]
                                    ??
                                    "Não informada"
                                ) ?>

                            </td>



                            <!-- DESCRIÇÃO -->

                            <td
                                class="descricao"
                            >

                                <?= htmlspecialchars(

                                    mb_strimwidth(

                                        $jogo["descricao"]
                                        ??
                                        "",

                                        0,

                                        120,

                                        "..."

                                    )

                                ) ?>

                            </td>



                            <!-- AÇÕES -->

                            <td>


                                <div
                                    class="acoes"
                                >


                                    <a
                                        href="editar_jogo.php?id=<?= $jogo["id"] ?>"
                                        class="btn-editar"
                                    >

                                        ✏️ Editar

                                    </a>



                                    <form
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Tem certeza que deseja excluir este jogo?'
                                            );
                                        "
                                    >


                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir"
                                        >


                                        <input
                                            type="hidden"
                                            name="jogo_id"
                                            value="<?= $jogo["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="btn-excluir"
                                        >

                                            🗑️ Excluir

                                        </button>


                                    </form>


                                </div>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>


            </table>


        <?php else: ?>


            <div class="sem-jogos">

                <h2>
                    Nenhum jogo encontrado
                </h2>

                <br>

                <p>
                    Ainda não existem jogos cadastrados
                    ou nenhum jogo corresponde à pesquisa.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>

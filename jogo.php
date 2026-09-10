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
        background,
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
// PROCESSAR POST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =================================================
    // COMENTAR / RESPONDER
    // =================================================

    if (
        isset($_POST["acao"]) &&
        $_POST["acao"] === "comentar"
    ) {

        // Precisa estar logado
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: login.php");
            exit;
        }

        $usuario_id = intval($_SESSION["usuario_id"]);

        $texto = trim(
            $_POST["comentario"] ?? ""
        );

        // Por padrão é comentário principal
        $comentario_pai_id = null;

        // Se veio um comentário pai, é uma resposta.
        // IMPORTANTE:
        // Mantemos no banco o comentário exato que foi respondido.
        // Na exibição, todas as respostas serão "achatadas" visualmente
        // para ficarem em apenas 2 níveis, como no YouTube.
        if (
            isset($_POST["comentario_pai_id"]) &&
            $_POST["comentario_pai_id"] !== ""
        ) {

            $comentario_pai_id =
                intval($_POST["comentario_pai_id"]);

            // =================================================
            // VERIFICAR SE O COMENTÁRIO PAI REALMENTE EXISTE
            // E PERTENCE AO JOGO ATUAL
            // =================================================

            $stmt = $conn->prepare("
                SELECT id
                FROM comentarios
                WHERE id = ?
                AND jogo_id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $comentario_pai_id,
                $jogo_id
            );

            $stmt->execute();

            $resultadoPai = $stmt->get_result();

            if ($resultadoPai->num_rows === 0) {
                $comentario_pai_id = null;
            }

            $stmt->close();
        }


        // =================================================
        // INSERIR COMENTÁRIO
        // =================================================

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


        // Voltar para o jogo
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

        // Precisa estar logado
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: login.php");
            exit;
        }

        $usuario_id =
            intval($_SESSION["usuario_id"]);

        $comentario_id =
            intval($_POST["comentario_id"] ?? 0);

        $tipo =
            $_POST["tipo"] ?? "";


        // =================================================
        // VALIDAR TIPO
        // =================================================

        if (
            $tipo !== "like" &&
            $tipo !== "dislike"
        ) {

            header(
                "Location: jogo.php?id=" . $jogo_id
            );

            exit;
        }


        // =================================================
        // VERIFICAR SE COMENTÁRIO EXISTE
        // E PERTENCE AO JOGO
        // =================================================

        $stmt = $conn->prepare("
            SELECT id
            FROM comentarios
            WHERE id = ?
            AND jogo_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $comentario_id,
            $jogo_id
        );

        $stmt->execute();

        $resultadoComentario =
            $stmt->get_result();

        if ($resultadoComentario->num_rows === 0) {

            $stmt->close();

            header(
                "Location: jogo.php?id=" . $jogo_id
            );

            exit;
        }

        $stmt->close();


        // =================================================
        // VERIFICAR INTERAÇÃO EXISTENTE
        // =================================================

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


        // =================================================
        // JÁ EXISTE INTERAÇÃO
        // =================================================

        if ($interacao) {

            // Clicou novamente no mesmo botão
            if ($interacao["tipo"] === $tipo) {

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

                // Trocar like por dislike
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

            // =================================================
            // PRIMEIRA INTERAÇÃO
            // =================================================

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

        u.foto_perfil,

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


$stmt = $conn->prepare(
    $sqlComentarios
);

$stmt->bind_param(
    "i",
    $jogo_id
);

$stmt->execute();

$resultadoComentarios =
    $stmt->get_result();

$stmt->close();


// =====================================================
// TRANSFORMAR RESULTADO EM ARRAY
// =====================================================

$comentarios = [];

while (
    $comentario =
    $resultadoComentarios->fetch_assoc()
) {

    $comentarios[] = $comentario;
}


// =====================================================
// CRIAR ÍNDICE POR ID
// =====================================================

$comentariosPorId = [];

foreach ($comentarios as $comentario) {

    $comentariosPorId[
        intval($comentario["id"])
    ] = $comentario;
}


// =====================================================
// FUNÇÃO: DESCOBRIR O COMENTÁRIO PRINCIPAL
// =====================================================
//
// Mesmo que no banco existam respostas de respostas de respostas,
// visualmente todas elas serão colocadas abaixo do comentário principal.
//
// Exemplo no banco:
//
// Comentário 1
//   └ Resposta 2
//       └ Resposta 3
//           └ Resposta 4
//
// Exibição:
//
// Comentário 1
//   ├ Resposta 2
//   ├ @Resposta 2 - Resposta 3
//   └ @Resposta 3 - Resposta 4
//

function descobrirComentarioPrincipal(
    $comentario,
    $comentariosPorId
) {

    $paiId = intval(
        $comentario["comentario_pai_id"] ?? 0
    );

    if ($paiId <= 0) {
        return intval($comentario["id"]);
    }

    $visitados = [];

    while (
        $paiId > 0 &&
        isset($comentariosPorId[$paiId])
    ) {

        // Evita loop caso haja algum dado inconsistente no banco
        if (isset($visitados[$paiId])) {
            break;
        }

        $visitados[$paiId] = true;

        $pai = $comentariosPorId[$paiId];

        if (empty($pai["comentario_pai_id"])) {
            return intval($pai["id"]);
        }

        $paiId = intval(
            $pai["comentario_pai_id"]
        );
    }

    return intval($comentario["id"]);
}


// =====================================================
// ORGANIZAR EM APENAS 2 NÍVEIS VISUAIS
// =====================================================

$comentariosPrincipais = [];

$respostas = [];

foreach ($comentarios as $comentario) {

    if (empty($comentario["comentario_pai_id"])) {

        $comentariosPrincipais[] =
            $comentario;

        continue;
    }

    $paiImediatoId =
        intval($comentario["comentario_pai_id"]);

    // Nome exato da pessoa que recebeu a resposta
    $comentario["respondendo_nome"] = "";

    if (
        isset(
            $comentariosPorId[
                $paiImediatoId
            ]
        )
    ) {

        $comentario["respondendo_nome"] =
            $comentariosPorId[
                $paiImediatoId
            ]["nome"];
    }

    // Descobrir a raiz da conversa
    $comentarioPrincipalId =
        descobrirComentarioPrincipal(
            $comentario,
            $comentariosPorId
        );

    // Se por algum motivo a raiz não existir,
    // trata como comentário principal para não "sumir"
    if (
        !isset(
            $comentariosPorId[
                $comentarioPrincipalId
            ]
        ) ||
        !empty(
            $comentariosPorId[
                $comentarioPrincipalId
            ]["comentario_pai_id"]
        )
    ) {

        $comentariosPrincipais[] =
            $comentario;

        continue;
    }

    $respostas[
        $comentarioPrincipalId
    ][] = $comentario;
}


// =====================================================
// USUÁRIO ATUAL
// =====================================================

$logado =
    isset($_SESSION["usuario_id"]);

$usuarioAtual =
    $logado
    ? intval($_SESSION["usuario_id"])
    : 0;


// =====================================================
// FUNÇÕES VISUAIS
// =====================================================

function fotoPerfilComentario($usuario) {

    $foto = trim(
        $usuario["foto_perfil"] ?? ""
    );

    if ($foto !== "") {

        ?>
        <img
            src="<?= htmlspecialchars($foto) ?>"
            alt="Foto de <?= htmlspecialchars($usuario["nome"]) ?>"
            class="foto-perfil-comentario"
        >
        <?php

    } else {

        ?>
        <div class="foto-perfil-comentario foto-padrao">
            <i class="fa-solid fa-user"></i>
        </div>
        <?php
    }
}


function botoesComentario(
    $comentario,
    $logado
) {

    ?>

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
                value="<?= intval($comentario["id"]) ?>"
            >

            <input
                type="hidden"
                name="tipo"
                value="like"
            >

            <button
                type="submit"
                class="btn-acao"
                title="Curtir"
            >
                <i class="fa-regular fa-thumbs-up"></i>
                <?= intval($comentario["likes"]) ?>
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
                value="<?= intval($comentario["id"]) ?>"
            >

            <input
                type="hidden"
                name="tipo"
                value="dislike"
            >

            <button
                type="submit"
                class="btn-acao"
                title="Não curtir"
            >
                <i class="fa-regular fa-thumbs-down"></i>
                <?= intval($comentario["dislikes"]) ?>
            </button>

        </form>


        <!-- RESPONDER -->

        <?php if ($logado): ?>

            <button
                type="button"
                class="btn-responder"
                onclick='responderComentario(
                    <?= intval($comentario["id"]) ?>,
                    <?= json_encode(
                        $comentario["nome"],
                        JSON_HEX_TAG |
                        JSON_HEX_APOS |
                        JSON_HEX_AMP |
                        JSON_HEX_QUOT
                    ) ?>
                )'
            >
                Responder
            </button>

        <?php endif; ?>

    </div>

    <?php
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
                2px solid rgba(68, 91, 119, .7);

            border-right:
                2px solid rgba(68, 91, 119, .7);

            padding-top: 20px;

            box-shadow:
                0 5px 10px rgba(0, 0, 0, .5);
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


        .logo-container h1 {

            text-align: center;

            padding-top: 25px;
        }


        /* =========================================
           PESQUISA
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
                2px solid rgba(255, 255, 255, .2);

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

            border-radius: 8px;

            border:
                1px solid rgb(36, 53, 75);

            min-height: 480px;

            background:
                rgba(20, 29, 41, 1);

            padding:
                18px 16px;
        }


        /*
            Cada .thread é uma conversa completa:
            1 comentário principal + todas as respostas.
        */
        .thread {

            padding:
                8px 4px 18px;

            border-bottom:
                1px solid rgba(162, 201, 212, .10);

            margin-bottom: 12px;
        }


        .thread:last-child {

            border-bottom: none;
        }


        .comentario {

            background: transparent;

            padding: 8px 4px;

            border-radius: 8px;
        }


        .conteudo-comentario {

            min-width: 0;

            flex: 1;
        }


        /* =========================================
           FOTO DE PERFIL
        ========================================= */

        .foto-perfil-comentario {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            object-fit: cover;

            flex-shrink: 0;

            background:
                rgb(39, 57, 80);

            border:
                1px solid
                rgba(162, 201, 212, .25);
        }


        .foto-padrao {

            display: flex;

            align-items: center;

            justify-content: center;

            color:
                rgb(162, 201, 212);

            font-size: 16px;
        }


        /* =========================================
           TOPO COMENTÁRIO
        ========================================= */

        .linha-comentario {

            display: flex;

            align-items: flex-start;

            gap: 12px;
        }


        .topo {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;

            margin-bottom: 4px;
        }


        .topo a {

            color:
                rgb(220, 235, 240);

            text-decoration: none;
        }


        .topo a:hover {

            color: white;
        }


        .nome {

            font-weight: bold;

            font-size: 14px;
        }


        .data {

            font-size: 11px;

            color:
                #8fa0a8;
        }


        /* =========================================
           TEXTO
        ========================================= */

        .texto-comentario {

            margin:
                4px 0 7px;

            word-wrap: break-word;

            overflow-wrap: anywhere;

            white-space: normal;

            color:
                rgb(205, 220, 225);

            line-height: 1.45;

            font-size: 14px;
        }


        .mencao {

            color:
                #6eb8ff;

            font-weight: bold;

            margin-right: 5px;
        }


        /* =========================================
           AÇÕES
        ========================================= */

        .acoes {

            display: flex;

            align-items: center;

            gap: 4px;

            margin-top: 4px;

            flex-wrap: wrap;
        }


        .acoes form {

            display: inline;
        }


        .acoes button {

            min-height: 30px;

            padding:
                4px 9px;

            border: none;

            border-radius: 18px;

            background: transparent;

            color:
                rgb(150, 175, 183);

            cursor: pointer;

            font-size: 13px;

            transition: .2s;
        }


        .acoes button:hover {

            background:
                rgba(162, 201, 212, .10);

            color:
                rgb(220, 235, 240);
        }


        .btn-acao i {

            margin-right: 3px;
        }


        .btn-responder {

            font-weight: bold;

            color:
                rgb(180, 205, 212) !important;
        }


        /* =========================================
           RESPOSTAS - APENAS SEGUNDO NÍVEL VISUAL
        ========================================= */

        .bloco-respostas {

            position: relative;

            margin-left: 52px;

            margin-top: 5px;

            /*
                Espaço reservado para as linhas que
                conectam visualmente as respostas.
            */
            padding-left: 22px;
        }


        /*
            Linha vertical principal.
            Ela acompanha todas as respostas abertas,
            semelhante ao visual das conversas do YouTube.
        */
        .respostas {

            position: relative;
        }


        .respostas::before {

            content: "";

            position: absolute;

            left: 0;

            top: 0;

            bottom: 14px;

            width: 1px;

            background:
                rgba(162, 201, 212, .28);

            border-radius: 10px;
        }


        .btn-toggle-respostas {

            border: none;

            background: transparent;

            color:
                #6eb8ff;

            font-size: 14px;

            font-weight: bold;

            padding:
                7px 10px;

            border-radius: 18px;

            cursor: pointer;

            transition: .2s;
        }


        .btn-toggle-respostas:hover {

            background:
                rgba(110, 184, 255, .10);
        }


        .btn-toggle-respostas i {

            width: 18px;

            margin-right: 4px;
        }


        .respostas {

            display: none;

            margin-top: 4px;

            padding-left: 20px;
        }


        .respostas.abertas {

            display: block;
        }


        /*
            IMPORTANTE:
            todas as respostas usam o mesmo recuo.
            Não existe .respostas dentro de .respostas.
        */
        .respostas .comentario {

            position: relative;

            margin-bottom: 2px;

            padding:
                7px 0;
        }


        /*
            Linha horizontal ligando a linha vertical
            até cada resposta.
        */
        .respostas .comentario::before {

            content: "";

            position: absolute;

            left: -20px;

            top: 24px;

            width: 20px;

            height: 1px;

            background:
                rgba(162, 201, 212, .28);
        }


        /*
            Pequena curva no encontro da linha vertical
            com cada resposta.
        */
        .respostas .comentario::after {

            content: "";

            position: absolute;

            left: -20px;

            top: 14px;

            width: 10px;

            height: 11px;

            border-left:
                1px solid rgba(162, 201, 212, .28);

            border-bottom:
                1px solid rgba(162, 201, 212, .28);

            border-bottom-left-radius: 12px;

            pointer-events: none;
        }


        .respostas .foto-perfil-comentario {

            width: 34px;

            height: 34px;
        }


        /* =========================================
           ÁREA "RESPONDENDO"
        ========================================= */

        #respondendo {

            display: none;

            margin:
                0 1rem 8px;

            padding:
                8px 12px;

            background:
                rgb(39, 57, 80);

            border-radius: 5px;

            font-size: 14px;
        }


        #respondendo strong {

            color:
                #6eb8ff;
        }


        #respondendo button {

            margin-left: 8px;

            border: none;

            background: transparent;

            color:
                rgb(162, 201, 212);

            cursor: pointer;
        }


        #respondendo button:hover {

            color: #ff7089;
        }


        /* =========================================
           BARRA COMENTÁRIO
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

            border-top:
                1px solid rgba(162, 201, 212, .12);
        }


        .input-area {

            display: flex;

            gap: 10px;

            margin:
                0 1rem;
        }


        .input-area input {

            width: 100%;

            padding: 10px 14px;

            color:
                rgb(220, 235, 240);

            background:
                rgb(20, 29, 41);

            border-radius: 20px;

            border:
                1px solid rgba(162, 201, 212, .25);

            outline: none;
        }


        .input-area input:focus {

            border-color:
                rgba(162, 201, 212, .65);
        }


        .btnEnviar {

            padding:
                8px 19px;

            background:
                rgb(162, 201, 212);

            border: none;

            color:
                rgb(27, 40, 56);

            border-radius: 20px;

            cursor: pointer;
        }


        .btnEnviar:hover {

            background:
                rgb(190, 220, 228);
        }


        /* =========================================
           LOGIN
        ========================================= */

        .aviso-login {

            text-align: center;

            padding: 12px;
        }


        .aviso-login a {

            color:
                rgb(162, 201, 212);
        }


        /* =========================================
           SEM COMENTÁRIOS
        ========================================= */

        .sem-comentarios {

            text-align: center;

            padding: 40px;
        }


        .sem-comentarios i {

            font-size: 35px;
        }


        .sem-comentarios p {

            margin-top: 15px;
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

                padding:
                    12px 9px;
            }


            .input-area {

                margin: 0;
            }


            .input-area input {

                font-size: 16px;
            }


            .btnEnviar {

                padding:
                    8px 14px;
            }


            .linha-comentario {

                gap: 9px;
            }


            .foto-perfil-comentario {

                width: 36px;

                height: 36px;
            }


            .respostas .foto-perfil-comentario {

                width: 30px;

                height: 30px;
            }

            .bloco-respostas {

                margin-left: 38px;

                padding-left: 16px;
            }


            .respostas {

                padding-left: 16px;
            }


            .respostas .comentario::before {

                left: -16px;

                width: 16px;
            }


            .respostas .comentario::after {

                left: -16px;
            }



            .bloco-respostas {

                margin-left: 34px;
            }


            .respostas {

                padding-left: 5px;
            }


            .data {

                width: 100%;
            }
        }

    
        /* =========================================
           LINHAS CONECTANDO COMENTÁRIO E RESPOSTAS
           ========================================= */

        .thread {

            position: relative;
        }


        .comentario-principal {

            position: relative;
        }


        /*
            A linha sai da região da foto do comentário
            principal e continua até a área das respostas.
        */
        .thread.respostas-conectadas
        .comentario-principal::after {

            content: "";

            position: absolute;

            left: 21px;

            bottom: -40px;

            width: 1px;

            height: 46px;

            background:
                rgba(162, 201, 212, .38);

            border-radius: 10px;

            pointer-events: none;

            z-index: 1;
        }


        /*
            O bloco das respostas começa alinhado
            depois da foto do comentário principal.
        */
        .thread.respostas-conectadas
        .bloco-respostas::before {

            content: "";

            position: absolute;

            left: -31px;

            top: 0;

            width: 1px;

            height: 48px;

            background:
                rgba(162, 201, 212, .38);

            border-radius: 10px;

            pointer-events: none;
        }


        /*
            Linha horizontal que cria a ramificação:
                    |
                    |________ respostas
        */
        .thread.respostas-conectadas
        .bloco-respostas::after {

            content: "";

            position: absolute;

            left: -31px;

            top: 47px;

            width: 31px;

            height: 1px;

            background:
                rgba(162, 201, 212, .38);

            pointer-events: none;
        }


        /*
            A linha vertical das respostas começa
            exatamente na ramificação do comentário
            principal.
        */
        .thread.respostas-conectadas
        .respostas::before {

            top: 0;

            bottom: 14px;

            background:
                rgba(162, 201, 212, .38);
        }


        /*
            Melhora o encaixe da primeira resposta
            na linha principal da conversa.
        */
        .thread.respostas-conectadas
        .respostas .comentario:first-child::after {

            border-color:
                rgba(162, 201, 212, .38);
        }


        @media(max-width: 480px) {

            .thread.respostas-conectadas
            .comentario-principal::after {

                left: 18px;

                bottom: -38px;

                height: 44px;
            }


            .thread.respostas-conectadas
            .bloco-respostas::before {

                left: -20px;

                height: 46px;
            }


            .thread.respostas-conectadas
            .bloco-respostas::after {

                left: -20px;

                top: 45px;

                width: 20px;
            }

        }

    </style>

</head>


<body>


    <!-- =========================================
         FUNDO
    ========================================== -->

    <div class="fundoimg">

        <?php if (!empty($jogo["background"])): ?>

            <img
                src="<?= htmlspecialchars($jogo["background"]) ?>"
                alt=""
            >

        <?php endif; ?>

    </div>


    <!-- =========================================
         VOLTAR
    ========================================== -->

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
    ========================================== -->

    <main class="containerjogo">


        <!-- LOGO -->

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


        <!-- =========================================
             PESQUISA
        ========================================== -->

        <div class="linha-divisoria">

            <div class="input-box">

                <input
                    placeholder="Pesquisar nos comentários..."
                    type="text"
                    id="pesquisaComentario"
                    autocomplete="off"
                >

            </div>

        </div>


        <!-- =========================================
             COMENTÁRIOS
        ========================================== -->

        <div id="comentarios">


            <?php if (count($comentariosPrincipais) === 0): ?>

                <div class="sem-comentarios">

                    <i class="fa-solid fa-comments"></i>

                    <p>
                        Ainda não existem comentários
                        neste jogo.
                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($comentariosPrincipais as $comentario): ?>

                    <?php

                    $idComentarioPrincipal =
                        intval($comentario["id"]);

                    $listaRespostas =
                        $respostas[
                            $idComentarioPrincipal
                        ] ?? [];

                    // Texto completo da thread para pesquisa
                    $textoPesquisa =
                        $comentario["nome"] . " " .
                        $comentario["texto"];

                    foreach (
                        $listaRespostas
                        as $respostaPesquisa
                    ) {

                        $textoPesquisa .= " " .
                            ($respostaPesquisa["respondendo_nome"] ?? "") .
                            " " .
                            $respostaPesquisa["nome"] .
                            " " .
                            $respostaPesquisa["texto"];
                    }

                    ?>


                    <div
                        class="thread"
                        data-texto="<?= htmlspecialchars(
                            mb_strtolower(
                                $textoPesquisa,
                                "UTF-8"
                            )
                        ) ?>"
                    >


                        <!-- =================================
                             COMENTÁRIO PRINCIPAL
                        ================================== -->

                        <div class="comentario comentario-principal">

                            <div class="linha-comentario">


                                <!-- FOTO -->

                                <a
                                    href="perfil.php?id=<?= intval($comentario["usuario_id"]) ?>"
                                    aria-label="Abrir perfil de <?= htmlspecialchars($comentario["nome"]) ?>"
                                >
                                    <?php
                                    fotoPerfilComentario(
                                        $comentario
                                    );
                                    ?>
                                </a>


                                <div class="conteudo-comentario">


                                    <!-- TOPO -->

                                    <div class="topo">

                                        <a
                                            href="perfil.php?id=<?= intval($comentario["usuario_id"]) ?>"
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

                                    <?php
                                    botoesComentario(
                                        $comentario,
                                        $logado
                                    );
                                    ?>


                                </div>

                            </div>

                        </div>


                        <!-- =================================
                             RESPOSTAS
                        ================================== -->

                        <?php if (
                            count($listaRespostas) > 0
                        ): ?>

                            <?php

                            $quantidadeRespostas =
                                count($listaRespostas);

                            $textoQtd =
                                $quantidadeRespostas === 1
                                ? "1 resposta"
                                : $quantidadeRespostas . " respostas";

                            $idBloco =
                                "respostas-" .
                                $idComentarioPrincipal;

                            ?>

                            <div class="bloco-respostas">

                                <button
                                    type="button"
                                    class="btn-toggle-respostas"
                                    onclick="alternarRespostas(
                                        '<?= $idBloco ?>',
                                        this
                                    )"
                                    data-quantidade="<?= htmlspecialchars($textoQtd) ?>"
                                >

                                    <i class="fa-solid fa-chevron-down"></i>

                                    <span>
                                        Ver <?= htmlspecialchars($textoQtd) ?>
                                    </span>

                                </button>


                                <div
                                    class="respostas"
                                    id="<?= $idBloco ?>"
                                >


                                    <?php foreach (
                                        $listaRespostas
                                        as $resposta
                                    ): ?>


                                        <div class="comentario resposta">

                                            <div class="linha-comentario">


                                                <!-- FOTO -->

                                                <a
                                                    href="perfil.php?id=<?= intval($resposta["usuario_id"]) ?>"
                                                    aria-label="Abrir perfil de <?= htmlspecialchars($resposta["nome"]) ?>"
                                                >
                                                    <?php
                                                    fotoPerfilComentario(
                                                        $resposta
                                                    );
                                                    ?>
                                                </a>


                                                <div class="conteudo-comentario">


                                                    <!-- TOPO -->

                                                    <div class="topo">

                                                        <a
                                                            href="perfil.php?id=<?= intval($resposta["usuario_id"]) ?>"
                                                            class="nome"
                                                        >
                                                            <?= htmlspecialchars(
                                                                $resposta["nome"]
                                                            ) ?>
                                                        </a>


                                                        <span class="data">

                                                            <?= date(
                                                                "d/m/Y H:i",
                                                                strtotime(
                                                                    $resposta["data_criacao"]
                                                                )
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                    <!-- TEXTO -->

                                                    <p class="texto-comentario">

                                                        <?php if (
                                                            !empty(
                                                                $resposta["respondendo_nome"]
                                                            )
                                                        ): ?>

                                                            <span class="mencao">
                                                                @<?= htmlspecialchars(
                                                                    $resposta["respondendo_nome"]
                                                                ) ?>
                                                            </span>

                                                        <?php endif; ?>

                                                        <?= nl2br(
                                                            htmlspecialchars(
                                                                $resposta["texto"]
                                                            )
                                                        ) ?>

                                                    </p>


                                                    <!-- AÇÕES -->

                                                    <?php
                                                    botoesComentario(
                                                        $resposta,
                                                        $logado
                                                    );
                                                    ?>


                                                </div>

                                            </div>

                                        </div>


                                    <?php endforeach; ?>


                                </div>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


        <!-- =========================================
             BARRA DE COMENTÁRIO
        ========================================== -->

        <div class="barra-comentario">


            <?php if ($logado): ?>


                <!-- AVISO DE RESPOSTA -->

                <div id="respondendo">

                    Respondendo a
                    <strong id="nomeRespondendo"></strong>

                    <button
                        type="button"
                        onclick="cancelarResposta()"
                    >

                        ✕ Cancelar

                    </button>

                </div>


                <!-- FORMULÁRIO -->

                <form
                    method="POST"
                    class="input-area"
                >

                    <input
                        type="hidden"
                        name="acao"
                        value="comentar"
                    >


                    <!-- ID EXATO DO COMENTÁRIO QUE ESTÁ SENDO RESPONDIDO -->

                    <input
                        type="hidden"
                        name="comentario_pai_id"
                        id="comentarioPai"
                        value=""
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
        // PESQUISAR THREADS DE COMENTÁRIOS
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
                            .toLocaleLowerCase("pt-BR")
                            .trim();


                    const threads =
                        document.querySelectorAll(
                            ".thread"
                        );


                    threads.forEach(
                        function (thread) {

                            const conteudo =
                                (
                                    thread.dataset.texto || ""
                                ).toLocaleLowerCase("pt-BR");


                            if (
                                conteudo.includes(texto)
                            ) {

                                thread.style.display =
                                    "";

                            } else {

                                thread.style.display =
                                    "none";
                            }

                        }
                    );

                }
            );

        }


        // =============================================
        // ABRIR / FECHAR RESPOSTAS
        // =============================================

        function alternarRespostas(
            id,
            botao
        ) {

            const bloco =
                document.getElementById(id);

            if (!bloco) {
                return;
            }

            const abriu =
                bloco.classList.toggle(
                    "abertas"
                );

            // Encontrar a conversa completa
            const thread =
                botao.closest(".thread");

            // Ativar/desativar as linhas que conectam
            // o comentário principal às respostas
            if (thread) {

                thread.classList.toggle(
                    "respostas-conectadas",
                    abriu
                );

            }

            const icone =
                botao.querySelector("i");

            const texto =
                botao.querySelector("span");

            const quantidade =
                botao.dataset.quantidade || "respostas";


            if (abriu) {

                if (icone) {

                    icone.className =
                        "fa-solid fa-chevron-up";

                }

                if (texto) {

                    texto.textContent =
                        "Ocultar " + quantidade;

                }

            } else {

                if (icone) {

                    icone.className =
                        "fa-solid fa-chevron-down";

                }

                if (texto) {

                    texto.textContent =
                        "Ver " + quantidade;

                }

            }

        }

        // =============================================
        // RESPONDER COMENTÁRIO
        // =============================================

        function responderComentario(
            id,
            nome
        ) {

            const comentarioPai =
                document.getElementById(
                    "comentarioPai"
                );


            const nomeRespondendo =
                document.getElementById(
                    "nomeRespondendo"
                );


            const respondendo =
                document.getElementById(
                    "respondendo"
                );


            const input =
                document.getElementById(
                    "inputComentario"
                );


            if (
                !comentarioPai ||
                !nomeRespondendo ||
                !respondendo ||
                !input
            ) {
                return;
            }


            comentarioPai.value =
                id;


            nomeRespondendo.textContent =
                "@" + nome;


            respondendo.style.display =
                "block";


            input.placeholder =
                "Responder a @" +
                nome +
                "...";


            input.focus();


            // Rolar suavemente até o formulário

            document
                .querySelector(
                    ".barra-comentario"
                )
                .scrollIntoView({
                    behavior: "smooth",
                    block: "end"
                });

        }


        // =============================================
        // CANCELAR RESPOSTA
        // =============================================

        function cancelarResposta() {

            const comentarioPai =
                document.getElementById(
                    "comentarioPai"
                );


            const nomeRespondendo =
                document.getElementById(
                    "nomeRespondendo"
                );


            const respondendo =
                document.getElementById(
                    "respondendo"
                );


            const input =
                document.getElementById(
                    "inputComentario"
                );


            if (comentarioPai) {

                comentarioPai.value = "";

            }


            if (nomeRespondendo) {

                nomeRespondendo.textContent = "";

            }


            if (respondendo) {

                respondendo.style.display =
                    "none";

            }


            if (input) {

                input.placeholder =
                    "Dê sua solução...";

                input.focus();

            }

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

                    if (
                        event.key === "Enter"
                    ) {

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


<?php

// ==========================================
// CONEXÃO
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
// FUNÇÃO PARA SALVAR IMAGEM
// ==========================================

function salvarImagem($arquivo, $pasta)
{
    if (
        !isset($arquivo) ||
        $arquivo["error"] != 0
    ) {
        return null;
    }

    $extensao = strtolower(
        pathinfo(
            $arquivo["name"],
            PATHINFO_EXTENSION
        )
    );

    $permitidas = [
        "jpg",
        "jpeg",
        "png",
        "gif",
        "webp"
    ];

    if (!in_array($extensao, $permitidas)) {
        return null;
    }

    $pastaCompleta =
        __DIR__ . "/" . $pasta;

    if (!is_dir($pastaCompleta)) {
        mkdir(
            $pastaCompleta,
            0777,
            true
        );
    }

    $nome =
        uniqid() . "." . $extensao;

    $caminhoCompleto =
        $pastaCompleta . "/" . $nome;

    if (
        move_uploaded_file(
            $arquivo["tmp_name"],
            $caminhoCompleto
        )
    ) {
        return $pasta . "/" . $nome;
    }

    return null;
}


// ==========================================
// BUSCAR CATEGORIAS
// ==========================================

$resultCategorias = $conn->query(
    "SELECT id, nome
     FROM categorias
     ORDER BY nome"
);


// ==========================================
// BUSCAR DESENVOLVEDORAS
// ==========================================

$resultDesenvolvedoras = $conn->query(
    "SELECT id, nome
     FROM desenvolvedoras
     ORDER BY nome"
);


// ==========================================
// BUSCAR PLATAFORMAS
// ==========================================

$resultPlataformas = $conn->query(
    "SELECT id, nome
     FROM plataformas
     ORDER BY nome"
);


// ==========================================
// CADASTRO
// ==========================================

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);

    $dataLancamento =
        !empty($_POST["data_lancamento"])
        ? $_POST["data_lancamento"]
        : null;

    $desenvolvedoraId =
        !empty($_POST["desenvolvedora_id"])
        ? intval($_POST["desenvolvedora_id"])
        : null;


    if ($nome == "") {

        $mensagem =
            "<div class='erro'>
                O nome do jogo é obrigatório.
            </div>";

    } else {

        // ======================================
        // IMAGENS PRINCIPAIS
        // ======================================

        $banner = salvarImagem(
            $_FILES["banner"],
            "uploads/jogos"
        );

        $capa = salvarImagem(
            $_FILES["capa"],
            "uploads/jogos"
        );

        $logo = salvarImagem(
            $_FILES["logo"],
            "uploads/jogos"
        );


        // ======================================
        // CADASTRAR JOGO
        // ======================================

        $sql = "
            INSERT INTO jogos
            (
                nome,
                descricao,
                banner,
                capa,
                logo,
                data_lancamento,
                desenvolvedora_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssssi",
            $nome,
            $descricao,
            $banner,
            $capa,
            $logo,
            $dataLancamento,
            $desenvolvedoraId
        );


        if ($stmt->execute()) {

            $jogoId =
                $conn->insert_id;


            // ==================================
            // CATEGORIAS
            // ==================================

            if (
                isset($_POST["categorias"])
                &&
                is_array($_POST["categorias"])
            ) {

                $stmtCategoria =
                    $conn->prepare(
                        "INSERT INTO jogos_categorias
                         (id_jogo, id_categoria)
                         VALUES (?, ?)"
                    );

                foreach (
                    $_POST["categorias"]
                    as $categoriaId
                ) {

                    $categoriaId =
                        intval($categoriaId);

                    $stmtCategoria->bind_param(
                        "ii",
                        $jogoId,
                        $categoriaId
                    );

                    $stmtCategoria->execute();
                }

                $stmtCategoria->close();
            }


            // ==================================
            // PLATAFORMAS
            // ==================================

            if (
                isset($_POST["plataformas"])
                &&
                is_array($_POST["plataformas"])
            ) {

                $stmtPlataforma =
                    $conn->prepare(
                        "INSERT INTO jogo_plataforma
                         (jogo_id, plataforma_id)
                         VALUES (?, ?)"
                    );

                foreach (
                    $_POST["plataformas"]
                    as $plataformaId
                ) {

                    $plataformaId =
                        intval($plataformaId);

                    $stmtPlataforma->bind_param(
                        "ii",
                        $jogoId,
                        $plataformaId
                    );

                    $stmtPlataforma->execute();
                }

                $stmtPlataforma->close();
            }


            // ==================================
            // IMAGENS ADICIONAIS
            // ==================================

            if (
                isset($_FILES["imagens"])
                &&
                is_array($_FILES["imagens"]["name"])
            ) {

                $stmtImagem =
                    $conn->prepare(
                        "INSERT INTO imagens_jogo
                         (jogo_id, imagem)
                         VALUES (?, ?)"
                    );


                for (
                    $i = 0;
                    $i <
                    count(
                        $_FILES["imagens"]["name"]
                    );
                    $i++
                ) {

                    if (
                        $_FILES["imagens"]["error"][$i]
                        != 0
                    ) {
                        continue;
                    }


                    $arquivo = [

                        "name" =>
                            $_FILES["imagens"]["name"][$i],

                        "tmp_name" =>
                            $_FILES["imagens"]["tmp_name"][$i],

                        "error" =>
                            $_FILES["imagens"]["error"][$i]
                    ];


                    $caminho =
                        salvarImagem(
                            $arquivo,
                            "uploads/jogos"
                        );


                    if ($caminho !== null) {

                        $stmtImagem->bind_param(
                            "is",
                            $jogoId,
                            $caminho
                        );

                        $stmtImagem->execute();
                    }
                }

                $stmtImagem->close();
            }


            $mensagem =
                "<div class='sucesso'>
                    Jogo cadastrado com sucesso!
                    <br>
                    ID do jogo: $jogoId
                </div>";


            // Recarrega os selects
            $resultCategorias =
                $conn->query(
                    "SELECT id, nome
                     FROM categorias
                     ORDER BY nome"
                );

            $resultDesenvolvedoras =
                $conn->query(
                    "SELECT id, nome
                     FROM desenvolvedoras
                     ORDER BY nome"
                );

            $resultPlataformas =
                $conn->query(
                    "SELECT id, nome
                     FROM plataformas
                     ORDER BY nome"
                );
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
        Cadastrar jogo - SolveBugs
    </title>


    <style>

        /* ==================================
           RESET
        ================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: rgb(162, 201, 212);
        }


        /* ==================================
           BODY
        ================================== */

        body {

            min-height: 100vh;

            background-color:
                rgb(20, 29, 41);

            font-family:
                Arial, Helvetica, sans-serif;

            padding: 40px 20px;

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
                rgba(20, 29, 41, 0.65);

        }


        /* ==================================
           CONTAINER
        ================================== */

        .containerjogo {

            width: 73%;

            max-width: 1000px;

            margin: auto;

            background-color:
                rgba(27, 40, 56, 0.88);

            border:
                2px solid
                rgba(68, 91, 119, 0.7);

            padding: 30px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .5);

            border-radius: 5px;

        }


        /* ==================================
           TÍTULO
        ================================== */

        .titulo {

            text-align: center;

            font-size: 30px;

            margin-bottom: 10px;

        }


        .subtitulo {

            text-align: center;

            color: #c5c5c5;

            margin-bottom: 30px;

        }


        /* ==================================
           DIVISÓRIA
        ================================== */

        .linha-divisoria {

            width: 100%;

            height: 1px;

            background-color:
                rgba(162, 201, 212, 0.3);

            margin:
                20px 0 30px;

        }


        /* ==================================
           FORMULÁRIO
        ================================== */

        .form-group {

            margin-bottom: 22px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 15px;

        }


        /* ==================================
           INPUTS
        ================================== */

        input[type="text"],
        input[type="date"],
        textarea,
        select {

            width: 100%;

            background-color:
                rgba(20, 29, 41, 0.9);

            border:
                2px solid
                rgba(255, 255, 255, 0.15);

            border-radius: 5px;

            outline: none;

            padding: 12px;

            font-size: 16px;

            color:
                rgb(162, 201, 212);

            transition: .2s;

        }


        textarea {

            min-height: 130px;

            resize: vertical;

        }


        select option {

            background-color:
                rgb(27, 40, 56);

            color:
                rgb(162, 201, 212);

        }


        input:focus,
        textarea:focus,
        select:focus {

            border-color:
                rgb(162, 201, 212);

            box-shadow:
                0 0 5px
                rgba(162, 201, 212, .25);

        }


        input::placeholder,
        textarea::placeholder {

            color: #c5c5c5;

        }


        /* ==================================
           GRID
        ================================== */

        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;

        }


        /* ==================================
           CHECKBOXES
        ================================== */

        .opcoes {

            display: flex;

            flex-wrap: wrap;

            gap: 8px;

            padding: 12px;

            background-color:
                rgba(20, 29, 41, 0.9);

            border:
                1px solid
                rgba(255, 255, 255, 0.15);

            border-radius: 5px;

        }


        .opcao {

            display: flex;

            align-items: center;

            gap: 6px;

            background-color:
                rgb(39, 57, 80);

            padding:
                8px 12px;

            border-radius: 5px;

            cursor: pointer;

            transition: .2s;

        }


        .opcao:hover {

            background-color:
                rgb(50, 71, 97);

        }


        .opcao input {

            accent-color:
                rgb(162, 201, 212);

            cursor: pointer;

        }


        /* ==================================
           UPLOAD
        ================================== */

        .upload {

            background-color:
                rgba(20, 29, 41, 0.9);

            border:
                1px dashed
                rgba(162, 201, 212, 0.5);

            padding: 15px;

            border-radius: 5px;

        }


        .upload input {

            width: 100%;

            cursor: pointer;

        }


        /* ==================================
           BOTÃO
        ================================== */

        .btnEnviar {

            width: 100%;

            padding: 13px 20px;

            background:
                rgb(162, 201, 212);

            border: none;

            color:
                rgb(27, 40, 56);

            border-radius: 5px;

            cursor: pointer;

            font-size: 16px;

            font-weight: bold;

            transition: .2s;

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


        /* ==================================
           MENSAGENS
        ================================== */

        .sucesso {

            background-color:
                rgba(60, 130, 90, .25);

            border:
                1px solid
                rgba(100, 200, 130, .5);

            padding: 12px;

            border-radius: 5px;

            margin-bottom: 20px;

            text-align: center;

        }


        .erro {

            background-color:
                rgba(184, 44, 70, .25);

            border:
                1px solid
                #B82c46;

            padding: 12px;

            border-radius: 5px;

            margin-bottom: 20px;

            text-align: center;

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

            background-color:
                rgb(39, 57, 80);

            transition: .3s;

            text-decoration: none;

            z-index: 10;

        }


        .sign {

            width: 100%;

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

            background-color:
                #B82c46;

        }


        .Btn:hover .sign {

            width: 30%;

        }


        .Btn:hover .text {

            opacity: 1;

            width: 60%;

        }


        /* ==================================
           RESPONSIVO
        ================================== */

        @media (max-width: 832px) {

            .containerjogo {

                width: 90%;

                padding: 25px;

            }

            .grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 480px) {

            body {

                padding:
                    20px 10px;

            }


            .containerjogo {

                width: 100%;

                padding: 20px 15px;

            }


            .titulo {

                font-size: 24px;

            }


            .opcoes {

                flex-direction: column;

            }


            .opcao {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =====================================
     FUNDO
===================================== -->

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


    <h1 class="titulo">
        Cadastrar jogo
    </h1>


    <p class="subtitulo">
        Adicione um novo jogo ao SolveBugs
    </p>


    <div class="linha-divisoria"></div>


    <?= $mensagem ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- ==============================
             INFORMAÇÕES
        =============================== -->

        <div class="grid">


            <div class="form-group">

                <label>
                    Nome do jogo *
                </label>

                <input
                    type="text"
                    name="nome"
                    maxlength="150"
                    placeholder="Ex: Minecraft"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Data de lançamento
                </label>

                <input
                    type="date"
                    name="data_lancamento"
                >

            </div>


        </div>


        <!-- ==============================
             DESCRIÇÃO
        =============================== -->

        <div class="form-group">

            <label>
                Descrição
            </label>

            <textarea
                name="descricao"
                placeholder="Digite uma descrição do jogo..."
            ></textarea>

        </div>


        <!-- ==============================
             DESENVOLVEDORA
        =============================== -->

        <div class="form-group">

            <label>
                Desenvolvedora
            </label>

            <select
                name="desenvolvedora_id"
            >

                <option value="">
                    Selecione uma desenvolvedora
                </option>


                <?php

                while (
                    $desenvolvedora =
                    $resultDesenvolvedoras
                    ->fetch_assoc()
                ) {

                ?>

                    <option
                        value="<?= $desenvolvedora["id"] ?>"
                    >

                        <?= htmlspecialchars(
                            $desenvolvedora["nome"]
                        ) ?>

                    </option>

                <?php

                }

                ?>

            </select>

        </div>


        <!-- ==============================
             CATEGORIAS
        =============================== -->

        <div class="form-group">

            <label>
                Categorias
            </label>


            <div class="opcoes">

                <?php

                while (
                    $categoria =
                    $resultCategorias
                    ->fetch_assoc()
                ) {

                ?>

                    <label class="opcao">

                        <input
                            type="checkbox"
                            name="categorias[]"
                            value="<?= $categoria["id"] ?>"
                        >

                        <?= htmlspecialchars(
                            $categoria["nome"]
                        ) ?>

                    </label>

                <?php

                }

                ?>

            </div>

        </div>


        <!-- ==============================
             PLATAFORMAS
        =============================== -->

        <div class="form-group">

            <label>
                Plataformas
            </label>


            <div class="opcoes">

                <?php

                while (
                    $plataforma =
                    $resultPlataformas
                    ->fetch_assoc()
                ) {

                ?>

                    <label class="opcao">

                        <input
                            type="checkbox"
                            name="plataformas[]"
                            value="<?= $plataforma["id"] ?>"
                        >

                        <?= htmlspecialchars(
                            $plataforma["nome"]
                        ) ?>

                    </label>

                <?php

                }

                ?>

            </div>

        </div>


        <div class="linha-divisoria"></div>


        <!-- ==============================
             IMAGENS
        =============================== -->

        <div class="grid">


            <div class="form-group">

                <label>
                    Banner
                </label>

                <div class="upload">

                    <input
                        type="file"
                        name="banner"
                        accept="image/*"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Capa
                </label>

                <div class="upload">

                    <input
                        type="file"
                        name="capa"
                        accept="image/*"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Logo
                </label>

                <div class="upload">

                    <input
                        type="file"
                        name="logo"
                        accept="image/*"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Imagens adicionais
                </label>

                <div class="upload">

                    <input
                        type="file"
                        name="imagens[]"
                        accept="image/*"
                        multiple
                    >

                    <small>
                        Você pode selecionar várias.
                    </small>

                </div>

            </div>


        </div>


        <br>


        <!-- ==============================
             BOTÃO
        =============================== -->

        <button
            type="submit"
            class="btnEnviar"
        >

            Cadastrar jogo

        </button>


    </form>


</main>


</body>

</html>

<?php

$conn->close();

?>


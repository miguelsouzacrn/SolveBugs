
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

        $background = salvarImagem(
            $_FILES["background"],
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
                background,
                data_lancamento,
                desenvolvedora_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssssi",
            $nome,
            $descricao,
            $banner,
            $capa,
            $logo,
            $background,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/cadastro_jogos.css">

    <title>
        Cadastrar jogo - SolveBugs
    </title>

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
                Background
            </label>

            <div class="upload">

                <input
                    type="file"
                    name="background"
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


<?php

require_once __DIR__ . "/config/auth.php";

exigirLogin();


// ==========================================
// VERIFICAR SE É ADMIN
// ==========================================

if (
    !isset($_SESSION["usuario_tipo"])
    ||
    $_SESSION["usuario_tipo"] !== "admin"
) {

    header("Location: index.php");
    exit;

}


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

    die(
        "Erro na conexão: "
        . $conn->connect_error
    );

}


$conn->set_charset("utf8mb4");


// ==========================================
// VERIFICAR ID
// ==========================================

if (
    !isset($_GET["id"])
    ||
    !is_numeric($_GET["id"])
) {

    header("Location: gerenciar_jogos.php");
    exit;

}


$jogoId = intval($_GET["id"]);


// ==========================================
// BUSCAR JOGO
// ==========================================

$stmt = $conn->prepare(
    "SELECT *
     FROM jogos
     WHERE id = ?"
);


$stmt->bind_param(
    "i",
    $jogoId
);


$stmt->execute();


$resultadoJogo =
    $stmt->get_result();


$jogo =
    $resultadoJogo->fetch_assoc();


$stmt->close();


// ==========================================
// JOGO NÃO ENCONTRADO
// ==========================================

if (!$jogo) {

    header("Location: gerenciar_jogos.php");
    exit;

}


// ==========================================
// FUNÇÃO SALVAR IMAGEM
// ==========================================

function salvarImagem(
    $arquivo,
    $pasta
) {

    if (
        !isset($arquivo)
        ||
        $arquivo["error"] !== UPLOAD_ERR_OK
    ) {

        return null;

    }


    // ======================================
    // VERIFICAR EXTENSÃO
    // ======================================

    $extensao =
        strtolower(
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


    if (
        !in_array(
            $extensao,
            $permitidas
        )
    ) {

        return null;

    }


    // ======================================
    // PASTA
    // ======================================

    $pastaCompleta =
        __DIR__
        . "/"
        . $pasta;


    if (
        !is_dir($pastaCompleta)
    ) {

        mkdir(
            $pastaCompleta,
            0777,
            true
        );

    }


    // ======================================
    // NOME ÚNICO
    // ======================================

    $nome =
        uniqid()
        . "_"
        . bin2hex(
            random_bytes(5)
        )
        . "."
        . $extensao;


    $caminhoCompleto =
        $pastaCompleta
        . "/"
        . $nome;


    // ======================================
    // SALVAR
    // ======================================

    if (
        move_uploaded_file(
            $arquivo["tmp_name"],
            $caminhoCompleto
        )
    ) {

        return
            $pasta
            . "/"
            . $nome;

    }


    return null;

}


// ==========================================
// FUNÇÃO APAGAR IMAGEM
// ==========================================

function apagarImagem(
    $caminho
) {

    if (
        empty($caminho)
    ) {

        return;

    }


    $arquivo =
        __DIR__
        . "/"
        . $caminho;


    if (
        file_exists($arquivo)
        &&
        is_file($arquivo)
    ) {

        unlink($arquivo);

    }

}


// ==========================================
// BUSCAR CATEGORIAS
// ==========================================

$resultCategorias =
    $conn->query(
        "SELECT id, nome
         FROM categorias
         ORDER BY nome"
    );


// ==========================================
// BUSCAR DESENVOLVEDORAS
// ==========================================

$resultDesenvolvedoras =
    $conn->query(
        "SELECT id, nome
         FROM desenvolvedoras
         ORDER BY nome"
    );


// ==========================================
// BUSCAR PLATAFORMAS
// ==========================================

$resultPlataformas =
    $conn->query(
        "SELECT id, nome
         FROM plataformas
         ORDER BY nome"
    );


// ==========================================
// BUSCAR CATEGORIAS DO JOGO
// ==========================================

$stmtCategorias =
    $conn->prepare(
        "SELECT id_categoria
         FROM jogos_categorias
         WHERE id_jogo = ?"
    );


$stmtCategorias->bind_param(
    "i",
    $jogoId
);


$stmtCategorias->execute();


$resultCategoriasJogo =
    $stmtCategorias->get_result();


$categoriasSelecionadas = [];


while (
    $categoria =
    $resultCategoriasJogo->fetch_assoc()
) {

    $categoriasSelecionadas[] =
        $categoria["id_categoria"];

}


$stmtCategorias->close();


// ==========================================
// BUSCAR PLATAFORMAS DO JOGO
// ==========================================

$stmtPlataformas =
    $conn->prepare(
        "SELECT plataforma_id
         FROM jogo_plataforma
         WHERE jogo_id = ?"
    );


$stmtPlataformas->bind_param(
    "i",
    $jogoId
);


$stmtPlataformas->execute();


$resultPlataformasJogo =
    $stmtPlataformas->get_result();


$plataformasSelecionadas = [];


while (
    $plataforma =
    $resultPlataformasJogo->fetch_assoc()
) {

    $plataformasSelecionadas[] =
        $plataforma["plataforma_id"];

}


$stmtPlataformas->close();


// ==========================================
// BUSCAR IMAGENS ADICIONAIS
// ==========================================

$stmtImagens =
    $conn->prepare(
        "SELECT *
         FROM imagens_jogo
         WHERE jogo_id = ?
         ORDER BY id"
    );


$stmtImagens->bind_param(
    "i",
    $jogoId
);


$stmtImagens->execute();


$imagensAdicionais =
    $stmtImagens
    ->get_result()
    ->fetch_all(
        MYSQLI_ASSOC
    );


$stmtImagens->close();


// ==========================================
// MENSAGEM
// ==========================================

$mensagem = "";


// ==========================================
// ATUALIZAR JOGO
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    // ======================================
    // DADOS
    // ======================================

    $nome =
        trim(
            $_POST["nome"]
            ?? ""
        );


    $descricao =
        trim(
            $_POST["descricao"]
            ?? ""
        );


    $dataLancamento =
        !empty(
            $_POST["data_lancamento"]
        )
        ?
        $_POST["data_lancamento"]
        :
        null;


    $desenvolvedoraId =
        !empty(
            $_POST["desenvolvedora_id"]
        )
        ?
        intval(
            $_POST["desenvolvedora_id"]
        )
        :
        null;


    // ======================================
    // VALIDAR NOME
    // ======================================

    if (
        $nome === ""
    ) {

        $mensagem =
            "<div class='erro'>
                O nome do jogo é obrigatório.
            </div>";

    }

    else {


        // ==================================
        // IMAGENS ATUAIS
        // ==================================

        $banner =
            $jogo["banner"];


        $capa =
            $jogo["capa"];


        $logo =
            $jogo["logo"];


        $background =
            $jogo["background"];


        // ==================================
        // NOVO BANNER
        // ==================================

        if (
            isset(
                $_FILES["banner"]
            )
            &&
            $_FILES["banner"]["error"]
            === UPLOAD_ERR_OK
        ) {

            $novoBanner =
                salvarImagem(
                    $_FILES["banner"],
                    "uploads/jogos"
                );


            if ($novoBanner) {

                apagarImagem(
                    $banner
                );

                $banner =
                    $novoBanner;

            }

        }


// ==================================
// NOVA CAPA
// ==================================

if (
    isset($_FILES["capa"])
    &&
    $_FILES["capa"]["error"] === UPLOAD_ERR_OK
) {

    $novaCapa = salvarImagem(
        $_FILES["capa"],
        "uploads/jogos"
    );


    if ($novaCapa !== null) {

        // Só tenta apagar se existir uma capa antiga
        if (!empty($capa)) {

            apagarImagem($capa);

        }


        // Salva o novo caminho
        $capa = $novaCapa;

    }

}

        // ==================================
        // NOVO LOGO
        // ==================================

        if (
            isset(
                $_FILES["logo"]
            )
            &&
            $_FILES["logo"]["error"]
            === UPLOAD_ERR_OK
        ) {

            $novoLogo =
                salvarImagem(
                    $_FILES["logo"],
                    "uploads/jogos"
                );


            if ($novoLogo) {

                apagarImagem(
                    $logo
                );

                $logo =
                    $novoLogo;

            }

        }


        // ==================================
        // NOVO BACKGROUND
        // ==================================

        if (
            isset(
                $_FILES["background"]
            )
            &&
            $_FILES["background"]["error"]
            === UPLOAD_ERR_OK
        ) {

            $novoBackground =
                salvarImagem(
                    $_FILES["background"],
                    "uploads/jogos"
                );


            if ($novoBackground) {

                apagarImagem(
                    $background
                );

                $background =
                    $novoBackground;

            }

        }


        // ==================================
        // ATUALIZAR JOGO
        // ==================================

        $sql = "
            UPDATE jogos
            SET

                nome = ?,

                descricao = ?,

                banner = ?,

                capa = ?,

                logo = ?,

                background = ?,

                data_lancamento = ?,

                desenvolvedora_id = ?

            WHERE id = ?
        ";


        $stmtUpdate =
            $conn->prepare(
                $sql
            );


        $stmtUpdate->bind_param(
            "sssssssii",

            $nome,

            $descricao,

            $banner,

            $capa,

            $logo,

            $background,

            $dataLancamento,

            $desenvolvedoraId,

            $jogoId
        );


        // ==================================
        // EXECUTAR UPDATE
        // ==================================

        if (
            $stmtUpdate->execute()
        ) {


            // ==============================
            // APAGAR CATEGORIAS ANTIGAS
            // ==============================

            $stmtDeleteCategorias =
                $conn->prepare(
                    "DELETE FROM jogos_categorias
                     WHERE id_jogo = ?"
                );


            $stmtDeleteCategorias
                ->bind_param(
                    "i",
                    $jogoId
                );


            $stmtDeleteCategorias
                ->execute();


            $stmtDeleteCategorias
                ->close();


            // ==============================
            // NOVAS CATEGORIAS
            // ==============================

            if (
                isset(
                    $_POST["categorias"]
                )
                &&
                is_array(
                    $_POST["categorias"]
                )
            ) {

                $stmtCategoria =
                    $conn->prepare(
                        "INSERT INTO jogos_categorias
                         (
                            id_jogo,
                            id_categoria
                         )
                         VALUES (?, ?)"
                    );


                foreach (
                    $_POST["categorias"]
                    as $categoriaId
                ) {

                    $categoriaId =
                        intval(
                            $categoriaId
                        );


                    $stmtCategoria
                        ->bind_param(
                            "ii",

                            $jogoId,

                            $categoriaId
                        );


                    $stmtCategoria
                        ->execute();

                }


                $stmtCategoria
                    ->close();

            }


            // ==============================
            // APAGAR PLATAFORMAS ANTIGAS
            // ==============================

            $stmtDeletePlataformas =
                $conn->prepare(
                    "DELETE FROM jogo_plataforma
                     WHERE jogo_id = ?"
                );


            $stmtDeletePlataformas
                ->bind_param(
                    "i",
                    $jogoId
                );


            $stmtDeletePlataformas
                ->execute();


            $stmtDeletePlataformas
                ->close();


            // ==============================
            // NOVAS PLATAFORMAS
            // ==============================

            if (
                isset(
                    $_POST["plataformas"]
                )
                &&
                is_array(
                    $_POST["plataformas"]
                )
            ) {

                $stmtPlataforma =
                    $conn->prepare(
                        "INSERT INTO jogo_plataforma
                         (
                            jogo_id,
                            plataforma_id
                         )
                         VALUES (?, ?)"
                    );


                foreach (
                    $_POST["plataformas"]
                    as $plataformaId
                ) {

                    $plataformaId =
                        intval(
                            $plataformaId
                        );


                    $stmtPlataforma
                        ->bind_param(
                            "ii",

                            $jogoId,

                            $plataformaId
                        );


                    $stmtPlataforma
                        ->execute();

                }


                $stmtPlataforma
                    ->close();

            }


            // ==============================
            // ADICIONAR NOVAS IMAGENS
            // ==============================

            if (
                isset(
                    $_FILES["imagens"]
                )
                &&
                is_array(
                    $_FILES["imagens"]["name"]
                )
            ) {

                $stmtImagem =
                    $conn->prepare(
                        "INSERT INTO imagens_jogo
                         (
                            jogo_id,
                            imagem
                         )
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
                        !== UPLOAD_ERR_OK
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


                    if ($caminho) {

                        $stmtImagem
                            ->bind_param(
                                "is",

                                $jogoId,

                                $caminho
                            );


                        $stmtImagem
                            ->execute();

                    }

                }


                $stmtImagem
                    ->close();

            }


            // ==============================
            // SUCESSO
            // ==============================

            $mensagem =
                "<div class='sucesso'>
                    Jogo atualizado com sucesso!
                </div>";


            // ==============================
            // ATUALIZAR DADOS
            // ==============================

            $jogo["nome"] =
                $nome;

            $jogo["descricao"] =
                $descricao;

            $jogo["banner"] =
                $banner;

            $jogo["capa"] =
                $capa;

            $jogo["logo"] =
                $logo;

            $jogo["background"] =
                $background;

            $jogo["data_lancamento"] =
                $dataLancamento;

            $jogo["desenvolvedora_id"] =
                $desenvolvedoraId;


            // ==============================
            // ATUALIZAR SELECIONADOS
            // ==============================

            $categoriasSelecionadas =
                isset(
                    $_POST["categorias"]
                )
                ?
                array_map(
                    "intval",
                    $_POST["categorias"]
                )
                :
                [];


            $plataformasSelecionadas =
                isset(
                    $_POST["plataformas"]
                )
                ?
                array_map(
                    "intval",
                    $_POST["plataformas"]
                )
                :
                [];


            // ==============================
            // BUSCAR IMAGENS NOVAMENTE
            // ==============================

            $stmtImagens =
                $conn->prepare(
                    "SELECT *
                     FROM imagens_jogo
                     WHERE jogo_id = ?
                     ORDER BY id"
                );


            $stmtImagens
                ->bind_param(
                    "i",
                    $jogoId
                );


            $stmtImagens
                ->execute();


            $imagensAdicionais =
                $stmtImagens
                ->get_result()
                ->fetch_all(
                    MYSQLI_ASSOC
                );


            $stmtImagens
                ->close();

        }


        $stmtUpdate->close();

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

    <link
        rel="stylesheet"
        href="./css/cadastro_jogos.css"
    >

    <title>
        Editar jogo - SolveBugs
    </title>

</head>


<body>


<div class="fundoimg"></div>


<!-- =====================================
     BOTÃO VOLTAR
===================================== -->

<a
    href="gerenciar_jogos.php"
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

        Editar jogo

    </h1>


    <p class="subtitulo">

        Altere as informações do jogo

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
                    required
                    value="<?= htmlspecialchars($jogo["nome"]) ?>"
                >

            </div>


            <div class="form-group">

                <label>

                    Data de lançamento

                </label>


                <input
                    type="date"
                    name="data_lancamento"
                    value="<?= htmlspecialchars(
                        $jogo["data_lancamento"]
                        ?? ""
                    ) ?>"
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
            ><?= htmlspecialchars(
                $jogo["descricao"]
                ?? ""
            ) ?></textarea>

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


                <?php while (
                    $desenvolvedora =
                    $resultDesenvolvedoras
                    ->fetch_assoc()
                ): ?>

                    <option
                        value="<?= $desenvolvedora["id"] ?>"

                        <?=

                        $jogo["desenvolvedora_id"]
                        == $desenvolvedora["id"]

                        ?

                        "selected"

                        :

                        ""

                        ?>

                    >

                        <?= htmlspecialchars(
                            $desenvolvedora["nome"]
                        ) ?>

                    </option>

                <?php endwhile; ?>


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


                <?php while (
                    $categoria =
                    $resultCategorias
                    ->fetch_assoc()
                ): ?>


                    <label class="opcao">


                        <input
                            type="checkbox"
                            name="categorias[]"
                            value="<?= $categoria["id"] ?>"

                            <?=

                            in_array(
                                $categoria["id"],
                                $categoriasSelecionadas
                            )

                            ?

                            "checked"

                            :

                            ""

                            ?>

                        >


                        <?= htmlspecialchars(
                            $categoria["nome"]
                        ) ?>


                    </label>


                <?php endwhile; ?>


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


                <?php while (
                    $plataforma =
                    $resultPlataformas
                    ->fetch_assoc()
                ): ?>


                    <label class="opcao">


                        <input
                            type="checkbox"
                            name="plataformas[]"
                            value="<?= $plataforma["id"] ?>"

                            <?=

                            in_array(
                                $plataforma["id"],
                                $plataformasSelecionadas
                            )

                            ?

                            "checked"

                            :

                            ""

                            ?>

                        >


                        <?= htmlspecialchars(
                            $plataforma["nome"]
                        ) ?>


                    </label>


                <?php endwhile; ?>


            </div>

        </div>


        <div class="linha-divisoria"></div>


        <!-- ==============================
             IMAGENS PRINCIPAIS
        =============================== -->

        <h2>

            Imagens atuais

        </h2>


        <br>


        <!-- BANNER -->

        <?php if (
            !empty(
                $jogo["banner"]
            )
        ): ?>

            <div class="form-group">

                <label>

                    Banner atual

                </label>


                <img
                    src="<?= htmlspecialchars(
                        $jogo["banner"]
                    ) ?>"
                    style="
                        width:100%;
                        max-height:250px;
                        object-fit:cover;
                        border-radius:8px;
                    "
                >

            </div>

        <?php endif; ?>


        <!-- CAPA -->

        <?php if (
            !empty(
                $jogo["capa"]
            )
        ): ?>

            <div class="form-group">

                <label>

                    Capa atual

                </label>


                <img
                    src="<?= htmlspecialchars(
                        $jogo["capa"]
                    ) ?>"
                    style="
                        width:200px;
                        max-height:300px;
                        object-fit:cover;
                        border-radius:8px;
                    "
                >

            </div>

        <?php endif; ?>


        <!-- LOGO -->

        <?php if (
            !empty(
                $jogo["logo"]
            )
        ): ?>

            <div class="form-group">

                <label>

                    Logo atual

                </label>


                <img
                    src="<?= htmlspecialchars(
                        $jogo["logo"]
                    ) ?>"
                    style="
                        max-width:300px;
                        max-height:150px;
                        object-fit:contain;
                    "
                >

            </div>

        <?php endif; ?>


        <!-- BACKGROUND -->

        <?php if (
            !empty(
                $jogo["background"]
            )
        ): ?>

            <div class="form-group">

                <label>

                    Background atual

                </label>


                <img
                    src="<?= htmlspecialchars(
                        $jogo["background"]
                    ) ?>"
                    style="
                        width:100%;
                        max-height:250px;
                        object-fit:cover;
                        border-radius:8px;
                    "
                >

            </div>

        <?php endif; ?>


        <!-- ==============================
             TROCAR IMAGENS
        =============================== -->

        <div class="grid">


            <div class="form-group">

                <label>

                    Trocar banner

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

                    Trocar capa

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

                    Trocar logo

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

                    Trocar background

                </label>


                <div class="upload">

                    <input
                        type="file"
                        name="background"
                        accept="image/*"
                    >

                </div>

            </div>


        </div>


        <!-- ==============================
             IMAGENS ADICIONAIS
        =============================== -->

        <div class="linha-divisoria"></div>


        <h2>

            Imagens adicionais

        </h2>


        <br>


        <?php if (
            count(
                $imagensAdicionais
            ) > 0
        ): ?>


            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(180px,1fr)
                        );
                    gap:15px;
                    margin-bottom:30px;
                "
            >


                <?php foreach (
                    $imagensAdicionais
                    as $imagem
                ): ?>


                    <div>


                        <img
                            src="<?= htmlspecialchars(
                                $imagem["imagem"]
                            ) ?>"
                            style="
                                width:100%;
                                height:150px;
                                object-fit:cover;
                                border-radius:8px;
                            "
                        >


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <p>

                Nenhuma imagem adicional cadastrada.

            </p>


        <?php endif; ?>


        <!-- NOVAS IMAGENS -->

        <div class="form-group">

            <label>

                Adicionar novas imagens

            </label>


            <div class="upload">

                <input
                    type="file"
                    name="imagens[]"
                    accept="image/*"
                    multiple
                >


                <small>

                    Você pode selecionar várias imagens.

                </small>


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

            Salvar alterações

        </button>


    </form>


</main>


</body>

</html>


<?php

$conn->close();

?>
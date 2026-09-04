<?php

// ============================================================
// CONEXÃO
// ============================================================

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
    die("Erro na conexão: " .
        $conn->connect_error);
}

$conn->set_charset("utf8mb4");


// ============================================================
// FUNÇÃO PARA SALVAR IMAGEM ENVIADA
// ============================================================

function salvarImagem($arquivo, $pasta)
{
    if (
        !isset($arquivo) ||
        !is_array($arquivo) ||
        !isset($arquivo["error"]) ||
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

    if (
        !in_array(
            $extensao,
            $permitidas,
            true
        )
    ) {
        return null;
    }

    $pastaCompleta =
        __DIR__ . "/" . $pasta;

    if (
        !is_dir($pastaCompleta)
    ) {

        mkdir(
            $pastaCompleta,
            0777,
            true
        );
    }

    $nome =
        uniqid() . "." . $extensao;

    $caminhoCompleto =
        $pastaCompleta .
        "/" .
        $nome;

    if (
        move_uploaded_file(
            $arquivo["tmp_name"],
            $caminhoCompleto
        )
    ) {

        return
            $pasta .
            "/" .
            $nome;
    }

    return null;
}


// ============================================================
// FUNÇÃO PARA BAIXAR IMAGEM DA IGDB
// ============================================================

function baixarImagemIGDB(
    $url,
    $pasta = "uploads/jogos"
) {

    if (
        empty($url)
    ) {
        return null;
    }

    if (
        strpos($url, "//") === 0
    ) {

        $url =
            "https:" .
            $url;
    }

    $ch =
        curl_init($url);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_TIMEOUT => 30,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_USERAGENT =>
        "SolveBugs/1.0",

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2

    ]);

    $imagem =
        curl_exec($ch);

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    $contentType =
        curl_getinfo(
            $ch,
            CURLINFO_CONTENT_TYPE
        );

    curl_close($ch);


    if (
        $imagem === false ||
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        return null;
    }


    // ============================================
    // CONFERE SE É UMA IMAGEM
    // ============================================

    if (
        $contentType &&
        strpos(
            strtolower($contentType),
            "image/"
        ) !== 0
    ) {

        return null;
    }


    // ============================================
    // DETECTA EXTENSÃO
    // ============================================

    $extensao = "jpg";

    if (
        strpos(
            strtolower($contentType),
            "png"
        ) !== false
    ) {

        $extensao = "png";
    } elseif (
        strpos(
            strtolower($contentType),
            "webp"
        ) !== false
    ) {

        $extensao = "webp";
    } elseif (
        strpos(
            strtolower($contentType),
            "gif"
        ) !== false
    ) {

        $extensao = "gif";
    }


    // ============================================
    // CRIA PASTA
    // ============================================

    $pastaCompleta =
        __DIR__ .
        "/" .
        $pasta;

    if (
        !is_dir($pastaCompleta)
    ) {

        mkdir(
            $pastaCompleta,
            0777,
            true
        );
    }


    // ============================================
    // SALVA
    // ============================================

    $nome =
        uniqid(
            "igdb_",
            true
        ) .
        "." .
        $extensao;

    $caminhoCompleto =
        $pastaCompleta .
        "/" .
        $nome;


    if (
        file_put_contents(
            $caminhoCompleto,
            $imagem
        ) === false
    ) {

        return null;
    }


    return
        $pasta .
        "/" .
        $nome;
}


// ============================================================
// BUSCAR CATEGORIAS
// ============================================================

$resultCategorias =
    $conn->query(
        "SELECT id, nome
         FROM categorias
         ORDER BY nome"
    );


// ============================================================
// BUSCAR DESENVOLVEDORAS
// ============================================================

$resultDesenvolvedoras =
    $conn->query(
        "SELECT id, nome
         FROM desenvolvedoras
         ORDER BY nome"
    );


// ============================================================
// BUSCAR PLATAFORMAS
// ============================================================

$resultPlataformas =
    $conn->query(
        "SELECT id, nome
         FROM plataformas
         ORDER BY nome"
    );


// ============================================================
// CADASTRO
// ============================================================

$mensagem = "";

$nomeFormulario = "";
$descricaoFormulario = "";
$dataFormulario = "";
$desenvolvedoraFormulario = "";

$categoriasSelecionadas = [];
$plataformasSelecionadas = [];


// ============================================================
// PROCESSA POST
// ============================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $nome =
        trim(
            $_POST["nome"] ?? ""
        );

    $descricao =
        trim(
            $_POST["descricao"] ?? ""
        );


    $dataLancamento =
        !empty($_POST["data_lancamento"] ?? "")
        ? $_POST["data_lancamento"]
        : null;


    $desenvolvedoraId =
        !empty($_POST["desenvolvedora_id"] ?? "")
        ? intval(
            $_POST["desenvolvedora_id"]
        )
        : null;


    $categoriasSelecionadas =
        isset($_POST["categorias"]) &&
        is_array($_POST["categorias"])
        ? array_map(
            "intval",
            $_POST["categorias"]
        )
        : [];


    $plataformasSelecionadas =
        isset($_POST["plataformas"]) &&
        is_array($_POST["plataformas"])
        ? array_map(
            "intval",
            $_POST["plataformas"]
        )
        : [];


    // ========================================================
    // MANTÉM VALORES NO FORMULÁRIO
    // ========================================================

    $nomeFormulario =
        $nome;

    $descricaoFormulario =
        $descricao;

    $dataFormulario =
        $dataLancamento ?? "";


    // ============================================================
    // VALIDAÇÃO
    // ============================================================

    if (
        $nome === ""
    ) {

        $mensagem =
            "<div class='erro'>
                O nome do jogo é obrigatório.
            </div>";
    } else {


        // ====================================================
        // IMAGENS ENVIADAS MANUALMENTE
        // ====================================================

        $banner =
            salvarImagem(
                $_FILES["banner"] ?? null,
                "uploads/jogos"
            );


        $capa =
            salvarImagem(
                $_FILES["capa"] ?? null,
                "uploads/jogos"
            );


        $logo =
            salvarImagem(
                $_FILES["logo"] ?? null,
                "uploads/jogos"
            );


        $background =
            salvarImagem(
                $_FILES["background"] ?? null,
                "uploads/jogos"
            );


        // ====================================================
        // IMAGENS DA IGDB
        // ====================================================

        $imagemIGDBCapa =
            trim(
                $_POST["igdb_capa"] ?? ""
            );


        $imagemIGDBBackground =
            trim(
                $_POST["igdb_background"] ?? ""
            );


        $imagensIGDB =
            isset($_POST["igdb_imagens"]) &&
            is_array($_POST["igdb_imagens"])
            ? $_POST["igdb_imagens"]
            : [];


        // ====================================================
        // SE NÃO MANDOU CAPA MANUAL,
        // USA A CAPA DA IGDB
        // ====================================================

        if (
            $capa === null &&
            $imagemIGDBCapa !== ""
        ) {

            $capa =
                baixarImagemIGDB(
                    $imagemIGDBCapa,
                    "uploads/jogos"
                );
        }


        // ====================================================
        // SE NÃO MANDOU BACKGROUND MANUAL,
        // USA ARTWORK DA IGDB
        // ====================================================

        if (
            $background === null &&
            $imagemIGDBBackground !== ""
        ) {

            $background =
                baixarImagemIGDB(
                    $imagemIGDBBackground,
                    "uploads/jogos"
                );
        }


        // ====================================================
        // CADASTRAR JOGO
        // ====================================================

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


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            die("Erro ao preparar cadastro: " .
                $conn->error);
        }


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


        if (
            $stmt->execute()
        ) {

            $jogoId =
                $conn->insert_id;


            // =================================================
            // CATEGORIAS
            // =================================================

            if (
                count(
                    $categoriasSelecionadas
                ) > 0
            ) {

                $stmtCategoria =
                    $conn->prepare(
                        "INSERT IGNORE INTO
                         jogos_categorias
                         (
                            id_jogo,
                            id_categoria
                         )
                         VALUES (?, ?)"
                    );


                foreach (
                    $categoriasSelecionadas
                    as $categoriaId
                ) {

                    $stmtCategoria->bind_param(
                        "ii",
                        $jogoId,
                        $categoriaId
                    );

                    $stmtCategoria->execute();
                }


                $stmtCategoria->close();
            }


            // =================================================
            // PLATAFORMAS
            // =================================================

            if (
                count(
                    $plataformasSelecionadas
                ) > 0
            ) {

                $stmtPlataforma =
                    $conn->prepare(
                        "INSERT IGNORE INTO
                         jogo_plataforma
                         (
                            jogo_id,
                            plataforma_id
                         )
                         VALUES (?, ?)"
                    );


                foreach (
                    $plataformasSelecionadas
                    as $plataformaId
                ) {

                    $stmtPlataforma->bind_param(
                        "ii",
                        $jogoId,
                        $plataformaId
                    );

                    $stmtPlataforma->execute();
                }


                $stmtPlataforma->close();
            }


            // =================================================
            // IMAGENS ADICIONAIS DA IGDB
            // =================================================

            if (
                count($imagensIGDB) > 0
            ) {

                $stmtImagem =
                    $conn->prepare(
                        "INSERT INTO
                         imagens_jogo
                         (
                            jogo_id,
                            imagem
                         )
                         VALUES (?, ?)"
                    );


                $contadorImagens = 0;


                foreach (
                    $imagensIGDB
                    as $urlImagem
                ) {

                    // Limite de segurança
                    if (
                        $contadorImagens >= 8
                    ) {
                        break;
                    }


                    $caminho =
                        baixarImagemIGDB(
                            $urlImagem,
                            "uploads/jogos"
                        );


                    if (
                        $caminho !== null
                    ) {

                        $stmtImagem->bind_param(
                            "is",
                            $jogoId,
                            $caminho
                        );

                        $stmtImagem->execute();

                        $contadorImagens++;
                    }
                }


                $stmtImagem->close();
            }


            // =================================================
            // SUCESSO
            // =================================================

            $mensagem =
                "<div class='sucesso'>
                    Jogo cadastrado com sucesso!
                    <br>
                    ID do jogo: " .
                $jogoId .
                "</div>";


            // Limpa formulário
            $nomeFormulario = "";
            $descricaoFormulario = "";
            $dataFormulario = "";

            $categoriasSelecionadas = [];
            $plataformasSelecionadas = [];


            // Recarrega selects

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
        } else {

            $mensagem =
                "<div class='erro'>
                    Erro ao cadastrar jogo:
                    " .
                htmlspecialchars(
                    $stmt->error
                ) .
                "</div>";
        }


        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <link
        rel="stylesheet"
        href="./css/cadastro_jogos.css">

    <title>
        Cadastrar jogo - SolveBugs
    </title>

</head>


<body>


    <!-- =====================================================
     FUNDO
====================================================== -->

    <div class="fundoimg"></div>


    <!-- =====================================================
     BOTÃO VOLTAR
====================================================== -->

    <a
        href="index.php"
        class="Btn">

        <div class="sign">

            <svg
                viewBox="0 0 24 24"
                fill="none">

                <path
                    d="M20 11H7.83l5.59-5.59L12 4l-8 8
                   8 8 1.41-1.41L7.83 13H20v-2z" />

            </svg>

        </div>

        <div class="text">
            Voltar
        </div>

    </a>


    <!-- =====================================================
     CONTAINER
====================================================== -->

    <main class="containerjogo">


        <h1 class="titulo">
            Cadastrar jogo
        </h1>


        <p class="subtitulo">
            Adicione um novo jogo ao SolveBugs
        </p>


        <div class="linha-divisoria"></div>


        <?= $mensagem ?>


        <!-- =================================================
         BUSCA IGDB
    ================================================== -->

        <section class="busca-igdb">

            <h2>
                🎮 Buscar jogo na IGDB
            </h2>

            <p>
                Pesquise o jogo e preencha o formulário
                automaticamente.
            </p>


            <div class="campo-busca-igdb">

                <input
                    type="text"
                    id="buscaIGDB"
                    placeholder="Ex: GTA V, Minecraft, Counter-Strike...">

                <button
                    type="button"
                    id="btnBuscarIGDB">
                    🔎 Buscar
                </button>

            </div>


            <div
                id="mensagemIGDB"
                class="mensagem-igdb"></div>


            <div
                id="resultadosIGDB"
                class="resultados-igdb"></div>

        </section>


        <div class="linha-divisoria"></div>


        <!-- =================================================
         FORMULÁRIO
    ================================================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
            id="formCadastroJogo">


            <!-- =============================================
             CAMPOS IGDB OCULTOS
        ============================================== -->

            <input
                type="hidden"
                name="igdb_capa"
                id="igdb_capa">


            <input
                type="hidden"
                name="igdb_background"
                id="igdb_background">


            <div id="igdbImagensHidden"></div>


            <!-- =============================================
             INFORMAÇÕES
        ============================================== -->

            <div class="grid">


                <div class="form-group">

                    <label>
                        Nome do jogo *
                    </label>

                    <input
                        type="text"
                        name="nome"
                        id="nomeJogo"
                        maxlength="150"
                        placeholder="Ex: Minecraft"
                        value="<?= htmlspecialchars(
                                    $nomeFormulario
                                ) ?>"
                        required>

                </div>


                <div class="form-group">

                    <label>
                        Data de lançamento
                    </label>

                    <input
                        type="date"
                        name="data_lancamento"
                        id="dataLancamento"
                        value="<?= htmlspecialchars(
                                    $dataFormulario
                                ) ?>">

                </div>


            </div>


            <!-- =============================================
             DESCRIÇÃO
        ============================================== -->

            <div class="form-group">

                <label>
                    Descrição
                </label>

                <textarea
                    name="descricao"
                    id="descricaoJogo"
                    placeholder="Digite uma descrição do jogo..."><?= htmlspecialchars(
                                                                        $descricaoFormulario
                                                                    ) ?></textarea>

            </div>


            <!-- =============================================
             DESENVOLVEDORA
        ============================================== -->

            <div class="form-group">

                <label>
                    Desenvolvedora
                </label>


                <select
                    name="desenvolvedora_id"
                    id="desenvolvedoraJogo">

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
                            data-nome="<?= htmlspecialchars(
                                            $desenvolvedora["nome"],
                                            ENT_QUOTES
                                        ) ?>">

                            <?= htmlspecialchars(
                                $desenvolvedora["nome"]
                            ) ?>

                        </option>

                    <?php

                    }

                    ?>

                </select>

            </div>


            <!-- =============================================
             CATEGORIAS
        ============================================== -->

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
                                data-nome="<?= htmlspecialchars(
                                                $categoria["nome"],
                                                ENT_QUOTES
                                            ) ?>">

                            <?= htmlspecialchars(
                                $categoria["nome"]
                            ) ?>

                        </label>

                    <?php

                    }

                    ?>

                </div>

            </div>


            <!-- =============================================
             PLATAFORMAS
        ============================================== -->

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
                                data-nome="<?= htmlspecialchars(
                                                $plataforma["nome"],
                                                ENT_QUOTES
                                            ) ?>">

                            <?= htmlspecialchars(
                                $plataforma["nome"]
                            ) ?>

                        </label>

                        <?php

                        ?>

                    <?php

                    }

                    ?>

                </div>

            </div>


            <div class="linha-divisoria"></div>


            <!-- =============================================
             IMAGENS
        ============================================== -->

            <div class="grid">


                <div class="form-group">

                    <label>
                        Banner
                    </label>

                    <div class="upload">

                        <input
                            type="file"
                            name="banner"
                            accept="image/*">

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
                            accept="image/*">

                        <small>
                            Se não enviar, a capa da IGDB
                            será usada.
                        </small>

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
                            accept="image/*">

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
                            accept="image/*">

                        <small>
                            Se não enviar, uma artwork
                            da IGDB será usada.
                        </small>

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
                            multiple>

                        <small>
                            Você pode selecionar várias.
                        </small>

                    </div>

                </div>


            </div>


            <br>


            <!-- =============================================
             BOTÃO
        ============================================== -->

            <button
                type="submit"
                class="btnEnviar">

                Cadastrar jogo

            </button>


        </form>


    </main>


    <!-- =====================================================
     JAVASCRIPT IGDB
====================================================== -->

    <script>
        const btnBuscar =
            document.getElementById(
                "btnBuscarIGDB"
            );

        const campoBusca =
            document.getElementById(
                "buscaIGDB"
            );

        const resultados =
            document.getElementById(
                "resultadosIGDB"
            );

        const mensagem =
            document.getElementById(
                "mensagemIGDB"
            );


        // ========================================================
        // ENTER PARA PESQUISAR
        // ========================================================

        campoBusca.addEventListener(
            "keydown",
            function(event) {

                if (
                    event.key === "Enter"
                ) {

                    event.preventDefault();

                    pesquisarJogos();
                }

            }
        );


        // ========================================================
        // BOTÃO PESQUISAR
        // ========================================================

        btnBuscar.addEventListener(
            "click",
            pesquisarJogos
        );


        // ========================================================
        // PESQUISAR
        // ========================================================

        async function pesquisarJogos() {

            const nome =
                campoBusca.value.trim();


            if (
                nome === ""
            ) {

                mensagem.innerHTML =
                    "Digite o nome de um jogo.";

                return;
            }


            btnBuscar.disabled =
                true;

            btnBuscar.innerText =
                "🔄 Buscando...";


            mensagem.innerHTML =
                "Consultando a IGDB...";


            resultados.innerHTML =
                "";


            try {

                const resposta =
                    await fetch(
                        "config/igdb.php?acao=pesquisar&nome=" +
                        encodeURIComponent(nome)
                    );


                const dados =
                    await resposta.json();


                if (
                    !resposta.ok ||
                    !dados.sucesso
                ) {

                    throw new Error(
                        dados.erro ||
                        "Erro ao pesquisar."
                    );
                }


                if (
                    !dados.jogos ||
                    dados.jogos.length === 0
                ) {

                    mensagem.innerHTML =
                        "Nenhum jogo encontrado.";

                    return;
                }


                mensagem.innerHTML =
                    dados.jogos.length +
                    " resultado(s) encontrado(s).";


                mostrarResultados(
                    dados.jogos
                );


            } catch (erro) {

                console.error(erro);

                mensagem.innerHTML =
                    "❌ " +
                    erro.message;

            } finally {

                btnBuscar.disabled =
                    false;

                btnBuscar.innerText =
                    "🔎 Buscar";
            }
        }


        // ========================================================
        // MOSTRA RESULTADOS
        // ========================================================

        function mostrarResultados(
            jogos
        ) {

            resultados.innerHTML =
                "";


            jogos.forEach(
                function(jogo) {

                    const card =
                        document.createElement(
                            "div"
                        );


                    card.className =
                        "resultado-igdb";


                    let capa =
                        jogo.capa ||
                        "";


                    let data =
                        jogo.data_lancamento ?
                        formatarData(
                            jogo.data_lancamento
                        ) :
                        "Não informada";


                    let nota =
                        jogo.rating ?
                        Number(
                            jogo.rating
                        ).toFixed(1) :
                        "N/A";


                    let desenvolvedora =
                        jogo.desenvolvedora ||
                        "Não informada";


                    let plataformas =
                        jogo.plataformas &&
                        jogo.plataformas.length ?
                        jogo.plataformas.join(
                            ", "
                        ) :
                        "Não informadas";


                    card.innerHTML = `

                <div class="resultado-imagem">

                    ${
                        capa
                        ?
                        `<img
                            src="${escapeHTML(capa)}"
                            alt="${escapeHTML(jogo.nome)}"
                        >`
                        :
                        `<div>
                            Sem capa
                        </div>`
                    }

                </div>


                <div class="resultado-info">

                    <h3>
                        ${escapeHTML(jogo.nome)}
                    </h3>


                    <p>
                        <strong>
                            Lançamento:
                        </strong>

                        ${data}
                    </p>


                    <p>
                        <strong>
                            Nota:
                        </strong>

                        ⭐ ${nota}
                    </p>


                    <p>
                        <strong>
                            Desenvolvedora:
                        </strong>

                        ${escapeHTML(
                            desenvolvedora
                        )}
                    </p>


                    <p>
                        <strong>
                            Plataformas:
                        </strong>

                        ${escapeHTML(
                            plataformas
                        )}
                    </p>


                    <button
                        type="button"
                        class="btnUsarIGDB"
                    >
                        ➕ Usar este jogo
                    </button>

                </div>

            `;


                    const botao =
                        card.querySelector(
                            ".btnUsarIGDB"
                        );


                    botao.addEventListener(
                        "click",
                        function() {

                            usarJogo(
                                jogo
                            );

                        }
                    );


                    resultados.appendChild(
                        card
                    );

                }
            );
        }


        // ========================================================
        // USA JOGO
        // ========================================================

        function usarJogo(
            jogo
        ) {

            // =====================================================
            // NOME
            // =====================================================

            document.getElementById(
                    "nomeJogo"
                ).value =
                jogo.nome || "";


            // =====================================================
            // DESCRIÇÃO
            // =====================================================

            document.getElementById(
                    "descricaoJogo"
                ).value =
                jogo.descricao || "";


            // =====================================================
            // DATA
            // =====================================================

            document.getElementById(
                    "dataLancamento"
                ).value =
                jogo.data_lancamento || "";


            // =====================================================
            // CAPA
            // =====================================================

            document.getElementById(
                    "igdb_capa"
                ).value =
                jogo.capa || "";


            // =====================================================
            // BACKGROUND
            // =====================================================

            let background =
                "";


            if (
                jogo.artworks &&
                jogo.artworks.length > 0
            ) {

                background =
                    jogo.artworks[0];

            } else if (
                jogo.screenshots &&
                jogo.screenshots.length > 0
            ) {

                background =
                    jogo.screenshots[0];
            }


            document.getElementById(
                    "igdb_background"
                ).value =
                background;


            // =====================================================
            // IMAGENS ADICIONAIS
            // =====================================================

            const container =
                document.getElementById(
                    "igdbImagensHidden"
                );


            container.innerHTML =
                "";


            let imagens = [];


            if (
                jogo.screenshots &&
                jogo.screenshots.length > 0
            ) {

                imagens =
                    jogo.screenshots;

            } else if (
                jogo.artworks &&
                jogo.artworks.length > 0
            ) {

                imagens =
                    jogo.artworks;
            }


            imagens
                .slice(0, 8)
                .forEach(
                    function(url) {

                        const input =
                            document.createElement(
                                "input"
                            );


                        input.type =
                            "hidden";

                        input.name =
                            "igdb_imagens[]";

                        input.value =
                            url;


                        container.appendChild(
                            input
                        );

                    }
                );


            // =====================================================
            // DESENVOLVEDORA
            // =====================================================

            if (
                jogo.desenvolvedora
            ) {

                selecionarDesenvolvedora(
                    jogo.desenvolvedora
                );
            }


            // =====================================================
            // CATEGORIAS
            // =====================================================

            desmarcarCategorias();

            if (
                jogo.generos
            ) {

                jogo.generos.forEach(
                    function(genero) {

                        selecionarCategoria(
                            genero
                        );

                    }
                );
            }


            // =====================================================
            // PLATAFORMAS
            // =====================================================

            desmarcarPlataformas();

            if (
                jogo.plataformas
            ) {

                jogo.plataformas.forEach(
                    function(plataforma) {

                        selecionarPlataforma(
                            plataforma
                        );

                    }
                );
            }


            // =====================================================
            // AVISO
            // =====================================================

            mensagem.innerHTML =
                "✅ Jogo selecionado! Confira os dados abaixo.";


            // =====================================================
            // SCROLL
            // =====================================================

            document.getElementById(
                "formCadastroJogo"
            ).scrollIntoView({
                behavior: "smooth"
            });

        }


        // ========================================================
        // DESENVOLVEDORA
        // ========================================================

        function selecionarDesenvolvedora(
            nome
        ) {

            const select =
                document.getElementById(
                    "desenvolvedoraJogo"
                );


            const opcoes =
                select.querySelectorAll(
                    "option"
                );


            const nomeNormalizado =
                normalizar(
                    nome
                );


            let encontrada =
                false;


            opcoes.forEach(
                function(opcao) {

                    if (
                        normalizar(
                            opcao.dataset.nome || ""
                        ) === nomeNormalizado
                    ) {

                        select.value =
                            opcao.value;

                        encontrada =
                            true;
                    }

                }
            );


            if (!encontrada) {

                select.value =
                    "";
            }

        }


        // ========================================================
        // CATEGORIAS
        // ========================================================

        function selecionarCategoria(
            genero
        ) {

            const mapa = {

                "adventure": [
                    "Aventura"
                ],

                "role-playing-rpg": [
                    "RPG"
                ],

                "rpg": [
                    "RPG"
                ],

                "fighting": [
                    "Luta"
                ],

                "sport": [
                    "Esporte"
                ],

                "arcade": [
                    "Arcade"
                ],

                "quiz-trivia": [
                    "Puzzle"
                ],

                "puzzle": [
                    "Puzzle"
                ]
            };


            const chave =
                normalizar(
                    genero
                );


            if (
                !mapa[chave]
            ) {

                return;
            }


            mapa[chave].forEach(
                function(nomeCategoria) {

                    document
                        .querySelectorAll(
                            'input[name="categorias[]"]'
                        )
                        .forEach(
                            function(input) {

                                if (
                                    normalizar(
                                        input.dataset.nome
                                    ) ===
                                    normalizar(
                                        nomeCategoria
                                    )
                                ) {

                                    input.checked =
                                        true;
                                }

                            }
                        );

                }
            );
        }


        // ========================================================
        // DESMARCA CATEGORIAS
        // ========================================================

        function desmarcarCategorias() {

            document
                .querySelectorAll(
                    'input[name="categorias[]"]'
                )
                .forEach(
                    function(input) {

                        input.checked =
                            false;

                    }
                );
        }


        // ========================================================
        // PLATAFORMAS
        // ========================================================

        function selecionarPlataforma(
            nome
        ) {

            const mapa = {

                "pc": [
                    "PC"
                ],

                "playstation 5": [
                    "PlayStation 5"
                ],

                "xbox series x|s": [
                    "Xbox Series X/S"
                ],

                "nintendo switch": [
                    "Nintendo Switch"
                ]
            };


            const chave =
                normalizar(
                    nome
                );


            if (
                !mapa[chave]
            ) {

                return;
            }


            mapa[chave].forEach(
                function(nomePlataforma) {

                    document
                        .querySelectorAll(
                            'input[name="plataformas[]"]'
                        )
                        .forEach(
                            function(input) {

                                if (
                                    normalizar(
                                        input.dataset.nome
                                    ) ===
                                    normalizar(
                                        nomePlataforma
                                    )
                                ) {

                                    input.checked =
                                        true;
                                }

                            }
                        );

                }
            );
        }


        // ========================================================
        // DESMARCA PLATAFORMAS
        // ========================================================

        function desmarcarPlataformas() {

            document
                .querySelectorAll(
                    'input[name="plataformas[]"]'
                )
                .forEach(
                    function(input) {

                        input.checked =
                            false;

                    }
                );
        }


        // ========================================================
        // NORMALIZA TEXTO
        // ========================================================

        function normalizar(
            texto
        ) {

            return String(
                    texto || ""
                )
                .toLowerCase()
                .normalize(
                    "NFD"
                )
                .replace(
                    /[\u0300-\u036f]/g,
                    ""
                )
                .trim();
        }


        // ========================================================
        // ESCAPA HTML
        // ========================================================

        function escapeHTML(
            texto
        ) {

            return String(
                    texto ?? ""
                )
                .replace(
                    /&/g,
                    "&amp;"
                )
                .replace(
                    /</g,
                    "&lt;"
                )
                .replace(
                    />/g,
                    "&gt;"
                )
                .replace(
                    /"/g,
                    "&quot;"
                )
                .replace(
                    /'/g,
                    "&#039;"
                );
        }


        // ========================================================
        // FORMATA DATA
        // ========================================================

        function formatarData(
            data
        ) {

            const partes =
                data.split("-");


            if (
                partes.length !== 3
            ) {

                return data;
            }


            return (
                partes[2] +
                "/" +
                partes[1] +
                "/" +
                partes[0]
            );
        }
    </script>


</body>

</html>


<?php

$conn->close();

?>

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

        // Se veio um comentário pai,
        // então é uma resposta
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
// ORGANIZAR COMENTÁRIOS
// =====================================================

$comentariosPrincipais = [];

$respostas = [];

foreach ($comentarios as $comentario) {

    if (
        empty($comentario["comentario_pai_id"])
    ) {

        $comentariosPrincipais[] =
            $comentario;

    } else {

        $respostas[
            $comentario["comentario_pai_id"]
        ][] = $comentario;
    }
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

    <link rel="stylesheet" href="./css/jogo.css">

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


                    <!-- =================================
                         COMENTÁRIO PRINCIPAL
                    ================================== -->

                    <div
                        class="comentario"
                        data-texto="<?= htmlspecialchars(
                            strtolower(
                                $comentario["texto"]
                            )
                        ) ?>"
                    >


                        <!-- USUÁRIO -->

                        <div class="topo">

                            <i
                                class="fa-solid fa-user icone"
                            ></i>


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

                                <button type="submit">

                                    👍
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

                                <button type="submit">

                                    👎
                                    <?= intval($comentario["dislikes"]) ?>

                                </button>

                            </form>


                            <!-- RESPONDER -->

                            <?php if ($logado): ?>

                                <button
                                    type="button"
                                    class="btn-responder"
                                    onclick="responderComentario(
                                        <?= intval($comentario["id"]) ?>,
                                        '<?= htmlspecialchars(
                                            $comentario["nome"],
                                            ENT_QUOTES
                                        ) ?>'
                                    )"
                                >

                                    ↩ Responder

                                </button>

                            <?php endif; ?>


                        </div>


                        <!-- =================================
                             RESPOSTAS
                        ================================== -->

                        <?php if (
                            isset(
                                $respostas[
                                    $comentario["id"]
                                ]
                            )
                        ): ?>

                            <div class="respostas">


                                <?php foreach (
                                    $respostas[
                                        $comentario["id"]
                                    ]
                                    as $resposta
                                ): ?>


                                    <div
                                        class="comentario"
                                        data-texto="<?= htmlspecialchars(
                                            strtolower(
                                                $resposta["texto"]
                                            )
                                        ) ?>"
                                    >


                                        <!-- USUÁRIO -->

                                        <div class="topo">

                                            <i
                                                class="fa-solid fa-user icone"
                                            ></i>


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

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $resposta["texto"]
                                                )
                                            ) ?>

                                        </p>


                                        <!-- AÇÕES -->

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
                                                    value="<?= intval($resposta["id"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="tipo"
                                                    value="like"
                                                >

                                                <button type="submit">

                                                    👍
                                                    <?= intval($resposta["likes"]) ?>

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
                                                    value="<?= intval($resposta["id"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="tipo"
                                                    value="dislike"
                                                >

                                                <button type="submit">

                                                    👎
                                                    <?= intval($resposta["dislikes"]) ?>

                                                </button>

                                            </form>


                                            <!-- RESPONDER RESPOSTA -->

                                            <?php if ($logado): ?>

                                                <button
                                                    type="button"
                                                    class="btn-responder"
                                                    onclick="responderComentario(
                                                        <?= intval($resposta["id"]) ?>,
                                                        '<?= htmlspecialchars(
                                                            $resposta["nome"],
                                                            ENT_QUOTES
                                                        ) ?>'
                                                    )"
                                                >

                                                    ↩ Responder

                                                </button>

                                            <?php endif; ?>


                                        </div>


                                    </div>


                                <?php endforeach; ?>


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


                    <!-- ID DO COMENTÁRIO PAI -->

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
        // PESQUISAR COMENTÁRIOS
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
                            .toLowerCase()
                            .trim();


                    const comentarios =
                        document.querySelectorAll(
                            ".comentario"
                        );


                    comentarios.forEach(
                        function (comentario) {

                            const conteudo =
                                comentario.dataset.texto
                                    .toLowerCase();


                            if (
                                conteudo.includes(texto)
                            ) {

                                comentario.style.display =
                                    "";

                            } else {

                                comentario.style.display =
                                    "none";
                            }

                        }
                    );

                }
            );

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


            if (!comentarioPai) {
                return;
            }


            comentarioPai.value = id;


            nomeRespondendo.textContent =
                nome;


            respondendo.style.display =
                "block";


            input.placeholder =
                "Escreva sua resposta...";


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

<input
    type="hidden"
    name="comentario_pai_id"
    id="comentarioPai"
    value=""
>

<?php

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conn.php";

exigirLogin();


// ======================================================
// ID DO USUÁRIO LOGADO
// ======================================================

$usuarioId = $_SESSION["usuario_id"];


// ======================================================
// CONFIGURAÇÕES
// ======================================================

$mensagem = "";
$erro = "";


// Pasta onde as fotos serão armazenadas
$pastaUpload = __DIR__ . "/img/perfis/";


// Caminho que será salvo no banco
$pastaBanco = "img/perfis/";


// Cria a pasta caso ela ainda não exista
if (!is_dir($pastaUpload)) {

    mkdir($pastaUpload, 0755, true);

}


// ======================================================
// BUSCAR USUÁRIO
// ======================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        email,
        senha,
        tipo,
        data_cadastro,
        foto_perfil
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


// Se o usuário não existir
if (!$usuario) {

    header("Location: logout.php");
    exit;

}


// ======================================================
// FOTO ATUAL
// ======================================================

$fotoAtual = !empty($usuario["foto_perfil"])
    ? $usuario["foto_perfil"]
    : "img/perfilIcon.avif";


// ======================================================
// PROCESSAR FORMULÁRIOS
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // ==================================================
    // ALTERAR DADOS
    // ==================================================

    if (isset($_POST["acao"]) && $_POST["acao"] === "dados") {

        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");


        // ------------------------------
        // VALIDAR NOME
        // ------------------------------

        if ($nome === "") {

            $erro = "O nome não pode ficar vazio.";

        }

        elseif (strlen($nome) < 3) {

            $erro = "O nome deve ter pelo menos 3 caracteres.";

        }


        // ------------------------------
        // VALIDAR E-MAIL
        // ------------------------------

        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = "Digite um e-mail válido.";

        }


        // ------------------------------
        // VERIFICAR E-MAIL DUPLICADO
        // ------------------------------

        if ($erro === "") {

            $stmtEmail = $pdo->prepare("
                SELECT id
                FROM usuarios
                WHERE email = ?
                AND id != ?
            ");

            $stmtEmail->execute([
                $email,
                $usuarioId
            ]);

            if ($stmtEmail->fetch()) {

                $erro = "Esse e-mail já está sendo usado por outro usuário.";

            }

        }


        // ------------------------------
        // SALVAR
        // ------------------------------

        if ($erro === "") {

            $stmtUpdate = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?, email = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $nome,
                $email,
                $usuarioId
            ]);


            $mensagem = "Dados atualizados com sucesso!";


            // Atualiza os dados na tela
            $usuario["nome"] = $nome;
            $usuario["email"] = $email;

        }

    }


    // ==================================================
    // ALTERAR SENHA
    // ==================================================

    elseif (
        isset($_POST["acao"])
        &&
        $_POST["acao"] === "senha"
    ) {

        $senhaAtual = $_POST["senha_atual"] ?? "";
        $novaSenha = $_POST["nova_senha"] ?? "";
        $confirmarSenha = $_POST["confirmar_senha"] ?? "";


        // ------------------------------
        // VERIFICAR SENHA ATUAL
        // ------------------------------

        if ($senhaAtual === "") {

            $erro = "Digite sua senha atual.";

        }

        elseif (
            !password_verify(
                $senhaAtual,
                $usuario["senha"]
            )
        ) {

            $erro = "A senha atual está incorreta.";

        }


        // ------------------------------
        // TAMANHO DA NOVA SENHA
        // ------------------------------

        elseif (strlen($novaSenha) < 6) {

            $erro = "A nova senha deve ter pelo menos 6 caracteres.";

        }


        // ------------------------------
        // CONFIRMAR SENHA
        // ------------------------------

        elseif ($novaSenha !== $confirmarSenha) {

            $erro = "As novas senhas não são iguais.";

        }


        // ------------------------------
        // SALVAR NOVA SENHA
        // ------------------------------

        if ($erro === "") {

            $senhaHash = password_hash(
                $novaSenha,
                PASSWORD_DEFAULT
            );


            $stmtSenha = $pdo->prepare("
                UPDATE usuarios
                SET senha = ?
                WHERE id = ?
            ");

            $stmtSenha->execute([
                $senhaHash,
                $usuarioId
            ]);


            $mensagem = "Senha alterada com sucesso!";

        }

    }


    // ==================================================
    // ALTERAR FOTO
    // ==================================================

    elseif (
        isset($_POST["acao"])
        &&
        $_POST["acao"] === "foto"
    ) {


        // Verifica se enviou arquivo
        if (
            !isset($_FILES["foto"])
            ||
            $_FILES["foto"]["error"] !== UPLOAD_ERR_OK
        ) {

            $erro = "Selecione uma imagem para enviar.";

        }


        else {

            $arquivo = $_FILES["foto"];


            // ------------------------------
            // TAMANHO MÁXIMO
            // ------------------------------

            $tamanhoMaximo = 20 * 1024 * 1024;


            if ($arquivo["size"] > $tamanhoMaximo) {

                $erro = "A imagem deve ter no máximo 2 MB.";

            }


            // ------------------------------
            // VERIFICAR MIME
            // ------------------------------

            if ($erro === "") {

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $tipoArquivo =
                    $finfo->file($arquivo["tmp_name"]);


                $tiposPermitidos = [

                    "image/jpeg" => "jpg",

                    "image/png" => "png",

                    "image/webp" => "webp"

                ];


                if (
                    !isset(
                        $tiposPermitidos[$tipoArquivo]
                    )
                ) {

                    $erro =
                        "Formato inválido. Use JPG, PNG ou WEBP.";

                }

            }


            // ------------------------------
            // CRIAR NOME SEGURO
            // ------------------------------

            if ($erro === "") {

                $extensao =
                    $tiposPermitidos[$tipoArquivo];


                $nomeArquivo =
                    "perfil_" .
                    $usuarioId .
                    "_" .
                    bin2hex(random_bytes(8)) .
                    "." .
                    $extensao;


                $destino =
                    $pastaUpload .
                    $nomeArquivo;


                // ------------------------------
                // MOVER ARQUIVO
                // ------------------------------

                if (
                    move_uploaded_file(
                        $arquivo["tmp_name"],
                        $destino
                    )
                ) {


                    // --------------------------
                    // APAGAR FOTO ANTIGA
                    // --------------------------

                    if (!empty($usuario["foto_perfil"])) {

                        $fotoAntiga =
                            __DIR__ .
                            "/" .
                            $usuario["foto_perfil"];


                        if (
                            file_exists($fotoAntiga)
                        ) {

                            unlink($fotoAntiga);

                        }

                    }


                    // --------------------------
                    // SALVAR NO BANCO
                    // --------------------------

                    $caminhoBanco =
                        $pastaBanco .
                        $nomeArquivo;


                    $stmtFoto = $pdo->prepare("
                        UPDATE usuarios
                        SET foto_perfil = ?
                        WHERE id = ?
                    ");


                    $stmtFoto->execute([
                        $caminhoBanco,
                        $usuarioId
                    ]);


                    $usuario["foto_perfil"] =
                        $caminhoBanco;


                    $fotoAtual =
                        $caminhoBanco;


                    $mensagem =
                        "Foto de perfil alterada com sucesso!";

                }

                else {

                    $erro =
                        "Não foi possível salvar a imagem.";

                }

            }

        }

    }


    // ==================================================
    // EXCLUIR CONTA
    // ==================================================

    elseif (
        isset($_POST["acao"])
        &&
        $_POST["acao"] === "excluir"
    ) {

        $senhaExcluir =
            $_POST["senha_excluir"] ?? "";


        // ------------------------------
        // CONFIRMAR SENHA
        // ------------------------------

        if ($senhaExcluir === "") {

            $erro =
                "Digite sua senha para excluir a conta.";

        }

        elseif (
            !password_verify(
                $senhaExcluir,
                $usuario["senha"]
            )
        ) {

            $erro =
                "Senha incorreta. A conta não foi excluída.";

        }


        // ------------------------------
        // EXCLUIR
        // ------------------------------

        if ($erro === "") {


            // Apagar foto do usuário
            if (!empty($usuario["foto_perfil"])) {

                $fotoExcluir =
                    __DIR__ .
                    "/" .
                    $usuario["foto_perfil"];


                if (
                    file_exists($fotoExcluir)
                ) {

                    unlink($fotoExcluir);

                }

            }


            // Apagar usuário
            $stmtDelete = $pdo->prepare("
                DELETE FROM usuarios
                WHERE id = ?
            ");

            $stmtDelete->execute([
                $usuarioId
            ]);


            // Limpar sessão
            $_SESSION = [];


            if (ini_get("session.use_cookies")) {

                $params =
                    session_get_cookie_params();


                setcookie(
                    session_name(),
                    "",
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );

            }


            session_destroy();


            // Voltar para o início
            header("Location: index.php");
            exit;

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
        content="width=device-width, initial-scale=1.0">

    <title>SolveBug - Minha conta</title>

    <link
        rel="stylesheet"
        href="css/Usuario.css">

</head>


<body>


<a
    class="close-btn"
    href="index.php">

    Voltar

</a>


<div class="container">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <div class="profile">


            <div class="foto-container">

                <img
                    src="<?= htmlspecialchars($fotoAtual) ?>"
                    alt="Foto de perfil"
                    class="foto-perfil">

            </div>


            <h3>

                <?= htmlspecialchars($usuario["nome"]) ?>

            </h3>


            <p>

                <?= htmlspecialchars($usuario["email"]) ?>

            </p>


        </div>


        <ul class="menu">


            <li class="ativo">

                ⭐ Minha conta

            </li>


            <li onclick="mostrarDados()">

                👤 Dados da conta

            </li>


            <li onclick="mostrarSenha()">

                🔒 Segurança

            </li>


            <li onclick="mostrarExcluir()">

                ⚠️ Excluir conta

            </li>


            <li>

                <a href="logout.php">

                    🚪 Sair

                </a>

            </li>


        </ul>


    </aside>



    <!-- ==================================================
         CONTEÚDO PRINCIPAL
    ================================================== -->

    <main class="main">


        <div class="header">

            <div>

                <h2>

                    Minha conta

                </h2>


                <p class="subtitulo">

                    Gerencie seus dados do SolveBug

                </p>

            </div>

        </div>



        <!-- ==================================================
             MENSAGENS
        ================================================== -->

        <?php if ($mensagem !== ""): ?>

            <div class="mensagem sucesso">

                <?= htmlspecialchars($mensagem) ?>

            </div>

        <?php endif; ?>


        <?php if ($erro !== ""): ?>

            <div class="mensagem erro">

                <?= htmlspecialchars($erro) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             PERFIL
        ================================================== -->

        <section class="perfil-card">


            <div class="perfil-foto-area">


                <img
                    src="<?= htmlspecialchars($fotoAtual) ?>"
                    alt="Foto de perfil"
                    class="foto-grande">


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="form-foto">


                    <input
                        type="hidden"
                        name="acao"
                        value="foto">


                    <label
                        for="foto"
                        class="botao">

                        📷 Alterar foto

                    </label>


                    <input
                        type="file"
                        id="foto"
                        name="foto"
                        accept="image/jpeg,image/png,image/webp"
                        onchange="this.form.submit()"
                        hidden>


                    <small>

                        JPG, PNG ou WEBP — máximo 2 MB

                    </small>


                </form>


            </div>



            <div class="perfil-info">


                <h2>

                    <?= htmlspecialchars($usuario["nome"]) ?>

                </h2>


                <p>

                    <?= htmlspecialchars($usuario["email"]) ?>

                </p>


                <span class="tipo-conta">

                    <?= htmlspecialchars($usuario["tipo"]) ?>

                </span>


            </div>


        </section>



        <!-- ==================================================
             DADOS DA CONTA
        ================================================== -->

        <section
            class="painel"
            id="dados">


            <div class="painel-header">

                <div>

                    <h2>

                        👤 Dados da conta

                    </h2>

                    <p>

                        Altere suas informações pessoais.

                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="formulario">


                <input
                    type="hidden"
                    name="acao"
                    value="dados">


                <div class="campo">

                    <label for="nome">

                        Nome de usuário

                    </label>


                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        value="<?= htmlspecialchars($usuario["nome"]) ?>"
                        minlength="3"
                        required>

                </div>



                <div class="campo">

                    <label for="email">

                        E-mail

                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($usuario["email"]) ?>"
                        required>

                </div>



                <button
                    type="submit"
                    class="botao salvar">

                    💾 Salvar alterações

                </button>


            </form>

        </section>



        <!-- ==================================================
             INFORMAÇÕES DA CONTA
        ================================================== -->

        <section class="info-grid">


            <div class="card">

                <h3>

                    Tipo de conta

                </h3>


                <p>

                    <?= htmlspecialchars($usuario["tipo"]) ?>

                </p>

            </div>



            <div class="card">

                <h3>

                    Cadastro

                </h3>


                <p>

                    <?= date(
                        "d/m/Y H:i",
                        strtotime(
                            $usuario["data_cadastro"]
                        )
                    ) ?>

                </p>

            </div>


        </section>



        <!-- ==================================================
             SEGURANÇA
        ================================================== -->

        <section
            class="painel"
            id="seguranca">


            <div class="painel-header">

                <div>

                    <h2>

                        🔒 Segurança

                    </h2>


                    <p>

                        Altere a senha da sua conta.

                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="formulario">


                <input
                    type="hidden"
                    name="acao"
                    value="senha">


                <div class="campo">

                    <label for="senha_atual">

                        Senha atual

                    </label>


                    <input
                        type="password"
                        id="senha_atual"
                        name="senha_atual"
                        required>

                </div>



                <div class="campo">

                    <label for="nova_senha">

                        Nova senha

                    </label>


                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        minlength="6"
                        required>


                    <small>

                        A senha deve ter pelo menos 6 caracteres.

                    </small>

                </div>



                <div class="campo">

                    <label for="confirmar_senha">

                        Confirmar nova senha

                    </label>


                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        minlength="6"
                        required>

                </div>



                <button
                    type="submit"
                    class="botao salvar">

                    🔑 Alterar senha

                </button>


            </form>

        </section>



        <!-- ==================================================
             EXCLUIR CONTA
        ================================================== -->

        <section
            class="painel perigo"
            id="excluir">


            <div class="painel-header">

                <div>

                    <h2>

                        ⚠️ Excluir conta

                    </h2>


                    <p>

                        Esta ação não poderá ser desfeita.

                    </p>

                </div>

            </div>


            <button
                type="button"
                class="botao botao-perigo"
                onclick="abrirExcluir()">

                🗑️ Excluir minha conta

            </button>


        </section>


    </main>

</div>



<!-- ==================================================
     MODAL EXCLUIR CONTA
================================================== -->

<div
    class="modal"
    id="modalExcluir">


    <div class="modal-conteudo">


        <button
            class="modal-fechar"
            onclick="fecharExcluir()">

            ×

        </button>


        <h2>

            ⚠️ Excluir conta

        </h2>


        <p>

            Tem certeza que deseja excluir sua conta?

        </p>


        <p>

            Todos os seus dados serão removidos
            permanentemente.

        </p>


        <form
            method="POST">


            <input
                type="hidden"
                name="acao"
                value="excluir">


            <div class="campo">

                <label for="senha_excluir">

                    Digite sua senha para confirmar:

                </label>


                <input
                    type="password"
                    id="senha_excluir"
                    name="senha_excluir"
                    required>

            </div>


            <div class="modal-botoes">


                <button
                    type="button"
                    class="botao"
                    onclick="fecharExcluir()">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="botao botao-perigo">

                    Sim, excluir minha conta

                </button>


            </div>


        </form>


    </div>

</div>



<script>


// ==================================================
// MOSTRAR DADOS
// ==================================================

function mostrarDados() {

    document
        .getElementById("dados")
        .scrollIntoView({
            behavior: "smooth"
        });

}


// ==================================================
// MOSTRAR SEGURANÇA
// ==================================================

function mostrarSenha() {

    document
        .getElementById("seguranca")
        .scrollIntoView({
            behavior: "smooth"
        });

}


// ==================================================
// MOSTRAR EXCLUSÃO
// ==================================================

function mostrarExcluir() {

    document
        .getElementById("excluir")
        .scrollIntoView({
            behavior: "smooth"
        });

}


// ==================================================
// ABRIR MODAL
// ==================================================

function abrirExcluir() {

    document
        .getElementById("modalExcluir")
        .style.display = "flex";

}


// ==================================================
// FECHAR MODAL
// ==================================================

function fecharExcluir() {

    document
        .getElementById("modalExcluir")
        .style.display = "none";

}


// ==================================================
// FECHAR CLICANDO FORA
// ==================================================

document
    .getElementById("modalExcluir")
    .addEventListener("click", function(event) {

        if (event.target === this) {

            fecharExcluir();

        }

    });


</script>


</body>

</html>
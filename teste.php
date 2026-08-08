```php
<?php

// ==============================
// CONEXÃO COM O BANCO DE DADOS
// ==============================

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "meu_banco";

$conexao = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco
);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");


// ==============================
// UPLOAD DA IMAGEM
// ==============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST["nome"] ?? "";

    // Verifica se uma imagem foi enviada
    if (isset($_FILES["imagem"]) && $_FILES["imagem"]["error"] === UPLOAD_ERR_OK) {

        $arquivo = $_FILES["imagem"];

        $tmp = $arquivo["tmp_name"];
        $tamanho = $arquivo["size"];

        // Verifica o tipo real do arquivo
        $tipo = mime_content_type($tmp);

        $tiposPermitidos = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp"
        ];

        if (!in_array($tipo, $tiposPermitidos)) {
            die("Tipo de imagem não permitido.");
        }

        // Limite de 5 MB
        if ($tamanho > 5 * 1024 * 1024) {
            die("A imagem não pode ter mais de 5 MB.");
        }

        // Define a extensão
        switch ($tipo) {

            case "image/jpeg":
                $extensao = "jpg";
                break;

            case "image/png":
                $extensao = "png";
                break;

            case "image/gif":
                $extensao = "gif";
                break;

            case "image/webp":
                $extensao = "webp";
                break;

            default:
                die("Extensão inválida.");
        }


        // ==============================
        // CRIA UM NOME ÚNICO
        // ==============================

        $novoNome = uniqid() . "." . $extensao;


        // ==============================
        // CAMINHO DA IMAGEM
        // ==============================

        $caminho = "uploads/" . $novoNome;


        // ==============================
        // MOVE A IMAGEM PARA O SERVIDOR
        // ==============================

        if (move_uploaded_file($tmp, $caminho)) {

            // ==============================
            // SALVA NO BANCO DE DADOS
            // ==============================

            $sql = "INSERT INTO usuarios (nome, imagem) VALUES (?, ?)";

            $stmt = $conexao->prepare($sql);

            $stmt->bind_param(
                "ss",
                $nome,
                $caminho
            );

            if ($stmt->execute()) {

                echo "Imagem enviada com sucesso!";

                echo "<br><br>";

                echo "<img src='" .
                    htmlspecialchars($caminho) .
                    "' width='200'>";

            } else {

                echo "Erro ao salvar no banco de dados.";
            }

            $stmt->close();

        } else {

            echo "Erro ao mover a imagem.";
        }

    } else {

        echo "Nenhuma imagem foi enviada.";
    }
}

$conexao->close();

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Upload de imagem</title>

</head>

<body>

    <h1>Enviar imagem</h1>

    <form action="" method="POST" enctype="multipart/form-data">

        <label>
            Nome:
        </label>

        <br>

        <input
            type="text"
            name="nome"
            required
        >

        <br><br>

        <label>
            Imagem:
        </label>

        <br>

        <input
            type="file"
            name="imagem"
            accept="image/*"
            required
        >

        <br><br>

        <button type="submit">
            Enviar
        </button>

    </form>

</body>

</html>
```

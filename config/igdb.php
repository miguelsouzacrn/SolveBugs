
<?php

// ============================================================
// CONFIGURAÇÕES IGDB / TWITCH
// ============================================================
//
// IMPORTANTE:
// Coloque aqui o NOVO Client Secret gerado na Twitch.
// Não compartilhe esse arquivo publicamente.
//
// ============================================================

$igdbClientId = "vtvwtbg7y0g4uhttkrb1vooav26i6t";
$igdbClientSecret = "odmpp4ak28iq662jtybbr0n16cves1";


// ============================================================
// OBTÉM TOKEN DA TWITCH
// ============================================================

function obterTokenIGDB()
{
    global $igdbClientId, $igdbClientSecret;

    $url = "https://id.twitch.tv/oauth2/token";

    $dados = [
        "client_id" => $igdbClientId,
        "client_secret" => $igdbClientSecret,
        "grant_type" => "client_credentials"
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($dados),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20
    ]);

    $resposta = curl_exec($ch);

    if ($resposta === false) {
        $erro = curl_error($ch);
        curl_close($ch);

        throw new Exception(
            "Erro ao conectar com a Twitch: " . $erro
        );
    }

    $codigoHTTP = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    $resultado = json_decode(
        $resposta,
        true
    );

    if (
        $codigoHTTP < 200 ||
        $codigoHTTP >= 300 ||
        !isset($resultado["access_token"])
    ) {
        throw new Exception(
            "Erro ao obter token da Twitch."
        );
    }

    return $resultado["access_token"];
}


// ============================================================
// CONVERTE URL DE IMAGEM DA IGDB
// ============================================================

function imagemIGDB($url, $tamanho = "t_cover_big")
{
    if (empty($url)) {
        return null;
    }

    // A IGDB retorna normalmente:
    // //images.igdb.com/igdb/image/upload/t_thumb/...
    //
    // Transformamos para HTTPS e tamanho desejado.

    if (strpos($url, "//") === 0) {
        $url = "https:" . $url;
    }

    $url = str_replace(
        "/t_thumb/",
        "/" . $tamanho . "/",
        $url
    );

    return $url;
}


// ============================================================
// PESQUISA JOGO NA IGDB
// ============================================================

function pesquisarIGDB($nomeJogo)
{
    global $igdbClientId;

    $nomeJogo = trim($nomeJogo);

    if ($nomeJogo === "") {
        return [];
    }

    $token = obterTokenIGDB();

    $url = "https://api.igdb.com/v4/games";

    /*
     * Buscamos:
     *
     * id
     * nome
     * descrição
     * capa
     * artworks
     * screenshots
     * data
     * avaliações
     * gêneros
     * plataformas
     * desenvolvedoras
     */

    $nomeSeguro = addslashes($nomeJogo);

    $consulta = '
        search "' . $nomeSeguro . '";

        fields
            id,
            name,
            summary,
            cover.url,
            artworks.url,
            screenshots.url,
            first_release_date,
            rating,
            total_rating,
            genres.name,
            platforms.name,
            involved_companies.company.name,
            involved_companies.developer;

        limit 10;
    ';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $consulta,

        CURLOPT_HTTPHEADER => [
            "Client-ID: " . $igdbClientId,
            "Authorization: Bearer " . $token,
            "Content-Type: text/plain"
        ],

        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $resposta = curl_exec($ch);

    if ($resposta === false) {

        $erro = curl_error($ch);

        curl_close($ch);

        throw new Exception(
            "Erro ao conectar com a IGDB: " . $erro
        );
    }

    $codigoHTTP = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    $resultado = json_decode(
        $resposta,
        true
    );

    if (
        $codigoHTTP < 200 ||
        $codigoHTTP >= 300
    ) {
        throw new Exception(
            "Erro da IGDB. HTTP: " . $codigoHTTP
        );
    }

    if (!is_array($resultado)) {
        throw new Exception(
            "Resposta inválida da IGDB."
        );
    }

    return $resultado;
}


// ============================================================
// API AJAX
// ============================================================
//
// Exemplo:
//
// igdb.php?acao=pesquisar&nome=GTA V
//
// ============================================================

if (
    isset($_GET["acao"]) &&
    $_GET["acao"] === "pesquisar"
) {

    header(
        "Content-Type: application/json; charset=utf-8"
    );

    try {

        $nome = $_GET["nome"] ?? "";

        $resultados = pesquisarIGDB($nome);

        $jogos = [];

        foreach ($resultados as $jogo) {

            $capas = [];

            if (
                isset($jogo["cover"]["url"])
            ) {
                $capas[] =
                    imagemIGDB(
                        $jogo["cover"]["url"],
                        "t_cover_big"
                    );
            }

            $artworks = [];

            if (
                isset($jogo["artworks"]) &&
                is_array($jogo["artworks"])
            ) {

                foreach (
                    $jogo["artworks"]
                    as $artwork
                ) {

                    if (
                        isset($artwork["url"])
                    ) {

                        $artworks[] =
                            imagemIGDB(
                                $artwork["url"],
                                "t_1080p"
                            );
                    }
                }
            }

            $screenshots = [];

            if (
                isset($jogo["screenshots"]) &&
                is_array($jogo["screenshots"])
            ) {

                foreach (
                    $jogo["screenshots"]
                    as $screenshot
                ) {

                    if (
                        isset($screenshot["url"])
                    ) {

                        $screenshots[] =
                            imagemIGDB(
                                $screenshot["url"],
                                "t_1080p"
                            );
                    }
                }
            }


            // ============================================
            // DATA
            // ============================================

            $dataLancamento = null;

            if (
                isset(
                    $jogo["first_release_date"]
                )
            ) {

                $dataLancamento =
                    date(
                        "Y-m-d",
                        $jogo["first_release_date"]
                    );
            }


            // ============================================
            // GÊNEROS
            // ============================================

            $generos = [];

            if (
                isset($jogo["genres"]) &&
                is_array($jogo["genres"])
            ) {

                foreach (
                    $jogo["genres"]
                    as $genero
                ) {

                    if (
                        isset($genero["name"])
                    ) {

                        $generos[] =
                            $genero["name"];
                    }
                }
            }


            // ============================================
            // PLATAFORMAS
            // ============================================

            $plataformas = [];

            if (
                isset($jogo["platforms"]) &&
                is_array($jogo["platforms"])
            ) {

                foreach (
                    $jogo["platforms"]
                    as $plataforma
                ) {

                    if (
                        isset($plataforma["name"])
                    ) {

                        $plataformas[] =
                            $plataforma["name"];
                    }
                }
            }


            // ============================================
            // DESENVOLVEDORA
            // ============================================

            $desenvolvedora = null;

            if (
                isset(
                    $jogo["involved_companies"]
                ) &&
                is_array(
                    $jogo["involved_companies"]
                )
            ) {

                foreach (
                    $jogo["involved_companies"]
                    as $empresa
                ) {

                    if (
                        isset(
                            $empresa["developer"]
                        ) &&
                        $empresa["developer"] === true &&
                        isset(
                            $empresa["company"]["name"]
                        )
                    ) {

                        $desenvolvedora =
                            $empresa["company"]["name"];

                        break;
                    }
                }

                // Caso não tenha encontrado uma
                // marcada como developer

                if ($desenvolvedora === null) {

                    foreach (
                        $jogo["involved_companies"]
                        as $empresa
                    ) {

                        if (
                            isset(
                                $empresa["company"]["name"]
                            )
                        ) {

                            $desenvolvedora =
                                $empresa["company"]["name"];

                            break;
                        }
                    }
                }
            }


            $jogos[] = [

                "id" =>
                    $jogo["id"] ?? null,

                "nome" =>
                    $jogo["name"] ?? "",

                "descricao" =>
                    $jogo["summary"] ?? "",

                "capa" =>
                    $capas[0] ?? null,

                "artworks" =>
                    $artworks,

                "screenshots" =>
                    $screenshots,

                "data_lancamento" =>
                    $dataLancamento,

                "rating" =>
                    $jogo["rating"] ?? null,

                "total_rating" =>
                    $jogo["total_rating"] ?? null,

                "generos" =>
                    $generos,

                "plataformas" =>
                    $plataformas,

                "desenvolvedora" =>
                    $desenvolvedora
            ];
        }


        echo json_encode(
            [
                "sucesso" => true,
                "jogos" => $jogos
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

    } catch (Exception $e) {

        http_response_code(500);

        echo json_encode(
            [
                "sucesso" => false,
                "erro" => $e->getMessage()
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    exit;
}
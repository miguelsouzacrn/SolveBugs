<?php

require_once __DIR__ . "/config/igdb.php";

$resultados = pesquisarIGDB("GTA V");

echo "<pre>";
print_r($resultados);
echo "</pre>";
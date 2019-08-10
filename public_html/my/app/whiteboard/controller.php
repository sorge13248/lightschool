<?php
require_once __DIR__ . "/../../../etc/core.php";

header('Content-type:application/json;charset=utf-8');

require_once "model.php";

$type = isset($_GET["type"]) ? $_GET["type"] : null;
$response = [];

if ($type === "code") {
    $response = \FrancescoSorge\PHP\LightSchool\WhiteBoard::code();

    if (isset($response["code"])) {
        $response["text"] = "Il codice della LIM &egrave;";
    }
} else if ($type === "files") {
    require_once __DIR__ . "/../file-manager/model.php";
    require_once __DIR__ . "/../share/model.php";
    $response = \FrancescoSorge\PHP\LightSchool\WhiteBoard::files();

    if (isset($response["text"])) {
        switch ($response["text"]) {
            case "cookie":
                $response["text"] = "Errore nel cookie";
                break;
            case "code":
                $response["text"] = "Codice LIM scaduto o non valido";
                break;
        }
    }
} else {
    http_response_code(404);
}

echo(json_encode($response));
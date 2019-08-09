<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "create") {
        $folder = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;
        $name = isset($_POST["name"]) && $_POST["name"] !== "" ? $_POST["name"] : null;
        $content = isset($_POST["content"]) ? $_POST["content"] : null;

        require_once __DIR__ . "/../file-manager/model.php";

        $response = \FrancescoSorge\PHP\LightSchool\Writer::create($name, $content, (int)$folder);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Quaderno creato";
                break;
            case "already":
                $response["text"] = "Esiste gi&agrave; un file con questo nome in questa cartella";
                break;
            case "ownership":
                $response["text"] = "La cartalla non &egrave; di tua propriet&agrave;";
                break;
            case "invalid_id":
                $response["text"] = "ID non valido";
                break;
            case "name":
                $response["text"] = "Il nome del quaderno &egrave; obbligatorio";
                break;
        }
    } else if ($type === "edit") {
        $id = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;
        $name = isset($_POST["name"]) && $_POST["name"] !== "" ? $_POST["name"] : null;
        $content = isset($_POST["content"]) ? $_POST["content"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\Writer::edit($id, $name, $content);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "File modificato";
                break;
            case "invalid_id":
                $response["text"] = "ID non valido";
                break;
        }
    } else {
        $response = [];
        $response["response"] = "error";
        $response["text"] = "'type' non valido";
    }

    echo(json_encode($response));
} else {
    http_response_code(403);
}
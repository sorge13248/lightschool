<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once __DIR__ . "/../file-manager/model.php";

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "get") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;

        require_once __DIR__ . "/../file-manager/model.php";
        echo(json_encode((new \FrancescoSorge\PHP\LightSchool\FileManager())->listFolder("trash", null, $start)));
        exit();
    } else if ($type === "delete") {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->delete((int)$id, "delete_completely");
        $response["file_id"] = $id;

        switch ($response["text"]) {
            case "deleted":
                $response["text"] = "File eliminato con successo";
                break;
            case "invalid_id":
                $response["text"] = "ID non valido";
                break;
        }
    } else if ($type === "restore") {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->restore((int)$id);
        $response["file_id"] = $id;

        switch ($response["text"]) {
            case "restored":
                $response["text"] = "File ripristinato con successo";
                break;
            case "invalid_id":
                $response["text"] = "ID non valido";
                break;
        }
    } else if ($type === "empty") {
        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->empty();

        switch ($response["text"]) {
            case "emptied":
                $response["text"] = "Cestino svuotato";
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
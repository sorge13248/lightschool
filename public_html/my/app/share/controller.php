<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "get-all") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->all((int)$start);
    } else if ($type === "get-shared") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->shared((int)$start);
    } else if ($type === "get-sharing") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->sharing((int)$start);
    } else if ($type === "get-user-shared") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;
        $sender = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->sharedUser($sender, (int)$start);
    } else if ($type === "get-user-sharing") {
        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;
        $receiving = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->sharingUser($receiving, (int)$start);
    } else if ($type === "add") {
        $id = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;
        $receiving = isset($_POST["username"]) ? urlencode($_POST["username"]) : null;

        require_once __DIR__ . "/../file-manager/model.php";

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->add((int)$id, $receiving);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Condivisione iniziata";
                break;
            case "already":
                $response["text"] = "File gi&agrave; condiviso con questo utente";
                break;
            case "ownership":
                $response["text"] = "Il file non &egrave; di tua propriet&agrave;";
                break;
            case "username":
                $response["text"] = "L'utente non esiste";
                break;
            case "same":
                $response["text"] = "Non puoi condividere con te stesso";
                break;
            case "privacy":
                $response["text"] = "Questo utente non accetta condivisioni da parte tua";
                break;
        }
    } else if ($type === "delete") {
        $id = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;
        $file_id = isset($_GET["file_id"]) ? (int)urlencode($_GET["file_id"]) : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->delete((int)$id, (int)$file_id);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Condivisione interrotta";
                break;
        }
    } else if ($type === "file-shared") { // get share list for a given file
        $id = isset($_GET["id"]) ? (int)urlencode($_GET["id"]) : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Share())->fileShared((int)$id);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "invalid_id":
                    $response["text"] = "Invalid ID";
                    break;
            }
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
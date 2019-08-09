<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "history") {
        $id = isset($_GET["id"]) && $_GET["id"] !== "" ? (int)$_GET["id"] : null;

        require_once CONTROLLER . "/notebook.php";

        $response = \FrancescoSorge\PHP\LightSchool\Notebook::history($id);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "invalid_id":
                    $response["text"] = "ID non valido";
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
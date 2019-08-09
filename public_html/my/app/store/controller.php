<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = &$_GET["type"];
    if ($type === "get") {
        $app = isset($_POST["app"]) && trim($_POST["app"]) !== "" ? trim($_POST["app"]) : null;
        $response = \FrancescoSorge\PHP\LightSchool\Store::get($app);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "ok":
                    $response["text"] = "App ottenuta";
                    break;
                case "already":
                    $response["text"] = "Possiedi gi&agrave; quest'app";
                    break;
                case "app":
                    $response["text"] = "App inesistente";
                    break;
            }
        }
    } else if ($type === "theme") {
        $theme = isset($_POST["app"]) && trim($_POST["app"]) !== "" ? trim($_POST["app"]) : null;
        $response = \FrancescoSorge\PHP\LightSchool\Store::theme($theme);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "ok":
                    $response["text"] = "Tema applicato";
                    break;
                case "theme":
                    $response["text"] = "Tema inesistente";
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
    header("location: .");
}
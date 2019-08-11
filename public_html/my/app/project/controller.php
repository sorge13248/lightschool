<?php
require_once __DIR__ . "/../../../etc/core.php";

header('Content-type:application/json;charset=utf-8');

require_once "model.php";

$type = isset($_GET["type"]) ? $_GET["type"] : null;
$response = [];

if ($type === "delete") {
    $response = \FrancescoSorge\PHP\LightSchool\Project::delete();
} else if ($type === "code") {
    $response = \FrancescoSorge\PHP\LightSchool\Project::code();

    if (isset($response["code"])) {
        $response["text"] = "Il codice della LIM &egrave;";
    }
} else if ($type === "files") {
    require_once __DIR__ . "/../file-manager/model.php";
    require_once __DIR__ . "/../share/model.php";
    $response = \FrancescoSorge\PHP\LightSchool\Project::files();

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
}  else if ($type === "your-files") {
    require_once __DIR__ . "/../file-manager/model.php";
    require_once __DIR__ . "/../share/model.php";
    $response = \FrancescoSorge\PHP\LightSchool\Project::yourFiles();
} else if ($type === "project") {
    require_once __DIR__ . "/../file-manager/model.php";

    $id = isset($_GET["file"]) && (int)$_GET["file"] ? (int)$_GET["file"] : null;
    $editable = isset($_POST["editable"]);
    $project = isset($_POST["project"]) && trim($_POST["project"]) !== "" ? trim($_POST["project"]) : null;

    $response = \FrancescoSorge\PHP\LightSchool\Project::project($id, $editable, $project);

    if (isset($response["text"])) {
        switch ($response["text"]) {
            case "ok":
                $response["text"] = "File proiettato";
                break;
            case "id":
                $response["text"] = "ID file non valido";
                break;
            case "rcode":
                $response["text"] = "Codice LIM richiesto";
                break;
            case "code":
                $response["text"] = "Codice LIM scaduto o non valido";
                break;
            case "already":
                $response["text"] = "Stai gi&agrave; proiettando questo file";
                break;
            case "ownership":
                $response["text"] = "Non hai i permessi su questo file";
                break;
        }
    }
}  else if ($type === "stop") {
    require_once __DIR__ . "/../file-manager/model.php";

    $id = isset($_GET["file"]) && (int)$_GET["file"] ? (int)$_GET["file"] : null;
    $project = isset($_GET["project"]) && trim($_GET["project"]) !== "" ? trim($_GET["project"]) : null;

    $response = \FrancescoSorge\PHP\LightSchool\Project::stop($id, $project);

    if (isset($response["text"])) {
        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Proiezione interrotta";
                break;
            case "id":
                $response["text"] = "ID file non valido";
                break;
            case "rcode":
                $response["text"] = "Codice LIM richiesto";
                break;
            case "no_file":
                $response["text"] = "Il file non era in proiezione...";
                break;
            case "ownership":
                $response["text"] = "Non hai i permessi su questo file";
                break;
        }
    }
} else {
    $response = [];
    $response["response"] = "error";
    $response["text"] = "'type' non valido";
}

echo(json_encode($response));
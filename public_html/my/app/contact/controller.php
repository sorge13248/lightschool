<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "create") {
        $name = isset($_POST["name"]) ? $_POST["name"] : null;
        $surname = isset($_POST["surname"]) ? $_POST["surname"] : null;
        $username = isset($_POST["username"]) ? $_POST["username"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Contact())->create($name, $surname, $username);

        switch ($response["text"]) {
            case "contact_created":
                $response["text"] = "Contatto creato con successo";
                break;
            case "already_exists":
                $response["text"] = "Esiste gi&agrave; un contatto con questo nome utente e l'hai salvato nei contatti come \"" . htmlspecialchars($response["additional"]["name"]) . " " . htmlspecialchars($response["additional"]["surname"]) . "\"";
                break;
            case "invalid_username":
                $response["text"] = "Nome utente \"" . htmlspecialchars($username) . "\" inesistente oppure sei tu";
                break;
            case "name_too_short":
                $response["text"] = "Nome non pu&ograve; essere vuoto!";
                break;
            case "surname_too_short":
                $response["text"] = "Cognome non pu&ograve; essere vuoto!";
                break;
            case "username_too_short":
                $response["text"] = "Nome utente non pu&ograve; essere vuoto!";
                break;
            case "name_too_long":
                $response["text"] = "Hai superato la dimensione massima del nome di " . -(strlen($name) - 255) . " caratteri";
                break;
            case "surname_too_long":
                $response["text"] = "Hai superato la dimensione massima del cognome di " . -(strlen($surname) - 255) . " caratteri";
                break;
            case "username_too_long":
                $response["text"] = "Hai superato la dimensione massima del nome utente di " . -(strlen($username) - 255) . " caratteri";
                break;
        }
    } else if ($type === "get-contacts") {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
        $appData = $appApi->getData();

        if (!isset($appData->divide_letters)) {
            $appData->divide_letters = true;
        }

        if (!isset($appData->sort_by)) {
            $appData->sort_by = "name, surname";
        }

        if ($appData->sort_by === "name, surname") {
            $firstLetter = "contact.name";
        } else if ($appData->sort_by === "surname, name") {
            $firstLetter = "contact.surname";
        }

        $start = isset($_GET["start"]) ? urlencode($_GET["start"]) : 0;

        $contactApi = new \FrancescoSorge\PHP\LightSchool\Contact();
        $response = ["divide_letters" => $appData->divide_letters, "first_letter" => $firstLetter, "sort_by" => $appData->sort_by, "contacts" => $contactApi->getList($appData->sort_by, (int)$start)];
    } else if ($type === "delete") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;
        $deleteType = isset($_POST["type"]) ? $_POST["type"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Contact())->delete($id, $deleteType);

        switch ($response["text"]) {
            case "contact_deleted":
                $response["text"] = "Contatto spostato nel cestino";
                break;
            case "contact_deleted_completely":
                $response["text"] = "Contatto eliminato";
                break;
            case "invalid_id":
                $response["text"] = "ID del contatto non valido";
                break;
        }
    } else if ($type === "fav") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;
        $favType = isset($_GET["fav"]) ? $_GET["fav"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Contact())->fav($id, $favType);

        switch ($response["text"]) {
            case "added":
                $response["text"] = "Contatto aggiunto al desktop";
                break;
            case "removed":
                $response["text"] = "Contatto rimosso dal desktop";
                break;
            case "invalid_id":
                $response["text"] = "ID del contatto non valido";
                break;
        }
    } else if ($type === "block") {
        $username = isset($_GET["username"]) && $_GET["username"] ? $_GET["username"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\Contact())->block($username);

        switch ($response["text"]) {
            case "blocked":
                $response["text"] = "Utente bloccato";
                break;
            case "unblocked":
                $response["text"] = "Utente sbloccato";
                break;
            case "invalid_id":
                $response["text"] = "Utente non valido";
                break;
        }
    } else {
        http_response_code(404);
    }

    echo(json_encode($response));
} else {
    http_response_code(403);
}
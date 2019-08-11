<?php
require_once __DIR__ . "/../../../etc/core.php";

$type = isset($_GET["type"]) ? $_GET["type"] : null;
if ($fraUserManagement->isLogged() || ($_GET["type"] === "details")) {
    header('Content-type:application/json;charset=utf-8');

    require_once __DIR__ . "/../file-manager/model.php";
    require_once CONTROLLER . "/notebook.php";

    $response = [];

    if ($type === "create-folder") {
        $name = isset($_POST["name"]) ? $_POST["name"] : null;
        $folder = isset($_GET["folder"]) ? $_GET["folder"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->createFolder($name, $folder);

        switch ($response["text"]) {
            case "folder_created":
                $response["text"] = "Cartella creata con successo";
                break;
            case "already_exists":
                $response["text"] = "Esiste gi&agrave; un file con questo nome";
                break;
            case "too_long":
                $response["text"] = "Hai superato la dimensione massima del nome della cartella di " . -(strlen($name) - 255) . " caratteri";
                break;
            case "too_short":
                $response["text"] = "Il nome non pu&ograve; essere vuoto!";
                break;
            case "not_authorized":
                $response["text"] = "La cartella non &egrave; di tua propriet&agrave!";
                break;
        }
    } else if ($type === "details") {
        require_once __DIR__ . "/../project/model.php";

        if (isset($_GET['id'])) {
            if (isset($_GET["fields"])) {
                $fields = explode(",", $_GET["fields"]);
            } else {
                $fields = null;
            }
            require_once __DIR__ . "/../share/model.php";
            $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->getDetails((int)$_GET["id"], $fields);

            if (isset($response["text"])) {
                switch ($response["text"]) {
                    case "not_authorized":
                        $response["text"] = "Non sei autorizzato ad accedere a questo file oppure non esiste";
                        break;
                }
            }

            if (isset($response["file"]["file_size"])) {
                $response["file"]["file_size"] = \FrancescoSorge\PHP\LightSchool\FileManager::getHumanSize($response["file"]["file_size"]);
            }
        } else {
            $response = ["response" => "error", "text" => "ID file non specificato."];
        }
    } else if ($type === "rename") {
        $name = isset($_POST["name"]) ? $_POST["name"] : null;
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        $folder = isset($_GET["folder"]) && $_GET["folder"] !== "" ? $_GET["folder"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->rename((int)$id, $name, $folder);

        switch ($response["text"]) {
            case "renamed":
                $response["text"] = "File rinominato";
                break;
            case "invalid_id":
                $response["text"] = "ID non valido";
                break;
            case "already_exists":
                $response["text"] = "Esiste gi&agrave; un file con questo nome";
                break;
            case "too_long":
                $response["text"] = "Hai superato la dimensione massima del nome della cartella di " . -(strlen($name) - 255) . " caratteri";
                break;
            case "too_short":
                $response["text"] = "Il nome non pu&ograve; essere vuoto!";
                break;
            case "not_authorized":
                $response["text"] = "Il file non &egrave; di tua propriet&agrave!";
                break;
        }
    } else if ($type === "delete") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;
        $deleteType = isset($_POST["type"]) ? $_POST["type"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->delete($id, $deleteType);

        switch ($response["text"]) {
            case "move_to_trash":
                $response["text"] = "File spostato nel cestino";
                break;
            case "delete_completely":
                $response["text"] = "File eliminato";
                break;
            case "invalid_id":
                $response["text"] = "ID del file non valido";
                break;
        }
    } else if ($type === "fav") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->fav($id);

        switch ($response["text"]) {
            case "added":
                $response["text"] = "File aggiunto al desktop";
                break;
            case "removed":
                $response["text"] = "File rimosso dal desktop";
                break;
            case "invalid_id":
                $response["text"] = "ID del file non valido";
                break;
        }
    } else if ($type === "set-profile-picture") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->setProfilePicture($id);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Foto profilo aggiornata";
                break;
            case "missing_ownership":
                $response["text"] = "Non sei autorizzato ad utilizzare questo file";
                break;
        }
    } else if ($type === "set-wallpaper") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;

        $response = (new \FrancescoSorge\PHP\LightSchool\FileManager())->setWallpaper($id);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Sfondo aggiornato";
                break;
            case "missing_ownership":
                $response["text"] = "Non sei autorizzato ad utilizzare questo file";
                break;
        }
    } else if ($type === "move") {
        $id = isset($_GET["id"]) && (int)$_GET["id"] ? (int)$_GET["id"] : null;
        $folder = isset($_GET["folder"]) && $_GET["folder"] !== "" ? (int)$_GET["folder"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\FileManager::move($id, $folder);

        switch ($response["text"]) {
            case "ok":
                if ($_GET["mode"] === "paste") {
                    $response["text"] = "File incollato";
                } else {
                    $response["text"] = "File spostato";
                }
                break;
            case "invalid_folder":
                $response["text"] = "Cartella non valida";
                break;
            case "invalid_id":
                $response["text"] = "ID del file non valido";
                break;
        }
    } else if ($type === "upload") {
        $folder = isset($_GET["folder"]) && (int)$_GET["folder"] ? (int)$_GET["folder"] : null;
        $response = \FrancescoSorge\PHP\LightSchool\FileManager::upload($_FILES["file"], $folder);

        switch ($response["text"]) {
            case "invalid_folder":
                $response["text"] = "Cartella non valida";
                break;
            case "already":
                $response["text"] = $response["file_name"] . ": esiste gi&agrave; un file con questo nome in questa cartella";
                break;
            case "max_or_ext":
                $response["text"] = $response["file_name"] . ": questo file supera la dimensione massima oppure l'estensione non &egrave; accettata";
                break;
            case "move_uploaded_file":
                $response["text"] = $response["file_name"] . ": impossibile caricare questo file. &Egrave; successo qualcosa, contatta il supporto tecnico.";
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
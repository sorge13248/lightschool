<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = isset($_GET["type"]) ? $_GET["type"] : null;
    $response = [];

    if ($type === "account") {
        $name = isset($_POST["name"]) ? $_POST["name"] : null;
        $surname = isset($_POST["surname"]) ? $_POST["surname"] : null;
        $email = isset($_POST["email"]) ? $_POST["email"] : null;
        $username = isset($_POST["username"]) ? $_POST["username"] : null;

        $response = (new \FrancescoSorge\PHP\Settings())->account($name, $surname, $email, $username);

        switch ($response["text"]) {
            case "saved":
                $response["text"] = "Impostazioni salvate";
                break;
            case "email_changed":
                $response["text"] = "Indirizzo e-mail cambiato correttamente. Ti chiediamo di verificare il tuo nuovo indirizzo e-mail cliccando sul link che ti abbiamo inviato via e-mail al nuovo indirizzo $email";
                break;
            case "invalid_email":
                $response["text"] = "Indirizzo e-mail non valido";
                break;
            case "already_used_email":
                $response["text"] = "Indirizzo e-mail gi&agrave; usato";
                break;
            case "already_used_username":
                $response["text"] = "Nome utente gi&agrave; usato";
                break;
            case "name_too_short":
                $response["text"] = "Nome non pu&ograve; essere vuoto!";
                break;
            case "surname_too_short":
                $response["text"] = "Cognome non pu&ograve; essere vuoto!";
                break;
            case "email_too_short":
                $response["text"] = "Indirizzo e-mail non pu&ograve; essere vuoto!";
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
            case "email_too_long":
                $response["text"] = "Hai superato la dimensione massima dell'indirizzo e-mail di " . -(strlen($email) - 255) . " caratteri";
                break;
            case "username_too_long":
                $response["text"] = "Hai superato la dimensione massima del nome utente di " . -(strlen($username) - 255) . " caratteri";
                break;
        }
    } else if ($type === "customize") {
        $accent = isset($_POST["accent"]) ? $_POST["accent"] : null;
        $profile_picture = isset($_POST["profile_picture"]) ? $_POST["profile_picture"] : null;
        $taskbar = isset($_POST["taskbar"]) ? $_POST["taskbar"] : null;
        $taskbar_size = isset($_POST["taskbar_size"]) ? $_POST["taskbar_size"] : null;
        $pp_id = isset($_POST["pp-id"]) ? $_POST["pp-id"] : "";
        $bkg_id = isset($_POST["bkg-id"]) ? $_POST["bkg-id"] : "";
        $bkg_opacity = isset($_POST["bkg-opacity"]) ? $_POST["bkg-opacity"] : null;
        $bkg_color = isset($_POST["bkg-color"]) ? $_POST["bkg-color"] : null;

        $response = (new \FrancescoSorge\PHP\Settings())->customize($accent, $profile_picture, $taskbar, $taskbar_size, $bkg_id, $bkg_opacity, $bkg_color, $pp_id);

        switch ($response["text"]) {
            case "saved":
                $response["text"] = "Impostazioni salvate";
                break;
        }
    } else if ($type === "privacy") {
        $search_visible = (int)$_POST["search_visible"];
        $show_email = (int)$_POST["show_email"];
        $show_username = (int)$_POST["show_username"];
        $send_messages = (int)$_POST["send_messages"];
        $share_documents = (int)$_POST["share_documents"];
        $ms_office = (int)$_POST["ms_office"];

        $response = \FrancescoSorge\PHP\Settings::privacy($search_visible, $show_email, $show_username, $send_messages, $share_documents, $ms_office);

        switch ($response["text"]) {
            case "saved":
                $response["text"] = "Impostazioni salvate";
                break;
        }
    } else if ($type === "password") {
        $old = isset($_POST["old"]) && $_POST["old"] !== "" ? $_POST["old"] : null;
        $new = isset($_POST["new"]) && $_POST["new"] !== "" ? $_POST["new"] : null;
        $new_2 = isset($_POST["new-2"]) && $_POST["new-2"] !== "" ? $_POST["new-2"] : null;

        $response = \FrancescoSorge\PHP\Settings::password($old, $new, $new_2);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Password aggiornata";
                break;
            case "old":
                $response["text"] = "La password attuale &egrave; non corretta";
                break;
            case "different":
                $response["text"] = "La password nuova &egrave; diversa da quella ripetuta";
                break;
            case "e-old":
                $response["text"] = "La password attuale &egrave; obbligatoria";
                break;
            case "e-new":
                $response["text"] = "La password nuova &egrave; obbligatoria";
                break;
            case "e-new-2":
                $response["text"] = "Ripetere la password nuova &egrave; obbligatorio";
                break;
        }
    } else if ($type === "app-to-taskbar") {
        $app = isset($_GET["app"]) && trim($_GET["app"]) !== "" ? trim($_GET["app"]) : null;
        $response = \FrancescoSorge\PHP\Settings::appToTaskbar($app);

        switch ($response["text"]) {
            case "added":
                $response["text"] = "App aggiunta alla taskbar";
                break;
            case "removed":
                $response["text"] = "App rimossa dalla taskbar";
                break;
            case "invalid_app":
                $response["text"] = "App non valida";
                break;
        }
    } else if ($type === "erase-app-data") {
        $app = isset($_GET["app"]) && trim($_GET["app"]) !== "" ? trim($_GET["app"]) : null;
        $response = \FrancescoSorge\PHP\Settings::eraseAppData($app);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Dati app cancellati";
                break;
            case "no_app":
                $response["text"] = "App non valida";
                break;
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
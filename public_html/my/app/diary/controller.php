<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once __DIR__ . "/model.php";

    $type = &$_GET["type"];
    if ($type === "events") {
        if (isset($_GET['year']) && isset($_GET['month'])) {
            $response = (new \FrancescoSorge\PHP\LightSchool\Diary())->getEvents($_GET["year"], $_GET["month"]);
        } else {
            $response = ["response" => "error", "text" => "Anno o mese non specificati."];
        }
    } else if ($type === "details") {
        if (isset($_GET['id'])) {
            $response = (new \FrancescoSorge\PHP\LightSchool\Diary())->getDetails($_GET["id"]);
        } else {
            $response = ["response" => "error", "text" => "ID evento non specificato."];
        }
    } else if ($type === "create") {
        $diaryType = isset($_POST["type"]) && $_POST["type"] !== "" ? $_POST["type"] : null;
        $color = isset($_POST["color"]) && $_POST["color"] !== "" ? $_POST["color"] : null;
        $subject = isset($_POST["subject"]) && $_POST["subject"] !== "" ? $_POST["subject"] : null;
        $date = isset($_POST["date"]) ? $_POST["date"] : null;
        $reminder = isset($_POST["reminder"]) ? $_POST["reminder"] : null;
        $priority = isset($_POST["priority"]) ? (int)$_POST["priority"] : null;
        $content = isset($_POST["content"]) && $_POST["content"] !== "" ? $_POST["content"] : null;

        require_once __DIR__ . "/model.php";

        $response = \FrancescoSorge\PHP\LightSchool\Diary::create($diaryType, $subject, $date, $color, $reminder, $priority, $content);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Evento creato";
                break;
            case "type":
                $response["text"] = "Il tipo &egrave; obbligatorio";
                break;
            case "name":
                $response["text"] = "Il nome &egrave; obbligatorio";
                break;
            case "date":
                $response["text"] = "La data &egrave; obbligatoria";
                break;
        }
    } else if ($type === "edit") {
        $id = isset($_GET["id"]) && $_GET["id"] !== "" ? (int)$_GET["id"] : null;
        $diaryType = isset($_POST["type"]) && $_POST["type"] !== "" ? $_POST["type"] : null;
        $color = isset($_POST["color-$id"]) && $_POST["color-$id"] !== "" ? $_POST["color-$id"] : null;
        $subject = isset($_POST["subject"]) && $_POST["subject"] !== "" ? $_POST["subject"] : null;
        $date = isset($_POST["date"]) ? $_POST["date"] : null;
        $reminder = isset($_POST["reminder"]) ? $_POST["reminder"] : null;
        $priority = isset($_POST["priority"]) ? (int)$_POST["priority"] : null;
        $content = isset($_POST["content"]) && $_POST["content"] !== "" ? $_POST["content"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\Diary::edit($id, $diaryType, $subject, $date, $color, $reminder, $priority, $content);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Evento modificato";
                break;
            case "id":
                $response["text"] = "ID errato";
                break;
            case "type":
                $response["text"] = "Il tipo &egrave; obbligatorio";
                break;
            case "name":
                $response["text"] = "Il nome &egrave; obbligatorio";
                break;
            case "date":
                $response["text"] = "La data &egrave; obbligatoria";
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
<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once __DIR__ . "/../timetable/model.php";

    $type = &$_GET["type"];
    if ($type === "get") {
        $response = \FrancescoSorge\PHP\LightSchool\Timetable::get();
    } else if ($type === "get-tomorrow") {
        $response = \FrancescoSorge\PHP\LightSchool\Timetable::getTomorrow();
    } else if ($type === "get-subjects") {
        $response = \FrancescoSorge\PHP\LightSchool\Timetable::getSubjects();
    } else if ($type === "create") {
        $year = isset($_POST["year"]) && $_POST["year"] !== "" ? (int)$_POST["year"] : null;
        $day = isset($_POST["day"]) && $_POST["day"] !== "" ? (int)$_POST["day"] : null;
        $slot = isset($_POST["slot"]) && $_POST["slot"] !== "" ? (int)$_POST["slot"] : null;
        $color = isset($_POST["color"]) && $_POST["color"] !== "" ? $_POST["color"] : null;
        $subject = isset($_POST["subject"]) && $_POST["subject"] !== "" ? $_POST["subject"] : null;
        $book = isset($_POST["book"]) && $_POST["book"] !== "" ? $_POST["book"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\Timetable::create($year, $day, $slot, $subject, $color, $book);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Materia aggiunta";
                break;
            case "already":
                $response["text"] = "Lo slot di questo giorno &egrave; gi&agrave; occupato";
                break;
            case "day":
                $response["text"] = "Giorno &egrave; obbligatorio";
                break;
            case "slot":
                $response["text"] = "Slot &egrave; obbligatorio";
                break;
            case "subject":
                $response["text"] = "Materia &egrave; obbligatoria";
                break;
        }
    } else if ($type === "edit") {
        $id = isset($_GET["id"]) && $_GET["id"] !== "" ? (int)$_GET["id"] : null;
        $year = isset($_POST["year"]) && $_POST["year"] !== "" ? (int)$_POST["year"] : null;
        $day = isset($_POST["day"]) && $_POST["day"] !== "" ? (int)$_POST["day"] : null;
        $slot = isset($_POST["slot"]) && $_POST["slot"] !== "" ? (int)$_POST["slot"] : null;
        $color = isset($_POST["color-$id"]) && $_POST["color-$id"] !== "" ? $_POST["color-$id"] : null;
        $subject = isset($_POST["subject"]) && $_POST["subject"] !== "" ? $_POST["subject"] : null;
        $book = isset($_POST["book"]) && $_POST["book"] !== "" ? $_POST["book"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\Timetable::edit($id, $year, $day, $slot, $subject, $color, $book);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Materia modificata";
                break;
            case "id":
                $response["text"] = "ID errato";
                break;
            case "already":
                $response["text"] = "Lo slot di questo giorno &egrave; gi&agrave; occupato";
                break;
            case "day":
                $response["text"] = "Giorno &egrave; obbligatorio";
                break;
            case "slot":
                $response["text"] = "Slot &egrave; obbligatorio";
                break;
            case "subject":
                $response["text"] = "Materia &egrave; obbligatoria";
                break;
        }
    } else if ($type === "remove") {
        $id = isset($_GET["id"]) && $_GET["id"] !== "" ? (int)$_GET["id"] : null;

        $response = \FrancescoSorge\PHP\LightSchool\Timetable::remove($id);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Materia eliminata";
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
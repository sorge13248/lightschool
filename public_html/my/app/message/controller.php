<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    require_once "model.php";

    $type = &$_GET["type"];
    if ($type === "list") {
        $start = isset($_GET["start"]) && trim($_GET["start"]) !== "" ? (int)trim($_GET["start"]) : null;
        $response = \FrancescoSorge\PHP\LightSchool\Message::list($start);
    } else if ($type === "chat") {
        $id = isset($_GET["id"]) && trim($_GET["id"]) !== "" ? (int)trim($_GET["id"]) : null;
        $start = isset($_GET["start"]) && trim($_GET["start"]) !== "" ? (int)trim($_GET["start"]) : null;
        $response = \FrancescoSorge\PHP\LightSchool\Message::chat($id, $start);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "invalid_conversation_id":
                    $response["text"] = "Conversazione inesistente";
                    break;
            }
        }
    } else if ($type === "send") {
        $id = isset($_GET["id"]) && trim($_GET["id"]) !== "" ? (int)trim($_GET["id"]) : null;
        if (isset($_POST["body"])) {
            $body = base64_decode($_POST["body"]);
            if (trim($body) !== "") {
                $body = nl2br(trim($body));
            } else {
                $body = null;
            }
        } else {
            $body = null;
        }
        $response = \FrancescoSorge\PHP\LightSchool\Message::send($id, $body);

        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Messaggio inviato";
                break;
            case "body":
                $response["text"] = "Il messaggio non pu&ograve; essere vuoto";
                break;
            case "id":
                $response["text"] = "ID chat non valido";
                break;
            case "privacy":
                $response["text"] = "Questo utente non accetta messaggi da parte tua";
                break;
        }
    } else if ($type === "new") {
        if (isset($_POST["attach"])) {
            $attach = base64_decode($_POST["attach"]);
        } else {
            $attach = null;
        }
        $username = isset($_POST["username"]) && trim($_POST["username"]) !== "" ? trim($_POST["username"]) : null;
        if (isset($_POST["body"])) {
            $body = base64_decode($_POST["body"]);
            if (trim($body) !== "") {
                $body = nl2br(trim($body));
            } else {
                $body = null;
            }
        } else {
            $body = null;
        }
        $response = \FrancescoSorge\PHP\LightSchool\Message::new($username, $body, $attach);

        if (isset($response["text"])) {
            switch ($response["text"]) {
                case "ok":
                    $response["text"] = "Messaggio inviato";
                    break;
                case "username":
                    $response["text"] = "Il nome utente non pu&ograve; essere vuoto";
                    break;
                case "body":
                    $response["text"] = "Il messaggio non pu&ograve; essere vuoto";
                    break;
                case "invalid_username":
                    $response["text"] = "Il nome utente non &egrave; valido";
                    break;
                case "same":
                    $response["text"] = "Non puoi scrivere a te stesso";
                    break;
                case "privacy":
                    $response["text"] = "Questo utente non accetta messaggi da parte tua";
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
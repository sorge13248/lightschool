<?php
require_once __DIR__ . "/../etc/core.php";

if (!$fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $username = isset($_POST["username"]) ? urlencode($_POST["username"]) : null;

    if (strlen($username) === 0 || strlen($username > 128)) {
        $response["response"] = "error";
        $response["text"] = "Nome utente richiesto";
    } else {
        $response = $fraUserManagement->recover($username);
    }

    echo(json_encode($response));
} else {
    http_response_code(403);
}
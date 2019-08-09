<?php
require_once __DIR__ . "/../etc/core.php";

if (!$fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $username = isset($_POST["username"]) ? urlencode($_POST["username"]) : null;

    if (strlen($username) === 0 || strlen($username > 128)) {
        $response["response"] = "error";
        $response["text"] = "Nome utente richiesto";
    } else {
        $response = $fraUserManagement->deactivateOTP(1, $username);
        switch ($response["text"]) {
            case "ok":
                $response["text"] = "Procedura avviata con successo. Controlla la tua e-mail.";
                break;
            case "user":
                $response["text"] = "Utente non trovato. Il nome utente potrebbe essere errato o potrebbe non aver attiva l'Autenticazione a Due Passaggi.";
                break;
            case "phase":
                $response["text"] = "Fase errata.";
                break;
        }
    }

    echo(json_encode($response));
} else {
    http_response_code(403);
}
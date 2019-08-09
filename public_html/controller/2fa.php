<?php
require_once __DIR__ . "/../etc/core.php";

$type = isset($_GET["type"]) ? urlencode($_GET["type"]) : null;

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $user = $fraUserManagement->getCurrentUserInfo(["twofa"], ["users_expanded"]);
    $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

    if ($type === "activate" && $user->twofa === null && isset($_POST["token"])) {
        $tfa = new RobThree\Auth\TwoFactorAuth('LightSchool');
        $response = [];

        if (isset($_POST["password"]) && $fraUserManagement->checkPassword($_POST["password"])) {
            if ($tfa->verifyCode($_SESSION["secret"], $_POST["token"])) {
                $rsa = new \phpseclib\Crypt\RSA();
                $rsa->setHash("sha512");
                $rsa->loadKey((new \FrancescoSorge\PHP\Keyring())->get("public"));

                $twofa = $database->query("UPDATE users_expanded SET twofa = :twofa WHERE id = :id LIMIT 1", [
                    [
                        "name" => "id",
                        "value" => $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "twofa",
                        "value" => $rsa->encrypt($_SESSION["secret"]),
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                $response["response"] = "success";
                $response["header"] = "Congratulazioni!";
                $response["text"] = "Proceduta portata a termine con successo. Le prossime volte che accederai al tuo account, dovrai fornire anche il codice temporaneo generato dalla tua app, subito dopo aver inserito correttamente nome utente e password.";
            } else {
                $response["response"] = "error";
                $response["text"] = "Token immesso non valido. Ritenta oppure ricomincia la procedura.";
            }
        } else {
            $response["response"] = "error";
            $response["text"] = "Password sbagliata. Ritenta.";
        }

        echo(json_encode($response));
    } else if ($type === "deactivate" && $user->twofa !== null) {
        if (isset($_POST["password"]) && $fraUserManagement->checkPassword($_POST["password"])) {
            $twofa = $database->query("UPDATE users_expanded SET twofa = :twofa WHERE id = :id LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "twofa",
                    "value" => null,
                    "type" => \PDO::PARAM_NULL,
                ],
            ], "fetchAll");

            $response["response"] = "success";
            $response["text"] = "Autenticazione a 2 Fattori disattivata.";
        } else {
            $response["response"] = "error";
            $response["text"] = "Password sbagliata. Ritenta.";
        }

        echo(json_encode($response));
    } else {
        http_response_code(404);
    }
} else {
    http_response_code(403);
}
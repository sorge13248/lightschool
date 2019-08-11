<?php
require_once __DIR__ . "/../etc/core.php";

if (!$fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $username = isset($_POST["username"]) ? urlencode($_POST["username"]) : null;
    $password = isset($_POST["password"]) ? urlencode($_POST["password"]) : null;
    $token = isset($_POST["token"]) ? urlencode($_POST["token"]) : null;

    if (strlen($username) === 0 || strlen($username > 128) || strlen($password) === 0 || strlen($password) > 128) {
        if (isset($_COOKIE["temp_2fa_id"]) && isset($_COOKIE["temp_2fa_token"]) && isset($_GET["2fa"]) && $_GET["2fa"] === "true") {
            if (isset($token)) {
                $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
                $database->setTableDotField(false);

                $query = "SELECT users.id, twofa FROM users, users_expanded WHERE username = :username AND users.id = users_expanded.id LIMIT 1";
                $param = [
                    [
                        "name" => "username",
                        "value" => $_COOKIE["temp_2fa_id"],
                        "type" => \PDO::PARAM_STR,
                    ],
                ];
                $result = $database->query($query, $param, "fetchAll");
                $id = isset($result[0]["id"]) ? $result[0]["id"] : null;
                $twofa = isset($result[0]["twofa"]) ? $result[0]["twofa"] : null;

                if ($id > 0 && $twofa !== null) {
                    $keyring = new \FrancescoSorge\PHP\Keyring($fraUserManagement);
                    $rsa = new \phpseclib\Crypt\RSA();
                    $rsa->setHash("sha512");
                    $rsa->loadKey($keyring->get("private", $id));

                    $password = $rsa->decrypt($_COOKIE["temp_2fa_token"]);

                    $tfa = new RobThree\Auth\TwoFactorAuth('LightSchool');

                    if ($tfa->verifyCode($rsa->decrypt($twofa), $token)) {
                        $login = $fraUserManagement->login($_COOKIE["temp_2fa_id"], $password);

                        \FrancescoSorge\PHP\Cookie::delete("temp_2fa_id");
                        \FrancescoSorge\PHP\Cookie::delete("temp_2fa_token");

                        $response["response"] = "success";
                        $response["text"] = "Codice OTP valido. Accesso in corso...";
                    } else {
                        $response["response"] = "error";
                        $response["text"] = "Codice OTP errato. Ritenta.";
                    }
                } else {
                    $response["response"] = "error";
                    $response["text"] = "Non riesco a verificare il codice OTP.";
                }
            } else {
                $response["response"] = "error";
                $response["text"] = "Inserisci un codice OTP.";
            }
        } else {
            $response["response"] = "error";
            $response["text"] = "Nome utente e password non possono essere vuoti!";
        }
    } else {
        $login = $fraUserManagement->login($username, $password);

        if ($login["response"] === "success") {
            $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
            $database->setTableDotField(false);

            $query = "SELECT twofa FROM users_expanded, users WHERE users.username = :username AND users.id = users_expanded.id LIMIT 1";
            $param = [
                [
                    "name" => "username",
                    "value" => $username,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $result = $database->query($query, $param, "fetchAll");
            $twofa = isset($result[0]["twofa"]) ? $result[0]["twofa"] : null;
            if ($twofa === null) {
                \FrancescoSorge\PHP\Cookie::delete("temp_2fa_id");
                \FrancescoSorge\PHP\Cookie::delete("temp_2fa_token");

                $response["response"] = "success";
                $response["text"] = "Accesso in corso...";
            } else {
                $keyring = new \FrancescoSorge\PHP\Keyring($fraUserManagement);
                $rsa = new \phpseclib\Crypt\RSA();
                $rsa->setHash("sha512");
                $rsa->loadKey($keyring->get("public"));
                $fraUserManagement->logout();

                \FrancescoSorge\PHP\Cookie::set("temp_2fa_id", $username);
                \FrancescoSorge\PHP\Cookie::set("temp_2fa_token", $rsa->encrypt($password));
                $response["response"] = "2fa";
            }
        } else {
            $response["response"] = "error";
            switch ($login["response"]) {
                case "error_UnknownUsernameException":
                case "error_InvalidPasswordException":
                    $response["text"] = "Nome utente o password errati";
                    break;
                case "error_AttemptCancelledException":
                    $response["text"] = "Tentativo annullato";
                    break;
                case "error_AmbiguousUsernameException":
                    $response["text"] = "Nome utente ambiguo";
                    break;
                case "error_EmailNotVerifiedException":
                    $response["text"] = "Non hai verificato il tuo indirizzo e-mail. Clicca sul link inviato alla tua e-mail (controlla la casella spam). Se entro 30 minuti non ricevi la mail, contattaci";
                    break;
                case "error_TooManyRequestsException":
                    $response["text"] = "Troppe richieste. Bloccato";
                    break;
            }
        }
    }

    echo(json_encode($response));
} else {
    http_response_code(403);
}
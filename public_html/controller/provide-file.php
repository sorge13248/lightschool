<?php

use FrancescoSorge\PHP\Cookie;

require_once __DIR__ . "/../etc/core.php";

if ($_GET["id"] !== null) {
    $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

    require_once __DIR__ . "/../my/app/file-manager/model.php";
    require_once __DIR__ . "/../my/app/whiteboard/model.php";

    $owner = null;

    if (\FrancescoSorge\PHP\LightSchool\WhiteBoard::isFileProjecting((int)$_GET["id"], Cookie::get("whiteboard_code"))) {
        $owner = \FrancescoSorge\PHP\LightSchool\FileManager::getOwner((int)$_GET["id"]);
    }

    if ($owner === null) {
        if ($fraUserManagement->isLogged() && (new \FrancescoSorge\PHP\LightSchool\FileManager())->checkOwnership((int)$_GET["id"])) {
            $owner = $currentUser->id;
        } else if ($fraUserManagement->isLogged()) {
            $result = $database->query("SELECT id, profile_picture FROM users_expanded WHERE profile_picture = :id LIMIT 1", [["name" => "id", "value" => (int)$_GET["id"], "type" => \PDO::PARAM_INT]], "fetchAll");
            if (isset($result[0]) && (int)$_GET["id"] === (int)$result[0]["profile_picture"]) { // Allow to view a file if not owner and not shared only if it is set as profile_picture
                $owner = $result[0]["id"];
            } else {
                require_once __DIR__ . "/../my/app/share/model.php";
                $owner = (new \FrancescoSorge\PHP\LightSchool\Share())->authorized((int)$_GET["id"]);
            }
        }
    }

    if ($fraUserManagement->isLogged()) {
        $query = "SELECT name, file_url, file_type, file_size FROM file WHERE id = :file AND user_id = :user_id AND type = 'file' LIMIT 1";
        $params = [
            [
                "name" => "user_id",
                "value" => $owner,
                "type" => \PDO::PARAM_INT,
            ],
            [
                "name" => "file",
                "value" => $_GET["id"],
                "type" => \PDO::PARAM_INT,
            ],
        ];
    } else { // Checks if 'bypass' is activated (bypasses are valid for 10 minutes and are intended to be used only when uploading file to a third party service)
        $query = "SELECT name, file_url, file_type, file_size FROM file WHERE id = :file AND bypass IS NOT NULL AND bypass BETWEEN NOW() - INTERVAL 10 SECOND AND NOW() AND type = 'file' LIMIT 1";
        $params = [
            [
                "name" => "file",
                "value" => $_GET["id"],
                "type" => \PDO::PARAM_INT,
            ],
        ];
    }
    $file = $database->query($query, $params, "fetchAll");

    $file = (isset($file[0]) ? $file[0] : null);

    if ($file !== null) {
        if (file_exists(CONFIG_SITE["uploadDIR"] . "/" . $file["file_url"]) && $file["file_url"] !== null) {
            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Content-Type: " . $file['file_type']);
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:" . $file["file_size"]);
            header("Content-Disposition: attachment; filename=\"" . $file["name"] . "\"");
            readfile(CONFIG_SITE["uploadDIR"] . "/" . $file["file_url"]);
            die();
        } else {
            http_response_code(404);
        }
    } else {
        http_response_code(403);
    }
} else {
    http_response_code(404);
}
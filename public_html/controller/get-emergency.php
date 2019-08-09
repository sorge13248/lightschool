<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

    $temp = [];

    foreach ($currentUser->school as $school) {
        $current = $database->query("SELECT name, emergency, emergency_text FROM school WHERE id = :id LIMIT 1", [["name" => "id", "value" => $school, "type" => \PDO::PARAM_INT]], "fetchAll");
        $current = isset($current[0]) ? $current[0] : null;

        if ($current["emergency"] == 1) {
            array_push($temp, $current);
        }
    }
    echo(json_encode($temp));
} else {
    http_response_code(403);
}
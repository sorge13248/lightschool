<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Cookie;
    use FrancescoSorge\PHP\Database;

    final class WhiteBoard {

        public static function code() {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (Cookie::get("whiteboard_code") !== null) {
                $code = Cookie::get("whiteboard_code");

                $result = $database->query("SELECT id FROM whiteboard WHERE code = :code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW()", [
                    [
                        "name" => "code",
                        "value" => $code,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                if (!isset($result[0])) unset($code);
            }

            if (!isset($code)) {
                do {
                    $ok = false;
                    $code = Basic::generateRandomID(6);
                    try {
                        $database->query("INSERT INTO whiteboard (code) VALUES (:code)", [
                            [
                                "name" => "code",
                                "value" => $code,
                                "type" => \PDO::PARAM_STR,
                            ],
                        ], "fetchAll");
                        $ok = true;
                    } catch (\Exception $e) {
                        if ($e->getCode() != 23000) {
                            return ["response" => "error", "text" => $e->getMessage()];
                        }
                    }
                } while ($ok === false);
            }

            Cookie::set("whiteboard_code", $code);
            return ["response" => "success", "code" => $code];
        }

        public static function files() {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (Cookie::get("whiteboard_code") !== null) {
                $code = Cookie::get("whiteboard_code");

                $result = $database->query("SELECT files, editable FROM whiteboard WHERE code = :code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW()", [
                    [
                        "name" => "code",
                        "value" => $code,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                if (!isset($result[0])) {
                    return ["response" => "error", "file" => "code"];
                }

                $files = [];
                if ($result[0]["files"] !== null) {
                    $editable = $result[0]["editable"] === null ? [] : explode(",", $result[0]["editable"]);
                    foreach (explode(",", $result[0]["files"]) as &$file) {
                        $current = [];
                        $current["id"] = (int)$file;
                        $current["editable"] = in_array($current["id"], $editable);
                        $details = (new FileManager())->getDetails($current["id"], ["user_id", "name", "type", "icon", "file_type", "file_url", "diary_type", "diary_date"], FileManager::getOwner($current["id"]), true)["file"];
                        $current["name"] = $details["name"];
                        $current["type"] = $details["type"];
                        $current["icon"] = $details["icon"];
                        $current["diary_type"] = $details["diary_type"];
                        $current["diary_date"] = $details["diary_date"];
                        $current["user"] = User::get(["name", "surname"], $details["user_id"]);

                        array_push($files, $current);
                    }
                }

                return $files;
            } else {
                return ["response" => "error", "file" => "cookie"];
            }
        }

        public static function isFileProjecting($id, $code) {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "SELECT files FROM whiteboard WHERE code = :code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW() AND files LIKE :files";
            $param = [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "files",
                    "value" => "%$id%",
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $result = $database->query($query, $param, "fetchAll");

            if (!isset($result[0])) {
                return false;
            }

            if ($result[0]["files"] !== null) {
                return in_array($id, explode(",", $result[0]["files"]));
            }
        }

    }
}
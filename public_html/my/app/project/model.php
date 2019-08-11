<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Cookie;
    use FrancescoSorge\PHP\Database;

    final class Project {

        public static function delete() { // Deletes current Project code
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (Cookie::get("project_code") !== null) {
                $code = Cookie::get("project_code");

                $database->query("DELETE FROM project_files WHERE project = :code", [
                    [
                        "name" => "code",
                        "value" => $code,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                $result = $database->query("DELETE FROM project WHERE code = :code LIMIT 1", [
                    [
                        "name" => "code",
                        "value" => $code,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                return $result === 1 ? true : false;
            }

            return false;
        }

        public static function code() {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (Cookie::get("project_code") !== null) {
                $code = Cookie::get("project_code");

                $result = $database->query("SELECT id FROM project WHERE code = :code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW() LIMIT 1", [
                    [
                        "name" => "code",
                        "value" => $code,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                if (!isset($result[0])) {
                    self::delete();
                    unset($code);
                }
            }

            if (!isset($code)) {
                do {
                    $ok = false;
                    $code = Basic::generateRandomID(6);
                    try {
                        $database->query("INSERT INTO project (code) VALUES (:code)", [
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

            Cookie::set("project_code", $code);
            return ["response" => "success", "code" => $code];
        }

        public static function files() {
            if (Cookie::get("project_code") !== null) {
                $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

                $code = Cookie::get("project_code");

                $result = $database->query("SELECT user, file, editable FROM project, project_files WHERE project = :code AND project = code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW()", [
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
                foreach ($result as &$file) {
                    $current = [];
                    $current["id"] = (int)$file["file"];
                    $current["editable"] = $file["editable"];
                    $details = (new FileManager())->getDetails($current["id"], ["name", "type", "icon", "file_type", "file_url", "diary_type", "diary_date"], $file["user"])["file"];
                    $current["name"] = $details["name"];
                    $current["type"] = $details["type"];
                    $current["icon"] = $details["icon"];
                    $current["diary_type"] = $details["diary_type"];
                    $current["diary_date"] = $details["diary_date"];
                    $current["user"] = User::get(["name", "surname"], $file["user"]);

                    array_push($files, $current);
                }

                return $files;
            } else {
                return ["response" => "error", "text" => "cookie"];
            }
        }

        public static function yourFiles($userid = null) {
            global $fraUserManagement;

            if (!$fraUserManagement->isLogged()) {
                return ["response" => "error", "text" => "not_logged_in"];
            }
            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $result = $database->query("SELECT user, file, editable, project FROM project, project_files WHERE user = :user AND project = code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW()", [
                [
                    "name" => "user",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (!isset($result[0])) {
                return [];
            }

            $files = [];
            foreach ($result as &$file) {
                $current = [];
                $current["id"] = (int)$file["file"];
                $current["project"] = $file["project"];
                $current["editable"] = $file["editable"];
                $details = (new FileManager())->getDetails($current["id"], ["name", "type", "icon", "file_type", "file_url", "diary_type", "diary_date"], $file["user"])["file"];
                $current["name"] = $details["name"];
                $current["type"] = $details["type"];
                $current["icon"] = $details["icon"];
                $current["diary_type"] = $details["diary_type"];
                $current["diary_date"] = $details["diary_date"];
                $current["user"] = User::get(["name", "surname"], $file["user"]);

                array_push($files, $current);
            }

            return $files;
        }

        public static function isFileProjecting($id, $code) {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "SELECT id FROM project_files WHERE project = :code AND file = :id LIMIT 1";
            $param = [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $result = $database->query($query, $param, "fetchAll");

            return isset($result[0]);
        }

        public static function isFileEditable($id, $code) {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "SELECT id FROM project_files WHERE project = :code AND file = :id AND editable = true LIMIT 1";
            $param = [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $result = $database->query($query, $param, "fetchAll");

            return isset($result[0]);
        }

        public static function project($id, $editable, $code) {
            if (!is_int($id)) {
                return ["response" => "error", "text" => "id"];
            }
            if ($code === null) {
                return ["response" => "error", "text" => "rcode"];
            }

            if (!FileManager::checkOwnership($id)) {
                return ["response" => "error", "text" => "ownership"];
            }
            if (self::isFileProjecting($id, $code)) {
                return ["response" => "error", "text" => "already"];
            }

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            global $fraUserManagement;
            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $result = $database->query("SELECT id FROM project WHERE code = :code AND timestamp BETWEEN NOW() - INTERVAL 7 DAY AND NOW() LIMIT 1", [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ]
            ], "fetchAll");

            if (!isset($result[0])) {
                return ["response" => "error", "text" => "code"];
            }

            $database->query("INSERT INTO project_files (project, file, user, editable) VALUES (:code, :id, :user, :editable)", [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "editable",
                    "value" => $editable,
                    "type" => \PDO::PARAM_BOOL,
                ]
            ], "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function stop($id, $code) {
            if (!is_int($id)) {
                return ["response" => "error", "text" => "id"];
            }
            if ($code === null) {
                return ["response" => "error", "text" => "rcode"];
            }

            if (!FileManager::checkOwnership($id)) {
                return ["response" => "error", "text" => "ownership"];
            }
            if (!self::isFileProjecting($id, $code)) {
                return ["response" => "error", "text" => "no_file"];
            }

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            global $fraUserManagement;
            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $result = $database->query("SELECT id FROM project WHERE code = :code LIMIT 1", [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ]
            ], "fetchAll");

            if (!isset($result[0])) {
                return ["response" => "error", "text" => "code"];
            }

            $database->query("DELETE FROM project_files WHERE project = :code AND file = :id AND user = :user", [
                [
                    "name" => "code",
                    "value" => $code,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ]
            ], "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

    }
}
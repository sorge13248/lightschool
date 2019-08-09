<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Crypto;
    use FrancescoSorge\PHP\Database;

    final class Notebook {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public static function history ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            global $fraUserManagement;


            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $history = $database->query("SELECT id, name, icon, create_date FROM file WHERE history = :file AND user_id = :user_id AND type = 'notebook' AND deleted IS NULL ORDER BY create_date DESC", [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            foreach ($history as &$item) {
                $item["icon"] = FileManager::getIcon($item["id"], $item["name"], $item["icon"], "notebook");
                $item["create_date"] = Basic::timestampToHuman($item["create_date"]);
            }

            return $history;
        }

        public function getDetails ($id, $userid = null, $getHtml = true) {
            $notebook = $this->database->query("SELECT name, type, create_date, last_edit, last_view, folder, n_ver FROM file WHERE id = :file AND user_id = :user_id AND type = 'notebook' AND deleted IS NULL LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($notebook[0])) {
                $notebook = $notebook[0];

                if ($getHtml) {
                    $notebook["html"] = self::decrypt($id, $userid)["notebook"];
                    if ((int)$notebook["n_ver"] == 2) $notebook["html"] = base64_decode($notebook["html"]);
                }

                $data = ["response" => "success"];
                $data["notebook"] = $notebook;

                return $data;
            } else {
                return ["response" => "error"];
            }
        }

        public static function decrypt ($id, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $notebook = $database->query("SELECT cypher, html FROM file WHERE id = :file AND user_id = :user_id AND (type = 'notebook' OR type = 'diary') AND deleted IS NULL LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($notebook[0])) {
                $notebook = $notebook[0];

                try {
                    $notebook = Crypto::decrypt($notebook["html"], $notebook["cypher"], $userid);
                } catch (\Exception $e) {
                    $notebook = null;
                }

                $data = ["response" => "success"];
                $data["notebook"] = $notebook;

                return $data;
            } else {
                return ["response" => "error"];
            }
        }
    }
}
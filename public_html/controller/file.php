<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Database;

    final class File {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;

            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public function getDetails ($id, $userid = null) {
            $file = $this->database->query("SELECT * FROM file WHERE id = :file AND user_id = :user_id AND type = 'file' AND deleted is NULL LIMIT 1", [
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

            if (isset($file[0])) {
                $file = $file[0];

                $data = ["response" => "success"];
                $data["file"] = $file;

                return $data;
            } else {
                return ["response" => "error"];
            }
        }
    }
}
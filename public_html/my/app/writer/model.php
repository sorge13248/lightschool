<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Crypto;
    use FrancescoSorge\PHP\Database;

    final class Writer {

        public static function create ($name, $content, $folder = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($name === null) {
                return ["response" => "error", "text" => "name"];
            }
            if (!is_int($folder)) {
                return ["response" => "error", "text" => "invalid_id"];
            }

            if ($folder !== 0 && !FileManager::checkOwnership($folder, $userid)) {
                return ["response" => "error", "text" => "ownership"];
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "SELECT id FROM file WHERE user_id = :user_id AND name = :file_name AND folder " . ($folder === 0 ? "IS" : "=") . " :folder AND deleted IS NULL AND trash = 0 LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file_name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "folder",
                    "value" => $folder === 0 ? null : $folder,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $database->query($query, $param, "fetchAll");

            if (isset($items[0])) {
                return ["response" => "error", "text" => "already"];
            }

            $content = Crypto::encrypt($content, null, $userid);

            $query = "INSERT INTO file(user_id, type, name, n_ver, cypher, html, folder) VALUES (:user_id, 'notebook', :name, 2, :cypher, :content, :folder)";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "cypher",
                    "value" => $content["key"],
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "content",
                    "value" => $content["data"],
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "folder",
                    "value" => $folder === 0 ? null : $folder,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $id = $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok", "id" => $id];
        }

        public static function edit ($id, $title, $content, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (!is_int($id)) {
                return ["response" => "error", "text" => "invalid_id"];
            }

            if ($userid === null && \FrancescoSorge\PHP\LightSchool\Project::isFileProjecting((int)$_GET["id"], \FrancescoSorge\PHP\Cookie::get("project_code")) && \FrancescoSorge\PHP\LightSchool\Project::isFileEditable((int)$_GET["id"], \FrancescoSorge\PHP\Cookie::get("project_code"))) {
                $userid = \FrancescoSorge\PHP\LightSchool\FileManager::getOwner((int)$_GET["id"]);
            }

            if ($userid === null && !$fraUserManagement->isLogged()) {
                $userid = null;
            } else {
                $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;
            }

            if ($userid === null) {
                return ["response" => "error", "text" => "ownership"];
            }

            $content = Crypto::encrypt($content, null, $userid);

            $query = "UPDATE file SET name = :name, cypher = :cypher, html = :html, last_edit = NOW() WHERE id = :id AND user_id = :user_id";
            $param = [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "cypher",
                    "value" => $content["key"],
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "html",
                    "value" => $content["data"],
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "name",
                    "value" => $title,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }
    }
}
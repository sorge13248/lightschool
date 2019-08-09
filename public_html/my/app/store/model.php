<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Database;

    final class Store {

        public static function get ($app, $userid = null) {
            if ($app === null) {
                return ["response" => "error", "text" => "app"];
            }

            try {
                require_once(APP_API);
                $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi($app);
                return ["response" => "error", "text" => "already"];
            } catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "INSERT INTO app_purchase(user, app) VALUES (:user, :app)";
            $param = [
                [
                    "name" => "user",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "app",
                    "value" => $app,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function theme ($theme, $userid = null) {
            if ($theme === null) {
                return ["response" => "error", "text" => "app"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "UPDATE users_expanded SET theme = :theme WHERE id = :user";
            $param = [
                [
                    "name" => "user",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "theme",
                    "value" => $theme === "t-default" ? null : str_replace("t-", "", $theme),
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function returnPurchaseID ($app, $userid = null) {
            if ($app === null) {
                return ["response" => "error", "text" => "app"];
            }

            try {
                require_once(APP_API);
                $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi($app);
            } catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
                return ["response" => "error", "text" => "no_app"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $query = "SELECT id FROM app_purchase WHERE user = :user AND app = :app LIMIT 1";
            $param = [
                [
                    "name" => "user",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "app",
                    "value" => $app,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $result = $database->query($query, $param, "fetchAll");

            return ["response" => "success", "id" => $result[0]["id"]];
        }
    }
}
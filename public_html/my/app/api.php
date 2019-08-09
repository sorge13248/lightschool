<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Database;

    final class AppApi {
        private $uniqueID, $database, $userManagement;

        public function __construct ($uniqueID = null) {

            global $fraUserManagement;

            $this->uniqueID = $uniqueID !== null ? $uniqueID : explode("/", strstr(Basic::getURL(), 'app/'))[1];
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
            $this->userManagement = $fraUserManagement;

            $data = $this->database->query("SELECT COUNT(*) AS num FROM app_catalog, app_purchase WHERE app_catalog.unique_name = :app_name AND app_catalog.unique_name = app_purchase.app AND user = :user_id LIMIT 1", [
                [
                    "name" => "app_name",
                    "value" => $this->uniqueID,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "user_id",
                    "value" => $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if ($data[0]["num"] == 0) throw new \FrancescoSorge\PHP\LightSchool\AppNotPurchased(); // App has not been purchased
        }

        public function getData () {
            $data = $this->database->query("SELECT data FROM app_catalog, app_purchase WHERE app_catalog.unique_name = :app_name AND app_catalog.unique_name = app_purchase.app AND user = :user_id LIMIT 1", [
                [
                    "name" => "app_name",
                    "value" => $this->uniqueID,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "user_id",
                    "value" => $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll")[0]["data"];

            return $data === null ? (object)[] : json_decode(base64_decode($data));
        }

        public function eraseData () {
            $this->setData(null);
        }

        public function setData ($data) {
            $param = [
                [
                    "name" => "app_name",
                    "value" => $this->uniqueID,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "user_id",
                    "value" => $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            if ($data === null) {
                array_push($param, ["name" => "data", "value" => null, "type" => \PDO::PARAM_NULL]);
            } else {
                array_push($param, ["name" => "data", "value" => base64_encode(json_encode($data)), "type" => \PDO::PARAM_STR]);
            }

            $this->database->query("UPDATE app_catalog, app_purchase SET app_purchase.data = :data WHERE app_catalog.unique_name = :app_name AND app_catalog.unique_name = app_purchase.app AND user = :user_id", $param, "fetchAll");

            return true;
        }
    }
}
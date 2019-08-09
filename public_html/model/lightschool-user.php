<?php

namespace FrancescoSorge\PHP\LightSchool {

    final class User {

        public static function get ($fields, $userid = null, $username = null) {
            global $fraUserManagement;

            $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($username === null) {
                $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;
            }

            foreach ($fields as &$field) {
                $field = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], "", (string)$field);
            }

            $fieldsSql = implode(", ", $fields);

            $query = "SELECT {$fieldsSql} FROM all_users WHERE " . ($username === null ? "id" : "username") . " = :user LIMIT 1";
            $param = [
                [
                    "name" => "user",
                    "value" => $username === null ? $userid : $username,
                    "type" => $username === null ? \PDO::PARAM_INT : \PDO::PARAM_STR,
                ],
            ];
            $user = $database->query($query, $param, "fetchAll");

            if (isset($user[0])) {
                $user = $user[0];

                if (in_array("profile_picture", $fields)) {
                    $user["profile_picture"] = $user["profile_picture"] === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : CONFIG_SITE["baseURL"] . "/controller/provide-file/" . $user["profile_picture"];
                }
                if (in_array("third_parties", $fields)) {
                    $user["third_parties"] = $user["third_parties"] === null ? null : json_decode($user["third_parties"], true);
                }
                if (in_array("blocked", $fields)) {
                    $user["blocked"] = $user["blocked"] === null ? null : json_encode(explode(",", $user["blocked"]));
                }

                return $user;
            }

            return null;
        }
    }
}
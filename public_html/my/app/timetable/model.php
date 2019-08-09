<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Database;

    final class Timetable {

        public static function get ($userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            return $database->query("SELECT id, day, slot, subject, book, fore FROM timetable WHERE user = :user_id AND deleted IS NULL ORDER BY day, slot", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");
        }

        public static function getTomorrow ($userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            return $database->query("SELECT id, day, slot, subject, book, fore FROM timetable WHERE user = :user_id AND day = :day AND deleted IS NULL ORDER BY day, slot", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "day",
                    "value" => (new \DateTime('tomorrow'))->format("N"),
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");
        }

        public static function getSubjects ($year = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            return $database->query("SELECT subject, fore, book FROM timetable WHERE user = :user_id AND deleted IS NULL GROUP BY subject, fore, book ORDER BY subject", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");
        }

        public static function create ($year, $day, $slot, $subject, $color = null, $book = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($day === null || !is_int($day)) {
                return ["response" => "error", "text" => "day"];
            }
            if ($slot === null || !is_int($slot)) {
                return ["response" => "error", "text" => "slot"];
            }
            if ($subject === null) {
                return ["response" => "error", "text" => "subject"];
            }
            if ($day > 7) {
                $day = 1;
            }
            if ($slot > 255) {
                $day = 255;
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $result = $database->query("SELECT id FROM timetable WHERE user = :user_id AND year " . ($year === null ? "IS" : "=") . " :year AND day = :day AND slot = :slot AND deleted IS NULL LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "year",
                    "value" => $year,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "day",
                    "value" => $day,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "slot",
                    "value" => $slot,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($result[0])) {
                return ["response" => "error", "text" => "already"];
            }

            $query = "INSERT INTO timetable(user, year, day, slot, subject, book, fore) VALUES (:user_id, :year, :day, :slot, :subject, :book, :fore)";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "year",
                    "value" => $year,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "day",
                    "value" => $day,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "slot",
                    "value" => $slot,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "subject",
                    "value" => $subject,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "book",
                    "value" => $book,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "fore",
                    "value" => str_replace("#", "", $color),
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function edit ($id, $year, $day, $slot, $subject, $color = null, $book = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($id === null || !is_int($id)) {
                return ["response" => "error", "text" => "id"];
            }
            if ($day === null || !is_int($day)) {
                return ["response" => "error", "text" => "day"];
            }
            if ($slot === null || !is_int($slot)) {
                return ["response" => "error", "text" => "slot"];
            }
            if ($subject === null) {
                return ["response" => "error", "text" => "subject"];
            }
            if ($day > 7) {
                $day = 1;
            }
            if ($slot > 255) {
                $day = 255;
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $result = $database->query("SELECT id FROM timetable WHERE user = :user_id AND year " . ($year === null ? "IS" : "=") . " :year AND day = :day AND slot = :slot AND id != :id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "year",
                    "value" => $year,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "day",
                    "value" => $day,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "slot",
                    "value" => $slot,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($result[0])) {
                return ["response" => "error", "text" => "already"];
            }

            $query = "UPDATE timetable SET year = :year, day = :day, slot = :slot, subject = :subject, book = :book, fore = :fore WHERE user = :user_id AND id = :id";
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
                    "name" => "year",
                    "value" => $year,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "day",
                    "value" => $day,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "slot",
                    "value" => $slot,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "subject",
                    "value" => $subject,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "book",
                    "value" => $book,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "fore",
                    "value" => str_replace("#", "", $color),
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function remove ($id, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($id === null || !is_int($id)) {
                return ["response" => "error", "text" => "id"];
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "UPDATE timetable SET deleted = NOW() WHERE user = :user_id AND id = :id";
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
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public function getDetails ($id, $userid = null) {

        }
    }
}
<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Crypto;
    use FrancescoSorge\PHP\Database;

    final class Diary {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public static function create ($type, $subject, $date, $color = null, $reminder = null, $priority = null, $content = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($type === null) {
                return ["response" => "error", "text" => "type"];
            }
            if ($subject === null) {
                return ["response" => "error", "text" => "name"];
            }
            if ($date === null) {
                return ["response" => "error", "text" => "date"];
            }
            if ($priority === null || !is_int($priority)) {
                $priority = 0;
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($content !== null) {
                $content = Crypto::encrypt($content, null, $userid);
            }

            $query = "INSERT INTO file(user_id, type, name, diary_type, diary_date, diary_reminder, diary_priority, diary_color, cypher, html) VALUES (:user_id, 'diary', :name, :type, :date, :reminder, :priority, :color, :cypher, :content)";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $subject,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "type",
                    "value" => $type,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "date",
                    "value" => $date,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "reminder",
                    "value" => $reminder === "0000-00-00" ? null : $reminder,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "priority",
                    "value" => $priority,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "color",
                    "value" => str_replace("#", "", $color),
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "cypher",
                    "value" => $content !== null ? $content["key"] : null,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "content",
                    "value" => $content !== null ? $content["data"] : null,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function edit ($id, $type, $subject, $date, $color = null, $reminder = null, $priority = null, $content = null, $userid = null) {
            if ($id === null || !is_int($id)) {
                return ["response" => "error", "text" => "id"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($type === null) {
                return ["response" => "error", "text" => "type"];
            }
            if ($subject === null) {
                return ["response" => "error", "text" => "name"];
            }
            if ($date === null) {
                return ["response" => "error", "text" => "date"];
            }
            if ($priority === null || !is_int($priority)) {
                $priority = 0;
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($content !== null) {
                $content = Crypto::encrypt($content, null, $userid);
            }

            $query = "UPDATE file SET name = :name, diary_type = :type, diary_date = :date, diary_reminder = :reminder, diary_priority = :priority, diary_color = :color, cypher = :cypher, html = :content WHERE id = :id AND user_id = :user_id";
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
                    "name" => "name",
                    "value" => $subject,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "type",
                    "value" => $type,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "date",
                    "value" => $date,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "reminder",
                    "value" => $reminder === "0000-00-00" ? null : $reminder,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "priority",
                    "value" => $priority,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "color",
                    "value" => str_replace("#", "", $color),
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "cypher",
                    "value" => $content !== null ? $content["key"] : null,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "content",
                    "value" => $content !== null ? $content["data"] : null,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public function getIncoming ($userid = null, $limit = null) {
            if ($limit !== null && is_int($limit) && $limit > 1) {
                $limit = "LIMIT {$limit}";
            } else {
                $limit = "";
            }

            $diary = $this->database->query("SELECT diary_type, diary_date, diary_priority, name, id FROM file WHERE user_id = :user_id AND type = 'diary' AND ((diary_priority >= 1 AND diary_date > NOW()) OR (diary_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY))) AND trash = 0 AND deleted IS NULL ORDER BY diary_date, diary_priority {$limit}", [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            $i = 0;
            foreach ($diary as $item) {
                $i++;
            }

            if ($i === 0) {
                return [];
            } else {
                return ["count" => $i, "events" => $diary];
            }
        }

        public function getEvents ($year, $month = null, $userid = null) {
            $query = "SELECT id, name, diary_date, diary_type, diary_reminder, diary_priority, diary_color, fav FROM file WHERE type = 'diary' AND user_id = :user_id AND diary_date BETWEEN :date AND DATE_ADD(:date, Interval +1 MONTH) AND trash = 0 AND deleted IS NULL";
            $param = [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "date",
                    "value" => "{$year}-{$month}-01",
                    "type" => \PDO::PARAM_STR,
                ],
            ];

            $events = $this->database->query($query, $param, "fetchAll");

            $data = ["response" => "success"];
            $data["events"] = $events;

            return $data;
        }

        public function getDetails ($id, $userid = null) {
            $event = $this->database->query("SELECT * FROM file WHERE id = :file AND user_id = :user_id AND type = 'diary' AND deleted IS NULL LIMIT 1", [
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

            if (isset($event[0])) {
                $event = $event[0];

                try {
                    $event["html"] = Crypto::decrypt($event["html"], $event["cypher"], $userid);
                } catch (\Exception $e) {
                    $event["html"] = null;
                }

                $data = ["response" => "success"];
                $data["event"] = $event;

                return $data;
            } else {
                return ["response" => "error"];
            }
        }
    }
}
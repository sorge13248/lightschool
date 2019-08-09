<?php

namespace FrancescoSorge\PHP {

    use FrancescoSorge\PHP\LightSchool\User;

    final class Settings {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public static function privacy ($search_visible, $show_email, $show_username, $send_messages, $share_documents, $ms_office, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $search_visible = (bool)$search_visible;
            $show_email = (bool)$show_email;
            $show_username = (bool)$show_username;

            $query = "UPDATE users_expanded SET privacy_search_visible = :search_visible, privacy_show_email = :show_email, privacy_show_username = :show_username, privacy_send_messages = :send_messages, privacy_share_documents = :share_documents, privacy_ms_office = :ms_office WHERE users_expanded.id = :user_id";
            $param = [
                [
                    "name" => "search_visible",
                    "value" => $search_visible,
                    "type" => \PDO::PARAM_BOOL,
                ],
                [
                    "name" => "show_email",
                    "value" => $show_email,
                    "type" => \PDO::PARAM_BOOL,
                ],
                [
                    "name" => "show_username",
                    "value" => $show_username,
                    "type" => \PDO::PARAM_BOOL,
                ],
                [
                    "name" => "send_messages",
                    "value" => $send_messages,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "share_documents",
                    "value" => $share_documents,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "ms_office",
                    "value" => $ms_office,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "saved"];
        }

        public static function password ($old, $new, $new_2, $userid = null) {
            if ($old === null) {
                return ["response" => "error", "text" => "e-old"];
            }
            if ($new === null) {
                return ["response" => "error", "text" => "e-new"];
            }
            if ($new_2 === null) {
                return ["response" => "error", "text" => "e-new-2"];
            }
            if ($new !== $new_2) {
                return ["response" => "error", "text" => "different"];
            }

            global $fraUserManagement;

            $response = $fraUserManagement->changePassword($old, $new);
            if ($response["response"] === "success") {
                return ["response" => "success", "text" => "ok"];
            } else {
                return $response;
            }
        }

        public static function appToTaskbar ($app, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($app === null) {
                return ["response" => "error", "text" => "invalid_app"];
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $taskbar = User::get(["taskbar"], $userid)["taskbar"];

            if ($taskbar === null || $taskbar === "") {
                $taskbar = [];
            } else {
                $taskbar = explode(",", $taskbar);
            }

            require_once __DIR__ . "/../store/model.php";
            $app = \FrancescoSorge\PHP\LightSchool\Store::returnPurchaseID($app, $userid);
            if (isset($app["id"])) {
                $app = $app["id"];
            } else {
                return ["response" => "error", "text" => "invalid_app"];
            }

            if (($key = array_search($app, $taskbar)) !== false) {
                unset($taskbar[$key]);
                $operation = "removed";
            } else {
                array_push($taskbar, $app);
                $operation = "added";
            }

            $query = "UPDATE users_expanded SET taskbar = :taskbar WHERE id = :user_id";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "taskbar",
                    "value" => implode(",", $taskbar),
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => $operation];
        }

        public static function eraseAppData ($app, $userid = null) {
            if ($app === null) {
                return ["response" => "error", "text" => "invalid_app"];
            }

            try {
                require_once(APP_API);
                $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi($app);
                $appApi->eraseData();
                return ["response" => "success", "text" => "ok"];
            } catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
                return ["response" => "error", "text" => "no_app"];
            }
        }

        public function account ($name, $surname, $email, $username, $userid = null) {
            $name = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $name); // replaces invalid chars with whitespaces
            $surname = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $surname); // replaces invalid chars with whitespaces
            $email = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", strtolower($email)); // replaces invalid chars with whitespaces
            $username = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", " ", "'"], "", strtolower($username)); // replaces invalid chars with nothing

            if (strlen($name) === 0) { // name too short
                return ["response" => "error", "text" => "name_too_short"];
            }
            if (strlen($surname) === 0) { // surname too short
                return ["response" => "error", "text" => "surname_too_short"];
            }
            if (strlen($email) === 0) { // email too short
                return ["response" => "error", "text" => "email_too_short"];
            }
            if (strlen($username) === 0) { // username too short
                return ["response" => "error", "text" => "username_too_short"];
            }

            if (strlen($name) > 255) { // name too long
                return ["response" => "error", "text" => "name_too_long"];
            }
            if (strlen($surname) > 255) { // surname too long
                return ["response" => "error", "text" => "surname_too_long"];
            }
            if (strlen($email) > 255) { // email too long
                return ["response" => "error", "text" => "email_too_long"];
            }
            if (strlen($username) > 255) { // username too long
                return ["response" => "error", "text" => "username_too_long"];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["response" => "error", "text" => "invalid_email"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            // check for already existing username or email
            $query = "SELECT email, username FROM users WHERE (email = :email OR username = :username) AND id != :user_id LIMIT 1";
            $param = [
                [
                    "name" => "email",
                    "value" => $email,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "username",
                    "value" => $username,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $items = $this->database->query($query, $param, "fetchAll");

            // if found at least one entry
            if (isset($items[0])) {
                if ($items[0]["email"] === $email) { // if email is already used
                    return ["response" => "error", "text" => "already_used_email"];
                }
                if ($items[0]["username"] === $username) { // if username is already used
                    return ["response" => "error", "text" => "already_used_username"];
                }
            }

            $query = "UPDATE users, users_expanded SET name = :name, surname = :surname, username = :username WHERE users.id = :user_id AND users.id = users_expanded.id";
            $param = [
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "surname",
                    "value" => $surname,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "username",
                    "value" => $username,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $this->database->query($query, $param, "fetchAll");

            // get current email
            $query = "SELECT email FROM users WHERE id = :user_id LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $this->database->query($query, $param, "fetchAll")[0];

            if ($items["email"] !== $email) { // e-mail changed
                return $this->userManagement->changeEmail($email);
            }

            return ["response" => "success", "text" => "saved"];
        }

        public function customize ($accent, $profile_picture, $taskbar, $taskbar_size, $bkg_id, $bkg_opacity, $bkg_color, $pp_id, $userid = null) {
            $accent = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", " ", "'"], " ", $accent); // replaces invalid chars with whitespaces
            $accent = str_replace("#", "", $accent); // remove # if any

            $taskbar = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", " ", "'"], " ", $taskbar); // replaces invalid chars with whitespaces
            $taskbar_size = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", " ", "'"], "", $taskbar_size); // replaces invalid chars with nothing

            if (strlen($accent) === 0 || strlen($accent) > 6) { // invalid accent
                $accent = null;
            }

            if (strlen($taskbar) === 0) { // taskbar empty
                $taskbar = null;
            }
            if (strlen($taskbar_size) === 0 || $taskbar_size > 2 || $taskbar_size < 0) { // taskbar_size not provided or not valid
                $taskbar_size = null;
            }

            $bkg_opacity = ((int)$bkg_opacity) / 100;
            $bkg_color = Basic::hexToRgb($bkg_color);

            if ($bkg_id === "") {
                $wallpaper = null;
            } else {
                $wallpaper = ["id" => (int)$bkg_id, "opacity" => $bkg_opacity, "color" => implode(", ", $bkg_color)];
                $wallpaper = json_encode($wallpaper);
            }
            if ($pp_id === "") {
                $pp_id = null;
            }

            $taskbar = explode(",", $taskbar);
            $newtaskbar = [];
            foreach ($taskbar as $app) {
                if (is_numeric($app)) {
                    array_push($newtaskbar, $app);
                }
            }
            $taskbar = implode(",", $newtaskbar);
            unset($newtaskbar);

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "UPDATE users_expanded SET accent = :accent, taskbar = :taskbar, taskbar_size = :taskbar_size, wallpaper = :wallpaper, profile_picture = :pp WHERE users_expanded.id = :user_id";
            $param = [
                [
                    "name" => "accent",
                    "value" => $accent,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "taskbar",
                    "value" => $taskbar,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "taskbar_size",
                    "value" => $taskbar_size,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "wallpaper",
                    "value" => $wallpaper,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "pp",
                    "value" => $pp_id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $this->database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "saved"];
        }
    }
}
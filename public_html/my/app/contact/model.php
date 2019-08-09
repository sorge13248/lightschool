<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Database;

    final class Contact {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public function getList ($sortBy = null, $limit = null) {
            if (!is_int($limit)) {
                $limit = 0;
            }
            $limit = $limit !== null ? $limit : 0;

            $this->database->setTableDotField();
            $query = "SELECT users_expanded.name, users_expanded.surname, contact.id, contact.name, contact.surname, contact_id, profile_picture, username, contact.fav, users.id FROM contact, users_expanded, users WHERE contact.trash = 0 AND contact.deleted = 0 AND contact.user_id = :user_id AND contact_id IS NOT NULL AND contact.contact_id = users_expanded.id AND users_expanded.id = users.id ORDER BY ";
            if ($sortBy === "surname, name") {
                $query .= "contact.surname, contact.name";
            } else {
                $query .= "contact.name, contact.surname";
            }

            if ($limit !== -1) {
                $query .= " LIMIT {$limit}, 20";
            }

            $contacts = $this->database->query($query, [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (count($contacts) > 0) {
                $blockedList = User::get(["blocked"])["blocked"];
                if ($blockedList !== null) {
                    $blockedList = json_decode($blockedList);
                }
            }

            foreach ($contacts as &$contact) {
                $contact['users_expanded.profile_picture'] = User::get(["profile_picture"], $contact["users.id"])["profile_picture"];

                if (isset($blockedList) && $blockedList !== null) {
                    if (in_array($contact["users.id"], $blockedList)) {
                        $contact["blocked"] = 1;
                    } else {
                        $contact["blocked"] = 0;
                    }
                }
                unset($contact["users.id"]);
            }

            return $contacts;
        }

        public function getDetails ($id, $userid = null) {
            $this->database->setTableDotField();
            $contact = $this->database->query("SELECT users_expanded.name, users_expanded.surname, profile_picture, username, contact.name, contact.surname, contact.fav FROM contact, users_expanded, users WHERE deleted = 0 AND contact.id = :contact AND contact.user_id = :user_id AND contact_id = users_expanded.id AND users_expanded.id = users.id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "contact",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($contact[0])) {
                $contact = $contact[0];
                $contact['profile_picture'] = (!isset($contact['profile_picture']) ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $contact['profile_picture']);

                $data = ["response" => "success"];
                $data["contact"] = $contact;

                return $data;
            } else {
                return ["response" => "error"];
            }
        }

        public function create ($name, $surname, $username, $userid = null) {
            $name = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $name); // replaces invalid chars with whitespaces
            $surname = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $surname); // replaces invalid chars with whitespaces
            $username = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $username); // replaces invalid chars with whitespaces

            if (strlen($name) === 0) { // name too short
                return ["response" => "error", "text" => "name_too_short"];
            }
            if (strlen($surname) === 0) { // surname too short
                return ["response" => "error", "text" => "surname_too_short"];
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
            if (strlen($username) > 255) { // username too long
                return ["response" => "error", "text" => "username_too_long"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "SELECT contact.name, contact.surname FROM contact, users WHERE contact.deleted = 0 AND contact.trash = 0 AND users.username = :username AND users.id = contact.contact_id AND contact.user_id = :user_id LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "username",
                    "value" => $username,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $items = $this->database->query($query, $param, "fetchAll");

            if (isset($items[0])) { // a contact already exists with $username
                return ["response" => "error", "text" => "already_exists", "additional" => ["name" => $items[0]["name"], "surname" => $items[0]["surname"]]];
            }

            $query = "SELECT id FROM users WHERE users.username = :username AND users.id != :user_id LIMIT 1";
            $param = [
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
            $items = $this->database->query($query, $param, "fetchAll");

            if (!isset($items[0])) { // no user with $username
                return ["response" => "error", "text" => "invalid_username"];
            }

            $this->database->query("INSERT INTO contact(user_id, name, surname, contact_id) VALUES (:user_id, :name, :surname, :contact_id)", [
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
                    "name" => "surname",
                    "value" => $surname,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "contact_id",
                    "value" => $items[0]["id"],
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "contact_created"];
        }

        public function delete ($id, $type = null, $userid = null) {
            $id = isset($id) && (int)$id ? (int)$id : null;
            $type = isset($type) ? $type : "move_to_trash";

            if ($id !== null && $id > 0) {
                $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

                $field = "trash";
                if ($type === "delete_completely") {
                    $field = "deleted";
                }

                $this->database->query("UPDATE contact SET $field = 1 WHERE user_id = :user_id AND id = :id LIMIT 1", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "id",
                        "value" => $id,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                if ($type === "delete_completely") {
                    return ["response" => "success", "text" => "contact_deleted_completely"];
                } else {
                    return ["response" => "success", "text" => "contact_deleted"];
                }
            } else {
                return ["response" => "error", "text" => "invalid_id"];
            }
        }

        public function fav ($id, $type = null, $userid = null) {
            $id = isset($id) && (int)$id ? (int)$id : null;
            $type = isset($type) ? $type : "add";

            if ($id !== null && $id > 0) {
                $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

                $field = 1;
                if ($type === "remove") {
                    $field = 0;
                }

                $this->database->query("UPDATE contact SET fav = :fav WHERE user_id = :user_id AND id = :id LIMIT 1", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "id",
                        "value" => $id,
                        "type" => \PDO::PARAM_STR,
                    ],
                    [
                        "name" => "fav",
                        "value" => $field,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                if ($type === "remove") {
                    return ["response" => "success", "text" => "removed"];
                } else {
                    return ["response" => "success", "text" => "added"];
                }
            } else {
                return ["response" => "error", "text" => "invalid_id"];
            }
        }

        public function block ($username, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $findUserId = $this->database->query("SELECT id FROM users WHERE id != :user_id AND username = :username LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "username",
                    "value" => $username,
                    "type" => \PDO::PARAM_STR,
                ],
            ], "fetchAll");

            if (isset($findUserId[0]["id"])) {
                $blockedList = User::get(["blocked"], $userid)["blocked"];

                if ($blockedList === null) {
                    $blockedList = [];
                } else {
                    $blockedList = json_decode($blockedList);
                }

                if (in_array($findUserId[0]["id"], $blockedList)) {
                    $type = "unblocked";
                    foreach ($blockedList as $key => $value) {
                        if ($value == $findUserId[0]["id"]) {
                            unset($blockedList[$key]);
                            break;
                        }
                    }
                } else {
                    $type = "blocked";
                    array_push($blockedList, $findUserId[0]["id"]);
                }

                $this->database->query("UPDATE users_expanded SET blocked = :blocked WHERE id = :user_id LIMIT 1", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "blocked",
                        "value" => count($blockedList) === 0 ? null : implode(",", $blockedList),
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                return ["response" => "success", "text" => $type];
            } else {
                return ["response" => "error", "text" => "invalid_id"];
            }
        }
    }
}
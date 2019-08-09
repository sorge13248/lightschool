<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Crypto;
    use FrancescoSorge\PHP\Database;

    final class Message {

        public static function list ($start = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($start === null) $start = 0;
            $start = "LIMIT {$start}, 20";

            $query = "SELECT MAX(message_chat.date) AS date, message_list.id FROM message_actors, message_list, message_chat WHERE message_actors.user_id = :user_id AND message_actors.list_id = message_list.id AND message_list.id = message_chat.message_list_id GROUP BY id ORDER BY date DESC {$start}";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $database->query($query, $param, "fetchAll");

            foreach ($items as &$item) {
                $item["date"] = Basic::timestampToHuman($item["date"]);
                $other = $database->query("SELECT user_id FROM message_actors WHERE list_id = :id AND user_id != :me LIMIT 1", [
                    [
                        "name" => "id",
                        "value" => $item["id"],
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "me",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                $other = isset($other[0]) ? $other[0] : ["user_id" => $userid];

                $item["user"] = User::get(["name", "surname", "profile_picture"], $other["user_id"]);

                // Are there messages to be read?
                $item["new"] = isset($database->query("SELECT id FROM message_chat WHERE message_list_id = :id AND sender != :user AND is_read IS NULL LIMIT 1", [
                        [
                            "name" => "id",
                            "value" => $item["id"],
                            "type" => \PDO::PARAM_INT,
                        ],
                        [
                            "name" => "user",
                            "value" => $userid,
                            "type" => \PDO::PARAM_INT,
                        ],
                ], "fetchAll")[0]);
            }

            return $items;
        }

        public static function chat ($id, $start = null, $userid = null) {
            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($start === null) $start = 0;
            $start = "LIMIT {$start}, 20";

            $query = "SELECT message_list.id, message_chat.date, message_chat.cypher, message_chat.body, message_chat.sender, message_chat.attachment, message_chat.is_read FROM message_actors, message_list, message_chat WHERE message_actors.user_id = :user_id AND message_actors.list_id = :list_id AND message_actors.list_id = message_list.id AND message_list.id = message_chat.message_list_id ORDER BY message_chat.date DESC {$start}";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "list_id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $item = $database->query($query, $param, "fetchAll");

            if (count($item) === 0) {
                return ["response" => "error", "text" => "invalid_conversation_id"];
            }

            $query = "UPDATE message_chat SET is_read = NOW() WHERE message_list_id = :list_id AND sender != :user_id AND is_read IS NULL";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "list_id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            foreach ($item as &$value) {
                $value["date"] = Basic::timestampToHuman($value["date"]);
                if ($value["is_read"] !== null) {
                    $value["is_read"] = Basic::timestampToHuman($value["is_read"]);
                }
                $value["body"] = Crypto::decrypt($value["body"], $value["cypher"], $value["sender"]);

                if ($value["attachment"] !== null) {
                    $value["attachment"] = Crypto::decrypt($value["attachment"], $value["cypher"], $value["sender"]);
                    $value["attachment"] = json_decode($value["attachment"], true);
                    if ($value["attachment"]["type"] === "contact") {
                        $value["attachment"]["user"] = User::get(["username", "name", "surname", "profile_picture"], $value["attachment"]["user"]);
                    }
                }
                unset($value["cypher"]);
            }

            $other = $database->query("SELECT user_id FROM message_actors WHERE list_id = :id AND user_id != :me LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "me",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");
            $other = isset($other[0]) ? $other[0] : ["user_id" => $userid];
            $other["user_id"] = User::get(["username"], $other["user_id"])["username"];

            return ["response" => "success", "current_user_id" => $userid, "other_user" => User::get(["name", "surname", "profile_picture"], null, $other["user_id"]), "chat" => $item];
        }

        public static function new ($username, $body, $attach = null, $userid = null) {
            if ($username === null) {
                return ["response" => "error", "text" => "username"];
            }
            if ($body === null) {
                return ["response" => "error", "text" => "body"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $id = User::get(["id"], null, $username);

            if ($id === null) {
                return ["response" => "error", "text" => "invalid_username"];
            } else {
                $id = $id["id"];
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($id === $userid) {
                return ["response" => "error", "text" => "same"];
            }

            if ($attach !== null) {
                $attach = json_decode($attach, true);
                switch ($attach["type"]) {
                    case "contact":
                        $id = User::get(["id"], null, $attach["value"]);
                        if (!isset($id["id"])) {

                        }
                        $attach["user"] = $id["id"];
                        break;
                    default:
                        $attach = null;
                }
                if ($attach !== null) {
                    unset($attach["value"]);
                    $attach = json_encode($attach);
                }
            }

            $query = "SELECT a.list_id FROM message_actors a, message_actors b WHERE a.user_id = :user_id AND a.list_id = b.list_id AND b.user_id = :id LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $item = $database->query($query, $param, "fetchAll");

            if (count($item) === 0) {
                $query = "INSERT INTO message_list() VALUES()";
                $listID = $database->query($query, [], "fetchAll");

                $query = "INSERT INTO message_actors(list_id, user_id) VALUES (:list_id, :user_id)";
                $database->query($query, [
                    [
                        "name" => "list_id",
                        "value" => $listID,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                $database->query($query, [
                    [
                        "name" => "list_id",
                        "value" => $listID,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "user_id",
                        "value" => $id,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");
            } else {
                $listID = $item[0]["list_id"];
            }

            $response = self::send($listID, $body, $attach, $userid);
            $response["id"] = $listID;

            return $response;
        }

        public static function send ($id, $body, $attach = null, $userid = null) {
            if ($id === null) {
                return ["response" => "error", "text" => "id"];
            }
            if ($body === null) {
                return ["response" => "error", "text" => "body"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "SELECT id FROM message_actors WHERE list_id = :id AND user_id = :user_id LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $item = $database->query($query, $param, "fetchAll");

            if (count($item) === 0) {
                return ["response" => "error", "text" => "id"];
            }

            $other = $database->query("SELECT user_id FROM message_actors WHERE list_id = :id AND user_id != :user_id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ]], "fetchAll")[0]["user_id"];
            $privacy = (int)(User::get(["privacy_send_messages"], $other)["privacy_send_messages"]);

            if ($privacy === 0) {
                return ["response" => "error", "text" => "privacy"];
            } else if ($privacy === 1) {
                $result = $database->query("SELECT id FROM contact WHERE user_id = :user AND contact_id = :contact AND trash = 0 AND deleted = 0 LIMIT 1", [
                    [
                        "name" => "user",
                        "value" => $other,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "contact",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ]
                ], "fetchAll");

                if (!isset($result[0])) {
                    return ["response" => "error", "text" => "privacy"];
                }
            }

            $crypto = \FrancescoSorge\PHP\Crypto::encrypt("<p>" . $body . "</p>", null, $userid);
            if ($attach !== null) {
                $rsa = new \phpseclib\Crypt\RSA();
                $rsa->setHash("sha512");
                $rsa->loadKey(\FrancescoSorge\PHP\Keyring::get("private", $userid));
                $planKey = $rsa->decrypt($crypto["key"]);
                $crypto2 = \FrancescoSorge\PHP\Crypto::encrypt($attach, $planKey, $userid);
            }

            $query = "INSERT INTO message_chat(message_list_id, sender, cypher, body, attachment) VALUES (:id, :sender, :cypher, :body, :attach)";
            $param = [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "sender",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "cypher",
                    "value" => $crypto["key"],
                    "type" => \PDO::PARAM_LOB,
                ],
                [
                    "name" => "body",
                    "value" => $crypto["data"],
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "attach",
                    "value" => $attach !== null ? $crypto2["data"] : null,
                    "type" => \PDO::PARAM_STR,
                ],
            ];
            $database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }
    }
}
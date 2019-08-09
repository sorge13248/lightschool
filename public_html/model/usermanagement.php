<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Database;
    use FrancescoSorge\PHP\Email;

    final class UserManagement {
        private $email, $auth;

        public function __construct () {
            $this->auth = new \Delight\Auth\Auth(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]), null, null, false);
        }

        public function isLogged () {
            return $this->auth->isLoggedIn();
        }

        public function checkPassword ($password) {
            try {
                return $this->auth->reconfirmPassword($password);
            } catch (\Delight\Auth\AuthError $e) {
                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not login", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                return ["response" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\UnknownUsernameException  $e) {
                return ["response" => "error_UnknownUsernameException"];
            } catch (\Delight\Auth\AttemptCancelledException  $e) {
                return ["response" => "error_AttemptCancelledException"];
            } catch (\Delight\Auth\AmbiguousUsernameException  $e) {
                return ["response" => "error_AmbiguousUsernameException"];
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                return ["response" => "error_InvalidPasswordException"];
            } catch (\Delight\Auth\EmailNotVerifiedException $e) {
                return ["response" => "error_EmailNotVerifiedException"];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                return ["response" => "error_TooManyRequestsException"];
            } catch (\Delight\Auth\NotLoggedInException $e) {
                return ["response" => "error_NotLoggedInException"];
            }
        }

        public function register ($name, $surname, $email, $username, $password, $password_2) {
            $array = ["response" => "error", "text" => ""];
            try {
                $name = trim($name);
                $name = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], "", $name);
                if (strlen($name) === 0) {
                    $array["text"] = "Name is empty. ";
                }
                $surname = trim($surname);
                $surname = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], "", $surname);
                if (strlen($surname) === 0) {
                    $array["text"] .= "Surname is empty. ";
                }

                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $array["text"] .= "Email is not valid. ";
                }

                $username = trim($username);
                $username = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", " "], "", $username);
                if (strlen($username) === 0) {
                    $array["text"] .= "Username is empty. ";
                }
                if (strlen($username) > 0 && strlen($username) < 4) {
                    $array["text"] .= "Username must be at least 4 characters long. ";
                }
                if ($this->isPasswordAllowed($password) == false) {
                    $array["text"] .= "Password too easy, choose a stronger one. Password must be at least 8 characters long and should not be too easy to guess (like 12345678 and so). ";
                    //throw new \Delight\Auth\InvalidPasswordException;
                }
                if ($password !== $password_2) {
                    $array["text"] .= "Passwords are different!";
                }

                if ($array["response"] === "error" && $array["text"] !== "") {
                    return $array;
                }

                $this->email = $email;
                $userId = $this->auth->registerWithUniqueUsername($email, $password, $username, function ($selector, $token) {
                    // send `$selector` and `$token` to the user (e.g. via email)
                    $url = $this->generateUrl("registration", $selector, $token);
                    $register = new Email(CONFIG_EMAIL["host"]);
                    $register->send(["address" => CONFIG_EMAIL["no-reply"]["email"], "name" => CONFIG_EMAIL["no-reply"]["name"]], CONFIG_EMAIL["no-reply"]["password"], [["address" => $this->email, "name" => ""]], "New account", "$url");
                });

                // we have signed up a new user with the ID `$userId`
                $this->registerUserExpanded($userId, trim($name), trim($surname)); // Added name and surname to user_expanded table

                $append = "";
                try {
                    (new \FrancescoSorge\PHP\Keyring())->set($userId);
                } catch (\Exception $e) {
                    $append = "<br/>IMPORTANT: Something went wrong while creating your public and private keys. Contact support center before logging in.";
                }

                $array["response"] = "success";
                $array["text"] = ["header" => "Registration successfully", "text" => "Your username is <code>$username</code>. Now you need to activate your account in order to proceed further. We sent you an email at $email so check your inbox (and spam folder) and click the link we sent you, then you will be able to login with your credentials.<br/>If you do not receive any email from us within an hour, contact us from $email. $append"];
            } catch (\Delight\Auth\AuthError $e) {

                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not create a new user", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                $array["response"] = "error";
                $array["text"] .= "Authentication error. Try again later. ";
            } catch (\Delight\Auth\InvalidEmailException $e) {
                $array["response"] = "error";
                $array["text"] .= "Invalid email address. ";
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                $array["response"] = "error";
                $array["text"] .= "Invalid password. ";
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                $array["response"] = "error";
                $array["text"] .= "User already exists with this email. ";
            } catch (\Delight\Auth\DuplicateUsernameException $e) {
                $array["response"] = "error";
                $array["text"] .= "User already exists with this username. ";
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $array["response"] = "error";
                $array["text"] .= "Too many requests! Blocked. ";
            }
            return $array;
        }

        protected function isPasswordAllowed ($password) {
            if (strlen($password) < 8) {
                return false;
            }

            $blacklist = ['password', '12345678', 'qwerty', 'azerty', 'qwertz', 'helloooo'];

            if (in_array($password, $blacklist)) {
                return false;
            }

            return true;
        }

        protected function generateUrl ($type, $selector, $token = null) {
            switch ($type) {
                case "registration":
                    return CONFIG_SITE["baseURL"] . '/my/verify?type=' . urlencode($type) . '&selector=' . urlencode($selector) . '&token=' . urlencode($token);
                    break;
                case "password-recover":
                    return CONFIG_SITE["baseURL"] . '/my/password?selector=' . urlencode($selector) . '&token=' . urlencode($token);
                    break;
                case "deactivate-twofa":
                    return CONFIG_SITE["baseURL"] . '/my/otp?token=' . urlencode($selector);
                    break;
            }
        }

        protected function registerUserExpanded ($id, $name, $surname) {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
            $database->query("INSERT INTO users_expanded (id, name, surname) VALUES (:id, :name, :surname)",
                [
                    ["name" => "id", "value" => $id, "type" => \PDO::PARAM_INT],
                    ["name" => "name", "value" => $name, "type" => \PDO::PARAM_STR],
                    ["name" => "surname", "value" => $surname, "type" => \PDO::PARAM_STR],
                ]
            );
            $database->closeConnection();
            unset($database);
        }

        public function emailVerification ($selector, $token) {
            try {
                $this->auth->confirmEmail($selector, $token);
                return ["response" => "success", "text" => "ok"];
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
                return ["response" => "error", "text" => "selector"];
            } catch (\Delight\Auth\TokenExpiredException $e) {
                return ["response" => "error", "text" => "token"];
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                return ["response" => "error", "text" => "already"];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                return ["response" => "error", "text" => "too"];
            }
            return false;
        }

        public function login ($username, $password, $rememberMeDuration = null) {
            try {
                $this->auth->loginWithUsername($username, $password, $rememberMeDuration);
                //$this->auth->admin()->logInAsUserById(165);
                return ["response" => "success"];
            } catch (\Delight\Auth\AuthError $e) {

                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not login", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                return ["response" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\UnknownUsernameException  $e) {
                return ["response" => "error_UnknownUsernameException"];
            } catch (\Delight\Auth\AttemptCancelledException  $e) {
                return ["response" => "error_AttemptCancelledException"];
            } catch (\Delight\Auth\AmbiguousUsernameException  $e) {
                return ["response" => "error_AmbiguousUsernameException"];
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                return ["response" => "error_InvalidPasswordException"];
            } catch (\Delight\Auth\EmailNotVerifiedException $e) {
                return ["response" => "error_EmailNotVerifiedException"];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                return ["response" => "error_TooManyRequestsException"];
            }
        }

        public function recover ($username) {
            $array = ["response" => "", "text" => ""];
            $this->email = "";

            if (isset($username) && $username != "") {
                $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
                $requestedEmail = $database->query("SELECT email FROM users WHERE username = :username LIMIT 1",
                    [
                        ["name" => "username", "value" => $username, "type" => \PDO::PARAM_STR],
                    ]
                );
                $database->closeConnection();
                unset($database);

                if (is_object($requestedEmail)) {
                    $this->email = $requestedEmail->email;
                }
            }

            try {
                if (!$this->email) {
                    throw new \Delight\Auth\InvalidEmailException;
                }

                $this->auth->forgotPassword($this->email, function ($selector, $token) {
                    // send `$selector` and `$token` to the user (e.g. via email)
                    $url = $this->generateUrl("password-recover", $selector, $token);

                    $recoverPasswordEmail = new Email(CONFIG_EMAIL["host"]);
                    $recoverPasswordEmail->send(["address" => CONFIG_EMAIL["no-reply"]["email"], "name" => CONFIG_EMAIL["no-reply"]["name"]], CONFIG_EMAIL["no-reply"]["password"], [["address" => $this->email, "name" => ""]], "Password recover", "$url");
                });

                // request has been generated
                $email = $this->email;
                unset($this->email);
                $array = ["response" => "success", "text" => ["header" => "Email sent", "text" => "An email has been sent to your email address ({$this->obfuscateEmail($email)})."]];
            } catch (\Delight\Auth\AuthError $e) {

                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not recover your password", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                $array = ["response" => "error", "text" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\InvalidEmailException $e) {
                // invalid email address
                $array = ["response" => "error", "text" => "Invalid username."];
            } catch (\Delight\Auth\EmailNotVerifiedException $e) {
                // email not verified
                $array = ["response" => "error", "text" => "This email address has not been verified yet."];
            } catch (\Delight\Auth\ResetDisabledException $e) {
                // password reset is disabled
                $array = ["response" => "error", "text" => "This account cannot reset its password this way. Do you need help? Contact us."];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                // too many requests
                $array = ["response" => "error", "text" => "Too many requests! Blocked."];
            }
            return $array;
        }

        public function obfuscateEmail ($email) {
            $domain = explode("@", $email);
            $name = implode(array_slice($domain, 0, count($domain) - 1), '@');
            $len = floor(strlen($name) / 4);

            return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($domain);
        }

        public function recoverCanSetNewPassword ($selector, $token, $callFunction = null) {
            try {
                $canResetPassword = $this->auth->canResetPassword($selector, $token);
                if ($canResetPassword && $callFunction === "recoverSetNewPassword") {
                    return $this->recoverSetNewPassword($selector, $token);
                } else if ($canResetPassword) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Delight\Auth\AuthError $e) {
                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not check if your request is legit", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                return false;
            }
        }

        protected function recoverSetNewPassword ($selector, $token) {
            try {
                $newPassword = new Basic();
                $newPassword = $newPassword->generateRandomID(12);
                $this->auth->resetPassword($selector, $token, $newPassword);

                // password has been reset
                $array = ["response" => "success", "text" => ["password" => "$newPassword"]];
            } catch (\Delight\Auth\AuthError $e) {
                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not set a new password", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                $array = ["response" => "error", "text" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
                // invalid token
                $array = ["response" => "error", "text" => "Selector and token has expired."];
            } catch (\Delight\Auth\TokenExpiredException $e) {
                // token expired
                $array = ["response" => "error", "text" => "Token has expired."];
            } catch (\Delight\Auth\ResetDisabledException $e) {
                // password reset is disabled
                $array = ["response" => "error", "text" => "This account cannot reset its password this way. Do you need help? Contact us."];
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                // invalid password
                $array = ["response" => "error", "text" => "An invalid password has been provided."];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                // too many requests
                $array = ["response" => "error", "text" => "Too many requests! Blocked."];
            }
            return $array;
        }

        public function changePassword ($oldPassword, $newPassword) {
            try {
                $this->auth->changePassword($oldPassword, $newPassword);

                $recoverPasswordEmail = new Email(CONFIG_EMAIL["host"]);
                $recoverPasswordEmail->send(["address" => CONFIG_EMAIL["no-reply"]["email"], "name" => CONFIG_EMAIL["no-reply"]["name"]], CONFIG_EMAIL["no-reply"]["password"], [["address" => $this->getCurrentUserInfo("email", "users")->email, "name" => $this->getCurrentUserInfo("name", "users_expanded")->name . " " . $this->getCurrentUserInfo("surname", "users_expanded")->surname]], "Password changed", "Your account's password has changed. If you made that change, you can ignore this e-mail. Otherwise we recommend you to recover your current password (using Password Recover procedure) and then change it immediately to a new one.");

                // password changed
                $array = ["response" => "success", "text" => "Password changed successfully"];
            } catch (\Delight\Auth\AuthError $e) {
                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not set a new password", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                $array = ["response" => "error", "text" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\NotLoggedInException $e) {
                // not logged in
                $array = ["response" => "error", "text" => "You're not logged in."];
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                // invalid password
                $array = ["response" => "error", "text" => "old"];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                // too many requests
                $array = ["response" => "error", "text" => "Too many requests! Blocked."];
            }
            return $array;
        }

        public function getCurrentUserInfo ($fields, $tables, $append = "") {
            if (!$this->auth->isLoggedIn()) {
                return ["error" => "Not logged in"];
            }

            if (is_string($fields)) {
                $fields = [$fields];
            }
            if (is_string($tables)) {
                $tables = [$tables];
            }

            $query = "SELECT ";
            $i = 1;
            foreach ($fields as $field) {
                $query .= "$field";
                if ($i < count($fields)) {
                    $query .= ", ";
                }
                $i++;
            }

            $query .= " FROM ";
            $i = 1;
            foreach ($tables as $table) {
                $query .= "$table";
                if ($i < count($tables)) {
                    $query .= ", ";
                }
                $i++;
            }
            $query .= " WHERE id = :user_id";
            if ($append !== "") {
                $query .= " {$append}";
            }

            $query .= " LIMIT 1";

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
            $currentUserInfo = $database->query($query,
                [
                    ["name" => "user_id", "value" => $this->auth->getUserId(), "type" => \PDO::PARAM_INT],
                ]
            );
            $database->closeConnection();
            unset($database);

            if (is_object($currentUserInfo)) {
                return $currentUserInfo;
            } else {
                $this->logout();
                return null;
            }
        }

        public function logout () {
            try {
                $this->auth->logOut();
                $this->auth->destroySession();
                return true;
            } catch (\Delight\Auth\AuthError $e) {
                return false;
            }
        }

        public function changeEmail ($email) {
            try {
                $this->auth->changeEmail($email, function ($selector, $token) {
                    $url = $this->generateUrl("registration", $selector, $token);
                    $register = new Email(CONFIG_EMAIL["host"]);
                    $register->send(["address" => CONFIG_EMAIL["no-reply"]["email"], "name" => CONFIG_EMAIL["no-reply"]["name"]], CONFIG_EMAIL["no-reply"]["password"], [["address" => $this->email, "name" => ""]], "Email changed", "$url");
                });

                $array = ["response" => "success", "text" => "email_changed"];
            } catch (\Delight\Auth\AuthError $e) {
                $eAuthError = new ErrorHandler(CONFIG_DATABASE, "Auth Error", "Delight Auth could not set a new password", "Error output: {$e}.Page URL: " . Basic::getURL() . " and script path: " . Basic::getScriptPath() . ". Error ID: ");
                $eAuthError->reportError();
                $array = ["response" => "error", "text" => "Authentication error. {$eAuthError->getDescription()}. Try again later.<br/>A bug report has been automatically sent."];
            } catch (\Delight\Auth\InvalidEmailException $e) {
                return ["response" => "error_InvalidEmail"];
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                return ["response" => "error_EmailAlreadyExists"];
            } catch (\Delight\Auth\EmailNotVerifiedException $e) {
                return ["response" => "error_EmailNotVerifiedException"];
            } catch (\Delight\Auth\NotLoggedInException $e) {
                return ["response" => "error_NotLoggedIn"];
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                return ["response" => "error_TooManyRequestsException"];
            }
            return $array;
        }

        public function deactivateOTP ($phase, $username) {
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if ($phase === 1) {
                $result = $database->query("SELECT users.id, users.email FROM users, users_expanded WHERE users.id = users_expanded.id AND users.username = :username AND users_expanded.twofa IS NOT NULL LIMIT 1", [
                    [
                        "name" => "username",
                        "value" => $username,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");
                if (isset($result[0])) {
                    $result = $result[0];
                    $token = Basic::generateRandomID(128);

                    $database->query("UPDATE users_expanded SET deac_twofa = :token WHERE id = :id AND twofa IS NOT NULL LIMIT 1", [
                        [
                            "name" => "id",
                            "value" => $result["id"],
                            "type" => \PDO::PARAM_INT,
                        ],
                        [
                            "name" => "token",
                            "value" => $token,
                            "type" => \PDO::PARAM_STR,
                        ],
                    ], "fetchAll");

                    $url = $this->generateUrl("deactivate-twofa", $token);
                    $register = new Email(CONFIG_EMAIL["host"]);
                    $register->send(["address" => CONFIG_EMAIL["no-reply"]["email"], "name" => CONFIG_EMAIL["no-reply"]["name"]], CONFIG_EMAIL["no-reply"]["password"], [["address" => $result["email"], "name" => ""]], "Deactivate 2FA", "$url");

                    return ["response" => "success", "text" => "ok"];
                } else {
                    return ["response" => "error", "text" => "user"];
                }
            } else if ($phase === 2) {
                $result = $database->query("SELECT id FROM users_expanded WHERE deac_twofa = :token LIMIT 1", [
                    [
                        "name" => "token",
                        "value" => $username,
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");
                if (isset($result[0])) {
                    $result = $result[0];

                    $database->query("UPDATE users_expanded SET twofa = NULL, deac_twofa = NULL WHERE id = :id AND twofa IS NOT NULL LIMIT 1", [
                        [
                            "name" => "id",
                            "value" => $result["id"],
                            "type" => \PDO::PARAM_INT,
                        ],
                    ], "fetchAll");

                    return ["response" => "success", "text" => "ok"];
                } else {
                    return ["response" => "error", "text" => "token"];
                }
            } else {
                return ["response" => "error", "text" => "phase"];
            }
        }
    }
}
<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

namespace FrancescoSorge\PHP {

    final class Basic {
        const NAME = "Basic";
        const VERSION = 2.3;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\Basic";
        const LICENSE = "MIT";

        protected static $debugMode = false;

        public static function getIP () {
            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }

            if ($_SERVER['REMOTE_ADDR'] === "::1") return "localhost";

            return $_SERVER['REMOTE_ADDR'];
        }

        /**
         * @param int $length (optional): specify the length of the generated ID
         * @return string: generated ID
         */
        public static function generateRandomID ($length = 16) {
            $pool = array_merge(range(0, 9), range('a', 'z'));
            $key = "";

            for ($i = 0; $i < $length; $i++) {
                $key .= $pool[mt_rand(0, count($pool) - 1)];
            }

            return $key;
        }

        /**
         * @return string: complete URL
         */
        public static function getURL () {
            return ((self::checkHTTPS()) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }

        /**
         * @return bool: true if user is connected through HTTPS, otherwise false
         */
        public static function checkHTTPS () {
            $isSecure = false;

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $isSecure = true;
            } else if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
                $isSecure = true;
            }

            if (self::$debugMode) {
                $isSecure = true;
            }

            return $isSecure;
        }

        /**
         * @return string: script path
         */
        public static function getScriptPath () {
            return $_SERVER["SCRIPT_FILENAME"];
        }

        /**
         * @return string: page name
         */
        public static function getPageName () {
            return basename($_SERVER["PHP_SELF"]);
        }

        public static function getHost () {
            return $_SERVER['SERVER_NAME'];
        }

        /**
         * @param string $url : pass url where JSON data resides
         * @return array: json is transformed in array
         */
        public static function getJSON ($url, $parsePHP = true, $allowUrlFopen = false) {
            if ($parsePHP) {
                ob_start();

                $_GET["header"] = "";
                include $url;

                return json_decode(ob_get_clean());
            }

            if ($allowUrlFopen) ini_set("allow_url_fopen", 1);

            return file_get_contents($url);
        }

        /**
         * @param array $values : values to search in array
         * @param array $array : array where to look for $values
         * @param bool $all : if true, $array MUST CONTAIN ALL $values, otherwise it just look for one correspondence
         * @return bool: returns true if condition satisfied (quite self-explanatory)
         */
        public static function in_array ($values, $array, $all = false) {
            if ($all) {
                foreach ($values as $value) {
                    if (!in_array($value, $array)) return false;
                }

                return true;
            } else {
                foreach ($values as $value) {
                    if (in_array($value, $array)) return true;
                }
            }
        }

        /**
         * @param $timestamp : a timestamp is formatted as Y-m-d h:i:s. It is usually used in MySQL.
         * @param string $format (optional): specify the format of the output according to http://php.net/manual/function.date.php#refsect1-function.date-parameters
         * @return string: formatted timestamp
         */
        public static function timestampToHuman ($timestamp, $format = "d/m/Y H:i") {
            return date($format, strtotime($timestamp));
        }

        /**
         * @param $username : username for .htpasswd file
         * @param $password : password to be encrypted for .htpasswd file
         * @return string: to be copied in a new blank row of .htpasswd file
         */
        public static function generateHtpasswd ($username, $password) {
            return $username . ":" . self::generatePasswd($password);
        }

        /**
         * @param $clearPassword : password to be encrypted for a .htpasswd file
         * @return string: encrypted password
         */
        public static function generatePasswd ($clearPassword) {
            $salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
            $len = strlen($clearPassword);
            $text = $clearPassword . '$apr1$' . $salt;
            $bin = pack("H32", md5($clearPassword . $salt . $clearPassword));
            for ($i = $len; $i > 0; $i -= 16) {
                $text .= substr($bin, 0, min(16, $i));
            }
            for ($i = $len; $i > 0; $i >>= 1) {
                $text .= ($i & 1) ? chr(0) : $clearPassword{0};
            }
            $bin = pack("H32", md5($text));
            for ($i = 0; $i < 1000; $i++) {
                $new = ($i & 1) ? $clearPassword : $bin;
                if ($i % 3) $new .= $salt;
                if ($i % 7) $new .= $clearPassword;
                $new .= ($i & 1) ? $bin : $clearPassword;
                $bin = pack("H32", md5($new));
            }
            for ($i = 0; $i < 5; $i++) {
                $k = $i + 6;
                $j = $i + 12;
                if ($j == 16) $j = 5;
                $tmp = $bin[$i] . $bin[$k] . $bin[$j] . (isset($tmp) ? $tmp : "");
            }
            $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
            $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
                "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
                "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");

            return "$" . "apr1" . "$" . $salt . "$" . $tmp;
        }

        /**
         * @param $a (by reference)
         * @param $b (by reference)
         * Swaps two variables by just calling Basic::swap($variable1, $variable2);
         */
        public static function swap (&$a, &$b) {
            $temp = $b;
            $b = $a;
            $a = $temp;
        }

        public static function inArrayPartial ($array, $value) {
            foreach ($array as $item) {
                if (stripos($value, $item) !== false) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @param $hex : input hex color
         * @param $steps (optional): positive is lighter, negative is darker
         * @return string: new hex color
         * @author Torkil Johnsen on StackOverflow
         */
        public static function adjustBrightness ($hex, $steps = 20) {
            // Steps should be between -255 and 255. Negative = darker, positive = lighter
            $steps = max(-255, min(255, $steps));

            // Normalize into a six character long hex string
            $hex = str_replace('#', '', $hex);
            if (strlen($hex) == 3) {
                $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
            }

            // Split into three parts: R, G and B
            $color_parts = str_split($hex, 2);
            $return = '#';

            foreach ($color_parts as $color) {
                $color = hexdec($color); // Convert to decimal
                $color = max(0, min(255, $color + $steps)); // Adjust color
                $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
            }

            return strtoupper($return);
        }

        /**
         * @param $hex
         * @return array: rgb color
         * @author john on StackOverflow
         */
        public static function hexToRgb ($hex) {
            return sscanf($hex, "#%02x%02x%02x");
        }

        /**
         * @param $array : array to be printed with style. Must be a valid array
         */
        public static function print_r ($array, $title = null) {
            if (is_object($array)) $array = json_decode(json_encode($array), true);

            if (is_array($array)) {
                echo("<table border='1' cellpadding='10'>");
                if ($title !== null) echo("<tr><td colspan='2'><h3>$title</h3></td></tr>");
                echo("<tr style='font-weight: bold'><td>Key</td><td>Value</td></tr>");

                foreach ($array as $key => $item) {
                    echo("<tr>");
                    $info = ["key" => "", "item" => ""];
                    if (is_string($key)) {
                        $info["key"] = "(" . strlen($key) . ")";
                    }
                    if (is_string($item)) {
                        $info["item"] = "(" . strlen($item) . ")";
                    } else if (is_array($item)) {
                        $info["item"] = "(" . count($item) . ")";
                    }
                    echo("<td style='text-align: center'><small><i>" . gettype($key) . " {$info["key"]}</i></small><br/>$key</td>");
                    echo("<td><small><i>" . gettype($item) . " {$info["item"]}</i></small><br/>");
                    if (is_array($item)) {
                        echo("<br/>");
                        self::print_r($item);
                    } else {
                        echo(is_bool($item) ? $item === true ? "true" : "false" : $item);
                    }
                    echo("</td>");
                    echo("</tr>");
                }

                echo("</table>");
            } else {
                if ($title !== null) echo("<h3>$title</h3>");
                var_dump($array);
            }
        }
    }
}
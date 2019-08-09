<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

namespace FrancescoSorge\PHP {

    class Cookie {
        public static function set ($name, $value, $expires = null, $path = null, $domain = null) {
            if ($expires === null) {
                $expires = time() + 60 * 60 * 24 * 30; // 2592000 seconds = 30 days
            }

            if ($path === null) {
                $path = "/";
            }

            if ($domain === null) {
                $domain = str_replace("www.", "", $_SERVER['HTTP_HOST']); // cross-subdomains
            }

            setcookie($name, $value, $expires, $path, $domain);
        }

        public static function get ($name) {
            if (isset($_COOKIE[$name])) return $_COOKIE[$name];
            else return null;
        }

        public static function delete ($name, $path = null, $domain = null) {
            if ($path === null) {
                $path = "/";
            }

            if ($domain === null) {
                $domain = str_replace("www.", "", $_SERVER['HTTP_HOST']); // cross-subdomains
            }

            setcookie($name, "", time() - 3600, $path, $domain);
        }
    }
}
<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

namespace FrancescoSorge\PHP {

    class Language {
        const NAME = "Language";
        const VERSION = 2.0;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\Language";
        const LICENSE = "MIT";

        /**
         * @var string $fallbackLanguage : specify the language that will be loaded if the requested one is not available. Please, be sure that the fallback language file exists otherwise if the main language load fails, your website will become unavailable.
         */
        // @TODO remember to switch fallback language to "en" and create english file translation
        protected $fallbackLanguage = "en";
        protected $lang, $path, $strings;

        /**
         * Language constructor.
         * @param string $lang (optional): specify the language to load. If blank or not passed, the library will load the fallback language
         * @param string $path (optional): specify subdirectories to look-in inside language folder. If black or not passed, no subdirectory is intended.
         */
        public function __construct ($lang = null, $path = null) {
            if ($lang === null) {
                $cookie = new Cookie();

                if ($cookie->get("language") === null) {
                    $lang = $this->getBrowserLanguage();
                    $this->set($lang);
                } else {
                    $lang = $_COOKIE["language"];
                }
            }
            $this->lang = $lang;
            $this->path = $path;

            if ($this->fallbackLanguage) {
                if (!file_exists(__DIR__ . "/../language/{$this->fallbackLanguage}.php")) {
                    echo("FRALANGUAGE Warning: \$fallbackLanguage file missing.<br/>");
                }
            } else {
                echo("FRALANGUAGE Warning: \$fallbackLanguage not setted.<br/>");
            }

            $fraBasic = new \FrancescoSorge\PHP\Basic();
            if (file_exists(__DIR__ . "/../language/{$path}/{$lang}.php")) {
                $this->strings = $fraBasic->getJSON(__DIR__ . "/../language/{$path}/{$lang}.php");
            } else {
                if (file_exists(__DIR__ . "/../language/{$this->fallbackLanguage}.php")) {
                    $this->set($this->fallbackLanguage);
                    $this->strings = $fraBasic->getJSON(__DIR__ . "/../language/{$this->fallbackLanguage}.php");
                } else {
                    die("Primary and fallback language not available. Cannot load page. Contact the webmaster.");
                }
            }
        }

        public function getBrowserLanguage () {
            $lang = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : "en";
            switch ($lang) {
                case "it":
                case "en":
                    return $lang;
                    break;
                default:
                    return "en";
                    break;
            }
        }

        public function set ($lang) {
            $cookie = new Cookie();
            $cookie->set("language", $lang);
        }

        public function get ($string, $array = null) {
            if ($array === null) $array = false;

            if (isset($this->strings->$string))
                if ($array) return json_decode(json_encode($this->strings->$string), true);
                else return $this->strings->$string;
            else
                return "FRALANGUAGE Unknown string: {$this->lang}/{$string}";
        }

        public function getLanguage () {
            return $this->lang;
        }

        public function getFallbackLanguage () {
            return $this->fallbackLanguage;
        }

        public function getAll () {
            $languages = scandir(__DIR__ . "/../language");

            $i = 0;
            foreach ($languages as $file) {
                if (strlen($file) !== 6) {
                    unset($languages[$i]);
                } else {
                    $languages[$i] = str_replace(".php", "", $languages[$i]);
                }

                $i++;
            }

            return $languages;
        }
    }
}
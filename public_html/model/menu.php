<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 * @version 1.1
 */

namespace FrancescoSorge\PHP {
    class Menu {
        protected $class, $id, $style, $elements;

        public function __construct ($class, $id, $style, $elements) {
            $this->class = $class;
            $this->id = $id;
            $this->style = $style;
            $this->elements = $elements;
        }

        public function showMenu () {
            $menu = "<div class=\"fra-collection-menu {$this->class}\" id=\"fra-menu-{$this->id}\" style=\"{$this->style}\">";

            foreach ($this->elements as $element) {
                if (!isset($element["class"])) {
                    $element["class"] = "";
                }
                if (!isset($element["id"])) {
                    $element["id"] = "";
                }
                if (!isset($element["style"])) {
                    $element["style"] = "";
                }
                if (!isset($element["goto"])) {
                    $element["goto"] = "";
                }
                $menu .= "<a href=\"{$element["url"]}\" class=\"{$element["class"]}\" id=\"{$element["id"]}\" style=\"{$element["style"]}\" tabindex=\"{$element["tab-index"]}\" goto=\"{$element["goto"]}\">{$element["text"]}</a>";
            }

            $menu .= "</div>";

            $menu .= "<div class=\"fra-collection-menu fra-collection-menu-mobile {$this->class}\" id=\"fra-menu-mobile-{$this->id}\" style=\"{$this->style}\">";

            foreach ($this->elements as $element) {
                if (!isset($element["class"])) {
                    $element["class"] = "";
                }
                if (!isset($element["id"])) {
                    $element["id"] = "";
                }
                if (!isset($element["style"])) {
                    $element["style"] = "";
                }
                if (!isset($element["goto"])) {
                    $element["goto"] = "";
                }
                $menu .= "<a href=\"{$element["url"]}\" class=\"{$element["class"]}\" id=\"{$element["id"]}\" style=\"{$element["style"]}\" tabindex=\"{$element["tab-index"]}\" goto=\"{$element["goto"]}\">{$element["text"]}</a>";
            }

            $menu .= "</div>";

            echo($menu);
        }

    }
}
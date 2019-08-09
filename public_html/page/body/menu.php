<link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/css/menu.css"/>
<script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"]); ?>/js/fra-menu.js"></script>

<?php
(new FrancescoSorge\PHP\Menu("menu", "menu", "text-align: center", [
    [
        "url" => "#",
        "text" => "<span class='oi oi-menu'></span><span class='menu-mobile-text' style='margin-left: 10px'>{$this->getVariables("FraLanguage")->get("menu-close-mobile")}</span>",
        "class" => "mobile-menu mobile",
        "tab-index" => 9,
    ],
    [
        "url" => CONFIG_SITE["baseURL"],
        "text" => "<img src=\"upload/logo.png\" style=\"float: left; max-width: 24px; margin-right: 10px\">".CONFIG_SITE["title"],
        "class" => "title",
        "style" => "font-weight: bold; display: inline-block; text-transform: uppercase",
        "tab-index" => 1,
    ],
    [
        "url" => CONFIG_SITE["baseURL"] . "/overview",
        "text" => "<span class=\"\">{$this->getVariables("FraLanguage")->get("overview")}</span>",
        "class" => "pc",
        "tab-index" => 2,
    ],
    [
        "url" => CONFIG_SITE["baseURL"] . "/features",
        "text" => "<span class=\"\">{$this->getVariables("FraLanguage")->get("features")}</span>",
        "class" => "pc",
        "tab-index" => 3,
    ],
    [
        "url" => CONFIG_SITE["baseURL"] . "/my/",
        "text" => "<span class=\"\">" . ($this->getVariables("fraUserManagement")->isLogged() ? $this->getVariables("FraLanguage")->get("hello") . ", " . $this->getVariables("fraUserManagement")->getCurrentUserInfo(["name"], ["users_expanded"])->name : $this->getVariables("FraLanguage")->get("account")) . "</span>",
        "class" => "pc",
        "tab-index" => 4,
    ],
    [
        "url" => CONFIG_SITE["baseURL"] . "/language.php?redirect=" . $this->getVariables("FraBasic")->getURL(),
        "text" => "<img src='" . CONFIG_SITE["baseURL"] . "/language/icon/{$this->getVariables("FraLanguage")->getLanguage()}.png' style='width: 46px' /><span class='menu-mobile-text' style='margin-left: 10px'>{$this->getVariables("FraLanguage")->get("change-language")}</span>",
        "class" => "pc",
        "tab-index" => 5,
    ],
]))->showMenu();
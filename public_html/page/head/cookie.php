<?php
require_once __DIR__ . "/../../model/language.php";
$cookie = new FrancescoSorge\PHP\Language("fra-cookie-bar-{$this->getVariables("FraLanguage")->getLanguage()}");
?>
<title><?php echo($cookie->get("cookie-bar")); ?> - <?php echo(CONFIG_SITE["title"]); ?></title>

<script>
    function deleteCookieBar() {
        const cookie = new FraCookie();
        cookie.delete("cookie-bar");
        location.reload();
    }
</script>
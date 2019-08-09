<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">

    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">
    <meta name="msapplication-TileImage" content="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png">

    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/jquery-ui.css"); ?>"/>
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/bootstrap.min.css"); ?>"/>
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/open-iconic-bootstrap.min.css"); ?>"/>
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/lightschool.css"); ?>"/>
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/placeholder-loading.min.css"); ?>"/>

    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/jquery-3.3.1.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/jquery-ui.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/bootstrap.min.js"); ?>"></script>

    <!-- ConfigSite -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"]); ?>/config/site.js"></script>

    <!-- FraBasic -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-basic.js"); ?>"></script>

    <!-- FraAjax -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-ajax.js"); ?>"></script>

    <!-- FraForm -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-form.js"); ?>"></script>

    <!-- FraCookie -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-cookie.js"); ?>"></script>

    <!-- FraJson -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-json.js"); ?>"></script>

    <!-- FraWindows -->
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/fra-windows.css"); ?>"/>
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-windows.js"); ?>"></script>

    <!-- FraContextMenu -->
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/fra-context-menu.css"); ?>"/>
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-context-menu.js"); ?>"></script>

    <!-- FraNotifications -->
    <link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/fra-notifications.css"); ?>"/>
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-notifications.js"); ?>"></script>

    <!-- FraCookieBar -->
    <script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"] . "/js/fra-cookie-bar.js"); ?>"></script>

    <meta name="theme-color" content="#1e6ad3">
    <meta name="msapplication-navbutton-color" content="#1e6ad3">
    <!--<meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">-->

    <?php new FrancescoSorge\PHP\Page($headPage, $variablesToPages, "head"); ?>
</head>
<body>
<?php new FrancescoSorge\PHP\Page($bodyPage, $variablesToPages, "body"); ?>
</body>
<script>

</script>
</html>
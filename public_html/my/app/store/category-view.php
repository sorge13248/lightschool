<?php
$database->setTableDotField(false);
$category = $database->query("SELECT icon, name_it FROM app_category WHERE name = :name LIMIT 1", [
    [
        "name" => "name",
        "value" => $_GET["name"],
        "type" => \PDO::PARAM_STR,
    ],
], "fetchAll");

$exists = isset($category[0]);
?>

<div class="container content-my store category">
    <div style="margin: 0 auto; width: 100%; max-width: 1200px">
        <?php if ($exists) { ?>
            <h1><?php echo(htmlspecialchars($category[0]['name_it'])); ?></h1>
            <?php
            $apps = $database->query("SELECT unique_name AS id, icon, IF(name_it IS NOT NULL, name_it, name_en) AS name, IF(author IS NOT NULL, username, null) AS author FROM app_catalog LEFT OUTER JOIN users ON app_catalog.author = users.id WHERE category = :name AND visible = 1 ORDER BY name_it", [
                [
                    "name" => "name",
                    "value" => $_GET["name"],
                    "type" => \PDO::PARAM_STR,
                ],
            ], "fetchAll");

            foreach ($apps as &$item) {
                if ($item["author"] === null) {
                    $item["author"] = "LightSchool";
                }
                $icon = $item['icon'] ? "<img src=\"" . CONFIG_SITE["baseURL"] . "/my/app/" . htmlspecialchars($item['id']) . "/icon/black/icon.png\" class=\"change-this-img\" style=\"float: left; width: 54px\" />" : "";
                echo("<a href=\"" . CONFIG_SITE["baseURL"] . "/my/app/store/d/{$item['id']}/\" class='icon img-change-to-white box-shadow-1-all accent-bkg-all-darker' style='width: 100%; max-width: 250px' title=\"" . htmlspecialchars($item['name']) . "\">{$icon}<span style='display: block; font-size: 1.2em' class='text-ellipsis'>{$item['name']}</span><i>" . htmlspecialchars($item["author"]) . "</i></a>");
            }

            if (count($apps) === 0) {
                echo("<p style='color: gray'>Nessuna app trovata in questa categoria.</p>");
            }
            ?>
        <?php } else { ?>
            <p class="alert alert-danger">Categoria non valida.</p>
        <?php } ?>
    </div>
</div>
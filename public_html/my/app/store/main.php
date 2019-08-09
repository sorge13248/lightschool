<div class="container content-my store">
    <div style="margin: 0 auto; width: 100%; max-width: 1200px">
        <h1>Categorie</h1>
        <?php
        $database->setTableDotField(false);
        $category = $database->query("SELECT name, name_it FROM app_category WHERE visible = 1 AND sub IS NULL ORDER BY name_it", [], "fetchAll");

        $i = 0;
        foreach ($category as $item) {
            echo("<a href=\"c/{$item['name']}/\" class='icon img-change-to-white box-shadow-1-all accent-bkg-all-darker' style='width: 100%; max-width: 250px' title=\"" . htmlspecialchars($item['name_it']) . "\"><span style='display: block; font-size: 1.2em' class='text-ellipsis'>{$item['name_it']}</span></a>");
            $i++;
        }

        if ($i === 0) {
            echo("<p style='color: gray'>Nessuna categoria trovata.</p>");
        }
        ?>
    </div>
</div>
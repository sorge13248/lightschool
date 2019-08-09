<style type="text/css">
    form div.row div {
        padding: 10px;
    }

    form input, form select {
        width: 100%;
    }

    form ul {
        list-style-type: none;
        padding-inline-start: 0;
    }
</style>

<script type="text/javascript">
    const preview = (accent = null, bkg_color = null) => {
        accent = accent === null ? $("#accent").val() : accent;
        bkg_color = bkg_color === null ? $("#bkg-color").val() : bkg_color;

        // bkg_color
        $(".wallpaper-opacity").css("background-color", ("rgba(" + hexToRgb(bkg_color).r + ", " + hexToRgb(bkg_color).g + ", " + hexToRgb(bkg_color).b + ", " + $("#bkg-opacity").val() / 100 + ")"));
    };

    $(document).ready(function () {
        $("#taskbar_size option[value='<?php echo((int)$this->getVariables("currentUser")->taskbar_size); ?>']").attr("selected", "selected");

        $("#reorder-taskbar").sortable();

        const accent = "<?php echo(htmlentities($this->getVariables("currentUser")->accent["base"])); ?>";
        setFraColorPicker("accent", accent, false);

        const bkg_color = "<?php echo(htmlentities($this->getVariables("currentUser")->wallpaper["color"])); ?>".split(", ");
        setFraColorPicker("bkg-color", bkg_color);
    });

    $(document).on("submit", ".form-customize", function (e) {
        e.preventDefault();
        const form = new FraForm($(this), "Caricamento...");
        form.lock();

        let data = form.getDomElements("input, select", "string");

        let taskbar = [];
        $("#reorder-taskbar li").each(function () {
            taskbar.push($(this).attr("app_id"));
        });

        data += "&taskbar=" + taskbar.join(",");

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            form.unlock();
            if (result["response"] === "success") {
                $(".form-customize .response").show().html(result["text"]).removeClass("alert-danger").addClass("alert alert-success");
            } else {
                $(".form-customize .response").show().html(result["text"]).removeClass("alert-success").addClass("alert alert-danger");
            }
        });
    });

    $(document).on("click", "li .remove-app", function (e) {
        e.preventDefault();
        $(this).closest("li").remove();
    });

    $(document).on("input", "#bkg-opacity", function () {
        preview();
    });

    $(document).on("click", ".remove-profile-picture", function (e) {
        e.preventDefault();

        $('#pp-id').val('');
        $('.pp .button, .pp img').remove();
    });

    $(document).on("click", ".remove-wallpaper", function (e) {
        e.preventDefault();

        $('#bkg-id').val('');
        $('.bkg img, .bkg .button, .bkg-opacity, .bkg-color').remove();
        $('#background-styles').remove();
    });

    document.addEventListener("bkg-color-color-picked", function (e) {
        preview(null, $("#bkg-color").val());
    });
</script>

<div class="container content-my settings-app">
    <form method="post" action="controller.php?type=customize" class="form-customize" style="padding: 25px">
        <div style="max-width: 1300px; margin: 0 auto">
            <div class="row">
                <div class="col-md-6">
                    <h2>Personalizza</h2>
                    <div>
                        <label for="accent">Colore preferito</label><br/>
                        <input type="text" id="accent" name="accent" placeholder="Colore preferito"
                               value="<?php echo($this->getVariables("currentUser")->accent["base"]); ?>"
                               fra-color-picker="1" class="box-shadow-1-all" maxlength="7"/>
                        <small>Il colore preferito verr&agrave; applicato in moltissime parti di LightSchool come la
                            taskbar, i bottoni, le finestre, le icone e molto altro</small>
                    </div>
                    <div class="pp">
                        <label for="profile_picture">Foto profilo</label><br/>
                        <img src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                             style="width: 120px; height: 120px; border-radius: 50%; margin-right: 10px; margin-top: 16px; float: left"/>
                        <span>Passa a "Gestore File" per scegliere un'immagine da usare come foto profilo.</span><br/>
                        <input type="hidden" id="pp-id" name="pp-id"
                               value="<?php echo($this->getVariables("currentUser")->profile_picture["id"]); ?>"
                               style="display: none" class="box-shadow-1-all"/>
                        <?php if ($this->getVariables("currentUser")->wallpaper !== null) { ?>
                            <a href="#"
                               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker remove-profile-picture"
                               style="float: right;">Rimuovi foto profilo</a>
                        <?php } ?>
                    </div>
                    <div style="clear: both"></div>
                    <div class="bkg">
                        <label for="wallpaper">Sfondo</label><br/>
                        <?php if ($this->getVariables("currentUser")->wallpaper !== null) { ?>
                            <img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/provide-file.php?id=<?php echo($this->getVariables("currentUser")->wallpaper["id"]); ?>"
                                 style="width: 100%; max-width: 128px; float: left; margin-right: 10px"/>
                        <?php } ?>
                        <span>Passa a "Gestore File" per scegliere un'immagine da usare come sfondo.</span><br/>
                        <?php if ($this->getVariables("currentUser")->wallpaper !== null) { ?>
                            <a href="#"
                               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker remove-wallpaper"
                               style="float: right;">Rimuovi sfondo</a>
                        <?php } ?>
                        <input type="hidden" id="bkg-id" name="bkg-id"
                               value="<?php echo($this->getVariables("currentUser")->wallpaper["id"]); ?>"
                               style="display: none" class="box-shadow-1-all"/>
                    </div>
                    <div style="clear: both"></div>
                    <?php if ($this->getVariables("currentUser")->wallpaper !== null) { ?>
                        <div class="bkg-opacity">
                            <label for="bkg-opacity">Opacit&agrave; sfondo</label><br/>
                            <input type="range" id="bkg-opacity" name="bkg-opacity" min="0" max="100"
                                   value="<?php echo(((float)$this->getVariables("currentUser")->wallpaper["opacity"]) * 100); ?>"
                                   class="box-shadow-1-all">
                        </div>
                        <div class="bkg-color">
                            <label for="bkg-color">Colore sfondo</label><br/>
                            <input type="text" id="bkg-color" name="bkg-color" placeholder="Colore" fra-color-picker="1"
                                   class="box-shadow-1-all" maxlength="7"/>
                        </div>
                    <?php } ?>
                </div>
                <div class="col-md-6">
                    <h2>Taskbar</h2>
                    <div>
                        <label for="taskbar_size">Dimensione</label><br/>
                        <select id="taskbar_size" name="taskbar_size" class="box-shadow-1-all">
                            <option value="2">Grande</option>
                            <option value="0">Normale</option>
                            <option value="1">Piccola</option>
                        </select>
                        <small>Tre grandezze per la tua taskbar, per soddisfare ogni desiderio di
                            personalizzazione</small>
                    </div>
                    <div>
                        <label for="taskbar_order">Riordina</label>
                        <p>Clicca e trascina un'app per riordinarla nella taskbar. Clicca sulla <img
                                    src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/cross.png"
                                    class="img-change-to-white" style="width: 16px; height: 16px"/> per rimuovere l'app
                            dalla taskbar.</p>
                        <?php if ($this->getVariables("currentUser")->taskbar["interpreted"] !== null && count($this->getVariables("currentUser")->taskbar["interpreted"]) > 0) { ?>
                            <ul id="reorder-taskbar">
                                <?php foreach ($this->getVariables("currentUser")->taskbar["interpreted"] as $app) { ?>
                                    <li href="#"
                                        class="list no-transition accent-all box-shadow-1-all img-change-to-white"
                                        style="display: block" app_id="<?php echo($app["id"]); ?>"><img
                                                src="<?php echo($app["icon-black"]); ?>" class="change-this-img"
                                                style="width: 24px; height: 24px; float: left; margin-right: 10px"/><?php echo($app["name"]); ?>
                                        <a href="#delete" title="Rimuovi dalla taskbar" class="remove-app"><img
                                                    src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/cross.png"
                                                    class="img-change-to-white"
                                                    style="width: 16px; height: 20px; float: right; margin-left: 10px; padding-top: 3px"/></a>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } else { ?>
                            <p class="alert alert-warning">Nessun app nella taskbar. Passa a "Impostazioni > App" per
                                aggiungere app alla taskbar.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <input type="submit" value="Salva" class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">
            <div class="response" style="margin-top: 10px"></div>
        </div>
    </form>
</div>

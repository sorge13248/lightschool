<?php if (isset($_GET["selector"]) && isset($_GET["token"])) {
    $response = $this->getVariables("fraUserManagement")->recoverCanSetNewPassword($_GET["selector"], $_GET["token"], "recoverSetNewPassword");
} else { ?>
    <script type="text/javascript">
        $(document).on("submit", ".form-password", function (e) {
            e.preventDefault();
            const form = new FraForm($(this));
            form.lock();

            const data = form.getDomElements("input", "string");
            const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

            ajax.execute(function (result) {
                console.log(result);
                if (result["response"] === "success") {
                    $(".form-content").html("<div class='alert alert-success'><h1>" + result["text"]["header"] + "</h1><p>" + result["text"]["text"] + "</p></div>");
                } else {
                    $(".form-password .response").html(result["text"]).addClass("alert alert-danger").slideDown(200);
                    form.unlock();
                    $(".form-password #username").focus();
                }
            });
        });
    </script>
<?php } ?>

<div class="welcome center-content background-image login password"
     style="background-image: url('<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/background.png')">
        <span>
            <div class="content">
                <h1 style="color: #004A7F"><img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png"
                                                style="width: 64px; height: 64px; margin-right: 10px"/>LightSchool</h1>
                <br/>
                <div class="form-content">
                    <?php if (isset($_GET["selector"]) && isset($_GET["token"])) {
                        if ($response["response"] === "success") {
                            ?>
                            <div class="alert alert-success">
                                Password reimpostata con successo. La tua nuova password &egrave;:<br/>
                                <code><?php echo($response["text"]["password"]); ?></code><br/><br/>
                                <a href="index.php" class="button">Accedi</a>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                Impossibile reimpostare la password. Controlla che l'URL sia giusto o ripeti la procedura da capo.
                            </div>
                            <?php
                        }
                        ?>
                    <?php } else { ?>
                        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/password.php"
                              class="form-password">
                            <p>Per effettuare il recupero password, inserisci il nome utente con cui ti sei registrato</p>
                            <div class="response" style="display: none"></div>
                            <input type="text" id="username" name="username" placeholder="Nome utente"
                                   style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"/><br/>
                            <input type="submit" value="Recupera"/>
                        </form>
                    <?php } ?>
                    <br/>
                    <small><a href="<?php echo(CONFIG_SITE["baseURL"]); ?>">Sito web di LightSchool</a></small>
                </div>
            </div>
        </span>
</div>
<style type="text/css">
    .left {
        margin-right: 0;
        padding-right: 0;
    }

    .right {
        margin-left: 0;
        padding-left: 0;
    }

    input:not([type=submit]) {
        margin-left: 0;
        margin-right: 0;
    }
</style>

<script type="text/javascript">
    $(document).on("submit", ".form-register", function (e) {
        e.preventDefault();
        const form = new FraForm($(this), "Caricamento...");
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".form-content").html("<div class='alert alert-success'><h1>" + result["text"]["header"] + "</h1><p>" + result["text"]["text"] + "</p></div>");
            } else {
                $(".form-register .response").html(result["text"]).addClass("alert alert-danger").slideDown(200);
                form.unlock();
                $(".form-register #name").focus();
            }
        });
    });
</script>

<div class="welcome center-content background-image login register"
     style="background-image: url('<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/background.png')">
        <span>
            <div class="content">
                <h1 style="color: #004A7F"><img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png"
                                                style="width: 64px; height: 64px; margin-right: 10px"/>LightSchool</h1>
                <br/>
                <div class="form-content">
                    <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/register.php"
                          class="form-register">
                        <p>Registrati gratuitamente per utilizzare tutti gli strumenti didattici messi a disposizione</p><br/>
                        <input type="text" id="name" name="name" placeholder="Nome"
                               style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"/>
                        <input type="text" id="surname" name="surname" placeholder="Cognome" style="border-radius: 0"/>
                        <input type="text" id="username" name="username"
                               placeholder="Nome utente (senza spazi o caratteri speciali)" style="border-radius: 0"/>
                        <input type="email" id="email" name="email" placeholder="Indirizzo e-mail"
                               style="border-radius: 0"/>
                        <input type="password" id="password" name="password" placeholder="Password"
                               style="border-radius: 0"/>
                        <input type="password" id="password-2" name="password-2" placeholder="Conferma password"
                               style="border-radius: 0"/>
                        <input type="submit" value="Registrati"/>
                        <div class="response" style="display: none; margin-top: 10px; margin-bottom: 0"></div>
                        <p>Registrandoti, accetti i <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/tos">termini del servizio</a> e l'<a
                                    href="<?php echo(CONFIG_SITE["baseURL"]); ?>/privacy">informativa sulla privacy</a>.</p>
                    </form>
                    <br/>
                    <small><a href="<?php echo(CONFIG_SITE["baseURL"]); ?>">Sito web di LightSchool</a></small>
                </div>
            </div>
        </span>
</div>
<script type="text/javascript">
    $(document).on("submit", ".form-login", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".form-content").html("<div class='alert alert-success'>" + result["text"] + "</div>");
                location.reload();
            } else if (result["response"] === "2fa") {
                $(".form-login").hide();
                $(".form-login-2fa").show();
                $(".form-login-2fa #token").focus();
                $(".form-content #register").remove();
                $(".form-content #recover-pwd").html("OTP perso").attr("href", $(".form-content #recover-pwd").attr("href").replace("password", "otp"));
            } else {
                $(".form-login .response").html(result["text"]).addClass("alert alert-danger").slideDown(200);
                form.unlock();
                $(".form-login #password").focus();
            }
        });
    });

    $(document).on("submit", ".form-login-2fa", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".form-content").html("<div class='alert alert-success'>" + result["text"] + "</div>");
                location.reload();
            } else {
                $(".form-login-2fa .response").html(result["text"]).addClass("alert alert-danger").slideDown(200);
                form.unlock();
                $(".form-login-2fa #token").focus();
            }
        });
    });
</script>

<div class="welcome center-content background-image login"
     style="background-image: url('<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/background.png')">
        <span>
            <div class="content">
                <h1 style="color: #004A7F"><img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png"
                                                style="width: 64px; height: 64px; margin-right: 10px"/>LightSchool</h1>
                <br/>
                <div class="form-content">
                    <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/login.php"
                          class="form-login">
                        <div class="response" style="display: none"></div>
                        <input type="text" id="username" name="username" placeholder="Nome utente"/>
                        <input type="password" id="password" name="password" placeholder="Password"/><br/>
                        <input type="submit" value="Accedi"/>
                    </form>
                    <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/login.php?2fa=true"
                          class="form-login-2fa" style="display: none">
                        <div class="response" style="display: none"></div>
                        <input type="number" id="token" name="token" placeholder="OTP" maxlength="6"/>
                        <input type="submit" value="Accedi"/>
                    </form>
                    <br/>
                    <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/register" class="button"
                       id="register">Registrati</a> <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/password"
                                                       class="button" id="recover-pwd">Password dimenticata</a>
                    <br/><br/>
                    <small><a href="<?php echo(CONFIG_SITE["baseURL"]); ?>">Sito web di LightSchool</a></small>
                </div>
            </div>
        </span>
</div>
<style type="text/css">
    .big {
        font-size: 1.5em;
        font-weight: bold;
    }
</style>

<?php
$user = $this->getVariables("fraUserManagement")->getCurrentUserInfo(["twofa", "name", "surname"], ["users_expanded"]);

if ($user->twofa === null) {
    $tfa = new RobThree\Auth\TwoFactorAuth('LightSchool');
    $_SESSION["secret"] = $tfa->createSecret();
    ?>
    <script type="text/javascript">
        $(document).on("submit", ".activate-2fa", function (e) {
            e.preventDefault();
            const form = new FraForm($(this));
            form.lock();

            const data = form.getDomElements("input", "string");
            const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

            ajax.execute(function (result) {
                if (result["response"] === "success") {
                    $(".configure-2fa").html("<div class='alert alert-success'><h1>" + result["header"] + "</h1>" + result["text"] + "</div>");
                } else {
                    $(".activate-2fa .response").show().html(result["text"]).addClass("alert alert-danger");
                    form.unlock();
                }
            });
        });
    </script>
    <?php
} else {
    ?>
    <script type="text/javascript">
        document.addEventListener("fra-windows-deactivate-2fa-close-event", function (e) {
            $(".menu-my[dynamic-hide='1']").fadeIn(200).removeAttr("dynamic-hide");
        });

        $(document).on("click", "a.deactivate-2fa", function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!FraWindows.windowExists("deactivate-2fa")) {
                const confirmWindow = new FraWindows("deactivate-2fa", "Disattiva Autenticazione a 2 Fattori", "Caricamento...");
                confirmWindow.setTitlebarPadding(10, 0, 10, 0);
                confirmWindow.setContentPadding(20);
                confirmWindow.setSize("100%");
                confirmWindow.setProperty("max-width", "522px");
                confirmWindow.setProperty("max-height", "100vh");
                confirmWindow.setControl("close");
                confirmWindow.setDraggable();
                confirmWindow.setOverlay();

                const confirmContent = $(".grab form.deactivate-2fa").wrap('<p/>').parent().html();
                confirmWindow.setContent(confirmContent);

                confirmWindow.setPosition();
                confirmWindow.show("fadeIn", 300);

                $("form.deactivate-2fa input[type='password']").focus();

                $(".menu-my:visible").fadeOut(200).attr("dynamic-hide", "1");
            } else {
                FraWindows.getWindow("deactivate-2fa").bringToFront();
            }
        });

        $(document).on("submit", "form.deactivate-2fa", function (e) {
            e.preventDefault();
            const form = new FraForm($(this));
            form.lock();

            const data = form.getDomElements("input", "string");
            const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

            ajax.execute(function (result) {
                if (result["response"] === "success") {
                    $(".configure-2fa").html("<div class='alert alert-success'>" + result["text"] + "</div>");
                    FraWindows.getWindow("deactivate-2fa").close();
                } else {
                    $(".fra-windows form.deactivate-2fa .response").addClass("alert alert-danger").html(result["text"]).show();
                    FraWindows.getWindow("deactivate-2fa").setPosition();
                    $(".deactivate-2fa input[type='password']").focus();
                }
            });

            form.unlock();
        });
    </script>
    <?php
}
?>

<div class="container content-my settings-app">
    <div class="grab" style="display: none">
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/2fa.php?type=deactivate"
              class="deactivate-2fa">
            <p>Per motivi di sicurezza, ti chiediamo la tua password prima di disattivare l'Autenticazione a 2 Fattori.
                Ricordati che potrai sempre riattivarla dalle impostazioni di sicurezza del tuo account, tuttavia dovrai
                rifare la procedura da capo.</p>
            <input type="password" id="password" name="password" placeholder="Password" maxlength="128"/>
            <input type="submit" value="Disattiva" class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                   style="float: right"/>
            <div class="response" style="margin-top: 10px"></div>
        </form>
    </div>
    <div style="max-width: 1300px; margin: 0 auto" class="configure-2fa">
        <?php if ($user->twofa === null) { ?>
            <p>Segui questi semplici passaggi per configurare l'Autenticazione a 2 Fattori sul tuo account
                LightSchool.</p>
            <div class="row">
                <div class="col-md-4">
                    <p class="big">1. Scarica l'app</p>
                    <p>Esistono molte app gratuite per Android ed iOS. Scaricane una per poter proseguire.</p>
                    <p>LightSchool raccomanda</p>
                    <img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/authy.png" style="max-width: 250px"/><br/>
                    <a href="https://play.google.com/store/apps/details?id=com.authy.authy" target="_blank"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/play-store/en.svg"
                                style="height: 40px; margin: 10px"/></a>
                    <a href="https://itunes.apple.com/app/authy/id494168017" target="_blank"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/app-store/it.svg"
                                style="height: 40px; margin: 10px"/></a>

                    <br/>
                    <p>Oppure ci sono app alternative</p>
                    <ul>
                        <li>Google Authenticator: <a
                                    href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                                    target="_blank">Play Store</a> &bull; <a
                                    href="https://itunes.apple.com/app/google-authenticator/id388497605"
                                    target="_blank">App Store</a></li>
                        <li>Microsoft Authenticator: <a
                                    href="https://play.google.com/store/apps/details?id=com.azure.authenticator"
                                    target="_blank">Play Store</a> &bull; <a
                                    href="https://itunes.apple.com/app/microsoft-authenticator/id983156458"
                                    target="_blank">App Store</a></li>
                        <li>FreeOTP Authenticator: <a
                                    href="https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp"
                                    target="_blank">Play Store</a> &bull; <a
                                    href="https://itunes.apple.com/app/freeotp-authenticator/id872559395"
                                    target="_blank">App Store</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <p class="big">2. Scannerizza il QR Code</p>
                    <p>Apri l'app che hai scelto e aggiungi un nuovo account. Poi scannerizza il QR Code <b>oppure</b>
                        scrivi a mano il codice.</p>
                    <ul>
                        <li>
                            Scannerizza il QR code
                            <p>
                                <img src="<?php echo $tfa->getQRCodeImageAsDataUri("LightSchool - " . htmlspecialchars($user->name) . " " . htmlspecialchars($user->surname), $_SESSION["secret"]); ?>">
                            </p>
                        </li>
                        <li>
                            Inserisci il codice esattamente come riportato qui sotto<br/>
                            <code style="background-color: #F6F6F6; border-radius: 10px; padding: 10px; margin: 5px; display: inline-block; margin-left: 0"><?php echo($_SESSION["secret"]); ?></code>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <p class="big">3. Verifica</p>
                    <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/2fa.php?type=activate"
                          class="activate-2fa">
                        <p>Inserisci la tua password</p>
                        <input type="password" id="password" name="password" placeholder="Password" maxlength="128"/>
                        <p>Arrivati fin qui, l'app dovrebbe mostrarti un codice temporaneo e monouso da 6 cifre.
                            Inserisci il codice per verificare che la procedura di configurazione sia andata a buon
                            fine.</p>
                        <input type="number" id="token" name="token" placeholder="Token" maxlength="12"/>
                        <input type="submit" value="Attiva"
                               class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/>
                        <div class="response" style="margin-top: 10px"></div>
                    </form>
                </div>
            </div>
        <?php } else { ?>
            <p class="alert alert-success">Stai gi&agrave; utilizzando l'Autenticazione a 2 Fattori.
                Congratulazioni!</p>
            <p>Desideri disattivare l'Autenticazione a 2 Fattori su questo account?</p>
            <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker deactivate-2fa">Disattiva</a>
        <?php } ?>
    </div>
</div>

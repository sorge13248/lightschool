<?php
$user = $this->getVariables("fraUserManagement")->getCurrentUserInfo(["privacy_search_visible", "privacy_show_email", "privacy_show_username", "privacy_send_messages", "privacy_share_documents", "privacy_ms_office", "twofa", "password_last_change"], ["all_users"]);
?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#search_visible option[value='<?php echo($user->privacy_search_visible); ?>']").attr("selected", "selected");
        $("#show_email option[value='<?php echo($user->privacy_show_email); ?>']").attr("selected", "selected");
        $("#show_username option[value='<?php echo($user->privacy_show_username); ?>']").attr("selected", "selected");
        $("#send_messages option[value='<?php echo($user->privacy_send_messages); ?>']").attr("selected", "selected");
        $("#share_documents option[value='<?php echo($user->privacy_share_documents); ?>']").attr("selected", "selected");
        $("#ms_office option[value='<?php echo($user->privacy_ms_office); ?>']").attr("selected", "selected");
    });

    $(document).on("submit", ".form-privacy", function (e) {
        e.preventDefault();
        const form = new FraForm($(this), "Caricamento...");
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());

        ajax.execute(function (result) {
            form.unlock();
            if (result["response"] === "success") {
                $(".form-privacy .response").show().html(result["text"]).removeClass("alert-danger").addClass("alert alert-success");
            } else {
                $(".form-privacy .response").show().html(result["text"]).removeClass("alert-success").addClass("alert alert-danger");
            }
        });
    });
</script>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto">
        <div class="row">
            <div class="col-md-4">
                <h3>Chiavi crittografiche RSA</h3>
                <?php
                $keyring = new \FrancescoSorge\PHP\Keyring();
                try {
                    $keys = ["public" => $keyring->get("public"), "private" => $keyring->get("private")];
                    $rsa = new \phpseclib\Crypt\RSA();
                    $rsa->setHash("sha512");
                    $rsa->loadKey($keys["public"]);
                    $plainText = \FrancescoSorge\PHP\Basic::generateRandomID(1);
                    $encrypted = $rsa->encrypt($plainText);

                    $rsa->loadKey($keys["private"]);
                    if ($plainText === $rsa->decrypt($encrypted)) {
                        echo("<div class='alert alert-success'><b>Chiavi verificate</b><br/>L'integrit&agrave; del sistema &egrave; stata verificata correttamente</div>");
                    }
                } catch (\Exception $e) {
                    echo("<div class='alert alert-danger'><b>Chiavi danneggiate</b><br/>Contattare il supporto tecnico.</div>");
                }
                ?>
                <h3>Autenticazione a 2 Fattori</h3>
                <?php
                if ($user->twofa !== null) {
                    ?>
                    <p>Gi&agrave; attiva su questo account.</p>
                    <a href="2fa"
                       class="button  accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Gestisci</a>
                <?php } else { ?>
                    <p>Rendi il tuo account più sicuro chiedendoti, ad ogni accesso, un codice monouso.<br/>Ci vorranno
                        un paio di minuti.</p>
                    <a href="2fa"
                       class="button  accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Configura</a>
                <?php } ?>
                <br/>
                <h3>Password</h3>
                <?php
                if ($user->password_last_change === null) {
                    ?>
                    <p>Non hai mai cambiato la tua password.</p>
                <?php } else { ?>
                    <p>Password cambiata
                        il <?php echo(\FrancescoSorge\PHP\Basic::timestampToHuman($user->password_last_change)); ?></p>
                <?php } ?>
                <a href="password" class="button  accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Cambia
                    password</a>
            </div>
            <div class="col-md-8">
                <h3>Privacy</h3>
                <p>Decidi chi pu&ograve; inviarti messaggi e condividerti documenti. Scegli quali informazioni personali
                    mostrare nel tuo profilo pubblico oppure puoi scegliere di non comparire nei risultati di
                    ricerca.</p>
                <br/>
                <form method="post"
                      action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/settings/controller.php?type=privacy"
                      class="form-privacy">
                    <h4>Social</h4>
                    <p>Queste opzioni si riferiscono all'app "Social", il posto dove puoi trovare ed interagire con
                        altri utenti. Nell'app "Social" &egrave; possibile cercare gli utenti per nome, cognome, nome
                        utente ed indirizzo e-mail.</p>
                    <label for="search_visible">Visibile nei risultati di ricerca</label>
                    <select id="search_visible" name="search_visible" class="box-shadow-1-all">
                        <option value="1">Sì</option>
                        <option value="0">No</option>
                    </select>
                    <p class="small">Disabilitanto questa opzione, gli altri utenti non potranno cercarti per nome e
                        cognome.</p>
                    <br/>
                    <label for="show_email">Mostra il tuo indirizzo e-mail</label>
                    <select id="show_email" name="show_email" class="box-shadow-1-all">
                        <option value="1">Sì</option>
                        <option value="0">No</option>
                    </select>
                    <p class="small">Disabilitanto questa opzione, gli altri utenti non potranno cercarti per indirizzo
                        e-mail, n&eacute; apparir&agrave; nella pagina del tuo profilo.</p>
                    <br/>
                    <label for="show_username">Mostra il tuo nome utente</label>
                    <select id="show_username" name="show_username" class="box-shadow-1-all">
                        <option value="1">Sì</option>
                        <option value="0">No</option>
                    </select>
                    <p class="small">Disabilitanto questa opzione, gli altri utenti non potranno cercarti per nome
                        utente, n&eacute; apparir&agrave; nella pagina del tuo profilo.</p>
                    <br/>
                    <p>Disabilitando tutte le opzioni, il tuo profilo non verr&agrave; mai mostrato all'interno dell'app
                        "Social".</p>
                    <hr/>
                    <h4>Interazioni con altri utenti</h4>
                    <p>Scegli chi pu&ograve; inviarti messaggi e condividerti documenti.</p>
                    <label for="send_messages">Possono inviarmi messaggi</label>
                    <select id="send_messages" name="send_messages" class="box-shadow-1-all">
                        <option value="2">Tutti</option>
                        <option value="1">Solo utenti nei miei "Contatti"</option>
                        <option value="0">Nessuno</option>
                    </select>
                    <br/>
                    <br/>
                    <label for="share_documents">Possono condividermi documenti</label>
                    <select id="share_documents" name="share_documents" class="box-shadow-1-all">
                        <option value="2">Tutti</option>
                        <option value="1">Solo utenti nei miei "Contatti"</option>
                        <option value="0">Nessuno</option>
                    </select>
                    <hr/>
                    <h4>Servizi di terze parti</h4>
                    <p>Autorizza o nega l'utilizzo di servizi di terze parti da parte di LightSchool.</p>
                    <label for="ms_office">Microsoft Office Online</label>
                    <select id="ms_office" name="ms_office" class="box-shadow-1-all">
                        <option value="2">Consenti</option>
                        <option value="1">Chiedi ogni volta</option>
                        <option value="0">Nega</option>
                    </select>
                    <p class="small">Utilizza Microsoft Office Online, quando possibile, per visualizzare in anteprima i
                        file doc, xls, ppt, docx, xlsx e pptx.</p>
                    <p class="small">Si applicano le <a href="https://www.microsoft.com/it-IT/servicesagreement/"
                                                        target="_blank">condizioni</a> e l'<a
                                href="https://privacy.microsoft.com/it-it/privacystatement" target="_blank">Informativa
                            sulla privacy</a> di Microsoft.</p>
                    <hr/>
                    <input type="submit" value="Applica"
                           class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker" style="float: right"/>
                    <div style="clear: both"></div>
                    <div class="response" style="margin-top: 10px"></div>
                </form>
            </div>
        </div>
    </div>
</div>

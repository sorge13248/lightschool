<style type="text/css">
    label, input {
        max-width: 300px;
        width: 100%;
    }
</style>

<script type="text/javascript">
    $(document).on("submit", ".form-password", function (e) {
        e.preventDefault();
        const form = new FraForm($(this), "Caricamento...");
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());

        ajax.execute(function (result) {
            form.unlock();
            if (result["response"] === "success") {
                $(".form-password .response").show().html(result["text"]).removeClass("alert-danger").addClass("alert alert-success");
            } else {
                $(".form-password .response").show().html(result["text"]).removeClass("alert-success").addClass("alert alert-danger");
            }
        });
    });

    $(document).on("input", "input[type='checkbox'].show", function (e) {
        $("input#" + $(this).attr("input")).attr("type", ($(this).is(":checked") ? "text" : "password"));
    });
</script>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto" class="change-password">
        <h1>Cambia password</h1>
        <p>Inserisci la tua password attuale e due volte la password nuova. Dopo che avrai cambiato la password,
            invieremo una e-mail al tuo indirizzo di posta elettronica per comunicare il cambio password.</p>
        <hr/>
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/settings/controller.php?type=password"
              class="form-password">
            <label for="old">Password attuale</label><input type="password" id="old" name="old"
                                                            placeholder="Password attuale" tabindex="1"
                                                            class="box-shadow-1-all"/><label><input type="checkbox"
                                                                                                    class="show"
                                                                                                    input="old"/>Mostra</label><br/>
            <label for="new">Password nuova</label><input type="password" id="new" name="new"
                                                          placeholder="Password nuova" tabindex="2"
                                                          class="box-shadow-1-all"/><label><input type="checkbox"
                                                                                                  class="show"
                                                                                                  input="new"/>Mostra</label><br/>
            <label for="new-2">Ripeti la password nuova</label><input type="password" id="new-2" name="new-2"
                                                                      placeholder="Ripeti la password nuova"
                                                                      tabindex="3"
                                                                      class="box-shadow-1-all"/><label><input
                        type="checkbox" class="show" input="new-2"/>Mostra</label><br/>
            <br/>
            <label>&nbsp;</label><input type="submit" value="Cambia password"
                                        class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                                        tabindex="4"/>
            <div class="response" style="margin-top: 10px"></div>
        </form>
    </div>
</div>

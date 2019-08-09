<style type="text/css">
    form div.row div {
        padding: 10px;
    }

    form input {
        width: 100%;
    }
</style>

<script type="text/javascript">
    $(document).on("submit", ".form-account", function (e) {
        e.preventDefault();
        const form = new FraForm($(this), "Caricamento...");
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            form.unlock();
            if (result["response"] === "success") {
                $(".form-account .response").show().html(result["text"]).removeClass("alert-danger").addClass("alert alert-success");
            } else {
                $(".form-account .response").show().html(result["text"]).removeClass("alert-success").addClass("alert alert-danger");
            }
        });
    });
</script>

<div class="container content-my settings-app">
    <form method="post" action="controller.php?type=account" class="form-account" style="padding: 25px">
        <div style="max-width: 1300px; margin: 0 auto">
            <div class="row">
                <div class="col-md-2">
                    <img src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                         style="width: 120px; height: 120px; border-radius: 50%; margin-right: 50px; margin-top: 16px; float: left"/>
                </div>
                <div class="col-md-10">
                    <h1 style="text-align: left"><?php echo(htmlspecialchars($this->getVariables("currentUser")->name)); ?> <?php echo(htmlspecialchars($this->getVariables("currentUser")->surname)); ?></h1>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">Nome</label><br/>
                            <input type="text" id="name" name="name" placeholder="Nome"
                                   value="<?php echo($this->getVariables("currentUser")->name); ?>"
                                   class="box-shadow-1-all"/>
                        </div>
                        <div class="col-md-6">
                            <label for="name">Cognome</label><br/>
                            <input type="text" id="surname" name="surname" placeholder="Cognome"
                                   value="<?php echo($this->getVariables("currentUser")->surname); ?>"
                                   class="box-shadow-1-all"/>
                        </div>
                        <div class="col-md-6">
                            <label for="email">Indirizzo e-mail</label><br/>
                            <input type="email" id="email" name="email" placeholder="Indirizzo e-mail"
                                   value="<?php echo($this->getVariables("currentUser")->email); ?>"
                                   class="box-shadow-1-all"/>
                            <small>Se cambi indirizzo e-mail, invieremo una comunicazione al tuo vecchio indirizzo
                                e-mail. Dovrai inoltre confermare il nuovo indirizzo cliccando sul link presente nella
                                e-mail che ti invieremo.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="username">Nome utente</label><br/>
                            <input type="text" id="username" name="username" placeholder="Nome utente"
                                   value="<?php echo($this->getVariables("currentUser")->username); ?>"
                                   class="box-shadow-1-all"/>
                            <small>Il nome utente permette a chiunque di trovarti facilmente sulla piattaforma
                                LightSchool, senza fornire informazioni personali come ad esempio l'indirizzo
                                e-mail.</small>
                        </div>
                        <div class="col-md-12">
                            <input type="submit" value="Salva"
                                   class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">
                            <div class="response" style="margin-top: 10px"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

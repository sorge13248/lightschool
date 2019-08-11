<?php
    $style = $this->getVariables("fraUserManagement")->isLogged() ? ["col-md-6", "", ""] : ["col-md-12", "text-align: center", "display: none"];
?>

<style type="text/css">
    .your-files .icon {
        max-width: 200px;
    }
</style>

<div class="container content-my project">

    <div class="welcome-screen">
        <div class="row">
            <div class="<?php echo($style[0]); ?>" style="<?php echo($style[1]); ?>">
                <h2>Proiezione</h2>
                <p>Entrando in modalit&agrave; proiezione, verr&agrave; generato un codice LIM univoco che permetter&agrave; agli utenti di condividere i propri file.</p>
                <p>Questa funzione &egrave; utile per mostrare davanti alla classe i lavori degli studenti, come esercizi, temi e consegne di vario genere.</p>
                <p>&Egrave; anche possibile proiettare foto, file Office, file audio e video.</p>
                <br/>
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker enter-projection-mode">Entra in modalit&agrave; proiezione</a>
            </div>
            <div class="col-md-6" style="<?php echo($style[2]); ?>">
                <h2>File che stai proiettando</h2>
                <p>Puoi interrompere in qualsiasi momento la proiezione di un file.</p>
                <br/>
                <div class="your-files">
                    <p>Caricamento...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="projection-mode" style="display: none">
        <div class="row">
            <div class="col-md-0 sidebar" style="display: none; text-align: center">
                <h2>Codice LIM: <span></span></h2>
                <p>
                    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker small delete">Cambia codice LIM</a>
                    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker small refresh-files">Ricarica file</a>
                    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker small exit-projection-mode">Esci dalla modalit&agrave; presentazione</a>
                </p>
            </div>
            <div class="col-md-12 files">
                <div id="loading" style="text-align: center">
                    <p>Sto ottenendo il codice univoco...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/project/app.js"></script>
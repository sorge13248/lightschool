<?php
if (file_exists(__DIR__ . "/../config/site.php")) {
    header("location: ../");
}
$doInstall = true;
require_once __DIR__ . "/../etc/core.php";
?>
<title>Installazione</title>

<link rel="stylesheet" href="../css/bootstrap.min.css" />
<style type="text/css">
    * {
        font-family: "Arial", "Tahoma", sans-serif;
    }
    .content {
        margin: 0 auto;
        width: 100%;
        max-width: 1200px;
        padding: 20px;
    }
    label {
        display: inline-block;
        width: 100%;
        max-width: 150px;
        text-align: right;
    }
    input {
        padding: 10px;
        border: 1px solid lightgray;
        border-radius: 10px;
        margin: 10px;
        width: 100%;
        max-width: 300px;
    }
    .small {
        color: grey;
        font-size: 0.9em;
    }
    code {
        display: inline-block;
        padding: 5px 10px;
        color: blue;
        background-color: lightblue;
        border-radius: 10px;
    }
</style>

<div class="content">
    <h1>Installazione</h1>
    <p>Benvenuto nella procedura d'installazione di LightSchool.</p>
    <hr/>
    <form method="post" action="post">
        <h2>Generali</h2>
        <p>In questa fase, ti chiediamo dei dati semplici.</p>
        <label for="url">URL:</label><input type="url" id="url" name="url" placeholder="URL" value="<?php echo(\FrancescoSorge\PHP\Basic::checkHTTPS() ? "https" : "http"); ?>://<?php echo($_SERVER['SERVER_NAME']); ?>" required />
        <p class="small">L'URL &egrave; l'indirizzo web presso cui sar&agrave; raggiungibile LightSchool. Potrebbe essere <code>http://localhost</code> (se intendi usarlo solo in locale) oppure <code>https://www.ilmiosito.it</code> (se intendi renderlo raggiungibile al di fuori dalla rete locale)</p>
        <label for="secure">Cartella sicura:</label><input type="text" id="secure" name="secure" placeholder="Cartella sicura" value="<?php echo(realpath(__DIR__ . '/../../secure')); ?>" required />
        <p class="small">La cartella sicura &egrave; una cartella che dovresti posizionare un livello prima di <code>public_html</code> o <code>htdocs</code> o comunque in un percorso sul tuo server web non raggiungibile tramite URL.</p>
        <p class="small">Se per esempio stai installando LightSchool nel seguente percorso <code>/home/server/public_html</code> allora la cartella sicura dovrebbe stare su <code>/home/server/secure</code></p>
        <label for="upload">Cartella upload:</label><input type="text" id="upload" name="upload" placeholder="Cartella upload" value="<?php echo(realpath(__DIR__ . '/../../secure/upload')); ?>" required />
        <p class="small">Dovrebbe corrispondere alla cartella sicura, pertanto puoi ripetere quanto scritto al campo precedente, aggiungendo <code>/secure</code></p>
        <p class="small">Se per esempio la cartella sicura l'hai messa su <code>/home/server/secure</code> allora dovrai scrivere <code>/home/server/secure/upload</code></p>
        <hr/>
        <h2>Database</h2>
        <p>Ora &egrave; il turno di configurare i parametri di connessione al database. Per prima cosa, assicurati di aver gi&agrave; creato sul tuo database, un account e un database dedicato interamente a LightSchool.</p>
        <label for="host">Host:</label><input type="text" id="host" name="host" placeholder="Host" value="localhost" required />
        <p class="small">L'indirizzo presso cui &egrave; raggiungibile il tuo database server. Solitamente, &egrave; semplicemente <code>localhost</code>.</p>
        <label for="database">Database:</label><input type="text" id="database" name="database" placeholder="Database" required />
        <p class="small">Il nome del database vuoto sul quale LightSchool scriver&agrave; i dati.</p>
        <p class="small"><b>IMPORTANTE</b> Crea il database utilizzando un qualsiasi charset <code>utf8mb4</code></p>
        <label for="username">Nome utente:</label><input type="text" id="username" name="username" placeholder="Nome utente" required />
        <p class="small">Il nome utente per accedere al database di LightSchool.</p>
        <label for="password">Password:</label><input type="password" id="password" name="password" placeholder="Password" required />
        <p class="small">La password per accedere con l'utente scelto, al database.</p>
        <hr/>
        <h2>E-mail</h2>
        <p>LightSchool deve poter inviare e-mail, ad esempio in fase di registrazione o per il recupero della password. Inserisci quindi i dati del server e-mail.</p>
        <label for="host2">Host:</label><input type="text" id="host2" name="host2" placeholder="Host" value="localhost" required />
        <p class="small">L'indirizzo presso cui &egrave; raggiungibile il tuo database e-mail. Solitamente, &egrave; semplicemente <code>localhost</code>.</p>
        <label for="email">Indirizzo e-mail:</label><input type="email" id="email" name="email" placeholder="E-mail" required />
        <p class="small">Indirizzo e-mail dal quale LightSchool invier&agrave; le e-mail.</p>
        <label for="password2">Password:</label><input type="password" id="password2" name="password2" placeholder="Password" required />
        <p class="small">La password per accedere all'account di posta.</p>
        <hr/>
        <h2>Finito</h2>
        <p>Controlla che i dati inseriti siano corretti e poi clicca su 'Installa!'.</p>
        <div class="alert">

        </div>
        <input type="submit" value="Installa!" />
    </form>
</div>

<script type="text/javascript" src="../js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="../js/fra-ajax.js"></script>
<script type="text/javascript" src="../js/fra-form.js"></script>

<script type="text/javascript">
    $(document).on("submit", "form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());
        $(".alert").hide();

        ajax.execute(function (result) {
            $(".alert").show();
            if (result["response"] === "success") {
                $(".alert").removeClass("alert-danger").addClass("alert-success").html(result["text"]);
                window.location.href = '../';
            } else {
                $(".alert").removeClass("alert-success").addClass("alert-danger").html(result["text"]);
                form.unlock();
            }
        });
    });
</script>
<?php
// @TODO convert everything in a JSON file so server is less busy in useless job

if (isset($_GET["header"]) && $_GET["header"] === "json") header('Content-type:application/json;charset=utf-8');

$strings = [
    "cookie-bar" => "Informativa sui Cookie",
    "cookie-bar-last-edit" => "Ultimo aggiornamento: 22/09/2018 13:00",
    "cookie-bar-description" => "Utilizziamo i Cookie per tenere traccia della lingua di visualizzazione preferita, per tenere traccia che hai accettato quest'informativa (e quindi non mostrarti questo messaggio ogni volta che entri), per permetterti di utilizzare alcuni nostri servizi (es: LightSchool) e infine alcuni servizi di terze parti che includiamo nel sito potrebbero salvare dei cookie.<br/>Se non sei d'accordo, puoi disattivare i cookie per questo sito web dalle impostazioni del tuo browser; tieni tuttavia presente che il sito potrebbe non funzionare correttamente (o addirittura non funzionare proprio).<br/>Potremmo cambiare quest'informativa da un momento all'altro e senza preavviso. Sei pertanto tenuto a controllarla periodicamente e l'utilizzo dei sito implica l'accettazione implicita delle modifiche.",
    "cookie-bar-dialog" => "<br/><br/>Per permetterti di leggere questo messaggio, ho gi&agrave; impostato un cookie per la lingua e indovina un po'? Il tuo dispositivo non &egrave; ancora esploso! Questo dovrebbe farti capire che i cookie non sono esseri malvagi.<br/><br/>Ora ti presento due scelte: accettare i cookie e continuare a navigare nel sito, oppure cancellare i cookie gi&agrave; salvati (per sicurezza, elimina manualmente i cookie qualora ne dovessero rimanere) ed uscire dal sito (ti porter&ograve; a Google).<br/><small style='margin-top: 10px; display: inline-block'>N.B.: Potrai visionare nuovamente l'Informativa sui Cookie accedendo alla pagina dedicata.</small>",
    "cookie-bar-exit" => "Cancella cookie ed esci",
    "cookie-bar-accept" => "Accetto",
    "cookie-show-message-again" => "Mostra nuovamente l'avviso sui cookie",
];
echo(json_encode($strings));
<?php
// @TODO convert everything in a JSON file so server is less busy in useless job

if (isset($_GET["header"]) && $_GET["header"] === "json") header('Content-type:application/json;charset=utf-8');

$strings = [
    "LANG_NAME" => "Italiano",
    "LANG_INT_NAME" => "Italian",
    "LANG_AUTHOR" => "Francesco Sorge <contact@francescosorge.com>",
    "LANG_VER" => 1.0,
    "LANG_REV" => 1,

    "overview" => "Panoramica",
    "features" => "Funzioni",
    "account" => "Account",

    "introduction" => "LightSchool: il futuro dell'istruzione",
    "introduction-description" => "Dì addio al tuo zaino pesante! Porta con te un semplice tablet o il tuo smartphone e accedi ovunque a tutti i tuoi contenuti scolastici!<br/>
Ovunque tu sia potrai accedere ai tuoi appunti, controllare i compiti, giustificare un'assenza, controllare le materie della settimana, mandare e rispondere ai messaggi di studenti e docenti, studiare e molto altro! Non dovrai più ricordarti di portare a scuola del materiale specifico perché sarà sempre con te ovunque tu vada.",
    "what-is" => "Cos'è LightSchool?",
    "what-is-description" => "Uno splendido futuro per ogni scuola nel mondo!<br/>
Unisciti al miglior sito web per la gestione digitale della scuola e sii fra i primi a provare le nuove funzioni offerte.<br/>
Prendere appunti, pianificare eventi e condividere file fra gli utenti sono alcune delle tante funzioni offerte.<br/>
Il nostro obiettivo è di rendere più economico il materiale scolastico e di rendere l'apprendimento più facile e divertente grazie all'utilizzo delle pi&ugrave; moderne tecnologie.",
    "for-whom" => "Per chi &egrave; pensato?",
    "for-whom-student" => "<h3>Studenti</h3><p>LightSchool &egrave; il posto ideale per ogni studente. I docenti iscritti al portale forniscono materiale gratuito che puoi usare per studiare ed imparare. Puoi anche caricare file, prendere appunti, gestire il tuo calendario, l'orario delle lezioni e molto altro.</p>",
    "for-whom-teacher" => "<h3>Docenti</h3><p>I docenti possono ottenere grandi vantaggi da LightSchool. &Egrave; possibile creare classi e gestirne i propri studenti, inviare loro dei quiz, chattare e discutere sul forum privato della classe. Per i docenti &egrave; anche possibile caricare materiale solo per i propri studenti, o scegliere di rendere il materiale pubblico per chiunque!</p>",
    "for-whom-everyone" => "<h3>Chiunque</h3><p>Se non rientri in nessuna delle precedenti categorie, puoi comunque scoprire che LightSchool pu&ograve; essere utile per te. Il materiale gratuito reso disponibile dai docenti di tutto il mondo &egrave; una risorsa importante per tutti, non solo per gli studenti.</p>",
    "get-started" => "Iniziamo!",
    "get-started-login" => "<h3>Accedi</h3><p>Possiedi gi&agrave; un'account? Allora ti basta accedere!</p>",
    "get-started-register" => "<h3>Registrati</h3><p>Se sei un nuovo utente, devi creare un account prima di poter accedere ai servizi offerti dalla piattaforma.</p>",

    "login" => "Accedi",
    "register" => "Registrati",
    "hello" => "Ciao",

    "welcome-my" => "Benvenuto",

    "quotes" => [
        [
            "text" => "Fondare biblioteche è come costruire ancora granai pubblici, ammassare riserve contro un inverno dello spirito che da molti indizi, mio malgrado, vedo venire",
            "author" => "Marguerite Yourcenar",
        ],
        [
            "text" => "Il solo vero viaggio, il solo bagno di giovinezza, non sarebbe quello di andare verso nuovi paesaggi, ma di avere occhi diversi, di vedere l'universo con gli occhi di un altro, di cento altri, di vedere i cento universi che ciascuno di essi vede, che ciascuno di essi è",
            "author" => "Marcel Proust",
        ],
        [
            "text" => "Oggi non è che un giorno qualunque di tutti i giorni che verranno. Ma quello che accadrà in tutti gli altri giorni che verranno può dipendere da quello che farai oggi",
            "author" => "Ernest Hemingway",
        ],
        [
            "text" => "Il più bello dei mari è quello che non navigammo. Il più bello dei nostri figli non è ancora cresciuto. I più belli dei nostri giorni non li abbiamo ancora vissuti. E quello che vorrei dirti di più bello non te l’ho ancora detto",
            "author" => "Nazim Hikmet",
        ],
        [
            "text" => "Quando la tempesta sarà finita, probabilmente non saprai neanche tu come hai fatto ad attraversarla e a uscirne vivo. Anzi, non sarai neanche sicuro se sia finita per davvero. Ma su un punto non c'è dubbio. Ed è che tu, uscito da quel vento, non sarai lo stesso che vi è entrato",
            "author" => "Murakami",
        ],
        [
            "text" => "Preferisco una sconfitta consapevole della bellezza dei fiori, piuttosto che una vittoria in mezzo ai deserti, una vittoria colma della cecità dell'anima, di fronte alla sua nullità separata",
            "author" => "Fernando Pessoa",
        ],
        [
            "text" => "Leggendo non cerchiamo idee nuove, ma pensieri già da noi pensati, che acquistano sulla pagina un suggello di conferma",
            "author" => "Cesare Pavese",
        ],
        [
            "text" => "Ecco il mio segreto. È molto semplice: si vede solo con il cuore. L'essenziale è invisibile agli occhi",
            "author" => "Antoine de Saint-Exupéry",
        ],
        [
            "text" => "I passi che compi non devono essere grandi, devono semplicemente portarti nella direzione giusta",
            "author" => "Jemma Simmons, Agents of S.H.I.E.L.D.",
        ],
        [
            "text" => "Discriminando, generi solo odio",
            "author" => "The Black Eyed Peas",
        ],
        [
            "text" => "“Ciao” è una parola che pronunciamo spesso distrattamente, senza coglierne il senso profondo. Ci si dice “ciao” quando ci si vede, ma anche quando ci si saluta: “ciao” è la premessa di un incontro che si rinnova, il piccolo motore di un riconoscersi che può diventare, via via, conoscenza, relazione, amicizia, collaborazione e condivisione",
            "author" => "Don Luigi Ciotti",
        ],
        [
            "text" => "Dio ci ha donato la memoria, così possiamo avere le rose anche a dicembre",
            "author" => "J.M. Barrie",
        ],
        [
            "text" => "Ogni mare ha un'altra riva.",
            "author" => "Cesare Pavese",
        ],
        [
            "text" => "I sentieri si costruiscono viaggiando",
            "author" => "Franz Kafka",
        ],
        [
            "text" => "Viaggiare &egrave; dare un senso alla propria vita, viaggiare &egrave; donare la vita ai propri sensi",
            "author" => "Alexandre Poussin",
        ],
        [
            "text" => "Un paese &egrave;, per me, un sorriso, un'accoglienza, un nome, molto pi&ugrave; che delle citt&agrave;, delle montagne, delle foreste o delle rive",
            "author" => "Pierre Fillit",
        ],
        [
            "text" => "In viaggio, la cosa migliore &egrave; perdersi. Quando ci si smarrisce, i progetti lasciano il posto alle sorprese, ed &egrave; allora, ma solamente allora, che il viaggio comincia",
            "author" => "Nicolas Bouvier",
        ],
        [
            "text" => "Viaggiare significa aggiungere vita alla vita",
            "author" => "Gesualdo Bufalino",
        ],
        [
            "text" => "In tibetano la definizione di \"essere umano\" &egrave; a-Go ba, \"Viandante\", \"Chi fa migrazioni\"",
            "author" => "Bruce Chatwin",
        ],
        [
            "text" => "Il miglior modo per cercare di capire il mondo &egrave; vederlo dal maggior numero di angolazioni possibili",
            "author" => "Ari Kiev",
        ],
        [
            "text" => "Si viaggia non per cambiare luogo, ma idea",
            "author" => "Hippolyte Adolphe Taine",
        ],
        [
            "text" => "I viaggi cominciano molto prima degli autobus, degli aerei, degli elicotteri, delle navi, dei piedi. I viaggi cominciano dentro la testa. &Egrave; l&igrave; che ci si deve spostare, altrimenti, niente si muove",
            "author" => "Simona Vinci",
        ],
        [
            "text" => "Non c'&egrave; niente come tornare in un luogo che non &egrave; cambiato per rendersi conto di quanto sei cambiato",
            "author" => "Nelson Mandela",
        ],
        [
            "text" => "Quando aiuto la gente che ha fame mi dicono che sono un bravo prete. Quando domando perch&eacute; quella gente ha fame mi chiamano comunista",
            "author" => "Don Andrea Gallo",
        ],
    ],

    "week-days" => [
        1 => "Luned&igrave;",
        2 => "Marted&igrave;",
        3 => "Mercoled&igrave;",
        4 => "Gioved&igrave;",
        5 => "Venerd&igrave;",
        6 => "Sabato",
        7 => "Domenica",
    ],

    "loading" => "Caricamento...",

    "maintenance-mode" => "Manutenzione in corso. Torneremo online quanto prima.",

    "tos" => "Termini del Servizio",
    "tos-last-edit" => "Ultimo aggiornamento: ",
    "tos-text" => "
			
		",

    "privacy-policy" => "Informativa sulla privacy",
    "privacy-policy-last-edit" => "Ultimo aggiornamento: 21/09/2018 20:00",
    "privacy-policy-text" => "
			<p>
				FrancescoSorge.com (\"Noi\") gestisce http://www.francescosorge.com (il
				\"Sito\"). Questa pagina ha lo scopo di informarti sulle nostre politiche riguardanti la raccolta, l'uso e la comunicazione dei Dati Personali cbe riceviamo dagli utenti del Sito.<br>
				Utilizziamo i Dati Personali solo per fornire e migliorare il Sito. Utilizzando questo Sito, accetti alla raccolta e all'uso delle informazioni secondo quanto specificato nella presente informativa.
			</p>
			<h2>Raccolta ed uso Informazioni Personali</h2>
			<p>
				Finch&eacute; usi il Sito, potremmo chiederti di fornirci informazioni utili alla tua identificazione, anche allo scopo di contattarti (es: modulo \"Contattami\"). Queste informazioni possono includere, ma non limitarsi, al tuo nome (\"Informazioni Personali\").
			</p>
			<h2>Dati di log</h2>
			<p>
				Come molti altri siti, raccogliamo informazioni che il tuo browser ci invia (\"Dati di Log\").<br>
				Questi Dati di Log potrebbero includere informazioni come ad esempio il tuo Indirizzo IP, tipo del browser, versione, le pagine che visiti sul Sito, la data ed ora della tua visita, il tempo speso sulle nostre pagine e altri dati statistici.<br>
				In aggiunta, potremmo utilizzare servizi di terze parti come Google Analytics che raccolgono, monitorano e analizzano i log.
			</p>
			<h2>Comunicazioni</h2>
			<p>
				Potremmo usare le tue Informazioni Personali per contattarti, come nel caso tu utilizza il modulo \"contattami\". In futuro, potremmo proporre l'adesione a newsletter e comunicazioni di altro tipo. L'adesione sar&agrave; comunque volontaria e non avr&agrave; effetti retroattivi salvo diversamente specificato.
			</p>
			<h2>Cookie</h2>
			<p>
				I cookie sono file di piccole dimensioni, che potrebbero includere un identificatore unico ed anonimo.<br>
				I cookie vengono inviati dal tuo browser al sito web e vengono salvati nel tuo dispositivo.<br>
				Come molti altri siti, utilizziamo i \"Cookie\" per raccogliere informazioni. Puoi dire al tuo browser di rifiutare tutti i nostri cookie o solo alcuni. Tuttavia se non accetti i cookie, il Sito potrebbe non funzionare correttamente.<br/>
				Noi utilizziamo i cookie solamente per tenere traccia della lingua di visualizzazione preferita e per permetterti di collegarti con il tuo account e sfruttare i nostri servizi (es: LightSchool).
			</p>
			<h2>Sicurezza</h2>
			<p>
				La sicurezza dei tuoi Dati Personali &egrave; importante per Noi, ma ricorda che nessun metodo di trasmissione su Internet, o sistema di memorizazione, &egrave; sicuro al 100%. Noi garantiamo di utilizzare metodi di sicurezza commercialmente accettabili per proteggere le tue Informazioni Personali, non possiamo garantire l'assoluta sicurezza.
			</p>
			<h2>Modifiche apportate a questa informativa</h2>
			<p>
				Questa Informativa sulla Privacy &egrave; in vigore dalla data ed ora riportata in cima alla pagina. Qualora dovessero essere apportate modifiche in futuro, verranno pubblicate su questa pagina, aggiornando quindi la data ed ora dell'ultimo aggiornamento.<br>
				Ci riserviamo il diritto di aggiornare l'Informativa sulla Privacy in ogni momento ed &egrave; tuo compito controllare periodicamente questa pagina. Continuare ad utilizzare il Sito dopo i cambiamenti della presente Informativa costituiscono l'accettazione implicita della stessa.<br>
				Qualora possibile potremmo notificarti via e-mail oppure posizionando un banner visibile nelle pagine del Sito, dei cambiamenti apportati alla presente Informativa.
			</p>
		",

    "footer-useful-links" => "Link utili",
    "contact" => "Contattaci",
    "change-language" => "Cambia lingua",
    "footer-share-this" => "Condivisi questo sito",
    "footer-copyright" => "Sito web realizzato da Francesco Sorge. Tutti i diritti riservati.",
    "menu-close-mobile" => "Chiudi menu",
    "error-404" => "Errore 404 Pagina non trovata",
    "error-404-description" => "La pagina che stavi cercando potrebbe non esistere pi&ugrave; oppure essere stata spostata.",
];

echo(json_encode($strings));

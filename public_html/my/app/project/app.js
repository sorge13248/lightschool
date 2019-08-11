class Project {
    static code() {
        const code = new FraAjax(ConfigSite.baseURL + "/my/app/project/controller?type=code");

        code.execute(function(result) {
            if (result["response"] === "success") {
                $(".sidebar > h2 > span").html(result["code"]);
                $(".files").html("<div id=\"loading\" style=\"text-align: center\"><p>" + result["text"] + "</p><h3>" + result["code"] + "</h3><br/><p>Per mostrare qualcosa sulla LIM, andare sul File Manager dal proprio dispositivo, fare tasto destro su un file e selezionare \"Proietta su LIM\" e digitare il codice mostrato.</p><br/><p><i>In attesa di connessioni...</i></p><p><a href=\"#\" class=\"button small refresh-files\">Ricarica</a></p></div>");

                Project.files();
            } else {
                $(".files").html("<div id=\"loading\" style=\"text-align: center\"><div class='alert alert-danger'>" + result["text"] + "</div></div>");
            }
        });
    }

    static files() {
        const files = new FraAjax(ConfigSite.baseURL + "/my/app/project/controller?type=files");

        files.execute(function(result) {
            console.log(result);
            if (result !== null && result.length > 0) {
                $(".files").html("<h2>File proiettati</h2>");

                for (const i in result) {
                    let secondRow = "<small class='second-row text-ellipsis'>";
                    secondRow += result[i]["user"]["name"] + " " + result[i]["user"]["surname"];
                    if (result[i]["type"] === "diary") {
                        result[i]["name"] = result[i]["diary_type"] + " il " + result[i]["name"];
                        secondRow += " &bull; " + result[i]["diary_date"];
                    }
                    secondRow += "</small>";

                    $(".files").append("<a href='" + ConfigSite.baseURL + "/my/app/reader/" + result[i]["type"] + "/" + result[i]["id"] + "' class='icon img-change-to-white accent-all box-shadow-1-all'><img src='" + result[i]["icon"] + "' class='change-this-img' style='float: left; max-height: 40px; width: auto' /><span style=\"display: block; font-size: 1.2em\" class=\"filename text-ellipsis\">" + result[i]["name"] + "</span>" + secondRow + "</a>");
                }
                recalculateIcons();
                $(".sidebar").removeClass("col-md-0").addClass("col-md-2").show();
                $(".files").removeClass("col-md-12").addClass("col-md-10");
            }
        });
    }

    static delete() {
        const code = new FraAjax(ConfigSite.baseURL + "/my/app/project/controller?type=delete");

        code.execute(function(result) {
            if (result === true) {
                Project.code();
            } else {
                alert("Something bad happened");
            }
        });
    }

    static yourFiles() {
        const files = new FraAjax(ConfigSite.baseURL + "/my/app/project/controller?type=your-files");

        files.execute(function(result) {
            $(".your-files").html("");
            if (result !== null && result.length > 0) {
                for (const i in result) {
                    let secondRow = "<small class='second-row text-ellipsis'>";
                    secondRow += result[i]["project"];
                    if (result[i]["type"] === "diary") {
                        result[i]["name"] = result[i]["diary_type"] + " il " + result[i]["name"];
                        secondRow += " &bull; " + result[i]["diary_date"];
                    }
                    secondRow += "</small>";

                    $(".your-files").append("<a href='" + ConfigSite.baseURL + "/my/app/reader/" + result[i]["type"] + "/" + result[i]["id"] + "' class='icon img-change-to-white accent-all box-shadow-1-all' fileid='" + result[i]["id"] +"' projectcode='" + result[i]["project"] + "'><img src='" + result[i]["icon"] + "' class='change-this-img' style='float: left; max-height: 40px; width: auto' /><span style=\"display: block; font-size: 1.2em\" class=\"filename text-ellipsis\">" + result[i]["name"] + "</span>" + secondRow + "</a>");
                }
                recalculateIcons();
            } else if (result !== null && result.length === 0) {
                $(".your-files").html("<p>Attualmente non stai proiettando nessun file</p>");
            }
        });
    }
}

$(document).on("click", ".enter-projection-mode", function (e) {
    e.preventDefault();

    history.pushState({
        id: 'project-projection',
    }, null, ConfigSite.baseURL + '/my/app/project/projection/');

    $(".welcome-screen").hide();
    $(".projection-mode").show();

    Project.code();
});

$(document).on("click", ".exit-projection-mode", function (e) {
    e.preventDefault();

    history.pushState({
        id: 'project-welcome-screen',
    }, null, ConfigSite.baseURL + '/my/app/project/');

    $(".projection-mode").hide();
    $(".welcome-screen").show();
});

$(document).on("click", ".delete", function(e) {
    e.preventDefault();
    Project.delete();
});

$(document).on("click", ".refresh-files", function(e) {
    e.preventDefault();
    Project.files();
});

$(document).ready(() => {
    history.replaceState({
        id: history.state ? history.state.id : "project-welcome-screen"
    }, null);

    let temp = (window.location.href).split("/");
    const page = temp[temp.length - 2];

    if (page === "projection") {
        $(".welcome-screen").hide();
        $(".projection-mode").show();

        Project.code();
    }

    Project.yourFiles();
});

window.addEventListener('popstate', function (event) {
    if (history.state && history.state.id === 'project-welcome-screen') {
        $(".projection-mode").hide();
        $(".welcome-screen").show();
    } else if (history.state && history.state.id === 'project-projection') {
        $(".welcome-screen").hide();
        $(".projection-mode").show();
    }
}, false);
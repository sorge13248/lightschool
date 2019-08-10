<style type="text/css">
    .icon {
        max-width: 100%;
    }
</style>

<div class="container content-my whiteboard">

    <div class="row">
        <div class="col-md-0 sidebar" style="display: none">
            <h2>Codice LIM: <span></span></h2>
            <div>

            </div>
        </div>
        <div class="col-md-12 reader">
            <div id="loading" style="text-align: center">
                <p>Sto ottenendo il codice univoco...</p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    let WhiteBoardCode = null;

    class WhiteBoard {
        static code() {
            const code = new FraAjax("controller?type=code");

            code.execute(function(result) {
                if (result["response"] === "success") {
                    WhiteBoardCode = result["code"];

                    $(".sidebar > h2 > span").html(result["code"]);
                    $("#loading").html("<p>" + result["text"] + "</p><h3>" + result["code"] + "</h3><br/><p>Per mostrare qualcosa sulla LIM, andare sul File Manager dal proprio dispositivo, fare tasto destro su un file e selezionare \"Proietta su LIM\" e digitare il codice mostrato.</p><br/><p><i>In attesa di connessioni...</i></p>");

                    WhiteBoard.files();
                } else {
                    $("#loading").html("<div class='alert alert-danger'>" + result["text"] + "</div>");
                }
            });
        }

        static files() {
            const files = new FraAjax("controller?type=files");

            files.execute(function(result) {
                if (result !== null && result.length > 0) {
                    for (const i in result) {
                        let secondRow = "<small class='second-row'>";
                        secondRow += result[i]["user"]["name"] + " " + result[i]["user"]["surname"];
                        if (result[i]["type"] === "diary") {
                            result[i]["name"] = result[i]["diary_type"] + " il " + result[i]["name"];
                            secondRow += " &bull; " + result[i]["diary_date"];
                        }
                        secondRow += "</small>";

                        $(".sidebar > div").append("<a href='../reader/" + result[i]["type"] + "/" + result[i]["id"] + "' class='icon img-change-to-white accent-all box-shadow-1-all'><img src='" + result[i]["icon"] + "' class='change-this-img' style='float: left' />" + result[i]["name"] + secondRow + "</a>");
                    }
                    recalculateIcons();
                    $(".sidebar").removeClass("col-md-0").addClass("col-md-2").show();
                    $(".reader").removeClass("col-md-12").addClass("col-md-10").html("<i>Seleziona un file dalla barra laterale per mostrarlo</i>");
                }
            });
        }
    }

    $(document).ready(function () {
        WhiteBoard.code();
    });
</script>
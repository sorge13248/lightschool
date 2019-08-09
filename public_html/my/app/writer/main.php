<?php
$database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

$ownership = true;
if ($_GET["id"] !== null) {
    require_once __DIR__ . "/../file-manager/model.php";
    $ownership = (new \FrancescoSorge\PHP\LightSchool\FileManager())->checkOwnership((int)$_GET["id"]);
}
?>

<link href="https://cdn.quilljs.com/1.2.6/quill.snow.css" rel="stylesheet">
<div class="container content-my writer">
    <div class="main">
        <?php if ($ownership === false) { ?>
            <script type="text/javascript">
                $(document).ready(() => {
                    $(".menu-my.top#toolbar").remove();
                    $(".menu-my.top form").remove();
                });
            </script>
            <div style="text-align: center;">
                <div class="file">
                    <p>Non hai il permesso per scrivere in questa cartella o su questo quaderno.</p>
                </div>
            </div>
        <?php } else { ?>
            <div class="A4" id="notebook-editor">

            </div>
            <script src="<?php echo(CONFIG_SITE["baseURL"]); ?>/js/quill.min.js"></script>

            <script type="text/javascript">
                Quill.prototype.getHtml = function () {
                    return this.container.firstChild.innerHTML;
                };

                let quill = null;

                const initializeQuill = () => {
                    quill = new Quill('#notebook-editor', {
                        placeholder: 'Inizia a scrivere un capolavoro!',
                        modules: {
                            toolbar: {
                                container: '#toolbar'
                            },
                        },
                        theme: 'snow'
                    });
                    quill.focus();
                };

                let response = null;

                $(document).ready(() => {
                    $(".menu-my.main").css("box-shadow", "none");
                    $(".menu-my.top form").show();
                    <?php if ($_GET["type"] === "edit") { ?>
                    const loadingWindow = new FraWindows("loading", "Writer", "<p style='text-align: center'><span style='font-size: 1.2em'>Caricamento quaderno in corso</span><br/>Attendere prego...</p>");
                    loadingWindow.setOverlay();
                    loadingWindow.setTitlebarPadding(10, 0, 10, 0);
                    loadingWindow.setContentPadding(20);
                    loadingWindow.setSize("100%");
                    loadingWindow.setProperty("max-width", "450px");
                    loadingWindow.setProperty("max-height", "100vh");
                    loadingWindow.setDraggable();
                    loadingWindow.setPosition();
                    loadingWindow.show("fadeIn", 300);

                    setTimeout(() => {
                        response = new FraJson(ConfigSite.baseURL + "/my/app/file-manager/controller?type=details&id=<?php echo($_GET["id"]); ?>&fields=name,type,html,n_ver").getAll();

                        if (response.response === "error") {
                            loadingWindow.setContent(response.text);
                        } else if (response.file) {
                            loadingWindow.close();
                            $("title").text(response.file.name + " - " + $("title").text());
                            $(".menu-my #name").val(response.file.name);
                            if (parseInt(response.file.n_ver) === 2) {
                                initializeQuill();
                                quill.setContents(JSON.parse(response.file.html));
                            } else {
                                $("#notebook-editor").html(response.file.html);
                                initializeQuill();
                            }
                        }

                        $(".menu-my.top#toolbar").show();
                    }, 300);
                    <?php } else { ?>
                    initializeQuill();
                    $(".menu-my.top#toolbar").show();
                    <?php } ?>
                });

                $(document).on("click", "#notebook-editor", () => {
                    quill.focus();
                });

                $(document).on("submit", ".menu-my.top form", function (e) {
                    e.preventDefault();

                    const form = new FraForm($(this));
                    form.lock();

                    let content;
                    if (response === null || parseInt(response.file.n_ver) === 2) {
                        content = base64Encode(JSON.stringify(quill.getContents()["ops"]));
                    } else {
                        content = $("#notebook-editor").html();
                    }

                    const ajax = new FraAjax($(this).attr("action") + "&type=" + (response === null ? "create" : "edit"), $(this).attr("method"), form.getInput() + "&content=" + content);

                    ajax.execute(function (result) {
                        const createNotification = new FraNotifications("notebook-created-" + FraBasic.generateGUID(), result["text"]);
                        if (result["response"] === "success") {
                            window.location.href = ConfigSite.baseURL + "/my/app/reader/notebook/" + result["id"];
                        } else {
                            createNotification.setType("error");
                            form.unlock();
                        }
                        createNotification.setZIndex(100000);
                        createNotification.show();
                        createNotification.setAutoClose(2000);

                        $(".menu-my.top form #name").focus();
                    });
                });
            </script>
        <?php } ?>

    </div>
</div>
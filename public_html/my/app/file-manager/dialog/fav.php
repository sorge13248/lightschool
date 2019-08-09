<script type="text/javascript">
    $(document).on("click", ".fra-context-menu[fileid] .fav", function (e) {
        e.preventDefault();
        fileFraContextMenu.hide();
        const fileid = $(this).closest(".fra-context-menu").attr("fileid");
        const ajax = new FraAjax(ConfigSite.baseURL + '/my/app/file-manager/controller?type=fav&id=' + fileid, "POST", "");

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const element = $(".icon[fileid='" + fileid + "']").length > 0 ? $(".icon[fileid='" + fileid + "']") : $(".event[fileid='" + fileid + "']");
                const prev = parseInt(element.attr("file_fav"));
                if (prev === 1) {
                    element.attr("file_fav", "0");
                    <?php if (isset($_GET["folder"]) && $_GET["folder"] === "desktop") { ?>
                    element.remove();
                    <?php } ?>
                } else {
                    $(".icon[fileid='" + fileid + "']").attr("file_fav", "1");
                }
            }

            const errorNotification = new FraNotifications("file-fav-" + FraBasic.generateGUID(), result["text"]);
            errorNotification.show();
            errorNotification.setAutoClose(2000);
        });
    });
</script>
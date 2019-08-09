<script type="text/javascript">
    $(document).on("input", "#subject", function () {
        const val = $(this).val().toLowerCase();
        if (val.length === 0) {
            $(this).closest(".fra-windows").find(".subjectsList").slideUp(200);
            $(this).closest(".fra-windows").find(".no-result").hide();
        } else {
            $(this).closest(".fra-windows").find(".subjectsList .icon").css("display", "block");
            $(this).closest(".fra-windows").find(".subjectsList .icon").each(function () {
                if (!$(this).find(".print").text().toLowerCase().includes(val) && !$(this).attr("title").toLowerCase().includes(val)) {
                    $(this).css("display", "none");
                }
            });

            $(this).closest(".fra-windows").find(".subjectsList").slideDown(200);
            if ($(this).closest(".fra-windows").find(".subjectsList .icon:visible").length > 0) {
                $(this).closest(".fra-windows").find(".no-result").hide();
            } else {
                $(this).closest(".fra-windows").find(".no-result").show();
            }
        }
    });

    $(document).on("click", ".subjectsList .icon", function (e) {
        e.preventDefault();
        $(this).closest(".fra-windows").find("#subject").val($(this).attr("title").trim());
        if ($(this).closest(".fra-windows").find("#book").length > 0) $(this).closest(".fra-windows").find("#book").val($(this).attr("book").trim());
        setFraColorPicker($(this).closest(".fra-windows").find(".color"), "#" + $(this).attr("fore").trim(), false, true);
        $(this).closest(".fra-windows").find(".subjectsList").slideUp(200);
    });
</script>
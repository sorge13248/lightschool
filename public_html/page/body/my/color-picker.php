<style type="text/css">
    *[fra-color-picker="1"], *[fra-color-picker="1"]:hover, *[fra-color-picker="1"]:focus {

    }

    .fra-color-picker {
        display: none;
        position: absolute;
        padding: 5px;
        padding-bottom: 0;
        background-color: rgba(255, 255, 255, 0.9);
        z-index: 100000;
        margin-bottom: 80px;
    }

    .fra-color-picker a {
        display: inline-block;
        width: 24px;
        height: 24px;
        margin: 2px;
    }
</style>

<script type="text/javascript">
    const rgbToHex = (rgb) => {
        let hex = Number(rgb).toString(16);
        if (hex.length < 2) {
            hex = "0" + hex;
        }
        return hex;
    };

    const hexToRgb = (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    };

    window.fra_color_picker_field = null;
    $(document).on("focus", "[fra-color-picker='1']", function () {
        if ($(this).is("[fra-color-picker-default]")) $(".fra-color-picker .none").css("display", "block");
        else $(".fra-color-picker .none").css("display", "none");

        $(".fra-color-picker").fadeIn(200).css({
            "top": ($(this).offset().top + $(this).outerHeight()) + "px",
            "left": ($(this).offset().left) + "px"
        });
        window.fra_color_picker_field = $(this).attr("id");
    });

    $(document).on("blur", "[fra-color-picker='1']", function () {
        setTimeout(function () {
            if ($(':focus').attr("fra-color-picker") !== "1") $(".fra-color-picker").fadeOut(200);
        }, 200);
    });

    $(document).on("click", ".fra-color-picker > .none > a", function (e) {
        e.preventDefault();
        setFraColorPicker(window.fra_color_picker_field, "");

        document.dispatchEvent(new CustomEvent(window.fra_color_picker_field + "-color-picked", {"detail": window.fra_color_picker_field + " color picked"}));
    });

    $(document).on("click", ".fra-color-picker > a", function (e) {
        e.preventDefault();
        setFraColorPicker(window.fra_color_picker_field, $(this).css("background-color").replace("rgb(", "").replace(")", "").split(", "));

        document.dispatchEvent(new CustomEvent(window.fra_color_picker_field + "-color-picked", {"detail": window.fra_color_picker_field + " color picked"}));
    });

    const setFraColorPicker = (id, color, convertToHex = true, selector = false) => {
        if (color === "") {
            convertToHex = false;
        }
        if (convertToHex) color = "#" + (rgbToHex(color[0]) + rgbToHex(color[1]) + rgbToHex(color[2])).toUpperCase();
        if (!selector) id = $("input#" + id);
        id.val(color);
        id.css({"color": color, "background-color": color});
    };
</script>

<div class="fra-color-picker">
    <span style="display: none; text-align: center" class="none"><a href="#" class="button"
                                                                    style="width: auto; height: auto">Nessuno</a><br/></span>
    <a href="#" style="background-color: #000000"></a>
    <a href="#" style="background-color: #202020"></a>
    <a href="#" style="background-color: #404040"></a>
    <a href="#" style="background-color: #606060"></a>
    <a href="#" style="background-color: #808080"></a>
    <a href="#" style="background-color: #A0A0A0"></a>
    <a href="#" style="background-color: #C0C0C0"></a>
    <a href="#" style="background-color: #E0E0E0"></a>
    <a href="#" style="background-color: #FFFFFF"></a>
    <br/>
    <a href="#" style="background-color: #610B0B"></a>
    <a href="#" style="background-color: #8A0808"></a>
    <a href="#" style="background-color: #B40404"></a>
    <a href="#" style="background-color: #DF0101"></a>
    <a href="#" style="background-color: #FF0000"></a>
    <a href="#" style="background-color: #FE2E2E"></a>
    <a href="#" style="background-color: #FA5858"></a>
    <a href="#" style="background-color: #F78181"></a>
    <a href="#" style="background-color: #F5A9A9"></a>
    <br/>
    <a href="#" style="background-color: #61380B"></a>
    <a href="#" style="background-color: #8A4B08"></a>
    <a href="#" style="background-color: #B45F04"></a>
    <a href="#" style="background-color: #DF7401"></a>
    <a href="#" style="background-color: #FF8000"></a>
    <a href="#" style="background-color: #FE9A2E"></a>
    <a href="#" style="background-color: #FAAC58"></a>
    <a href="#" style="background-color: #F7BE81"></a>
    <a href="#" style="background-color: #F5D0A9"></a>
    <br/>
    <a href="#" style="background-color: #5E610B"></a>
    <a href="#" style="background-color: #868A08"></a>
    <a href="#" style="background-color: #AEB404"></a>
    <a href="#" style="background-color: #D7DF01"></a>
    <a href="#" style="background-color: #FFFF00"></a>
    <a href="#" style="background-color: #F7FE2E"></a>
    <a href="#" style="background-color: #F4FA58"></a>
    <a href="#" style="background-color: #F3F781"></a>
    <a href="#" style="background-color: #F2F5A9"></a>
    <br/>
    <a href="#" style="background-color: #0B610B"></a>
    <a href="#" style="background-color: #088A08"></a>
    <a href="#" style="background-color: #04B404"></a>
    <a href="#" style="background-color: #01DF01"></a>
    <a href="#" style="background-color: #00FF00"></a>
    <a href="#" style="background-color: #2EFE2E"></a>
    <a href="#" style="background-color: #58FA58"></a>
    <a href="#" style="background-color: #81F781"></a>
    <a href="#" style="background-color: #A9F5A9"></a>
    <br/>
    <a href="#" style="background-color: #0B615E"></a>
    <a href="#" style="background-color: #088A85"></a>
    <a href="#" style="background-color: #04B4AE"></a>
    <a href="#" style="background-color: #01DFD7"></a>
    <a href="#" style="background-color: #00FFFF"></a>
    <a href="#" style="background-color: #2EFEF7"></a>
    <a href="#" style="background-color: #58FAF4"></a>
    <a href="#" style="background-color: #81F7F3"></a>
    <a href="#" style="background-color: #A9F5F2"></a>
    <br/>
    <a href="#" style="background-color: #0B3861"></a>
    <a href="#" style="background-color: #084B8A"></a>
    <a href="#" style="background-color: #045FB4"></a>
    <a href="#" style="background-color: #0174DF"></a>
    <a href="#" style="background-color: #0080FF"></a>
    <a href="#" style="background-color: #2E9AFE"></a>
    <a href="#" style="background-color: #58ACFA"></a>
    <a href="#" style="background-color: #81BEF7"></a>
    <a href="#" style="background-color: #A9D0F5"></a>
    <br/>
    <a href="#" style="background-color: #0B0B61"></a>
    <a href="#" style="background-color: #08088A"></a>
    <a href="#" style="background-color: #0404B4"></a>
    <a href="#" style="background-color: #0101DF"></a>
    <a href="#" style="background-color: #0000FF"></a>
    <a href="#" style="background-color: #2E2EFE"></a>
    <a href="#" style="background-color: #5858FA"></a>
    <a href="#" style="background-color: #8181F7"></a>
    <a href="#" style="background-color: #A9A9F5"></a>
    <br/>
    <a href="#" style="background-color: #610B5E"></a>
    <a href="#" style="background-color: #8A0886"></a>
    <a href="#" style="background-color: #B404AE"></a>
    <a href="#" style="background-color: #DF01D7"></a>
    <a href="#" style="background-color: #FF00FF"></a>
    <a href="#" style="background-color: #FE2EF7"></a>
    <a href="#" style="background-color: #FA58F4"></a>
    <a href="#" style="background-color: #F781F3"></a>
    <a href="#" style="background-color: #F5A9F2"></a>
</div>
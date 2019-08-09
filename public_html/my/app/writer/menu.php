<style type="text/css">
    .menu-my {
        z-index: 1001;
    }

    .menu-my.top#toolbar {
        top: 41px;
        border: none;
        z-index: 1000;
        text-align: center;
        background-image: none;
        background-color: #F6F6F6;
        display: none;
        overflow-x: auto;
        white-space: nowrap;
    }

    .menu-my.top#toolbar > span > * {
        padding: 3px 0;
        min-width: 28px;
    }

    .menu-my.top#toolbar > span > *:hover, .menu-my.top#toolbar > span > *:focus {
        box-shadow: none;
    }
</style>

<script type="text/javascript">
    $(document).on("input", ".menu-my.top form input", function () {
        const length = $(this).val().length;
        $(this).css("width", (length * 8) + "px");
    });
</script>

<div class="menu-my top main no-print accent-bkg-gradient">
    <div class="row">
        <div class="col-sm-12" style="text-align: center">
            <div class="pc-md">
                <a href="../../file-manager/<?php echo($_GET["id"] !== null ? $_GET["id"] . "/" : ""); ?>"
                   class="back-button" style="display: inline-block; padding: 10px 5px 0; float: left"><img
                            src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/back.png" title="Indietro"/></a>
                <h5 style="float: left; text-align: left; font-weight: bold"><?php echo($this->getVariables("pageTitle")); ?></h5>
            </div>
            <form method="post"
                  action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/writer/controller.php?id=<?php echo($_GET["id"] !== null ? $_GET["id"] : ""); ?>&type="
                  style="display: none">
                <input type="text" id="name" name="name" placeholder="Nome quaderno"
                       style="padding: 6px 10px; font-size: 0.8em; text-align: center; min-width: 170px; max-width: 100%; border: 0"
                       value="Documento senza titolo" autocomplete="off"/>
                <input type="submit" value="Salva" class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                       style="padding: 6px 10px; font-size: 0.8em"/>
            </form>
        </div>
    </div>
</div>

<div class="menu-my top no-print accent-box-shadow-1" id="toolbar">
    <span class="ql-formats">
        <select class="ql-font"></select>
        <select class="ql-size"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-bold"></button>
        <button class="ql-italic"></button>
        <button class="ql-underline"></button>
        <button class="ql-strike"></button>
    </span>
    <span class="ql-formats">
        <select class="ql-color"></select>
        <select class="ql-background"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-script" value="sub"></button>
        <button class="ql-script" value="super"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-header" value="1"></button>
        <button class="ql-header" value="2"></button>
        <button class="ql-blockquote"></button>
        <button class="ql-code-block"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-list" value="ordered"></button>
        <button class="ql-list" value="bullet"></button>
        <button class="ql-indent" value="-1"></button>
        <button class="ql-indent" value="+1"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-direction" value="rtl"></button>
        <select class="ql-align"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-link"></button>
        <button class="ql-image"></button>
        <button class="ql-video"></button>
        <button class="ql-formula"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-clean"></button>
    </span>
</div>
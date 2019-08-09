<?php if (!isset($_GET["min"]) || $_GET["min"] !== "1") { ?>
    <style type="text/css">
        html {
            overflow-y: hidden;
        }
    </style>
    <script type="text/javascript">
        $(document).ready(() => {
            const id = 0 + <?php echo(isset($_GET["id"]) ? (int)$_GET["id"] : 0); ?>;
            Message.chat(id);

            if (drafts[id] !== null) {
                $("#body").val(drafts[id]);
            }

            Message.goDown();
        });
    </script>
<div class="container content-my message">
    <?php } ?>
    <div class="chat-opened">
        <h3><img src='' class='profile_picture'
                 style='display: none; margin-top: 0; box-shadow: none; width: 40px; height: 40px'/><span
                    class="name_and_surname"></span></h3>
        <!--    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Chiamata</a> <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Videochiamata</a>-->
        <div style="clear: both"></div>
        <hr style="margin-bottom: 0"/>
        <div class="chat" style="height: calc(100vh - 300px); overflow-y: auto">
            <div style="text-align: center">
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra
                    pi&ugrave; elementi</a>
            </div>

            <div class="items">
                <p class="loading">Caricamento...</p>
            </div>
        </div>
        <div class="box" style="margin-top: 5px">
            <form method="post" action="controller?type=send&id=<?php echo($_GET["id"]); ?>" class="send-message">
                <textarea id="body" name="body" style="width: calc(100% - 80px); height: 80px"
                          placeholder="Scrivi qui il tuo messaggio..."></textarea><input type="submit" value=""
                                                                                         style="padding: 10px; float: right; width: 60px; height: 80px; background-image: url('../../../upload/mono/<?php echo($this->getVariables("currentUser")->theme["icon"]); ?>/send.png'); background-size: contain; background-position: center; background-repeat: no-repeat; background-origin: content-box;"/>
            </form>
        </div>
    </div>
    <?php if (!isset($_GET["min"]) || $_GET["min"] !== "1") { ?>
</div>
<?php } ?>


<div class="content content-my app-not-purchased">
    <div style="margin: 0 auto; width: 100%; max-width: 1100px; text-align: center">
        <h2>App non associata all'account</h2>
        <p>Applicazione non associata al tuo account. Passa a LightStore per ottenerla o acquistarla.</p>
        <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/store/d/<?php echo(explode("/", strstr($this->getVariables("FraBasic")::getURL(), 'app/'))[1]); ?>/"
           class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Vai a LightStore &gt;</a>
    </div>
</div>
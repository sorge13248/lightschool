<script module>
    import PublicLayout from '../../layouts/PublicLayout.svelte';
    export const layout = PublicLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';

    interface Props {
        isAuthenticated: boolean;
        username?: string;
        appName: string;
        locale: string;
        currentUrl: string;
    }
    const { isAuthenticated: _isAuthenticated, username: _username, appName, locale, currentUrl }: Props = $props();
</script>

<svelte:head><title>{t('tos')} - {appName}</title></svelte:head>

<div id="pageContent" class="elementToPadMenu">
    <div class="container">
        <h1 style="margin-top: 0; padding-top: 0">{@html t('tos')}</h1>
        <p>{@html t('tos-last-edit')}</p>
        <hr />
        {@html t('tos-text')}
    </div>

    <!-- footer -->
    <div style="background-color: #F6F6F6; padding: 40px">
        <div class="contact container">
            <div class="row">
                <div class="col-md-6">
                    <h4>{appName}</h4>
                </div>
                <div class="col-md-6">
                    <p style="font-weight: bold">{t('footer-useful-links')}</p>
                    <div class="row">
                        <div class="col-md-6"><p><a href="/privacy">{t('privacy-policy')}</a></p></div>
                        <div class="col-md-6"><p><a href="/tos">{t('tos')}</a></p></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><p><a href="/cookie">{t('cookie-bar')}</a></p></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><p><a href="//www.francescosorge.com/#contact">{t('contact')}</a></p></div>
                    </div>
                    <p>
                        <a href={'/language?redirect=' + encodeURIComponent(currentUrl)}>
                            <img src={'/img/language/' + locale + '.png'} style="width: 26px; margin-right: 5px" alt="" />
                            {t('change-language')}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

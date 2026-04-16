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
        languages: Record<string, { LANG_INT_NAME: string; LANG_NAME: string }>;
        redirect: string;
    }
    const { isAuthenticated: _isAuthenticated, username: _username, appName, locale, currentUrl, languages, redirect }: Props = $props();
</script>

<svelte:head><title>{t('change-language')} - {appName}</title></svelte:head>

<div id="pageContent" class="elementToPadMenu">
    <div class="container">
        <h1 style="margin-top: 0; padding-top: 0">{@html t('change-language')}</h1>
        <hr />
        <div style="text-align: center">
            {#each Object.entries(languages) as [code, info]}
                <a
                    href={'/language/set?lang=' + code + '&redirect=' + encodeURIComponent(redirect)}
                    class="list list-inline"
                    style="text-align: center; padding-top: 40px; padding-bottom: 40px; display: inline-block"
                >
                    <img src={'/img/language/' + code + '.png'} style="width: 128px; margin-bottom: 10px" alt={info.LANG_INT_NAME ?? code} /><br />
                    <b style="font-size: 1.3em">{info.LANG_INT_NAME ?? code}</b><br />
                    {info.LANG_NAME ?? ''}
                </a>
            {/each}
        </div>
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

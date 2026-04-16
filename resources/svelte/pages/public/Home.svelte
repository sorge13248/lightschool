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
    const { isAuthenticated, username, appName, locale, currentUrl }: Props = $props();
</script>

<svelte:head><title>{appName}</title></svelte:head>

<div class="welcome center-content background-image">
    <span>
        <h1 style="margin-top: 80px">{@html t('introduction')}</h1>
        <h3 style="max-width: 1500px; margin: 0 auto">{@html t('introduction-description')}</h3>
    </span>
</div>

<div id="pageContent">
    <div style="background-color: #FFFFFF" id="what-is">
        <div class="what-is container">
            <div class="row">
                <div class="col-md-2 pc"
                     style="background: url(/img/logo.png) no-repeat top center; background-size: contain; min-height: 50px;">
                </div>
                <div class="col-md-10">
                    <h2 class="title">
                        <img src="/img/logo.png" style="max-width: 64px; margin-right: 10px" class="mobile" alt="" />
                        {@html t('what-is')}
                    </h2>
                    {@html t('what-is-description')}
                </div>
            </div>
        </div>
    </div>

    <div style="background-color: #F6F6F6" id="for-whom">
        <div class="for-whom container">
            <div class="row">
                <div class="col-md-2 pc"
                     style="background: url(/img/for-whom.png) no-repeat top center; background-size: contain; min-height: 50px">
                </div>
                <div class="col-md-10">
                    <h2 class="title">
                        <img src="/img/for-whom.png" style="max-width: 70px; margin-right: 20px" class="mobile" alt="" />
                        {@html t('for-whom')}
                    </h2>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">{@html t('for-whom-student')}</div>
                <div class="col-md-4">{@html t('for-whom-teacher')}</div>
                <div class="col-md-4">{@html t('for-whom-everyone')}</div>
            </div>
            <div class="row" style="text-align: center">
                <div class="col-md-12">
                    <p>{t('home-learn-more')}
                        <a href="/overview" class="button">{t('overview')}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="get-started">
        {#if isAuthenticated && username}
            <div class="get-started container">
                <div class="row">
                    <div class="col-md-10">
                        <h2 class="title">{t('hello')}, {username}</h2>
                    </div>
                </div>
            </div>
            <div class="container" style="text-align: center; padding-bottom: 40px">
                <p><a href="/my/" class="button">{t('go-to-area')}</a></p>
            </div>
        {:else}
            <div class="get-started container">
                <div class="row">
                    <div class="col-md-2 pc"
                         style="background: url(/img/get-started.png) no-repeat top center; background-size: contain; min-height: 50px">
                    </div>
                    <div class="col-md-10">
                        <h2 class="title">
                            <img src="/img/get-started.png" style="max-width: 70px; margin-right: 20px" class="mobile" alt="" />
                            {@html t('get-started')}
                        </h2>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        {@html t('get-started-login')}
                        <a href="/my/" class="button" style="float: right">{t('login')}</a>
                    </div>
                    <div class="col-md-6">
                        {@html t('get-started-register')}
                        <a href="/my/register" class="button" style="float: right">{t('register')}</a>
                    </div>
                </div>
            </div>
        {/if}
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

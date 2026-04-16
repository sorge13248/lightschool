<script lang="ts">
    import type { Snippet } from 'svelte';
    import CookieBar from '../components/CookieBar.svelte';
    import { ListIcon } from 'phosphor-svelte';
    import { t } from '../lib/i18n';

    interface Props {
        appName: string;
        locale: string;
        isAuthenticated: boolean;
        username?: string;
        currentUrl: string;
        children: Snippet;
    }

    const { appName, locale, isAuthenticated, username, currentUrl, children }: Props = $props();

    let isMobileMenuOpen = $state(false);

    const languageRedirectUrl = $derived(
        '/language?redirect=' + encodeURIComponent(currentUrl)
    );

    function toggleMobileMenu() {
        isMobileMenuOpen = !isMobileMenuOpen;
    }
</script>

<svelte:head>
    <link rel="stylesheet" href="/css/lightschool.css" />
    <link rel="stylesheet" href="/css/menu.css" />
</svelte:head>

<div id="fra-menu-menu" class="fra-collection-menu" style="text-align: center">
    <button
        type="button"
        class="mobile-menu mobile"
        tabindex={9}
        style="background: none; border: none; cursor: pointer;"
        onclick={toggleMobileMenu}
    >
        <ListIcon weight="light" />
        <span class="menu-mobile-text" style="margin-left: 10px">
            {t('menu-close-mobile')}
        </span>
    </button>

    <a href="/" class="title" tabindex={1} style="font-weight: bold; display: inline-block; text-transform: uppercase">
        <img src="/img/logo.png" style="float: left; max-width: 24px; margin-right: 10px" alt="" />
        {appName}
    </a>

    <a href="/overview" class="pc" tabindex={2}>{t('overview')}</a>
    <a href="/features" class="pc" tabindex={3}>{t('features')}</a>

    {#if isAuthenticated && username}
        <a href="/my/" class="pc" tabindex={4}>{t('hello')}, {username}</a>
    {:else}
        <a href="/my/" class="pc" tabindex={4}>{t('account')}</a>
    {/if}

    <a href={languageRedirectUrl} class="pc" tabindex={5}>
        <img src="/img/language/{locale}.png" style="width: 46px" alt={t('change-language')} />
        <span class="menu-mobile-text" style="margin-left: 10px">{t('change-language')}</span>
    </a>

    <!-- Mobile menu overlay -->
    <div
        id="fra-menu-mobile-menu"
        class="fra-collection-menu-mobile"
        style="display: {isMobileMenuOpen ? '' : 'none'}"
    >
        <a href="/overview" tabindex={2}>{t('overview')}</a>
        <a href="/features" tabindex={3}>{t('features')}</a>

        {#if isAuthenticated && username}
            <a href="/my/" tabindex={4}>{t('hello')}, {username}</a>
        {:else}
            <a href="/my/" tabindex={4}>{t('account')}</a>
        {/if}

        <a href={languageRedirectUrl} tabindex={5}>
            <img src="/img/language/{locale}.png" style="width: 46px; margin-right: 10px" alt="" />
            {t('change-language')}
        </a>
    </div>
</div>

{@render children()}
<CookieBar />

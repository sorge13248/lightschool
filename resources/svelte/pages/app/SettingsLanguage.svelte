<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import type { CurrentUser } from '../../lib/types';
    import { t } from '../../lib/i18n';

    interface LanguageInfo {
        LANG_INT_NAME: string;
        LANG_NAME: string;
    }

    interface Props {
        currentUser: CurrentUser;
        languages: Record<string, LanguageInfo>;
        currentLanguage: string;
        currentUrl: string;
    }

    const { currentUser, languages, currentLanguage, currentUrl }: Props = $props();

    const accentHex    = untrack(() => ((currentUser.accent ?? '#1e6ad3').replace('#', '').replace(/[^0-9a-fA-F]/g, '')));
    const safeAccent   = untrack(() => accentHex.length === 6 ? accentHex : '1e6ad3');
    const ar           = untrack(() => parseInt(safeAccent.substring(0, 2), 16));
    const ag           = untrack(() => parseInt(safeAccent.substring(2, 4), 16));
    const ab           = untrack(() => parseInt(safeAccent.substring(4, 6), 16));
    const currentUrlVal = untrack(() => currentUrl ?? window.location.href);
</script>

<svelte:head><title>{t('settings-language-title')} - {t('app-settings')} - LightSchool</title></svelte:head>

<div class="container content-my settings-app">
    <div style="max-width: 800px; margin: 0 auto; padding: 50px 25px; text-align: center">

        <h2 style="margin-bottom: 10px">{t('settings-language-title')}</h2>
        <p style="color: gray; font-size: 1.05em; margin: 0 auto 48px; max-width: 440px">
            {t('settings-language-desc')}
        </p>

        <div style="display: flex; flex-wrap: wrap; gap: 24px; justify-content: center">
            {#each Object.entries(languages) as [code, info]}
                {@const isActive = currentLanguage === code}
                <a href="/language/set?lang={code}&redirect={encodeURIComponent(currentUrlVal)}"
                   class="accent-all img-change-to-white box-shadow-1-all"
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center;
                          text-decoration: none; color: inherit; width: 180px;
                          padding: 32px 20px; border-radius: 14px;
                          border: {isActive ? `2px solid #${safeAccent}` : ''};
                          background: {isActive ? `rgba(${ar},${ag},${ab},0.07)` : 'transparent'};
                          transition: border-color 0.2s">
                    <img src="/img/language/{code}.png"
                         style="width: 88px; margin-bottom: 20px; border-radius: 6px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.18)"
                         alt={info.LANG_INT_NAME}/>
                    <b style="font-size: 1.1em; margin-bottom: 4px">{info.LANG_INT_NAME}</b>
                    <span style="font-size: 0.9em; opacity: 0.6">{info.LANG_NAME}</span>
                    {#if isActive}
                        <span class="accent-fore" style="margin-top: 14px; font-size: 1.4em; font-weight: bold; line-height: 1">✓</span>
                    {/if}
                </a>
            {/each}
        </div>

    </div>
</div>

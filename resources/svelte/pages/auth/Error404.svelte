<script module>
    import AuthLayout from '../../layouts/AuthLayout.svelte';
    export const layout = AuthLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';

    interface PageData {
        appName: string;
    }

    const { data }: { data: PageData } = $props();
</script>

<svelte:head><title>{t('error-404')} - LightSchool</title></svelte:head>

<div
    style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px"
>
    <h1 style="font-size: 5em; font-weight: 700; color: #1e6ad3; margin-bottom: 0">404</h1>
    <h2 style="margin-top: 8px; color: #333">{t('error-404-title')}</h2>
    <p style="color: gray; margin-bottom: 32px">{t('error-404-description')}</p>
    <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center">
        <a href="/my" class="button accent-bkg-gradient box-shadow-1-all">{t('error-404-go-dashboard')}</a>
        <button type="button" class="button box-shadow-1-all" onclick={() => history.back()}>{t('error-404-go-back')}</button>
    </div>
</div>

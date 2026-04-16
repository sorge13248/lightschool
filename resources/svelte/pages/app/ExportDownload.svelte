<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';

    interface Props {
        token: string;
        status: 'pending' | 'processing' | 'ready' | 'failed' | 'downloaded';
        isExpired: boolean;
        expiresAt: string | null;
        error: string | null;
    }

    const { token, status, isExpired, expiresAt, error }: Props = $props();

    let csrfToken = $state('');
    $effect(() => {
        csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
    });

    function formatDate(iso: string): string {
        try {
            const d = new Date(iso);
            return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        } catch {
            return iso;
        }
    }
</script>

<svelte:head><title>{t('settings-export-title')} - LightSchool</title></svelte:head>

<div class="container content-my settings-app">
    <div style="max-width: 600px; margin: 0 auto; padding-top: 2rem;">
        <h2>{t('settings-export-title')}</h2>

        {#if error}
            <div class="alert alert-danger">{error}</div>
        {/if}

        {#if status === 'pending' || status === 'processing'}
            <div class="alert alert-success">
                <b>{t('settings-export-processing-title')}</b><br/>
                {t('settings-export-processing-desc')}
            </div>

        {:else if status === 'failed'}
            <div class="alert alert-danger">
                <b>{t('export-failed-title')}</b><br/>
                {t('export-failed-desc')}
            </div>

        {:else if status === 'downloaded'}
            <div class="alert alert-danger">
                <b>{t('export-downloaded-title')}</b><br/>
                {t('export-downloaded-desc')}
            </div>

        {:else if isExpired}
            <div class="alert alert-danger">
                <b>{t('export-expired-title')}</b><br/>
                {t('export-expired-desc')}
            </div>

        {:else if status === 'ready'}
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            <p>{@html expiresAt
                ? t('export-ready-desc').replace(':date', `<strong>${formatDate(expiresAt)}</strong>`)
                : t('export-ready-desc')}</p>

            <form method="post" action="/my/export/{token}">
                <input type="hidden" name="_token" value={csrfToken} />
                <label for="password">{t('settings-password-current')}</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="box-shadow-1-all"
                    style="width: 100%; margin-bottom: 1rem;"
                />
                <input
                    type="submit"
                    value={t('settings-export-download')}
                    class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                />
            </form>
        {/if}
    </div>
</div>

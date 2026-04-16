<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';

    interface ActiveExport {
        status: 'pending' | 'processing' | 'ready';
        expires_at: string;
        token: string;
    }

    interface Props {
        activeExport: ActiveExport | null;
        downloadRoute: string;
        requestRoute: string;
    }

    const { activeExport: activeExportRaw, downloadRoute, requestRoute }: Props = $props();

    const downloadRouteInit = untrack(() => downloadRoute);
    const requestRouteInit  = untrack(() => requestRoute);

    let activeExport = $state<ActiveExport | null>(untrack(() => activeExportRaw ?? null));
    let requesting   = $state(false);
    let respText     = $state('');
    let respType     = $state<'success' | 'error' | ''>('');

    function formatDate(iso: string): string {
        try {
            const d = new Date(iso);
            const day   = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year  = d.getFullYear();
            return `${day}/${month}/${year}`;
        } catch {
            return iso;
        }
    }

    async function requestExport(): Promise<void> {
        requesting = true;
        respText   = '';
        respType   = '';

        try {
            const res = await apiFetch(requestRouteInit, 'POST', '');
            respText = res.text ?? '';
            respType = res.response === 'success' ? 'success' : 'error';
            if (res.response === 'success') {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            respType = 'error';
            respText = t('error', 'Error');
        } finally {
            requesting = false;
        }
    }

    const isReady      = $derived(activeExport?.status === 'ready');
    const isProcessing = $derived(
        activeExport?.status === 'pending' || activeExport?.status === 'processing'
    );

    const downloadUrl = $derived(
        activeExport && downloadRouteInit
            ? downloadRouteInit.replace('__TOKEN__', activeExport.token)
            : ''
    );
</script>

<svelte:head><title>{t('settings-export-title')} - {t('app-settings')} - LightSchool</title></svelte:head>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto">
        <div class="row">
            <div class="col-md-5">
                <h3>{t('settings-export-title')}</h3>

                {#if isReady && activeExport}
                    <div class="alert alert-success">
                        <b>{t('settings-export-archive-ready-title')}</b><br/>
                        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                        {@html t('settings-export-archive-ready-desc', 'Your archive is ready and expires on :date.')
                            .replace(':date', `<b>${formatDate(activeExport.expires_at)}</b>`)}
                    </div>
                    <a href={downloadUrl}
                       class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">
                        {t('settings-export-download')}
                    </a>
                {:else if isProcessing}
                    <div class="alert alert-success">
                        <b>{t('settings-export-processing-title')}</b><br/>
                        {t('settings-export-processing-desc')}
                    </div>
                {:else}
                    <p>{t('settings-export-desc')}</p>
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    <p>{@html t('settings-export-valid-for')}</p>

                    <button type="button"
                            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                            disabled={requesting}
                            onclick={requestExport}>
                        {requesting ? t('loading', 'Loading...') : t('settings-export-request')}
                    </button>
                    {#if respText}
                        <div class="alert alert-{respType === 'success' ? 'success' : 'danger'}"
                             style="margin-top: .75rem">
                            {respText}
                        </div>
                    {/if}
                {/if}
            </div>
        </div>
    </div>
</div>

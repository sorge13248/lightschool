<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';

    interface PendingRequest {
        deletion_timestamp: string;
    }

    interface Props {
        pendingRequest: PendingRequest | null;
        exportDataUrl: string;
    }

    const { pendingRequest: pendingRequestRaw, exportDataUrl }: Props = $props();

    const pendingRequestInit = untrack(() => pendingRequestRaw ?? null);

    let pendingRequest = $state<PendingRequest | null>(pendingRequestInit);

    let password    = $state('');
    let saving      = $state(false);
    let respHtml    = $state('');
    let respType    = $state<'success' | 'error' | ''>('');

    let cancelResp     = $state('');
    let cancelRespType = $state<'success' | 'error' | ''>('');

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

    async function handleDeleteRequest(e: Event): Promise<void> {
        e.preventDefault();
        saving = true;
        respHtml = '';
        respType = '';

        try {
            const res = await apiFetch('/api/settings?type=request-deletion', 'POST',
                `password=${encodeURIComponent(password)}`);
            respHtml = res.text ?? '';
            respType = res.response === 'success' ? 'success' : 'error';
            if (res.response === 'success') {
                setTimeout(() => {
                    window.location.href = (res['redirect-url'] as string) ?? '/auth/login';
                }, 1500);
            }
        } catch {
            respType = 'error';
            respHtml = t('error', 'Error');
        } finally {
            saving = false;
        }
    }

    async function cancelDeletion(): Promise<void> {
        if (!confirm(t('settings-delete-cancel-confirm', 'Cancel the deletion request?'))) return;
        cancelResp     = '';
        cancelRespType = '';

        try {
            const res = await apiFetch('/api/settings?type=cancel-deletion', 'POST', '');
            cancelResp     = res.text ?? '';
            cancelRespType = res.response === 'success' ? 'success' : 'error';
            if (res.response === 'success') {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch {
            cancelRespType = 'error';
            cancelResp = t('error', 'Error');
        }
    }
</script>

<svelte:head><title>{t('settings-delete-title')} - {t('app-settings')} - LightSchool</title></svelte:head>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto">
        <div class="row">
            <div class="col-md-5">
                <h3>{t('settings-delete-title')}</h3>

                {#if !pendingRequest}
                    <div class="alert alert-warning">
                        <b>{t('settings-delete-warning-title')}</b><br/>
                        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                        {@html t('settings-delete-warning-desc')}
                    </div>

                    <p>
                        {t('settings-delete-before-proceeding')}
                        <a href={exportDataUrl}>{t('settings-delete-export-link')}</a>.
                    </p>

                    <form method="post" action="/api/settings?type=request-deletion"
                          class="form-delete-account"
                          onsubmit={handleDeleteRequest}>
                        <label for="password">{t('settings-delete-confirm-password')}</label>
                        <input type="password" id="password" name="password"
                               class="box-shadow-1-all"
                               autocomplete="current-password"
                               placeholder={t('settings-password-current')}
                               bind:value={password}/>
                        <br/><br/>
                        <input type="submit"
                               value={t('settings-delete-submit')}
                               disabled={saving}
                               style="background: #c0392b; color: #fff; border: none; float: right"
                               class="button box-shadow-1-all"/>
                        <div style="clear: both"></div>
                        {#if respHtml}
                            <div class="response alert alert-{respType === 'success' ? 'success' : 'danger'}"
                                 style="margin-top: 10px">
                                {respHtml}
                            </div>
                        {/if}
                    </form>
                {:else}
                    <div class="alert alert-danger">
                        <b>{t('settings-delete-pending-title')}</b><br/>
                        {t('settings-delete-pending-desc', 'Your account will be deleted on :date.')
                            .replace(':date', formatDate(pendingRequest.deletion_timestamp))}
                    </div>

                    <button type="button" onclick={cancelDeletion}
                            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">
                        {t('settings-delete-cancel')}
                    </button>
                    {#if cancelResp}
                        <div class="alert alert-{cancelRespType === 'success' ? 'success' : 'danger'}"
                             style="margin-top: 10px">
                            {cancelResp}
                        </div>
                    {/if}
                {/if}
            </div>
        </div>
    </div>
</div>

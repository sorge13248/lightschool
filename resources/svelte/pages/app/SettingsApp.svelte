<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';
    import { notifications } from '../../stores/notifications.svelte';
    import Modal from '../../components/ui/Modal.svelte';
    import { PlusIcon, XIcon } from 'phosphor-svelte';

    interface AppItem {
        id: number;
        unique_name: string;
    }

    interface Props {
        apps: AppItem[];
    }

    const { apps: appsRaw }: Props = $props();

    const apps = untrack(() => appsRaw ?? []);

    let selectedApp    = $state<AppItem | null>(null);
    let showAppModal   = $state(false);
    let showEraseModal = $state(false);

    function openApp(app: AppItem): void {
        selectedApp  = app;
        showAppModal = true;
    }

    async function addRemoveTaskbar(): Promise<void> {
        if (!selectedApp) return;
        try {
            const res = await apiFetch(`/api/settings?type=app-to-taskbar&app=${encodeURIComponent(selectedApp.unique_name)}`);
            notifications.add(res.text, { type: res.response === 'success' ? 'success' : 'error' });
        } catch {
            notifications.add(t('error', 'Error'), { type: 'error' });
        }
    }

    async function eraseAppData(): Promise<void> {
        if (!selectedApp) return;
        showEraseModal = false;
        try {
            const res = await apiFetch(`/api/settings?type=erase-app-data&app=${encodeURIComponent(selectedApp.unique_name)}`);
            notifications.add(res.text, { type: res.response === 'success' ? 'success' : 'error' });
        } catch {
            notifications.add(t('error', 'Error'), { type: 'error' });
        }
    }
</script>

<svelte:head><title>App - {t('app-settings')} - LightSchool</title></svelte:head>

<style>
    .app-detail :global(.icon) { width: 100%; max-width: 100%; text-align: left; }
    .app-detail :global(.icon img) { float: left; width: 24px; height: 24px; margin-right: 10px; }
</style>

<div class="container content-my settings-app">

    {#each apps as app (app.unique_name)}
        <!-- svelte-ignore a11y_invalid_attribute -->
        <a href="#"
           class="icon selectable accent-all box-shadow-1-all"
           onclick={(e) => { e.preventDefault(); openApp(app); }}>
            <img src="/img/app-icons/{app.unique_name}/white/icon.png"
                 style="width: 16px; height: 16px; margin-right: 10px"
                 alt={t('app-' + app.unique_name)}/>
            {t('app-' + app.unique_name)}
        </a>
    {/each}

</div>

<!-- App detail modal -->
<Modal
    open={showAppModal}
    title={selectedApp ? t('app-' + selectedApp.unique_name) : ''}
    maxWidth="540px"
    draggable
    onclose={() => { showAppModal = false; }}
>
    {#if selectedApp}
        <div class="app-detail">
            <h3>
                <img src="/img/app-icons/{selectedApp.unique_name}/white/icon.png"
                     style="width: 32px; height: 32px; float: left; margin-right: 20px; margin-top: 5px"
                     alt={t('app-' + selectedApp.unique_name)}/>
                <span>{t('app-' + selectedApp.unique_name)}</span>
            </h3>
            <div style="clear: both"></div>
            <br/>
            <div class="row">
                <div class="col-md-12">
                    <!-- svelte-ignore a11y_invalid_attribute -->
                    <a href="#"
                       class="icon img-change-to-white accent-all box-shadow-1-all"
                       onclick={(e) => { e.preventDefault(); addRemoveTaskbar(); }}>
                        <PlusIcon weight="light" />
                        {t('settings-app-add-remove-taskbar')}
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <!-- svelte-ignore a11y_invalid_attribute -->
                    <a href="#"
                       class="icon img-change-to-white accent-all box-shadow-1-all"
                       onclick={(e) => { e.preventDefault(); showEraseModal = true; }}>
                        <XIcon weight="light" />
                        {t('settings-app-clear-data')}
                    </a>
                </div>
            </div>
        </div>
    {/if}
</Modal>

<!-- Erase confirmation modal -->
<Modal
    open={showEraseModal}
    title={t('settings-app-clear-data')}
    maxWidth="400px"
    draggable
    onclose={() => { showEraseModal = false; }}
>
    <p>{t('settings-app-clear-data-confirm')}</p>
    <button
        type="button"
        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        style="float: right"
        onclick={eraseAppData}
    >
        {t('confirm', 'Confirm')}
    </button>
    <div style="clear: both"></div>
</Modal>

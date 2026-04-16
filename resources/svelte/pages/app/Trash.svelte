<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';
    import { notifications } from '../../stores/notifications.svelte';
    import Modal from '../../components/ui/Modal.svelte';

    interface TrashItem {
        id: string;
        name: string;
        icon: string;
        secondRow?: string;
        diary_color?: string;
        style?: string;
    }

    let items       = $state<TrashItem[]>([]);
    let loading     = $state(true);
    let loadMoreVis = $state(false);
    let submitting  = $state(false);

    let restoreItem = $state<TrashItem | null>(null);
    let deleteItem  = $state<TrashItem | null>(null);
    let emptyOpen   = $state(false);
    let restoreErr  = $state('');
    let deleteErr   = $state('');
    let emptyErr    = $state('');

    async function loadTrash(start = 0): Promise<void> {
        try {
            const data = await apiFetch(`/api/file-manager?type=list-trash&start=${start}`);
            const trash = (Array.isArray(data) ? data : []) as TrashItem[];
            if (start === 0) loading = false;
            items = start === 0 ? trash : [...items, ...trash];
            loadMoreVis = trash.length >= 20;
        } catch {
            loading = false;
        }
    }

    async function doRestore(): Promise<void> {
        if (!restoreItem || submitting) return;
        submitting = true;
        const id = restoreItem.id;
        try {
            const res = await apiFetch(`/api/file-manager?type=restore&id=${id}`, 'POST', '');
            if (res.response === 'success') {
                items = items.filter(i => i.id !== id);
                restoreItem = null;
                notifications.add(res.text, { type: 'success' });
            } else {
                restoreErr = res.text;
            }
        } catch {
            restoreErr = t('error', 'Error');
        } finally { submitting = false; }
    }

    async function doDelete(): Promise<void> {
        if (!deleteItem || submitting) return;
        submitting = true;
        const id = deleteItem.id;
        try {
            const res = await apiFetch(`/api/file-manager?type=delete&id=${id}`, 'POST', '');
            if (res.response === 'success') {
                items = items.filter(i => i.id !== id);
                deleteItem = null;
                notifications.add(res.text, { type: 'success' });
            } else {
                deleteErr = res.text;
            }
        } catch {
            deleteErr = t('error', 'Error');
        } finally { submitting = false; }
    }

    async function doEmpty(): Promise<void> {
        if (submitting) return;
        submitting = true;
        try {
            const res = await apiFetch('/api/file-manager?type=empty', 'POST', '');
            if (res.response === 'success') {
                items = []; emptyOpen = false; loadMoreVis = false;
                notifications.add(res.text, { type: 'success' });
            } else {
                emptyErr = res.text;
            }
        } catch {
            emptyErr = t('error', 'Error');
        } finally { submitting = false; }
    }

    $effect(() => { loadTrash(); });
</script>

<svelte:head><title>Cestino - LightSchool</title></svelte:head>

<div class="container content-my trash">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">
                Cestino ({items.length} elementi)
                {#if !loading && items.length > 0}
                    <button type="button"
                        class="button small accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                        onclick={() => { emptyOpen = true; emptyErr = ''; }}
                    >Svuota</button>
                {/if}
            </li>
        </ol>
    </nav>

    <div class="folder-view">
        {#if loading}
            <div class="loading ph-item">
                <div class="ph-col-12">
                    <div class="ph-row">
                        <div class="ph-col-6 big"></div><div class="ph-col-4 empty big"></div>
                        <div class="ph-col-4"></div><div class="ph-col-8 empty"></div>
                        <div class="ph-col-6"></div><div class="ph-col-6 empty"></div>
                        <div class="ph-col-12" style="margin-bottom: 0"></div>
                    </div>
                </div>
            </div>
        {:else if items.length === 0}
            <p style="color: gray">Nessun elemento presente nel cestino.</p>
        {:else}
            <div class="items">
                {#each items as item (item.id)}
                    <!-- svelte-ignore a11y_invalid_attribute -->
                    <a
                        href="#"
                        class="icon img-change-to-white accent-all box-shadow-1-all"
                        style={item.diary_color ? `color: #${item.diary_color} !important` : ''}
                        title={item.name}
                        onclick={(e) => { e.preventDefault(); restoreItem = item; restoreErr = ''; }}
                    >
                        <img src={item.icon} style="float: left; {item.style ?? ''}" alt="" />
                        <span class="text-ellipsis" style="display: block; font-size: 1.2em">{item.name}</span>
                        {#if item.secondRow}<small class="second-row">{item.secondRow}</small>{/if}
                    </a>
                {/each}
            </div>
        {/if}

        {#if loadMoreVis}
            <div style="text-align: center">
                <button type="button" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                    onclick={() => loadTrash(items.length)}>Mostra più elementi</button>
            </div>
        {/if}
    </div>
</div>

<Modal open={restoreItem !== null} title="Ripristina file" maxWidth="522px" draggable
    onclose={() => { restoreItem = null; restoreErr = ''; }}>
    {#if restoreItem}
        <p>Vuoi ripristinare il file <strong>{restoreItem.name}</strong>?</p>
        {#if restoreErr}<div class="alert alert-danger" style="margin-bottom:10px">{restoreErr}</div>{/if}
        <input type="submit" value="Ripristina" style="float: right"
            class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            disabled={submitting} onclick={doRestore} />
        <div style="clear: both"></div>
    {/if}
</Modal>

<Modal open={deleteItem !== null} title="Elimina file" maxWidth="522px" draggable
    onclose={() => { deleteItem = null; deleteErr = ''; }}>
    {#if deleteItem}
        <p>Vuoi eliminare il file <strong>{deleteItem.name}</strong>?</p>
        {#if deleteErr}<div class="alert alert-danger" style="margin-bottom:10px">{deleteErr}</div>{/if}
        <input type="submit" value="Elimina definitivamente" style="float: right"
            class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            disabled={submitting} onclick={doDelete} />
        <div style="clear: both"></div>
    {/if}
</Modal>

<Modal open={emptyOpen} title="Svuota cestino" maxWidth="522px" draggable
    onclose={() => { emptyOpen = false; emptyErr = ''; }}>
    <p>Vuoi veramente svuotare il cestino?</p>
    <p class="small">L'operazione eliminerà tutti i file contenuti nel cestino. L'operazione è irreversibile.</p>
    {#if emptyErr}<div class="alert alert-danger" style="margin-bottom:10px">{emptyErr}</div>{/if}
    <input type="submit" value="Svuota cestino" style="float: right"
        class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        disabled={submitting} onclick={doEmpty} />
    <div style="clear: both"></div>
</Modal>

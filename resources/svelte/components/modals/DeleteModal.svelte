<script lang="ts">
    import { onMount } from 'svelte';
    import { apiFetch } from '../../lib/api';
    import { t } from '../../lib/i18n';
    import { notifications } from '../../stores/notifications.svelte';
    import Modal from '../ui/Modal.svelte';

    let open      = $state(false);
    let fileId    = $state('');
    let fileName  = $state('');
    let inTrash   = $state(false);
    let mode      = $state<'move_to_trash' | 'delete_completely'>('move_to_trash');
    let error     = $state('');
    let loading   = $state(false);

    async function handleDelete(): Promise<void> {
        loading = true;
        error   = '';
        try {
            const res = await apiFetch(
                '/api/file-manager?type=delete&id=' + fileId,
                'POST', 'delete_mode=' + mode,
            );
            if (res.response === 'success') {
                open = false;
                window.dispatchEvent(new CustomEvent('cm-file-removed', { detail: { fileId } }));
                notifications.add(res.text, { autoClose: 2000 });
            } else {
                error = res.text;
            }
        } catch { error = 'Error'; }
        finally { loading = false; }
    }

    onMount(() => {
        const handler = (e: Event): void => {
            const d = (e as CustomEvent).detail as { fileId: string; fileName: string; inTrash?: boolean };
            fileId   = d.fileId;
            fileName = d.fileName;
            inTrash  = !!d.inTrash;
            mode     = 'move_to_trash';
            error    = '';
            open     = true;
        };
        window.addEventListener('cm-delete', handler);
        return () => window.removeEventListener('cm-delete', handler);
    });
</script>

<Modal
    open={open}
    title={t('delete', 'Elimina') + ' "' + fileName + '"'}
    maxWidth="450px"
    draggable
    onclose={() => { open = false; }}
>
    {#snippet children()}
        <p style="margin-bottom: 0; padding-bottom: 0">
            {t('delete-confirm', 'Vuoi eliminare il file')} "{fileName}"?
        </p>
        <form onsubmit={(e) => { e.preventDefault(); void handleDelete(); }}>
            {#if !inTrash}
                <label>
                    <input type="radio" name="delete_mode" value="move_to_trash"
                           checked={mode === 'move_to_trash'}
                           onchange={() => (mode = 'move_to_trash')} />
                    {t('move-to-trash', 'Sposta nel cestino')}
                </label>
            {/if}
            <label>
                <input type="radio" name="delete_mode" value="delete_completely"
                       checked={mode === 'delete_completely' || inTrash}
                       onchange={() => (mode = 'delete_completely')} />
                {t('delete-permanent', 'Elimina definitivamente')}
            </label>
            <input type="submit" value={t('confirm', 'Conferma')} style="float: right"
                   class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                   disabled={loading} />
            <br />
            {#if error}
                <div class="response alert alert-danger" style="clear: both; margin-top: 10px">{error}</div>
            {/if}
        </form>
    {/snippet}
</Modal>

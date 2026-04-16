<script lang="ts">
    import { onMount } from 'svelte';
    import { apiFetch } from '../../lib/api';
    import { t } from '../../lib/i18n';
    import { notifications } from '../../stores/notifications.svelte';
    import Modal from '../ui/Modal.svelte';

    let open      = $state(false);
    let fileId    = $state('');
    let fileName  = $state('');
    let fileType  = $state('');
    let code      = $state('');
    let editable  = $state(false);
    let error     = $state('');
    let loading   = $state(false);

    async function handleSubmit(): Promise<void> {
        loading = true;
        error   = '';
        const body = 'project=' + encodeURIComponent(code) + '&editable=' + (editable ? '1' : '0');
        try {
            const res = await apiFetch('/api/project?type=project&file=' + fileId, 'POST', body);
            if (res.response === 'success') {
                open = false;
                notifications.add(res.text, { autoClose: 2000 });
            } else {
                error = res.text;
            }
        } catch { error = 'Error'; }
        finally { loading = false; }
    }

    onMount(() => {
        const handler = (e: Event): void => {
            const d = (e as CustomEvent).detail as { fileId: string; fileType: string; fileName?: string };
            fileId   = d.fileId;
            fileType = d.fileType;
            fileName = d.fileName ?? '';
            code     = '';
            editable = false;
            error    = '';
            open     = true;
        };
        window.addEventListener('cm-project', handler);
        return () => window.removeEventListener('cm-project', handler);
    });
</script>

<Modal
    open={open}
    title={t('project', 'Proietta') + (fileName ? ' "' + fileName + '"' : '')}
    maxWidth="450px"
    draggable
    onclose={() => { open = false; }}
>
    {#snippet children()}
        <p>Inserisci il codice della sessione a cui vuoi aggiungere il file.</p>
        <form onsubmit={(e) => { e.preventDefault(); void handleSubmit(); }}>
            <input type="text" name="project" placeholder="Codice sessione"
                   autocomplete="off" class="box-shadow-1-all"
                   bind:value={code}
                   style="width: calc(100% - 10px); text-transform: uppercase; letter-spacing: 0.1em; font-family: monospace" />
            <br />
            {#if fileType === 'notebook'}
                <label class="editable-label">
                    <input type="checkbox" name="editable" bind:checked={editable} />
                    Modificabile
                </label>
            {/if}
            <input type="submit" value="Proietta"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                   style="float: right; margin-top: 8px"
                   disabled={loading} />
            {#if error}
                <div class="response alert alert-danger" style="clear: both; margin-top: 10px">{error}</div>
            {/if}
        </form>
    {/snippet}
</Modal>

<script lang="ts">
    import { onMount } from 'svelte';
    import { XIcon, FolderIcon, BookOpenIcon, BookIcon, FileIcon } from 'phosphor-svelte';
    import { apiFetch } from '../../lib/api';
    import { notifications } from '../../stores/notifications.svelte';
    import { themePreview } from '../../stores/themePreview.svelte';
  import { slide } from 'svelte/transition';

    interface FileDetails {
        id: string;
        name: string;
        type: 'folder' | 'notebook' | 'file' | 'diary';
        file_type?: string;
        file_size_human?: string;
        file_exists?: boolean;
        create_date?: string;
        last_edit?: string;
        diary_date?: string;
        diary_reminder?: string;
        diary_type?: string;
    }

    let fileId = $state<string | null>(null);
    let loading = $state(false);
    let file = $state<FileDetails | null>(null);

    $effect(() => {
        if (fileId) {
            loadDetails(fileId);
        } else {
            file = null;
        }
    });

    onMount(() => {
        const handler = (e: Event): void => {
            const id = (e as CustomEvent).detail?.fileId as string | undefined;
            if (id) fileId = id;
        };
        window.addEventListener('cm-property', handler);
        return () => window.removeEventListener('cm-property', handler);
    });

    async function loadDetails(id: string): Promise<void> {
        loading = true;
        file = null;
        try {
            const res = await apiFetch('/api/file-manager?type=details&id=' + id);
            if (res['response'] === 'success') {
                file = res['file'] as FileDetails;
            }
        } catch {
            // silent
        } finally {
            loading = false;
        }
    }

    const iconMap = { folder: FolderIcon, notebook: BookOpenIcon, diary: BookIcon } as Record<string, typeof FileIcon>;
    const FileIconComp = $derived(file ? (iconMap[file.type] ?? FileIcon) : FileIcon);

    function formatDate(dt: string | undefined): string {
        if (!dt) return '—';
        return dt.substring(0, 16).replace('T', ' ');
    }

    function typeLabel(type: string): string {
        const map: Record<string, string> = { folder: 'Cartella', notebook: 'Quaderno', file: 'File', diary: 'Evento diario' };
        return map[type] ?? type;
    }

    async function setProfilePicture(): Promise<void> {
        if (!fileId) return;
        try {
            const res = await apiFetch('/api/file-manager?type=set-profile-picture&id=' + fileId, 'POST', '');
            if (res['response'] === 'success') {
                const closedId = fileId;
                fileId = null;
                window.dispatchEvent(new CustomEvent('cm-profile-picture-changed', { detail: { fileId: closedId } }));
            }
            notifications.add(res['text'] as string, { type: res['response'] === 'success' ? '' : 'error', autoClose: 2000 });
        } catch {
            // silent
        }
    }

    async function setWallpaper(): Promise<void> {
        if (!fileId) return;
        try {
            const res = await apiFetch('/api/file-manager?type=set-wallpaper&id=' + fileId, 'POST', '');
            if (res['response'] === 'success') {
                const closedId = fileId;
                fileId = null;
                themePreview.wallpaperFileId  = closedId;
                themePreview.wallpaperBlur    = 0;
                themePreview.wallpaperColor   = 'background-color: rgba(0,0,0,0)';
                themePreview.wallpaperOpacity = 0;
                window.dispatchEvent(new CustomEvent('cm-wallpaper-changed', { detail: { fileId: closedId } }));
            }
            notifications.add(res['text'] as string, { type: res['response'] === 'success' ? '' : 'error', autoClose: 2000 });
        } catch {
            // silent
        }
    }
</script>

{#if fileId}
<div class="property-panel accent-bkg-gradient"
    transition:slide={{ duration: 300, axis: 'x' }}>
    <p class="mobile-block"><br/></p>
    <button type="button" title="Chiudi" class="close" onclick={() => { fileId = null; }}>
        <XIcon weight="light" style="float: right; font-size: 16px" />
    </button>
    <div style="clear: both"></div>

    {#if loading || !file}
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-6 big"></div>
                    <div class="ph-col-4 empty big"></div>
                    <div class="ph-col-4"></div>
                    <div class="ph-col-8 empty"></div>
                    <div class="ph-col-6"></div>
                    <div class="ph-col-6 empty"></div>
                    <div class="ph-col-12" style="margin-bottom: 0"></div>
                </div>
            </div>
        </div>
    {:else}
        <div class="structure">
            <div class="row">
                <div class="col-md-2 file-icon-col">
                    <FileIconComp weight="light" size={48} style="float: left; margin-right: 20px; margin-top: 15px" />
                </div>
                <div class="col-md-10 file-title-col">
                    <h2 class="file-title" style="margin-bottom: 0; word-break: break-all">
                        {file.type === 'diary' && file.diary_type ? file.diary_type + ' di ' : ''}{file.name}
                    </h2>
                    <p class="file-type" style="margin-top: 0">{typeLabel(file.type)}</p>
                </div>
                <div class="col-md-12">
                    <p class="create-date"><b>Data di creazione:</b> {formatDate(file.create_date)}</p>
                </div>

                {#if file.type === 'notebook'}
                    <div class="col-md-12">
                        <p class="last-edit"><b>Ultima modifica:</b> {formatDate(file.last_edit)}</p>
                    </div>
                {/if}

                {#if file.type === 'diary'}
                    <div class="col-md-12">
                        <p class="date"><b>Data:</b> {file.diary_date ?? '—'}</p>
                        <p class="reminder"><b>Promemoria:</b> {file.diary_reminder ?? 'Mai'}</p>
                    </div>
                {/if}

                {#if file.type === 'file'}
                    <div class="col-md-12">
                        <p class="file-size"><b>Dimensione:</b> {file.file_size_human ?? '—'}</p>
                        <p>
                            <a href="/api/file/{fileId}"
                               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker download"
                               style="width: 100%"
                               download>Scarica</a>
                        </p>
                        {#if file.file_type?.startsWith('image/')}
                            <p style="padding-top: 10px">
                                <b>Utilizza come:</b>
                                <button type="button"
                                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker set-profile-picture"
                                   style="width: 100%"
                                   onclick={setProfilePicture}>Immagine profilo</button><br/>
                                <button type="button"
                                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker set-wallpaper"
                                   style="width: 100%"
                                   onclick={setWallpaper}>Sfondo</button>
                            </p>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</div>
{/if}

<style lang="scss">
    button.close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        float: right;
        font-size: 1.2rem;
        line-height: 1;
    }

    .property-panel {
        background-color: rgba(0, 0, 0, 0.8);
        box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
        width: 100%;
        max-width: 400px;
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        color: #fff;
        padding: 20px;
        z-index: 50000;
    }
</style>

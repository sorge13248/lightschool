<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { apiFetch } from '../../lib/api';
    import { MonitorIcon, XIcon, CopyIcon, ArrowsClockwiseIcon, ArrowClockwiseIcon, ArrowLeftIcon, FolderIcon, BookOpenIcon, CalendarBlankIcon, FileIcon } from 'phosphor-svelte';

    const FILE_ICONS: Record<string, typeof FileIcon> = {
        folder: FolderIcon, notebook: BookOpenIcon, diary: CalendarBlankIcon, file: FileIcon,
    };

    interface ProjectFile {
        id: string;
        name: string;
        type: string;
        icon?: string;
        editable?: boolean;
        project?: string;
        user?: { name: string; surname?: string };
    }

    type Screen = 'welcome' | 'projection';

    let screen       = $state<Screen>('welcome');
    let projectCode  = $state('——————');
    let yourFiles    = $state<ProjectFile[]>([]);
    let projFiles    = $state<ProjectFile[]>([]);
    let loadingYours = $state(true);
    let loadingProj  = $state(false);
    let errorProj    = $state('');

    async function loadCode(): Promise<void> {
        const data = await apiFetch('/api/project?type=code');
        if (data.response === 'success') projectCode = (data.code as string) ?? '——————';
    }

    async function loadYourFiles(): Promise<void> {
        loadingYours = true;
        try {
            const data = await apiFetch('/api/project?type=your-files');
            yourFiles = (data.files as ProjectFile[]) ?? [];
        } finally { loadingYours = false; }
    }

    async function loadProjectionFiles(): Promise<void> {
        loadingProj = true; errorProj = '';
        try {
            const data = await apiFetch('/api/project?type=files');
            projFiles = Array.isArray(data) ? (data as ProjectFile[]) : [];
        } catch {
            errorProj = 'Errore durante il caricamento dei file.';
        } finally { loadingProj = false; }
    }

    async function enterProjection(): Promise<void> {
        screen = 'projection';
        await Promise.all([loadCode(), loadProjectionFiles()]);
        history.pushState({ screen: 'projection' }, '');
    }

    function exitProjection(): void {
        screen = 'welcome';
        history.back();
    }

    async function changeCode(): Promise<void> {
        const data = await apiFetch('/api/project?type=new-code', 'POST', '');
        if (data.response === 'success') projectCode = (data.code as string) ?? projectCode;
    }

    async function copyCode(): Promise<void> {
        try { await navigator.clipboard.writeText(projectCode); } catch { /* ignore */ }
    }

    async function stopFile(f: ProjectFile): Promise<void> {
        const res = await apiFetch(`/api/project?type=stop&file=${f.id}&project=${encodeURIComponent(f.project ?? '')}`, 'POST', '');
        if (res.response === 'success') await loadYourFiles();
    }

    $effect(() => {
        loadYourFiles();

        const onPop = (e: PopStateEvent) => {
            if ((e.state as { screen?: string } | null)?.screen !== 'projection') screen = 'welcome';
        };
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    });
</script>

<svelte:head><title>Project - LightSchool</title></svelte:head>

<style lang="scss">
    .sidebar {
        box-shadow: 0 0 0.3cm rgba(0,0,0,0.15);
        border-radius: 8px;
        padding: 20px;
        text-align: center;

        .code-display {
            font-size: 2em;
            font-weight: bold;
            font-family: monospace;
            letter-spacing: 0.2em;
            display: block;
            margin: 10px 0 16px;
        }

        button { display: block; width: 100%; margin-bottom: 8px; }
    }

    .file-card {
        display: inline-block;
        vertical-align: top;
        width: 160px;
        margin: 8px;
        padding: 14px 10px;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        word-break: break-word;

        .file-name { font-size: 0.9em; font-weight: bold; display: block; }
        .file-meta { font-size: 0.75em; color: gray; display: block; margin-top: 4px; }
    }

    .your-file-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        margin-bottom: 6px;
        border-radius: 6px;

        .file-name { flex: 1; margin: 0 10px; }
    }
</style>

<div class="container content-my project">

    <!-- Welcome screen -->
    {#if screen === 'welcome'}
        <div id="welcome-screen">
            <div class="row">
                <div class="col-sm-6" style="padding: 20px">
                    <h2 style="margin-top: 0">Proiezione</h2>
                    <p>La modalità proiezione ti permette di mostrare i tuoi file agli altri utenti che condividono il tuo codice. Condividi il codice con i tuoi compagni o colleghi per iniziare.</p>
                    <button type="button" class="button accent-bkg-gradient box-shadow-1-all" onclick={enterProjection}>
                        <MonitorIcon weight="light" /> Entra in modalità proiezione
                    </button>
                </div>
                <div class="col-sm-6" style="padding: 20px">
                    <h2 style="margin-top: 0">Stai proiettando</h2>
                    {#if loadingYours}
                        <p style="color: gray">Caricamento...</p>
                    {:else if yourFiles.length === 0}
                        <p style="color: gray">Non stai proiettando nessun file. Aggiungi file alla proiezione dal File Manager.</p>
                    {:else}
                        {#each yourFiles as f (f.id)}
                            {@const YourFileIcon = FILE_ICONS[f.icon ?? f.type] ?? FileIcon}
                            <div class="your-file-row box-shadow-1-all">
                                <YourFileIcon weight="light" />
                                <span class="file-name">
                                    {f.name ?? 'Senza nome'}
                                    <small style="color: gray"> ({f.editable ? 'modificabile' : 'sola lettura'})</small>
                                </span>
                                <button type="button" class="button" title="Rimuovi dalla proiezione" onclick={() => stopFile(f)}>
                                    <XIcon weight="light" />
                                </button>
                            </div>
                        {/each}
                    {/if}
                </div>
            </div>
        </div>

    <!-- Projection mode -->
    {:else}
        <div id="projection-mode">
            <div class="row">
                <div class="col-sm-3">
                    <div class="sidebar">
                        <h4 style="margin-top: 0">Codice sessione</h4>
                        <span class="code-display">{projectCode}</span>
                        <button type="button" class="button box-shadow-1-all" onclick={copyCode}>
                            <CopyIcon weight="light" /> Copia
                        </button>
                        <button type="button" class="button box-shadow-1-all" onclick={changeCode}>
                            <ArrowsClockwiseIcon weight="light" /> Cambia codice
                        </button>
                        <button type="button" class="button box-shadow-1-all" onclick={loadProjectionFiles}>
                            <ArrowClockwiseIcon weight="light" /> Ricarica file
                        </button>
                        <hr />
                        <button type="button" class="button box-shadow-1-all" onclick={exitProjection}>
                            <ArrowLeftIcon weight="light" /> Esci
                        </button>
                    </div>
                </div>
                <div class="col-sm-9">
                    {#if loadingProj}
                        <p style="color: gray">Caricamento file...</p>
                    {:else if errorProj}
                        <p style="color: red">{errorProj}</p>
                    {:else if projFiles.length === 0}
                        <p style="color: gray">Nessun file in questa sessione.</p>
                    {:else}
                        <div id="projection-files">
                            {#each projFiles as f (f.id)}
                                {@const readerType = f.type === 'diary' ? 'diary' : f.type === 'notebook' ? 'notebook' : 'file'}
                                {@const ProjFileIcon = FILE_ICONS[f.icon ?? f.type] ?? FileIcon}
                                <a href="/my/app/reader/{readerType}/{f.id}"
                                    class="file-card box-shadow-1-all img-change-to-white accent-bkg-all-darker">
                                    <ProjFileIcon weight="light" />
                                    <span class="file-name">{f.name ?? 'Senza nome'}</span>
                                    {#if f.user?.name}
                                        <span class="file-meta">
                                            {f.user.name} {f.user.surname ?? ''}{f.editable ? ' · modificabile' : ''}
                                        </span>
                                    {/if}
                                </a>
                            {/each}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    {/if}

</div>

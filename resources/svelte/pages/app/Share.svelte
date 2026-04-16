<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { onMount } from 'svelte';
    import { apiFetch } from '../../lib/api';

    interface ShareItem {
        file_id: string;
        name: string;
        type: string;
    }

    const TYPE_ICON: Record<string, string> = {
        folder:   'fa-folder',
        notebook: 'fa-book-open',
        diary:    'fa-book',
        file:     'fa-file',
    };

    let sharedWithMe  = $state<ShareItem[]>([]);
    let iAmSharing    = $state<ShareItem[]>([]);
    let loadingWith   = $state(true);
    let loadingMine   = $state(true);
    let moreWith      = $state(false);
    let moreMine      = $state(false);
    let errorWith     = $state('');
    let errorMine     = $state('');

    function readerUrl(item: ShareItem): string {
        const type = item.type === 'folder' ? 'file' : item.type;
        return `/my/app/reader/${type}/${item.file_id}`;
    }

    async function loadSection(
        apiType: string,
        start: number,
        setter: (v: ShareItem[]) => void,
        current: ShareItem[],
        setLoading: (v: boolean) => void,
        setMore: (v: boolean) => void,
        setError: (v: string) => void
    ): Promise<void> {
        try {
            const res = await apiFetch(`/api/share?type=${apiType}&start=${start}`);
            if (res.response === 'error') { setError(res.text); return; }
            const shares = (res.shares as ShareItem[]) ?? [];
            setter(start === 0 ? shares : [...current, ...shares]);
            setMore(shares.length >= 20);
        } catch {
            setError('Errore durante il caricamento.');
        } finally {
            setLoading(false);
        }
    }

    onMount(() => {
        loadSection('get-sharing', 0,
            v => { sharedWithMe = v; },
            sharedWithMe,
            v => { loadingWith = v; },
            v => { moreWith = v; },
            v => { errorWith = v; }
        );
        loadSection('get-shared', 0,
            v => { iAmSharing = v; },
            iAmSharing,
            v => { loadingMine = v; },
            v => { moreMine = v; },
            v => { errorMine = v; }
        );
    });
</script>

<svelte:head><title>Condivisioni - LightSchool</title></svelte:head>

<div class="container content-my share">
    <div class="row">

        <!-- File condivisi con me -->
        <div class="col-md-6">
            <h4 style="margin-bottom: 10px">File condivisi con me</h4>
            <div class="section get-sharing">
                {#if loadingWith}
                    <div class="loading ph-item">
                        <div class="ph-col-12">
                            <div class="ph-row">
                                <div class="ph-col-6 big"></div><div class="ph-col-4 empty big"></div>
                                <div class="ph-col-4"></div><div class="ph-col-8 empty"></div>
                                <div class="ph-col-12" style="margin-bottom:0"></div>
                            </div>
                        </div>
                    </div>
                {:else if errorWith}
                    <div class="alert alert-danger"><h4>Errore</h4><p>{errorWith}</p></div>
                {:else if sharedWithMe.length === 0}
                    <p style="color: gray">Nessun elemento.</p>
                {:else}
                    <div class="items">
                        {#each sharedWithMe as s (s.file_id)}
                            <a href={readerUrl(s)}
                                class="icon img-change-to-white accent-all box-shadow-1-all"
                                style="display: inline-block"
                                title={s.name}>
                                <i class="fa-solid {TYPE_ICON[s.type] ?? 'fa-file'}"
                                    style="float: left; font-size: 24px; margin-right: 8px; margin-top: 2px"></i>
                                <span class="text-ellipsis" style="display: block; font-size: 1.2em">{s.name}</span>
                                <small class="second-row">{s.type}</small>
                            </a>
                        {/each}
                    </div>
                {/if}
                {#if moreWith}
                    <div style="text-align: center">
                        <button type="button" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                            onclick={() => loadSection('get-sharing', sharedWithMe.length,
                                v => { sharedWithMe = v; }, sharedWithMe,
                                v => { loadingWith = v; }, v => { moreWith = v; }, v => { errorWith = v; }
                            )}>Mostra più elementi</button>
                    </div>
                {/if}
            </div>
        </div>

        <!-- File che sto condividendo -->
        <div class="col-md-6">
            <h4 style="margin-bottom: 10px">File che sto condividendo</h4>
            <div class="section get-shared">
                {#if loadingMine}
                    <div class="loading ph-item">
                        <div class="ph-col-12">
                            <div class="ph-row">
                                <div class="ph-col-6 big"></div><div class="ph-col-4 empty big"></div>
                                <div class="ph-col-4"></div><div class="ph-col-8 empty"></div>
                                <div class="ph-col-12" style="margin-bottom:0"></div>
                            </div>
                        </div>
                    </div>
                {:else if errorMine}
                    <div class="alert alert-danger"><h4>Errore</h4><p>{errorMine}</p></div>
                {:else if iAmSharing.length === 0}
                    <p style="color: gray">Nessun elemento.</p>
                {:else}
                    <div class="items">
                        {#each iAmSharing as s (s.file_id)}
                            <a href={readerUrl(s)}
                                class="icon img-change-to-white accent-all box-shadow-1-all"
                                style="display: inline-block"
                                title={s.name}>
                                <i class="fa-solid {TYPE_ICON[s.type] ?? 'fa-file'}"
                                    style="float: left; font-size: 24px; margin-right: 8px; margin-top: 2px"></i>
                                <span class="text-ellipsis" style="display: block; font-size: 1.2em">{s.name}</span>
                                <small class="second-row">{s.type}</small>
                            </a>
                        {/each}
                    </div>
                {/if}
                {#if moreMine}
                    <div style="text-align: center">
                        <button type="button" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                            onclick={() => loadSection('get-shared', iAmSharing.length,
                                v => { iAmSharing = v; }, iAmSharing,
                                v => { loadingMine = v; }, v => { moreMine = v; }, v => { errorMine = v; }
                            )}>Mostra più elementi</button>
                    </div>
                {/if}
            </div>
        </div>

    </div>
</div>

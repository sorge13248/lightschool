<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { onMount } from 'svelte';
    import { ArrowLeftIcon } from 'phosphor-svelte';
    import { apiFetch } from '../../lib/api';
    import { notifications } from '../../stores/notifications.svelte';
    import { t } from '../../lib/i18n';
    import Modal from '../../components/ui/Modal.svelte';

    interface Props {
        fileId: number | null;
        folderId: number;
    }

    const { fileId, folderId }: Props = $props();

    let loadingOpen  = $state(false);
    let loadingError = $state<string | null>(null);

    onMount(() => {
        const LANGUAGE = window.LANGUAGE;

        let currentFileId: number | null   = fileId;
        let currentNVer      = 2;
        let canEdit          = true;
        let quill: any       = null;
        let currentResponse: any = null;

        // Load quill.snow.css
        if (!document.querySelector('link[href*="quill.snow"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/css/quill.snow.css';
            document.head.appendChild(link);
        }

        const initQuill = () => {
            if (quill !== null) return;
            const Quill = window.Quill;
            if (!Quill) return;
            Quill.prototype.getHtml = function () {
                return this.container.firstChild.innerHTML;
            };
            quill = new Quill('#notebook-editor', {
                placeholder: 'Inizia a scrivere un capolavoro!',
                modules: { toolbar: { container: '#toolbar' } },
                theme: 'snow',
            });
            quill.focus();
        };

        const loadQuillContent = (content: string, nVer: number) => {
            if (nVer === 2 && content) {
                try {
                    quill.setContents(JSON.parse(decodeURIComponent(escape(atob(content)))));
                    return;
                } catch {
                    try { quill.setContents(JSON.parse(content)); return; } catch { /* fall through */ }
                }
            }
            if (content) quill.clipboard.dangerouslyPasteHTML(content);
        };

        const getQuillContent = (): string => {
            const ops = JSON.stringify(quill.getContents().ops);
            try { return btoa(unescape(encodeURIComponent(ops))); } catch { return btoa(ops); }
        };

        const ensureQuillLoaded = (cb: () => void) => {
            if (window.Quill) { cb(); return; }
            const script = document.createElement('script');
            script.src = '/js/quill.min.js';
            script.onload = cb;
            document.body.appendChild(script);
        };

        // Remove box-shadow from main menu bar
        const mainBar = document.querySelector('.menu-my.main') as HTMLElement | null;
        if (mainBar) mainBar.style.boxShadow = 'none';

        if (fileId) {
            ensureQuillLoaded(() => {
                loadingOpen = true;

                apiFetch('/api/writer?type=get&id=' + fileId).then((data: any) => {
                    if (data.response !== 'success') {
                        loadingError = data.text || 'Errore';
                        return;
                    }

                    currentResponse = data;
                    canEdit         = data.can_edit;
                    currentNVer     = data.n_ver || 2;

                    loadingOpen = false;

                    document.title = data.name + ' - ' + document.title;
                    const nameInput = document.querySelector('#writer-form #name') as HTMLInputElement | null;
                    if (nameInput) nameInput.value = data.name || '';
                    const writerForm = document.getElementById('writer-form') as HTMLElement | null;
                    if (writerForm) writerForm.style.display = '';

                    initQuill();
                    loadQuillContent(data.content, currentNVer);

                    if (!canEdit) {
                        quill.disable();
                        document.getElementById('toolbar')?.remove();
                        const submitBtn = document.querySelector('#writer-form [type=submit]') as HTMLElement | null;
                        if (submitBtn) submitBtn.remove();
                    } else {
                        quill.focus();
                        const toolbar = document.getElementById('toolbar') as HTMLElement | null;
                        if (toolbar) toolbar.style.display = '';
                    }
                }).catch(() => {
                    loadingError = 'Errore durante il caricamento.';
                });
            });
        } else {
            ensureQuillLoaded(() => {
                initQuill();
                const writerForm = document.getElementById('writer-form') as HTMLElement | null;
                if (writerForm) writerForm.style.display = '';
                const toolbar = document.getElementById('toolbar') as HTMLElement | null;
                if (toolbar) toolbar.style.display = '';
            });
        }

        // Auto-resize title input
        document.addEventListener('input', e => {
            const inp = (e.target as Element).closest('#writer-form input[type=text]') as HTMLInputElement | null;
            if (inp) inp.style.width = (inp.value.length * 8) + 'px';
        });

        // Click inside editor focuses Quill
        document.addEventListener('click', e => {
            if ((e.target as Element).closest('#notebook-editor') && quill) quill.focus();
        });

        // Save form submit
        document.addEventListener('submit', e => {
            const formEl = (e.target as Element).closest('#writer-form') as HTMLFormElement | null;
            if (!formEl) return;
            e.preventDefault();

            const fields = formEl.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea');
            const submitBtn = formEl.querySelector<HTMLInputElement>("input[type='submit']");
            const prevLabel = submitBtn?.value ?? '';

            fields.forEach(el => el.disabled = true);
            if (submitBtn) submitBtn.value = LANGUAGE.loading || 'Loading...';

            const unlock = () => {
                fields.forEach(el => el.disabled = false);
                if (submitBtn) submitBtn.value = prevLabel;
            };

            const isEdit  = currentResponse !== null;
            const content = getQuillContent();
            const nameVal = formEl.querySelector<HTMLInputElement>('#name')?.value ?? '';
            const url     = '/api/writer?type=' + (isEdit ? 'edit&id=' + currentFileId : 'create&id=' + folderId);
            const data    = 'name=' + nameVal + '&content=' + encodeURIComponent(content);

            apiFetch(url, 'post', data).then((result: any) => {
                if (result.response === 'success') {
                    notifications.add(result.text, { autoClose: 2000 });
                    if (result.id) {
                        window.location.href = '/my/app/reader/notebook/' + result.id;
                    } else {
                        currentNVer = 2;
                        unlock();
                    }
                } else {
                    notifications.add(result.text, { type: 'error', autoClose: 2000 });
                    unlock();
                }
                formEl.querySelector<HTMLInputElement>('#name')?.focus();
            }).catch(() => unlock());
        });
    });
</script>

<svelte:head><title>{t('app-writer')} - LightSchool</title></svelte:head>

<Modal bind:open={loadingOpen} title="Writer" maxWidth="450px" draggable onclose={() => { loadingOpen = false; }}>
    {#if loadingError}
        <p style="color: red; text-align: center">{loadingError}</p>
    {:else}
        <p style="text-align: center">
            <span style="font-size: 1.2em">Caricamento quaderno in corso</span><br/>
            Attendere prego...
        </p>
    {/if}
</Modal>

<!-- Writer top bar -->
<div class="menu-my top main no-print accent-bkg-gradient" style="top: 0">
    <div class="row">
        <div class="col-sm-12" style="text-align: center">
            <div class="pc-md">
                <a href={'/my/app/file-manager' + (folderId ? '?folder=' + folderId : '')}
                   class="back-button" style="display: inline-block; padding: 10px 5px 0; float: left"
                   aria-label="Indietro">
                    <ArrowLeftIcon weight="light" />
                </a>
                <h5 style="float: left; text-align: left; font-weight: bold">Writer</h5>
            </div>
            <form id="writer-form" method="post" action="#" style="display: none">
                <input type="text" id="name" name="name" placeholder="Nome quaderno"
                       style="padding: 6px 10px; font-size: 0.8em; text-align: center; min-width: 170px; max-width: 100%; border: 0"
                       value="Documento senza titolo" autocomplete="off" aria-label="Nome quaderno" />
                <input type="submit" value="Salva"
                       class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                       style="padding: 6px 10px; font-size: 0.8em" />
            </form>
        </div>
    </div>
</div>

<!-- Quill toolbar -->
<div class="menu-my top no-print accent-box-shadow-1" id="toolbar">
    <span class="ql-formats">
        <select class="ql-font" aria-label="Font"></select>
        <select class="ql-size" aria-label="Dimensione"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-bold" aria-label="Grassetto"></button>
        <button class="ql-italic" aria-label="Corsivo"></button>
        <button class="ql-underline" aria-label="Sottolineato"></button>
        <button class="ql-strike" aria-label="Barrato"></button>
    </span>
    <span class="ql-formats">
        <select class="ql-color" aria-label="Colore testo"></select>
        <select class="ql-background" aria-label="Colore sfondo"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-script" value="sub" aria-label="Pedice"></button>
        <button class="ql-script" value="super" aria-label="Apice"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-header" value="1" aria-label="Titolo 1"></button>
        <button class="ql-header" value="2" aria-label="Titolo 2"></button>
        <button class="ql-blockquote" aria-label="Citazione"></button>
        <button class="ql-code-block" aria-label="Blocco codice"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-list" value="ordered" aria-label="Lista numerata"></button>
        <button class="ql-list" value="bullet" aria-label="Lista puntata"></button>
        <button class="ql-indent" value="-1" aria-label="Riduci rientro"></button>
        <button class="ql-indent" value="+1" aria-label="Aumenta rientro"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-direction" value="rtl" aria-label="Direzione testo"></button>
        <select class="ql-align" aria-label="Allineamento"></select>
    </span>
    <span class="ql-formats">
        <button class="ql-link" aria-label="Link"></button>
        <button class="ql-image" aria-label="Immagine"></button>
        <button class="ql-video" aria-label="Video"></button>
        <button class="ql-formula" aria-label="Formula"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-clean" aria-label="Rimuovi formattazione"></button>
    </span>
</div>

<!-- Editor body -->
<div class="container content-my writer">
    <div class="main">
        <div class="A4" id="notebook-editor"></div>
    </div>
</div>

<style lang="scss">
    #toolbar {
        top: 41px;
        border: none;
        z-index: 1000;
        text-align: center;
        background-image: none;
        background-color: #F6F6F6;
        overflow-x: auto;
        white-space: nowrap;

        > span > * {
            padding: 3px 0;
            min-width: 28px;
        }

        > span > *:hover,
        > span > *:focus {
            box-shadow: none;
        }
    }

    .writer {
        padding-top: 140px;

        .A4 {
            background: white;
            width: 100%;
            max-width: calc(21cm + 4cm);
            min-height: calc(29.7cm + 5cm);
            display: block;
            margin: 10px auto 0.5cm;
            padding: 2cm;
            box-sizing: border-box;
            font-size: 12pt;
            box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
            overflow-wrap: break-word;
            color: black;

            @media (max-width: 768px) {
                padding: 0.5cm;
            }
        }

        @media print {
            :global(.page-break) {
                display: block;
                page-break-before: always;
            }

            :global(body) {
                margin: 0;
                padding: 0;
            }

            .A4 {
                box-shadow: none;
                margin: 0;
                width: auto;
                height: auto;
                padding: 1cm 2cm 2cm;
            }

            :global(.noprint)      { display: none; }
            :global(.enable-print) { display: block; }
        }
    }
</style>

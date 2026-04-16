<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { untrack } from "svelte";
  import {
    ArrowLeftIcon,
    DownloadSimpleIcon,
    PencilSimpleIcon,
    UserIcon,
    ChatIcon,
    ShareNetworkIcon,
    XIcon,
    TrashIcon,
    StarIcon,
  } from "phosphor-svelte";
  import { apiFetch } from "../../lib/api";
  import Modal from "../../components/ui/Modal.svelte";

  interface Props {
    type: string;
    fileId: number;
    msOffice: number;
  }

  const { type, fileId, msOffice }: Props = $props();
  const fileType = untrack(() => type);
  const fileIdInit = untrack(() => fileId);
  const msOfficeSetting = untrack(() => msOffice);

  const provideUrl = `/api/file/${fileIdInit}`;

  type LoadState = "loading" | "done" | "error";

  let loadState: LoadState = $state("loading");
  let errorMsg = $state("");
  let title = $state("Reader");
  let backUrl = $state("/my/app/file-manager");

  interface ContactData {
    profile_picture?: string | number;
    name?: string;
    surname?: string;
    ue_name?: string;
    ue_surname?: string;
    username?: string;
    fav?: unknown;
  }

  // Content slots
  let fileContent = $state(""); // innerHTML for file-content
  let diaryContent = $state("");
  let contactData = $state<ContactData | null>(null);

  // Command visibility
  let showDownload = $state(false);
  let showEdit = $state(false);
  let menuVisible = $state(false);

  // Office consent modal
  let officeConsentOpen = $state(false);
  let officeUrl = $state("");

  function showError(msg?: string): void {
    loadState = "error";
    errorMsg = msg ?? "Errore durante il caricamento.";
  }

  function escapeHtml(s: string): string {
    return (s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  const OFFICE_MIME_TYPES = [
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "application/vnd.ms-powerpoint",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "application/vnd.oasis.opendocument.text",
    "application/vnd.oasis.opendocument.spreadsheet",
    "application/vnd.oasis.opendocument.presentation",
  ];

  function isOfficeType(mime: string): boolean {
    return OFFICE_MIME_TYPES.includes(mime);
  }

  async function loadFile(): Promise<void> {
    const res = await apiFetch(
      `/api/file-manager?type=details&id=${fileIdInit}`,
    );
    if (res.response !== "success") {
      showError(res.text);
      return;
    }
    const file = res.file as Record<string, string>;
    loadState = "done";
    title = file.name ?? "";
    backUrl =
      "/my/app/file-manager" + (file.folder ? "?folder=" + file.folder : "");
    showDownload = true;

    const ft = (file.file_type ?? "").toLowerCase();
    const nm = (file.name ?? "").toLowerCase();

    if (ft.startsWith("image/")) {
      fileContent = `<div style="text-align:center;background-color:white;box-shadow:0 0 0.5cm rgba(0,0,0,0.5);display:inline-block"><img src="${provideUrl}" style="max-width:100%;max-height:calc(100vh - 100px)"/></div>`;
    } else if (ft.includes("pdf")) {
      fileContent = `<embed src="${provideUrl}" border="0" style="width:100%;height:calc(100vh - 45px)"></embed>`;
    } else if (nm.endsWith(".txt")) {
      try {
        const text = await fetch(provideUrl).then((r) => r.text());
        const div = document.createElement("div");
        div.textContent = text;
        fileContent = `<div class="file" style="text-align:left;max-width:1300px">${div.innerHTML.replace(/\n/g, "<br/>")}</div>`;
      } catch {
        showError("Could not load file content.");
        return;
      }
    } else if (isOfficeType(ft)) {
      await handleOffice();
      return;
    } else {
      fileContent = `<div class="file"><p>Nessuna anteprima disponibile.</p><p><a href="${provideUrl}" class="button">Download</a></p></div>`;
    }
  }

  async function handleOffice(): Promise<void> {
    if (msOfficeSetting === 0) {
      fileContent = `<div class="file"><p>Anteprima non disponibile.</p><p><a href="${provideUrl}" class="button">Download</a></p></div>`;
      return;
    }
    if (msOfficeSetting === 2) {
      await fetchOfficeUrl();
      return;
    }
    // msOfficeSetting === 1 — ask
    officeConsentOpen = true;
  }

  async function fetchOfficeUrl(): Promise<void> {
    const res = await apiFetch(
      `/api/file-manager?type=set-bypass&id=${fileIdInit}`,
    );
    if (res.response === "success") {
      officeUrl = res.url as string;
      fileContent = `<iframe src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(officeUrl)}" style="width:100%;height:calc(100vh - 45px);border:none"></iframe>`;
    } else {
      fileContent = `<div class="file"><p>Errore durante l'apertura del file.</p><p><a href="${provideUrl}" class="button">Download</a></p></div>`;
    }
    officeConsentOpen = false;
  }

  async function loadNotebook(): Promise<void> {
    // Load via API then init Quill after DOM settles
    const data = await apiFetch(`/api/writer?type=get&id=${fileIdInit}`);
    if (data.response !== "success") {
      showError(data.text);
      return;
    }
    loadState = "done";
    title = (data.name as string) ?? "";
    showEdit = true;

    // Wait for #notebook to be in the DOM
    await new Promise((r) => setTimeout(r, 50));
    const win = window as unknown as Record<string, unknown>;
    const Quill = win["Quill"] as
      | (new (
          el: string | Element,
          opts: unknown,
        ) => {
          setContents: (v: unknown) => void;
          clipboard: { dangerouslyPasteHTML: (h: string) => void };
        })
      | undefined;
    if (!Quill) {
      showError("Quill not loaded");
      return;
    }

    const quill = new Quill("#notebook", {
      placeholder: "Quaderno vuoto.",
      modules: { toolbar: false },
      readOnly: true,
      theme: "snow",
    });

    const ver = data.n_ver as number;
    const cont = data.content as string;
    if (ver === 2 && cont) {
      try {
        quill.setContents(JSON.parse(decodeURIComponent(escape(atob(cont)))));
      } catch {
        try {
          quill.setContents(JSON.parse(cont));
        } catch {
          /* ignore */
        }
      }
    } else if (cont) {
      quill.clipboard.dangerouslyPasteHTML(cont);
    }
  }

  async function loadDiary(): Promise<void> {
    const data = await apiFetch(`/api/diary?type=details&id=${fileIdInit}`);
    if (data.response !== "success") {
      showError(data.text);
      return;
    }
    const ev = data.event as Record<string, string>;
    const date = ev.diary_date
      ? new Date(ev.diary_date).toLocaleDateString("it-IT")
      : "";
    title = `${ev.diary_type ?? ""} di ${ev.name ?? ""} il ${date}`;
    backUrl = "/my/app/diary";
    diaryContent =
      `<div class="diary"><h1>${escapeHtml(ev.diary_type ?? "")} di ${escapeHtml(ev.name ?? "")}</h1>` +
      `<p style="text-align:right"><b>Il ${escapeHtml(date)}</b></p>` +
      `<div style="text-align:left;word-break:break-all">${ev.content ?? ""}</div></div>`;
    loadState = "done";
  }

  async function loadContact(): Promise<void> {
    const data = await apiFetch(`/api/contact?type=details&id=${fileIdInit}`);
    if (data.response !== "success") {
      showError(data.text);
      return;
    }
    const c = data.contact as ContactData;
    title = `${c.name ?? ""} ${c.surname ?? ""}`.trim();
    backUrl = "/my/app/contact";
    contactData = c;
    loadState = "done";
  }

  $effect(() => {
    menuVisible = true;

    if (fileType === "notebook") loadNotebook().catch(() => showError());
    else if (fileType === "file") loadFile().catch(() => showError());
    else if (fileType === "diary") loadDiary().catch(() => showError());
    else if (fileType === "contact") loadContact().catch(() => showError());
    else showError("Tipo non supportato.");
  });
</script>

<svelte:head>
  <title>{title} - Reader - LightSchool</title>
  {#if fileType === "notebook"}
    <link href="/css/quill.snow.css" rel="stylesheet" />
  {/if}
</svelte:head>

<!-- Invisible base bar for gradient reference -->
<div
  class="menu-my top base no-print accent-bkg-gradient"
  style="display: none"
></div>

<!-- Transparent overlay menu -->
<div
  class="menu-my top no-print img-change-to-white reader-menu-overlay"
  style:top={menuVisible ? "0" : undefined}
  style:transition="top 1s"
>
  <div class="row">
    <div class="col-sm-6">
      <a
        href={backUrl}
        aria-label="Indietro"
        class="back-button"
        style="display: inline-block; padding: 10px 5px 0; float: left"
      >
        <ArrowLeftIcon weight="light" />
      </a>
      <h5 style="font-weight: bold" class="text-ellipsis">{title}</h5>
    </div>
    <div class="col-sm-6 pc-md commands" style="text-align: right">
      {#if showDownload}
        <a
          href={provideUrl}
          style="display: inline-block; padding: 10px"
          title="Download"
        >
          <DownloadSimpleIcon weight="light" />
        </a>
      {/if}
      {#if showEdit}
        <a
          href="/my/app/writer?id={fileIdInit}"
          style="display: inline-block; padding: 10px"
          title="Modifica"
        >
          <PencilSimpleIcon weight="light" />
        </a>
      {/if}
    </div>
  </div>
</div>

<!-- Reader content -->
<div class="container content-my reader" id="reader-container">
  <div class="main">
    {#if loadState === "error"}
      <div style="text-align: center; margin-top: 40px">
        <div
          style="text-align:center;background-color:white;color:white;background-image:linear-gradient(to right,#c91127,#ec2032);box-shadow:0 0 0.5cm rgba(0,0,0,0.5);display:inline-block;border-radius:10px;padding:20px 30px"
        >
          <p>{errorMsg}</p>
        </div>
      </div>
    {/if}

    {#if fileType === "notebook" && loadState === "done"}
      <div class="A4" id="notebook"></div>
    {/if}

    {#if fileType === "file" && fileContent}
      <!-- eslint-disable-next-line svelte/no-at-html-tags -->
      <div id="file-content" style="text-align: center">
        {@html fileContent}
      </div>
    {/if}

    {#if fileType === "diary" && diaryContent}
      <!-- eslint-disable-next-line svelte/no-at-html-tags -->
      <div id="diary-content" style="text-align: center">
        {@html diaryContent}
      </div>
    {/if}

    {#if fileType === "contact" && contactData}
      <div id="contact-content" style="text-align: center; color: black">
        <div class="contact">
          <h3>
            {#if contactData.profile_picture}
              <img
                src="/api/file/{contactData.profile_picture}"
                style="width:64px;height:64px;border-radius:50%;float:left;margin-right:20px;margin-top:5px"
                alt=""
              />
            {:else}
              <UserIcon
                weight="light"
                style="font-size:48px;float:left;margin-right:20px;margin-top:5px"
              />
            {/if}
            {contactData.name ?? ""}
            {contactData.surname ?? ""}
          </h3>
          <p style="word-wrap:break-word">
            <b>Nome e cognome ufficiale:</b>
            {contactData.ue_name ?? ""}
            {contactData.ue_surname ?? ""}
            &bull; <b>Username:</b>
            {contactData.username ?? ""}
          </p>
          <br />
          <div class="row">
            <!-- svelte-ignore a11y_invalid_attribute -->
            <div class="col-sm-6">
              <a href="#" class="icon img-change-to-white"
                ><ChatIcon weight="light" /> Invia messaggio</a
              >
            </div>
            <!-- svelte-ignore a11y_invalid_attribute -->
            <div class="col-sm-6">
              <a href="#" class="icon img-change-to-white"
                ><ShareNetworkIcon weight="light" /> Condividi contatto</a
              >
            </div>
          </div>
          <div class="row">
            <!-- svelte-ignore a11y_invalid_attribute -->
            <div class="col-sm-6">
              <a href="#" class="icon img-change-to-white"
                ><XIcon weight="light" /> Blocca contatto</a
              >
            </div>
            <!-- svelte-ignore a11y_invalid_attribute -->
            <div class="col-sm-6">
              <a href="#" class="icon img-change-to-white"
                ><TrashIcon weight="light" /> Elimina contatto</a
              >
            </div>
          </div>
          <div class="row">
            <!-- svelte-ignore a11y_invalid_attribute -->
            <div class="col-sm-12">
              <a href="#" class="icon img-change-to-white"
                ><StarIcon weight="light" />
                {contactData.fav ? "Rimuovi da" : "Aggiungi a"} Desktop</a
              >
            </div>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>

{#if fileType === "notebook" && loadState === "done"}
  <script src="/js/quill.min.js"></script>
{/if}

<!-- Loading modal -->
<Modal open={loadState === "loading"} title="Reader" maxWidth="450px">
  <p style="text-align: center">
    <span style="font-size: 1.2em">Caricamento in corso</span><br />
    Attendere prego...
  </p>
</Modal>

<!-- Office Online consent -->
<Modal
  open={officeConsentOpen}
  title="Microsoft Office Online"
  maxWidth="500px"
  onclose={() => {
    officeConsentOpen = false;
  }}
>
  {#snippet children()}
    <p>
      Questo file verrà inviato a Microsoft Office Online per la
      visualizzazione.
    </p>
    <p class="small">
      Autorizzando, accetti le
      <a
        href="https://www.microsoft.com/it-IT/servicesagreement/"
        target="_blank">condizioni d'uso</a
      >
      e l'<a
        href="https://privacy.microsoft.com/it-it/privacystatement"
        target="_blank">informativa sulla privacy</a
      >
      di Microsoft.
    </p>
    <button
      type="button"
      class="button"
      style="margin-right: 8px"
      onclick={() => {
        officeConsentOpen = false;
        fileContent = `<div class="file"><p>Anteprima non disponibile.</p><p><a href="${provideUrl}" class="button">Download</a></p></div>`;
      }}
    >
      Nega
    </button>
    <button
      type="button"
      class="button accent-bkg-gradient"
      onclick={fetchOfficeUrl}>Autorizza</button
    >
  {/snippet}
</Modal>

<style lang="scss">
  .reader-menu-overlay {
    background-color: rgba(223, 223, 223, 0);
    background-image: none;
    box-shadow: none;
    position: fixed;
  }

  // .A4 is rendered directly in the template — scoped.
  .reader .A4 {
    background: white;
    width: 100%;
    max-width: calc(21cm + 4cm);
    min-height: calc(29.7cm + 5cm);
    display: block;
    margin: 40px auto 0.5cm;
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

  // .file, .diary, .contact are injected via {@html} — need :global().
  :global(.reader .file),
  :global(.reader .diary) {
    text-align: center;
    background-color: white;
    box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
    display: inline-block;
    border-radius: 10px;
    padding: 20px 30px;
    margin-top: 40px;

    @media (max-width: 768px) {
      margin-top: 10px;
    }
  }

  :global(.reader .diary) {
    word-wrap: break-word;
  }

  :global(.reader .contact) {
    text-align: left;
    box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
    display: inline-block;
    border-radius: 10px;
    padding: 20px 30px;
    width: 100%;
    max-width: 600px;
    background-color: white;
    margin-top: 40px;

    @media (max-width: 768px) {
      margin-top: 10px;
    }
  }
</style>

<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { onMount, tick } from "svelte";
  import Cookies from "js-cookie";
  import { t } from "../../lib/i18n";
  import { apiFetch } from "../../lib/api";
  import { notifications } from "../../stores/notifications.svelte";
  import type { FileItem } from "../../lib/types";
  import { FolderIcon, BookOpenIcon, CalendarBlankIcon, FileIcon, FileTextIcon, FilePdfIcon, FileDocIcon, FileXlsIcon, FilePptIcon, WarningCircleIcon } from 'phosphor-svelte';
  import ActionButton from "../../components/ui/ActionButton.svelte";
  import PropertyPanel from "../../components/ui/PropertyPanel.svelte";
  import Modal from "../../components/ui/Modal.svelte";
  import LoadingPlaceholder from "../../components/ui/LoadingPlaceholder.svelte";
  import DeleteModal from "../../components/modals/DeleteModal.svelte";
  import ShareModal from "../../components/modals/ShareModal.svelte";
  import ProjectModal from "../../components/modals/ProjectModal.svelte";
  import IconPickerModal from "../../components/modals/IconPickerModal.svelte";

  const ICON_MAP: Record<string, typeof FileIcon> = {
    folder:       FolderIcon,
    notebook:     BookOpenIcon,
    diary:        CalendarBlankIcon,
    file:         FileIcon,
    'file-text':  FileTextIcon,
    'file-pdf':   FilePdfIcon,
    'file-doc':   FileDocIcon,
    'file-xls':   FileXlsIcon,
    'file-ppt':   FilePptIcon,
    'file-missing': WarningCircleIcon,
  };

  // ── Page props ────────────────────────────────────────────────────────────────

  interface TreeItem { id: number; name: string }
  interface FolderInfo { id: number; name: string; trash?: number }

  interface Props {
    folder:        string | null;
    currentFolder: FolderInfo | null;
    tree:          TreeItem[];
    diskSpace:     number;
    owner:         string;
  }

  const { folder, currentFolder, tree, diskSpace, owner }: Props = $props();

  const isDesktop    = $derived(folder === "desktop");
  const isTrash      = $derived(!!currentFolder?.trash);
  const isOwned      = $derived(!owner || owner === "");
  const showActions  = $derived(!isDesktop && !isTrash && isOwned);
  const breadcrumbTree = $derived([...tree].reverse());
  const folderHref   = $derived(isTrash ? "/my/app/trash" : "/my/app/file-manager");

  // ── File list state ───────────────────────────────────────────────────────────

  let fileItems:       FileItem[] = $state([]);
  let fileListLoading              = $state(false);

  const nonImageFiles = $derived(fileItems.filter(i => !i.file_type?.includes("image/") || !i.file_exists));
  const imageFiles    = $derived(fileItems.filter(i =>  i.file_type?.includes("image/") && !!i.file_exists));

  async function listFiles(): Promise<void> {
    fileListLoading = true;
    fileItems       = [];
    try {
      const res = await apiFetch(
        "/api/file-manager?type=list-files" +
        "&folder=" + encodeURIComponent(folder ?? "") +
        "&owner="  + encodeURIComponent(owner  ?? ""),
      );
      fileItems = ((res["items"] as FileItem[]) ?? []).map(i => ({ ...i, id: String(i.id) }));
    } catch { /* silent */ }
    finally { fileListLoading = false; }
  }

  // ── Paste button ──────────────────────────────────────────────────────────────

  let showPasteButton = $state(false);

  function checkPastingFile(): void {
    if (Cookies.get("cuttingFileManagerFileID")) showPasteButton = true;
  }

  async function handlePaste(): Promise<void> {
    const raw = Cookies.get("cuttingFileManagerFileID");
    if (!raw) return;
    try {
      const [cutId] = JSON.parse(raw) as [string, ...unknown[]];
      const res = await apiFetch(
        "/api/file-manager?type=move&id=" + cutId + "&target=" + encodeURIComponent(folder ?? ""),
        "POST", "",
      );
      if (res.response === "success") {
        Cookies.remove("cuttingFileManagerFileID");
        showPasteButton = false;
        await listFiles();
      } else {
        notifications.add(res.text, { type: "error", autoClose: 2000 });
      }
    } catch { /* ignore */ }
  }

  // ── Drag & drop ───────────────────────────────────────────────────────────────

  let draggingId = $state<string | null>(null);

  async function moveFile(id: string, targetId: string): Promise<void> {
    const res = await apiFetch(
      "/api/file-manager?type=move&id=" + id + "&target=" + targetId,
      "POST", "",
    ).catch(() => null);
    if (!res) return;
    if (res.response === "success") {
      await listFiles();
    } else {
      notifications.add(res.text, { type: "error", autoClose: 2000 });
    }
  }

  function handleDragOver(e: Event): void { e.preventDefault(); }

  function handleDrop(e: DragEvent): void {
    e.preventDefault();
    if (draggingId === null) return;
    let node = e.target as HTMLElement | null;
    while (node && node !== document.documentElement) {
      if (typeof node.className === "string" && node.className.includes("folder")) {
        const targetId = node.getAttribute("data-fileid");
        if (targetId !== null && targetId !== draggingId) void moveFile(draggingId, targetId);
        return;
      }
      node = node.parentElement;
    }
  }

  // ── Rename modal ──────────────────────────────────────────────────────────────

  let renameOpen          = $state(false);
  let renameFileId        = $state("");
  let renameFileName      = $state("");
  let renameOriginalName  = $state("");
  let renameError    = $state("");
  let renameLoading  = $state(false);
  let renameInputEl  = $state<HTMLInputElement | null>(null);

  $effect(() => {
    if (!renameOpen) return;
    void tick().then(() => {
      if (!renameInputEl) return;
      renameInputEl.focus();
      const dot = renameFileName.lastIndexOf(".");
      renameInputEl.setSelectionRange(0, dot > 0 ? dot : renameFileName.length);
    });
  });

  async function handleRename(): Promise<void> {
    renameLoading = true;
    renameError   = "";
    const folderSuffix = folder && /^\d+$/.test(folder) ? "&folder=" + folder : "";
    try {
      const res = await apiFetch(
        "/api/file-manager?type=rename&id=" + renameFileId + folderSuffix,
        "POST", "name=" + encodeURIComponent(renameFileName),
      );
      if (res.response === "success") {
        fileItems   = fileItems.map(i => i.id === renameFileId ? { ...i, name: renameFileName } : i);
        renameOpen  = false;
        notifications.add(res.text, { autoClose: 2000 });
      } else {
        renameError = res.text;
      }
    } catch { renameError = "Error"; }
    finally { renameLoading = false; }
  }

  // ── New-select modal ──────────────────────────────────────────────────────────

  let newSelectOpen = $state(false);

  // ── New-folder modal ──────────────────────────────────────────────────────────

  let newFolderOpen    = $state(false);
  let newFolderName    = $state("");
  let newFolderError   = $state("");
  let newFolderLoading = $state(false);
  let newFolderInputEl = $state<HTMLInputElement | null>(null);

  $effect(() => {
    if (!newFolderOpen) return;
    void tick().then(() => newFolderInputEl?.focus());
  });

  async function handleNewFolder(): Promise<void> {
    newFolderLoading = true;
    newFolderError   = "";
    const suffix = folder && /^\d+$/.test(folder) ? "&folder=" + folder : "";
    try {
      const res = await apiFetch(
        "/api/file-manager?type=create-folder" + suffix,
        "POST", "name=" + encodeURIComponent(newFolderName),
      );
      if (res.response === "success") {
        newFolderName  = "";
        newFolderOpen  = false;
        newSelectOpen  = false;
        await listFiles();
        notifications.add(res.text, { autoClose: 2000 });
      } else {
        newFolderError = res.text;
        void tick().then(() => newFolderInputEl?.focus());
      }
    } catch { newFolderError = "Error"; }
    finally { newFolderLoading = false; }
  }

  // ── Upload modal ──────────────────────────────────────────────────────────────

  let uploadOpen   = $state(false);
  let uploadAreaEl = $state<HTMLElement | null>(null);

  $effect(() => {
    if (!uploadOpen) return;
    const Uppy = window.Uppy;
    if (!Uppy) return;
    void tick().then(() => {
      if (!uploadAreaEl) return;
      const uppy = Uppy.Core({
        restrictions: {
          maxFileSize: 1048576 * (diskSpace ?? 100),
          allowedFileTypes: [
            ".png", ".jpg", ".jpeg", ".bmp", ".gif", ".tiff",
            ".mp3", ".mp4", ".mov", ".wav",
            ".pdf", ".xps",
            ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".accdb",
            ".odt", ".ods", ".odp", ".odb",
            ".java", ".class", ".cpp", ".h", ".js", ".html", ".htm",
            ".css", ".sass", ".scss", ".txt", ".rtf", ".go", ".py",
          ],
        },
      })
        .use(Uppy.Dashboard, { inline: true, target: uploadAreaEl })
        .use(Uppy.XHRUpload, {
          endpoint: "/api/file-manager?type=upload&folder=" + encodeURIComponent(folder ?? ""),
          fieldName: "file",
          headers: {
            "X-CSRF-TOKEN": document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "",
          },
        });

      uppy.on("upload-success", (_file: any, response: any) => {
        if (response.body?.response === "error") {
          notifications.add(response.body.text, { type: "error", autoClose: 2000 });
        } else {
          void listFiles();
          uploadOpen = false;
        }
      });
    });
  });

  // ── Keyboard shortcuts for new-select ────────────────────────────────────────

  function handleKeydown(e: KeyboardEvent): void {
    if (!newSelectOpen) return;
    if (document.querySelector("input:focus, textarea:focus, select:focus")) return;
    const key = e.key.toLowerCase();
    if (key === "f") { newSelectOpen = false; newFolderOpen = true; }
    else if (key === "o") { window.location.href = "/my/app/writer/" + (folder && /^\d+$/.test(folder) ? folder : "0"); }
    else if (key === "u") { newSelectOpen = false; uploadOpen = true; }
  }

  // ── Context menu window events ────────────────────────────────────────────────

  function handleCmRename(e: Event): void {
    const { fileId, fileName } = (e as CustomEvent).detail as { fileId: string; fileName: string };
    renameFileId        = fileId;
    renameFileName      = fileName;
    renameOriginalName  = fileName;
    renameError         = "";
    renameOpen          = true;
  }

  function handleCmFavChanged(e: Event): void {
    const { fileId, newFav, removeFromView } = (e as CustomEvent).detail as { fileId: string; newFav: 0 | 1; removeFromView: boolean };
    if (removeFromView) {
      fileItems = fileItems.filter(i => i.id !== fileId);
    } else {
      fileItems = fileItems.map(i => i.id === fileId ? { ...i, fav: newFav } : i);
    }
  }

  function handleCmFileRemoved(e: Event): void {
    const { fileId } = (e as CustomEvent).detail as { fileId: string };
    fileItems = fileItems.filter(i => i.id !== fileId);
  }

  // ── Icon picker ───────────────────────────────────────────────────────────────

  function handleCmIconChanged(e: Event): void {
    const { fileId, icon } = (e as CustomEvent).detail as { fileId: string; icon: string | null };
    fileItems = fileItems.map(i => i.id === fileId ? { ...i, icon: icon ?? undefined } : i);
  }

  // ── Mount ─────────────────────────────────────────────────────────────────────

  onMount(() => {
    if (!window.Uppy) {
      const script = document.createElement("script");
      script.src = "/js/uppy.min.js";
      document.body.appendChild(script);
    }

    void listFiles();
    checkPastingFile();

    document.addEventListener("keydown",  handleKeydown);
    document.addEventListener("dragover", handleDragOver);
    document.addEventListener("drop",     handleDrop as EventListener);

    window.addEventListener("cm-check-paste",  () => checkPastingFile());
    window.addEventListener("cm-rename",       handleCmRename);
    window.addEventListener("cm-fav-changed",  handleCmFavChanged);
    window.addEventListener("cm-file-removed", handleCmFileRemoved);
    window.addEventListener("cm-icon-changed", handleCmIconChanged);

    return () => {
      document.removeEventListener("keydown",  handleKeydown);
      document.removeEventListener("dragover", handleDragOver);
      document.removeEventListener("drop",     handleDrop as EventListener);

      window.removeEventListener("cm-rename",       handleCmRename);
      window.removeEventListener("cm-fav-changed",  handleCmFavChanged);
      window.removeEventListener("cm-file-removed", handleCmFileRemoved);
      window.removeEventListener("cm-icon-changed", handleCmIconChanged);
    };
  });
</script>

<svelte:head>
  <title>{t("app-file-manager")} - LightSchool</title>
  <link rel="stylesheet" href="/css/uppy.min.css" />
</svelte:head>

<!-- ── Self-contained modals ──────────────────────────────────────────────────── -->
<PropertyPanel />
<DeleteModal />
<ShareModal />
<ProjectModal />
<IconPickerModal />

<!-- ── Rename modal ───────────────────────────────────────────────────────────── -->
<Modal
  open={renameOpen}
  title={t("rename", "Rinomina") + ' "' + renameOriginalName + '"'}
  maxWidth="450px"
  draggable
  onclose={() => { renameOpen = false; }}
>
  {#snippet children()}
    <form onsubmit={(e) => { e.preventDefault(); void handleRename(); }}>
      <input type="text" id="name" name="name"
             bind:value={renameFileName}
             bind:this={renameInputEl}
             class="box-shadow-1-all"
             style="width: calc(100% - 140px)" />
      <input type="submit" value={t("rename", "Rinomina")}
             class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
             style="float: right"
             disabled={renameLoading} />
      {#if renameError}
        <div class="response alert alert-danger" style="clear: both; margin-top: 10px">{renameError}</div>
      {/if}
    </form>
  {/snippet}
</Modal>

<!-- ── New-select modal ───────────────────────────────────────────────────────── -->
{#if showActions}
<Modal
  open={newSelectOpen}
  title={t("new-item", "New item")}
  maxWidth="522px"
  draggable
  onclose={() => { newSelectOpen = false; }}
>
  {#snippet children()}
    <div style="text-align: center">
      <button type="button" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker new-folder"
              onclick={() => { newSelectOpen = false; newFolderOpen = true; }}>
        {t("folder", "Folder")}
      </button>
      <a href="/my/app/writer/{folder && /^\d+$/.test(folder) ? folder : '0'}"
         class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker new-notebook">
        {t("notebook", "Notebook")}
      </a>
      <button type="button" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker upload-file"
              onclick={() => { newSelectOpen = false; uploadOpen = true; }}>
        {t("upload-file", "Upload file")}
      </button>
    </div>
  {/snippet}
</Modal>

<!-- ── New-folder modal ───────────────────────────────────────────────────────── -->
<Modal
  open={newFolderOpen}
  title={t("new-folder", "New folder")}
  maxWidth="522px"
  draggable
  onclose={() => { newFolderOpen = false; newFolderName = ""; newFolderError = ""; }}
>
  {#snippet children()}
    <form onsubmit={(e) => { e.preventDefault(); void handleNewFolder(); }}>
      <input type="text" id="name" name="name"
             placeholder={t("name", "Name")}
             bind:value={newFolderName}
             bind:this={newFolderInputEl}
             style="width: calc(100% - 105px)"
             maxlength="255" />
      <input type="submit" value={t("create", "Create")}
             class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
             disabled={newFolderLoading} /><br />
      {#if newFolderError}
        <div class="response alert alert-danger" style="margin-top: 10px">{newFolderError}</div>
      {/if}
      <p class="small">
        {t("folder-name-hint", 'Make sure the name is unique. Max 255 characters. Do not use: \\ / : * ? " < > | &')}
      </p>
    </form>
  {/snippet}
</Modal>

<!-- ── Upload modal ───────────────────────────────────────────────────────────── -->
<Modal
  open={uploadOpen}
  title={t("upload-file", "Upload file")}
  maxWidth="522px"
  draggable
  onclose={() => { uploadOpen = false; }}
>
  {#snippet children()}
    <div id="drag-drop-area" bind:this={uploadAreaEl}></div>
    <p class="small">{t("upload-hint", "Max size")}: {diskSpace ?? 100} MB</p>
  {/snippet}
</Modal>
{/if}

<!-- ── Main container ──────────────────────────────────────────────────────────── -->
<div class="container content-my file-manager">
  {#if showActions}
    <ActionButton index={0} title={t("add", "Add")} onclick={() => { newSelectOpen = true; }} />
    {#if showPasteButton}
      <ActionButton index={1} variant="paste" title={t("paste", "Paste")} onclick={handlePaste} />
    {/if}
  {/if}

  {#if !isDesktop}
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        {#if !currentFolder}
          <li class="breadcrumb-item active folder" aria-current="page" data-fileid="">
            {t("home-folder", "Home folder")}
          </li>
        {:else if currentFolder.trash}
          <li class="breadcrumb-item">
            <a href="/my/app/trash" class="accent-fore accent-fore-darker-all" style="text-decoration: none">
              {t("trash", "Trash")}
            </a>
          </li>
        {:else}
          <li class="breadcrumb-item folder" data-fileid="">
            <a href={folderHref} class="accent-fore accent-fore-darker-all" style="text-decoration: none">
              <span class="pc">{t("home", "Home")}&nbsp;</span>{t("folder", "folder")}
            </a>
          </li>
          {#each breadcrumbTree as f (f.id)}
            <li class="breadcrumb-item folder" data-fileid={f.id}>
              <a href="/my/app/file-manager?folder={f.id}"
                 class="accent-fore accent-fore-darker-all" style="text-decoration: none">
                <FolderIcon weight="light" style="width:16px;height:16px;margin-right:4px;vertical-align:middle" />{f.name}
              </a>
            </li>
          {/each}
          <li class="breadcrumb-item active" aria-current="page">
            <FolderIcon weight="light" style="width:16px;height:16px;margin-right:4px;vertical-align:middle" />{currentFolder.name}
          </li>
        {/if}
      </ol>
    </nav>
  {/if}

  <!-- ── File grid ───────────────────────────────────────────────────────────── -->
  <div class="folder-view">
    <p style="color: gray; display: none" class="search-no-result">
      {t("no-results-for", "No results for")} '<span class="searched-text"></span>'.
    </p>

    {#if fileListLoading}
      <LoadingPlaceholder />
    {:else if fileItems.length === 0 && (currentFolder || !folder || folder === "desktop")}
      <p class="empty-msg" style="color: gray">{t("no-files-in-folder", "No files in this folder.")}</p>
    {:else if fileItems.length === 0 && folder && folder !== "desktop"}
      <p style="color: gray">{t("folder-not-found", "This folder could not be found.")}</p>
    {/if}

    {#each nonImageFiles as item (item.id)}
      {@const ItemIcon = ICON_MAP[item.iconKey ?? item.type] ?? FileIcon}
      <!-- svelte-ignore a11y_missing_attribute -->
      <a
        href={item.link}
        title={item.name}
        draggable="true"
        data-fra-context-menu="file"
        data-fileid={item.id}
        data-file-fav={item.fav}
        data-file-type={item.type}
        data-file-in-trash={isTrash ? "" : undefined}
        class="icon accent-all box-shadow-1-all {item.type}"
        style="display: inline-block"
        ondragstart={(e) => { draggingId = item.id; (e.currentTarget as HTMLElement).style.opacity = "0.5"; }}
        ondragend={(e)   => { draggingId = null;    (e.currentTarget as HTMLElement).style.opacity = ""; }}
      >
        {#if item.icon}
          <img class="custom-icon"
               src={item.icon}
               style="float: left{item.style ? '; ' + item.style : ''}"
               alt="" />
        {:else}
          <ItemIcon weight="light" style="float: left; width: 40px; height: 40px; margin-right: 8px; margin-top: 2px" />
        {/if}
        <span class="filename text-ellipsis" style="display: block; font-size: 1.2em">{item.name}</span>
        <small class="second-row">{item.secondRow ?? ""}</small>
      </a>
    {/each}

    {#if nonImageFiles.length > 0 && imageFiles.length > 0}
      <br class="file-separator" />
      <br class="file-separator" />
    {/if}

    {#each imageFiles as item (item.id)}
      <!-- svelte-ignore a11y_missing_attribute -->
      <a
        href={item.link}
        title={item.name}
        draggable="true"
        data-fra-context-menu="file"
        data-fileid={item.id}
        data-file-fav={item.fav}
        data-file-type={item.type}
        data-file-in-trash={isTrash ? "" : undefined}
        class="icon img-change-to-white box-shadow-1-all image {item.type}"
        style="display: inline-block; background-image: url('{item.icon}'); background-position: center; background-size: cover; background-repeat: no-repeat"
        ondragstart={(e) => { draggingId = item.id; (e.currentTarget as HTMLElement).style.opacity = "0.5"; }}
        ondragend={(e)   => { draggingId = null;    (e.currentTarget as HTMLElement).style.opacity = ""; }}
      >
        <span class="filename text-ellipsis" style="display: block; font-size: 1.2em">{item.name}</span>
        <small class="second-row">{item.secondRow ?? ""}</small>
      </a>
    {/each}
  </div>
</div>

<style lang="scss">
  .contact-list-suggestion {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    overflow-y: auto;
    max-height: 30vh;
  }
</style>

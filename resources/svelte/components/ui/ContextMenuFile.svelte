<script lang="ts">
  /**
   * ContextMenuFile — Svelte 5 replacement for my/partials/context-menu-file.blade.php
   *
   * Dispatches synthetic CustomEvents so parent components (FileManager)
   * can react to actions (delete, rename, share, etc.) without tight coupling.
   * The parent is responsible for opening the appropriate Modal; this component
   * only renders the context-menu DOM and the modals for actions that are fully
   * self-contained (fav, cut, restore).
   *
   * Events dispatched on `window`:
   *   cm-rename    { fileId, fileName }
   *   cm-delete    { fileId, fileName, inTrash }
   *   cm-share     { fileId, fileName }
   *   cm-project   { fileId, fileType }
   *   cm-property  { fileId }
   */

  import {
    FolderOpenIcon,
    PencilSimpleIcon,
    DownloadSimpleIcon,
    ShareNetworkIcon,
    MonitorIcon,
    ScissorsIcon,
    StarIcon,
    XIcon,
    ArrowLeftIcon,
    InfoIcon,
    ImageIcon,
  } from "phosphor-svelte";
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";
  import Cookies from "js-cookie";
  import { fade } from "svelte/transition";

  interface Props {
    /** Current folder id (numeric string) or '' for root, 'desktop' for desktop. */
    currentFolder?: string;
    onListFiles?: () => void;
  }

  const { currentFolder = "", onListFiles }: Props = $props();

  // ── Reactive context menu state ──────────────────────────────────────────────
  let visible = $state(false);
  let posX = $state(0);
  let posY = $state(0);
  let fileId = $state("");
  let fileName = $state("");
  let fileTitle = $state("");
  let fileImgSrc = $state("");
  let fileSecondRow = $state("");
  let fileType = $state<
    "folder" | "notebook" | "file" | "diary" | "trash" | ""
  >("");

  // Diary-specific fields (captured at right-click time)
  let diaryType = $state("");
  let diarySubject = $state("");
  let diaryColor = $state(""); // hex without #
  let diaryDate = $state("");
  let diaryReminder = $state("");
  let diaryPriority = $state("0");
  let fileFav = $state<0 | 1>(0);
  let inTrash = $state(false);
  let openHref = $state("");
  let editHref = $state("");
  let downloadHref = $state("");

  // ── Listen for right-clicks on file icons ────────────────────────────────
  $effect(() => {
    const handler = (e: MouseEvent) => {
      const target = (e.target as Element).closest<HTMLElement>(
        '[data-fra-context-menu="file"]:not(.fra-context-menu)',
      );
      if (!target) return;
      e.preventDefault();

      const id = target.getAttribute("data-fileid") ?? "";
      const type = (target.getAttribute("data-file-type") ??
        "") as typeof fileType;
      const fav = parseInt(target.getAttribute("data-file-fav") ?? "0") as
        | 0
        | 1;
      const trash = target.hasAttribute("data-file-in-trash");
      const iconEl = document.querySelector<HTMLAnchorElement>(
        `.icon[data-fileid="${id}"]`,
      );

      fileId = id;
      fileName =
        iconEl?.querySelector(".filename")?.textContent ??
        target.querySelector(".filename")?.textContent ??
        "";
      fileTitle = iconEl?.title ?? target.title ?? "";
      fileImgSrc = iconEl?.querySelector<HTMLImageElement>("img")?.src ?? "";
      fileSecondRow =
        iconEl?.querySelector<HTMLElement>(".second-row")?.innerHTML ?? "";
      fileType = trash ? "trash" : type;

      // Diary-specific (stored on the target element via data-*)
      diaryType = target.getAttribute("data-type") ?? "";
      diarySubject = target.getAttribute("data-subject") ?? "";
      diaryColor = target.getAttribute("data-fore") ?? "";
      diaryDate = target.getAttribute("data-date") ?? "";
      diaryReminder = target.getAttribute("data-reminder") ?? "";
      diaryPriority = target.getAttribute("data-priority") ?? "0";
      fileFav = fav;
      inTrash = trash;
      openHref = iconEl?.href ?? "#";
      editHref = `/my/app/writer?id=${id}`;
      downloadHref = `/api/file/${id}`;
      posX = e.pageX;
      posY = e.pageY;
      visible = true;
    };
    document.addEventListener("contextmenu", handler);
    return () => document.removeEventListener("contextmenu", handler);
  });

  // Close on click outside
  $effect(() => {
    if (!visible) return;
    const handler = (e: MouseEvent) => {
      const menu = document.querySelector(".svelte-context-menu-file");
      if (menu && !menu.contains(e.target as Node)) {
        visible = false;
      }
    };
    window.addEventListener("click", handler, true);
    return () => window.removeEventListener("click", handler, true);
  });

  function close(): void {
    visible = false;
  }

  function showsFor(cls: string): boolean {
    // Which items are relevant for the current file type
    const clsList = cls.split(" ");
    if (inTrash) return clsList.includes("trash");
    return clsList.includes(fileType);
  }

  // ── Fav ──────────────────────────────────────────────────────────────────────
  async function handleFav(): Promise<void> {
    close();
    try {
      const res = await apiFetch(
        "/api/file-manager?type=fav&id=" + fileId,
        "POST",
        "",
      );
      if (res["response"] === "success") {
        const newFav = fileFav === 1 ? 0 : 1;
        const removeFromView = fileFav === 1 && currentFolder === "desktop";
        window.dispatchEvent(
          new CustomEvent("cm-fav-changed", {
            detail: { fileId, newFav, removeFromView },
          }),
        );
        fileFav = newFav as 0 | 1;
      }
      notifications.add(res["text"] as string, {
        type: res["response"] === "success" ? "" : "error",
        autoClose: 2000,
      });
    } catch {
      /* silent */
    }
  }

  // ── Cut ──────────────────────────────────────────────────────────────────────
  function handleCut(): void {
    close();
    const payload = JSON.stringify([
      fileId,
      fileTitle,
      String(fileType),
      fileImgSrc,
      fileSecondRow,
    ]);
    Cookies.set("cuttingFileManagerFileID", payload);
    window.dispatchEvent(new CustomEvent("cm-check-paste"));
    notifications.add(t("file-cutted", "File tagliato"), { autoClose: 2000 });
  }

  // ── Restore (trash) ──────────────────────────────────────────────────────────
  async function handleRestore(): Promise<void> {
    close();
    try {
      const res = await apiFetch(
        "/api/file-manager?type=restore&id=" + fileId,
        "POST",
        "",
      );
      if (res["response"] === "success") {
        window.dispatchEvent(
          new CustomEvent("cm-file-removed", { detail: { fileId } }),
        );
      }
      notifications.add(res["text"] as string, {
        type: res["response"] === "success" ? "" : "error",
        autoClose: 2000,
      });
    } catch {
      /* silent */
    }
  }

  // ── Dispatch events for parent-handled modals ─────────────────────────────────
  function dispatchAction(name: string, detail: Record<string, unknown>): void {
    close();
    window.dispatchEvent(new CustomEvent(name, { detail }));
  }
</script>

<!-- Context menu (positioned absolutely on the page) -->
{#if visible}
  <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
  <div
    class="fra-context-menu svelte-context-menu-file accent-bkg-gradient visible"
    data-fra-context-menu="file"
    style="left: {posX}px; top: {posY}px;"
    role="menu"
    transition:fade={{ duration: 150 }}
  >
    <!-- Close menu (mobile) -->
    <button
      type="button"
      class="accent-bkg-all-darker box-shadow-1-all mobile-block cm-close"
      onclick={close}
    >
      Chiudi menu
    </button>

    <!-- Open -->
    {#if showsFor("folder notebook file trash diary")}
      <a
        href={openHref}
        class="accent-bkg-all-darker box-shadow-1-all open follow-link"
        onclick={(e) => {
          e.preventDefault();
          window.location.href = openHref;
        }}
      >
        <FolderOpenIcon weight="light" /> Apri
      </a>
    {/if}

    <!-- Edit (notebook) -->
    {#if !inTrash && fileType === "notebook"}
      <a
        href={editHref}
        class="accent-bkg-all-darker box-shadow-1-all notebook edit follow-link"
        onclick={(e) => {
          e.preventDefault();
          window.location.href = editHref;
        }}
      >
        <PencilSimpleIcon weight="light" /> Modifica
      </a>
    {/if}

    <!-- Edit (diary) -->
    {#if !inTrash && fileType === "diary"}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all diary edit"
        onclick={() =>
          dispatchAction("cm-diary-edit", {
            fileId,
            diaryType,
            diarySubject,
            diaryColor,
            diaryDate,
            diaryReminder,
            diaryPriority,
          })}
      >
        <PencilSimpleIcon weight="light" /> Modifica
      </button>
    {/if}

    <!-- Download (file) -->
    {#if !inTrash && fileType === "file"}
      <a
        href={downloadHref}
        class="accent-bkg-all-darker box-shadow-1-all file download follow-link"
        download
        onclick={(e) => {
          e.preventDefault();
          window.location.href = downloadHref;
        }}
      >
        <DownloadSimpleIcon weight="light" /> Scarica
      </a>
    {/if}

    <!-- Share -->
    {#if !inTrash && showsFor("folder notebook file diary")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all share"
        onclick={() => dispatchAction("cm-share", { fileId, fileName })}
      >
        <ShareNetworkIcon weight="light" /> Condividi
      </button>
    {/if}

    <!-- Project -->
    {#if !inTrash && showsFor("notebook file diary")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all project"
        onclick={() => dispatchAction("cm-project", { fileId, fileType })}
      >
        <MonitorIcon weight="light" /> Proietta
      </button>
    {/if}

    <!-- Rename -->
    {#if !inTrash && showsFor("folder notebook file")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all rename"
        onclick={() => dispatchAction("cm-rename", { fileId, fileName })}
      >
        <PencilSimpleIcon weight="light" /> Rinomina
      </button>
    {/if}

    <!-- Change icon -->
    {#if !inTrash && showsFor("folder notebook file")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all change-icon"
        onclick={() =>
          dispatchAction("cm-change-icon", { fileId, currentIcon: fileImgSrc })}
      >
        <ImageIcon weight="light" />
        {t("change-icon", "Cambia icona")}
      </button>
    {/if}

    <!-- Cut -->
    {#if !inTrash && showsFor("folder notebook file")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all cut"
        onclick={handleCut}
      >
        <ScissorsIcon weight="light" /> Taglia
      </button>
    {/if}

    <!-- Fav / Desktop -->
    {#if !inTrash && showsFor("folder notebook file diary")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all fav"
        onclick={handleFav}
      >
        <StarIcon weight={fileFav === 1 ? "fill" : "light"} />
        {fileFav === 1
          ? t("remove-from-desktop", "Rimuovi dal Desktop")
          : t("add-to-desktop", "Aggiungi al Desktop")}
      </button>
    {/if}

    <!-- Delete (non-trash) -->
    {#if !inTrash && showsFor("folder notebook file diary")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all delete"
        onclick={() =>
          dispatchAction("cm-delete", { fileId, fileName, inTrash: false })}
      >
        <XIcon weight="light" /> Elimina
      </button>
    {/if}

    <!-- Restore (trash) -->
    {#if inTrash}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all trash restore"
        onclick={handleRestore}
      >
        <ArrowLeftIcon weight="light" /> Ripristina
      </button>

      <!-- Delete definitively (trash) -->
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all trash delete"
        onclick={() =>
          dispatchAction("cm-delete", { fileId, fileName, inTrash: true })}
      >
        <XIcon weight="light" /> Elimina definitivamente
      </button>
    {/if}

    <!-- Properties -->
    {#if showsFor("folder notebook file trash diary")}
      <button
        type="button"
        class="accent-bkg-all-darker box-shadow-1-all property"
        onclick={() => dispatchAction("cm-property", { fileId })}
      >
        <InfoIcon weight="light" /> Proprietà
      </button>
    {/if}
  </div>
{/if}

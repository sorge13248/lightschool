<script lang="ts">
  import type { Snippet } from "svelte";
  import { untrack } from "svelte";
  import { ListIcon, UserIcon, SignOutIcon } from "phosphor-svelte";
  import { fade } from "svelte/transition";
  import ContextMenuFile from "../components/ui/ContextMenuFile.svelte";
  import Notifications from "../components/Notifications.svelte";
  import CookieBar from "../components/CookieBar.svelte";
  import ColorPicker from "../components/ColorPicker.svelte";
  import { t } from "../lib/i18n";
  import { apiFetch } from "../lib/api";
  import type { AppItem, CurrentUser } from "../lib/types";
  import { themePreview } from "../stores/themePreview.svelte";

  interface Props {
    currentUser: CurrentUser;
    currentApp: string;
    pageTitle: string;
    allApps: AppItem[];
    children: Snippet;
  }

  const { currentUser, currentApp, pageTitle, allApps, children }: Props =
    $props();

  const profilePicUrl = $derived(
    currentUser.profile_picture ? currentUser.profile_picture : null,
  );

  let isLauncherOpen = $state(false);
  let searchText = $state("");
  let dockEl = $state<HTMLElement | undefined>(undefined);
  let dockVisible = $state(false);
  let searchInputEl = $state<HTMLInputElement | undefined>(undefined);

  const isSearching = $derived(searchText.trim().length > 0);

  const filteredApps = $derived(
    !isSearching
      ? allApps
      : allApps.filter(
          (app) =>
            app.unique_name.toLowerCase().includes(searchText.toLowerCase()) ||
            t("app-" + app.unique_name)
              .toLowerCase()
              .includes(searchText.toLowerCase()),
        ),
  );

  const noResults = $derived(isSearching && filteredApps.length === 0);

  function openLauncher(): void {
    isLauncherOpen = true;
    if (window.outerHeight > 500) {
      setTimeout(() => searchInputEl?.focus(), 100);
    }
  }

  function closeLauncher(): void {
    isLauncherOpen = false;
    searchText = "";
  }

  function toggleLauncher(): void {
    isLauncherOpen ? closeLauncher() : openLauncher();
  }

  // ── Wallpaper — merge currentUser with themePreview overrides ─────────────
  const wallpaperBgStyle = $derived.by(() => {
    const w = currentUser.wallpaper;
    if (!w && themePreview.wallpaperFileId === null) return null;
    const fileId = themePreview.wallpaperFileId ?? w?.id;
    const blur = themePreview.wallpaperBlur ?? w?.blur ?? 0;
    const blurCss = blur > 0 ? `; filter: blur(${blur}px)` : "";
    return `background-image: url('/api/file/${fileId}')${blurCss}`;
  });

  const wallpaperOverlayStyle = $derived.by(() => {
    const w = currentUser.wallpaper;
    if (!w && themePreview.wallpaperFileId === null) return null;
    if (themePreview.wallpaperColor !== null)
      return themePreview.wallpaperColor;
    if (!w) return null;
    const parts = w.color.split(",").map((s: string) => parseInt(s.trim(), 10));
    const [r, g, b] = parts.length === 3 ? parts : [0, 0, 0];
    return `background-color: rgba(${r}, ${g}, ${b}, ${w.opacity})`;
  });

  // ── Accent colors — use themePreview.accent when SettingsCustomize is live ──
  const accent = $derived.by(() => {
    const raw = (themePreview.accent ?? currentUser.accent ?? "1e6ad3")
      .replace("#", "")
      .padEnd(6, "0")
      .slice(0, 6);
    const r = parseInt(raw.slice(0, 2), 16) || 30;
    const g = parseInt(raw.slice(2, 4), 16) || 107;
    const b = parseInt(raw.slice(4, 6), 16) || 201;
    const lr = Math.round(r + (255 - r) * 0.25);
    const lg = Math.round(g + (255 - g) * 0.25);
    const lb = Math.round(b + (255 - b) * 0.25);
    const dr = Math.round(r * 0.8);
    const dg = Math.round(g * 0.8);
    const db = Math.round(b * 0.8);
    return {
      bg: `linear-gradient(to right, rgba(${r},${g},${b},0.60), rgba(${lr},${lg},${lb},0.60))`,
      shadow: `0 2px 10px 0 rgba(0,0,0,0.5)`,
      hover: `rgba(${dr},${dg},${db},0.65)`,
      iconHover: `rgba(${r},${g},${b},0.80)`,
      iconShadow: `0 0 37px -8px rgba(${r},${g},${b},1)`,
    };
  });

  // ── Dock drag-and-drop reorder ───────────────────────────────────────────
  let taskbar = $state(untrack(() => [...currentUser.taskbar]));
  let dragSrcId = $state<number | null>(null);
  let dragOverId = $state<number | null>(null);
  let saveTimer: ReturnType<typeof setTimeout> | null = null;

  function onDragStart(id: number): void {
    dragSrcId = id;
  }

  function onDragOver(e: DragEvent, id: number): void {
    e.preventDefault();
    if (dragSrcId === null || dragSrcId === id) return;
    dragOverId = id;
    const from = taskbar.findIndex((a) => a.id === dragSrcId);
    const to = taskbar.findIndex((a) => a.id === id);
    if (from === -1 || to === -1 || from === to) return;
    const next = [...taskbar];
    next.splice(to, 0, next.splice(from, 1)[0]);
    taskbar = next;
  }

  function onDragEnd(): void {
    dragSrcId = null;
    dragOverId = null;
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      apiFetch(
        "/api/settings?type=reorder-taskbar",
        "POST",
        `taskbar=${encodeURIComponent(taskbar.map((a) => a.id).join(","))}`,
      );
    }, 300);
  }

  // ── Dock slide-up animation: freeze min-width then reveal via CSS class ──────
  $effect(() => {
    if (!dockEl) return;
    dockEl.style.minWidth = dockEl.offsetWidth + "px";
    // One rAF so the browser registers the initial bottom: -200px before transitioning
    requestAnimationFrame(() => {
      dockVisible = true;
    });
  });

  function onKeydown(e: KeyboardEvent): void {
    const tag = (e.target as Element).tagName ?? "";
    const editable = (e.target as HTMLElement).isContentEditable;
    if (
      e.key.toLowerCase() === "s" &&
      !editable &&
      tag !== "INPUT" &&
      tag !== "TEXTAREA" &&
      tag !== "SELECT"
    ) {
      toggleLauncher();
    }
  }
</script>

<svelte:head>
  {#if currentUser.wallpaper}
    <style>
      html,
      body,
      .content-my {
        background-color: transparent !important;
      }
    </style>
  {/if}
</svelte:head>

<svelte:window onkeydown={onKeydown} />
<svelte:document class:no-scroll={isLauncherOpen} />

<!-- ── Root accent vars scoped to this layout ──────────────────────────────── -->
<div
  class="layout-root"
  style:--a-hover={accent.hover}
  style:--a-icon-hover={accent.iconHover}
  style:--a-icon-shadow={accent.iconShadow}
>
  <!-- ── Wallpaper ───────────────────────────────────────────────────────────── -->
  {#if wallpaperBgStyle}
    <div class="wallpaper" style={wallpaperBgStyle}></div>
    <div class="wallpaper-overlay" style={wallpaperOverlayStyle ?? ""}></div>
  {/if}

  <!-- ── Mobile top bar ───────────────────────────────────────────────────────── -->
  <header
    class="mobile-bar no-print"
    style:background={accent.bg}
    style:box-shadow={accent.shadow}
  >
    <button
      type="button"
      class="menu-btn"
      onclick={toggleLauncher}
      aria-label="Menu"
    >
      <ListIcon weight="light" />
    </button>
    <h5 class="page-title">{pageTitle}</h5>
  </header>

  <!-- ── Taskbar / Dock ───────────────────────────────────────────────────────── -->
  <nav
    class="dock no-print"
    class:big={currentUser.taskbar_size === 2}
    class:small={currentUser.taskbar_size === 1}
    class:dock-visible={dockVisible}
    style:background={accent.bg}
    style:box-shadow={accent.shadow}
    bind:this={dockEl}
    aria-label="Taskbar"
  >
    <button
      type="button"
      class="dock-btn"
      onclick={toggleLauncher}
      title="{currentUser.name} {currentUser.surname}"
      aria-label="Application launcher"
    >
      {#if profilePicUrl}
        <img src={profilePicUrl} class="profile-pic" alt="" />
      {:else}
        <UserIcon weight="light" class="profile-icon" />
      {/if}
    </button>

    {#each taskbar as app (app.id)}
      <a
        href="/my/app/{app.unique_name}"
        class="dock-item"
        class:active={currentApp === app.unique_name}
        class:drag-over={dragOverId === app.id}
        title={t("app-" + app.unique_name)}
        data-app-name={app.unique_name}
        draggable="true"
        ondragstart={() => onDragStart(app.id)}
        ondragover={(e) => onDragOver(e, app.id)}
        ondragend={onDragEnd}
      >
        <img
          src="/img/app-icons/{app.unique_name}/white/icon.png"
          alt={t("app-" + app.unique_name)}
        />
      </a>
    {/each}
  </nav>

  <!-- ── Application Launcher ─────────────────────────────────────────────────── -->
  {#if isLauncherOpen}
    <div
      class="launcher no-print"
      style:background={accent.bg}
      transition:fade={{ duration: 150 }}
      role="dialog"
      aria-modal="true"
      aria-label={t("search", "Search")}
    >
      <div class="launcher-layout" class:searching={isSearching}>
        <!-- Apps grid (hidden while searching) -->
        {#if !isSearching}
          <section class="apps-section">
            <div class="apps-grid">
              {#each allApps as app (app.unique_name)}
                <a
                  href="/my/app/{app.unique_name}"
                  class="app-icon"
                  class:active={currentApp === app.unique_name}
                  title={t("app-" + app.unique_name)}
                  data-app-name={app.unique_name}
                >
                  <img
                    src="/img/app-icons/{app.unique_name}/white/icon.png"
                    alt={t("app-" + app.unique_name)}
                  />
                  {t("app-" + app.unique_name)}
                </a>
              {/each}
            </div>
          </section>
        {/if}

        <!-- Sidebar: search input + results / quick links -->
        <aside class="launcher-sidebar">
          <div class="sidebar-inner">
            <form
              method="get"
              action="/my/app/search"
              onsubmit={(e) => e.preventDefault()}
            >
              <input
                type="text"
                id="search"
                name="search"
                class="search-input"
                placeholder={t("search")}
                bind:value={searchText}
                bind:this={searchInputEl}
                autocomplete="off"
              />
            </form>

            {#if isSearching}
              <!-- Search results -->
              <div class="search-results">
                {#each filteredApps as app (app.unique_name)}
                  <a
                    href="/my/app/{app.unique_name}"
                    class="app-icon"
                    class:active={currentApp === app.unique_name}
                    title={t("app-" + app.unique_name)}
                    data-app-name={app.unique_name}
                  >
                    <img
                      src="/img/app-icons/{app.unique_name}/white/icon.png"
                      alt={t("app-" + app.unique_name)}
                    />
                    {t("app-" + app.unique_name)}
                  </a>
                {/each}
                {#if noResults}
                  <p class="no-results">
                    {t("search-no-result", "No results for")}
                    "<span class="searched-text">{searchText}</span>"
                  </p>
                {/if}
              </div>
            {:else}
              <!-- Quick links -->
              <nav class="quick-links" aria-label="Quick links">
                <a
                  href="/my"
                  class="quick-link"
                  title="{currentUser.name} {currentUser.surname}"
                >
                  {#if profilePicUrl}
                    <img src={profilePicUrl} class="quick-profile-pic" alt="" />
                  {:else}
                    <UserIcon weight="light" size={24} />
                  {/if}
                </a>
                <a
                  href="/my/app/settings"
                  class="quick-link"
                  title={t("app-settings")}
                >
                  <img
                    src="/img/app-icons/settings/white/icon.png"
                    alt={t("app-settings")}
                  />
                </a>
                <a href="/auth/logout" class="quick-link" title={t("logout")}>
                  <SignOutIcon weight="light" size={32} />
                </a>
              </nav>
            {/if}
          </div>
        </aside>
      </div>
    </div>
  {/if}

  <!-- ── Page content ──────────────────────────────────────────────────────────── -->
  {@render children()}
  <CookieBar />
  <ColorPicker />

  <Notifications />
  <ContextMenuFile currentFolder="" />
</div>

<!-- ── /layout-root ── -->

<style lang="scss">
  @property --dock-zindex {
    syntax: "<integer>";
    inherits: false;
    initial-value: 7000;
  }

  :global(html.no-scroll) {
    overflow-y: hidden;
  }

  .layout-root {
    display: contents;
  }

  .wallpaper,
  .wallpaper-overlay {
    position: fixed;
    inset: 0;
    z-index: -1;
    pointer-events: none;
  }

  .wallpaper {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  // ── Mobile top bar ────────────────────────────────────────────────────────

  .mobile-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 10px;
    height: 56px;
    z-index: var(--dock-zindex);
    background: var(--a-bg);
    box-shadow: 0 3px 16px 0 var(--a-shadow);
    color: white;

    @media (min-width: 577px) {
      display: none;
    }
    @media print {
      display: none;
    }

    .menu-btn {
      background: none;
      border: none;
      color: white;
      cursor: pointer;
      font-size: 1.2em;
      padding: 5px 8px;
      flex-shrink: 0;
      line-height: 1;
    }

    .page-title {
      font-weight: bold;
      font-size: 1em;
      margin: 0;
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  // ── Taskbar / Dock ────────────────────────────────────────────────────────

  .dock {
    position: fixed;
    bottom: -200px;
    left: 50%;
    transform: translateX(-50%);
    z-index: var(--dock-zindex);
    display: flex;
    backdrop-filter: blur(5px);
    padding: 0.5rem;
    border-radius: 0.5rem;
    gap: 0.5rem;
    transition: bottom 1s;

    &.dock-visible {
      bottom: 1rem;
    }

    @media (max-width: 576px) {
      display: none !important;
    }
    @media print {
      display: none !important;
    }

    > * {
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 0.2rem 0.5rem;
      border-radius: 0.5rem;
      cursor: pointer;
      color: white;
      text-decoration: none;
      background: none;
      border: none;
      transition: background-color 0.2s ease-in-out;

      &:hover {
        background: var(--a-hover);
        box-shadow: none;
      }

      img {
        display: block;
        width: 24px;
        height: 24px;
        margin: 5px;
      }
    }

    .profile-pic {
      display: block;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      margin: 5px;
    }

    &.big {
      .dock-btn img,
      .dock-item img,
      .profile-pic {
        width: 32px !important;
        height: 32px !important;
        margin: 8px !important;
      }
    }

    &.small {
      .dock-btn img,
      .dock-item img,
      .profile-pic {
        width: 16px !important;
        height: 16px !important;
        margin: 2px !important;
      }
    }
  }

  // ── Application Launcher ──────────────────────────────────────────────────

  .launcher {
    position: fixed;
    inset: 0;
    z-index: calc(var(--dock-zindex) - 1);
    backdrop-filter: blur(5px);
    padding-top: 40px;
    color: white;

    @media print {
      display: none;
    }

    a {
      color: white;
      text-decoration: none;
    }
  }

  .launcher-layout {
    display: grid;
    grid-template-columns: 1fr 20rem;
    height: 100%;

    &.searching {
      grid-template-columns: 1fr;
    }

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      grid-template-rows: auto 1fr;

      .apps-section {
        order: 2;
      }
      .launcher-sidebar {
        order: 1;
      }
    }
  }

  .apps-section {
    overflow-y: auto;
    padding: 10px;
  }

  .apps-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-content: flex-start;
    padding: 10px;
  }

  .app-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    width: 160px;
    height: 160px;
    margin: 10px;
    padding-top: 0;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    text-align: center;
    font-size: 0.85em;
    transition:
      background 0.2s ease-in-out,
      box-shadow 0.2s ease-in-out;
    overflow: hidden;
    box-sizing: border-box;
    word-break: break-word;

    @media (max-width: 576px) {
      width: 100px;
      height: 100px;
      margin: 5px;
      font-size: 0.75em;
    }

    img {
      display: block;
      width: 64px;
      height: 64px;
      margin: 0 auto 10px;
      object-fit: contain;

      @media (max-width: 576px) {
        width: 32px;
        height: 32px;
        margin-bottom: 6px;
      }
    }

    &:hover,
    &:focus,
    &.active {
      background: var(--a-icon-hover);
      box-shadow: var(--a-icon-shadow);
    }
  }

  .launcher-sidebar {
    overflow-y: auto;
  }

  .sidebar-inner {
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .search-input {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 11pt;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    color: black;
    opacity: 0.5;
    transition: opacity 0.2s ease-in-out;
    box-sizing: border-box;

    &:focus,
    &:hover {
      opacity: 1;
      outline: none;
    }
  }

  .search-results {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    overflow-y: auto;
    max-height: calc(100vh - 140px);
  }

  .no-results {
    color: white;
    text-align: center;
    padding: 20px 10px;
    width: 100%;

    .searched-text {
      font-style: italic;
    }
  }

  .quick-links {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    .quick-link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: calc(50% - 3px);
      padding: 10px;
      border-radius: 8px;
      color: white;
      text-decoration: none;
      font-size: 1.2em;
      transition:
        background 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;

      &:hover {
        background: var(--a-hover);
        box-shadow: var(--a-icon-shadow);
      }

      img {
        display: block;
        width: 32px;
        height: 32px;
        object-fit: contain;
      }

      .quick-profile-pic {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
      }
    }
  }
</style>

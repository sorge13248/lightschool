<script lang="ts">
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";
  import Modal from "../ui/Modal.svelte";

  // ── State ────────────────────────────────────────────────────────────────────

  let open        = $state(false);
  let fileId      = $state("");
  let search      = $state("");
  let selected    = $state<string | null>(null); // bare filename, null = default
  let icons        = $state<string[]>([]);
  let loading      = $state(false);
  let iconsLoaded  = false;
  let recenterKey  = $state(0);

  const filtered = $derived(
    search.trim() === ""
      ? icons
      : icons.filter(name =>
          name.replace(/\.[^.]+$/, "").toLowerCase().includes(search.trim().toLowerCase())
        )
  );

  // ── Listen for cm-change-icon ─────────────────────────────────────────────────

  $effect(() => {
    async function handler(e: Event): Promise<void> {
      const { fileId: id } = (e as CustomEvent).detail as { fileId: string };
      fileId   = id;
      search   = "";
      selected = null;
      open     = true;

      if (!iconsLoaded) {
        try {
          const res = await apiFetch("/api/file-manager?type=list-icons");
          if (res.response === "success") {
            icons       = res.icons as string[];
            iconsLoaded = true;
            recenterKey++;
          }
        } catch { /* silent */ }
      }
    }

    window.addEventListener("cm-change-icon", handler);
    return () => window.removeEventListener("cm-change-icon", handler);
  });

  // ── Actions ──────────────────────────────────────────────────────────────────

  async function apply(): Promise<void> {
    loading = true;
    try {
      const res = await apiFetch(
        "/api/file-manager?type=set-icon&id=" + fileId,
        "POST",
        selected ? "icon=" + encodeURIComponent(selected) : "icon=",
      );
      if (res.response === "success") {
        window.dispatchEvent(new CustomEvent("cm-icon-changed", {
          detail: { fileId, icon: res.icon as string | null },
        }));
        notifications.add(res.text as string, { autoClose: 2000 });
        open = false;
      } else {
        notifications.add(res.text as string, { type: "error", autoClose: 3000 });
      }
    } catch { /* silent */ }
    finally { loading = false; }
  }

  async function reset(): Promise<void> {
    selected = null;
    await apply();
  }
</script>

<Modal
  bind:open
  title={t("change-icon", "Cambia icona")}
  maxWidth="660px"
  draggable
  {recenterKey}
  onclose={() => { open = false; }}
>
  {#snippet children()}
    <!-- Search -->
    <div class="search-wrap">
      <input
        type="text"
        placeholder={t("search-icons", "Cerca icona...")}
        bind:value={search}
        class="box-shadow-1-all"
        autocomplete="off"
      />
    </div>

    <!-- Grid -->
    <div class="icon-grid">
      {#each filtered as name (name)}
        {@const label = name.replace(/\.[^.]+$/, "")}
        <button
          type="button"
          title={label}
          class="icon-btn"
          class:is-selected={selected === name}
          onclick={() => { selected = name; }}
        >
          <img
            src="/img/color/{name}"
            alt={label}
            width="40"
            height="40"
            loading="lazy"
          />
          <span class="icon-label">{label}</span>
        </button>
      {/each}

      {#if filtered.length === 0 && icons.length > 0}
        <p class="empty-msg">{t("no-icons-found", "Nessuna icona trovata.")}</p>
      {/if}
    </div>

    <!-- Buttons -->
    <div class="actions">
      <button
        type="button"
        class="button box-shadow-1-all"
        onclick={reset}
        disabled={loading}
      >
        {t("reset-default", "Ripristina predefinita")}
      </button>
      <button
        type="button"
        class="button box-shadow-1-all"
        onclick={() => { open = false; }}
        disabled={loading}
      >
        {t("close", "Chiudi")}
      </button>
      <button
        type="button"
        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        onclick={apply}
        disabled={loading || selected === null}
      >
        {t("apply", "Applica")}
      </button>
    </div>
  {/snippet}
</Modal>

<style lang="scss">
  @use '../../../scss/variables' as *;

  .search-wrap {
    margin-bottom: 14px;

    input {
      width: 100%;
      box-sizing: border-box;
    }
  }

  .icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 6px;
    max-height: 360px;
    overflow-y: auto;
    padding: 2px;
  }

  .icon-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 6px 4px;
    border: 2px solid transparent;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    @include transition;

    img {
      object-fit: contain;
    }

    &.is-selected {
      border-color: var(--ac-hex, #{$accent-flat});
      background: rgba(30, 106, 211, 0.12);
    }

    &:hover:not(.is-selected) {
      background: rgba(0, 0, 0, 0.06);
    }
  }

  .icon-label {
    font-size: 0.65em;
    word-break: break-all;
    text-align: center;
    line-height: 1.2;
    max-width: 66px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .empty-msg {
    color: gray;
    grid-column: 1 / -1;
  }

  .actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 16px;
  }

  @media (prefers-color-scheme: dark) {
    .icon-label {
      color: #fff;
    }

    .icon-btn {
      &:hover:not(.is-selected) {
        background: rgba(255, 255, 255, 0.08);
      }
    }

    .empty-msg {
      color: #aaa;
    }
  }
</style>

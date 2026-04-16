<script lang="ts">
  import { onMount } from "svelte";
  import { UserIcon } from "phosphor-svelte";
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";
  import type { SharedUser, SuggestContact } from "../../lib/types";
  import Modal from "../ui/Modal.svelte";
  import LoadingPlaceholder from "../ui/LoadingPlaceholder.svelte";

  // ── Share state ──────────────────────────────────────────────────────────────
  let open = $state(false);
  let fileId = $state("");
  let fileName = $state("");
  let username = $state("");
  let error = $state("");
  let loading = $state(false);
  let users = $state<SharedUser[]>([]);
  let usersLoading = $state(false);
  let contacts = $state<SuggestContact[]>([]);

  const filteredContacts = $derived(
    username.trim().length === 0
      ? []
      : contacts.filter((c) => {
          const q = username.toLowerCase();
          return (
            c.name.toLowerCase().includes(q) ||
            c.username.toLowerCase().includes(q)
          );
        }),
  );

  const maxWidth = $derived(users.length > 0 ? "650px" : "450px");

  // ── Stop-share state ─────────────────────────────────────────────────────────
  let stopOpen = $state(false);
  let stopShareId = $state("");
  let stopUserName = $state("");
  let stopError = $state("");
  let stopLoading = $state(false);

  // ── API ──────────────────────────────────────────────────────────────────────
  async function loadUsers(): Promise<void> {
    usersLoading = true;
    try {
      const res = await apiFetch("/api/share?type=file-shared&id=" + fileId);
      users = (res["users"] as SharedUser[]) ?? [];
    } catch {
      /* silent */
    } finally {
      usersLoading = false;
    }
  }

  async function loadContacts(): Promise<void> {
    try {
      const res = await apiFetch("/api/contact?type=get-contacts");
      contacts = ((res["contacts"] as any[]) ?? []).map((c: any) => ({
        name: (
          (c.name ?? c.ue_name ?? "") +
          " " +
          (c.surname ?? c.ue_surname ?? "")
        ).trim(),
        username: c.username ?? "",
        profile_picture: c.profile_picture ?? null,
      }));
    } catch {
      /* silent */
    }
  }

  $effect(() => {
    if (!open) return;
    void loadUsers();
    if (contacts.length === 0) void loadContacts();
  });

  async function handleAdd(): Promise<void> {
    loading = true;
    error = "";
    try {
      const res = await apiFetch(
        "/api/share?type=add&id=" + fileId,
        "POST",
        "username=" + encodeURIComponent(username),
      );
      if (res.response === "success") {
        username = "";
        await loadUsers();
        notifications.add(res.text, { autoClose: 2000 });
      } else {
        error = res.text;
      }
    } catch {
      error = "Error";
    } finally {
      loading = false;
    }
  }

  async function handleStopShare(): Promise<void> {
    stopLoading = true;
    stopError = "";
    try {
      const res = await apiFetch(
        "/api/share?type=delete&id=" + stopShareId + "&file_id=" + fileId,
        "POST",
        "",
      );
      if (res.response === "success") {
        stopOpen = false;
        await loadUsers();
        notifications.add(res.text, { autoClose: 2000 });
      } else {
        stopError = res.text;
      }
    } catch {
      stopError = "Error";
    } finally {
      stopLoading = false;
    }
  }

  onMount(() => {
    const handler = (e: Event): void => {
      const d = (e as CustomEvent).detail as {
        fileId: string;
        fileName: string;
      };
      fileId = d.fileId;
      fileName = d.fileName;
      username = "";
      error = "";
      users = [];
      open = true;
    };
    window.addEventListener("cm-share", handler);
    return () => window.removeEventListener("cm-share", handler);
  });
</script>

{#snippet userRow(profilePicture: number | null | undefined, name: string)}
  {#if profilePicture}
    <img
      src="/api/file/{profilePicture}"
      class="user_image"
      style="width:48px;height:48px;float:left;margin-right:10px;border-radius:50%"
      alt=""
    />
  {:else}
    <UserIcon
      weight="light"
      class="user_image"
      style="font-size:32px;float:left;margin-right:10px;line-height:48px"
    />
  {/if}
  <span class="user-name">{name}</span>
{/snippet}

<!-- Share modal -->
<Modal
  {open}
  title={t("share", "Condividi") + ' "' + fileName + '"'}
  {maxWidth}
  draggable
  onclose={() => {
    open = false;
  }}
>
  {#snippet children()}
    <div class="row">
      <div class="col">
        <p>
          {t("share-intro", "Puoi condividere")} "{fileName}"
          {t("share-intro2", "per permettere ad altri di visualizzarlo.")}.
        </p>
        <p class="small">{t("share-with", "Condividi con:")}</p>
        <form
          onsubmit={(e) => {
            e.preventDefault();
            void handleAdd();
          }}
        >
          <input
            type="text"
            id="username"
            name="username"
            bind:value={username}
            placeholder={t(
              "username-or-contact",
              "Nome utente o nome del contatto",
            )}
            class="box-shadow-1-all"
            style="width: calc(100% - 10px)"
            autocomplete="off"
          />
          {#if username.trim().length > 0}
            {#if filteredContacts.length === 0}
              <p class="no-result small" style="margin-bottom: -40px">
                {t(
                  "no-contact-result",
                  "Nessun risultato trovato nei tuoi contatti",
                )}
              </p>
            {:else}
              <div class="contact-list-suggestion">
                {#each filteredContacts as c (c.username)}
                  <!-- svelte-ignore a11y_interactive_supports_focus -->
                  <button
                    type="button"
                    class="accent-all box-shadow-1-all"
                    title={c.name}
                    onclick={() => {
                      username = c.username;
                    }}
                  >
                    {@render userRow(c.profile_picture, c.name)}
                  </button>
                {/each}
              </div>
            {/if}
          {/if}
          <input
            type="submit"
            value={t("share", "Condividi")}
            class="start-sharing button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            style="float: right"
            disabled={loading}
          />
          {#if error}
            <div
              class="response alert alert-danger"
              style="clear: both; margin-top: 10px"
            >
              {error}
            </div>
          {/if}
        </form>
        {#if !usersLoading && users.length === 0}
          <p class="small not_sharing" style="clear: both">
            {t("not-sharing", "Non stai condividendo con nessuno")}
          </p>
        {/if}
      </div>

      {#if usersLoading}
        <div class="col"><LoadingPlaceholder /></div>
      {:else if users.length > 0}
        <div class="col sharing">
          <p class="small">{t("sharing-with", "Stai condividendo con:")}</p>
          <ul style="padding-left: 0">
            {#each users as u (u.id)}
              {@const userName = (
                (u.name ?? "") +
                " " +
                (u.surname ?? "")
              ).trim()}
              <li
                class="list no-transition accent-all box-shadow-1-all img-change-to-white"
                style="display: block"
              >
                <button
                  type="button"
                  style="text-decoration: none; background: none; border: none; padding: 0; width: 100%; text-align: left; cursor: pointer; color: inherit;"
                  onclick={() => {
                    stopShareId = u.id;
                    stopUserName = userName;
                    stopError = "";
                    stopOpen = true;
                  }}
                >
                  {@render userRow(u.profile_picture, userName)}
                </button>
              </li>
            {/each}
          </ul>
        </div>
      {/if}
    </div>
  {/snippet}
</Modal>

<!-- Stop-share confirmation modal -->
<Modal
  open={stopOpen}
  title={t("confirm", "Conferma")}
  maxWidth="450px"
  draggable
  onclose={() => {
    stopOpen = false;
  }}
>
  {#snippet children()}
    <form
      onsubmit={(e) => {
        e.preventDefault();
        void handleStopShare();
      }}
    >
      <p>
        {t("stop-share-confirm", "Vuoi interrompere la condivisione di")}
        "{fileName}" {t("with", "con")}
        {stopUserName}?
      </p>
      <input
        type="submit"
        value={t("confirm", "Conferma")}
        class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        style="float: right"
        disabled={stopLoading}
      />
      {#if stopError}
        <div
          class="response alert alert-danger"
          style="clear: both; margin-top: 10px"
        >
          {stopError}
        </div>
      {/if}
    </form>
  {/snippet}
</Modal>

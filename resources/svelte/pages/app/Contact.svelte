<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { onMount } from "svelte";
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";
  import Modal from "../../components/ui/Modal.svelte";
  import { UserIcon, ChatIcon, ShareNetworkIcon, XIcon, TrashIcon, StarIcon } from 'phosphor-svelte';
  import ActionButton from "../../components/ui/ActionButton.svelte";
  import LoadingPlaceholder from "../../components/ui/LoadingPlaceholder.svelte";

  interface ContactItem {
    id: number;
    name: string;
    surname: string;
    username: string;
    ue_name: string | null;
    ue_surname: string | null;
    profile_picture: string | null;
    fav: number;
    blocked: number;
    contact_id: number;
  }

  const {} = $props();

  // ── State ──────────────────────────────────────────────────────────────────
  let contacts = $state<ContactItem[]>([]);
  let loading = $state(true);
  let hasMore = $state(false);

  // new-contact modal
  let showNewContact = $state(false);
  let newContactName = $state("");
  let newContactSurname = $state("");
  let newContactUsername = $state("");
  let newContactLoading = $state(false);
  let newContactError = $state("");

  // detail modal
  let selectedContact = $state<ContactItem | null>(null);
  let showContactDetail = $state(false);

  // delete confirm modal
  let showDeleteConfirm = $state(false);
  let contactToDelete = $state<ContactItem | null>(null);
  let deleteLoading = $state(false);
  let deleteError = $state("");

  // ── Derived ────────────────────────────────────────────────────────────────
  const groupedContacts = $derived.by(() => {
    const groups: Array<{ letter: string; contacts: ContactItem[] }> = [];
    let currentLetter = "";
    for (const c of contacts) {
      const print = `${c.name} ${c.surname}`.trim();
      const letter = print.charAt(0).toLowerCase();
      if (letter !== currentLetter) {
        currentLetter = letter;
        groups.push({ letter, contacts: [] });
      }
      groups[groups.length - 1].contacts.push(c);
    }
    return groups;
  });

  // ── Methods ────────────────────────────────────────────────────────────────
  async function loadContacts(start = 0, reload = false): Promise<void> {
    if (start === 0) loading = true;
    try {
      const res = await apiFetch(
        `/api/contact?type=get-contacts&limit=${start}`,
      );
      if (res["response"] === "success") {
        const fetched = (res["contacts"] as ContactItem[]) ?? [];
        if (reload || start === 0) {
          contacts = fetched;
        } else {
          contacts = [...contacts, ...fetched];
        }
        hasMore = fetched.length >= 20;
      }
    } catch {
      // silent
    } finally {
      loading = false;
    }
  }

  function openContactDetail(contact: ContactItem): void {
    selectedContact = contact;
    showContactDetail = true;
  }

  async function handleBlock(contact: ContactItem): Promise<void> {
    try {
      const body = `username=${encodeURIComponent(contact.username)}`;
      const res = await apiFetch(`/api/contact?type=block`, "POST", body);
      if (res["response"] === "success") {
        const wasBlocked = contact.blocked === 1;
        // update in-place
        contacts = contacts.map((c) =>
          c.id === contact.id ? { ...c, blocked: wasBlocked ? 0 : 1 } : c,
        );
        if (selectedContact?.id === contact.id) {
          selectedContact = { ...selectedContact, blocked: wasBlocked ? 0 : 1 };
        }
        notifications.add(res["text"] as string);
      } else {
        notifications.add(res["text"] as string, { type: "error" });
      }
    } catch {
      notifications.add(t("error", "Errore"), { type: "error" });
    }
  }

  async function handleFav(contact: ContactItem): Promise<void> {
    const action = contact.fav === 1 ? "remove" : "add";
    try {
      const res = await apiFetch(
        `/api/contact?type=fav&action=${action}&id=${contact.id}`,
        "POST",
        "",
      );
      if (res["response"] === "success") {
        const newFav = action === "add" ? 1 : 0;
        contacts = contacts.map((c) =>
          c.id === contact.id ? { ...c, fav: newFav } : c,
        );
        if (selectedContact?.id === contact.id) {
          selectedContact = { ...selectedContact, fav: newFav };
        }
        notifications.add(res["text"] as string);
      } else {
        notifications.add(res["text"] as string, { type: "error" });
      }
    } catch {
      notifications.add(t("error", "Errore"), { type: "error" });
    }
  }

  async function handleDeleteContact(): Promise<void> {
    if (!contactToDelete) return;
    deleteLoading = true;
    deleteError = "";
    try {
      const res = await apiFetch(
        `/api/contact?type=delete&id=${contactToDelete.id}`,
        "POST",
        "type=delete",
      );
      if (res["response"] === "success") {
        showDeleteConfirm = false;
        showContactDetail = false;
        contactToDelete = null;
        selectedContact = null;
        await loadContacts(0, true);
        notifications.add(res["text"] as string);
      } else {
        deleteError = (res["text"] as string) ?? t("error", "Errore");
      }
    } catch {
      deleteError = t("error", "Errore");
    } finally {
      deleteLoading = false;
    }
  }

  async function handleNewContact(): Promise<void> {
    newContactLoading = true;
    newContactError = "";
    try {
      const body = [
        `name=${encodeURIComponent(newContactName)}`,
        `surname=${encodeURIComponent(newContactSurname)}`,
        `username=${encodeURIComponent(newContactUsername)}`,
      ].join("&");
      const res = await apiFetch("/api/contact?type=create", "POST", body);
      if (res["response"] === "success") {
        showNewContact = false;
        newContactName = "";
        newContactSurname = "";
        newContactUsername = "";
        await loadContacts(0, true);
        notifications.add(res["text"] as string);
      } else {
        newContactError = (res["text"] as string) ?? t("error", "Errore");
      }
    } catch {
      newContactError = t("error", "Errore");
    } finally {
      newContactLoading = false;
    }
  }

  // ── Lifecycle ──────────────────────────────────────────────────────────────
  onMount(() => {
    loadContacts();
  });
</script>

<svelte:head><title>{t("app-contact")} - LightSchool</title></svelte:head>

<div class="container content-my contact">
  <ActionButton
    onclick={() => {
      showNewContact = true;
    }}
    title={t("new", "Nuovo")}
  />

  <div class="section contacts">
    {#if loading}
      <LoadingPlaceholder />
    {:else if contacts.length === 0}
      <p class="search-no-result">
        {t("no-results", "Nessun contatto trovato.")}
      </p>
    {:else}
      {#each groupedContacts as group}
        <br /><span style="font-weight:bold">{group.letter.toUpperCase()}</span
        ><br />
        {#each group.contacts as c}
          <!-- svelte-ignore a11y_invalid_attribute -->
          <a
            href="/my/app/reader/contact/{c.id}"
            class="icon img-change-to-white contact_icon selectable accent-all box-shadow-1-all"
            style="display:inline-block"
            title="{c.name} {c.surname}"
            data-contact-username={c.username}
            data-contact-id={String(c.id)}
            onclick={(e) => {
              e.preventDefault();
              openContactDetail(c);
            }}
          >
            {#if c.profile_picture}
              <img
                src={c.profile_picture}
                alt="{c.name} {c.surname}"
                style="float:left;border-radius:50%;width:32px;height:32px"
              />
            {:else}
              <UserIcon weight="light" size={20} style="float:left;margin-right:6px" />
            {/if}
            <span style="display:block;font-size:1.2em" class="text-ellipsis"
              >{c.name} {c.surname}</span
            >
          </a>
        {/each}
      {/each}
    {/if}

    {#if hasMore}
      <div style="text-align:center">
        <!-- svelte-ignore a11y_invalid_attribute -->
        <a
          href="#"
          class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
          onclick={(e) => {
            e.preventDefault();
            loadContacts(contacts.length);
          }}
        >
          {t("load-more", "Mostra più elementi")}
        </a>
      </div>
    {/if}
  </div>

  <!-- Contact detail modal -->
  <Modal
    open={showContactDetail}
    title={selectedContact
      ? `${selectedContact.name} ${selectedContact.surname}`
      : ""}
    maxWidth="622px"
    draggable
    onclose={() => {
      showContactDetail = false;
    }}
  >
    {#if selectedContact}
      <span class="contact_id" style="display:none">{selectedContact.id}</span>
      <h3>
        {#if selectedContact.profile_picture}
          <img
            src={selectedContact.profile_picture}
            alt="{selectedContact.name} {selectedContact.surname}"
            style="width:64px;height:64px;border-radius:50%;float:left;margin-right:20px;margin-top:5px"
          />
        {/if}
        <span class="contact_name_and_surname"
          >{selectedContact.name} {selectedContact.surname}</span
        >
      </h3>
      <p style="word-wrap:break-word">
        <b>Nome ufficiale:</b>
        <span class="official_name"
          >{selectedContact.ue_name ?? ""}
          {selectedContact.ue_surname ?? ""}</span
        >
        &bull;
        <b>Username:</b>
        {selectedContact.username}
      </p>
      <a
        href="/my/app/message?username={selectedContact.username}"
        class="button accent-all box-shadow-1-all"
      >
        <ChatIcon weight="light" />Invia messaggio
      </a>
      <a
        href="/my/app/message?attach={selectedContact.username}"
        class="button accent-all box-shadow-1-all"
      >
        <ShareNetworkIcon weight="light" />Condividi contatto
      </a>
      <button
        type="button"
        class="button accent-all box-shadow-1-all"
        onclick={() => handleBlock(selectedContact!)}
      >
        <XIcon weight="light" />{selectedContact.blocked
          ? t("unblock", "Sblocca")
          : t("block", "Blocca")}
      </button>
      <button
        type="button"
        class="button accent-all box-shadow-1-all"
        onclick={() => { contactToDelete = selectedContact; showDeleteConfirm = true; }}
      >
        <TrashIcon weight="light" />Elimina contatto
      </button>
      <button
        type="button"
        class="button accent-all box-shadow-1-all"
        onclick={() => handleFav(selectedContact!)}
      >
        <StarIcon weight="light" />{selectedContact.fav
          ? t("remove-from-desktop", "Rimuovi dal desktop")
          : t("add-to-desktop", "Aggiungi al desktop")}
      </button>
    {/if}
  </Modal>

  <!-- Delete confirm modal -->
  <Modal
    open={showDeleteConfirm}
    title={t("delete-contact", "Elimina contatto")}
    maxWidth="522px"
    draggable
    onclose={() => {
      showDeleteConfirm = false;
    }}
  >
    <p>
      Vuoi eliminare il contatto {contactToDelete?.name}
      {contactToDelete?.surname}?
    </p>
    <button
      type="button"
      class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
      style="float:right"
      disabled={deleteLoading}
      onclick={handleDeleteContact}
    >
      Conferma
    </button>
    {#if deleteError}
      <div class="response alert alert-danger">{deleteError}</div>
    {/if}
  </Modal>

  <!-- New contact modal -->
  <Modal
    open={showNewContact}
    title={t("new", "Nuovo")}
    maxWidth="522px"
    draggable
    onclose={() => {
      showNewContact = false;
    }}
  >
    <input
      type="text"
      bind:value={newContactName}
      placeholder="Nome"
      style="width: calc(50% - 13px)"
    />
    <input
      type="text"
      bind:value={newContactSurname}
      placeholder="Cognome"
      style="width: calc(50% - 13px)"
    /><br />
    <input
      type="text"
      bind:value={newContactUsername}
      placeholder="Username"
      style="width: calc(100% - 150px)"
    />
    <button type="button" class="button" onclick={handleNewContact} disabled={newContactLoading}>
      Aggiungi
    </button>
    {#if newContactError}
      <div class="response alert alert-danger">{newContactError}</div>
    {/if}
  </Modal>
</div>

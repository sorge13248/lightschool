<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import Cookies from "js-cookie";
  import { UserIcon, PaperPlaneIcon } from 'phosphor-svelte';
  import { onMount, tick } from "svelte";
  import ActionButton from "../../components/ui/ActionButton.svelte";
  import Modal from "../../components/ui/Modal.svelte";
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";

  const browserLocale = (
    Cookies.get("language") ??
    navigator.language ??
    "en"
  ).replace("_", "-");

  const chatListDateFormatter = new Intl.DateTimeFormat(browserLocale, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  const chatBubblesDateFormatter = new Intl.DateTimeFormat(browserLocale, {
    dateStyle: "long",
    timeStyle: "short",
  });

  function formatTimestamp(
    raw: string,
    formatter: Intl.DateTimeFormat,
  ): string {
    const d = new Date(raw);
    return isNaN(d.getTime()) ? raw : formatter.format(d);
  }

  // ── Types ─────────────────────────────────────────────────────────────────────

  interface UserInfo {
    name: string;
    surname: string;
    profile_picture: string | null;
  }

  interface ConversationItem {
    id: string;
    user: UserInfo | null;
    date: string;
    new: boolean;
  }

  interface MessageAttachment {
    type: string;
    user?: UserInfo;
  }

  interface ChatMessage {
    id: string;
    sender: string;
    body: string;
    date: string;
    attachment?: MessageAttachment;
  }

  interface ContactItem {
    id: number;
    name: string;
    surname: string;
    username: string;
    profile_picture: string | null;
  }

  // ── Props ─────────────────────────────────────────────────────────────────────

  interface Props {
    openUsername: string;
    openAttach: string;
  }

  const { openUsername, openAttach }: Props = $props();

  // ── Helpers ───────────────────────────────────────────────────────────────────

  /** Encode a UTF-8 string as base64 (replaces deprecated btoa+unescape pattern). */
  function encodeBody(str: string): string {
    const bytes = new TextEncoder().encode(str);
    let binary = "";
    for (const b of bytes) binary += String.fromCharCode(b);
    return btoa(binary);
  }

  // ── State: Conversation list ──────────────────────────────────────────────────

  let conversations = $state<ConversationItem[]>([]);
  let convsLoading = $state(true);
  let convsHasMore = $state(false);

  // ── State: Active chat ────────────────────────────────────────────────────────

  let currentChatId = $state<string | null>(null);
  let chatMessages = $state<ChatMessage[]>([]); // oldest-first
  let chatOtherUser = $state<UserInfo | null>(null);
  let chatCurrentUserId = $state("");
  let chatLoading = $state(false);
  let chatHasMore = $state(false);
  let chatLoadedCount = $state(0);
  let chatEl = $state<HTMLElement | null>(null);

  let replyBody = $state("");
  let replySending = $state(false);
  let drafts = $state<Record<string, string>>({});

  // ── State: New message modal ──────────────────────────────────────────────────

  let showNewMessage = $state(false);
  let newMsgUsername = $state("");
  let newMsgBody = $state("");
  let newMsgLoading = $state(false);
  let newMsgError = $state("");
  let contacts = $state<ContactItem[]>([]);
  let hasAttachment = $state(false);
  let attachType = $state("");
  let attachValue = $state("");

  // ── Derived ───────────────────────────────────────────────────────────────────

  const filteredContacts = $derived(
    newMsgUsername.trim()
      ? contacts.filter((c) => {
          const q = newMsgUsername.toLowerCase();
          const name = (c.name + " " + c.surname).toLowerCase();
          return name.includes(q) || c.username.toLowerCase().includes(q);
        })
      : [],
  );

  // ── Conversations ─────────────────────────────────────────────────────────────

  async function loadConversations(start = 0, refresh = false): Promise<void> {
    if (start === 0) convsLoading = true;
    try {
      const data = await apiFetch(`/api/message?type=list&start=${start}`);
      if (!data || (data as any).response === "error") {
        if (start === 0) conversations = [];
        convsHasMore = false;
        return;
      }
      const list = (Array.isArray(data) ? data : []) as ConversationItem[];
      conversations =
        refresh || start === 0 ? list : [...conversations, ...list];
      convsHasMore = list.length >= 20;
    } catch {
      /* silent */
    } finally {
      convsLoading = false;
    }
  }

  // ── Chat ──────────────────────────────────────────────────────────────────────

  async function openChat(id: string): Promise<void> {
    // Save draft for the previous conversation
    if (currentChatId && replyBody) {
      drafts = { ...drafts, [currentChatId]: replyBody };
    } else if (currentChatId && !replyBody) {
      const { [currentChatId]: _, ...rest } = drafts;
      drafts = rest;
    }

    currentChatId = id;
    chatLoading = true;
    chatMessages = [];
    chatOtherUser = null;
    chatHasMore = false;
    chatLoadedCount = 0;
    replyBody = drafts[id] ?? "";

    // Mark as read in the list
    conversations = conversations.map((c) =>
      c.id === id ? { ...c, new: false } : c,
    );

    try {
      const data = await apiFetch(`/api/message?type=chat&id=${id}`);
      if (!data || (data as any).response === "error") return;
      chatOtherUser = (data as any).other_user as UserInfo;
      chatCurrentUserId = String((data as any).current_user_id ?? "");
      const msgs = ((data as any).chat ?? []) as ChatMessage[];
      chatMessages = msgs.slice().reverse(); // oldest first
      chatHasMore = msgs.length >= 20;
      chatLoadedCount = msgs.length;
    } catch {
      /* silent */
    } finally {
      chatLoading = false;
    }

    await tick();
    if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;
  }

  async function loadOlderMessages(): Promise<void> {
    if (!currentChatId || chatLoading) return;
    chatLoading = true;
    const prevScrollHeight = chatEl?.scrollHeight ?? 0;
    const prevScrollTop = chatEl?.scrollTop ?? 0;
    try {
      const data = await apiFetch(
        `/api/message?type=chat&id=${currentChatId}&start=${chatLoadedCount}`,
      );
      if (!data || (data as any).response === "error") return;
      const older = ((data as any).chat ?? []) as ChatMessage[];
      chatMessages = [...older.slice().reverse(), ...chatMessages];
      chatHasMore = older.length >= 20;
      chatLoadedCount += older.length;
    } catch {
      /* silent */
    } finally {
      chatLoading = false;
    }

    // Preserve scroll position after prepending
    await tick();
    if (chatEl)
      chatEl.scrollTop = chatEl.scrollHeight - prevScrollHeight + prevScrollTop;
  }

  async function sendMessage(): Promise<void> {
    if (!currentChatId || !replyBody.trim() || replySending) return;
    replySending = true;
    const id = currentChatId;
    const payload = encodeBody(replyBody);
    const prev = replyBody;
    replyBody = "";
    const { [id]: _, ...rest } = drafts;
    drafts = rest;

    try {
      const res = await apiFetch(
        `/api/message?type=send&id=${id}`,
        "POST",
        "body=" + payload,
      );
      if (res.response === "success") {
        await openChat(id);
        await loadConversations(0, true);
      } else {
        replyBody = prev; // restore on error
        notifications.add(res.text as string, {
          type: "error",
          autoClose: 2000,
        });
      }
    } catch {
      replyBody = prev;
    } finally {
      replySending = false;
    }
  }

  function saveDraft(): void {
    if (!currentChatId) return;
    if (replyBody) {
      drafts = { ...drafts, [currentChatId]: replyBody };
    } else {
      const { [currentChatId]: _, ...rest } = drafts;
      drafts = rest;
    }
  }

  // ── New message modal ─────────────────────────────────────────────────────────

  async function openNewMessageModal(): Promise<void> {
    showNewMessage = true;
    newMsgUsername = openUsername || "";
    newMsgBody = "";
    newMsgError = "";
    if (openAttach) {
      hasAttachment = true;
      attachType = "contact";
      attachValue = openAttach;
    }
    try {
      const data = await apiFetch("/api/contact?type=get-contacts&limit=0");
      contacts = ((data as any).contacts ?? []) as ContactItem[];
    } catch {
      /* silent */
    }
  }

  async function submitNewMessage(): Promise<void> {
    if (!newMsgUsername.trim() || !newMsgBody.trim() || newMsgLoading) return;
    newMsgLoading = true;
    newMsgError = "";
    const bodyB64 = encodeBody(newMsgBody);
    let bodyStr = `username=${encodeURIComponent(newMsgUsername)}&body=${bodyB64}`;
    if (hasAttachment) {
      const attach = btoa(
        JSON.stringify({ type: attachType, value: attachValue }),
      );
      bodyStr = `attach=${attach}&${bodyStr}`;
    }
    try {
      const res = await apiFetch("/api/message?type=new", "POST", bodyStr);
      notifications.add(res.text as string, { autoClose: 2000 });
      if (res.response === "success") {
        showNewMessage = false;
        newMsgUsername = "";
        newMsgBody = "";
        hasAttachment = false;
        attachType = "";
        attachValue = "";
        await loadConversations(0, true);
        await openChat(String(res.id));
      } else {
        newMsgError = res.text as string;
      }
    } catch {
      newMsgError = t("error", "Errore");
    } finally {
      newMsgLoading = false;
    }
  }

  // ── Lifecycle ─────────────────────────────────────────────────────────────────

  onMount(() => {
    loadConversations();
    if (openUsername || openAttach) openNewMessageModal();
  });
</script>

<svelte:head><title>{t("app-message")} - LightSchool</title></svelte:head>

<div class="container content-my message">
  <!-- FAB: new conversation -->
  <ActionButton onclick={openNewMessageModal} />

  <div class="row">
    <!-- ── Conversation list ──────────────────────────────────────────────── -->
    <div class="col-md-4 conv-col">
      <div class="message-list">
        {#if convsLoading}
          <div class="ph-item">
            <div class="ph-col-12">
              <div class="ph-row">
                <div class="ph-col-6 big"></div>
                <div class="ph-col-4 empty big"></div>
                <div class="ph-col-4"></div>
                <div class="ph-col-8 empty"></div>
                <div class="ph-col-6"></div>
                <div class="ph-col-6 empty"></div>
                <div class="ph-col-12" style="margin-bottom: 0"></div>
              </div>
            </div>
          </div>
        {:else if conversations.length === 0}
          <p style="color: gray">Inizia una nuova conversazione!</p>
        {:else}
          {#each conversations as conv (conv.id)}
            <!-- svelte-ignore a11y_invalid_attribute -->
            <a
              href="#"
              class="list icon img-change-to-white accent-all box-shadow-1-all{conv.new
                ? ' new'
                : ''}{conv.id === currentChatId ? ' selected' : ''}"
              onclick={(e) => {
                e.preventDefault();
                openChat(conv.id);
              }}
            >
              {#if conv.user?.profile_picture}
                <img
                  src={conv.user.profile_picture}
                  alt="{conv.user.name} {conv.user.surname}"
                  class="conv-avatar"
                />
              {:else}
                <UserIcon weight="light" class="conv-avatar-icon" />
              {/if}
              <span class="user">
                {#if drafts[conv.id]}
                  <span style="color: orange">[Bozza]</span>
                {/if}
                {" "}{conv.user ? conv.user.name + " " + conv.user.surname : ""}
              </span>
              <br />
              <span class="date"
                >{formatTimestamp(conv.date, chatListDateFormatter)}</span
              >
            </a>
          {/each}
        {/if}

        {#if convsHasMore}
          <div style="text-align: center; margin-top: 8px">
            <button
              type="button"
              class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
              onclick={() => loadConversations(conversations.length)}
              >Mostra più elementi</button
            >
          </div>
        {/if}
      </div>
    </div>

    <!-- ── Chat panel ────────────────────────────────────────────────────── -->
    <div class="col chat-col">
      {#if currentChatId}
        {#if chatLoading && chatMessages.length === 0}
          <div class="ph-item">
            <div class="ph-col-12">
              <div class="ph-row">
                <div class="ph-col-6 big"></div>
                <div class="ph-col-4 empty big"></div>
                <div class="ph-col-4"></div>
                <div class="ph-col-8 empty"></div>
              </div>
            </div>
          </div>
        {:else if chatOtherUser}
          <div class="chat-opened">
            <!-- Chat header -->
            <h3 class="chat-header">
              {#if chatOtherUser.profile_picture}
                <img
                  src={chatOtherUser.profile_picture}
                  alt="{chatOtherUser.name} {chatOtherUser.surname}"
                  class="chat-avatar"
                />
              {/if}
              <span>&nbsp;{chatOtherUser.name} {chatOtherUser.surname}</span>
            </h3>
            <div style="clear: both"></div>
            <hr style="margin-bottom: 0" />

            <!-- Messages area -->
            <div class="chat-messages" bind:this={chatEl}>
              {#if chatHasMore}
                <div style="text-align: center; padding: 10px">
                  <button
                    type="button"
                    class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                    disabled={chatLoading}
                    onclick={loadOlderMessages}>Mostra più messaggi</button
                  >
                </div>
              {/if}

              {#each chatMessages as msg (msg.id)}
                {@const isMine = String(msg.sender) === chatCurrentUserId}
                <div class="msg-row" class:mine={isMine}>
                  <div class="bubble" class:mine={isMine}>
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    {@html msg.body}
                    {#if msg.attachment?.type === "contact" && msg.attachment.user}
                      <small class="attachment">
                        {#if msg.attachment.user.profile_picture}
                          <img
                            src={msg.attachment.user.profile_picture}
                            alt=""
                            class="att-avatar"
                          />
                        {/if}
                        {msg.attachment.user.name}
                        {msg.attachment.user.surname}
                      </small>
                    {/if}
                    <small class="msg-time"
                      >{formatTimestamp(
                        msg.date,
                        chatBubblesDateFormatter,
                      )}</small
                    >
                  </div>
                </div>
              {/each}

              {#if chatMessages.length === 0 && !chatLoading}
                <p class="empty-chat">Nessun messaggio. Di' ciao!</p>
              {/if}
            </div>

            <!-- Reply form -->
            <div class="reply-box">
              <textarea
                bind:value={replyBody}
                placeholder="Scrivi un messaggio..."
                aria-label="Messaggio"
                class="reply-textarea"
                oninput={saveDraft}
                onkeydown={(e) => {
                  if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                  }
                }}
              ></textarea>
              <button
                type="button"
                class="send-btn"
                disabled={replySending}
                onclick={sendMessage}
                aria-label="Invia"
              >
                <PaperPlaneIcon weight="light" size={28} />
              </button>
            </div>
          </div>
        {/if}
      {/if}
    </div>
  </div>
</div>

<!-- New message modal -->
<Modal
  open={showNewMessage}
  title="Nuovo messaggio"
  maxWidth="450px"
  draggable
  onclose={() => {
    showNewMessage = false;
  }}
>
  <input
    type="text"
    bind:value={newMsgUsername}
    placeholder="Username o nome"
    class="box-shadow-1-all"
    style="width: calc(100% - 10px)"
    autocomplete="off"
    aria-label="Username o nome"
  />

  {#if filteredContacts.length > 0}
    <div class="contacts-list">
      {#each filteredContacts as c}
        <!-- svelte-ignore a11y_invalid_attribute -->
        <a
          href="#"
          class="icon img-change-to-white accent-all box-shadow-1-all"
          style="display: inline-block; max-width: 100%"
          onclick={(e) => {
            e.preventDefault();
            newMsgUsername = c.username;
          }}
        >
          {#if c.profile_picture}
            <img
              src={c.profile_picture}
              alt="{c.name} {c.surname}"
              class="contact-pic"
            />
          {:else}
            <UserIcon weight="light" size={20} style="float:left;margin-right:6px" />
          {/if}
          <span
            class="print text-ellipsis"
            style="display: block; font-size: 1.2em">{c.name} {c.surname}</span
          >
        </a>
      {/each}
    </div>
  {:else if newMsgUsername.trim()}
    <p class="no-result small" style="color: gray; margin: 4px 0">
      Nessun contatto trovato.
    </p>
  {/if}

  {#if hasAttachment}
    <p style="font-size: 0.9em; margin: 4px 0">
      Allegato: <strong>{attachValue}</strong>
    </p>
  {/if}

  <textarea
    bind:value={newMsgBody}
    placeholder="Scrivi un messaggio..."
    style="width: calc(100% - 10px)"
    aria-label="Messaggio"
  ></textarea>

  {#if newMsgError}
    <div
      class="response alert alert-danger"
      style="clear: both; margin-top: 10px"
    >
      {newMsgError}
    </div>
  {/if}

  <button
    type="button"
    class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
    style="float: right"
    disabled={newMsgLoading}
    onclick={submitNewMessage}>Invia</button
  >
  <div style="clear: both"></div>
</Modal>

<style lang="scss">
  // DOM is now Svelte-driven — fully scoped styles, no :global() needed.

  .conv-col {
    max-width: 500px;
    padding: 0;
  }

  .message-list {
    max-height: calc(100vh - 140px);
    padding: 20px 20px 80px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    overflow: hidden auto;

    > * {
      max-width: 100%;
    }
  }

  .conv-avatar {
    float: left;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    object-fit: cover;
  }

  .list .date {
    font-size: 0.8em;
    display: block;
    margin-top: -2px;
    color: grey;
  }

  // Unread conversation indicator (orange gradient overrides accent)
  :global(.list.new) {
    background-image: linear-gradient(to right, #c96600, #ec9f52) !important;
  }

  .chat-col {
    padding-top: 20px;
  }

  .chat-header {
    margin: 0 0 4px;
  }

  .chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    float: left;
    margin-right: 10px;
    box-shadow: none;
  }

  .chat-messages {
    height: calc(100vh - 300px);
    overflow-y: auto;
    padding: 8px 0;
  }

  .msg-row {
    display: flex;
    margin: 6px 10px;

    &.mine {
      justify-content: flex-end;
    }
  }

  .bubble {
    display: inline-block;
    max-width: calc(66.66% - 20px);
    padding: 8px 12px;
    border-radius: 12px;
    text-align: left;
    transition: box-shadow 0.1s ease-in-out;
    background: #f0f0f0;
    color: black;

    @media (max-width: 768px) {
      max-width: calc(95% - 20px);
    }

    &.mine {
      background: var(--accent-color, #1e6ad3);
      color: white;
    }

    .msg-time {
      opacity: 0.6;
      font-size: 0.72em;
    }

    .attachment {
      display: block;
      margin-top: 4px;
    }

    .att-avatar {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      vertical-align: middle;
      margin-right: 4px;
    }
  }

  .empty-chat {
    color: gray;
    text-align: center;
    padding: 20px;
  }

  .reply-box {
    margin-top: 5px;
    display: flex;
    align-items: flex-end;
  }

  .reply-textarea {
    flex: 1;
    height: 80px;
    resize: vertical;
  }

  .send-btn {
    width: 60px;
    height: 80px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px;
    flex-shrink: 0;
  }

  .contacts-list {
    margin: 6px 0 8px;
  }

  .contact-pic {
    float: left;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    object-fit: cover;
  }
</style>

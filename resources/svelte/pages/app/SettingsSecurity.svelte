<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { untrack } from "svelte";
  import { t } from "../../lib/i18n";
  import { apiFetch } from "../../lib/api";

  interface Privacy {
    search_visible: number;
    show_email: number;
    show_username: number;
    send_messages: number;
    share_documents: number;
    ms_office: number;
  }

  interface Props {
    keysOk: boolean;
    hasTwofa: boolean;
    passwordLastChange: string | null;
    exportDataUrl: string;
    privacy: Privacy;
  }

  const {
    keysOk,
    hasTwofa,
    passwordLastChange,
    exportDataUrl,
    privacy: privacyRaw,
  }: Props = $props();

  const privacyInit = untrack(
    () =>
      privacyRaw ?? {
        search_visible: 1,
        show_email: 0,
        show_username: 0,
        send_messages: 2,
        share_documents: 2,
        ms_office: 1,
      },
  );

  let searchVisible = $state(String(privacyInit.search_visible));
  let showEmail = $state(String(privacyInit.show_email));
  let showUsername = $state(String(privacyInit.show_username));
  let sendMessages = $state(String(privacyInit.send_messages));
  let shareDocuments = $state(String(privacyInit.share_documents));
  let msOffice = $state(String(privacyInit.ms_office));

  let saving = $state(false);
  let respHtml = $state("");
  let respType = $state<"success" | "error" | "">("");

  async function handlePrivacy(e: Event): Promise<void> {
    e.preventDefault();
    saving = true;
    respHtml = "";
    respType = "";

    const body = [
      `search_visible=${encodeURIComponent(searchVisible)}`,
      `show_email=${encodeURIComponent(showEmail)}`,
      `show_username=${encodeURIComponent(showUsername)}`,
      `send_messages=${encodeURIComponent(sendMessages)}`,
      `share_documents=${encodeURIComponent(shareDocuments)}`,
      `ms_office=${encodeURIComponent(msOffice)}`,
    ].join("&");

    try {
      const res = await apiFetch("/api/settings?type=privacy", "POST", body);
      respHtml = res.text ?? "";
      respType = res.response === "success" ? "success" : "error";
    } catch {
      respType = "error";
      respHtml = t("error", "Error");
    } finally {
      saving = false;
    }
  }

  function formatDate(iso: string): string {
    try {
      const d = new Date(iso);
      const day = String(d.getDate()).padStart(2, "0");
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const year = d.getFullYear();
      return `${day}/${month}/${year}`;
    } catch {
      return iso;
    }
  }
</script>

<svelte:head
  ><title>{t("security")} - {t("app-settings")} - LightSchool</title
  ></svelte:head
>

<div class="container content-my settings-app">
  <div style="max-width: 1300px; margin: 0 auto">
    <div class="row">
      <div class="col-md-4 sidebar">
        <div>
          <h3>{t("settings-security-encryption-title")}</h3>
          {#if keysOk}
            <div class="alert alert-success">
              <b>{t("settings-security-keys-ok-title")}</b><br />
              {t("settings-security-keys-ok-desc")}
            </div>
          {:else}
            <div class="alert alert-danger">
              <b>{t("settings-security-keys-broken-title")}</b><br />
              {t("settings-security-keys-broken-desc")}
            </div>
          {/if}
        </div>

        <div>
          <h3>{t("settings-security-2fa-title")}</h3>
          {#if hasTwofa}
            <p>{t("settings-security-2fa-active")}</p>
            <a
              href="/my/app/settings/2fa"
              class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            >
              {t("settings-security-2fa-manage")}
            </a>
          {:else}
            <p>{t("settings-security-2fa-description")}</p>
            <a
              href="/my/app/settings/2fa"
              class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            >
              {t("settings-security-2fa-configure")}
            </a>
          {/if}
        </div>

        <div>
          <h3>{t("settings-passkeys")}</h3>
          <p>{t("settings-security-passkeys-desc")}</p>
          <a
            href="/my/app/settings/passkeys"
            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
          >
            {t("settings-security-passkeys-manage")}
          </a>
        </div>

        <div>
          <h3>{t("settings-security-password-title")}</h3>
          {#if passwordLastChange}
            <p>
              {t(
                "settings-security-password-changed",
                "Password changed on :date",
              ).replace(":date", formatDate(passwordLastChange))}
            </p>
          {:else}
            <p>{t("settings-security-password-never-changed")}</p>
          {/if}
          <a
            href="/my/app/settings/password"
            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
          >
            {t("settings-security-password-change")}
          </a>
        </div>

        <div>
          <h3>{t("settings-security-export-title")}</h3>
          <p>{t("settings-security-export-desc")}</p>
          <a
            href={exportDataUrl}
            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
          >
            {t("settings-security-export-button")}
          </a>
        </div>
      </div>

      <div class="col-md-8">
        <h3>{t("settings-security-privacy-title")}</h3>
        <p>{t("settings-security-privacy-desc")}</p>
        <br />

        <form
          method="post"
          action="/api/settings?type=privacy"
          class="form-privacy"
          onsubmit={handlePrivacy}
        >
          <label for="search_visible"
            >{t("settings-security-search-visible")}</label
          >
          <select
            id="search_visible"
            name="search_visible"
            bind:value={searchVisible}
            class="box-shadow-1-all"
          >
            <option value="1">{t("privacy-yes")}</option>
            <option value="0">{t("privacy-no")}</option>
          </select>
          <p class="small">{t("settings-security-search-visible-hint")}</p>
          <br />

          <label for="show_email">{t("settings-security-show-email")}</label>
          <select
            id="show_email"
            name="show_email"
            bind:value={showEmail}
            class="box-shadow-1-all"
          >
            <option value="1">{t("privacy-yes")}</option>
            <option value="0">{t("privacy-no")}</option>
          </select>
          <p class="small">{t("settings-security-show-email-hint")}</p>
          <br />

          <label for="show_username"
            >{t("settings-security-show-username")}</label
          >
          <select
            id="show_username"
            name="show_username"
            bind:value={showUsername}
            class="box-shadow-1-all"
          >
            <option value="1">{t("privacy-yes")}</option>
            <option value="0">{t("privacy-no")}</option>
          </select>
          <p class="small">{t("settings-security-show-username-hint")}</p>
          <br />

          <p>{t("settings-security-all-hidden")}</p>
          <hr />

          <h4>{t("settings-security-interactions-title")}</h4>
          <p>{t("settings-security-interactions-desc")}</p>

          <label for="send_messages"
            >{t("settings-security-send-messages")}</label
          >
          <select
            id="send_messages"
            name="send_messages"
            bind:value={sendMessages}
            class="box-shadow-1-all"
          >
            <option value="2">{t("privacy-everyone")}</option>
            <option value="1">{t("privacy-contacts-only")}</option>
            <option value="0">{t("nobody")}</option>
          </select>
          <br /><br />

          <label for="share_documents"
            >{t("settings-security-share-docs")}</label
          >
          <select
            id="share_documents"
            name="share_documents"
            bind:value={shareDocuments}
            class="box-shadow-1-all"
          >
            <option value="2">{t("privacy-everyone")}</option>
            <option value="1">{t("privacy-contacts-only")}</option>
            <option value="0">{t("nobody")}</option>
          </select>
          <hr />

          <h4>{t("settings-security-third-party-title")}</h4>
          <p>{t("settings-security-third-party-desc")}</p>

          <label for="ms_office">Microsoft Office Online</label>
          <select
            id="ms_office"
            name="ms_office"
            bind:value={msOffice}
            class="box-shadow-1-all"
          >
            <option value="2">{t("privacy-allow")}</option>
            <option value="1">{t("privacy-ask-each-time")}</option>
            <option value="0">{t("privacy-deny")}</option>
          </select>
          <p class="small">{t("settings-security-ms-office-hint")}</p>
          <hr />

          <input
            type="submit"
            value={t("apply")}
            disabled={saving}
            class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            style="float: right"
          />
          <div style="clear: both"></div>

          {#if respHtml}
            <div
              class="response alert alert-{respType === 'success'
                ? 'success'
                : 'danger'}"
              style="margin-top: 10px"
            >
              {respHtml}
            </div>
          {/if}
        </form>
      </div>
    </div>
  </div>
</div>

<style lang="scss">
    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
</style>
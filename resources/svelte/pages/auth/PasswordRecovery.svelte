<script module>
    import AuthLayout from '../../layouts/AuthLayout.svelte';
    export const layout = AuthLayout;
</script>

<script lang="ts">
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";

  interface HeaderText {
    header: string;
    text: string;
  }

  const { resetToken = false, selector = null, token = null }: {
    resetToken?: boolean;
    selector?: string | null;
    token?: string | null;
  } = $props();

  const isReset = $derived(resetToken === true);

  let loading = $state(false);
  let error = $state("");
  let requestSuccess = $state<HeaderText | null>(null);
  let resetSuccess = $state<string | null>(null);

  // Request form fields
  let usernameVal = $state("");

  // Reset form fields
  let newPassword = $state("");
  let newPassword2 = $state("");

  // DOM ref for re-focus
  let usernameInput = $state<HTMLInputElement | null>(null);

  async function handleRequest(e: SubmitEvent): Promise<void> {
    e.preventDefault();
    if (loading) return;

    loading = true;
    error = "";

    const body = `username=${encodeURIComponent(usernameVal)}`;

    const result = await apiFetch("/auth/password/request", "POST", body);

    if (result.response === "success") {
      const text = result["text"] as unknown as HeaderText;
      requestSuccess = { header: text.header, text: text.text };
      return;
    }

    error = (result["text"] as string) ?? t("error");
    loading = false;
    setTimeout(() => usernameInput?.focus(), 0);
  }

  async function handleReset(e: SubmitEvent): Promise<void> {
    e.preventDefault();
    if (loading) return;

    loading = true;
    error = "";

    const body = [
      `selector=${encodeURIComponent(selector ?? "")}`,
      `token=${encodeURIComponent(token ?? "")}`,
      `password=${encodeURIComponent(newPassword)}`,
      `password-2=${encodeURIComponent(newPassword2)}`,
    ].join("&");

    const result = await apiFetch("/auth/password/reset", "POST", body);

    if (result.response === "success") {
      resetSuccess = (result["text"] as string) ?? "";
      return;
    }

    error = (result["text"] as string) ?? t("error");
    loading = false;
  }
</script>

<svelte:head><title>{t('password')} - LightSchool</title></svelte:head>

<div
  class="welcome center-content background-image login password"
  style="background-image: url('/img/background.png')"
>
  <span>
    <div class="content">
      <h1 style="color: #004A7F">
        <img
          src="/img/logo.png"
          style="width: 64px; height: 64px; margin-right: 10px"
          alt="LightSchool logo"
        />
        LightSchool
      </h1>
      <br />
      <div class="form-content">
        {#if isReset}
          {#if resetSuccess !== null}
            <div class="alert alert-success">
              <p>{resetSuccess}</p>
              <br />
              <a href="/auth/login">{t("login")}</a>
            </div>
          {:else}
            <form class="form-password" onsubmit={handleReset}>
              <p>{t("enter-new-password")}</p>
              {#if error}
                <div class="response alert alert-danger">{error}</div>
              {/if}
              <input
                type="password"
                id="password"
                name="password"
                placeholder={t("new-password")}
                style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"
                bind:value={newPassword}
                disabled={loading}
              /><br />
              <input
                type="password"
                id="password-2"
                name="password-2"
                placeholder={t("confirm-new-password")}
                style="border-top-left-radius: 0; border-top-right-radius: 0"
                bind:value={newPassword2}
                disabled={loading}
              /><br />
              <input
                type="submit"
                value={t("set-new-password")}
                disabled={loading}
              />
            </form>
          {/if}
        {:else if requestSuccess !== null}
          <div class="alert alert-success">
            <h1>{requestSuccess.header}</h1>
            <p>{requestSuccess.text}</p>
          </div>
        {:else}
          <form class="form-request" onsubmit={handleRequest}>
            <p>{t("recover-pwd-text")}</p>
            {#if error}
              <div class="response alert alert-danger">{error}</div>
            {/if}
            <input
              type="text"
              id="username"
              name="username"
              placeholder={t("username")}
              style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"
              bind:value={usernameVal}
              bind:this={usernameInput}
              disabled={loading}
            /><br />
            <input type="submit" value={t("recover")} disabled={loading} />
          </form>
        {/if}
        <br />
        <small><a href="/">{t("website")}</a></small>
      </div>
    </div>
  </span>
</div>

<script module>
  import AuthLayout from "../../layouts/AuthLayout.svelte";
  export const layout = AuthLayout;
</script>

<script lang="ts">
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";

  interface PageData {
    appName: string;
  }

  interface SuccessText {
    header: string;
    text: string;
  }

  const { data }: { data: PageData } = $props();

  let loading = $state(false);
  let error = $state("");
  let success = $state<SuccessText | null>(null);

  // Form fields
  let name = $state("");
  let surname = $state("");
  let username = $state("");
  let email = $state("");
  let password = $state("");
  let password2 = $state("");

  // DOM ref for re-focus on error
  let nameInput = $state<HTMLInputElement | null>(null);

  async function handleRegister(e: SubmitEvent): Promise<void> {
    e.preventDefault();
    if (loading) return;

    loading = true;
    error = "";

    const body = [
      `name=${encodeURIComponent(name)}`,
      `surname=${encodeURIComponent(surname)}`,
      `username=${encodeURIComponent(username)}`,
      `email=${encodeURIComponent(email)}`,
      `password=${encodeURIComponent(password)}`,
      `password-2=${encodeURIComponent(password2)}`,
    ].join("&");

    const result = await apiFetch("/auth/register", "POST", body);

    if (result.response === "success") {
      const text = result.text as SuccessText;
      success = { header: text.header, text: text.text };
      return;
    }

    error = (result.text as string) ?? t("error");
    loading = false;
    setTimeout(() => nameInput?.focus(), 0);
  }
</script>

<svelte:head><title>{t("register")} - LightSchool</title></svelte:head>

<div
  class="welcome center-content background-image login register"
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
      <div class="form-content">
        {#if success}
          <div class="alert alert-success">
            <h1>{success.header}</h1>
            <p>{success.text}</p>
          </div>
        {:else}
          <form class="form-register" onsubmit={handleRegister}>
            <p>{t("create-account")}</p>
            <input
              type="text"
              id="name"
              name="name"
              placeholder={t("name")}
              style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"
              bind:value={name}
              bind:this={nameInput}
              disabled={loading}
            />
            <input
              type="text"
              id="surname"
              name="surname"
              placeholder={t("surname")}
              style="border-radius: 0"
              bind:value={surname}
              disabled={loading}
            />
            <input
              type="text"
              id="username"
              name="username"
              placeholder={t("username")}
              style="border-radius: 0"
              bind:value={username}
              disabled={loading}
            />
            <input
              type="email"
              id="email"
              name="email"
              placeholder={t("e-mail")}
              style="border-radius: 0"
              bind:value={email}
              disabled={loading}
            />
            <input
              type="password"
              id="password"
              name="password"
              placeholder={t("password")}
              style="border-radius: 0"
              bind:value={password}
              disabled={loading}
            />
            <input
              type="password"
              id="password-2"
              name="password-2"
              placeholder={t("confirm-password")}
              style="border-radius: 0"
              bind:value={password2}
              disabled={loading}
            />
            <input type="submit" value={t("register-b")} disabled={loading} />
            {#if error}
              <div
                class="response alert alert-danger"
                style="margin-top: 10px; margin-bottom: 0"
              >
                {error}
              </div>
            {/if}
            <p>
              {t("register-text2")}
              <a href="/tos">{t("tos")}</a>
              {t("and")}
              <a href="/privacy">{t("privacy-policy")}</a>.
            </p>
          </form>
        {/if}
        <small><a href="/">{t("website")}</a></small>
      </div>
    </div>
  </span>
</div>

<style>
  input:not([type="submit"]) {
    margin-left: 0;
    margin-right: 0;
  }
</style>

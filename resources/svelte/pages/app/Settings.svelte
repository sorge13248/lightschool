<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { untrack } from "svelte";
  import {
    UserIcon,
    DotsSixVerticalIcon,
    PaintBrushIcon,
    TranslateIcon,
    LockIcon,
    TrashIcon,
  } from "phosphor-svelte";
  import type { CurrentUser } from "../../lib/types";
  import { t } from "../../lib/i18n";

  interface Props {
    currentUser: CurrentUser;
    diskSpaceUsed: number;
    diskSpaceTotal: number;
  }

  const { currentUser, diskSpaceUsed, diskSpaceTotal }: Props = $props();

  const usedBytes = untrack(() => diskSpaceUsed ?? 0);
  const totalBytes = untrack(() => diskSpaceTotal ?? 100 * 1024 * 1024);
  const accentBase = untrack(() => currentUser.accent ?? "#1e6ad3");
  const usedPct =
    totalBytes > 0
      ? Math.min(100, Math.round((usedBytes * 100) / totalBytes))
      : 0;

  function humanSize(bytes: number): string {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + " MB";
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + " KB";
    return bytes + " B";
  }

  const humanUsed = humanSize(usedBytes);
  const humanTotal = humanSize(totalBytes);

  const isFull = usedBytes >= totalBytes;
  const isAlmostFull = !isFull && usedBytes + totalBytes / 10 >= totalBytes;

  const storageHtml = $derived(
    t("settings-storage-using", "You are using :used of :total.")
      .replace(":used", `<b>${humanUsed}</b>`)
      .replace(":total", humanTotal),
  );
</script>

<svelte:head><title>{t("app-settings")} - LightSchool</title></svelte:head>

<div
  class="container content-my settings"
  style="padding-left: 0; padding-right: 0; padding-top: 0"
>
  {#if isFull}
    <div class="alert alert-danger" style="margin: 0; border-radius: 0">
      {t("settings-storage-full")}
    </div>
  {:else if isAlmostFull}
    <div class="alert alert-warning" style="margin: 0; border-radius: 0">
      {t("settings-storage-almost-full")}
    </div>
  {/if}

  <div class="header">
    <div class="row" style="align-items: center">
      <div class="col-md-2" style="text-align: center">
        {#if currentUser.profile_picture}
          <img
            src={currentUser.profile_picture}
            style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover"
            alt=""
          />
        {:else}
          <div
            style="width: 120px; height: 120px; border-radius: 50%; background: #ddd; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5em; color: #999"
          >
            {(currentUser.name || "?").charAt(0).toUpperCase()}
          </div>
        {/if}
      </div>
      <div class="col-md-10" style="text-align: left">
        <h1 style="margin-top: 16px">
          {currentUser.name}
          {currentUser.surname}
        </h1>
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        <p>{@html storageHtml}</p>
        <div
          class="bar"
          style="width: 100%; max-width: 300px; background-color: #e0e0e0; margin-left: 5px"
        >
          <span style="width: {usedPct}%; background-color: {accentBase}"
            >&nbsp;</span
          >
        </div>
      </div>
    </div>
  </div>

  <div class="sections">
    <a
      href="/my/app/settings/account"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <UserIcon weight="light" />
      <span>{t("account")}</span>
      <small>{t("settings-nav-account-desc")}</small>
    </a>
    <a
      href="/my/app/settings/app"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <DotsSixVerticalIcon weight="light" />
      <span>App</span>
      <small>{t("settings-nav-app-desc")}</small>
    </a>
    <a
      href="/my/app/settings/customize"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <PaintBrushIcon weight="light" />
      <span>{t("settings-nav-customize")}</span>
      <small>{t("settings-nav-customize-desc")}</small>
    </a>
    <a
      href="/my/app/settings/language"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <TranslateIcon weight="light" />
      <span>{t("settings-language-title")}</span>
      <small>{t("settings-nav-language-desc")}</small>
    </a>
    <a
      href="/my/app/settings/security"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <LockIcon weight="light" />
      <span>{t("security")}</span>
      <small>{t("settings-nav-security-desc")}</small>
    </a>
    <a
      href="/my/app/settings/delete-account"
      class="icon img-change-to-white accent-all box-shadow-1-all"
    >
      <TrashIcon weight="light" />
      <span>{t("settings-nav-delete")}</span>
      <small>{t("settings-nav-delete-desc")}</small>
    </a>
  </div>
</div>

<style lang="scss">
  .settings {
    .header {
      padding: 20px;
      margin-bottom: 32px;
      display: flex;
      align-items: center;
      justify-content: center;

      div img {
        float: right;

        @media (max-width: 768px) {
          float: none;
        }
      }
    }

    .sections {
      margin: 20px auto 0;
      width: 100%;
      max-width: 1100px;
      text-align: center;
      padding: 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 16px;

      .icon {
        width: 300px;
        max-width: 100%;
        display: block;
        padding: 16px;
        border-radius: 8px;
        text-decoration: none;
        color: inherit;

        :global(svg) {
          font-size: 3em;
        }

        span {
          display: block;
          font-weight: bold;
          margin-bottom: 4px;
        }

        small {
          display: block;
          opacity: 0.6;
        }
      }
    }
  }

  .bar {
    display: flex;
    text-align: left !important;
    background-color: #f6f6f6;
    border: 1px solid lightgray;
    border-radius: 10px;

    > * {
      width: auto;
      min-width: 1px;
      max-width: 100% !important;
      margin: 0;
      padding: 2px;

      &:first-child {
        border-radius: 10px;
      }
    }
  }
</style>

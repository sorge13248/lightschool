<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { onDestroy, untrack } from "svelte";
  import type { CurrentUser } from "../../lib/types";
  import { t } from "../../lib/i18n";
  import { apiFetch } from "../../lib/api";
  import { themePreview } from "../../stores/themePreview.svelte";

  interface TaskbarAppItem {
    id: number;
    unique_name: string;
  }
  interface Wallpaper {
    id: number;
    opacity: string;
    color: string;
    blur: number;
  }

  interface Props {
    currentUser: CurrentUser;
    profilePicId: number | null;
    wallpaper: Wallpaper | null;
    taskbarItems: TaskbarAppItem[];
  }

  const {
    currentUser,
    profilePicId: profilePicIdRaw,
    wallpaper: wallpaperRaw,
    taskbarItems: taskbarItemsRaw,
  }: Props = $props();

  function rgbStringToHex(rgb: string): string {
    const parts = rgb.split(",").map((s) => parseInt(s.trim(), 10));
    if (parts.length !== 3 || parts.some(isNaN)) return "#000000";
    return "#" + parts.map((n) => n.toString(16).padStart(2, "0")).join("");
  }

  const accentInit = untrack(() => currentUser.accent ?? "#1e6ad3");
  const profilePicUrl = untrack(() => currentUser.profile_picture ?? null);
  const profilePicId = untrack(() => profilePicIdRaw ?? null);
  const wallpaperInit = untrack(() => wallpaperRaw ?? null);
  const taskbarSizeInit = untrack(() => currentUser.taskbar_size ?? 0);
  const taskbarItemsInit = untrack(() => taskbarItemsRaw ?? []);

  let accent = $state(accentInit);
  let ppId = $state<number | null>(profilePicId);
  let wallpaper = $state<Wallpaper | null>(wallpaperInit);
  let wallpaperUrl = $state<string | null>(
    wallpaperInit ? `/api/file/${wallpaperInit.id}` : null,
  );
  let bkgOpacity = $state(
    wallpaperInit ? Math.round(parseFloat(wallpaperInit.opacity) * 100) : 50,
  );
  let bkgColor = $state(
    wallpaperInit?.color ? rgbStringToHex(wallpaperInit.color) : "#000000",
  );
  let bkgBlur = $state(wallpaperInit?.blur ?? 0);
  let taskbarSize = $state(String(taskbarSizeInit));
  let taskbarItems = $state<TaskbarAppItem[]>([...taskbarItemsInit]);

  let saving = $state(false);
  let respText = $state("");
  let respType = $state<"success" | "error" | "">("");

  // ── Live preview via themePreview store (read by AppLayout) ─────────────────
  $effect(() => {
    themePreview.accent = accent;
  });

  $effect(() => {
    if (!wallpaper) {
      themePreview.wallpaperFileId  = null;
      themePreview.wallpaperBlur    = null;
      themePreview.wallpaperColor   = null;
      themePreview.wallpaperOpacity = null;
      return;
    }
    const hex = bkgColor.replace('#', '');
    const r   = parseInt(hex.substring(0, 2), 16) || 0;
    const g   = parseInt(hex.substring(2, 4), 16) || 0;
    const b   = parseInt(hex.substring(4, 6), 16) || 0;
    themePreview.wallpaperFileId  = String(wallpaper.id);
    themePreview.wallpaperBlur    = bkgBlur;
    themePreview.wallpaperColor   = `background-color: rgba(${r}, ${g}, ${b}, ${bkgOpacity / 100})`;
    themePreview.wallpaperOpacity = bkgOpacity / 100;
  });

  // Reset preview when navigating away
  onDestroy(() => {
    themePreview.accent          = null;
    themePreview.wallpaperFileId = null;
    themePreview.wallpaperBlur   = null;
    themePreview.wallpaperColor  = null;
    themePreview.wallpaperOpacity = null;
  });

  function removeProfilePicture(): void {
    ppId = null;
  }
  function removeWallpaper(): void {
    wallpaper = null;
    wallpaperUrl = null;
  }
  async function handleSubmit(e: Event): Promise<void> {
    e.preventDefault();
    saving = true;
    respText = "";
    respType = "";

    const parts = [
      `accent=${encodeURIComponent(accent)}`,
      `taskbar_size=${encodeURIComponent(taskbarSize)}`,
      `taskbar=${encodeURIComponent(taskbarItems.map((a) => a.id).join(","))}`,
      `pp-id=${encodeURIComponent(ppId ?? "")}`,
      `bkg-id=${encodeURIComponent(wallpaper?.id ?? "")}`,
    ];
    if (wallpaper) {
      parts.push(`bkg-opacity=${encodeURIComponent(bkgOpacity)}`);
      parts.push(`bkg-color=${encodeURIComponent(bkgColor)}`);
      parts.push(`bkg-blur=${encodeURIComponent(bkgBlur)}`);
    }

    try {
      const res = await apiFetch(
        "/api/settings?type=customize",
        "POST",
        parts.join("&"),
      );
      respText = res.text ?? "";
      respType = res.response === "success" ? "success" : "error";
    } catch {
      respType = "error";
      respText = t("error", "Error");
    } finally {
      saving = false;
    }
  }
</script>

<svelte:head
  ><title
    >{t("settings-nav-customize")} - {t("app-settings")} - LightSchool</title
  ></svelte:head
>

<div class="container content-my settings-app">
  <form
    method="post"
    action="/api/settings?type=customize"
    class="form-customize"
    style="padding: 25px"
    onsubmit={handleSubmit}
  >
    <div style="max-width: 1300px; margin: 0 auto">
      <div class="row">
        <div class="col-md-6">
          <h2>{t("settings-nav-customize")}</h2>

          <div>
            <label for="accent">{t("settings-customize-accent-label")}</label
            ><br />
            <input
              type="text"
              id="accent"
              name="accent"
              placeholder={t("settings-customize-accent-label")}
              bind:value={accent}
              data-fra-color-picker="1"
              class="box-shadow-1-all"
              maxlength={7}
              style:color={accent}
              style:background-color={accent}
            />
            <small>{t("settings-customize-accent-hint")}</small>
          </div>

          <div class="pp">
            <label for="pp-id">{t("settings-customize-profile-picture")}</label
            ><br />
            {#if ppId && profilePicUrl}
              <img
                src={profilePicUrl}
                style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-right: 10px; margin-top: 16px; float: left"
                alt="Profile"
              />
            {/if}
            <span>{t("settings-customize-profile-picture-hint")}</span><br />
            <input type="hidden" id="pp-id" name="pp-id" value={ppId ?? ""} />
            {#if ppId && profilePicUrl}
              <!-- svelte-ignore a11y_invalid_attribute -->
              <a
                href="#"
                class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                style="float: right"
                onclick={(e) => {
                  e.preventDefault();
                  removeProfilePicture();
                }}
              >
                {t("settings-customize-remove-profile-picture")}
              </a>
            {/if}
          </div>
          <div style="clear: both"></div>

          <div class="bkg">
            <label for="bkg-id">{t("settings-customize-background")}</label><br
            />
            {#if wallpaper && wallpaperUrl}
              <img
                src={wallpaperUrl}
                style="width: 100%; max-width: 128px; float: left; margin-right: 10px"
                alt="Background"
              />
            {/if}
            <span>{t("settings-customize-background-hint")}</span><br />
            {#if wallpaper}
              <!-- svelte-ignore a11y_invalid_attribute -->
              <a
                href="#"
                class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                style="float: right"
                onclick={(e) => {
                  e.preventDefault();
                  removeWallpaper();
                }}
              >
                {t("settings-customize-remove-background")}
              </a>
            {/if}
            <input
              type="hidden"
              id="bkg-id"
              name="bkg-id"
              value={wallpaper?.id ?? ""}
            />
          </div>
          <div style="clear: both"></div>

          {#if wallpaper}
            <div class="wallpaper-options">
              <div class="bkg-opacity">
                <label for="bkg-opacity"
                  >{t("settings-customize-background-opacity")}</label
                ><br />
                <input
                  type="range"
                  id="bkg-opacity"
                  name="bkg-opacity"
                  min={0}
                  max={100}
                  bind:value={bkgOpacity}
                  class="box-shadow-1-all"
                />
              </div>
              <div class="bkg-blur">
                <label for="bkg-blur"
                  >{t("settings-customize-background-blur")}</label
                ><br />
                <input
                  type="range"
                  id="bkg-blur"
                  name="bkg-blur"
                  min={0}
                  max={20}
                  bind:value={bkgBlur}
                  class="box-shadow-1-all"
                />
              </div>
              <div class="bkg-color">
                <label for="bkg-color"
                  >{t("settings-customize-background-overlay")}</label
                ><br />
                <input
                  type="text"
                  id="bkg-color"
                  name="bkg-color"
                  placeholder={t("settings-customize-background-overlay")}
                  bind:value={bkgColor}
                  data-fra-color-picker="1"
                  class="box-shadow-1-all"
                  maxlength={7}
                  style:color={bkgColor}
                  style:background-color={bkgColor}
                />
              </div>
            </div>
          {/if}
        </div>

        <div class="col-md-6">
          <h2>Taskbar</h2>

          <div>
            <label for="taskbar_size"
              >{t("settings-customize-taskbar-size")}</label
            ><br />
            <select
              id="taskbar_size"
              name="taskbar_size"
              bind:value={taskbarSize}
              class="box-shadow-1-all"
            >
              <option value="2"
                >{t("settings-customize-taskbar-size-large")}</option
              >
              <option value="0"
                >{t("settings-customize-taskbar-size-normal")}</option
              >
              <option value="1"
                >{t("settings-customize-taskbar-size-small")}</option
              >
            </select>
            <small>{t("settings-customize-taskbar-size-hint")}</small>
          </div>

          <div>
            <p><strong>{t("settings-customize-taskbar-reorder")}</strong></p>
            <p>{t("settings-customize-taskbar-reorder-hint")}</p>
          </div>
        </div>
      </div>

      <input
        type="submit"
        value={t("save")}
        disabled={saving}
        class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
      />
      {#if respText}
        <div
          class="response alert alert-{respType === 'success'
            ? 'success'
            : 'danger'}"
          style="margin-top: 10px"
        >
          {respText}
        </div>
      {/if}
    </div>
  </form>
</div>

<style>
  form :global(div.row div) {
    padding: 10px;
  }
  form :global(input),
  form :global(select) {
    width: 100%;
  }
  form :global(ul) {
    list-style-type: none;
    padding-inline-start: 0;
  }

  .wallpaper-options {
    display: grid;
    grid-template-columns: repeat(3, minmax(200px, 1fr));
    gap: 0.5rem;
  }
</style>

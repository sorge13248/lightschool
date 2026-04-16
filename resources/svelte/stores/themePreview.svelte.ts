/**
 * themePreview — live accent/wallpaper preview state written by SettingsCustomize
 * and read by AppLayout and PropertyPanel to override currentUser values during preview.
 *
 * Reset to null values when the settings customize page unmounts.
 */
export const themePreview = $state<{
    accent: string | null;
    wallpaperFileId: string | null;
    wallpaperBlur: number | null;
    wallpaperColor: string | null;   // CSS rgba string e.g. "rgba(0,0,0,0.5)"
    wallpaperOpacity: number | null; // 0–1
}>({
    accent: null,
    wallpaperFileId: null,
    wallpaperBlur: null,
    wallpaperColor: null,
    wallpaperOpacity: null,
});

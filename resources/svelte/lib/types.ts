// ── Generic API response ──────────────────────────────────────────────────────

/**
 * Standard API response shape returned by all Laravel JSON endpoints.
 * The `response` field is always 'success', 'error', or '2fa'.
 * Additional endpoint-specific fields are captured by the index signature.
 */
export interface ApiResponse<T extends Record<string, unknown> = Record<string, unknown>> {
    response: 'success' | 'error' | '2fa' | string;
    text: string;
    [key: string]: unknown;
}

// ── User models ───────────────────────────────────────────────────────────────

export interface User {
    id: number;
    name: string;
    surname: string;
    username: string;
    email: string;
}

export interface UserExpanded {
    user_id: number;
    profile_picture: number | null;
    wallpaper: string | null; // JSON-encoded object
    accent: string;           // hex colour e.g. "#4a90d9"
    theme: string;            // e.g. "dark"
    taskbar: number[];        // JSON array of apps.id integers
    blocked: number[];        // JSON array of user IDs
    twofa: string | null;     // RSA-encrypted TOTP secret or null
    privacy_online: boolean;
    privacy_search: boolean;
}

export interface CurrentUserWallpaper {
    id: number;
    opacity: string; // e.g. "0.5"
    color: string;   // e.g. "255, 0, 128" (rgb components)
    blur: number;    // px, e.g. 8
}

export interface CurrentUser {
    name: string;
    surname: string;
    username: string;
    profile_picture: string | null;
    taskbar_size: number; // 0 = normal, 1 = small, 2 = big
    taskbar: Array<{ id: number; unique_name: string }>;
    accent: string; // hex colour
    wallpaper: CurrentUserWallpaper | null;
}

// ── File Manager models ───────────────────────────────────────────────────────

export interface FileItem {
    id: string;
    name: string;
    type: 'folder' | 'notebook' | 'diary' | 'file';
    link: string;
    fav: 0 | 1;
    iconKey?: string;
    icon?: string;
    style?: string;
    secondRow?: string;
    file_type?: string;
    file_exists?: boolean;
}

// ── App / Taskbar models ──────────────────────────────────────────────────────

export interface AppItem {
    id: number;
    unique_name: string;
}

export interface AppModel {
    id: number;
    unique_name: string;
    is_system: boolean;
    settings: boolean;
}

// ── Contact models ────────────────────────────────────────────────────────────

export interface ContactItem {
    id: number;
    name: string;
    surname: string;
    username: string;
    fav: 0 | 1;
    blocked: 0 | 1;
    profile_picture?: number | null;
}

// ── Diary models ──────────────────────────────────────────────────────────────

export interface DiaryEvent {
    id: number;
    name: string;
    diary_type: string;
    diary_date: string;
    diary_reminder: string | null;
    diary_priority: number;
    diary_color: string;
    fav: 0 | 1;
    content?: string;
}

// ── Timetable models ──────────────────────────────────────────────────────────

export interface TimetableItem {
    id: number;
    day: number;
    slot: number;
    subject: string;
    book: string;
    fore?: string;
}

// ── Message / Chat models ─────────────────────────────────────────────────────

export interface MessageChat {
    id: number;
    user: {
        name: string;
        surname: string;
        profile_picture: number | null;
    };
    date: string;
    new: boolean;
}

export interface MessageItem {
    sender: number;
    body: string;
    date: string;
    attachment?: {
        id: number;
        name: string;
        type: string;
    };
}

// ── Share models ──────────────────────────────────────────────────────────────

export interface ShareItem {
    file_id: number;
    name: string;
    type: string;
}

/** A user a file is currently shared with (returned by /api/share?type=file-shared) */
export interface SharedUser {
    id: string;
    name?: string;
    surname?: string;
    profile_picture?: number | null;
}

/** A contact suggestion shown in the share-with autocomplete */
export interface SuggestContact {
    name: string;
    username: string;
    profile_picture?: number | null;
}

// ── Social models ─────────────────────────────────────────────────────────────

export interface SocialUser {
    name: string;
    surname: string;
    username: string;
    email: string;
    profile_picture: number | null;
    blocked: boolean;
    self: boolean;
}

// ── Page data ─────────────────────────────────────────────────────────────────

export interface PageData {
    [key: string]: unknown;
}

export interface AppPageData extends PageData {
    currentUser: CurrentUser;
    allApps: AppItem[];
    page: string;
}

// ── Global window augmentation ────────────────────────────────────────────────

declare global {
    interface Window {
        __PAGE__: string;
        __PAGE_DATA__: Record<string, unknown>;
        LANGUAGE: Record<string, string | string[]>;
    }
}


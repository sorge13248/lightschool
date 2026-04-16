# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development server (Docker)
docker compose up

# Run tests (clears config cache first)
npm test                          # alias for: artisan config:clear && artisan test

# Run a single test file
php artisan test tests/Feature/AuthTest.php

# Run a single test by name
php artisan test --filter "test name"

# CSS/JS build
npm run dev     # Vite dev server
npm run build   # Production build

# Database
php artisan migrate --force --seed

# Laravel utilities
php artisan view:clear
php artisan config:clear
```

## Architecture

### Request Flow

There are two classes of requests:

**Page requests** go through Laravel routes → `PageController` → `Inertia::render('path/Page', [props])`. Inertia serialises the props as JSON and the single root Blade view (`resources/views/app.blade.php`) boots the Svelte app. The browser receives a fully hydrated Svelte component.

**API requests** go through Laravel routes → a feature controller's `handle(Request $request)` method → a `match($request->query('type'))` dispatcher → specific sub-operation. The response format is always JSON:

```php
response()->json(['response' => 'success'|'error', 'text' => '...', /* extra fields */])
```

There are two parallel sets of routes: legacy paths (e.g. `/controller/login.php`, `/my/app/file-manager/controller.php`) and clean paths (e.g. `/auth/login`, `/api/file-manager`). Both point to the same controllers — the legacy paths exist from the PHP-to-Laravel migration.

### Controllers

Controllers live in `app/Http/Controllers/`. `PageController` handles all page renders. Feature controllers (FileManagerController, SettingsController, MessageController, etc.) each have a single `handle(Request $request)` method with an internal `match($type)` dispatch. Controllers use Eloquent models for DB operations — do not write raw SQL.

Auth controllers (`app/Http/Controllers/Auth/`) handle registration, login (including 2FA and passkeys), password reset, email verification, and logout.

### Views and Frontend

All pages are rendered via **Inertia.js**. The only Blade view that matters for page rendering is `resources/views/app.blade.php` — the single Inertia root. Every `Inertia::render('path/Page', $props)` call resolves to `resources/svelte/pages/path/Page.svelte`.

The single Vite entry point is `resources/svelte/entries/app.ts`, which boots `createInertiaApp` and resolves pages via `import.meta.glob('../pages/**/*.svelte', { eager: true })`.

**Page components** live under `resources/svelte/pages/`:
- `app/` — authenticated app pages (FileManager, Diary, Writer, Message, …)
- `auth/` — auth flow pages (Login, Register, Verify, …)
- `public/` — public landing pages (Home, Features, Tos, …)

Each page exports its layout at the top of a `<script module>` block:
```svelte
<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>
```

**Three Svelte layouts** exist in `resources/svelte/layouts/`:
- `AppLayout.svelte` — authenticated pages (taskbar, launcher, notifications, wallpaper)
- `AuthLayout.svelte` — login/register pages
- `PublicLayout.svelte` — public landing pages

**Shared props** (available in every layout and page via `$props()`) are injected by `HandleInertiaRequests` middleware:
- Always: `appName`, `locale`, `isAuthenticated`, `username`, `currentUrl`
- When authenticated: `currentUser` (name, surname, profile_picture, taskbar, accent, wallpaper, …), `allApps`

**Reusable components** live in `resources/svelte/components/`:
- `ui/` — Modal, ActionButton, PropertyPanel, LoadingPlaceholder, ContextMenuFile
- `modals/` — ShareModal, DeleteModal, ProjectModal
- `Notifications.svelte` — toast stack, wired to the `notifications` store

**Svelte stores** live in `resources/svelte/stores/`:
- `notifications.svelte.ts` — add/remove toast notifications
- `themePreview.svelte.ts` — live preview of wallpaper/accent changes
- `contextmenu.svelte.ts` — context menu state

**Client-side utilities** live in `resources/svelte/lib/`:
- `api.ts` — `apiFetch(url, method?, body?)` and `apiFetchJson(url, method, data)` — handle CSRF, rate-limit errors, JSON parsing
- `i18n.ts` — `t(key, fallback?)` — reads from the global `LANGUAGE` object injected by `/lang/{locale}.js`
- `types.ts` — shared TypeScript interfaces

CSS is split into compiled files served as static assets:
- `lightschool-base.css` — shared base (reset, forms, buttons, utilities)
- `lightschool-my.css` — app-specific styles (menu bar, icons, writer, chat)
- `lightschool.css` — public/auth pages (welcome, login)

### Encryption Architecture

This is the most non-obvious part of the codebase. Three layers of encryption coexist:

1. **Passwords** — standard Laravel bcrypt
2. **File content / messages** — hybrid encryption via `CryptoService`: content is encrypted (AES-256-GCM via Defuse for `enc_version=1`, libsodium for `enc_version=2`), the encryption key is wrapped with the user's public key. Stored as `file.html` (ciphertext) + `file.cypher` (encrypted key).
3. **2FA secret** — encrypted with the user's public key, stored in `users_expanded.twofa`

Each user has an Ed25519 key pair generated at registration by `KeyringService`, stored on the filesystem at `{SECURE_DIR}/keyring/{userId}/`. The `SECURE_DIR` is outside the web root (`/var/lightschool/secure/` in Docker).

`CryptoService` dispatches to `LegacyCryptoService` (Defuse, `enc_version=1`) or `SodiumCryptoService` (libsodium, `enc_version=2`) based on the `enc_version` column on the record.

### 2FA Login Flow

Login is two-phase when 2FA is active:
1. Username + password validated → `LoginController` stores user ID and encrypted password in session → returns `{response: '2fa'}`
2. Client submits TOTP token → `LoginController::verifyTwoFactor()` decrypts session password, decrypts user's TOTP secret from DB, verifies token → completes login

### Passkeys / WebAuthn

Passkey support is provided by `spatie/laravel-passkeys` (server) and `@simplewebauthn/browser` (client). Registration and deletion are managed in `PasskeyController` and `SettingsPasskeys.svelte`. Passkey login goes through `PasskeyLoginController`.

### Queue and Background Jobs

Laravel queues use the **database driver**. The `worker` Docker service runs `php artisan queue:work`. Current jobs:
- `ExportUserDataJob` — packages all user data into a ZIP file for download

### Database

All sessions, cache, and queues use the **database driver** (not Redis by default). Key tables:
- `users` + `users_expanded` — one-to-one, with profile, wallpaper (JSON), accent (hex), theme, twofa (encrypted), taskbar (**JSON array** of `apps.id` integers), blocked (**JSON array** of user IDs), privacy settings, language
- `file` — multi-type: `folder`, `notebook`, `diary`, `file`. Soft-deleted via `deleted_at` (TIMESTAMP, NULL = active), matching Laravel's standard soft-delete column name. `trash = 1` means in trash but not yet purged.
- `share` — file sharing records, including bypass tokens for unauthenticated access
- `timetable` — soft-deleted via `deleted_at` (TIMESTAMP)
- `message_chat` — read state stored in `read_at` (TIMESTAMP, NULL = unread)
- `apps` — app registry with `id` (PK), `unique_name` (unique slug), `is_system` boolean, `settings` boolean (has settings page). App display names live in the lang JSON files under the key `app-{unique_name}` (e.g. `"app-contact"`, `"app-file-manager"`). Server-side: `__('app-' . $app->unique_name)`. Client-side: `t('app-' + uniqueName)`.
- `contact` — soft-deleted via `deleted` (boolean 0/1, **not** renamed — this is intentional)
- `passkeys` — WebAuthn credentials (via spatie/laravel-passkeys)
- `data_exports` — tracks user data export jobs (pending / processing / ready / downloaded / failed)
- `user_deletion_requests` — tracks account deletion requests with grace-period timestamp

Column types migrated from VARCHAR(19) to proper TIMESTAMP: `access.date`, `file.last_view`, `file.last_edit`.

### Taskbar and App System

Apps are defined in the `apps` table (model: `App\Models\App`). All users have access to all apps — there is no store or purchase system. The active apps shown in the taskbar are stored as a **JSON array of `apps.id` integers** in `users_expanded.taskbar` (e.g. `[2,7]`). The order is user-configurable via drag-and-drop in Settings. Always read with `json_decode`, write with `json_encode`.

### File Serving

Files uploaded by users are stored in `UPLOAD_DIR` (outside web root) and served through `FileManagerController` via the `/api/file/{id}` endpoint. Profile pictures are stored as a file ID integer in `users_expanded.profile_picture`, served via that same endpoint.

### Language System

Languages are JSON files in `lang/{locale}.json` (e.g. `lang/it.json`, `lang/en.json`). The selected language is stored in a `language` cookie (excluded from Laravel's cookie encryption — see `bootstrap/app.php`). The `SetLocale` middleware reads the cookie and sets `app()->setLocale()`. Each user's preferred language is also persisted in `users_expanded.language` so server-side operations (emails, jobs) can use the correct locale.

Translations are served as a cacheable JS resource: `GET /lang/{locale}.js` returns `var LANGUAGE = {...};` with `Cache-Control: public, max-age=86400`. The Inertia root view loads this script before `fra.js`:

```html
<script src="{{ url('/lang/' . app()->getLocale() . '.js') }}"></script>
<script src="{{ asset('js/fra.js') }}"></script>
```

Svelte components access translations via `t('key')` from `resources/svelte/lib/i18n.ts`, which reads from the global `LANGUAGE` object. Server-side Blade uses `__('key')`.

### Theme / Accent System

- **Theme**: A string key (e.g. `dark`) stored in `users_expanded.theme`. At render time, the layout checks if `public/css/theme/{key}.css` exists and includes it.
- **Accent color**: A hex color stored in `users_expanded.accent`. The `layouts/partials/accent.blade.php` partial (included by `app.blade.php`) generates an inline `<style id="accent-styles">` block with CSS custom properties (`--ac-hex`, `--ac-base`, `--ac-lighter`, `--ac-darker`) and a full set of `.accent-*` utility class rules. Svelte components use these classes (e.g. `accent-bkg-gradient`, `box-shadow-1-all`) for runtime-themed styling.

### Legacy Fra JavaScript Libraries

The `resources/ts/` directory contains the legacy "Fra" UI framework, bundled into a single `public/js/fra.js` via esbuild (run automatically by the Vite build). These libraries back the parts of the UI that pre-date the Svelte migration and are still loaded globally:

- `FraWindows` — modal/floating window system
- `FraForm` — form lock/unlock during submission
- `FraNotifications` — toast notification queue
- `FraContextMenu` — right-click context menus
- `FraColorPicker` — color swatch widget
- `FraCookieBar` — GDPR cookie notice bar
- `FraJson` / `FraAjax` — AJAX wrappers

The `apiFetch(url, method, body)` global function defined in `app.blade.php` is a thin wrapper for API calls that injects the CSRF token. Svelte components use the typed `apiFetch` / `apiFetchJson` from `resources/svelte/lib/api.ts` instead.

### Legacy Code

The directory `/old/` at the repo root is the **old PHP codebase** from before the Laravel migration. It is not active but serves as a reference for the original behavior. When recreating a feature, check these directories for the original implementation.

### Migration instructions
* You **MUST** respect old pages UI and UX (no changes in appearance or interaction), but you **MUST** modernize the underlying layers, like controllers, validations, security, performance, standardization, and so on
* For every breaking change, like database, security algorithms (users' private/public key, OTPs, files, and so on), **YOU MUST** prepare/update a MIGRATION.md file that you can read later on to generate a PHP Laravel Artisan command to run those migrations.
* You **MUST** use the cleanest and modern approach possible
* You **MUST** use Eloquent models when performing DB operations
* You **MUST** evaluate .dockerignore when editing the content of .gitignore

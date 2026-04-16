# Migration Notes

This file documents breaking or data-migration steps required when deploying new versions of LightSchool. Each entry corresponds to a database migration or data-backfill that must be run manually or via Artisan.

---

## 2026-04-07 — WebAuthn passkeys table

**Migration file:** `2026_04_07_212630_create_passkeys_table.php`

**What it does:** Creates the `passkeys` table required by `spatie/laravel-passkeys` for storing WebAuthn credentials. Fields include credential ID, public key, counter, transports, user handle, and the authenticator's human-readable name.

**Run:**
```bash
php artisan migrate
```

**Note:** Existing users are unaffected. Passkey registration is available in Settings → Passkeys after migration.

---

## 2026-03-30 — Add `language` column to `users_expanded`

**Migration file:** `2026_03_30_000002_add_language_to_users_expanded.php`

**What it does:** Adds a nullable `language` VARCHAR(10) column to `users_expanded`. This column stores each user's preferred language (e.g. `'en'`, `'it'`) so that server-side operations (emails, console commands) can use the correct locale without relying on HTTP cookies.

**Run:**
```bash
php artisan migrate
```

**Backfill (optional):** Existing users will have `language = NULL`, which falls back to the app default locale (`config('app.locale')`). The column is synced from the `language` cookie on every authenticated request via `SetLocale` middleware. No manual backfill is strictly required.

---

## 2026-03-30 — Replace store system with apps table

**Migration file:** `2026_03_30_000001_replace_store_with_apps.php`

Replaced the old app-catalog/purchase model (`app_catalog`, `app_purchase`) with a flat `apps` table. All users have access to all apps; no purchase records needed.

**Run:**
```bash
php artisan migrate
```

---

## 2026-03-26 — User deletion requests, data exports, jobs tables

**Migration files:**
- `2026_03_26_000001_create_user_deletion_requests_table.php`
- `2026_03_26_000002_create_data_exports_table.php`
- `2026_03_26_000003_create_jobs_table.php`
- `2026_03_26_000004_add_foreign_keys_and_indexes.php`

**Run:**
```bash
php artisan migrate
```

---

## 2026-03-25 — Crypto schema migration

**Migration file:** `2026_03_25_200000_migrate_crypto_schema.php`

Migrated encryption key storage from legacy schema. Adds `enc_version` columns and widens binary columns to BLOB to support both the legacy Defuse AES-256 (`enc_version=1`) and the modern libsodium (`enc_version=2`) encryption backends. Creates a `crypto_migration_state` tracking table.

**Run:**
```bash
php artisan migrate
```

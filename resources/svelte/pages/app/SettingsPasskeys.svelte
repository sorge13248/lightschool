<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { startRegistration } from '@simplewebauthn/browser';
    import type { RegistrationResponseJSON } from '@simplewebauthn/browser';
    import { untrack } from 'svelte';
    import { apiFetch, apiFetchJson } from '../../lib/api';
    import { t } from '../../lib/i18n';
    import Modal from '../../components/ui/Modal.svelte';

    interface PasskeyItem {
        id: number;
        name: string;
        last_used_at: string | null;
        created_at: string;
    }

    interface Props {
        passkeys: PasskeyItem[];
    }

    const { passkeys: passkeysRaw }: Props = $props();

    let passkeys = $state<PasskeyItem[]>(untrack(() => passkeysRaw ?? []));

    // Registration state
    let registering       = $state(false);
    let registerError     = $state('');
    let showNameModal     = $state(false);
    let pendingCredential = $state<RegistrationResponseJSON | null>(null);
    let pendingOptions    = $state('');
    let suggestedName     = $state('');
    let passkeyName       = $state('');
    let nameSaving        = $state(false);
    let nameError         = $state('');

    // Delete state
    let deletingId    = $state<number | null>(null);
    let deleteError   = $state('');

    /** Guess a human-readable authenticator name from the WebAuthn response. */
    function guessAuthenticatorName(credential: RegistrationResponseJSON): string {
        const attachment = credential.authenticatorAttachment;
        const ua = navigator.userAgent;

        if (attachment === 'platform') {
            if (/windows/i.test(ua))                     return 'Windows Hello';
            if (/iphone|ipad/i.test(ua))                 return 'Face ID / Touch ID';
            if (/macintosh|mac os/i.test(ua))            return 'Touch ID';
            if (/android/i.test(ua))                     return 'Screen Lock';
            return t('passkey-platform-key');
        }

        if (attachment === 'cross-platform') {
            return t('passkey-security-key');
        }

        return t('passkey-default-name');
    }

    function formatDate(iso: string): string {
        try {
            const d = new Date(iso);
            return d.toLocaleDateString();
        } catch {
            return iso;
        }
    }

    async function startRegister(): Promise<void> {
        if (registering) return;
        registering    = true;
        registerError  = '';

        try {
            // 1. Fetch registration challenge
            const optRes = await fetch('/api/passkeys/register-options', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
            });
            if (!optRes.ok) throw new Error('options-failed');
            const optionsJson = await optRes.text();

            // 2. Trigger browser/authenticator prompt
            const credential = await startRegistration({ optionsJSON: JSON.parse(optionsJson) });

            // 3. Open name-confirmation modal with a pre-filled guess
            pendingCredential = credential;
            pendingOptions    = optionsJson;
            suggestedName     = guessAuthenticatorName(credential);
            passkeyName       = suggestedName;
            showNameModal     = true;
        } catch (err: unknown) {
            if (err instanceof Error && err.name === 'NotAllowedError') {
                // user cancelled — no error shown
            } else {
                registerError = t('passkey-error');
            }
        } finally {
            registering = false;
        }
    }

    async function confirmRegister(e: Event): Promise<void> {
        e.preventDefault();
        if (nameSaving || !pendingCredential) return;

        nameSaving = true;
        nameError  = '';

        const result = await apiFetchJson('/api/passkeys/register', 'POST', {
            passkey:         JSON.stringify(pendingCredential),
            passkey_options: pendingOptions,
            name:            passkeyName.trim() || suggestedName,
        });

        if (result.response === 'success') {
            // Refresh list
            const listResult = await apiFetch('/api/passkeys', 'GET');
            if (listResult.response === 'success') {
                passkeys = (listResult.passkeys as PasskeyItem[]) ?? [];
            }
            showNameModal     = false;
            pendingCredential = null;
            pendingOptions    = '';
            passkeyName       = '';
        } else {
            nameError = (result.text as string) ?? t('error');
        }

        nameSaving = false;
    }

    function closeNameModal(): void {
        if (nameSaving) return;
        showNameModal     = false;
        pendingCredential = null;
        pendingOptions    = '';
        passkeyName       = '';
        nameError         = '';
    }

    async function deletePasskey(id: number): Promise<void> {
        if (deletingId !== null) return;
        deletingId  = id;
        deleteError = '';

        const result = await apiFetch(`/api/passkeys/${id}`, 'DELETE');

        if (result.response === 'success') {
            passkeys = passkeys.filter(p => p.id !== id);
        } else {
            deleteError = (result.text as string) ?? t('error');
        }

        deletingId = null;
    }
</script>

<svelte:head><title>{t('settings-passkeys')} - {t('app-settings')} - LightSchool</title></svelte:head>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto">
        <p>{t('passkey-desc')}</p>

        {#if registerError}
            <div class="alert alert-danger">{registerError}</div>
        {/if}
        {#if deleteError}
            <div class="alert alert-danger">{deleteError}</div>
        {/if}

        {#if passkeys.length > 0}
            <table class="table" style="margin-bottom: 20px">
                <thead>
                    <tr>
                        <th>{t('passkey-name')}</th>
                        <th>{t('passkey-added')}</th>
                        <th>{t('passkey-last-used')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each passkeys as passkey (passkey.id)}
                        <tr>
                            <td>{passkey.name}</td>
                            <td>{formatDate(passkey.created_at)}</td>
                            <td>{passkey.last_used_at ? formatDate(passkey.last_used_at) : t('passkey-never-used')}</td>
                            <td style="text-align: right">
                                <button
                                    type="button"
                                    class="button"
                                    style="background: #c0392b; color: #fff; border: none; cursor: pointer"
                                    disabled={deletingId !== null}
                                    onclick={() => deletePasskey(passkey.id)}
                                >
                                    {t('delete')}
                                </button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        {:else}
            <p class="alert alert-warning">{t('passkey-none')}</p>
        {/if}

        <button
            type="button"
            class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            disabled={registering}
            onclick={startRegister}
        >
            {registering ? t('passkey-registering') : t('passkey-add')}
        </button>
    </div>
</div>

<!-- Name confirmation modal — shown after the browser prompt completes -->
<Modal
    open={showNameModal}
    title={t('passkey-name-modal-title')}
    maxWidth="480px"
    draggable
    onclose={closeNameModal}
>
    <form onsubmit={confirmRegister}>
        <p>{t('passkey-name-modal-desc')}</p>
        <input
            type="text"
            placeholder={t('passkey-name')}
            maxlength={255}
            bind:value={passkeyName}
            disabled={nameSaving}
            style="width: 100%; margin-bottom: 10px"
        />
        {#if nameError}
            <div class="alert alert-danger" style="margin-bottom: 10px">{nameError}</div>
        {/if}
        <input
            type="submit"
            value={nameSaving ? t('saving') : t('save')}
            disabled={nameSaving}
            class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
            style="float: right"
        />
        <div style="clear: both"></div>
    </form>
</Modal>

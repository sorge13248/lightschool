<script module>
    import AuthLayout from '../../layouts/AuthLayout.svelte';
    export const layout = AuthLayout;
</script>

<script lang="ts">
    import { startAuthentication } from '@simplewebauthn/browser';
    import { apiFetch, apiFetchJson } from '../../lib/api';
    import { t } from '../../lib/i18n';

    const {} = $props();

    let show2fa = $state(false);
    let loading = $state(false);
    let error = $state('');

    // Login form fields
    let username = $state('');
    let password = $state('');

    // 2FA form field
    let token = $state('');

    // DOM ref for post-2fa focus
    let tokenInput = $state<HTMLInputElement | null>(null);

    async function handleLogin(e: SubmitEvent): Promise<void> {
        e.preventDefault();
        if (loading) return;

        loading = true;
        error = '';

        const body = `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;

        const result = await apiFetch('/auth/login', 'POST', body);

        if (result.response === 'success') {
            window.location.href = result['redirect-url'] as string;
            return;
        }

        if (result.response === '2fa') {
            show2fa = true;
            setTimeout(() => tokenInput?.focus(), 0);
            loading = false;
            return;
        }

        error = (result.text as string) ?? t('error');
        loading = false;
    }

    async function handlePasskeyLogin(): Promise<void> {
        if (loading) return;

        loading = true;
        error = '';

        try {
            // 1. Fetch the WebAuthn challenge from the server
            const optionsRes = await fetch('/passkeys/authentication-options', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!optionsRes.ok) throw new Error('options-fetch-failed');
            const optionsJson = await optionsRes.text();

            // 2. Trigger the browser/authenticator prompt
            const credential = await startAuthentication({ optionsJSON: JSON.parse(optionsJson) });

            // 3. Send the signed credential to our JSON endpoint
            const result = await apiFetchJson('/api/passkeys/authenticate', 'POST', {
                start_authentication_response: JSON.stringify(credential),
            });

            if (result.response === 'success') {
                window.location.href = result['redirect-url'] as string;
                return;
            }

            error = (result.text as string) ?? t('error');
        } catch (err: unknown) {
            // User cancelled the browser prompt — don't show an error
            if (err instanceof Error && err.name === 'NotAllowedError') {
                // silently ignore
            } else {
                error = t('passkey-error');
            }
        } finally {
            loading = false;
        }
    }

    async function handle2fa(e: SubmitEvent): Promise<void> {
        e.preventDefault();
        if (loading) return;

        loading = true;
        error = '';

        const body = `2fa=true&token=${encodeURIComponent(token)}`;

        const result = await apiFetch('/auth/login?2fa=true', 'POST', body);

        if (result.response === 'success') {
            window.location.href = result['redirect-url'] as string;
            return;
        }

        error = (result.text as string) ?? t('error');
        loading = false;
        setTimeout(() => tokenInput?.focus(), 0);
    }
</script>

<svelte:head><title>{t('login')} - LightSchool</title></svelte:head>

<div
    class="welcome center-content background-image login"
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
                {#if !show2fa}
                    <form class="form-login" onsubmit={handleLogin}>
                        {#if error}
                            <div class="response alert alert-danger">{error}</div>
                        {/if}
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder={t('username')}
                            bind:value={username}
                            disabled={loading}
                        />
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder={t('password')}
                            bind:value={password}
                            disabled={loading}
                        />
                        <input
                            type="submit"
                            value={t('login')}
                            disabled={loading}
                        />
                    </form>
                {:else}
                    <form class="form-login-2fa" onsubmit={handle2fa}>
                        <input type="hidden" name="2fa" value="true" />
                        {#if error}
                            <div class="response alert alert-danger">{error}</div>
                        {/if}
                        <input
                            type="number"
                            id="token"
                            name="token"
                            placeholder={t('otp')}
                            maxlength={6}
                            bind:value={token}
                            bind:this={tokenInput}
                            disabled={loading}
                        />
                        <input
                            type="submit"
                            value={t('login')}
                            disabled={loading}
                        />
                    </form>
                {/if}
                {#if !show2fa}
                    <!-- svelte-ignore a11y_invalid_attribute -->
                    <a href="#" class="button" id="passkey-login"
                       onclick={(e) => { e.preventDefault(); handlePasskeyLogin(); }}
                       aria-disabled={loading}>
                        {t('passkey-login')}
                    </a>
                    <a href="/auth/register" class="button" id="register">{t('register')}</a>
                {/if}
                <a
                    href={show2fa ? '/auth/otp' : '/auth/password'}
                    class="button"
                    id="recover-pwd"
                >
                    {show2fa ? t('otp-lost') : t('recover-pwd')}
                </a>
                <small><a href="/">{t('website')}</a></small>
            </div>
        </div>
    </span>
</div>

<style lang="scss">
    h1, form {
        margin-bottom: 2rem;
    }

    small {
        display: block;
        margin-top: 2rem;
    }
</style>
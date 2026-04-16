<script module>
    import AuthLayout from '../../layouts/AuthLayout.svelte';
    export const layout = AuthLayout;
</script>

<script lang="ts">
    import { apiFetch } from '../../lib/api';
    import { t } from '../../lib/i18n';

    interface OtpResult {
        response: 'success' | 'error';
    }

    interface Props {
        otpResult?: OtpResult;
    }

    const { otpResult }: Props = $props();

    let loading = $state(false);
    let error = $state('');
    let submitted = $state(false);
    let successText = $state('');

    // Form field
    let username = $state('');

    // DOM ref for re-focus on error
    let usernameInput = $state<HTMLInputElement | null>(null);

    async function handleOtp(e: SubmitEvent): Promise<void> {
        e.preventDefault();
        if (loading) return;

        loading = true;
        error = '';

        const body = `username=${encodeURIComponent(username)}`;

        const result = await apiFetch('/auth/otp', 'POST', body);

        if (result.response === 'success') {
            successText = (result['text'] as string) ?? '';
            submitted = true;
            return;
        }

        error = (result['text'] as string) ?? t('error');
        loading = false;
        setTimeout(() => usernameInput?.focus(), 0);
    }
</script>

<svelte:head><title>{t('otp')} - LightSchool</title></svelte:head>

<div
    class="welcome center-content background-image login otp"
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
                {#if otpResult !== undefined}
                    {#if otpResult.response === 'success'}
                        <div class="alert alert-success">
                            {t('otp-deactivated')}<br /><br />
                            <a href="/auth/login" class="button">{t('login')}</a>
                        </div>
                    {:else}
                        <div class="alert alert-danger">
                            {t('otp-error')}
                        </div>
                    {/if}
                {:else if submitted}
                    <div class="alert alert-success">
                        <p>{successText}</p>
                    </div>
                {:else}
                    <form class="form-otp" onsubmit={handleOtp}>
                        <p>{t('otp-deactivate')}</p>
                        {#if error}
                            <div class="response alert alert-danger">{error}</div>
                        {/if}
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder={t('username')}
                            style="border-bottom-left-radius: 0; border-bottom-right-radius: 0"
                            bind:value={username}
                            bind:this={usernameInput}
                            disabled={loading}
                        /><br />
                        <input
                            type="submit"
                            value={t('deactovate')}
                            disabled={loading}
                        />
                    </form>
                {/if}
                <br />
                <small><a href="/">{t('website')}</a></small>
            </div>
        </div>
    </span>
</div>

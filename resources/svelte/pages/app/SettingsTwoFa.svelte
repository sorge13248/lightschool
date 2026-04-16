<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';
    import Modal from '../../components/ui/Modal.svelte';

    interface Props {
        hasTwofa: boolean;
        qrUri: string | null;
        secret: string | null;
    }

    const { hasTwofa: hasTwofaRaw, qrUri, secret }: Props = $props();

    const hasTwofaInit = untrack(() => hasTwofaRaw ?? false);

    let hasTwofa      = $state(hasTwofaInit);
    let activated     = $state(false);
    let resultHeader  = $state('');
    let resultText    = $state('');
    let resultType    = $state<'success' | ''>('');

    let activatePassword = $state('');
    let activateToken    = $state('');
    let activateSaving   = $state(false);
    let activateError    = $state('');

    let showDeactivate     = $state(false);
    let deactivatePassword = $state('');
    let deactivateSaving   = $state(false);
    let deactivateError    = $state('');

    async function handleActivate(e: Event): Promise<void> {
        e.preventDefault();
        activateSaving = true;
        activateError  = '';

        const body = [
            `password=${encodeURIComponent(activatePassword)}`,
            `token=${encodeURIComponent(activateToken)}`,
        ].join('&');

        try {
            const res = await apiFetch('/api/settings?type=twofa-activate', 'POST', body);
            if (res.response === 'success') {
                activated    = true;
                resultHeader = (res['header'] as string) ?? '';
                resultText   = res.text ?? '';
                resultType   = 'success';
            } else {
                activateError = (res.text as string) ?? t('error', 'Error');
            }
        } catch {
            activateError = t('error', 'Error');
        } finally {
            activateSaving = false;
        }
    }

    async function handleDeactivate(e: Event): Promise<void> {
        e.preventDefault();
        deactivateSaving = true;
        deactivateError  = '';

        try {
            const res = await apiFetch('/api/settings?type=twofa-deactivate', 'POST',
                `password=${encodeURIComponent(deactivatePassword)}`);
            if (res.response === 'success') {
                hasTwofa           = false;
                showDeactivate     = false;
                deactivatePassword = '';
                resultHeader = '';
                resultText   = res.text ?? '';
                resultType   = 'success';
            } else {
                deactivateError = (res.text as string) ?? t('error', 'Error');
            }
        } catch {
            deactivateError = t('error', 'Error');
        } finally {
            deactivateSaving = false;
        }
    }
</script>

<svelte:head><title>{t('settings-security-2fa-title')} - {t('app-settings')} - LightSchool</title></svelte:head>

<style>
    .big { font-size: 1.5em; font-weight: bold; }
</style>

<div class="container content-my settings-app">
    {#if activated || (resultText && !hasTwofa && hasTwofaInit)}
        <div class="configure-2fa">
            {#if resultHeader}<h1>{resultHeader}</h1>{/if}
            {#if resultType === 'success'}
                <div class="alert alert-success">{resultText}</div>
            {:else}
                <p>{resultText}</p>
            {/if}
        </div>
    {:else if hasTwofa}
        <div style="max-width: 1300px; margin: 0 auto" class="configure-2fa">
            <p class="alert alert-success">{t('settings-2fa-active-notice')}</p>
            <p>{t('settings-2fa-deactivate-question')}</p>
            <!-- svelte-ignore a11y_invalid_attribute -->
            <a href="#"
               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker deactivate-2fa"
               onclick={(e) => { e.preventDefault(); showDeactivate = true; }}>
                {t('settings-2fa-deactivate')}
            </a>
        </div>

        <Modal
            open={showDeactivate}
            title={t('settings-2fa-deactivate-window-title', 'Deactivate 2FA')}
            maxWidth="522px"
            draggable
            onclose={() => { showDeactivate = false; }}
        >
            <form method="post" action="/api/settings?type=twofa-deactivate" class="deactivate-2fa"
                  onsubmit={handleDeactivate}>
                <p>{t('settings-2fa-deactivate-warning')}</p>
                <input type="password" id="deactivate-password" name="password"
                       placeholder={t('password')} maxlength={128}
                       bind:value={deactivatePassword}/>
                <input type="submit" value={t('settings-2fa-deactivate')} disabled={deactivateSaving}
                       class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                       style="float: right"/>
                {#if deactivateError}
                    <div class="response alert alert-danger" style="margin-top: 10px">
                        {deactivateError}
                    </div>
                {/if}
            </form>
        </Modal>
    {:else}
        <div style="max-width: 1300px; margin: 0 auto" class="configure-2fa">
            <p>{t('settings-2fa-intro')}</p>
            <div class="row">
                <div class="col-md-4">
                    <p class="big">{t('settings-2fa-step1-title')}</p>
                    <p>{t('settings-2fa-step1-desc')}</p>
                    <p>{t('settings-2fa-recommends')}</p>
                    <img src="/img/authy.png" style="max-width: 250px" alt="Authy"/><br/>
                    <a href="https://play.google.com/store/apps/details?id=com.authy.authy" target="_blank">
                        <img src="/img/play-store/en.svg" style="height: 40px; margin: 10px" alt="Play Store"/>
                    </a>
                    <a href="https://itunes.apple.com/app/authy/id494168017" target="_blank">
                        <img src="/img/app-store/it.svg" style="height: 40px; margin: 10px" alt="App Store"/>
                    </a>
                    <br/>
                    <p>{t('settings-2fa-alternatives')}</p>
                    <ul>
                        <li>FreeOTP: <a href="https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp" target="_blank">Play</a> &bull; <a href="https://itunes.apple.com/app/freeotp-authenticator/id872559395" target="_blank">App</a></li>
                        <li>Google Auth: <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Play</a> &bull; <a href="https://itunes.apple.com/app/google-authenticator/id388497605" target="_blank">App</a></li>
                        <li>Microsoft Auth: <a href="https://play.google.com/store/apps/details?id=com.azure.authenticator" target="_blank">Play</a> &bull; <a href="https://itunes.apple.com/app/microsoft-authenticator/id983156458" target="_blank">App</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <p class="big">{t('settings-2fa-step2-title')}</p>
                    <p>{t('settings-2fa-step2-desc')}</p>
                    <ul>
                        <li>
                            {t('settings-2fa-scan-qr')}
                            <p>{#if qrUri}<img src={qrUri} alt="QR Code"/>{/if}</p>
                        </li>
                        <li>
                            {t('settings-2fa-enter-code')}<br/>
                            <code style="background-color: #F6F6F6; border-radius: 10px; padding: 10px; margin: 5px; display: inline-block; margin-left: 0">
                                {secret ?? ''}
                            </code>
                        </li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <p class="big">{t('settings-2fa-step3-title')}</p>
                    <form method="post" action="/api/settings?type=twofa-activate" class="activate-2fa"
                          onsubmit={handleActivate}>
                        <p>{t('settings-2fa-enter-password')}</p>
                        <input type="password" id="password" name="password"
                               placeholder={t('password')} maxlength={128}
                               bind:value={activatePassword}/>
                        <p>{t('settings-2fa-step3-desc')}</p>
                        <input type="number" id="token" name="token"
                               placeholder="Token" maxlength={12}
                               bind:value={activateToken}/>
                        <input type="submit" value={t('settings-2fa-activate')} disabled={activateSaving}
                               class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/>
                        {#if activateError}
                            <div class="response alert alert-danger" style="margin-top: 10px">
                                {activateError}
                            </div>
                        {/if}
                    </form>
                </div>
            </div>
        </div>
    {/if}
</div>

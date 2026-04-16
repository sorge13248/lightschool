<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';

    let oldPwd   = $state('');
    let newPwd   = $state('');
    let newPwd2  = $state('');
    let showOld  = $state(false);
    let showNew  = $state(false);
    let showNew2 = $state(false);
    let saving   = $state(false);
    let respHtml = $state('');
    let respType = $state<'success' | 'error' | ''>('');

    async function handleSubmit(e: Event): Promise<void> {
        e.preventDefault();
        saving = true;
        respHtml = '';
        respType = '';

        const body = [
            `old=${encodeURIComponent(oldPwd)}`,
            `new=${encodeURIComponent(newPwd)}`,
            `new-2=${encodeURIComponent(newPwd2)}`,
        ].join('&');

        try {
            const res = await apiFetch('/api/settings?type=password', 'POST', body);
            respHtml = res.text ?? '';
            respType = res.response === 'success' ? 'success' : 'error';
            if (res.response === 'success') {
                oldPwd = '';
                newPwd = '';
                newPwd2 = '';
            }
        } catch {
            respType = 'error';
            respHtml = t('error', 'Error');
        } finally {
            saving = false;
        }
    }
</script>

<svelte:head><title>{t('settings-password-title')} - {t('app-settings')} - LightSchool</title></svelte:head>

<style>
    .change-password label,
    .change-password input:not([type=checkbox]):not([type=submit]) {
        max-width: 300px;
        width: 100%;
    }
</style>

<div class="container content-my settings-app">
    <div style="max-width: 1300px; margin: 0 auto; padding: 25px" class="change-password">
        <h1>{t('settings-password-title')}</h1>
        <p>{t('settings-password-desc')}</p>
        <hr/>
        <form method="post" action="/api/settings?type=password" class="form-password"
              onsubmit={handleSubmit}>

            <label for="old">{t('settings-password-current')}</label>
            <input type={showOld ? 'text' : 'password'} id="old" name="old"
                   placeholder={t('settings-password-current')} tabindex={1}
                   bind:value={oldPwd} class="box-shadow-1-all"/>
            <label>
                <input type="checkbox" bind:checked={showOld}/> {t('settings-password-show')}
            </label><br/>

            <label for="new">{t('new-password')}</label>
            <input type={showNew ? 'text' : 'password'} id="new" name="new"
                   placeholder={t('new-password')} tabindex={2}
                   bind:value={newPwd} class="box-shadow-1-all"/>
            <label>
                <input type="checkbox" bind:checked={showNew}/> {t('settings-password-show')}
            </label><br/>

            <label for="new-2">{t('settings-password-repeat')}</label>
            <input type={showNew2 ? 'text' : 'password'} id="new-2" name="new-2"
                   placeholder={t('settings-password-repeat')} tabindex={3}
                   bind:value={newPwd2} class="box-shadow-1-all"/>
            <label>
                <input type="checkbox" bind:checked={showNew2}/> {t('settings-password-show')}
            </label><br/>
            <br/>

            <input type="submit" value={t('settings-password-submit')} disabled={saving} tabindex={4}
                   class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/>

            {#if respHtml}
                <div class="response alert alert-{respType === 'success' ? 'success' : 'danger'}"
                     style="margin-top: 10px">
                    {respHtml}
                </div>
            {/if}
        </form>
    </div>
</div>

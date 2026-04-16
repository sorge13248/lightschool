<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { untrack } from 'svelte';
    import type { CurrentUser } from '../../lib/types';
    import { t } from '../../lib/i18n';
    import { apiFetch } from '../../lib/api';

    interface Props {
        currentUser: CurrentUser;
        email: string;
        username: string;
    }

    const { currentUser, email, username }: Props = $props();

    const profilePicUrl  = untrack(() => currentUser.profile_picture ?? null);
    const initialLetter  = untrack(() => (currentUser.name || '?').charAt(0).toUpperCase());

    let nameVal     = $state(untrack(() => currentUser.name ?? ''));
    let surnameVal  = $state(untrack(() => currentUser.surname ?? ''));
    let emailVal    = $state(untrack(() => email ?? ''));
    let usernameVal = $state(untrack(() => username ?? ''));

    let saving   = $state(false);
    let respHtml = $state('');
    let respType = $state<'success' | 'error' | ''>('');

    async function handleSubmit(e: Event): Promise<void> {
        e.preventDefault();
        saving = true;
        respHtml = '';
        respType = '';

        const body = [
            `name=${encodeURIComponent(nameVal)}`,
            `surname=${encodeURIComponent(surnameVal)}`,
            `email=${encodeURIComponent(emailVal)}`,
            `username=${encodeURIComponent(usernameVal)}`,
        ].join('&');

        try {
            const res = await apiFetch('/api/settings?type=account', 'POST', body);
            respHtml = res.text ?? '';
            respType = res.response === 'success' ? 'success' : 'error';
        } catch {
            respType = 'error';
            respHtml = t('error', 'Error');
        } finally {
            saving = false;
        }
    }
</script>

<svelte:head><title>{t('account')} - {t('app-settings')} - LightSchool</title></svelte:head>

<style>
    .row > div { padding: 10px; }
    input { width: 100%; }
    small { display: block; }
</style>

<div class="container content-my settings-app">
    <form method="post" action="/api/settings?type=account" class="form-account" style="padding: 25px"
          onsubmit={handleSubmit}>
        <div style="max-width: 1300px; margin: 0 auto">
            <div class="row">
                <div class="col-md-2">
                    {#if profilePicUrl}
                        <img src={profilePicUrl}
                             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-right: 50px; margin-top: 16px; float: left"
                             alt=""/>
                    {:else}
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: #ddd; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5em; color: #999; float: left; margin-right: 50px; margin-top: 16px">
                            {initialLetter}
                        </div>
                    {/if}
                </div>
                <div class="col-md-10">
                    <h1 style="text-align: left">{nameVal} {surnameVal}</h1>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">{t('name')}</label><br/>
                            <input type="text" id="name" name="name" placeholder={t('name')}
                                   bind:value={nameVal} class="box-shadow-1-all"/>
                        </div>
                        <div class="col-md-6">
                            <label for="surname">{t('surname')}</label><br/>
                            <input type="text" id="surname" name="surname" placeholder={t('surname')}
                                   bind:value={surnameVal} class="box-shadow-1-all"/>
                        </div>
                        <div class="col-md-6">
                            <label for="email">{t('e-mail')}</label><br/>
                            <input type="email" id="email" name="email" placeholder={t('e-mail')}
                                   bind:value={emailVal} class="box-shadow-1-all"/>
                            <small>{t('settings-account-email-hint')}</small>
                        </div>
                        <div class="col-md-6">
                            <label for="username">{t('username')}</label><br/>
                            <input type="text" id="username" name="username" placeholder={t('username')}
                                   bind:value={usernameVal} class="box-shadow-1-all"/>
                            <small>{t('settings-account-username-hint')}</small>
                        </div>
                        <div class="col-md-12">
                            <input type="submit" value={t('save')} disabled={saving}
                                   class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/>
                            {#if respHtml}
                                <div class="response alert alert-{respType === 'success' ? 'success' : 'danger'}"
                                     style="margin-top: 10px">
                                    {respHtml}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

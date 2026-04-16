<script module>
    import AuthLayout from '../../layouts/AuthLayout.svelte';
    export const layout = AuthLayout;
</script>

<script lang="ts">
    import { t } from '../../lib/i18n';

    interface PageData {
        appName: string;
        success?: boolean;
        message?: string;
    }

    const { data }: { data: PageData } = $props();
</script>

<svelte:head><title>{t('verify')} - LightSchool</title></svelte:head>

<div
    class="welcome center-content background-image login verify"
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
                {#if data.success === true}
                    <div class="alert alert-success">
                        {data.message ?? t('verify-ok')}<br /><br />
                        <a href="/auth/login" class="button">{t('login')}</a>
                    </div>
                {:else if data.success === false}
                    <div class="alert alert-danger">
                        {data.message ?? t('verify-error')}
                    </div>
                {:else}
                    <div class="alert alert-danger">
                        {t('nothing-to-see')}
                    </div>
                {/if}
                <br />
                <small><a href="/">{t('website')}</a></small>
            </div>
        </div>
    </span>
</div>

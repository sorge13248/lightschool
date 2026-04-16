<script lang="ts">
    import Cookies from 'js-cookie';
    import { fly } from 'svelte/transition';
    import { t } from '../lib/i18n';

    type CookieContent = {
        has_user_accepted: boolean;
        accepted_timestamp: string;
    };

    function isAccepted(): boolean {
        const raw = Cookies.get('cookie-bar');
        if (!raw) return false;
        try {
            return (JSON.parse(raw) as CookieContent).has_user_accepted === true;
        } catch {
            return false;
        }
    }

    let show = $state(!isAccepted());

    function accept(): void {
        Cookies.set('cookie-bar', JSON.stringify({
            has_user_accepted: true,
            accepted_timestamp: new Date().toISOString(),
        } satisfies CookieContent), { expires: 365 * 10, path: '/' });
        show = false;
    }
</script>

{#if show}
    <div
        class="cookie-bar"
        role="region"
        aria-label={t('cookie-bar')}
        transition:fly={{ y: 100, duration: 350 }}
    >
        <div class="cookie-bar__inner">
            <span class="cookie-bar__icon" aria-hidden="true">🍪</span>
            <div class="cookie-bar__text">
                <p class="cookie-bar__title">{t('cookie-bar')}</p>
                <p class="cookie-bar__notice">
                    {t('cookie-bar-notice')}
                    <a href="/cookie">{t('cookie-learn-more')}</a>.
                </p>
            </div>
            <div class="cookie-bar__actions">
                <button type="button" class="cookie-bar__accept" onclick={accept}>
                    {t('cookie-bar-accept')}
                </button>
            </div>
        </div>
    </div>
{/if}

<style lang="scss">
    .cookie-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 99999;
        background: #fff;
        border-top: 3px solid #1e6ad3;
        box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.12);
        padding: 18px 24px;
        font-family: inherit;

        &__inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        &__icon {
            font-size: 28px;
            flex-shrink: 0;
            line-height: 1;
        }

        &__text {
            flex: 1;
            min-width: 200px;
        }

        &__title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a1a1a;
            margin: 0 0 3px;
        }

        &__notice {
            font-size: 0.87rem;
            color: #555;
            margin: 0;
            line-height: 1.45;

            a {
                color: #1e6ad3;
                text-decoration: underline;
                white-space: nowrap;

                &:hover { color: #155bb5; }
            }
        }

        &__actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        &__accept {
            background: #1e6ad3;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 9px 22px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
            white-space: nowrap;

            &:hover { background: #155bb5; }

            &:focus-visible {
                outline: 3px solid #1e6ad3;
                outline-offset: 2px;
            }
        }

        @media (max-width: 600px) {
            &__inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            &__icon { display: none; }

            &__actions { width: 100%; }

            &__accept {
                width: 100%;
                text-align: center;
            }
        }
    }
</style>

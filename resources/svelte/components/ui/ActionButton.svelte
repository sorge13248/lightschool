<script lang="ts">
    import { PlusIcon, ClipboardTextIcon } from 'phosphor-svelte';

    interface Props {
        onclick?: (e: MouseEvent) => void;
        title?: string;
        variant?: 'add' | 'paste';
        /** Stack position (0 = bottom-most). Each step offsets the button up by 100px. */
        index?: number;
    }

    const { onclick, title = '', variant = 'add', index = 0 }: Props = $props();
</script>

<button
    type="button"
    class="action-button accent-bkg-gradient box-shadow-1-all {variant}"
    style="--stack-index: {index}"
    {title}
    onclick={(e) => { onclick?.(e); }}
>
    {#if variant === 'add'}
        <PlusIcon weight="light" />
    {:else}
        <ClipboardTextIcon weight="light" />
    {/if}
</button>

<style lang="scss">
    .action-button {
        border: none;
        position: fixed;
        bottom: calc(90px + var(--stack-index, 0) * 100px);
        right: 30px;
        border-radius: 50%;
        z-index: 2000;
        font-size: 2em;
        transition: 0.1s ease-in-out;
        width: 76px;
        height: 76px;
        padding: 0;
        color: white;
        text-decoration: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

        @media (max-width: 576px) {
            bottom: calc(20px + var(--stack-index, 0) * 80px);
            right: 20px;
        }

        @media (max-width: 992px) {
            width: 56px;
            height: 56px;
            line-height: 56px;
        }
    }
</style>

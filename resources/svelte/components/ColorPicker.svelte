<script lang="ts">
    import { fade } from 'svelte/transition';
    import { t } from '../lib/i18n';

    const PALETTE: string[][] = [
        ['#000000','#202020','#404040','#606060','#808080','#A0A0A0','#C0C0C0','#E0E0E0','#FFFFFF'],
        ['#610B0B','#8A0808','#B40404','#DF0101','#FF0000','#FE2E2E','#FA5858','#F78181','#F5A9A9'],
        ['#61380B','#8A4B08','#B45F04','#DF7401','#FF8000','#FE9A2E','#FAAC58','#F7BE81','#F5D0A9'],
        ['#5E610B','#868A08','#AEB404','#D7DF01','#FFFF00','#F7FE2E','#F4FA58','#F3F781','#F2F5A9'],
        ['#0B610B','#088A08','#04B404','#01DF01','#00FF00','#2EFE2E','#58FA58','#81F781','#A9F5A9'],
        ['#0B615E','#088A85','#04B4AE','#01DFD7','#00FFFF','#2EFEF7','#58FAF4','#81F7F3','#A9F5F2'],
        ['#0B3861','#084B8A','#045FB4','#0174DF','#0080FF','#2E9AFE','#58ACFA','#81BEF7','#A9D0F5'],
        ['#0B0B61','#08088A','#0404B4','#0101DF','#0000FF','#2E2EFE','#5858FA','#8181F7','#A9A9F5'],
        ['#610B5E','#8A0886','#B404AE','#DF01D7','#FF00FF','#FE2EF7','#FA58F4','#F781F3','#F5A9F2'],
    ];

    let visible = $state(false);
    let top = $state(0);
    let left = $state(0);
    let showNone = $state(false);
    let activeInput: HTMLInputElement | null = null;
    let pickerEl: HTMLDivElement | null = $state(null);

    function handleFocusIn(e: FocusEvent): void {
        const target = e.target as Element | null;
        if (!target) return;
        const inp = target.closest("[data-fra-color-picker='1']") as HTMLInputElement | null;
        if (!inp) return;
        activeInput = inp;
        showNone = inp.hasAttribute('data-fra-color-picker-default');
        const rect = inp.getBoundingClientRect();
        top = rect.bottom + window.scrollY;
        left = rect.left + window.scrollX;
        visible = true;
    }

    function handleFocusOut(e: FocusEvent): void {
        const target = e.target as Element | null;
        if (!target) return;
        if (!target.closest("[data-fra-color-picker='1']")) return;
        setTimeout(() => {
            const focused = document.activeElement;
            if (focused && pickerEl?.contains(focused)) return;
            if (focused?.closest("[data-fra-color-picker='1']")) return;
            visible = false;
        }, 200);
    }

    function applyColor(hex: string): void {
        if (!activeInput) return;
        activeInput.value = hex;
        activeInput.style.color = hex;
        activeInput.style.backgroundColor = hex;
        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        visible = false;
    }
</script>

<svelte:document onfocusin={handleFocusIn} onfocusout={handleFocusOut} />

{#if visible}
    <div
        bind:this={pickerEl}
        class="color-picker"
        style:top="{top}px"
        style:left="{left}px"
        transition:fade={{ duration: 200 }}
    >
        {#if showNone}
            <div class="none-row">
                <button type="button" class="button none-btn" onclick={() => applyColor('')}>
                    {t('nobody', 'Nessuno')}
                </button>
            </div>
        {/if}
        {#each PALETTE as row}
            <div class="swatch-row">
                {#each row as color}
                    <button
                        type="button"
                        class="swatch"
                        style:background-color={color}
                        onclick={() => applyColor(color)}
                        aria-label={color}
                    ></button>
                {/each}
            </div>
        {/each}
    </div>
{/if}

<style lang="scss">
    .color-picker {
        position: absolute;
        padding: 5px;
        padding-bottom: 0;
        background-color: rgba(255, 255, 255, 0.9);
        z-index: 100000;
        margin-bottom: 80px;
    }

    .swatch-row {
        display: flex;
    }

    .swatch {
        display: inline-block;
        width: 24px;
        height: 24px;
        margin: 2px;
        border: none;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
    }

    .none-row {
        text-align: center;
        margin-bottom: 4px;
    }

    .none-btn {
        width: auto;
        height: auto;
    }
</style>

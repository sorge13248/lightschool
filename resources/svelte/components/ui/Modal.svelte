<script lang="ts">
  import { XCircleIcon } from "phosphor-svelte";
  import type { Snippet } from "svelte";
  import { fade } from "svelte/transition";

  interface Props {
    open: boolean;
    title: string;
    maxWidth?: string;
    draggable?: boolean;
    recenterKey?: unknown;
    onclose?: () => void;
    children?: Snippet;
  }

  const {
    open = $bindable(false),
    title,
    maxWidth = "522px",
    draggable = false,
    recenterKey,
    onclose,
    children,
  }: Props = $props();

  let windowEl = $state<HTMLDivElement | null>(null);
  let posX = $state<number | null>(null);
  let posY = $state<number | null>(null);

  let isDragging = false;
  let hasMoved = false;
  let dragOffsetX = 0;
  let dragOffsetY = 0;

  const animationDuration = 150; // Match fade transition duration

  $effect(() => {
    void maxWidth;     // track maxWidth as a dependency
    void recenterKey;  // bump this to force re-center after async content loads
    if (open && windowEl && !hasMoved) {
      posX = Math.max(0, (window.innerWidth - windowEl.offsetWidth) / 2);
      posY = Math.max(0, (window.innerHeight - windowEl.offsetHeight) / 2);
    }
    if (!open) {
      posX = null;
      posY = null;
      hasMoved = false;
    }
  });

  function handleClose(): void {
    onclose?.();
  }

  function startDrag(e: MouseEvent): void {
    if (!draggable || !windowEl) return;
    if (
      (e.target as HTMLElement).closest(
        ".btn_close, button, a, input, select, textarea",
      )
    )
      return;
    isDragging = true;
    const rect = windowEl.getBoundingClientRect();
    dragOffsetX = e.clientX - rect.left;
    dragOffsetY = e.clientY - rect.top;
    e.preventDefault();
  }

  function onMouseMove(e: MouseEvent): void {
    if (!isDragging || !windowEl) return;
    hasMoved = true;
    posX = Math.max(0, Math.min(window.innerWidth - windowEl.offsetWidth, e.clientX - dragOffsetX));
    posY = Math.max(0, Math.min(window.innerHeight - windowEl.offsetHeight, e.clientY - dragOffsetY));
  }

  function stopDrag(): void {
    isDragging = false;
  }
</script>

<svelte:window onmousemove={onMouseMove} onmouseup={stopDrag} />

<style lang="scss">
  @use '../../../scss/variables' as *;

  :global(.fra-windows-overlay) {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    margin: 0;
    border-radius: 0;

    &,
    &:hover {
      background-color: rgba(30, 106, 211, 0.8);
      background-image: none;
    }
  }

  :global(.fra-windows) {
    position: fixed;
    top: 0;
    left: 0;
    padding: 0;
    margin: 0;
    z-index: 10001;
    border-radius: 0.5rem;
    box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.5);

    :global(.titlebar) {
      background-color: $accent-flat;
      color: white;
      display: flex;
      align-items: center;
      border-radius: 0.5rem 0.5rem 0 0;

      button {
        display: inline-block;
        padding: 0.2rem;
        text-align: center;
        width: 40px;
        cursor: pointer;
        @include transition;
        border-top-right-radius: 0.5rem;
        font-size: 1.5em;
        background-color: transparent;
        border: 0;
        color: white;

        &:hover,
        &:focus {
          background-color: $accent-dark;
        }

        &.red {
          &:hover,
          &:focus {
            background-color: #ff1d04;
          }
        }
      }

      > .left-control  { text-align: left; }
      > .center-control { width: 100%; text-align: center; }
      > .right-control { text-align: right; }
    }

    :global(.fra-windows-content) {
      overflow-y: auto;
      border-radius: 0 0 0.5rem 0.5rem;
    }
  }
</style>

{#if open}
  <!-- Backdrop -->
  <button
    type="button"
    class="fra-windows-overlay"
    style="z-index: 10000; border: none; padding: 0; cursor: default;"
    onclick={handleClose}
    aria-label="Close dialog"
    transition:fade={{ duration: animationDuration }}
  ></button>

  <div
    bind:this={windowEl}
    class="fra-windows active"
    style:display="block"
    style:max-width={maxWidth}
    style:width="100%"
    style:left={posX != null ? posX + 'px' : undefined}
    style:top={posY != null ? posY + 'px' : undefined}
    transition:fade={{ duration: animationDuration }}
    role="dialog"
    aria-modal="true"
    aria-label={title}
  >
    <div
      class="titlebar accent-frawindows-titlebar"
      onmousedown={startDrag}
      role="presentation"
    >
      <div class="left-control"></div>
      <div class="center-control">{title}</div>
      <div class="right-control">
        <button
          type="button"
          class="red btn_close"
          onclick={handleClose}
          aria-label="Close"
        >
          <XCircleIcon weight="light" />
        </button>
      </div>
    </div>
    <div class="fra-windows-content" style="padding: 20px;">
      {@render children?.()}
    </div>
  </div>
{/if}

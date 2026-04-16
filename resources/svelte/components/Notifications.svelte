<script lang="ts">
  import { notifications } from "../stores/notifications.svelte";

  $effect(() => {
    for (const n of notifications.items) {
      if (n.autoClose && !(n as any)._timerSet) {
        (n as any)._timerSet = true;
        setTimeout(() => notifications.remove(n.id), n.autoClose);
      }
    }
  });
</script>

<div class="notifications-container">
  {#each notifications.items as n (n.id)}
    <button
      type="button"
      class="notification-toast"
      class:error={n.type === "error"}
      onclick={() => notifications.remove(n.id)}
    >
      {n.text}
    </button>
  {/each}
</div>

<style lang="scss">
  .notifications-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 50000;
    max-width: 100%;
    pointer-events: none;

    @media (max-width: 576px) {
      top: 0;
      bottom: auto;
      right: 0;
      left: 0;
      gap: 0;
    }

    .notification-toast {
      pointer-events: all;
      display: block;
      width: auto;
      height: auto;
      max-width: 100%;
      background-color: #1e6ad3;
      color: white;
      padding: 20px;
      cursor: pointer;
      border: none;
      text-align: left;

      &.error {
        background-color: red;
      }
    }
  }
</style>

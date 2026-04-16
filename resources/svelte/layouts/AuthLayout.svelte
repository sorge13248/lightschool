<script lang="ts">
    import type { Snippet } from 'svelte';
    import CookieBar from '../components/CookieBar.svelte';

    interface Props {
        children: Snippet;
    }

    const { children }: Props = $props();
</script>

<svelte:head>
    <link rel="stylesheet" href="/css/lightschool.css" />
</svelte:head>

{@render children()}
<CookieBar />

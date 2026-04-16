<script lang="ts">
    import type { AppPageData } from './lib/types';
    import AppLayout from './layouts/AppLayout.svelte';
    import Notifications from './components/Notifications.svelte';

    // Page components — TODO: implement each
    import Dashboard from './pages/app/Dashboard.svelte'; // TODO: implement
    import FileManager from './pages/app/FileManager.svelte'; // TODO: implement
    import Contact from './pages/app/Contact.svelte'; // TODO: implement
    import Diary from './pages/app/Diary.svelte'; // TODO: implement
    import Message from './pages/app/Message.svelte'; // TODO: implement
    import Timetable from './pages/app/Timetable.svelte'; // TODO: implement
    import Settings from './pages/app/Settings.svelte'; // TODO: implement
    import SettingsAccount from './pages/app/SettingsAccount.svelte'; // TODO: implement
    import SettingsApp from './pages/app/SettingsApp.svelte'; // TODO: implement
    import SettingsCustomize from './pages/app/SettingsCustomize.svelte'; // TODO: implement
    import SettingsLanguage from './pages/app/SettingsLanguage.svelte'; // TODO: implement
    import SettingsSecurity from './pages/app/SettingsSecurity.svelte'; // TODO: implement
    import SettingsTwoFa from './pages/app/SettingsTwoFa.svelte'; // TODO: implement
    import SettingsPassword from './pages/app/SettingsPassword.svelte'; // TODO: implement
    import SettingsDeleteAccount from './pages/app/SettingsDeleteAccount.svelte'; // TODO: implement
    import SettingsExportData from './pages/app/SettingsExportData.svelte'; // TODO: implement
    import Writer from './pages/app/Writer.svelte'; // TODO: implement
    import Reader from './pages/app/Reader.svelte'; // TODO: implement
    import Share from './pages/app/Share.svelte'; // TODO: implement
    import Social from './pages/app/Social.svelte'; // TODO: implement
    import Trash from './pages/app/Trash.svelte'; // TODO: implement
    import Project from './pages/app/Project.svelte'; // TODO: implement
    import AppNotPurchased from './pages/app/AppNotPurchased.svelte'; // TODO: implement
    import ExportDownload from './pages/app/ExportDownload.svelte'; // TODO: implement

    const page = window.__PAGE__ ?? '';
    const pageData = (window.__PAGE_DATA__ ?? {}) as AppPageData;

    const currentUser = pageData.currentUser;
    const allApps = pageData.allApps ?? [];
</script>

<AppLayout
    {currentUser}
    currentApp={page}
    pageTitle={pageData.pageTitle as string ?? ''}
    {allApps}
>
    {#if page === 'dashboard'}
        <Dashboard data={pageData} />
    {:else if page === 'file-manager'}
        <FileManager data={pageData} />
    {:else if page === 'contact'}
        <Contact data={pageData} />
    {:else if page === 'diary'}
        <Diary data={pageData} />
    {:else if page === 'message'}
        <Message data={pageData} />
    {:else if page === 'timetable'}
        <Timetable data={pageData} />
    {:else if page === 'settings'}
        <Settings data={pageData} />
    {:else if page === 'settings/account'}
        <SettingsAccount data={pageData} />
    {:else if page === 'settings/app'}
        <SettingsApp data={pageData} />
    {:else if page === 'settings/customize'}
        <SettingsCustomize data={pageData} />
    {:else if page === 'settings/language'}
        <SettingsLanguage data={pageData} />
    {:else if page === 'settings/security'}
        <SettingsSecurity data={pageData} />
    {:else if page === 'settings/2fa'}
        <SettingsTwoFa data={pageData} />
    {:else if page === 'settings/password'}
        <SettingsPassword data={pageData} />
    {:else if page === 'settings/delete-account'}
        <SettingsDeleteAccount data={pageData} />
    {:else if page === 'settings/export-data'}
        <SettingsExportData data={pageData} />
    {:else if page === 'writer'}
        <Writer data={pageData} />
    {:else if page === 'reader'}
        <Reader data={pageData} />
    {:else if page === 'share'}
        <Share data={pageData} />
    {:else if page === 'social'}
        <Social data={pageData} />
    {:else if page === 'trash'}
        <Trash data={pageData} />
    {:else if page === 'project'}
        <Project data={pageData} />
    {:else if page === 'app-not-purchased'}
        <AppNotPurchased data={pageData} />
    {:else if page === 'export-download'}
        <ExportDownload data={pageData} />
    {/if}
</AppLayout>

<Notifications />

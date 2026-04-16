<script module>
    import AppLayout from '../../layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import { onMount } from 'svelte';
    import { apiFetch } from '../../lib/api';
    import { t } from '../../lib/i18n';
    import type { CurrentUser } from '../../lib/types';
    import { ArrowUpIcon, FolderIcon, BookOpenIcon, BookIcon, FileIcon } from 'phosphor-svelte';

    const fileIconMap: Record<string, typeof FileIcon> = { folder: FolderIcon, notebook: BookOpenIcon, diary: BookIcon };

    interface DesktopItem {
        id: number;
        name: string;
        type: string;
        file_type: string | null;
        icon: string | null;
    }

    interface DiaryEvent {
        id: number;
        name: string;
        diary_type: string | null;
        diary_date: string;
        diary_priority: number;
    }

    interface TimetableEntry {
        subject: string;
        book: string;
        fore?: string;
    }

    interface Props {
        quote: { text: string; author: string } | null;
        desktop: DesktopItem[];
        diaryEvents: DiaryEvent[];
        tomorrowDayName: string;
        currentUser: CurrentUser;
    }

    const { quote, desktop, diaryEvents, tomorrowDayName }: Props = $props();

    let timetableLoading = $state(true);
    let timetableItems   = $state<TimetableEntry[]>([]);
    let timetableEmpty   = $state(false);

    function getItemHref(item: DesktopItem): string {
        switch (item.type) {
            case 'folder':   return `/my/app/file-manager?folder=${item.id}`;
            case 'notebook': return `/my/app/reader/notebook/${item.id}`;
            case 'file':     return `/my/app/reader/file/${item.id}`;
            case 'diary':    return `/my/app/reader/diary/${item.id}`;
            default:         return '/my/app/file-manager';
        }
    }


    function isImageFile(item: DesktopItem): boolean {
        return item.type === 'file' && (item.file_type?.startsWith('image/') ?? false);
    }

    function formatDiaryDate(dateStr: string): string {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day   = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year  = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function diaryLabel(event: DiaryEvent): string {
        const typePart = event.diary_type ? event.diary_type + ' di ' : '';
        return `${typePart}${event.name} il ${formatDiaryDate(event.diary_date)}`;
    }

    onMount(async () => {
        try {
            const res = await apiFetch('/api/timetable?type=get-tomorrow');
            if (!Array.isArray(res) || res.length === 0) {
                timetableEmpty = true;
            } else {
                timetableItems = res as TimetableEntry[];
            }
        } catch {
            timetableEmpty = true;
        } finally {
            timetableLoading = false;
        }
    });
</script>

<svelte:head><title>LightSchool</title></svelte:head>

<div class="content content-my">
    <div class="dashboard-inner">

        {#if quote}
            <p>
                <b>{t('quote')}</b><br/>
                {quote.text} &bull; <small><i>{quote.author}</i></small>
            </p>
        {/if}

        <div class="row">

            <!-- Desktop files -->
            <div class="col-md-6">
                <h2>
                    {t('desktop')}
                    <a
                        href="/my/app/file-manager?folder=desktop"
                        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                        style="float: right"
                    >{t('go-to')}</a>
                </h2>
                <div style="clear: both"></div>
                <div style="text-align: left">
                    {#if desktop.length === 0}
                        <p style="color: gray">{t('no-desktop')}</p>
                    {:else}
                        {#each desktop as item (item.id)}
                            {@const ItemIcon = fileIconMap[item.type] ?? FileIcon}
                            <a
                                href={getItemHref(item)}
                                data-fileid={item.id}
                                class="list icon img-change-to-white text-ellipsis accent-all box-shadow-1-all"
                                style="display: inline-block; width: 100%; max-width: 100%; text-align: left; margin-bottom: -10px; font-size: 0.8em"
                                title={item.name}
                            >
                                {#if isImageFile(item)}
                                    <img
                                        src="/api/file/{item.id}"
                                        alt=""
                                        style="float: left; width: 16px; height: 16px; object-fit: cover; margin-right: 5px; margin-top: 3px"
                                    />
                                {:else}
                                    <ItemIcon weight="light" size={14} style="float:left;margin-right:5px;margin-top:3px" />
                                {/if}
                                <span style="display: block; font-size: 1.2em" class="text-ellipsis">{item.name}</span>
                            </a>
                        {/each}
                    {/if}
                </div>
            </div>

            <!-- Next diary events -->
            <div class="col-md-6">
                <p class="mobile-md"></p>
                <h2>
                    {t('next-events')}
                    <a
                        href="/my/app/diary"
                        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                        style="float: right"
                    >{t('go-to')}</a>
                </h2>
                <div style="clear: both"></div>
                {#if diaryEvents.length === 0}
                    <p style="color: gray">{t('no-next-events')}</p>
                {:else}
                    {#each diaryEvents as event (event.id)}
                        <a
                            href="/my/app/reader/diary/{event.id}"
                            data-fileid={event.id}
                            class="list icon img-change-to-white text-ellipsis accent-all box-shadow-1-all"
                            style="display: inline-block; width: 100%; max-width: 100%; text-align: left; margin-bottom: -10px"
                            title={diaryLabel(event)}
                        >
                            {#if event.diary_priority >= 1}
                                <span title={t('priority-high')} style="margin-right:5px;color:red;display:inline-flex"><ArrowUpIcon weight="light" size={16} /></span>
                            {/if}
                            <span>{diaryLabel(event)}</span>
                        </a>
                    {/each}
                {/if}
            </div>

            <!-- Tomorrow's timetable -->
            <div class="col-md-12">
                <p class="mobile-md"></p>
                <h2>
                    {t('timetable-of')} {tomorrowDayName}
                    <a
                        href="/my/app/timetable"
                        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                    >{t('go-to')}</a>
                </h2>
                <div id="dashboard-timetable">
                    {#if timetableLoading}
                        <div class="ph-item">
                            <div class="ph-col-12">
                                <div class="ph-row">
                                    <div class="ph-col-6 big"></div>
                                    <div class="ph-col-4 empty big"></div>
                                    <div class="ph-col-4"></div>
                                    <div class="ph-col-8 empty"></div>
                                    <div class="ph-col-6"></div>
                                    <div class="ph-col-6 empty"></div>
                                    <div class="ph-col-12" style="margin-bottom: 0"></div>
                                </div>
                            </div>
                        </div>
                    {:else if timetableEmpty}
                        <p style="color: gray">{t('no-subject-tomorrow', 'No subjects tomorrow.')}</p>
                    {:else}
                        {#each timetableItems as item}
                            <a
                                href="/my/app/timetable"
                                class="icon img-change-to-white accent-all box-shadow-1-all"
                                style="display: inline-block"
                                title={item.subject + (item.book ? ': ' + item.book : '')}
                            >
                                <span
                                    class="filename text-ellipsis"
                                    style="display: block; font-size: 1.2em{item.fore ? '; color: #' + item.fore : ''}"
                                >{item.subject ?? ''}</span>
                                <span
                                    class="book text-ellipsis"
                                    style="display: inline-block; max-width: 100%"
                                >{item.book || '\u00a0'}</span>
                            </a>
                        {/each}
                    {/if}
                </div>
            </div>

        </div>
    </div>
</div>

<style lang="scss">
    .dashboard-inner {
        margin: 0 auto;
        width: 100%;
        max-width: 1100px;
        text-align: center;
    }
</style>

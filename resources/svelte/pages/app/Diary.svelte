<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { onMount } from "svelte";
  import { ArrowLeftIcon, ArrowRightIcon } from 'phosphor-svelte';
  import { apiFetch } from "../../lib/api";
  import { t } from "../../lib/i18n";
  import { notifications } from "../../stores/notifications.svelte";
  import Modal from "../../components/ui/Modal.svelte";
  import ActionButton from "../../components/ui/ActionButton.svelte";
  import DeleteModal from "../../components/modals/DeleteModal.svelte";
  import ShareModal from "../../components/modals/ShareModal.svelte";
  import ProjectModal from "../../components/modals/ProjectModal.svelte";
  import PropertyPanel from "../../components/ui/PropertyPanel.svelte";
  import Cookies from "js-cookie";

  // ── Types ─────────────────────────────────────────────────────────────────────

  interface DiaryEvent {
    id: string;
    name: string;
    diary_type: string;
    diary_date: string;
    diary_color?: string;
    diary_reminder?: string;
    diary_priority?: number | string;
    fav?: number | string;
  }

  interface Subject {
    subject: string;
    fore: string;
    book?: string;
  }

  interface CalendarCell {
    date: string | null; // 'YYYY-MM-DD' or null for empty padding
    day: number | null;
    isToday: boolean;
  }

  // ── Calendar helpers ──────────────────────────────────────────────────────────

  function buildCalendarGrid(
    y: number,
    m: number,
    startMonday: boolean,
  ): CalendarCell[][] {
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    const daysInMonth = new Date(y, m, 0).getDate();
    const firstDow = new Date(y, m - 1, 1).getDay(); // 0=Sun…6=Sat
    const offset = startMonday ? (firstDow + 6) % 7 : firstDow;

    const weeks: CalendarCell[][] = [];
    let week: CalendarCell[] = Array.from({ length: offset }, () => ({
      date: null,
      day: null,
      isToday: false,
    }));

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${y}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
      week.push({ date: dateStr, day: d, isToday: dateStr === todayStr });
      if (week.length === 7) {
        weeks.push(week);
        week = [];
      }
    }
    if (week.length > 0) {
      while (week.length < 7)
        week.push({ date: null, day: null, isToday: false });
      weeks.push(week);
    }
    return weeks;
  }

  function getDayHeaders(startMonday: boolean): string[] {
    const weekDays = t("week-days") as unknown as string[] | null;
    // weekDays[1]=Mon … weekDays[7]=Sun (1-indexed)
    const indices = startMonday ? [1, 2, 3, 4, 5, 6, 7] : [7, 1, 2, 3, 4, 5, 6];
    const fallbacks = startMonday
      ? ["Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"]
      : ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"];
    return indices.map(
      (i, idx) =>
        (Array.isArray(weekDays) ? weekDays[i] : null) ?? fallbacks[idx],
    );
  }

  // ── Init ──────────────────────────────────────────────────────────────────────

  const urlParams = new URLSearchParams(window.location.search);
  const now = new Date();

  // ── State ─────────────────────────────────────────────────────────────────────

  let year = $state(parseInt(urlParams.get("year") ?? "") || now.getFullYear());
  let month = $state(
    parseInt(urlParams.get("month") ?? "") || now.getMonth() + 1,
  );

  let events = $state<Map<string, DiaryEvent[]>>(new Map());
  let loadingEvents = $state(true);
  let subjects = $state<Subject[]>([]);

  // Day-events modal
  let showDayModal = $state(false);
  let selectedDay = $state<string | null>(null);
  let dayModalTitle = $state("");

  // New-event modal
  let showNewEvent = $state(false);
  let newType = $state("");
  let newSubject = $state("");
  let newColor = $state("#000000");
  let newDate = $state("");
  let newReminder = $state("");
  let newPriority = $state("0");
  let newContent = $state("");
  let newError = $state("");
  let newLoading = $state(false);
  let subjectFilter = $state("");

  // Edit-event modal
  let showEditEvent = $state(false);
  let editId        = $state("");
  let editType      = $state("");
  let editSubject   = $state("");
  let editColor     = $state("#000000");
  let editDate      = $state("");
  let editReminder  = $state("");
  let editPriority  = $state("0");
  let editContent   = $state("");
  let editError     = $state("");
  let editLoading   = $state(false);
  let editSubjectFilter = $state("");

  // ── Derived ───────────────────────────────────────────────────────────────────

  const weekStartsMonday = $derived.by(() => {
    const v = t("weekStarts") as unknown;
    return typeof v === "number" ? v === 0 : true;
  });

  const grid = $derived(buildCalendarGrid(year, month, weekStartsMonday));

  const dayHeaders = $derived(getDayHeaders(weekStartsMonday));

  const monthLabel = $derived.by(() => {
    const locale =
      (Cookies.get("language") ?? "it").replace("_", "-");
    const name = new Intl.DateTimeFormat(locale, { month: "long" }).format(
      new Date(year, month - 1, 1),
    );
    return name.charAt(0).toUpperCase() + name.slice(1) + " " + year;
  });

  const selectedDayEvents = $derived(
    selectedDay ? (events.get(selectedDay) ?? []) : [],
  );

  const filteredSubjects = $derived(
    subjectFilter.trim()
      ? subjects.filter((s) =>
          s.subject.toLowerCase().includes(subjectFilter.toLowerCase()),
        )
      : [],
  );

  // ── Navigation ────────────────────────────────────────────────────────────────

  function goBack(): void {
    if (month === 1) {
      year--;
      month = 12;
    } else {
      month--;
    }
    history.pushState(
      { id: `diary-${year}/${month}` },
      "",
      `/my/app/diary?year=${year}&month=${month}`,
    );
  }

  function goNext(): void {
    if (month === 12) {
      year++;
      month = 1;
    } else {
      month++;
    }
    history.pushState(
      { id: `diary-${year}/${month}` },
      "",
      `/my/app/diary?year=${year}&month=${month}`,
    );
  }

  // ── API ───────────────────────────────────────────────────────────────────────

  async function loadEvents(): Promise<void> {
    loadingEvents = true;
    try {
      const data = await apiFetch(
        `/api/diary?type=events&year=${year}&month=${month}`,
      );
      const map = new Map<string, DiaryEvent[]>();
      for (const ev of (data.events ?? []) as DiaryEvent[]) {
        if (!map.has(ev.diary_date)) map.set(ev.diary_date, []);
        map.get(ev.diary_date)!.push(ev);
      }
      events = map;
    } catch {
      /* silent */
    } finally {
      loadingEvents = false;
    }
  }

  async function loadSubjects(): Promise<void> {
    try {
      const data = await apiFetch("/api/timetable?type=get-subjects");
      subjects = (Array.isArray(data) ? data : []) as Subject[];
    } catch {
      /* silent */
    }
  }

  async function submitNewEvent(): Promise<void> {
    newLoading = true;
    newError = "";
    const body = new URLSearchParams({
      type: newType,
      subject: newSubject,
      color: newColor.replace("#", ""),
      date: newDate,
      reminder: newReminder,
      priority: newPriority,
      content: newContent,
    }).toString();
    try {
      const res = await apiFetch("/api/diary?type=create", "POST", body);
      if (res.response === "success") {
        showNewEvent = false;
        newType = "";
        newSubject = "";
        newColor = "#000000";
        newDate = "";
        newReminder = "";
        newPriority = "0";
        newContent = "";
        await loadEvents();
        notifications.add(res.text as string, { type: "success" });
      } else {
        newError = res.text as string;
      }
    } catch {
      newError = t("error", "Errore");
    } finally {
      newLoading = false;
    }
  }

  async function submitEditEvent(): Promise<void> {
    editLoading = true;
    editError = "";
    const body = new URLSearchParams({
      id:       editId,
      type:     editType,
      subject:  editSubject,
      color:    editColor.replace("#", ""),
      date:     editDate,
      reminder: editReminder,
      priority: editPriority,
      content:  editContent,
    }).toString();
    try {
      const res = await apiFetch("/api/diary?type=edit", "POST", body);
      if (res.response === "success") {
        showEditEvent = false;
        await loadEvents();
        notifications.add(res.text as string, { type: "success" });
      } else {
        editError = res.text as string;
      }
    } catch {
      editError = t("error", "Errore");
    } finally {
      editLoading = false;
    }
  }

  // ── Helpers ───────────────────────────────────────────────────────────────────

  function openDay(date: string): void {
    selectedDay = date;
    dayModalTitle = new Date(date + "T00:00:00").toLocaleDateString("it-IT", {
      day: "2-digit",
      month: "long",
      year: "numeric",
    });
    showDayModal = true;
  }

  function pickSubject(s: Subject): void {
    newSubject = s.subject;
    newColor = "#" + (s.fore || "000000");
    subjectFilter = "";
  }

  // ── Lifecycle ─────────────────────────────────────────────────────────────────

  // Reload events whenever year or month changes (runs on mount too)
  $effect(() => {
    void loadEvents();
  });

  onMount(() => {
    history.replaceState(
      { id: `diary-${year}/${month}` },
      "",
      `/my/app/diary?year=${year}&month=${month}`,
    );
    loadSubjects();

    const onPopState = (e: PopStateEvent): void => {
      if (String(e.state?.id ?? "").startsWith("diary-")) {
        const parts = String(e.state.id)
          .replace("diary-", "")
          .split("/")
          .map(Number);
        year = parts[0];
        month = parts[1];
        // $effect automatically reloads events
      }
    };
    window.addEventListener("popstate", onPopState);

    function removeEventFromMap(id: string): void {
      events = new Map(
        ([...events] as [string, DiaryEvent[]][])
          .map(([date, evs]) => [date, evs.filter(ev => ev.id !== id)] as [string, DiaryEvent[]])
          .filter(([, evs]) => evs.length > 0),
      );
    }

    const onFavChanged = (e: Event): void => {
      const { fileId, removeFromView } = (e as CustomEvent).detail as { fileId: string; newFav: 0 | 1; removeFromView: boolean };
      if (removeFromView) removeEventFromMap(fileId);
    };
    window.addEventListener("cm-fav-changed", onFavChanged);

    const onFileRemoved = (e: Event): void => {
      const { fileId } = (e as CustomEvent).detail as { fileId: string };
      removeEventFromMap(fileId);
    };
    window.addEventListener("cm-file-removed", onFileRemoved);

    const onDiaryEdit = (e: Event): void => {
      const d = (e as CustomEvent).detail as {
        fileId: string; diaryType: string; diarySubject: string;
        diaryColor: string; diaryDate: string; diaryReminder: string; diaryPriority: string;
      };
      editId       = d.fileId;
      editType     = d.diaryType;
      editSubject  = d.diarySubject;
      editColor    = d.diaryColor ? "#" + d.diaryColor : "#000000";
      editDate     = d.diaryDate;
      editReminder = d.diaryReminder;
      editPriority = d.diaryPriority;
      editContent  = "";
      editError    = "";
      editSubjectFilter = "";
      showEditEvent = true;
      // Load encrypted content in background
      apiFetch(`/api/diary?type=details&id=${d.fileId}`).then(res => {
        if (res.response === "success") {
          editContent = (res.event as Record<string, unknown>)?.content as string ?? "";
        }
      }).catch(() => {/* silent */});
    };
    window.addEventListener("cm-diary-edit", onDiaryEdit);

    return () => {
      window.removeEventListener("popstate", onPopState);
      window.removeEventListener("cm-fav-changed", onFavChanged);
      window.removeEventListener("cm-file-removed", onFileRemoved);
      window.removeEventListener("cm-diary-edit", onDiaryEdit);
    };
  });
</script>

<svelte:head><title>{t("app-diary")} - LightSchool</title></svelte:head>

<DeleteModal />
<ShareModal />
<ProjectModal />
<PropertyPanel />

<div class="container content-my">
  <!-- Floating action button -->
  <ActionButton
    title={t("new-diary-event", "Nuovo evento diario")}
    onclick={() => {
      showNewEvent = true;
    }}
  />

  <div id="diary">
    <!-- Month navigation -->
    <h3 class="month">
      <button
        type="button"
        class="nav-btn back"
        onclick={goBack}
        aria-label="Mese precedente"
      >
        <ArrowLeftIcon weight="light" size={32} />
      </button>
      {monthLabel}
      <button
        type="button"
        class="nav-btn next"
        onclick={goNext}
        aria-label="Mese successivo"
      >
        <ArrowRightIcon weight="light" size={32} />
      </button>
    </h3>

    {#if loadingEvents}
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
    {:else}
      <table>
        <thead>
          <tr>
            {#each dayHeaders as header}
              <td class="header">
                <span class="pc-md">{header}</span>
                <span class="mobile-md">{header.substring(0, 3)}</span>
              </td>
            {/each}
          </tr>
        </thead>
        <tbody>
          {#each grid as week}
            <tr>
              {#each week as cell}
                {#if cell.date === null}
                  <td class="empty"></td>
                {:else}
                  <!-- svelte-ignore a11y_click_events_have_key_events a11y_no_noninteractive_element_interactions -->
                  <td
                    class="day accent-all box-shadow-1-all white-text-hover"
                    class:selected={cell.isToday}
                    data-day={cell.date}
                    onclick={() => openDay(cell.date!)}
                  >
                    {cell.day}
                    {#if events.has(cell.date)}
                      <br class="mobile-md" />
                      <span class="mobile-md accent-fore">&#8226;</span>
                      {#each events.get(cell.date)! as ev}
                        <a
                          href="/my/app/reader/diary/{ev.id}"
                          class="event pc-md"
                          style:color={ev.diary_color
                            ? "#" + ev.diary_color
                            : undefined}
                          data-fra-context-menu="file"
                          data-fileid={ev.id}
                          data-file-fav={String(ev.fav ?? 0)}
                          data-file-type="diary"
                          data-type={ev.diary_type ?? ""}
                          data-subject={ev.name ?? ""}
                          data-fore={ev.diary_color ?? ""}
                          data-date={ev.diary_date ?? ""}
                          data-reminder={ev.diary_reminder ?? ""}
                          data-priority={String(ev.diary_priority ?? 0)}
                          data-content=""
                          onclick={(e) => e.stopPropagation()}
                          ><br /><span class="filename"
                            >{ev.diary_type} di {ev.name}</span
                          ></a
                        >
                      {/each}
                    {/if}
                  </td>
                {/if}
              {/each}
            </tr>
          {/each}
        </tbody>
      </table>
    {/if}
  </div>
</div>

<!-- Day events modal -->
<Modal
  open={showDayModal}
  title={dayModalTitle}
  maxWidth="522px"
  draggable
  onclose={() => {
    showDayModal = false;
  }}
>
  {#if selectedDayEvents.length === 0}
    <p style="color: gray">
      {t("no-events-today", "Nessun evento in questa giornata.")}
    </p>
  {:else}
    {#each selectedDayEvents as ev}
      <a
        href="/my/app/reader/diary/{ev.id}"
        class="icon event"
        style="max-width: 100%; display: block"
        style:color={ev.diary_color ? "#" + ev.diary_color : undefined}
        data-fra-context-menu="file"
        data-fileid={ev.id}
        data-file-fav={String(ev.fav ?? 0)}
        data-file-type="diary"
        data-type={ev.diary_type ?? ""}
        data-subject={ev.name ?? ""}
        data-fore={ev.diary_color ?? ""}
        data-date={ev.diary_date ?? ""}
        data-reminder={ev.diary_reminder ?? ""}
        data-priority={String(ev.diary_priority ?? 0)}
        data-content=""
        ><span class="filename">{ev.diary_type} di {ev.name}</span></a
      >
      <br />
    {/each}
  {/if}
</Modal>

<!-- New event modal -->
<Modal
  open={showNewEvent}
  title={t("new-diary-event", "Nuovo evento diario")}
  maxWidth="522px"
  draggable
  onclose={() => {
    showNewEvent = false;
  }}
>
  <div class="form-new-event">
    <div class="form-grid">
      <div class="field">
        <label for="new-type">{t("type", "Tipo")}</label>
        <input
          type="text"
          id="new-type"
          placeholder={t("type", "Tipo")}
          bind:value={newType}
          class="box-shadow-1-all form-input"
        />
      </div>
      <div class="field">
        <label for="new-subject">{t("subject", "Materia")}</label>
        <div class="color-picker-root">
          <input
            type="text"
            id="diary-new-color"
            data-fra-color-picker="1"
            bind:value={newColor}
            class="color-swatch box-shadow-1-all"
            aria-label="Colore materia"
            style:color={newColor}
            style:background-color={newColor}
          />
          <input
            type="text"
            id="new-subject"
            placeholder={t("subject", "Materia")}
            bind:value={newSubject}
            oninput={() => {
              subjectFilter = newSubject;
            }}
            class="box-shadow-1-all form-input"
          />
        </div>
      </div>
    </div>

    {#if filteredSubjects.length > 0}
      <div class="subjectsList">
        {#each filteredSubjects as s}
          <!-- svelte-ignore a11y_invalid_attribute -->
          <a
            href="#"
            class="accent-all box-shadow-1-all"
            style="color: {s.fore ? '#' + s.fore : 'inherit'}"
            onclick={(e) => {
              e.preventDefault();
              pickSubject(s);
            }}
          >
            <span class="print text-ellipsis">{s.subject}</span>
          </a>
        {/each}
      </div>
    {/if}

    <div class="form-grid">
      <div class="field">
        <label for="new-date">{t("date", "Data")}</label>
        <input
          type="date"
          id="new-date"
          bind:value={newDate}
          class="box-shadow-1-all form-input"
        />
      </div>
      <div class="field">
        <label for="new-reminder">{t("reminder", "Promemoria")}</label>
        <input
          type="date"
          id="new-reminder"
          bind:value={newReminder}
          class="box-shadow-1-all form-input"
        />
      </div>
    </div>

    <div class="priority-row">
      <b>{t("priority", "Priorità")}</b>
      <label>
        <input type="radio" name="new-priority" value="-1" bind:group={newPriority} />
        {t("priority-low", "Bassa")}
      </label>
      <label>
        <input type="radio" name="new-priority" value="0" bind:group={newPriority} />
        {t("priority-normal", "Normale")}
      </label>
      <label>
        <input type="radio" name="new-priority" value="1" bind:group={newPriority} />
        {t("priority-high", "Alta")}
      </label>
    </div>

    <textarea
      placeholder={t("content", "Contenuto")}
      bind:value={newContent}
      class="box-shadow-1-all"
    ></textarea>

    {#if newError}
      <div class="form-error">{newError}</div>
    {/if}

    <div class="form-actions">
      <button
        type="button"
        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        disabled={newLoading}
        onclick={submitNewEvent}
      >{t("create", "Crea")}</button>
    </div>
  </div>
</Modal>

<!-- Edit event modal -->
<Modal
  open={showEditEvent}
  title={t("edit-diary-event", "Modifica evento diario")}
  maxWidth="522px"
  draggable
  onclose={() => { showEditEvent = false; }}
>
  <div class="form-new-event">
    <div class="form-grid">
      <div class="field">
        <label for="edit-type">{t("type", "Tipo")}</label>
        <input
          type="text"
          id="edit-type"
          placeholder={t("type", "Tipo")}
          bind:value={editType}
          class="box-shadow-1-all form-input"
        />
      </div>
      <div class="field">
        <label for="edit-subject">{t("subject", "Materia")}</label>
        <div class="color-picker-root">
          <input
            type="text"
            id="diary-edit-color"
            data-fra-color-picker="1"
            bind:value={editColor}
            class="color-swatch box-shadow-1-all"
            aria-label="Colore materia"
            style:color={editColor}
            style:background-color={editColor}
          />
          <input
            type="text"
            id="edit-subject"
            placeholder={t("subject", "Materia")}
            bind:value={editSubject}
            oninput={() => { editSubjectFilter = editSubject; }}
            class="box-shadow-1-all form-input"
          />
        </div>
      </div>
    </div>

    {#if editSubjectFilter.trim() && subjects.filter(s => s.subject.toLowerCase().includes(editSubjectFilter.toLowerCase())).length > 0}
      <div class="subjectsList">
        {#each subjects.filter(s => s.subject.toLowerCase().includes(editSubjectFilter.toLowerCase())) as s}
          <!-- svelte-ignore a11y_invalid_attribute -->
          <a
            href="#"
            class="accent-all box-shadow-1-all"
            style="color: {s.fore ? '#' + s.fore : 'inherit'}"
            onclick={(e) => { e.preventDefault(); editSubject = s.subject; editColor = "#" + (s.fore || "000000"); editSubjectFilter = ""; }}
          >
            <span class="print text-ellipsis">{s.subject}</span>
          </a>
        {/each}
      </div>
    {/if}

    <div class="form-grid">
      <div class="field">
        <label for="edit-date">{t("date", "Data")}</label>
        <input
          type="date"
          id="edit-date"
          bind:value={editDate}
          class="box-shadow-1-all form-input"
        />
      </div>
      <div class="field">
        <label for="edit-reminder">{t("reminder", "Promemoria")}</label>
        <input
          type="date"
          id="edit-reminder"
          bind:value={editReminder}
          class="box-shadow-1-all form-input"
        />
      </div>
    </div>

    <div class="priority-row">
      <b>{t("priority", "Priorità")}</b>
      <label>
        <input type="radio" name="edit-priority" value="-1" bind:group={editPriority} />
        {t("priority-low", "Bassa")}
      </label>
      <label>
        <input type="radio" name="edit-priority" value="0" bind:group={editPriority} />
        {t("priority-normal", "Normale")}
      </label>
      <label>
        <input type="radio" name="edit-priority" value="1" bind:group={editPriority} />
        {t("priority-high", "Alta")}
      </label>
    </div>

    <textarea
      placeholder={t("content", "Contenuto")}
      bind:value={editContent}
      class="box-shadow-1-all"
    ></textarea>

    {#if editError}
      <div class="form-error">{editError}</div>
    {/if}

    <div class="form-actions">
      <button
        type="button"
        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        disabled={editLoading}
        onclick={submitEditEvent}
      >{t("save", "Salva")}</button>
    </div>
  </div>
</Modal>

<style lang="scss">
  // Calendar is now fully Svelte-driven — no :global() needed.

  #diary {
    .month {
      text-align: center;
      text-transform: capitalize;
    }

    table {
      width: 100%;
      margin: 0 auto;

      td {
        padding: 10px;
        text-align: center;
        width: 200px;
        height: 100px;
        vertical-align: top;

        @media (max-width: 576px) {
          height: 70px;
          vertical-align: middle;
        }

        &.header {
          height: auto;
          font-weight: bold;
          text-transform: capitalize;
        }

        &.day {
          cursor: pointer;

          &.selected {
            background-color: rgba(35, 126, 236, 0.8);
            color: white;

            * {
              color: white;
            }
          }

          &:hover,
          &:focus {
            background-image: linear-gradient(
              to right,
              rgba(30, 107, 201, 0.8),
              rgba(35, 126, 236, 0.8)
            );
            box-shadow: 0 0 37px -8px #1e6bc9;
            color: white;

            * {
              color: white;
            }
          }
        }
      }
    }
  }

  .nav-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: inherit;

    &.back {
      float: left;
    }
    &.next {
      float: right;
    }
  }

  .form-input {
    width: 100%;
    box-sizing: border-box;
  }

  .form-new-event {
    display: grid;
    gap: 1rem;

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;

      @media (max-width: 36rem) {
        grid-template-columns: 1fr;
      }
    }

    .field {
      display: grid;
      gap: 0.25rem;
    }

    .subjectsList {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;

      > a {
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.5rem;
        display: block;
      }
    }

    .priority-row {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }

    textarea {
      width: 100%;
      box-sizing: border-box;
      min-height: 5rem;
    }

    .form-error {
      padding: 0.75rem;
      border-radius: 0.25rem;
      background-color: rgba(220, 53, 69, 0.15);
      color: #dc3545;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
    }
  }
</style>

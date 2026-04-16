<script module>
  import AppLayout from "../../layouts/AppLayout.svelte";
  export const layout = AppLayout;
</script>

<script lang="ts">
  import { t } from "../../lib/i18n";
  import { apiFetch } from "../../lib/api";
  import { notifications } from "../../stores/notifications.svelte";
  import Modal from "../../components/ui/Modal.svelte";
  import ActionButton from "../../components/ui/ActionButton.svelte";

  interface TimetableItem {
    id: string;
    day: number;
    slot: number;
    subject: string;
    book: string;
    fore?: string;
  }

  interface SubjectItem {
    subject: string;
    book: string;
    fore: string;
  }

  type DayGroup = { day: number; label: string; items: TimetableItem[] };

  const DAYS = ["", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"];

  let groups = $state<DayGroup[]>([]);
  let subjects = $state<SubjectItem[]>([]);
  let loading = $state(true);
  let submitting = $state(false);

  // Modal state
  let modalOpen = $state(false);
  let modalTitle = $state("Nuova materia");
  let editId = $state<string | null>(null);
  let formDay = $state("");
  let formSlot = $state("");
  let formSubject = $state("");
  let formBook = $state("");
  let formColor = $state("");
  let formError = $state("");
  let subjectFilter = $state("");

  const filteredSubjects = $derived(
    subjectFilter.trim() === ""
      ? []
      : subjects.filter((s) =>
          s.subject.toLowerCase().includes(subjectFilter.toLowerCase()),
        ),
  );

  const today = new Date().getDay(); // 0=Sun, 1=Mon, …

  async function loadTimetable(): Promise<void> {
    loading = true;
    try {
      const data = await apiFetch("/api/timetable?type=get");
      const items = (Array.isArray(data) ? data : []) as TimetableItem[];
      const map = new Map<number, TimetableItem[]>();
      for (const item of items) {
        const d = Number(item.day);
        if (!map.has(d)) map.set(d, []);
        map.get(d)!.push(item);
      }
      const weekDays = t("week-days") as unknown as string[] | null;
      groups = [...map.entries()].map(([day, items]) => ({
        day,
        label:
          (Array.isArray(weekDays) ? weekDays[day] : null) ??
          DAYS[day] ??
          String(day),
        items,
      }));
    } catch {
      /* ignore */
    } finally {
      loading = false;
    }
  }

  async function loadSubjects(): Promise<void> {
    try {
      const data = await apiFetch("/api/timetable?type=get-subjects");
      subjects = (Array.isArray(data) ? data : []) as SubjectItem[];
    } catch {
      /* ignore */
    }
  }

  function toColorState(hex: string | undefined): string {
    if (!hex) return "";
    return hex.startsWith("#") ? hex : "#" + hex;
  }

  function fromColorState(hex: string): string {
    return hex.startsWith("#") ? hex.slice(1) : hex;
  }

  function openNew(): void {
    editId = null;
    modalTitle = "Nuova materia";
    formDay = "";
    formSlot = "";
    formSubject = "";
    formBook = "";
    formColor = "";
    formError = "";
    subjectFilter = "";
    modalOpen = true;
  }

  function openEdit(item: TimetableItem): void {
    editId = item.id;
    modalTitle = item.subject;
    formDay = String(item.day);
    formSlot = String(item.slot);
    formSubject = item.subject;
    formBook = item.book;
    formColor = toColorState(item.fore);
    formError = "";
    subjectFilter = "";
    modalOpen = true;
  }

  async function handleSubmit(
    action: "create" | "edit" | "remove",
  ): Promise<void> {
    if (submitting) return;
    submitting = true;
    formError = "";
    const params = new URLSearchParams({
      day: formDay,
      slot: formSlot,
      subject: formSubject,
      book: formBook,
      color: fromColorState(formColor),
    });
    if (editId) params.set("id", editId);
    try {
      const res = await apiFetch(
        `/api/timetable?type=${action}`,
        "POST",
        params.toString(),
      );
      if (res.response === "success") {
        modalOpen = false;
        notifications.add(res.text, { type: "success" });
        await loadTimetable();
      } else {
        formError = res.text;
      }
    } catch {
      formError = t("error", "Error");
    } finally {
      submitting = false;
    }
  }

  function pickSubject(s: SubjectItem): void {
    formSubject = s.subject;
    formBook = s.book;
    formColor = toColorState(s.fore);
    subjectFilter = "";
  }

  $effect(() => {
    loadTimetable();
    loadSubjects();
  });
</script>

<svelte:head><title>Orario - LightSchool</title></svelte:head>

<div class="container content-my timetable" id="timetable">
  <!-- FAB -->
  <ActionButton onclick={openNew} />

  <div class="folder-view">
    {#if loading}
      <div class="loading ph-item">
        <div class="ph-col-12">
          <div class="ph-row">
            <div class="ph-col-6 big"></div>
            <div class="ph-col-4 empty big"></div>
            <div class="ph-col-4"></div>
            <div class="ph-col-8 empty"></div>
            <div class="ph-col-6"></div>
            <div class="ph-col-6 empty"></div>
            <div class="ph-col-12" style="margin-bottom:0"></div>
          </div>
        </div>
      </div>
    {:else if groups.length === 0}
      <p style="color: gray">Nessun orario salvato. Creane uno!</p>
    {:else}
      <div class="items">
        {#each groups as group (group.day)}
          <span
            class="dayRow{group.day === today ? ' selected' : ''}"
            style="display: inline-block; width: 100%; max-width: 150px; font-weight: bold; font-size: 1.5em"
            >{group.label}</span
          >
          {#each group.items as item (item.id)}
            <!-- svelte-ignore a11y_invalid_attribute -->
            <a
              href="#"
              class="icon img-change-to-white accent-all box-shadow-1-all"
              style="display: inline-block"
              title={item.subject + (item.book ? ": " + item.book : "")}
              onclick={(e) => {
                e.preventDefault();
                openEdit(item);
              }}
            >
              <span
                class="filename text-ellipsis"
                style="display: block; font-size: 1.2em{item.fore
                  ? '; color: #' + item.fore
                  : ''}">{item.subject}</span
              >
              <span
                class="book text-ellipsis"
                style="display: inline-block; max-width: 100%"
              >
                {item.book || "\u00a0"}
              </span>
            </a>
          {/each}
          <br /><br />
        {/each}
      </div>
    {/if}
  </div>
</div>

<!-- New / Edit modal -->
<Modal
  open={modalOpen}
  title={modalTitle}
  maxWidth="522px"
  draggable
  onclose={() => {
    modalOpen = false;
  }}
>
  <div class="form-new-subject" style="text-align: center">
    <select id="day" bind:value={formDay}>
      <option value="" disabled selected>Giorno</option>
      <option value="1">Lunedì</option>
      <option value="2">Martedì</option>
      <option value="3">Mercoledì</option>
      <option value="4">Giovedì</option>
      <option value="5">Venerdì</option>
      <option value="6">Sabato</option>
      <option value="7">Domenica</option>
    </select>
    <input
      type="number"
      id="slot"
      placeholder="Slot"
      min="0"
      max="255"
      bind:value={formSlot}
    />
    <br />

    <div class="color-picker-root">
      <input
        type="text"
        id="timetable-color"
        title="Colore"
        bind:value={formColor}
        data-fra-color-picker="1"
        maxlength={7}
        style:color={formColor}
        style:background-color={formColor}
      />
      <input
        type="text"
        placeholder="Materia"
        bind:value={formSubject}
        oninput={() => {
          subjectFilter = formSubject;
        }}
      />
    </div>
    <input type="text" placeholder="Libro" bind:value={formBook} />
    <br />

    {#if filteredSubjects.length > 0}
      <div class="subjectsList" style="text-align: left; margin-bottom: 8px">
        {#each filteredSubjects as s (s.subject)}
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
            <span class="text-ellipsis">{s.subject}</span>
          </a>
        {/each}
      </div>
    {/if}

    {#if formError}
      <div class="response alert alert-danger" style="margin-bottom: 8px">
        {formError}
      </div>
    {/if}

    <div
      style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px"
    >
      {#if editId}
        <input
          type="submit"
          value="Elimina"
          disabled={submitting}
          class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
          style="float: left"
          onclick={() => handleSubmit("remove")}
        />
      {/if}
      <input
        type="submit"
        value={editId ? "Modifica" : "Crea"}
        disabled={submitting}
        class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
        style="float: right"
        onclick={() => handleSubmit(editId ? "edit" : "create")}
      />
    </div>
  </div>
</Modal>

<style lang="scss">
  .dayRow {
    padding: 15px;
    margin-right: 10px;
    float: left;
    border-radius: 10px;

    &.selected {
      background-image: linear-gradient(
        to right,
        rgba(30, 107, 201, 0.8),
        rgba(35, 126, 236, 0.8)
      );
      box-shadow: 0 0 37px -8px #1e6bc9;
    }
  }

  .form-new-subject {
    input[type="text"],
    input[type="number"],
    select {
      width: calc(50% - 20px);

      @media (max-width: 768px) {
        width: 100%;
      }
    }
  }

  .subjectsList {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 1rem;

    > a {
      text-decoration: none;
      padding: 0.5rem;
      border-radius: 0.5rem;
    }
  }
</style>

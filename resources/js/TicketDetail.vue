<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { fetchTicket, updateTicket } from './api';
import { formatWhen, statusLabel, statusLabels, typeLabels } from './labels';
import { demoBrand } from './demoBrand';
import DeskSelect from './DeskSelect.vue';
import {
    alertError,
    btnPrimary,
    field,
    fieldLabel,
    linkInline,
    scrollToPageTop,
    textareaControl,
} from './ui';

const changeStatusHeadingId = 'change-status-heading';

const props = defineProps({
    ticketId: {
        type: [String, Number],
        required: true,
    },
});

const loading = ref(true);
const error = ref('');
const ticket = ref(null);
const detailStatus = ref('');
const statusNote = ref('');
const saving = ref(false);

const statusChanged = computed(
    () => ticket.value && detailStatus.value !== ticket.value.status,
);

const statusOptions = computed(() =>
    Object.entries(statusLabels).map(([value, label]) => ({ value, label })),
);

const canSaveStatus = computed(
    () => statusChanged.value && statusNote.value.trim().length > 0 && !saving.value,
);

async function loadTicket() {
    loading.value = true;
    error.value = '';
    try {
        const full = await fetchTicket(props.ticketId);
        ticket.value = full;
        detailStatus.value = full.status;
        statusNote.value = '';
    } catch (e) {
        error.value = e?.response?.data?.message ?? e?.message ?? 'Could not load this ticket. Check the link or return to the queue.';
        ticket.value = null;
    } finally {
        loading.value = false;
    }
}

async function applyStatusChange() {
    if (!canSaveStatus.value) {
        return;
    }
    saving.value = true;
    error.value = '';
    try {
        ticket.value = await updateTicket(ticket.value.id, {
            status: detailStatus.value,
            status_note: statusNote.value.trim(),
        });
        detailStatus.value = ticket.value.status;
        statusNote.value = '';
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Could not save the status change. Add a note and try again.';
    } finally {
        saving.value = false;
    }
}

watch(
    () => props.ticketId,
    () => loadTicket(),
);

onMounted(() => {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    scrollToPageTop();
    loadTicket();
});
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-slate-800 bg-slate-900/80 px-4 py-4 sm:px-6">
      <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3">
        <a href="/" :class="linkInline">← Back to queue</a>
        <p class="text-xs text-slate-500">{{ demoBrand.name }} · ticket</p>
      </div>
    </header>

    <main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6" :aria-busy="loading || saving">
      <p v-if="error" role="alert" :class="alertError">{{ error }}</p>
      <p v-if="loading" class="text-slate-400" aria-live="polite">Loading ticket…</p>

      <template v-else-if="ticket">
        <div>
          <p class="font-mono text-sm text-cyan-300">{{ ticket.reference }}</p>
          <h1 id="page-heading" class="text-xl font-semibold text-white">{{ ticket.subject }}</h1>
        </div>

        <dl class="grid gap-4 text-sm sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-slate-500">Account</dt>
            <dd class="break-words">{{ ticket.account?.name }} ({{ ticket.account?.tier }})</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-slate-500">Type</dt>
            <dd>{{ typeLabels[ticket.type] ?? ticket.type }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-slate-500">Priority</dt>
            <dd class="capitalize">{{ ticket.priority }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-slate-500">Current status</dt>
            <dd>{{ statusLabels[ticket.status] ?? ticket.status }}</dd>
          </div>
          <div class="space-y-1 sm:col-span-2">
            <dt class="text-slate-500">Service line</dt>
            <dd>{{ ticket.service_line?.label ?? '—' }}</dd>
          </div>
          <div class="space-y-1 sm:col-span-2">
            <dt class="text-slate-500">Description</dt>
            <dd class="whitespace-pre-wrap break-words text-slate-200">{{ ticket.description || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-slate-500">Opened</dt>
            <dd>{{ formatWhen(ticket.created_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-slate-500">Last updated</dt>
            <dd>{{ formatWhen(ticket.updated_at) }}</dd>
          </div>
        </dl>

        <section class="rounded-xl border border-slate-800 bg-slate-900 p-4 sm:p-5" aria-labelledby="change-status-heading">
          <h2 id="change-status-heading" class="text-sm font-medium text-slate-300">Change status</h2>
          <div class="mt-3 grid gap-4">
            <label :class="field">
              <span :class="fieldLabel">New status</span>
              <DeskSelect
                v-model="detailStatus"
                :options="statusOptions"
                aria-describedby="status-note-help"
              />
            </label>
            <label :class="field">
              <span :class="fieldLabel">Note for audit log</span>
              <textarea
                id="status-note"
                v-model="statusNote"
                rows="3"
                :class="textareaControl"
                placeholder="What changed and why (visible to the team)"
                :disabled="!statusChanged"
                :aria-describedby="'status-note-help'"
              />
            </label>
            <p id="status-note-help" class="text-xs text-slate-500">
              {{ statusChanged ? demoBrand.statusNoteHint : 'Pick a different status to enable the note field.' }}
            </p>
            <button
              type="button"
              :class="btnPrimary"
              :disabled="!canSaveStatus"
              @click="applyStatusChange"
            >
              {{ saving ? 'Saving…' : 'Update status' }}
            </button>
          </div>
        </section>

        <section aria-labelledby="history-heading">
          <h2 id="history-heading" class="text-sm font-medium text-slate-300">Status history</h2>
          <ol v-if="ticket.status_histories?.length" class="mt-3 space-y-2">
            <li
              v-for="entry in ticket.status_histories"
              :key="entry.id"
              class="rounded border border-slate-800 bg-slate-950/80 px-3 py-2 text-sm"
            >
              <p>
                <time class="text-slate-400">{{ formatWhen(entry.created_at) }}</time>
                <span class="text-white">
                  · {{ statusLabel(entry.from_status) }} → {{ statusLabel(entry.to_status) }}
                </span>
              </p>
              <p v-if="entry.note" class="mt-1 break-words text-slate-300">{{ entry.note }}</p>
            </li>
          </ol>
          <p v-else class="mt-2 text-sm text-slate-500">No status changes recorded yet.</p>
        </section>
      </template>
    </main>
  </div>
</template>

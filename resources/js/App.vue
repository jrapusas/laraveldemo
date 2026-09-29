<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import {
    createTicket,
    fetchAccounts,
    fetchDashboard,
    fetchTickets,
} from './api';
import BidsPanel from './BidsPanel.vue';
import DeskPagination from './DeskPagination.vue';
import DeskSelect from './DeskSelect.vue';
import DeskSortTh from './DeskSortTh.vue';
import ProvisioningPanel from './ProvisioningPanel.vue';
import { formatWhen, statusLabels, typeLabels } from './labels';
import { demoBrand, demoDeskTabs, demoStackLinks } from './demoBrand';
import {
    alertError,
    btnDeskTab,
    btnGhost,
    btnPrimary,
    control,
    field,
    fieldLabel,
    inputControlCompact,
    linkStack,
    linkRow,
    textareaControl,
    toolbar,
    onSectionFormInteract,
    scrollToPageTop,
    scrollToSectionHeading,
} from './ui';
import { useDeskTableSort } from './useDeskTableSort';

const queueHeadingId = 'queue-heading';
const newTicketHeadingId = 'new-ticket-heading';
const supportQueueTableId = 'support-queue-table';

const booting = ref(true);
const queueBusy = ref(false);
const error = ref('');
const summary = ref(null);
const activeTab = ref('support');
const tickets = ref([]);
const accounts = ref([]);
const filter = reactive({ status: '', type: '', q: '' });
const ticketPage = ref({ data: [], total: 0, current_page: 1, last_page: 1 });

const textAsc = {
    reference: 'asc',
    account: 'asc',
    type: 'asc',
    subject: 'asc',
    priority: 'asc',
    status: 'asc',
};
const { sort: queueSort, toggle: toggleQueueSort, sortParams: queueSortParams } = useDeskTableSort(
    'created_at',
    { created_at: 'desc', ...textAsc },
);

function onQueueSort(column) {
    toggleQueueSort(column);
    load(1, true);
}

const form = reactive({
    account_id: '',
    service_line_id: '',
    type: 'fault',
    subject: '',
    description: '',
    priority: 'normal',
});

const serviceLinesForAccount = computed(() => {
    const account = accounts.value.find((a) => String(a.id) === String(form.account_id));
    return account?.service_lines ?? [];
});

const statusFilterOptions = computed(() => [
    { value: '', label: 'All statuses' },
    ...Object.entries(statusLabels).map(([value, label]) => ({ value, label })),
]);

const typeFilterOptions = computed(() => [
    { value: '', label: 'All types' },
    ...Object.entries(typeLabels).map(([value, label]) => ({ value, label })),
]);

const accountOptions = computed(() =>
    accounts.value.map((a) => ({ value: a.id, label: `${a.name} (${a.tier})` })),
);

const serviceLineOptions = computed(() => [
    { value: '', label: '— optional —' },
    ...serviceLinesForAccount.value.map((line) => ({
        value: line.id,
        label: `${line.label}${line.did ? ` (${line.did})` : ''}`,
    })),
]);

const ticketTypeOptions = [
    { value: 'fault', label: 'Fault' },
    { value: 'provisioning', label: 'Provisioning' },
    { value: 'number_port', label: 'Number port' },
];

const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'normal', label: 'Normal' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
];

function ticketUrl(id) {
    return `/tickets/${id}`;
}

async function ensureAccounts() {
    if (accounts.value.length) {
        return;
    }
    const accountList = await fetchAccounts();
    accounts.value = accountList;
    if (!form.account_id && accountList.length) {
        form.account_id = accountList[0].id;
    }
}

async function load(page = 1, scrollToTable = false, refreshSummary = false) {
    ticketPage.value.current_page = page;
    if (scrollToTable) {
        scrollToSectionHeading(queueHeadingId);
    }
    if (booting.value) {
        // first paint only
    } else {
        queueBusy.value = true;
    }
    error.value = '';
    try {
        const wantSummary = refreshSummary || summary.value === null;
        const [ticketPaginator, , dash] = await Promise.all([
            fetchTickets({
                status: filter.status || undefined,
                type: filter.type || undefined,
                q: filter.q || undefined,
                page: ticketPage.value.current_page,
                ...queueSortParams(),
            }),
            ensureAccounts(),
            wantSummary ? fetchDashboard() : Promise.resolve(null),
        ]);
        if (dash) {
            summary.value = dash;
        }
        ticketPage.value = ticketPaginator ?? { data: [], total: 0, current_page: 1, last_page: 1 };
        tickets.value = ticketPage.value.data ?? [];
    } catch (e) {
        error.value = e?.message ?? 'Could not load the desk. Refresh the page or try again.';
    } finally {
        booting.value = false;
        queueBusy.value = false;
        if (scrollToTable) {
            await nextTick();
            scrollToSectionHeading(queueHeadingId);
        }
    }
}

function resetQueueFilters() {
    filter.status = '';
    filter.type = '';
    filter.q = '';
    load(1, false, true);
}

async function submitTicket() {
    error.value = '';
    try {
        await createTicket({
            ...form,
            service_line_id: form.service_line_id || null,
        });
        form.subject = '';
        form.description = '';
        await load(ticketPage.value.current_page, false, true);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Could not create that ticket. Check the fields and try again.';
    }
}

onMounted(() => {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    scrollToPageTop();
    load();
});

function selectTab(tabId) {
    activeTab.value = tabId;
    scrollToPageTop();
}
</script>

<template>
  <div class="min-h-screen">
    <a
      href="#desk-main"
      class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-cyan-600 focus:px-4 focus:py-2 focus:text-white"
    >Skip to main content</a>

    <header class="border-b border-slate-800 bg-slate-900/80 px-4 py-4 sm:px-6">
      <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs uppercase tracking-widest text-cyan-400">{{ demoBrand.name }} · {{ demoBrand.deskTitle }}</p>
          <h1 id="page-heading" class="text-lg font-semibold text-white sm:text-xl">{{ demoBrand.tagline }}</h1>
          <p class="text-sm text-slate-400">{{ demoBrand.deskSubtitle }}</p>
        </div>
        <nav class="flex flex-wrap gap-2" aria-label="Demo stack links">
          <a
            v-for="link in demoStackLinks"
            :key="link.href"
            :class="linkStack"
            :href="link.href"
            :aria-current="link.href === '/' ? 'page' : undefined"
          >{{ link.label }}</a>
        </nav>
      </div>
    </header>

    <main
      id="desk-main"
      class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6"
      :aria-busy="booting || queueBusy"
    >
      <p v-if="error" role="alert" :class="alertError">{{ error }}</p>
      <p v-if="booting" class="text-slate-400" aria-live="polite">Loading desk…</p>

      <nav v-if="!booting" class="flex flex-wrap gap-2 border-b border-slate-800 pb-4" aria-label="Desk areas">
        <button
          v-for="tab in demoDeskTabs"
          :key="tab.id"
          type="button"
          :class="[activeTab === tab.id ? btnPrimary : btnGhost, btnDeskTab]"
          @click="selectTab(tab.id)"
        >{{ tab.label }}</button>
      </nav>

      <section v-if="summary && !booting" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Desk summary">
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Open urgent</p>
          <p class="text-3xl font-semibold text-amber-300">{{ summary.open_urgent }}</p>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Open tickets</p>
          <p class="text-3xl font-semibold text-white">{{ summary.totals?.open_tickets ?? '—' }}</p>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Accounts / lines</p>
          <p class="text-2xl font-semibold text-white">
            {{ summary.totals?.accounts ?? '—' }} · {{ summary.totals?.service_lines ?? '—' }}
          </p>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Tickets in system</p>
          <p class="text-3xl font-semibold text-cyan-200">{{ summary.totals?.tickets ?? '—' }}</p>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Corporate bids</p>
          <p class="text-3xl font-semibold text-emerald-200">{{ summary.totals?.quotes ?? '—' }}</p>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <p class="text-xs text-slate-400">Provisioning orders</p>
          <p class="text-3xl font-semibold text-violet-200">{{ summary.totals?.provisioning_orders ?? '—' }}</p>
        </article>
      </section>

      <section v-if="summary && !booting && activeTab === 'support'" class="grid gap-4 sm:grid-cols-2" aria-label="Breakdown">
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 class="text-xs text-slate-400">By type</h2>
          <ul class="mt-2 space-y-1 text-sm">
            <li v-for="(count, key) in summary.by_type" :key="key">{{ typeLabels[key] ?? key }}: {{ count }}</li>
          </ul>
        </article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 class="text-xs text-slate-400">By status</h2>
          <ul class="mt-2 space-y-1 text-sm">
            <li v-for="(count, key) in summary.by_status" :key="key">{{ statusLabels[key] ?? key }}: {{ count }}</li>
          </ul>
        </article>
      </section>

      <BidsPanel v-if="!booting && activeTab === 'bids'" />
      <ProvisioningPanel v-if="!booting && activeTab === 'provisioning'" />

      <template v-if="!booting && activeTab === 'support'">
      <section class="rounded-xl border border-slate-800 bg-slate-900 p-4 sm:p-5" aria-labelledby="new-ticket-heading">
        <h2 id="new-ticket-heading" class="mb-4 text-lg font-medium">New ticket</h2>
        <form
          class="grid gap-4 md:grid-cols-2 md:items-start"
          @submit.prevent="submitTicket"
          @click="(e) => onSectionFormInteract(newTicketHeadingId, e)"
        >
          <label :class="field">
            <span :class="fieldLabel">Account</span>
            <DeskSelect v-model="form.account_id" :options="accountOptions" />
          </label>
          <label :class="field">
            <span :class="fieldLabel">Service line</span>
            <DeskSelect v-model="form.service_line_id" :options="serviceLineOptions" />
          </label>
          <label :class="field">
            <span :class="fieldLabel">Type</span>
            <DeskSelect v-model="form.type" :options="ticketTypeOptions" />
          </label>
          <label :class="field">
            <span :class="fieldLabel">Priority</span>
            <DeskSelect v-model="form.priority" :options="priorityOptions" />
          </label>
          <label :class="[field, 'md:col-span-2']">
            <span :class="fieldLabel">Subject</span>
            <input v-model="form.subject" required :class="control" autocomplete="off" />
          </label>
          <label :class="[field, 'md:col-span-2']">
            <span :class="fieldLabel">Description</span>
            <textarea v-model="form.description" rows="3" :class="textareaControl" />
          </label>
          <div class="md:col-span-2">
            <button type="submit" :class="btnPrimary">Create ticket</button>
          </div>
        </form>
      </section>

      <section aria-labelledby="queue-heading">
        <h2 id="queue-heading" class="mb-3 text-lg font-medium">Queue</h2>
        <div
          :class="toolbar"
          class="mb-3"
          @click="(e) => onSectionFormInteract(queueHeadingId, e)"
        >
          <label :class="[field, 'shrink-0 sm:w-44']" for="filter-status">
            <span :class="fieldLabel">Status</span>
            <DeskSelect id="filter-status" v-model="filter.status" :options="statusFilterOptions" @change="load(1)" />
          </label>
          <label :class="[field, 'shrink-0 sm:w-44']" for="filter-type">
            <span :class="fieldLabel">Type</span>
            <DeskSelect id="filter-type" v-model="filter.type" :options="typeFilterOptions" @change="load(1)" />
          </label>
          <label :class="[field, 'min-w-0 flex-1 sm:w-52 sm:flex-none']" for="filter-q">
            <span :class="fieldLabel">Search</span>
            <input
              id="filter-q"
              v-model="filter.q"
              placeholder="Ref or subject"
              :class="inputControlCompact"
              @keyup.enter="load(1)"
            />
          </label>
          <div class="flex shrink-0 flex-col gap-1.5">
            <span :class="fieldLabel" class="invisible select-none" aria-hidden="true">Search</span>
            <button type="button" :class="btnGhost" @click="load(1)">Search</button>
          </div>
          <div class="flex shrink-0 flex-col gap-1.5">
            <span :class="fieldLabel" class="invisible select-none" aria-hidden="true">Reset</span>
            <button type="button" :class="btnGhost" @click="resetQueueFilters">Reset</button>
          </div>
        </div>
        <DeskPagination
          class="mb-3"
          :current-page="ticketPage.current_page"
          :last-page="ticketPage.last_page"
          :total="ticketPage.total"
          noun="tickets"
          :section-heading-id="queueHeadingId"
          @page="(p) => load(p, true)"
        />

        <div
          :id="supportQueueTableId"
          class="max-w-full overflow-x-auto rounded-xl border border-slate-800 transition-opacity"
          :class="queueBusy ? 'pointer-events-none opacity-60' : ''"
          :aria-busy="queueBusy"
        >
          <table class="desk-data-table">
            <caption class="sr-only">Support ticket queue</caption>
            <thead class="bg-slate-900 text-slate-400">
              <tr>
                <DeskSortTh
                  column="reference"
                  :active="queueSort.by === 'reference'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Ref</DeskSortTh>
                <DeskSortTh
                  column="account"
                  extra-class="hidden md:table-cell"
                  :active="queueSort.by === 'account'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Account</DeskSortTh>
                <DeskSortTh
                  column="type"
                  extra-class="hidden lg:table-cell"
                  :active="queueSort.by === 'type'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Type</DeskSortTh>
                <DeskSortTh
                  column="subject"
                  extra-class="desk-col-subject"
                  :active="queueSort.by === 'subject'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Subject</DeskSortTh>
                <DeskSortTh
                  column="priority"
                  extra-class="hidden sm:table-cell"
                  :active="queueSort.by === 'priority'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Priority</DeskSortTh>
                <DeskSortTh
                  column="status"
                  :active="queueSort.by === 'status'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Status</DeskSortTh>
                <DeskSortTh
                  column="created_at"
                  extra-class="hidden lg:table-cell desk-col-nowrap"
                  :active="queueSort.by === 'created_at'"
                  :direction="queueSort.direction"
                  @sort="onQueueSort"
                >Created</DeskSortTh>
                <th scope="col" class="px-4 py-3"><span class="sr-only">Open ticket</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="t in tickets"
                :key="t.id"
                class="border-t border-slate-800 bg-slate-950/60 hover:bg-slate-900/80"
              >
                <td class="px-4 py-2 font-mono">
                  <a
                    :href="ticketUrl(t.id)"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="linkRow"
                  >{{ t.reference }}</a>
                </td>
                <td class="hidden px-4 py-2 md:table-cell">
                  {{ t.account?.name }}
                </td>
                <td class="hidden px-4 py-2 lg:table-cell">{{ typeLabels[t.type] ?? t.type }}</td>
                <td class="desk-col-subject px-4 py-2">
                  <a
                    :href="ticketUrl(t.id)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="rounded-sm text-slate-100 hover:text-cyan-200 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                  >{{ t.subject }}</a>
                </td>
                <td class="hidden px-4 py-2 capitalize sm:table-cell">{{ t.priority }}</td>
                <td class="px-4 py-2">{{ statusLabels[t.status] ?? t.status }}</td>
                <td class="hidden px-4 py-2 lg:table-cell desk-col-nowrap">{{ formatWhen(t.created_at) }}</td>
                <td class="px-4 py-2">
                  <a
                    :href="ticketUrl(t.id)"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="linkRow"
                  >Open</a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      </template>
    </main>
  </div>
</template>

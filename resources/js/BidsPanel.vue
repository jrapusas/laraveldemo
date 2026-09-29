<script setup>
import { computed, onMounted, reactive, ref, nextTick } from 'vue';
import DeskPagination from './DeskPagination.vue';
import DeskSelect from './DeskSelect.vue';
import DeskSortTh from './DeskSortTh.vue';
import { fetchQuotes } from './api';
import { formatAud, formatDate } from './labels';
import {
    btnGhost,
    field,
    fieldLabel,
    inputControlCompact,
    linkRow,
    onSectionFormInteract,
    scrollToSectionHeading,
    toolbar,
} from './ui';
import { useDeskTableSort } from './useDeskTableSort';

const bidsHeadingId = 'bids-heading';
const bidsTableId = 'bids-table';

const quoteStatusLabels = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under review',
    approved: 'Approved',
    rejected: 'Rejected',
    expired: 'Expired',
};

const ready = ref(false);
const busy = ref(false);
const error = ref('');
const page = ref({ data: [], total: 0, current_page: 1, last_page: 1 });
const filter = reactive({ status: '', q: '' });

const bidStatusOptions = computed(() => [
    { value: '', label: 'All statuses' },
    ...Object.entries(quoteStatusLabels).map(([value, label]) => ({ value, label })),
]);

const bidSortDirs = {
    created_at: 'desc',
    reference: 'asc',
    account: 'asc',
    title: 'asc',
    subtotal_aud: 'desc',
    status: 'asc',
    valid_until: 'asc',
};
const { sort: bidSort, toggle: toggleBidSort, sortParams: bidSortParams } = useDeskTableSort(
    'created_at',
    bidSortDirs,
);

function onBidSort(column) {
    toggleBidSort(column);
    load(1, true);
}

function quoteUrl(id) {
    return `/quotes/${id}`;
}

async function load(p = 1, scrollToTable = false) {
    if (scrollToTable) {
        scrollToSectionHeading(bidsHeadingId);
    }
    if (ready.value) {
        busy.value = true;
    }
    error.value = '';
    try {
        page.value = await fetchQuotes({
            page: p,
            status: filter.status || undefined,
            q: filter.q || undefined,
            ...bidSortParams(),
        });
    } catch (e) {
        error.value = e?.message ?? 'Could not load corporate bids.';
    } finally {
        ready.value = true;
        busy.value = false;
        if (scrollToTable) {
            await nextTick();
            scrollToSectionHeading(bidsHeadingId);
        }
    }
}

onMounted(() => load(1));
</script>

<template>
  <section aria-labelledby="bids-heading">
    <p class="mb-3 text-sm text-slate-400">
      Corporate client bidding and pricing — line-item quotes with approval states (inspired by internal telco ops patterns, not a real system).
    </p>
    <h2 id="bids-heading" class="mb-3 text-lg font-medium">Corporate bids</h2>
    <div
      :class="toolbar"
      class="mb-3"
      @click="(e) => onSectionFormInteract(bidsHeadingId, e)"
    >
      <label :class="[field, 'shrink-0 sm:w-44']" for="bid-status">
        <span :class="fieldLabel">Status</span>
        <DeskSelect id="bid-status" v-model="filter.status" :options="bidStatusOptions" @change="load(1)" />
      </label>
      <label :class="[field, 'min-w-0 flex-1 sm:w-52 sm:flex-none']" for="bid-q">
        <span :class="fieldLabel">Search</span>
        <input id="bid-q" v-model="filter.q" placeholder="Ref or title" :class="inputControlCompact" @keyup.enter="load(1)" />
      </label>
      <div class="flex shrink-0 flex-col gap-1.5">
        <span :class="fieldLabel" class="invisible select-none" aria-hidden="true">Search</span>
        <button type="button" :class="btnGhost" @click="load(1)">Search</button>
      </div>
    </div>

    <p v-if="error" role="alert" class="mb-3 rounded border border-rose-500/40 bg-rose-950/40 px-4 py-2 text-sm text-rose-100">{{ error }}</p>
    <p v-if="!ready" class="text-slate-400">Loading bids…</p>

    <DeskPagination
      v-if="ready"
      class="mb-3"
      :current-page="page.current_page"
      :last-page="page.last_page"
      :total="page.total"
      noun="bids"
      :section-heading-id="bidsHeadingId"
      @page="(p) => load(p, true)"
    />

    <div
      v-if="ready"
      :id="bidsTableId"
      class="max-w-full overflow-x-auto rounded-xl border border-slate-800 transition-opacity"
      :class="busy ? 'pointer-events-none opacity-60' : ''"
      :aria-busy="busy"
    >
      <table class="desk-data-table">
        <caption class="sr-only">Corporate pricing quotes</caption>
        <thead class="bg-slate-900 text-slate-400">
          <tr>
            <DeskSortTh column="reference" :active="bidSort.by === 'reference'" :direction="bidSort.direction" @sort="onBidSort">Ref</DeskSortTh>
            <DeskSortTh column="account" :active="bidSort.by === 'account'" :direction="bidSort.direction" @sort="onBidSort">Corporate account</DeskSortTh>
            <DeskSortTh column="title" extra-class="desk-col-subject" :active="bidSort.by === 'title'" :direction="bidSort.direction" @sort="onBidSort">Title</DeskSortTh>
            <DeskSortTh column="subtotal_aud" align="right" :active="bidSort.by === 'subtotal_aud'" :direction="bidSort.direction" @sort="onBidSort">Subtotal</DeskSortTh>
            <DeskSortTh column="status" :active="bidSort.by === 'status'" :direction="bidSort.direction" @sort="onBidSort">Status</DeskSortTh>
            <DeskSortTh column="valid_until" :active="bidSort.by === 'valid_until'" :direction="bidSort.direction" @sort="onBidSort">Valid until</DeskSortTh>
            <th scope="col" class="px-4 py-3"><span class="sr-only">Open</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="q in page.data" :key="q.id" class="border-t border-slate-800 bg-slate-950/60">
            <td class="px-4 py-2 font-mono">
              <a :href="quoteUrl(q.id)" target="_blank" rel="noopener noreferrer" :class="linkRow">{{ q.reference }}</a>
            </td>
            <td class="px-4 py-2">{{ q.account?.name }}</td>
            <td class="desk-col-subject px-4 py-2">{{ q.title }}</td>
            <td class="desk-money px-4 py-2">{{ formatAud(q.subtotal_aud) }}</td>
            <td class="px-4 py-2">{{ quoteStatusLabels[q.status] ?? q.status }}</td>
            <td class="px-4 py-2">{{ formatDate(q.valid_until) }}</td>
            <td class="px-4 py-2">
              <a :href="quoteUrl(q.id)" target="_blank" rel="noopener noreferrer" :class="linkRow">Open</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, nextTick } from 'vue';
import DeskPagination from './DeskPagination.vue';
import DeskSelect from './DeskSelect.vue';
import DeskSortTh from './DeskSortTh.vue';
import { fetchProvisioningOrders } from './api';
import { formatDate } from './labels';
import {
    btnGhost,
    field,
    fieldLabel,
    inputControlCompact,
    onSectionFormInteract,
    scrollToSectionHeading,
    toolbar,
} from './ui';
import { useDeskTableSort } from './useDeskTableSort';

const provHeadingId = 'prov-heading';
const provisioningTableId = 'provisioning-table';

const provStatusLabels = {
    submitted: 'Submitted',
    scheduled: 'Scheduled',
    in_progress: 'In progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const ready = ref(false);
const busy = ref(false);
const error = ref('');
const page = ref({ data: [], total: 0, current_page: 1, last_page: 1 });
const filter = reactive({ status: '', q: '' });

const provStatusOptions = computed(() => [
    { value: '', label: 'All statuses' },
    ...Object.entries(provStatusLabels).map(([value, label]) => ({ value, label })),
]);

const provSortDirs = {
    created_at: 'desc',
    reference: 'asc',
    account: 'asc',
    title: 'asc',
    quote: 'asc',
    status: 'asc',
    requested_for: 'asc',
};
const { sort: provSort, toggle: toggleProvSort, sortParams: provSortParams } = useDeskTableSort(
    'created_at',
    provSortDirs,
);

function onProvSort(column) {
    toggleProvSort(column);
    load(1, true);
}

async function load(p = 1, scrollToTable = false) {
    if (scrollToTable) {
        scrollToSectionHeading(provHeadingId);
    }
    if (ready.value) {
        busy.value = true;
    }
    error.value = '';
    try {
        page.value = await fetchProvisioningOrders({
            page: p,
            status: filter.status || undefined,
            q: filter.q || undefined,
            ...provSortParams(),
        });
    } catch (e) {
        error.value = e?.message ?? 'Could not load provisioning orders.';
    } finally {
        ready.value = true;
        busy.value = false;
        if (scrollToTable) {
            await nextTick();
            scrollToSectionHeading(provHeadingId);
        }
    }
}

onMounted(() => load(1));
</script>

<template>
  <section aria-labelledby="prov-heading">
    <p class="mb-3 text-sm text-slate-400">
      Track corporate install and change orders — often linked to an approved bid (provisioning tracking lane).
    </p>
    <h2 id="prov-heading" class="mb-3 text-lg font-medium">Provisioning</h2>
    <div
      :class="toolbar"
      class="mb-3"
      @click="(e) => onSectionFormInteract(provHeadingId, e)"
    >
      <label :class="[field, 'shrink-0 sm:w-44']" for="prov-status">
        <span :class="fieldLabel">Status</span>
        <DeskSelect id="prov-status" v-model="filter.status" :options="provStatusOptions" @change="load(1)" />
      </label>
      <label :class="[field, 'min-w-0 flex-1 sm:w-52 sm:flex-none']" for="prov-q">
        <span :class="fieldLabel">Search</span>
        <input id="prov-q" v-model="filter.q" placeholder="Ref or title" :class="inputControlCompact" @keyup.enter="load(1)" />
      </label>
      <div class="flex shrink-0 flex-col gap-1.5">
        <span :class="fieldLabel" class="invisible select-none" aria-hidden="true">Search</span>
        <button type="button" :class="btnGhost" @click="load(1)">Search</button>
      </div>
    </div>

    <p v-if="error" role="alert" class="mb-3 rounded border border-rose-500/40 bg-rose-950/40 px-4 py-2 text-sm text-rose-100">{{ error }}</p>
    <p v-if="!ready" class="text-slate-400">Loading provisioning…</p>

    <DeskPagination
      v-if="ready"
      class="mb-3"
      :current-page="page.current_page"
      :last-page="page.last_page"
      :total="page.total"
      noun="orders"
      :section-heading-id="provHeadingId"
      @page="(p) => load(p, true)"
    />

    <div
      v-if="ready"
      :id="provisioningTableId"
      class="max-w-full overflow-x-auto rounded-xl border border-slate-800 transition-opacity"
      :class="busy ? 'pointer-events-none opacity-60' : ''"
      :aria-busy="busy"
    >
      <table class="desk-data-table">
        <caption class="sr-only">Provisioning orders</caption>
        <thead class="bg-slate-900 text-slate-400">
          <tr>
            <DeskSortTh column="reference" :active="provSort.by === 'reference'" :direction="provSort.direction" @sort="onProvSort">Ref</DeskSortTh>
            <DeskSortTh column="account" :active="provSort.by === 'account'" :direction="provSort.direction" @sort="onProvSort">Account</DeskSortTh>
            <DeskSortTh column="title" extra-class="desk-col-subject" :active="provSort.by === 'title'" :direction="provSort.direction" @sort="onProvSort">Title</DeskSortTh>
            <DeskSortTh column="quote" :active="provSort.by === 'quote'" :direction="provSort.direction" @sort="onProvSort">Bid ref</DeskSortTh>
            <DeskSortTh column="status" :active="provSort.by === 'status'" :direction="provSort.direction" @sort="onProvSort">Status</DeskSortTh>
            <DeskSortTh column="requested_for" :active="provSort.by === 'requested_for'" :direction="provSort.direction" @sort="onProvSort">Requested for</DeskSortTh>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in page.data" :key="row.id" class="border-t border-slate-800 bg-slate-950/60">
            <td class="px-4 py-2 font-mono text-cyan-200">{{ row.reference }}</td>
            <td class="px-4 py-2">{{ row.account?.name }}</td>
            <td class="desk-col-subject px-4 py-2">{{ row.title }}</td>
            <td class="px-4 py-2 font-mono text-slate-400">{{ row.quote?.reference ?? '—' }}</td>
            <td class="px-4 py-2">{{ provStatusLabels[row.status] ?? row.status }}</td>
            <td class="px-4 py-2">{{ formatDate(row.requested_for) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

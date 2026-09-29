<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchQuote } from './api';
import { formatAud, formatDate } from './labels';
import { alertError, linkInline, scrollToPageTop } from './ui';
import DeskSortTh from './DeskSortTh.vue';
import { sortRows, useDeskTableSort } from './useDeskTableSort';

const props = defineProps({
    quoteId: { type: [String, Number], required: true },
});

const loading = ref(true);
const error = ref('');
const quote = ref(null);

const statusLabels = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under review',
    approved: 'Approved',
    rejected: 'Rejected',
    expired: 'Expired',
};

const lineSortDirs = {
    product_code: 'asc',
    description: 'asc',
    quantity: 'desc',
    unit_price_aud: 'desc',
    line_total_aud: 'desc',
};
const { sort: lineSort, toggle: toggleLineSort } = useDeskTableSort('product_code', lineSortDirs);

const lineItemAccessors = {
    product_code: (row) => row.product_code,
    description: (row) => row.description,
    quantity: (row) => row.quantity,
    unit_price_aud: (row) => row.unit_price_aud,
    line_total_aud: (row) => row.line_total_aud,
};

const sortedLineItems = computed(() => {
    if (!quote.value?.items) {
        return [];
    }
    return sortRows(quote.value.items, lineSort, lineItemAccessors);
});

onMounted(async () => {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    scrollToPageTop();
    try {
        quote.value = await fetchQuote(props.quoteId);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Could not load this bid.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-slate-800 bg-slate-900/80 px-4 py-4 sm:px-6">
      <div class="mx-auto flex max-w-4xl justify-between gap-3">
        <a href="/" :class="linkInline">← Back to desk</a>
        <span class="text-xs text-slate-500">Corporate bid</span>
      </div>
    </header>
    <main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
      <p v-if="error" role="alert" :class="alertError">{{ error }}</p>
      <p v-if="loading" class="text-slate-400">Loading bid…</p>
      <template v-else-if="quote">
        <div>
          <p class="font-mono text-cyan-300">{{ quote.reference }}</p>
          <h1 id="page-heading" class="text-xl font-semibold">{{ quote.title }}</h1>
          <p class="text-sm text-slate-400">{{ quote.account?.name }} · {{ statusLabels[quote.status] ?? quote.status }}</p>
        </div>
        <p class="text-lg text-white">
          <span class="tabular-nums">{{ formatAud(quote.subtotal_aud) }}</span>
          <span v-if="quote.valid_until" class="text-sm text-slate-400"> · valid until {{ formatDate(quote.valid_until) }}</span>
        </p>
        <p v-if="quote.notes" class="text-sm text-slate-300">{{ quote.notes }}</p>

        <section>
          <h2 class="text-sm font-medium text-slate-300">Line items</h2>
          <div class="mt-2 max-w-full overflow-x-auto rounded-xl border border-slate-800">
            <table class="desk-data-table">
              <thead class="bg-slate-900 text-slate-400">
                <tr>
                  <DeskSortTh column="product_code" extra-class="desk-col-nowrap" :active="lineSort.by === 'product_code'" :direction="lineSort.direction" @sort="toggleLineSort">Code</DeskSortTh>
                  <DeskSortTh column="description" extra-class="desk-col-subject" :active="lineSort.by === 'description'" :direction="lineSort.direction" @sort="toggleLineSort">Description</DeskSortTh>
                  <DeskSortTh column="quantity" align="right" extra-class="px-4 py-2" :active="lineSort.by === 'quantity'" :direction="lineSort.direction" @sort="toggleLineSort">Qty</DeskSortTh>
                  <DeskSortTh column="unit_price_aud" align="right" :active="lineSort.by === 'unit_price_aud'" :direction="lineSort.direction" @sort="toggleLineSort">Unit</DeskSortTh>
                  <DeskSortTh column="line_total_aud" align="right" :active="lineSort.by === 'line_total_aud'" :direction="lineSort.direction" @sort="toggleLineSort">Line total</DeskSortTh>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in sortedLineItems" :key="item.id" class="border-t border-slate-800">
                  <td class="desk-col-nowrap px-4 py-2 font-mono">{{ item.product_code }}</td>
                  <td class="desk-col-subject px-4 py-2">{{ item.description }}</td>
                  <td class="px-4 py-2 text-right tabular-nums">{{ item.quantity }}</td>
                  <td class="desk-money px-4 py-2">{{ formatAud(item.unit_price_aud) }}</td>
                  <td class="desk-money px-4 py-2">{{ formatAud(item.line_total_aud) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="quote.provisioning_orders?.length">
          <h2 class="text-sm font-medium text-slate-300">Linked provisioning</h2>
          <ul class="mt-2 space-y-2 text-sm">
            <li v-for="p in quote.provisioning_orders" :key="p.id" class="rounded border border-slate-800 px-3 py-2">
              <span class="font-mono text-cyan-200">{{ p.reference }}</span> — {{ p.title }} ({{ p.status }})
            </li>
          </ul>
        </section>
      </template>
    </main>
  </div>
</template>

<script setup>
import { btnGhost, onSectionFormInteract } from './ui';

defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    total: { type: Number, required: true },
    noun: { type: String, required: true },
    sectionHeadingId: { type: String, default: '' },
});

const emit = defineEmits(['page']);

function onPagerInteract(event, headingId) {
    if (headingId) {
        onSectionFormInteract(headingId, event);
    }
}
</script>

<template>
  <div
    class="desk-pager text-sm text-slate-400"
    @click="(e) => onPagerInteract(e, sectionHeadingId)"
  >
    <button
      type="button"
      :class="btnGhost"
      :disabled="currentPage <= 1"
      @click="emit('page', currentPage - 1)"
    >
      Previous
    </button>
    <span class="desk-pager-stats" aria-live="polite">
      Page {{ currentPage }} / {{ lastPage }} · {{ total }} {{ noun }}
    </span>
    <button
      type="button"
      :class="btnGhost"
      :disabled="currentPage >= lastPage"
      @click="emit('page', currentPage + 1)"
    >
      Next
    </button>
  </div>
</template>

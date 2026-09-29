<script setup>
import { computed } from 'vue';

const props = defineProps({
    column: { type: String, required: true },
    active: { type: Boolean, default: false },
    direction: { type: String, default: 'asc' },
    extraClass: { type: String, default: '' },
    align: { type: String, default: 'left' },
});

const emit = defineEmits(['sort']);

const ariaSort = computed(() => {
    if (!props.active) {
        return 'none';
    }
    return props.direction === 'asc' ? 'ascending' : 'descending';
});

</script>

<template>
  <th
    scope="col"
    :class="[
      extraClass,
      align === 'right' ? 'text-right' : '',
      'px-4 py-3',
    ]"
    :aria-sort="ariaSort"
  >
    <button
      type="button"
      class="desk-sort-btn inline-flex items-center gap-1"
      :class="align === 'right' ? 'ml-auto' : ''"
      @click="emit('sort', column)"
    >
      <slot />
      <span class="desk-sort-glyph" aria-hidden="true"></span>
    </button>
  </th>
</template>

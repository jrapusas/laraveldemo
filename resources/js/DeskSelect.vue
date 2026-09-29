<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { focusRing } from './ui';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    id: { type: String, default: undefined },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Choose…' },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const root = ref(null);

const currentLabel = computed(() => {
    const hit = props.options.find((o) => String(o.value) === String(props.modelValue));
    return hit?.label ?? props.placeholder;
});

function toggle() {
    if (props.disabled) {
        return;
    }
    open.value = !open.value;
}

function pick(option) {
    emit('update:modelValue', option.value);
    emit('change', option.value);
    open.value = false;
}

function onDocumentClick(event) {
    if (!open.value || !root.value) {
        return;
    }
    if (!root.value.contains(event.target)) {
        open.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
  <div ref="root" class="relative w-full" data-desk-select>
    <button
      :id="id"
      type="button"
      class="desk-control flex h-11 w-full items-center justify-between gap-2 text-left"
      :class="focusRing"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click.stop="toggle"
    >
      <span class="min-w-0 flex-1">{{ currentLabel }}</span>
      <svg
        class="size-4 shrink-0 text-slate-400"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    <ul
      v-show="open"
      role="listbox"
      class="absolute left-0 top-full z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-700 bg-slate-950 py-1 text-sm shadow-lg"
      :aria-labelledby="id"
    >
      <li
        v-for="opt in options"
        :key="String(opt.value)"
        role="option"
        :aria-selected="String(modelValue) === String(opt.value)"
        class="cursor-pointer px-3 py-2 text-slate-100 hover:bg-slate-800"
        :class="String(modelValue) === String(opt.value) ? 'bg-slate-800/90 text-cyan-200' : ''"
        @click.stop="pick(opt)"
      >
        {{ opt.label }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const open = ref(false);
const el   = ref(null);

function onDocClick(e) {
    if (el.value && !el.value.contains(e.target)) open.value = false;
}

onMounted(()   => document.addEventListener('click', onDocClick, true));
onUnmounted(() => document.removeEventListener('click', onDocClick, true));
</script>

<template>
  <div ref="el" class="relative inline-block">
    <button
      @click.stop="open = !open"
      class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
      title="Acciones"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="5"  r="1.5"/>
        <circle cx="12" cy="12" r="1.5"/>
        <circle cx="12" cy="19" r="1.5"/>
      </svg>
    </button>

    <Transition name="drop">
      <div
        v-if="open"
        class="absolute left-0 top-8 z-30 bg-white rounded-xl shadow-lg border border-gray-100 py-1 min-w-[148px]"
        @click="open = false"
      >
        <slot />
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.drop-enter-active, .drop-leave-active { transition: opacity 0.15s, transform 0.15s; }
.drop-enter-from, .drop-leave-to       { opacity: 0; transform: translateY(-6px) scale(0.97); }
</style>

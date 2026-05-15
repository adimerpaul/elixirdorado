<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

defineProps({
    label: { type: String, default: 'Opciones' },
});

const open       = ref(false);
const trigger    = ref(null);
const menuStyle  = ref({});

async function toggle() {
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        position();
    }
}

function position() {
    if (!trigger.value) return;
    const r = trigger.value.getBoundingClientRect();
    // Prefer left-aligned; flip if too close to right edge
    const left = r.left + 160 > window.innerWidth ? r.right - 160 : r.left;
    menuStyle.value = {
        top:  `${r.bottom + 4}px`,
        left: `${left}px`,
    };
}

function onDocClick(e) {
    if (trigger.value && !trigger.value.contains(e.target)) open.value = false;
}

function onScroll() { if (open.value) position(); }

onMounted(() => {
    document.addEventListener('click', onDocClick, true);
    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', onScroll);
});
onUnmounted(() => {
    document.removeEventListener('click', onDocClick, true);
    window.removeEventListener('scroll', onScroll, true);
    window.removeEventListener('resize', onScroll);
});
</script>

<template>
  <div ref="trigger" class="inline-block">

    <!-- Trigger button — always visible -->
    <button
      @click.stop="toggle"
      :class="[
        'flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium border transition-colors',
        open
          ? 'bg-blue-50 border-blue-300 text-blue-700'
          : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 hover:border-gray-300 hover:text-gray-800'
      ]"
    >
      {{ label }}
      <svg
        class="w-3 h-3 transition-transform"
        :class="open ? 'rotate-180' : ''"
        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
      </svg>
    </button>

    <!-- Portal: render outside any overflow-hidden ancestor -->
    <Teleport to="body">
      <Transition name="drop">
        <div
          v-if="open"
          :style="menuStyle"
          class="fixed z-[9999] bg-white rounded-xl shadow-xl border border-gray-100 py-1 min-w-[160px]"
          @click="open = false"
        >
          <slot />
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.drop-enter-active, .drop-leave-active { transition: opacity 0.12s, transform 0.12s; }
.drop-enter-from, .drop-leave-to       { opacity: 0; transform: translateY(-5px) scale(0.97); }
</style>

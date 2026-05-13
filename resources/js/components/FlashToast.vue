<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type FlashPayload = {
    success?: string | null;
    error?: string | null;
};

const page = usePage<{ flash: FlashPayload }>();

const visible = ref(false);
const message = ref('');
const tone = ref<'success' | 'error'>('success');
let dismissTimer: ReturnType<typeof setTimeout> | null = null;

const toneClasses = computed(() => {
    if (tone.value === 'error') {
        return 'bg-rose-500 text-white';
    }

    return 'bg-teal-500 text-white';
});

function show(text: string, kind: 'success' | 'error'): void {
    message.value = text;
    tone.value = kind;
    visible.value = true;

    if (dismissTimer !== null) {
        clearTimeout(dismissTimer);
    }

    dismissTimer = setTimeout(() => {
        visible.value = false;
    }, 3500);
}

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([success, error]) => {
        if (success) {
            show(success, 'success');

            return;
        }

        if (error) {
            show(error, 'error');
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-from-class="translate-y-2 opacity-0"
        leave-to-class="translate-y-2 opacity-0"
        enter-active-class="transition duration-200 ease-out"
        leave-active-class="transition duration-200 ease-in"
    >
        <div
            v-if="visible"
            role="status"
            aria-live="polite"
            :class="[
                'fixed bottom-6 right-6 z-50 max-w-sm rounded-lg px-5 py-3 text-sm font-medium shadow-lg',
                toneClasses,
            ]"
        >
            {{ message }}
        </div>
    </Transition>
</template>

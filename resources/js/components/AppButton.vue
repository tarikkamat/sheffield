<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type Variant = 'primary' | 'secondary' | 'danger';
type Size = 'sm' | 'md';

const baseClasses: Record<Variant, string> = {
    primary: 'bg-teal-500 focus-visible:ring-teal-400',
    secondary: 'bg-slate-600 focus-visible:ring-slate-500',
    danger: 'bg-rose-500 focus-visible:ring-rose-400',
};

const hoverClasses: Record<Variant, string> = {
    primary: 'hover:bg-teal-600',
    secondary: 'hover:bg-slate-700',
    danger: 'hover:bg-rose-600',
};

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        size?: Size;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
    },
);

const classes = computed(() =>
    cn(
        'inline-flex items-center justify-center rounded font-medium text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        props.size === 'sm' ? 'px-3 py-1.5 text-sm' : 'px-5 py-2.5 text-base',
        baseClasses[props.variant],
        !props.disabled && hoverClasses[props.variant],
        props.disabled && 'cursor-not-allowed opacity-50',
    ),
);
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled">
        <slot />
    </button>
</template>

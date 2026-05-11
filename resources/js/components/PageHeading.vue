<script setup lang="ts">
defineProps<{
    as?: 'h1' | 'h2' | 'h3';
}>();
</script>

<template>
    <component
        :is="as ?? 'h1'"
        class="text-2xl font-light tracking-tight text-slate-700"
    >
        <slot />
    </component>
</template>

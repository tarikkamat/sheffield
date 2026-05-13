<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { update as updateFixture } from '@/actions/App/Http/Controllers/Tournament/FixtureController';

const props = defineProps<{
    fixtureId: number;
    homeGoals: number | null;
    awayGoals: number | null;
    isPlayed: boolean;
}>();

const editing = ref(false);

const form = useForm({
    home_goals: props.homeGoals ?? 0,
    away_goals: props.awayGoals ?? 0,
});

watch(
    () => [props.homeGoals, props.awayGoals],
    ([home, away]) => {
        form.home_goals = home ?? 0;
        form.away_goals = away ?? 0;
    },
);

function startEdit(): void {
    if (!props.isPlayed) {
        return;
    }

    form.home_goals = props.homeGoals ?? 0;
    form.away_goals = props.awayGoals ?? 0;
    editing.value = true;
}

function save(): void {
    form.submit(updateFixture(props.fixtureId), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
}

function cancel(): void {
    form.clearErrors();
    form.home_goals = props.homeGoals ?? 0;
    form.away_goals = props.awayGoals ?? 0;
    editing.value = false;
}
</script>

<template>
    <div v-if="editing" class="flex items-center justify-center gap-1">
        <input
            v-model.number="form.home_goals"
            type="number"
            min="0"
            max="20"
            class="w-10 rounded border border-slate-300 px-1 py-0.5 text-center text-sm"
        />

        <span class="text-slate-400">-</span>

        <input
            v-model.number="form.away_goals"
            type="number"
            min="0"
            max="20"
            class="w-10 rounded border border-slate-300 px-1 py-0.5 text-center text-sm"
        />

        <button
            type="button"
            class="ml-1 text-teal-600 hover:text-teal-700"
            :disabled="form.processing"
            @click="save"
        >
            ✓
        </button>

        <button
            type="button"
            class="text-slate-400 hover:text-slate-600"
            @click="cancel"
        >
            ✕
        </button>
    </div>

    <button
        v-else-if="isPlayed"
        type="button"
        class="rounded px-2 py-0.5 font-mono hover:bg-slate-100"
        @click="startEdit"
    >
        {{ homeGoals }} - {{ awayGoals }}
    </button>

    <span v-else class="text-slate-400">vs</span>
</template>

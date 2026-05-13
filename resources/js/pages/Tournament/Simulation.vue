<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { store as playAllWeeks } from '@/actions/App/Http/Controllers/Tournament/PlayAllController';
import { destroy as resetTournament } from '@/actions/App/Http/Controllers/Tournament/ResultController';
import { store as playNextWeek } from '@/actions/App/Http/Controllers/Tournament/WeekController';
import AppButton from '@/components/AppButton.vue';
import EditableScore from '@/components/EditableScore.vue';
import FlashToast from '@/components/FlashToast.vue';
import PageHeading from '@/components/PageHeading.vue';
import Table from '@/components/Table.vue';
import { show as showTeams } from '@/routes/tournaments';
import type { Fixture, Prediction, Standing, TableColumn, Tournament } from '@/types';

type WeekHistory = {
    week: number;
    matches: Fixture[];
};

const props = defineProps<{
    tournament: Tournament;
    currentWeek: number;
    standings: Standing[];
    fixtures: Fixture[];
    history: WeekHistory[];
    predictions: Prediction[];
    showPredictions: boolean;
}>();

const standingsColumns: TableColumn<Standing>[] = [
    { key: 'name', label: 'Team Name' },
    { key: 'played', label: 'P', align: 'center', headerAlign: 'center' },
    { key: 'won', label: 'W', align: 'center', headerAlign: 'center' },
    { key: 'drawn', label: 'D', align: 'center', headerAlign: 'center' },
    { key: 'lost', label: 'L', align: 'center', headerAlign: 'center' },
    { key: 'goal_difference', label: 'GD', align: 'center', headerAlign: 'center' },
    { key: 'points', label: 'PTS', align: 'right', headerAlign: 'right' },
];

const fixtureColumns: TableColumn<Fixture>[] = [
    { key: 'home', align: 'left' },
    { key: 'score', align: 'center', width: '80px' },
    { key: 'away', align: 'left' },
];

const predictionColumns: TableColumn<Prediction>[] = [
    { key: 'name', label: 'Championship Predictions' },
    { key: 'chance', label: '%', align: 'right', headerAlign: 'right' },
];

const isCompleted = computed(() => props.tournament.status === 'completed');
const isPending = computed(() => props.currentWeek === 0);

const weekLabel = computed(() => {
    if (isCompleted.value) {
        return 'Final Results';
    }

    if (isPending.value) {
        return 'Week 1 — Not Started';
    }

    return `Week ${props.currentWeek}`;
});

const isBusy = ref(false);

function withBusy(action: () => void): void {
    isBusy.value = true;
    action();
}

function playNext(): void {
    withBusy(() =>
        router.post(playNextWeek(props.tournament.id).url, {}, {
            preserveScroll: true,
            onFinish: () => (isBusy.value = false),
        }),
    );
}

function playAll(): void {
    withBusy(() =>
        router.post(playAllWeeks(props.tournament.id).url, {}, {
            preserveScroll: true,
            onFinish: () => (isBusy.value = false),
        }),
    );
}

function reset(): void {
    if (!window.confirm('Reset all match results?')) {
        return;
    }

    withBusy(() =>
        router.delete(resetTournament(props.tournament.id).url, {
            preserveScroll: true,
            onFinish: () => (isBusy.value = false),
        }),
    );
}
</script>

<template>
    <Head title="Simulation" />

    <FlashToast />

    <main class="min-h-screen bg-white px-6 py-12">
        <div class="mx-auto max-w-7xl space-y-8">
            <div class="flex items-baseline justify-between">
                <div class="flex items-baseline gap-3">
                    <PageHeading>{{ tournament.name ?? 'Simulation' }}</PageHeading>

                    <span
                        v-if="isCompleted"
                        class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold uppercase tracking-wide text-emerald-700"
                    >
                        Season Completed
                    </span>
                </div>

                <Link
                    :href="showTeams(tournament.id).url"
                    class="text-sm font-medium text-teal-600 hover:text-teal-700"
                >
                    ← Back to Teams
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr_1fr]">
                <Table :columns="standingsColumns" :rows="standings" />

                <Table
                    :columns="fixtureColumns"
                    :rows="fixtures"
                    :title="weekLabel"
                    empty-message="No fixtures yet."
                >
                    <template #cell-score="{ row }">
                        <EditableScore
                            :fixture-id="row.id"
                            :home-goals="row.home_goals"
                            :away-goals="row.away_goals"
                            :is-played="row.is_played"
                        />
                    </template>
                </Table>

                <Table
                    :columns="predictionColumns"
                    :rows="predictions"
                    :empty-message="
                        showPredictions
                            ? 'No predictions available.'
                            : 'Predictions appear from week 4.'
                    "
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr_1fr]">
                <div>
                    <AppButton
                        :disabled="isBusy || isCompleted"
                        @click="playAll"
                    >
                        {{ isCompleted ? 'Season Completed' : 'Play All Weeks' }}
                    </AppButton>
                </div>

                <div class="flex justify-center">
                    <AppButton
                        :disabled="isBusy || isCompleted"
                        @click="playNext"
                    >
                        Play Next Week
                    </AppButton>
                </div>

                <div class="flex justify-end">
                    <AppButton
                        variant="danger"
                        :disabled="isBusy || isPending"
                        @click="reset"
                    >
                        Reset Data
                    </AppButton>
                </div>
            </div>

            <section v-if="history.length > 0" class="space-y-4 pt-6">
                <h2 class="border-b border-slate-200 pb-2 text-lg font-semibold text-slate-800">
                    Match History
                </h2>

                <div class="space-y-6">
                    <article
                        v-for="weekBlock in history"
                        :key="weekBlock.week"
                        class="rounded-lg border border-slate-200 bg-slate-50/60 p-4"
                    >
                        <header class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-600">
                                Week {{ weekBlock.week }}
                            </h3>
                        </header>

                        <ul class="divide-y divide-slate-200">
                            <li
                                v-for="match in weekBlock.matches"
                                :key="match.id"
                                class="grid grid-cols-[1fr_auto_1fr] items-center gap-4 py-2 text-sm"
                            >
                                <span class="text-right text-slate-800">{{ match.home }}</span>

                                <EditableScore
                                    :fixture-id="match.id"
                                    :home-goals="match.home_goals"
                                    :away-goals="match.away_goals"
                                    :is-played="match.is_played"
                                />

                                <span class="text-left text-slate-800">{{ match.away }}</span>
                            </li>
                        </ul>
                    </article>
                </div>
            </section>
        </div>
    </main>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import PageHeading from '@/components/PageHeading.vue';
import Table from '@/components/Table.vue';
import type { Fixture, Prediction, Standing, TableColumn } from '@/types';

defineProps<{
    standings: Standing[];
    currentWeek: number;
    fixtures: Fixture[];
    predictions: Prediction[];
}>();

const standingsColumns: TableColumn<Standing>[] = [
    { key: 'name', label: 'Team Name' },
    { key: 'played', label: 'P' },
    { key: 'won', label: 'W' },
    { key: 'drawn', label: 'D' },
    { key: 'lost', label: 'L' },
    { key: 'goal_difference', label: 'GD' },
];

const fixtureColumns: TableColumn<Fixture>[] = [
    { key: 'home', align: 'left' },
    { key: 'separator', align: 'center', width: '40px' },
    { key: 'away', align: 'left' },
];

const predictionColumns: TableColumn<Prediction>[] = [
    { key: 'name', label: 'Championship Predictions' },
    { key: 'chance', label: '%', align: 'right', headerAlign: 'right' },
];

function handlePlayAll(): void {
    // TODO: trigger play-all-weeks action
}

function handlePlayNext(): void {
    // TODO: trigger play-next-week action
}

function handleReset(): void {
    // TODO: trigger reset action
}
</script>

<template>
    <Head title="Simulation" />

    <main class="min-h-screen bg-white px-6 py-12">
        <div class="mx-auto max-w-7xl space-y-8">
            <PageHeading class="text-center">Simulation</PageHeading>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr_1fr]">
                <Table :columns="standingsColumns" :rows="standings" />

                <Table
                    :columns="fixtureColumns"
                    :rows="fixtures"
                    :title="`Week ${currentWeek}`"
                >
                    <template #cell-separator>
                        <span class="text-slate-400">-</span>
                    </template>
                </Table>

                <Table :columns="predictionColumns" :rows="predictions" />
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr_1fr]">
                <div>
                    <AppButton @click="handlePlayAll">Play All Weeks</AppButton>
                </div>
                <div class="flex justify-center">
                    <AppButton @click="handlePlayNext">Play Next Week</AppButton>
                </div>
                <div class="flex justify-end">
                    <AppButton variant="danger" @click="handleReset">
                        Reset Data
                    </AppButton>
                </div>
            </div>
        </div>
    </main>
</template>

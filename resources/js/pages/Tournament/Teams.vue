<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import PageHeading from '@/components/PageHeading.vue';
import Table from '@/components/Table.vue';
import type { TableColumn, Team } from '@/types';

defineProps<{
    teams: Team[];
}>();

const columns: TableColumn<Team>[] = [
    { key: 'name', label: 'Team Name' },
];

function handleGenerateFixtures(): void {
    // TODO: dispatch fixture generation request
}
</script>

<template>
    <Head title="Tournament Teams" />

    <main class="min-h-screen bg-white px-6 py-12">
        <div class="mx-auto max-w-3xl space-y-6">
            <PageHeading>Tournament Teams</PageHeading>

            <Table :columns="columns" :rows="teams" />

            <AppButton @click="handleGenerateFixtures">
                Generate Fixtures
            </AppButton>
        </div>
    </main>
</template>

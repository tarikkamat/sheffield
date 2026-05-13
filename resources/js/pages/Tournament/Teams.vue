<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store as storeFixtures } from '@/actions/App/Http/Controllers/Tournament/FixtureController';
import AppButton from '@/components/AppButton.vue';
import FlashToast from '@/components/FlashToast.vue';
import PageHeading from '@/components/PageHeading.vue';
import Table from '@/components/Table.vue';
import { show as showSimulation } from '@/routes/tournaments/simulation';
import type { TableColumn, Team, Tournament } from '@/types';

const props = defineProps<{
    tournament: Tournament;
    teams: Team[];
}>();

const columns: TableColumn<Team>[] = [
    { key: 'name', label: 'Team Name' },
];

const form = useForm({});

function generateFixtures(): void {
    form.submit(storeFixtures(props.tournament.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tournament Teams" />

    <FlashToast />

    <main class="min-h-screen bg-white px-6 py-12">
        <div class="mx-auto max-w-3xl space-y-6">
            <PageHeading>{{ tournament.name ?? 'Tournament' }} — Teams</PageHeading>

            <Table :columns="columns" :rows="teams" />

            <div class="flex items-center justify-between gap-4">
                <AppButton
                    :disabled="form.processing"
                    @click="generateFixtures"
                >
                    {{ tournament.hasFixtures ? 'Regenerate Fixtures' : 'Generate Fixtures' }}
                </AppButton>

                <Link
                    v-if="tournament.hasFixtures"
                    :href="showSimulation(tournament.id).url"
                    class="font-medium text-teal-600 hover:text-teal-700"
                >
                    Go to Simulation →
                </Link>
            </div>
        </div>
    </main>
</template>

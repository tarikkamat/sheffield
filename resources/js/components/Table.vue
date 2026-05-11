<script setup lang="ts" generic="Row extends Record<string, unknown>">
import { cn } from '@/lib/utils';
import type { TableAlignment, TableColumn } from '@/types';

withDefaults(
    defineProps<{
        columns: TableColumn<Row>[];
        rows: Row[];
        /** When set, the header is a single cell spanning all columns. */
        title?: string;
        rowKey?: string;
        emptyMessage?: string;
    }>(),
    {
        rowKey: 'id',
        emptyMessage: 'No records to display.',
    },
);

function alignClass(align?: TableAlignment): string {
    if (align === 'right') {
        return 'text-right';
    }
    if (align === 'center') {
        return 'text-center';
    }
    return 'text-left';
}

function cellValue(row: Row, column: TableColumn<Row>): unknown {
    const field = column.field ?? column.key;
    return (row as Record<string, unknown>)[field];
}

function rowIdentifier(row: Row, key: string, fallback: number): string {
    const value = (row as Record<string, unknown>)[key];
    return value === undefined || value === null ? String(fallback) : String(value);
}
</script>

<template>
    <table class="w-full border-collapse text-left">
        <thead>
            <tr class="bg-slate-700 text-white">
                <th
                    v-if="title"
                    :colspan="columns.length"
                    scope="col"
                    class="px-6 py-4 text-left text-sm font-bold"
                >
                    {{ title }}
                </th>
                <template v-else>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        scope="col"
                        :style="column.width ? { width: column.width } : undefined"
                        :class="
                            cn(
                                'px-6 py-4 text-sm font-bold',
                                alignClass(column.headerAlign ?? column.align),
                            )
                        "
                    >
                        {{ column.label ?? '' }}
                    </th>
                </template>
            </tr>
        </thead>
        <tbody>
            <tr v-if="rows.length === 0">
                <td
                    :colspan="columns.length"
                    class="px-6 py-5 text-center text-sm text-slate-500"
                >
                    {{ emptyMessage }}
                </td>
            </tr>
            <tr
                v-for="(row, index) in rows"
                :key="rowIdentifier(row, rowKey, index)"
                class="border-b border-slate-100 last:border-b-0"
            >
                <td
                    v-for="column in columns"
                    :key="column.key"
                    :class="cn('px-6 py-5 text-base text-slate-800', alignClass(column.align))"
                >
                    <slot
                        :name="`cell-${column.key}`"
                        :row="row"
                        :value="cellValue(row, column)"
                        :column="column"
                    >
                        {{ cellValue(row, column) }}
                    </slot>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

defineProps({
    students: { type: Array, required: true },
})
</script>

<template>
    <Head :title="t('nav.students')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('teacher.students_heading') }}</h1>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.name') }}</th><th>{{ t('fields.contact') }}</th><th>{{ t('teacher.paid') }}</th><th>{{ t('teacher.pending') }}</th><th>{{ t('teacher.overdue') }}</th></tr></thead>
                <tbody>
                    <tr v-for="student in students" :key="student.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3 font-medium">{{ student.name }}</td>
                        <td>{{ student.email }}<span v-if="student.phone"> · {{ student.phone }}</span></td>
                        <td>{{ money(student.paid_total) }}</td>
                        <td>{{ money(student.pending_total) }}</td>
                        <td>{{ money(student.overdue_total) }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-if="students.length === 0" class="py-4 text-sm text-[#626F86]">{{ t('teacher.no_students') }}</p>
        </div>
    </AppLayout>
</template>

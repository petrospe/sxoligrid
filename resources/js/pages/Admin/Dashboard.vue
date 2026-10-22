<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    stats: { type: Object, required: true },
})

const cards = [
    ['students', t('nav.students')],
    ['teachers', t('admin.teachers')],
    ['courses', t('nav.courses')],
    ['active_enrollments', t('admin.active_enrollments')],
    ['overdue_payments', t('admin.overdue_payments')],
    ['unread_suggestions', t('admin.unread_suggestions')],
]
</script>

<template>
    <Head :title="t('nav.dashboard')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.dashboard_heading') }}</h1>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="[key, title] in cards" :key="key" class="card">
                <p class="text-sm text-[#626F86]">{{ title }}</p>
                <p class="mt-2 text-2xl font-medium">{{ stats[key] }}</p>
            </article>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <Link class="btn" href="/admin/schedules">{{ t('nav.timetable') }}</Link>
            <Link class="btn-soft" href="/admin/enrollments">{{ t('nav.enrollments') }}</Link>
            <Link class="btn-soft" href="/admin/payments">{{ t('nav.payments') }}</Link>
        </div>
    </AppLayout>
</template>

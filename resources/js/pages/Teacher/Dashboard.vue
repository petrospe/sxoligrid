<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import WeeklyGrid from '@/Components/WeeklyGrid.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    schedules: { type: Array, required: true },
    privateLessons: { type: Array, required: true },
})

function setStatus(lesson, status) {
    router.patch(`/teacher/private-lessons/${lesson.id}`, { status })
}
</script>

<template>
    <Head :title="t('teacher.schedule_title')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('teacher.schedule_title') }}</h1>
        <div class="mt-6">
            <WeeklyGrid :schedules="schedules" />
        </div>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.course') }}</th><th>{{ t('fields.day') }}</th><th>{{ t('fields.time') }}</th><th>{{ t('fields.room') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in schedules" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.course.title }}</td>
                        <td>{{ t('days.' + item.day_of_week) }}</td>
                        <td>{{ item.start_time }}–{{ item.end_time }}</td>
                        <td>{{ item.room }}</td>
                        <td class="text-right"><Link class="btn" :href="`/teacher/schedules/${item.id}/attendance`">{{ t('actions.attendance') }}</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h2 class="mt-8 text-xl font-semibold">{{ t('nav.private_lessons') }}</h2>
        <div class="mt-3 space-y-3">
            <article v-for="lesson in privateLessons" :key="lesson.id" class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium">{{ lesson.student }}</p>
                    <p class="text-sm text-[#44546F]">{{ lesson.scheduled_at }} · {{ lesson.duration_minutes }} {{ t('fields.minutes_short') }} · {{ t('lesson_statuses.' + lesson.status) }}</p>
                </div>
                <div class="flex gap-2">
                    <button class="btn-soft" type="button" @click="setStatus(lesson, 'confirmed')">{{ t('actions.confirm') }}</button>
                    <button class="btn-soft" type="button" @click="setStatus(lesson, 'completed')">{{ t('actions.complete') }}</button>
                    <button class="btn-danger" type="button" @click="setStatus(lesson, 'cancelled')">{{ t('actions.cancel') }}</button>
                </div>
            </article>
            <p v-if="privateLessons.length === 0" class="text-sm text-[#626F86]">{{ t('teacher.no_private_lessons') }}</p>
        </div>
    </AppLayout>
</template>

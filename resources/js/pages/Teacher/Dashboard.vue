<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import WeeklyGrid from '@/Components/WeeklyGrid.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    schedules: { type: Array, required: true },
    pastLessons: { type: Array, required: true },
    upcomingLessons: { type: Array, required: true },
    privateLessons: { type: Array, required: true },
})

function setStatus(lesson, status) {
    router.patch(`/teacher/private-lessons/${lesson.id}`, { status })
}
</script>

<template>
    <Head :title="t('teacher.schedule_title')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('lessons.this_week') }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.week_hint') }}</p>
        <div class="mt-6">
            <WeeklyGrid :schedules="schedules" />
        </div>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.course') }}</th><th>{{ t('fields.day') }}</th><th>{{ t('fields.time') }}</th><th>{{ t('fields.room') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in schedules" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.course.title }} · {{ item.section?.name }}</td>
                        <td>{{ t('days.' + item.day_of_week) }}</td>
                        <td>{{ item.start_time }}–{{ item.end_time }}</td>
                        <td>{{ item.room }}</td>
                        <td class="text-right"><Link class="btn" :href="`/teacher/schedules/${item.id}/attendance`">{{ t('actions.attendance') }}</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h2 class="mt-8 text-xl font-semibold">{{ t('lessons.past') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.past_hint') }}</p>
        <div class="mt-3 space-y-3">
            <article v-for="(lesson, index) in pastLessons" :key="`${lesson.date}-${index}`" class="card">
                <p class="text-sm text-[#626F86]">{{ lesson.date }} · {{ lesson.start_time }}–{{ lesson.end_time }} · {{ lesson.course }} · {{ lesson.student }}</p>
                <p v-if="lesson.performance_notes" class="mt-2">{{ lesson.performance_notes }}</p>
                <p v-if="lesson.test_title" class="mt-2 text-sm">{{ lesson.test_title }}: <strong>{{ lesson.test_score }}</strong></p>
            </article>
            <p v-if="pastLessons.length === 0" class="text-sm text-[#626F86]">{{ t('lessons.no_past') }}</p>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('lessons.upcoming') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.upcoming_hint') }}</p>
        <div class="mt-3 space-y-3">
            <article v-for="(lesson, index) in upcomingLessons" :key="`${lesson.date}-${index}`" class="card">
                <p class="font-medium">{{ lesson.date }} · {{ lesson.start_time }}–{{ lesson.end_time }} · {{ lesson.course }}</p>
                <ul class="mt-2 space-y-1 text-sm">
                    <li v-for="student in lesson.students" :key="student.name">{{ student.name }}</li>
                </ul>
            </article>
            <p v-if="upcomingLessons.length === 0" class="text-sm text-[#626F86]">{{ t('lessons.no_upcoming') }}</p>
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

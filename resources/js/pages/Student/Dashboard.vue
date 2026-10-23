<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import WeeklyGrid from '@/Components/WeeklyGrid.vue'
import { useI18n } from '@/i18n'

const { t, money } = useI18n()

const props = defineProps({
    profile: { type: Object, required: true },
    enrollments: { type: Array, required: true },
    subscriptions: { type: Array, required: true },
    payments: { type: Array, required: true },
    privateLessons: { type: Array, required: true },
    pastLessons: { type: Array, required: true },
    upcomingLessons: { type: Array, required: true },
    lessonLogs: { type: Array, required: true },
    events: { type: Array, required: true },
    teachers: { type: Array, required: true },
})

const schedules = props.enrollments.flatMap((enrollment) => enrollment.schedules)
const form = useForm({
    teacher_id: '',
    scheduled_at: '',
    duration_minutes: 60,
})

function submit() {
    form.post('/student/private-lessons', { onSuccess: () => form.reset('teacher_id', 'scheduled_at') })
}
</script>

<template>
    <Head :title="t('nav.profile')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ profile.name }}</h1>
        <p class="mt-2 text-[#44546F]">{{ profile.email }}<span v-if="profile.phone"> · {{ profile.phone }}</span></p>

        <h2 class="mt-8 text-xl font-semibold">{{ t('lessons.this_week') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.week_hint') }}</p>
        <div class="mt-3">
            <WeeklyGrid :schedules="schedules" show-teacher />
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('lessons.upcoming') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.upcoming_hint') }}</p>
        <div class="mt-3 space-y-2">
            <p v-for="(lesson, index) in upcomingLessons" :key="`${lesson.date}-${index}`" class="text-sm">
                {{ lesson.date }} · {{ lesson.start_time }}–{{ lesson.end_time }} · {{ lesson.course }}
                <span v-if="lesson.section"> · {{ lesson.section }}</span>
                <span v-if="lesson.teacher"> · {{ lesson.teacher }}</span>
            </p>
            <p v-if="upcomingLessons.length === 0" class="text-sm text-[#626F86]">{{ t('lessons.no_upcoming') }}</p>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('nav.subscriptions') }}</h2>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.total') }}</th><th>{{ t('fields.paid') }}</th><th>{{ t('fields.balance') }}</th><th>{{ t('fields.from') }}</th><th>{{ t('fields.until') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="item in subscriptions" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.title }}</td>
                        <td>{{ item.course || '—' }}</td>
                        <td>{{ money(item.total_amount) }}</td>
                        <td>{{ money(item.paid_amount) }}</td>
                        <td>{{ money(item.remaining_amount) }}</td>
                        <td>{{ item.starts_at }}</td>
                        <td>{{ item.ends_at || '—' }}</td>
                        <td>{{ t('subscription_statuses.' + item.status) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('student.charges') }}</h2>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.amount') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.due') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ money(payment.amount) }}</td>
                        <td>{{ t('payment_types.' + payment.type) }}</td>
                        <td>{{ payment.due_date || '—' }}</td>
                        <td>{{ t('payment_statuses.' + payment.status) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('lessons.past') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('lessons.past_hint') }}</p>
        <div class="mt-3 space-y-3">
            <article v-for="(lesson, index) in pastLessons" :key="`${lesson.date}-${index}`" class="card">
                <p class="text-sm text-[#626F86]">{{ lesson.date }} · {{ lesson.start_time }}–{{ lesson.end_time }} · {{ lesson.course }}<span v-if="lesson.teacher"> · {{ lesson.teacher }}</span></p>
                <p v-if="lesson.performance_notes" class="mt-2">{{ lesson.performance_notes }}</p>
                <p v-if="lesson.test_title" class="mt-2 text-sm">{{ lesson.test_title }}: <strong>{{ lesson.test_score }}</strong></p>
            </article>
            <p v-if="pastLessons.length === 0" class="text-sm text-[#626F86]">{{ t('lessons.no_past') }}</p>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('nav.private_lessons') }}</h2>
        <form class="card mt-3 grid gap-3 md:grid-cols-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.teacher') }}
                <select v-model="form.teacher_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.when') }}<input v-model="form.scheduled_at" class="field mt-1" type="datetime-local" required></label>
            <label class="text-sm">{{ t('fields.minutes') }}<input v-model="form.duration_minutes" class="field mt-1" type="number" min="15" max="480" required></label>
            <div class="md:col-span-3"><button class="btn" :disabled="form.processing">{{ t('actions.request') }}</button></div>
        </form>
        <div class="mt-3 space-y-2">
            <p v-for="lesson in privateLessons" :key="lesson.id" class="text-sm">
                {{ lesson.scheduled_at }} · {{ lesson.teacher }} · {{ lesson.duration_minutes }} {{ t('fields.minutes_short') }} · {{ t('lesson_statuses.' + lesson.status) }}
            </p>
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('nav.events') }}</h2>
        <div class="mt-3 space-y-2">
            <p v-for="event in events" :key="event.id" class="text-sm">
                {{ event.title }} · {{ event.event_date }} · {{ event.location || t('common.no_location') }} · {{ t('event_roles.' + event.role_in_event) }}
            </p>
            <p v-if="events.length === 0" class="text-sm text-[#626F86]">{{ t('student.no_events') }}</p>
        </div>
    </AppLayout>
</template>

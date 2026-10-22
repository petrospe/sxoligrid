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

        <h2 class="mt-8 text-xl font-semibold">{{ t('teacher.schedule_title') }}</h2>
        <div class="mt-3">
            <WeeklyGrid :schedules="schedules" show-teacher />
        </div>

        <h2 class="mt-8 text-xl font-semibold">{{ t('nav.subscriptions') }}</h2>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.package') }}</th><th>{{ t('fields.balance') }}</th><th>{{ t('fields.from') }}</th><th>{{ t('fields.until') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="item in subscriptions" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ item.package }}</td>
                        <td>{{ item.remaining_sessions ?? t('common.unlimited') }}</td>
                        <td>{{ item.starts_at }}</td>
                        <td>{{ item.expires_at || '—' }}</td>
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

        <h2 class="mt-8 text-xl font-semibold">{{ t('student.progress') }}</h2>
        <div class="mt-3 space-y-3">
            <article v-for="log in lessonLogs" :key="log.id" class="card">
                <p class="text-sm text-[#626F86]">{{ log.created_at }} · {{ log.context }} · {{ log.teacher }}</p>
                <p v-if="log.performance_notes" class="mt-2">{{ log.performance_notes }}</p>
                <p v-if="log.test_title" class="mt-2 text-sm">{{ log.test_title }}: <strong>{{ log.test_score }}</strong></p>
            </article>
            <p v-if="lessonLogs.length === 0" class="text-sm text-[#626F86]">{{ t('student.no_notes') }}</p>
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

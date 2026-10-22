<script setup>
import { watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    schedule: { type: Object, required: true },
    date: { type: String, required: true },
    students: { type: Array, required: true },
})

function entriesFrom(students) {
    return students.map((student) => ({
        student_id: student.id,
        attended: student.attended,
        performance_notes: '',
        test_title: '',
        test_score: '',
    }))
}

const form = useForm({
    date: props.date,
    entries: entriesFrom(props.students),
})

watch(() => props.students, (students) => {
    form.date = props.date
    form.entries = entriesFrom(students)
})

function changeDate() {
    router.get(`/teacher/schedules/${props.schedule.id}/attendance`, { date: form.date })
}

function submit() {
    form.post(`/teacher/schedules/${props.schedule.id}/attendance`)
}
</script>

<template>
    <Head :title="t('actions.attendance')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ schedule.course }}</h1>
        <p class="mt-2 text-[#44546F]">{{ schedule.start_time }}–{{ schedule.end_time }} · {{ schedule.room }}</p>
        <label class="mt-4 block max-w-xs text-sm">{{ t('fields.date') }}
            <input v-model="form.date" class="field mt-1" type="date" @change="changeDate">
        </label>
        <form class="mt-6 space-y-3" @submit.prevent="submit">
            <article v-for="(entry, index) in form.entries" :key="entry.student_id" class="card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ students[index].name }}</h2>
                        <p class="text-sm text-[#44546F]">
                            {{ students[index].package_title ? t('teacher.sessions_left', { package: students[index].package_title, count: students[index].remaining_sessions }) : t('teacher.no_session_pack') }}
                        </p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="entry.attended" type="checkbox">
                        {{ t('fields.present') }}
                    </label>
                </div>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <label class="text-sm md:col-span-3">{{ t('fields.note') }}
                        <textarea v-model="entry.performance_notes" class="field mt-1" rows="2"></textarea>
                    </label>
                    <label class="text-sm">{{ t('fields.test') }}<input v-model="entry.test_title" class="field mt-1"></label>
                    <label class="text-sm">{{ t('fields.score') }}<input v-model="entry.test_score" class="field mt-1" :placeholder="t('common.score_hint')"></label>
                </div>
            </article>
            <p v-if="form.errors.entries" class="error">{{ form.errors.entries }}</p>
            <button class="btn" :disabled="form.processing">{{ t('actions.save_attendance') }}</button>
        </form>
    </AppLayout>
</template>

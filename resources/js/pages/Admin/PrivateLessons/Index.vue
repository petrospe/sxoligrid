<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    lessons: { type: Array, required: true },
    students: { type: Array, required: true },
    teachers: { type: Array, required: true },
    statuses: { type: Array, required: true },
})

const form = useForm({
    student_id: '',
    teacher_id: '',
    scheduled_at: '',
    duration_minutes: 60,
    status: 'confirmed',
})

function submit() {
    form.post('/admin/private-lessons', { onSuccess: () => form.reset('student_id', 'teacher_id', 'scheduled_at') })
}

function changeStatus(lesson, status) {
    router.patch(`/admin/private-lessons/${lesson.id}`, { status })
}
</script>

<template>
    <Head :title="t('nav.private_lessons')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.private_lessons') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.teacher') }}
                <select v-model="form.teacher_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.when') }}<input v-model="form.scheduled_at" class="field mt-1" type="datetime-local" required></label>
            <label class="text-sm">{{ t('fields.minutes') }}<input v-model="form.duration_minutes" class="field mt-1" type="number" min="15" max="480" required></label>
            <label class="text-sm">{{ t('fields.status') }}
                <select v-model="form.status" class="field mt-1">
                    <option v-for="status in statuses" :key="status" :value="status">{{ t('lesson_statuses.' + status) }}</option>
                </select>
            </label>
            <div class="md:col-span-2"><button class="btn" :disabled="form.processing">{{ t('actions.record') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.when') }}</th><th>{{ t('fields.student') }}</th><th>{{ t('fields.teacher') }}</th><th>{{ t('fields.minutes') }}</th><th>{{ t('fields.status') }}</th></tr></thead>
                <tbody>
                    <tr v-for="lesson in lessons" :key="lesson.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ lesson.scheduled_at }}</td>
                        <td>{{ lesson.student }}</td>
                        <td>{{ lesson.teacher }}</td>
                        <td>{{ lesson.duration_minutes }}</td>
                        <td>
                            <select class="field" :value="lesson.status" @change="changeStatus(lesson, $event.target.value)">
                                <option v-for="status in statuses" :key="status" :value="status">{{ t('lesson_statuses.' + status) }}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

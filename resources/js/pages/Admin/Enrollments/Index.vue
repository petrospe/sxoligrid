<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    enrollments: { type: Array, required: true },
    students: { type: Array, required: true },
    courses: { type: Array, required: true },
    statuses: { type: Array, required: true },
})

const form = useForm({
    student_id: '',
    course_id: '',
    status: 'active',
})

function submit() {
    form.post('/admin/enrollments', { onSuccess: () => form.reset('student_id', 'course_id') })
}

function changeStatus(enrollment, status) {
    router.patch(`/admin/enrollments/${enrollment.id}`, { status })
}

function remove(enrollment) {
    if (window.confirm(t('confirm.delete_enrollment'))) {
        router.delete(`/admin/enrollments/${enrollment.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.enrollments')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.enrollments') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.student') }}
                <select v-model="form.student_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }}</option>
                </select>
                <span v-if="form.errors.student_id" class="error">{{ form.errors.student_id }}</span>
            </label>
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.status') }}
                <select v-model="form.status" class="field mt-1">
                    <option v-for="status in statuses" :key="status" :value="status">{{ t('enrollment_statuses.' + status) }}</option>
                </select>
            </label>
            <div class="md:col-span-3"><button class="btn" :disabled="form.processing">{{ t('admin.enroll') }}</button></div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.student') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.status') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="enrollment in enrollments" :key="enrollment.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ enrollment.student.name }}</td>
                        <td>{{ enrollment.course.title }}</td>
                        <td>
                            <select class="field" :value="enrollment.status" @change="changeStatus(enrollment, $event.target.value)">
                                <option v-for="status in statuses" :key="status" :value="status">{{ t('enrollment_statuses.' + status) }}</option>
                            </select>
                        </td>
                        <td class="text-right"><button class="btn-danger" type="button" @click="remove(enrollment)">{{ t('actions.delete') }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import WeeklyGrid from '@/Components/WeeklyGrid.vue'
import { dayValues, useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
    schedules: { type: Array, required: true },
    courses: { type: Array, required: true },
    teachers: { type: Array, required: true },
})

const form = useForm({
    course_id: '',
    teacher_ids: [],
    day_of_week: 1,
    start_time: '17:00',
    end_time: '18:00',
    room: '',
    visible_on_landing: true,
})

function teacherNames(item) {
    const names = Array.isArray(item.teachers)
        ? item.teachers.map((teacher) => teacher.name).filter(Boolean)
        : []

    return names.length > 0 ? names.join(', ') : '—'
}

function submit() {
    form.post('/admin/schedules', { onSuccess: () => form.reset('room') })
}

function remove(id) {
    if (window.confirm(t('confirm.delete_slot'))) {
        router.delete(`/admin/schedules/${id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.timetable')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.timetable') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.course') }}
                <select v-model="form.course_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>
            </label>
            <fieldset class="text-sm md:col-span-3">
                <legend>{{ t('fields.teachers') }}</legend>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                    <label v-for="teacher in teachers" :key="teacher.id" class="flex items-center gap-2">
                        <input v-model="form.teacher_ids" :value="teacher.id" class="h-4 w-4" type="checkbox">
                        {{ teacher.name }}
                    </label>
                </div>
                <span v-if="form.errors.teacher_ids" class="error">{{ form.errors.teacher_ids }}</span>
            </fieldset>
            <label class="text-sm">{{ t('fields.day') }}
                <select v-model="form.day_of_week" class="field mt-1">
                    <option v-for="day in dayValues" :key="day" :value="day">{{ t('days.' + day) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.from') }}
                <input v-model="form.start_time" class="field mt-1" type="time" required>
            </label>
            <label class="text-sm">{{ t('fields.until') }}
                <input v-model="form.end_time" class="field mt-1" type="time" required>
                <span v-if="form.errors.end_time" class="error">{{ form.errors.end_time }}</span>
            </label>
            <label class="text-sm">{{ t('fields.room') }}
                <input v-model="form.room" class="field mt-1" required>
            </label>
            <label class="flex items-center gap-2 text-sm md:col-span-3">
                <input v-model="form.visible_on_landing" class="h-4 w-4" type="checkbox">
                {{ t('admin.show_on_landing') }}
            </label>
            <div class="md:col-span-3">
                <button class="btn" :disabled="form.processing">{{ t('actions.add') }}</button>
            </div>
        </form>
        <div class="mt-6">
            <WeeklyGrid :schedules="schedules" show-teacher />
        </div>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.day') }}</th><th>{{ t('fields.time') }}</th><th>{{ t('fields.course') }}</th><th>{{ t('fields.teachers') }}</th><th>{{ t('fields.room') }}</th><th>{{ t('admin.show_on_landing') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in schedules" :key="item.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ t('days.' + item.day_of_week) }}</td>
                        <td>{{ item.start_time }}–{{ item.end_time }}</td>
                        <td>{{ item.course.title }}</td>
                        <td>{{ teacherNames(item) }}</td>
                        <td>{{ item.room }}</td>
                        <td>{{ item.visible_on_landing ? t('common.yes') : t('common.no') }}</td>
                        <td class="space-x-2 text-right">
                            <Link class="btn-soft" :href="`/admin/schedules/${item.id}/edit`">{{ t('actions.edit') }}</Link>
                            <button class="btn-danger" type="button" @click="remove(item.id)">{{ t('actions.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

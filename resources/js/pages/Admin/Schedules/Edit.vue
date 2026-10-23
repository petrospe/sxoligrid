<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { dayValues, useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    schedule: { type: Object, required: true },
    sections: { type: Array, required: true },
    teachers: { type: Array, required: true },
})

const form = useForm({
    section_id: props.schedule.section_id,
    teacher_ids: props.schedule.teacher_ids ?? [],
    day_of_week: props.schedule.day_of_week,
    start_time: props.schedule.start_time,
    end_time: props.schedule.end_time,
    room: props.schedule.room,
    visible_on_landing: props.schedule.visible_on_landing !== false,
})

function submit() {
    form.put(`/admin/schedules/${props.schedule.id}`)
}
</script>

<template>
    <Head :title="t('admin.edit_timetable')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.edit_timetable') }}</h1>
        <form class="card mt-6 grid max-w-2xl gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.section') }}
                <select v-model="form.section_id" class="field mt-1">
                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.label }}</option>
                </select>
            </label>
            <fieldset class="text-sm">
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
            <label class="text-sm">{{ t('fields.from') }}<input v-model="form.start_time" class="field mt-1" type="time" required></label>
            <label class="text-sm">{{ t('fields.until') }}
                <input v-model="form.end_time" class="field mt-1" type="time" required>
                <span v-if="form.errors.end_time" class="error">{{ form.errors.end_time }}</span>
            </label>
            <label class="text-sm">{{ t('fields.room') }}<input v-model="form.room" class="field mt-1" required></label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.visible_on_landing" class="h-4 w-4" type="checkbox">
                {{ t('admin.show_on_landing') }}
            </label>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.save') }}</button>
        </form>
    </AppLayout>
</template>

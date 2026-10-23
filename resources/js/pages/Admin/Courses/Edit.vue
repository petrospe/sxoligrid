<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
    course: { type: Object, required: true },
    types: { type: Array, required: true },
})

const { t } = useI18n()

const form = useForm({
    title: props.course.title,
    description: props.course.description ?? '',
    type: props.course.type,
})

function submit() {
    form.put(`/admin/courses/${props.course.id}`)
}
</script>

<template>
    <Head :title="course.title" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.edit_course') }}</h1>
        <form class="card mt-6 grid max-w-2xl gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}
                <input v-model="form.title" class="field mt-1" required>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('course_types.' + type) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.description') }}
                <textarea v-model="form.description" class="field mt-1" rows="3"></textarea>
            </label>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.save') }}</button>
        </form>
    </AppLayout>
</template>
